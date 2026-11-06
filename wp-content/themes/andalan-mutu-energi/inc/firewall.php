<?php
/**
 * Lightweight application-layer attack detection and automatic response.
 *
 * This is deliberately a small WordPress-level control, not a replacement for
 * a server WAF, rate limiter, or malware scanner. IP addresses are used only
 * in memory and stored as keyed hashes; raw payloads are never logged.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AME_FIREWALL_DB_VERSION', '1.0' );

/**
 * Return the per-site security event table name.
 *
 * @return string
 */
function ame_firewall_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'ame_security_events';
}

/**
 * Create or update the security event table.
 *
 * @return void
 */
function ame_firewall_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table           = ame_firewall_table_name();
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event_type varchar(40) NOT NULL,
		severity varchar(16) NOT NULL DEFAULT 'medium',
		ip_hash char(64) NOT NULL DEFAULT '',
		request_uri varchar(255) NOT NULL DEFAULT '',
		details varchar(255) NOT NULL DEFAULT '',
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY created_at (created_at),
		KEY event_type (event_type),
		KEY ip_hash (ip_hash)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'ame_firewall_db_version', AME_FIREWALL_DB_VERSION, false );
}

/**
 * Install the table after an update when an administrator visits wp-admin.
 *
 * @return void
 */
function ame_firewall_maybe_install() {
	if ( current_user_can( 'manage_options' ) && AME_FIREWALL_DB_VERSION !== get_option( 'ame_firewall_db_version' ) ) {
		ame_firewall_install();
	}
}
add_action( 'admin_init', 'ame_firewall_maybe_install' );

/**
 * Check whether the security event table exists.
 *
 * @return bool
 */
function ame_firewall_table_exists() {
	global $wpdb;
	$table = ame_firewall_table_name();
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	return $table === $found;
}

/**
 * Return the direct client address, without trusting forwarding headers.
 *
 * A reverse proxy must be configured at the server level if the site needs
 * client-IP-based controls behind that proxy.
 *
 * @return string
 */
function ame_firewall_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/**
 * Hash an IP for counters and block records.
 *
 * @param string $ip Optional IP; defaults to the current client.
 * @return string Empty when no valid IP is available.
 */
function ame_firewall_ip_hash( $ip = '' ) {
	if ( '' === $ip ) {
		$ip = ame_firewall_client_ip();
	}
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return '';
	}
	return hash_hmac( 'sha256', 'ame-firewall|' . $ip, wp_salt( 'auth' ) );
}

/**
 * Return a query-free, bounded request path for logs.
 *
 * @return string
 */
function ame_firewall_request_path() {
	$raw_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$path    = wp_parse_url( $raw_uri, PHP_URL_PATH );
	$path    = is_string( $path ) && $path ? sanitize_text_field( $path ) : '/';
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $path, 0, 255 );
	}
	return substr( $path, 0, 255 );
}

/**
 * Whether the master firewall switch is enabled.
 *
 * @return bool
 */
function ame_firewall_is_enabled() {
	return (bool) get_theme_mod( 'ame_firewall_enabled', true );
}

/**
 * Return the configured login failure threshold.
 *
 * @return int
 */
function ame_firewall_login_limit() {
	return max( 3, min( 20, absint( get_theme_mod( 'ame_login_attempt_limit', 5 ) ) ) );
}

/**
 * Return the configured 404 threshold.
 *
 * @return int
 */
function ame_firewall_404_limit() {
	return max( 5, min( 100, absint( get_theme_mod( 'ame_404_attempt_limit', 20 ) ) ) );
}

/**
 * Return the shared temporary block duration.
 *
 * @return int
 */
function ame_firewall_lockout_minutes() {
	return max( 5, min( 120, absint( get_theme_mod( 'ame_security_lockout_minutes', 15 ) ) ) );
}

/**
 * Return active temporary blocks and prune expired records.
 *
 * Block records contain only a keyed IP hash, reason, and timestamps.
 *
 * @return array<string, array<string, int|string>>
 */
function ame_firewall_get_blocks() {
	$blocks = get_option( 'ame_security_blocks', array() );
	if ( ! is_array( $blocks ) ) {
		$blocks = array();
	}

	$now     = time();
	$changed = false;
	foreach ( $blocks as $hash => $block ) {
		if ( ! is_array( $block ) || empty( $block['expires'] ) || (int) $block['expires'] <= $now ) {
			unset( $blocks[ $hash ] );
			$changed = true;
		}
	}
	if ( $changed ) {
		update_option( 'ame_security_blocks', $blocks, false );
	}
	return $blocks;
}

/**
 * Check whether a client hash has an active temporary block.
 *
 * @param string $hash Optional keyed IP hash.
 * @return bool
 */
function ame_firewall_is_blocked( $hash = '' ) {
	if ( ! ame_firewall_is_enabled() ) {
		return false;
	}
	if ( '' === $hash ) {
		$hash = ame_firewall_ip_hash();
	}
	if ( ! $hash ) {
		return false;
	}
	$blocks = ame_firewall_get_blocks();
	return isset( $blocks[ $hash ] ) && (int) $blocks[ $hash ]['expires'] > time();
}

/**
 * Add or refresh a temporary block.
 *
 * @param string $hash   Keyed IP hash.
 * @param string $reason Internal reason code.
 * @return void
 */
function ame_firewall_block_ip( $hash, $reason ) {
	if ( ! preg_match( '/^[a-f0-9]{64}$/', $hash ) ) {
		return;
	}

	$blocks = ame_firewall_get_blocks();
	$now    = time();
	$blocks[ $hash ] = array(
		'reason'  => sanitize_key( $reason ),
		'created' => $now,
		'expires' => $now + ( ame_firewall_lockout_minutes() * MINUTE_IN_SECONDS ),
	);

	// Bound the option even when an attack uses many different source IPs.
	if ( count( $blocks ) > 100 ) {
		uasort(
			$blocks,
			function( $a, $b ) {
				return (int) $a['created'] <=> (int) $b['created'];
			}
		);
		$blocks = array_slice( $blocks, -100, null, true );
	}
	update_option( 'ame_security_blocks', $blocks, false );
}

/**
 * Return a transient counter key containing no raw IP.
 *
 * @param string $type Counter type.
 * @param string $hash Keyed IP hash.
 * @return string
 */
function ame_firewall_counter_key( $type, $hash ) {
	return 'ame_fw_' . sanitize_key( $type ) . '_' . $hash;
}

/**
 * Increment a bounded sliding-window counter.
 *
 * @param string $type   Counter type.
 * @param string $hash   Keyed IP hash.
 * @param int    $window Lifetime in seconds.
 * @return int
 */
function ame_firewall_increment_counter( $type, $hash, $window ) {
	$key   = ame_firewall_counter_key( $type, $hash );
	$count = get_transient( $key );
	$count = false === $count ? 1 : (int) $count + 1;
	set_transient( $key, $count, max( 60, (int) $window ) );
	return $count;
}

/**
 * Clear one temporary block and its associated counters.
 *
 * @param string $hash Keyed IP hash.
 * @return bool
 */
function ame_firewall_clear_block( $hash ) {
	if ( ! preg_match( '/^[a-f0-9]{64}$/', $hash ) ) {
		return false;
	}
	$blocks = ame_firewall_get_blocks();
	if ( ! isset( $blocks[ $hash ] ) ) {
		return false;
	}
	unset( $blocks[ $hash ] );
	update_option( 'ame_security_blocks', $blocks, false );
	delete_transient( ame_firewall_counter_key( 'login', $hash ) );
	delete_transient( ame_firewall_counter_key( '404', $hash ) );
	return true;
}

/**
 * Clear all temporary blocks and their counters.
 *
 * @return int Number of blocks removed.
 */
function ame_firewall_clear_blocks() {
	$blocks = ame_firewall_get_blocks();
	foreach ( array_keys( $blocks ) as $hash ) {
		delete_transient( ame_firewall_counter_key( 'login', $hash ) );
		delete_transient( ame_firewall_counter_key( '404', $hash ) );
	}
	update_option( 'ame_security_blocks', array(), false );
	return count( $blocks );
}

/**
 * Prune old security events and cap the retained log at 1,000 rows.
 *
 * @return void
 */
function ame_firewall_prune_logs() {
	$today = current_time( 'Y-m-d' );
	if ( $today === get_transient( 'ame_firewall_pruned_on' ) || AME_FIREWALL_DB_VERSION !== get_option( 'ame_firewall_db_version' ) ) {
		return;
	}

	global $wpdb;
	$table  = ame_firewall_table_name();
	$cutoff = current_datetime()->sub( new DateInterval( 'P30D' ) )->format( 'Y-m-d H:i:s' );
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) );
	$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	if ( $count > 1000 ) {
		$delete = $count - 1000;
		$wpdb->query( "DELETE FROM {$table} ORDER BY id ASC LIMIT {$delete}" );
	}
	set_transient( 'ame_firewall_pruned_on', $today, DAY_IN_SECONDS );
}

/**
 * Store a security event without storing payloads or raw identities.
 *
 * @param string $event_type Event code.
 * @param string $severity   low, medium, or high.
 * @param string $ip_hash    Keyed IP hash, if available.
 * @param string $details    Internal detail code.
 * @return void
 */
function ame_firewall_log_event( $event_type, $severity, $ip_hash = '', $details = '' ) {
	if ( AME_FIREWALL_DB_VERSION !== get_option( 'ame_firewall_db_version' ) ) {
		return;
	}

	global $wpdb;
	$severity = in_array( $severity, array( 'low', 'medium', 'high' ), true ) ? $severity : 'medium';
	$details  = function_exists( 'mb_substr' ) ? mb_substr( sanitize_text_field( $details ), 0, 255 ) : substr( sanitize_text_field( $details ), 0, 255 );
	$ip_hash  = preg_match( '/^[a-f0-9]{64}$/', (string) $ip_hash ) ? $ip_hash : '';
	$wpdb->insert(
		ame_firewall_table_name(),
		array(
			'event_type'  => sanitize_key( $event_type ),
			'severity'    => $severity,
			'ip_hash'     => $ip_hash,
			'request_uri' => ame_firewall_request_path(),
			'details'     => $details,
			'created_at'  => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s' )
	);
	ame_firewall_prune_logs();
}

/**
 * Return whether the current request is a WordPress login request.
 *
 * @return bool
 */
function ame_firewall_is_login_request() {
	global $pagenow;
	return 'wp-login.php' === $pagenow;
}

/**
 * Detect a small set of high-confidence attack signatures.
 *
 * The result is an internal code, never the submitted payload.
 *
 * @param string $payload Decoded request data.
 * @return string
 */
function ame_firewall_detect_pattern( $payload ) {
	$patterns = array(
		'path_traversal'   => '/\.\.[\/\\\\]|\x00/i',
		'sql_injection'    => '/\bunion\s+(?:all\s+)?select\b|\binformation_schema\b|\b(?:sleep|benchmark|extractvalue|updatexml)\s*\(/i',
		'xss'              => '/<script\b|javascript\s*:|\bon(?:error|load|click)\s*=|\bsrcdoc\s*=/i',
		'file_inclusion'   => '~(?:php|expect|data|file):\/\/|\/etc\/passwd|wp-config\.php~i',
		'command_injection' => '/\$\(|;\s*(?:id|whoami|uname|cat)\b|\|\s*(?:id|whoami|cat)\b/i',
	);
	foreach ( $patterns as $name => $pattern ) {
		if ( preg_match( $pattern, $payload ) ) {
			return $name;
		}
	}
	return '';
}

/**
 * Build bounded detection input from the URI and safe front-end POST fields.
 *
 * @return string
 */
function ame_firewall_request_payload() {
	$raw_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$method  = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
	$payload = $raw_uri;

	// Do not inspect passwords, admin forms, login forms, or REST payloads.
	if ( 'POST' === $method && ! is_admin() && ! ame_firewall_is_login_request() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		foreach ( $_POST as $key => $value ) {
			if ( preg_match( '/pass|pwd|nonce|token|secret|auth/i', (string) $key ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$value = wp_json_encode( $value );
			}
			if ( is_scalar( $value ) ) {
				$payload .= '&' . (string) $key . '=' . (string) $value;
			}
		}
	}

	$payload = substr( $payload, 0, 16384 );
	for ( $i = 0; $i < 2; $i++ ) {
		$payload = urldecode( $payload );
	}
	return strtolower( $payload );
}

/**
 * Send a generic 403 without disclosing the matched signature.
 *
 * @return void
 */
function ame_firewall_reject_request() {
	status_header( 403 );
	nocache_headers();
	wp_die(
		esc_html__( 'Permintaan dibatasi sementara oleh perlindungan keamanan. Silakan coba lagi nanti.', 'andalan-mutu-energi' ),
		esc_html__( 'Akses dibatasi', 'andalan-mutu-energi' ),
		array(
			'response'  => 403,
			'back_link' => false,
		)
	);
}

/**
 * Whether the current process should not be interrupted by the firewall.
 *
 * @return bool
 */
function ame_firewall_is_exempt_process() {
	return ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_INSTALLING' ) && WP_INSTALLING );
}

/**
 * Enforce an existing temporary block early in the request.
 *
 * Administrators are allowed through so they can recover from a bad block.
 *
 * @return void
 */
function ame_firewall_enforce_block() {
	if ( ! ame_firewall_is_enabled() || ame_firewall_is_exempt_process() || current_user_can( 'manage_options' ) ) {
		return;
	}
	$hash = ame_firewall_ip_hash();
	if ( $hash && ame_firewall_is_blocked( $hash ) ) {
		ame_firewall_reject_request();
	}
}
add_action( 'init', 'ame_firewall_enforce_block', 1 );

/**
 * Inspect public requests for high-confidence attack signatures.
 *
 * @return void
 */
function ame_firewall_inspect_request() {
	if ( ! ame_firewall_is_enabled() || ame_firewall_is_exempt_process() || is_admin() || ame_firewall_is_login_request() || current_user_can( 'manage_options' ) ) {
		return;
	}
	$pattern = ame_firewall_detect_pattern( ame_firewall_request_payload() );
	if ( ! $pattern ) {
		return;
	}

	$hash = ame_firewall_ip_hash();
	ame_firewall_log_event( 'malicious_request', 'high', $hash, $pattern );
	if ( $hash ) {
		ame_firewall_block_ip( $hash, 'malicious_request' );
	}
	ame_firewall_reject_request();
}
add_action( 'init', 'ame_firewall_inspect_request', 2 );

/**
 * Record a failed credential authentication and block at the threshold.
 *
 * @param string $hash Keyed IP hash.
 * @return bool Whether the failure caused a block.
 */
function ame_firewall_record_login_failure( $hash ) {
	if ( ! $hash || ame_firewall_is_blocked( $hash ) ) {
		return false;
	}
	$count = ame_firewall_increment_counter( 'login', $hash, 15 * MINUTE_IN_SECONDS );
	if ( $count < ame_firewall_login_limit() ) {
		ame_firewall_log_event( 'login_failed', 'medium', $hash, 'attempt_' . $count );
		return false;
	}

	ame_firewall_block_ip( $hash, 'login_failures' );
	ame_firewall_log_event( 'login_lockout', 'high', $hash, 'threshold_' . $count );
	return true;
}

/**
 * Gate credential authentication and count standard WordPress failures.
 *
 * Running late covers wp-login and other consumers of the authenticate filter;
 * no username or password is persisted.
 *
 * @param WP_User|WP_Error|null $user     Authentication result.
 * @param string                $username Submitted username, unused.
 * @param string                $password Submitted password, unused.
 * @return WP_User|WP_Error|null
 */
function ame_firewall_authenticate( $user, $username, $password ) {
	if ( ! ame_firewall_is_enabled() || ! get_theme_mod( 'ame_login_protection_enabled', true ) ) {
		return $user;
	}

	$hash = ame_firewall_ip_hash();
	if ( $hash && ame_firewall_is_blocked( $hash ) ) {
		return new WP_Error( 'ame_temporarily_blocked', __( 'Terlalu banyak percobaan. Akses login dibatasi sementara. Silakan coba lagi nanti.', 'andalan-mutu-energi' ) );
	}
	if ( $user instanceof WP_User ) {
		if ( $hash ) {
			delete_transient( ame_firewall_counter_key( 'login', $hash ) );
		}
		return $user;
	}

	if ( is_wp_error( $user ) && in_array( $user->get_error_code(), array( 'invalid_username', 'invalid_email', 'incorrect_password', 'empty_username', 'empty_password' ), true ) ) {
		if ( ame_firewall_record_login_failure( $hash ) ) {
			return new WP_Error( 'ame_temporarily_blocked', __( 'Terlalu banyak percobaan. Akses login dibatasi sementara. Silakan coba lagi nanti.', 'andalan-mutu-energi' ) );
		}
	}
	return $user;
}
add_filter( 'authenticate', 'ame_firewall_authenticate', 99, 3 );

/**
 * Detect repeated public 404s as a lightweight scan signal.
 *
 * @return void
 */
function ame_firewall_detect_404_scan() {
	if ( ! ame_firewall_is_enabled() || ! get_theme_mod( 'ame_404_protection_enabled', true ) || ! is_404() || is_admin() || is_user_logged_in() || is_feed() || is_robots() || is_preview() || is_customize_preview() || ame_firewall_is_exempt_process() ) {
		return;
	}
	if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
	if ( 'GET' !== $method ) {
		return;
	}

	$hash = ame_firewall_ip_hash();
	if ( ! $hash || ame_firewall_is_blocked( $hash ) ) {
		return;
	}
	$count = ame_firewall_increment_counter( '404', $hash, 10 * MINUTE_IN_SECONDS );
	if ( $count < ame_firewall_404_limit() ) {
		return;
	}

	ame_firewall_block_ip( $hash, '404_scan' );
	ame_firewall_log_event( '404_scan', 'high', $hash, 'threshold_' . $count );
	ame_firewall_reject_request();
}
add_action( 'template_redirect', 'ame_firewall_detect_404_scan', 20 );

/**
 * Register the security dashboard menu.
 *
 * @return void
 */
function ame_firewall_add_menu() {
	add_menu_page(
		__( 'Keamanan AME', 'andalan-mutu-energi' ),
		__( 'Keamanan AME', 'andalan-mutu-energi' ),
		'manage_options',
		'ame-security-dashboard',
		'ame_render_security_dashboard',
		'dashicons-shield',
		27
	);
}
add_action( 'admin_menu', 'ame_firewall_add_menu' );

/**
 * Return metrics and recent security activity.
 *
 * @return array<string, mixed>
 */
function ame_firewall_get_dashboard_data() {
	global $wpdb;
	$table       = ame_firewall_table_name();
	$today_start = current_datetime()->setTime( 0, 0 )->format( 'Y-m-d H:i:s' );
	$week_start  = current_datetime()->sub( new DateInterval( 'P6D' ) )->setTime( 0, 0 )->format( 'Y-m-d H:i:s' );

	$recent = $wpdb->get_results( "SELECT event_type, severity, ip_hash, request_uri, details, created_at FROM {$table} ORDER BY id DESC LIMIT 30", ARRAY_A );
	$trends = $wpdb->get_results(
		$wpdb->prepare( "SELECT event_type, COUNT(*) AS total FROM {$table} WHERE created_at >= %s GROUP BY event_type ORDER BY total DESC LIMIT 5", $week_start ),
		ARRAY_A
	);

	return array(
		'today_events' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", $today_start ) ),
		'high_today'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s AND severity = 'high'", $today_start ) ),
		'total_events' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
		'blocks'       => ame_firewall_get_blocks(),
		'recent'       => (array) $recent,
		'trends'       => (array) $trends,
	);
}

/**
 * Clear retained security events after capability and nonce checks.
 *
 * @return void
 */
function ame_firewall_clear_logs() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Anda tidak memiliki izin untuk menghapus log keamanan.', 'andalan-mutu-energi' ) );
	}
	check_admin_referer( 'ame_firewall_clear_logs', 'ame_firewall_nonce' );
	global $wpdb;
	$wpdb->query( 'TRUNCATE TABLE ' . ame_firewall_table_name() );
	wp_safe_redirect( admin_url( 'admin.php?page=ame-security-dashboard&logs_cleared=1' ) );
	exit;
}
add_action( 'admin_post_ame_firewall_clear_logs', 'ame_firewall_clear_logs' );

/**
 * Clear all temporary blocks after capability and nonce checks.
 *
 * @return void
 */
function ame_firewall_clear_blocks_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Anda tidak memiliki izin untuk membuka blokir.', 'andalan-mutu-energi' ) );
	}
	check_admin_referer( 'ame_firewall_clear_blocks', 'ame_firewall_nonce' );
	ame_firewall_clear_blocks();
	wp_safe_redirect( admin_url( 'admin.php?page=ame-security-dashboard&blocks_cleared=1' ) );
	exit;
}
add_action( 'admin_post_ame_firewall_clear_blocks', 'ame_firewall_clear_blocks_action' );

/**
 * Clear one selected temporary block after capability and nonce checks.
 *
 * @return void
 */
function ame_firewall_unblock_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Anda tidak memiliki izin untuk membuka blokir.', 'andalan-mutu-energi' ) );
	}
	check_admin_referer( 'ame_firewall_unblock', 'ame_firewall_nonce' );
	$hash = isset( $_POST['ip_hash'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_hash'] ) ) : '';
	if ( ame_firewall_clear_block( $hash ) ) {
		ame_firewall_log_event( 'manual_unblock', 'low', $hash, 'admin_action' );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=ame-security-dashboard&unblocked=1' ) );
	exit;
}
add_action( 'admin_post_ame_firewall_unblock', 'ame_firewall_unblock_action' );

/**
 * Return a translated label for an event code.
 *
 * @param string $type Event code.
 * @return string
 */
function ame_firewall_event_label( $type ) {
	$labels = array(
		'malicious_request' => __( 'Request berbahaya', 'andalan-mutu-energi' ),
		'login_failed'      => __( 'Login gagal', 'andalan-mutu-energi' ),
		'login_lockout'     => __( 'Blokir brute-force', 'andalan-mutu-energi' ),
		'login_failures'    => __( 'Brute-force login', 'andalan-mutu-energi' ),
		'404_scan'          => __( 'Pemindaian 404', 'andalan-mutu-energi' ),
		'manual_unblock'    => __( 'Blokir dibuka', 'andalan-mutu-energi' ),
	);
	return isset( $labels[ $type ] ) ? $labels[ $type ] : $type;
}

/**
 * Return a translated label for an event severity.
 *
 * @param string $severity Severity code.
 * @return string
 */
function ame_firewall_severity_label( $severity ) {
	$labels = array(
		'high'   => __( 'Tinggi', 'andalan-mutu-energi' ),
		'medium' => __( 'Sedang', 'andalan-mutu-energi' ),
		'low'    => __( 'Rendah', 'andalan-mutu-energi' ),
	);
	return isset( $labels[ $severity ] ) ? $labels[ $severity ] : $severity;
}

/**
 * Render the security monitoring and recovery dashboard.
 *
 * @return void
 */
function ame_render_security_dashboard() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! ame_firewall_table_exists() ) {
		ame_firewall_install();
	}
	ame_firewall_prune_logs();
	$data = ame_firewall_get_dashboard_data();
	?>
	<div class="wrap ame-analytics-wrap ame-security-wrap">
		<h1><?php esc_html_e( 'Keamanan AME', 'andalan-mutu-energi' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Deteksi application-layer dan respons otomatis untuk request berbahaya, brute-force login, dan pemindaian 404.', 'andalan-mutu-energi' ); ?></p>

		<?php if ( isset( $_GET['logs_cleared'] ) && '1' === $_GET['logs_cleared'] ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Log keamanan telah dihapus.', 'andalan-mutu-energi' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['blocks_cleared'] ) && '1' === $_GET['blocks_cleared'] ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Semua blokir sementara telah dibuka.', 'andalan-mutu-energi' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['unblocked'] ) && '1' === $_GET['unblocked'] ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Blokir yang dipilih telah dibuka dan counter terkait direset.', 'andalan-mutu-energi' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! ame_firewall_is_enabled() ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Firewall sedang nonaktif. Blokir tersimpan tidak diterapkan sampai firewall diaktifkan kembali.', 'andalan-mutu-energi' ); ?></p></div>
		<?php endif; ?>

		<div class="ame-analytics-toolbar">
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=ame_firewall_section' ) ); ?>"><?php esc_html_e( 'Pengaturan deteksi', 'andalan-mutu-energi' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ame-visitor-dashboard' ) ); ?>"><?php esc_html_e( 'Dashboard Pengunjung', 'andalan-mutu-energi' ); ?></a>
		</div>

		<div class="ame-analytics-cards">
			<div class="ame-analytics-card"><span><?php esc_html_e( 'Aktivitas hari ini', 'andalan-mutu-energi' ); ?></span><strong><?php echo esc_html( number_format_i18n( $data['today_events'] ) ); ?></strong></div>
			<div class="ame-analytics-card"><span><?php esc_html_e( 'Prioritas tinggi hari ini', 'andalan-mutu-energi' ); ?></span><strong><?php echo esc_html( number_format_i18n( $data['high_today'] ) ); ?></strong></div>
			<div class="ame-analytics-card"><span><?php esc_html_e( 'Blokir sementara aktif', 'andalan-mutu-energi' ); ?></span><strong><?php echo esc_html( number_format_i18n( count( $data['blocks'] ) ) ); ?></strong></div>
			<div class="ame-analytics-card"><span><?php esc_html_e( 'Log tersimpan', 'andalan-mutu-energi' ); ?></span><strong><?php echo esc_html( number_format_i18n( $data['total_events'] ) ); ?></strong></div>
		</div>

		<div class="ame-analytics-panel ame-security-policy">
			<h2><?php esc_html_e( 'Respons otomatis', 'andalan-mutu-energi' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'Pola request berbahaya: request ditolak 403 dan sumber diblokir sementara bila IP tersedia.', 'andalan-mutu-energi' ); ?></li>
				<li><?php echo esc_html( sprintf( __( 'Brute-force login: blokir setelah %d kegagalan dalam 15 menit; counter direset setelah login berhasil.', 'andalan-mutu-energi' ), ame_firewall_login_limit() ) ); ?></li>
				<li><?php echo esc_html( sprintf( __( 'Pemindaian 404: blokir setelah %d halaman tidak ditemukan dalam 10 menit.', 'andalan-mutu-energi' ), ame_firewall_404_limit() ) ); ?></li>
				<li><?php echo esc_html( sprintf( __( 'Semua blokir otomatis berakhir setelah %d menit. Administrator dapat membuka blokir lebih awal.', 'andalan-mutu-energi' ), ame_firewall_lockout_minutes() ) ); ?></li>
			</ul>
		</div>

		<div class="ame-analytics-panel">
			<div class="ame-analytics-panel-heading"><h2><?php esc_html_e( 'Blokir sementara aktif', 'andalan-mutu-energi' ); ?></h2><?php if ( $data['blocks'] ) : ?><form class="ame-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ame_firewall_clear_blocks"><?php wp_nonce_field( 'ame_firewall_clear_blocks', 'ame_firewall_nonce' ); ?><button class="button" type="submit"><?php esc_html_e( 'Buka semua blokir', 'andalan-mutu-energi' ); ?></button></form><?php endif; ?></div>
			<?php if ( $data['blocks'] ) : ?>
				<div class="ame-security-table-wrap"><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Hash IP', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Penyebab', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Berakhir', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Aksi', 'andalan-mutu-energi' ); ?></th></tr></thead><tbody>
				<?php foreach ( $data['blocks'] as $hash => $block ) : ?>
					<tr><td><code><?php echo esc_html( substr( $hash, 0, 16 ) . '…' ); ?></code></td><td><?php echo esc_html( ame_firewall_event_label( $block['reason'] ) ); ?></td><td><?php echo esc_html( wp_date( 'd M Y H:i', (int) $block['expires'] ) . ' (' . human_time_diff( time(), (int) $block['expires'] ) . ')' ); ?></td><td><form class="ame-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ame_firewall_unblock"><input type="hidden" name="ip_hash" value="<?php echo esc_attr( $hash ); ?>"><?php wp_nonce_field( 'ame_firewall_unblock', 'ame_firewall_nonce' ); ?><button class="button" type="submit"><?php esc_html_e( 'Buka blokir', 'andalan-mutu-energi' ); ?></button></form></td></tr>
				<?php endforeach; ?>
				</tbody></table></div>
			<?php else : ?>
				<p><?php esc_html_e( 'Tidak ada blokir sementara aktif.', 'andalan-mutu-energi' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="ame-analytics-panel">
			<h2><?php esc_html_e( 'Aktivitas keamanan terbaru', 'andalan-mutu-energi' ); ?></h2>
			<?php if ( $data['recent'] ) : ?>
				<div class="ame-security-table-wrap"><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Waktu', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Aktivitas', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Level', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Hash IP', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Path', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Detail', 'andalan-mutu-energi' ); ?></th></tr></thead><tbody>
				<?php foreach ( $data['recent'] as $event ) : ?>
					<tr><td><?php echo esc_html( mysql2date( 'd M Y H:i', $event['created_at'] ) ); ?></td><td><?php echo esc_html( ame_firewall_event_label( $event['event_type'] ) ); ?></td><td><span class="ame-security-severity-<?php echo esc_attr( $event['severity'] ); ?>"><?php echo esc_html( ame_firewall_severity_label( $event['severity'] ) ); ?></span></td><td><code><?php echo esc_html( $event['ip_hash'] ? substr( $event['ip_hash'], 0, 12 ) . '…' : '—' ); ?></code></td><td><code><?php echo esc_html( $event['request_uri'] ); ?></code></td><td><code><?php echo esc_html( $event['details'] ); ?></code></td></tr>
				<?php endforeach; ?>
				</tbody></table></div>
			<?php else : ?>
				<p><?php esc_html_e( 'Belum ada aktivitas yang tercatat.', 'andalan-mutu-energi' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="ame-analytics-panel">
			<h2><?php esc_html_e( 'Ringkasan 7 hari', 'andalan-mutu-energi' ); ?></h2>
			<?php if ( $data['trends'] ) : ?>
				<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Jenis aktivitas', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Jumlah', 'andalan-mutu-energi' ); ?></th></tr></thead><tbody><?php foreach ( $data['trends'] as $trend ) : ?><tr><td><?php echo esc_html( ame_firewall_event_label( $trend['event_type'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( (int) $trend['total'] ) ); ?></td></tr><?php endforeach; ?></tbody></table>
			<?php else : ?>
				<p><?php esc_html_e( 'Belum ada ringkasan aktivitas.', 'andalan-mutu-energi' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="ame-analytics-panel ame-analytics-privacy">
			<h2><?php esc_html_e( 'Batasan & privasi', 'andalan-mutu-energi' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'Hanya hash IP, kode aktivitas, path tanpa query, dan waktu yang disimpan. Payload, username, password, dan IP mentah tidak disimpan.', 'andalan-mutu-energi' ); ?></li>
				<li><?php esc_html_e( 'Log dipangkas setelah 30 hari dan dibatasi maksimal 1.000 baris. Header forwarding tidak dipercaya secara otomatis.', 'andalan-mutu-energi' ); ?></li>
				<li><?php esc_html_e( 'Ini adalah perlindungan application-layer ringan, bukan pengganti WAF/server rate limit, malware scanner, backup, atau update WordPress.', 'andalan-mutu-energi' ); ?></li>
			</ul>
			<?php if ( $data['total_events'] ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Hapus seluruh log keamanan secara permanen?', 'andalan-mutu-energi' ) ); ?>');"><input type="hidden" name="action" value="ame_firewall_clear_logs"><?php wp_nonce_field( 'ame_firewall_clear_logs', 'ame_firewall_nonce' ); ?><button class="button button-secondary" type="submit"><?php esc_html_e( 'Hapus seluruh log keamanan', 'andalan-mutu-energi' ); ?></button></form>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

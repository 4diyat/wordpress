<?php
/**
 * Privacy-conscious native visitor analytics and admin dashboard.
 *
 * No cookies or third-party requests are used. Only a daily rotating visitor
 * hash and a query-free request path are stored; raw IP addresses and user
 * agents are never written to the database.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AME_ANALYTICS_DB_VERSION', '1.0' );

/**
 * Return the per-site analytics table name.
 *
 * @return string
 */
function ame_analytics_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'ame_visitor_events';
}

/**
 * Create or update the analytics table.
 *
 * @return void
 */
function ame_analytics_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table           = ame_analytics_table_name();
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		visited_at datetime NOT NULL,
		visit_date date NOT NULL,
		visitor_hash char(64) NOT NULL,
		request_uri varchar(255) NOT NULL,
		post_id bigint(20) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY visit_date (visit_date),
		KEY date_visitor (visit_date,visitor_hash),
		KEY post_id (post_id)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'ame_analytics_db_version', AME_ANALYTICS_DB_VERSION, false );
}

/**
 * Install the table after a theme update when an administrator visits wp-admin.
 *
 * @return void
 */
function ame_analytics_maybe_install() {
	if ( current_user_can( 'manage_options' ) && AME_ANALYTICS_DB_VERSION !== get_option( 'ame_analytics_db_version' ) ) {
		ame_analytics_install();
	}
}
add_action( 'admin_init', 'ame_analytics_maybe_install' );

/**
 * Check whether the analytics table exists.
 *
 * @return bool
 */
function ame_analytics_table_exists() {
	global $wpdb;
	$table = ame_analytics_table_name();
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	return $table === $found;
}

/**
 * Return whether a user agent looks like an automated request.
 *
 * @param string $user_agent User agent string.
 * @return bool
 */
function ame_analytics_is_bot( $user_agent ) {
	if ( ! $user_agent ) {
		return true;
	}
	return (bool) preg_match( '/bot|crawl|spider|slurp|curl|wget|python|httpclient|headless|lighthouse|pingdom|preview|facebookexternalhit|slackbot/i', $user_agent );
}

/**
 * Remove events older than the configured retention period, at most once per day.
 *
 * @return void
 */
function ame_analytics_prune() {
	$today = current_time( 'Y-m-d' );
	if ( $today === get_transient( 'ame_analytics_pruned_on' ) ) {
		return;
	}

	global $wpdb;
	$table     = ame_analytics_table_name();
	$retention = max( 7, min( 365, absint( get_theme_mod( 'ame_analytics_retention_days', 90 ) ) ) );
	$cutoff    = current_datetime()->sub( new DateInterval( 'P' . $retention . 'D' ) )->format( 'Y-m-d' );
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE visit_date < %s", $cutoff ) );
	set_transient( 'ame_analytics_pruned_on', $today, DAY_IN_SECONDS );
}

/**
 * Record one eligible front-end GET request.
 *
 * @return void
 */
function ame_analytics_track_visit() {
	if ( ! get_theme_mod( 'ame_analytics_enabled', true ) || AME_ANALYTICS_DB_VERSION !== get_option( 'ame_analytics_db_version' ) ) {
		return;
	}
	if ( is_admin() || is_user_logged_in() || is_feed() || is_robots() || is_404() || is_preview() || is_customize_preview() ) {
		return;
	}
	if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		return;
	}
	if ( ( isset( $_SERVER['HTTP_DNT'] ) && '1' === $_SERVER['HTTP_DNT'] ) || ( isset( $_SERVER['HTTP_SEC_GPC'] ) && '1' === $_SERVER['HTTP_SEC_GPC'] ) ) {
		return;
	}

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
	if ( 'GET' !== $method ) {
		return;
	}
	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	if ( ame_analytics_is_bot( $user_agent ) ) {
		return;
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		$ip = 'unknown';
	}
	$raw_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$path    = wp_parse_url( $raw_uri, PHP_URL_PATH );
	$path    = $path ? sanitize_text_field( $path ) : '/';
	$path    = $path ? substr( $path, 0, 255 ) : '/';
	$today   = current_time( 'Y-m-d' );

	global $wpdb;
	$visitor_hash = hash_hmac( 'sha256', $ip . '|' . $user_agent . '|' . $today, wp_salt( 'nonce' ) );
	$wpdb->insert(
		ame_analytics_table_name(),
		array(
			'visited_at'   => current_time( 'mysql' ),
			'visit_date'   => $today,
			'visitor_hash' => $visitor_hash,
			'request_uri'  => $path,
			'post_id'      => is_singular() ? get_queried_object_id() : 0,
		),
		array( '%s', '%s', '%s', '%s', '%d' )
	);
	ame_analytics_prune();
}
add_action( 'template_redirect', 'ame_analytics_track_visit', 1 );

/**
 * Register the visitor dashboard menu.
 *
 * @return void
 */
function ame_analytics_add_menu() {
	add_menu_page(
		__( 'Dashboard Pengunjung', 'andalan-mutu-energi' ),
		__( 'Pengunjung AME', 'andalan-mutu-energi' ),
		'manage_options',
		'ame-visitor-dashboard',
		'ame_render_visitor_dashboard',
		'dashicons-chart-area',
		26
	);
}
add_action( 'admin_menu', 'ame_analytics_add_menu' );

/**
 * Load dashboard-only styles.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function ame_analytics_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'toplevel_page_ame-visitor-dashboard', 'toplevel_page_ame-security-dashboard' ), true ) ) {
		return;
	}
	wp_enqueue_style(
		'andalan-mutu-energi-admin',
		get_template_directory_uri() . '/assets/css/admin.css',
		array(),
		AME_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'ame_analytics_admin_assets' );

/**
 * Get dashboard metrics for a bounded date range.
 *
 * @param int $days Number of days, including today.
 * @return array<string, mixed>
 */
function ame_analytics_get_dashboard_data( $days ) {
	global $wpdb;
	$table = ame_analytics_table_name();
	$today = current_time( 'Y-m-d' );
	$start = current_datetime()->sub( new DateInterval( 'P' . ( $days - 1 ) . 'D' ) )->format( 'Y-m-d' );

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT visit_date, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors FROM {$table} WHERE visit_date BETWEEN %s AND %s GROUP BY visit_date ORDER BY visit_date ASC",
			$start,
			$today
		),
		ARRAY_A
	);
	$by_date = array();
	foreach ( (array) $rows as $row ) {
		$by_date[ $row['visit_date'] ] = array(
			'views'    => (int) $row['views'],
			'visitors' => (int) $row['visitors'],
		);
	}

	$series   = array();
	$start_obj = new DateTimeImmutable( $start, wp_timezone() );
	for ( $index = 0; $index < $days; $index++ ) {
		$date = $start_obj->add( new DateInterval( 'P' . $index . 'D' ) );
		$key  = $date->format( 'Y-m-d' );
		$day  = isset( $by_date[ $key ] ) ? $by_date[ $key ] : array( 'views' => 0, 'visitors' => 0 );
		$series[] = array(
			'date'     => $key,
			'label'    => wp_date( 'd M', $date->getTimestamp() ),
			'full_label' => wp_date( 'd M Y', $date->getTimestamp() ),
			'views'    => $day['views'],
			'visitors' => $day['visitors'],
		);
	}

	$top_pages = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT request_uri, post_id, COUNT(*) AS views FROM {$table} WHERE visit_date BETWEEN %s AND %s GROUP BY request_uri, post_id ORDER BY views DESC LIMIT 8",
			$start,
			$today
		),
		ARRAY_A
	);
	foreach ( (array) $top_pages as $index => $page ) {
		$post_id = (int) $page['post_id'];
		$title   = $post_id ? get_the_title( $post_id ) : '';
		$url     = $post_id ? get_permalink( $post_id ) : '';
		$top_pages[ $index ]['views'] = (int) $page['views'];
		$top_pages[ $index ]['title'] = $title ? $title : $page['request_uri'];
		$top_pages[ $index ]['url']   = $url ? $url : home_url( $page['request_uri'] );
	}

	$today_stats = ! empty( $series[ $days - 1 ] ) ? $series[ $days - 1 ] : array( 'views' => 0, 'visitors' => 0 );
	return array(
		'series'          => $series,
		'top_pages'       => (array) $top_pages,
		'today_views'     => (int) $today_stats['views'],
		'today_visitors'  => (int) $today_stats['visitors'],
		'period_views'    => (int) array_sum( array_column( $series, 'views' ) ),
		'average_unique'  => (int) round( array_sum( array_column( $series, 'visitors' ) ) / max( 1, $days ) ),
		'total_views'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
	);
}

/**
 * Clear analytics after a capability and nonce check.
 *
 * @return void
 */
function ame_analytics_clear_data() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Anda tidak memiliki izin untuk menghapus data pengunjung.', 'andalan-mutu-energi' ) );
	}
	check_admin_referer( 'ame_clear_analytics', 'ame_analytics_nonce' );
	global $wpdb;
	$wpdb->query( 'TRUNCATE TABLE ' . ame_analytics_table_name() );
	wp_safe_redirect( admin_url( 'admin.php?page=ame-visitor-dashboard&cleared=1' ) );
	exit;
}
add_action( 'admin_post_ame_clear_analytics', 'ame_analytics_clear_data' );

/**
 * Render the visitor dashboard.
 *
 * @return void
 */
function ame_render_visitor_dashboard() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! ame_analytics_table_exists() ) {
		ame_analytics_install();
	}
	ame_analytics_prune();

	$days = isset( $_GET['range'] ) ? absint( wp_unslash( $_GET['range'] ) ) : 7;
	if ( ! in_array( $days, array( 7, 30, 90 ), true ) ) {
		$days = 7;
	}
	$data      = ame_analytics_get_dashboard_data( $days );
	$max_views = max( 1, max( array_column( $data['series'], 'views' ) ) );
	$label_every = (int) ceil( $days / 7 );
	?>
	<div class="wrap ame-analytics-wrap">
		<h1><?php esc_html_e( 'Dashboard Pengunjung', 'andalan-mutu-energi' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Statistik kunjungan front-end yang dikumpulkan secara lokal tanpa cookie atau layanan pihak ketiga.', 'andalan-mutu-energi' ); ?></p>

		<?php if ( isset( $_GET['cleared'] ) && '1' === $_GET['cleared'] ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Seluruh data pengunjung telah dihapus.', 'andalan-mutu-energi' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! get_theme_mod( 'ame_analytics_enabled', true ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Pelacakan sedang nonaktif. Data lama masih dapat dilihat sampai dihapus atau melewati masa retensi.', 'andalan-mutu-energi' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! $data['total_views'] ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Belum ada kunjungan yang tercatat. Data akan muncul setelah ada kunjungan front-end yang memenuhi aturan privasi.', 'andalan-mutu-energi' ); ?></p></div>
		<?php endif; ?>

		<div class="ame-analytics-toolbar">
			<div class="ame-analytics-ranges" aria-label="<?php esc_attr_e( 'Rentang waktu', 'andalan-mutu-energi' ); ?>">
				<?php foreach ( array( 7, 30, 90 ) as $option ) : ?>
					<a class="button <?php echo $days === $option ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=ame-visitor-dashboard&range=' . $option ) ); ?>"><?php echo esc_html( sprintf( __( '%d hari', 'andalan-mutu-energi' ), $option ) ); ?></a>
				<?php endforeach; ?>
			</div>
			<a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=ame_analytics_section' ) ); ?>"><?php esc_html_e( 'Pengaturan privasi', 'andalan-mutu-energi' ); ?></a>
		</div>

		<div class="ame-analytics-cards">
			<div class="ame-analytics-card"><span><?php esc_html_e( 'Tayangan hari ini', 'andalan-mutu-energi' ); ?></span><strong><?php echo esc_html( number_format_i18n( $data['today_views'] ) ); ?></strong></div>
			<div class="ame-analytics-card"><span><?php esc_html_e( 'Unik hari ini (estimasi)', 'andalan-mutu-energi' ); ?></span><strong><?php echo esc_html( number_format_i18n( $data['today_visitors'] ) ); ?></strong></div>
			<div class="ame-analytics-card"><span><?php echo esc_html( sprintf( __( 'Tayangan %d hari', 'andalan-mutu-energi' ), $days ) ); ?></span><strong><?php echo esc_html( number_format_i18n( $data['period_views'] ) ); ?></strong></div>
			<div class="ame-analytics-card"><span><?php esc_html_e( 'Rata-rata unik per hari', 'andalan-mutu-energi' ); ?></span><strong><?php echo esc_html( number_format_i18n( $data['average_unique'] ) ); ?></strong></div>
		</div>

		<div class="ame-analytics-panel">
			<div class="ame-analytics-panel-heading"><h2><?php esc_html_e( 'Tren kunjungan', 'andalan-mutu-energi' ); ?></h2><span><?php echo esc_html( sprintf( __( 'Total tersimpan: %s tayangan', 'andalan-mutu-energi' ), number_format_i18n( $data['total_views'] ) ) ); ?></span></div>
			<div class="ame-analytics-legend"><span><i class="is-views"></i><?php esc_html_e( 'Tayangan', 'andalan-mutu-energi' ); ?></span><span><i class="is-visitors"></i><?php esc_html_e( 'Unik harian', 'andalan-mutu-energi' ); ?></span></div>
			<div class="ame-analytics-chart" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Grafik kunjungan %d hari terakhir', 'andalan-mutu-energi' ), $days ) ); ?>">
				<?php foreach ( $data['series'] as $index => $day ) : ?>
					<div class="ame-analytics-day" title="<?php echo esc_attr( $day['full_label'] . ': ' . $day['views'] . ' tayangan, ' . $day['visitors'] . ' unik' ); ?>">
						<div class="ame-analytics-bars" aria-hidden="true"><span class="is-views" style="height:<?php echo (int) round( $day['views'] / $max_views * 100 ); ?>%"></span><span class="is-visitors" style="height:<?php echo (int) round( $day['visitors'] / $max_views * 100 ); ?>%"></span></div>
						<?php if ( 0 === $index % $label_every || count( $data['series'] ) - 1 === $index ) : ?><span class="ame-analytics-date"><?php echo esc_html( $day['label'] ); ?></span><?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<details class="ame-analytics-data"><summary><?php esc_html_e( 'Lihat data tabel', 'andalan-mutu-energi' ); ?></summary><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Tanggal', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Tayangan', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Unik harian', 'andalan-mutu-energi' ); ?></th></tr></thead><tbody><?php foreach ( $data['series'] as $day ) : ?><tr><td><?php echo esc_html( $day['full_label'] ); ?></td><td><?php echo esc_html( number_format_i18n( $day['views'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $day['visitors'] ) ); ?></td></tr><?php endforeach; ?></tbody></table></details>
		</div>

		<div class="ame-analytics-panel">
			<h2><?php esc_html_e( 'Halaman terpopuler', 'andalan-mutu-energi' ); ?></h2>
			<?php if ( $data['top_pages'] ) : ?>
				<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Halaman', 'andalan-mutu-energi' ); ?></th><th><?php esc_html_e( 'Tayangan', 'andalan-mutu-energi' ); ?></th></tr></thead><tbody><?php foreach ( $data['top_pages'] as $page ) : ?><tr><td><a href="<?php echo esc_url( $page['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $page['title'] ); ?></a><br><code><?php echo esc_html( $page['request_uri'] ); ?></code></td><td><?php echo esc_html( number_format_i18n( $page['views'] ) ); ?></td></tr><?php endforeach; ?></tbody></table>
			<?php else : ?>
				<p><?php esc_html_e( 'Belum ada data halaman pada rentang ini.', 'andalan-mutu-energi' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="ame-analytics-panel ame-analytics-privacy">
			<h2><?php esc_html_e( 'Privasi & retensi data', 'andalan-mutu-energi' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'Tanpa cookie dan tanpa permintaan ke layanan analitik eksternal.', 'andalan-mutu-energi' ); ?></li>
				<li><?php esc_html_e( 'IP dan user-agent tidak disimpan mentah; hanya hash berotasi harian yang disimpan untuk estimasi unik.', 'andalan-mutu-energi' ); ?></li>
				<li><?php esc_html_e( 'Query URL, bot, pengguna login, permintaan non-GET, dan pengunjung dengan DNT/Global Privacy Control tidak dicatat.', 'andalan-mutu-energi' ); ?></li>
				<li><?php echo esc_html( sprintf( __( 'Data otomatis dihapus setelah %d hari. Estimasi unik dapat berbeda saat pengunjung memakai proxy atau mengganti perangkat/browser.', 'andalan-mutu-energi' ), absint( get_theme_mod( 'ame_analytics_retention_days', 90 ) ) ) ); ?></li>
			</ul>
			<?php if ( $data['total_views'] ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Hapus seluruh data pengunjung secara permanen?', 'andalan-mutu-energi' ) ); ?>');">
					<input type="hidden" name="action" value="ame_clear_analytics">
					<?php wp_nonce_field( 'ame_clear_analytics', 'ame_analytics_nonce' ); ?>
					<button class="button button-secondary" type="submit"><?php esc_html_e( 'Hapus seluruh data pengunjung', 'andalan-mutu-energi' ); ?></button>
				</form>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

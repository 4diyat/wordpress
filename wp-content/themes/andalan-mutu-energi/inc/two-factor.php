<?php
/**
 * Email-based two-factor authentication for the native and front-end login.
 *
 * A password/CAPTCHA success creates a short-lived challenge instead of an
 * auth cookie. Only a keyed hash of the six-digit code is stored in a
 * transient; the raw code exists only in memory and in the outgoing email.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether email 2FA is enabled. The constant is an emergency recovery switch.
 *
 * @return bool
 */
function ame_2fa_is_enabled() {
	if ( defined( 'AME_DISABLE_2FA' ) && AME_DISABLE_2FA ) {
		return false;
	}
	return (bool) get_theme_mod( 'ame_2fa_email_enabled', true );
}

/**
 * Return a transient key for a public challenge ID.
 *
 * @param string $challenge_id Challenge ID.
 * @return string
 */
function ame_2fa_key( $challenge_id ) {
	return 'ame_2fa_' . hash_hmac( 'sha256', $challenge_id, wp_salt( 'auth' ) );
}

/**
 * Validate a public challenge ID.
 *
 * @param string $challenge_id Challenge ID.
 * @return bool
 */
function ame_2fa_valid_id( $challenge_id ) {
	return (bool) preg_match( '/^[A-Za-z0-9]{40}$/', $challenge_id );
}

/**
 * Retrieve an unexpired challenge.
 *
 * @param string $challenge_id Challenge ID.
 * @return array<string, mixed>|false
 */
function ame_2fa_get_challenge( $challenge_id ) {
	if ( ! is_string( $challenge_id ) || ! ame_2fa_valid_id( $challenge_id ) ) {
		return false;
	}
	$challenge = get_transient( ame_2fa_key( $challenge_id ) );
	if ( ! is_array( $challenge ) || empty( $challenge['user_id'] ) || empty( $challenge['expires'] ) || (int) $challenge['expires'] <= time() ) {
		if ( is_array( $challenge ) ) {
			delete_transient( ame_2fa_key( $challenge_id ) );
		}
		return false;
	}
	return $challenge;
}

/**
 * Delete a challenge.
 *
 * @param string $challenge_id Challenge ID.
 * @return void
 */
function ame_2fa_delete_challenge( $challenge_id ) {
	if ( is_string( $challenge_id ) && ame_2fa_valid_id( $challenge_id ) ) {
		delete_transient( ame_2fa_key( $challenge_id ) );
	}
}

/**
 * Generate a six-digit code.
 *
 * @return string
 */
function ame_2fa_generate_code() {
	return str_pad( (string) wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
}

/**
 * Hash a code with the user and challenge as context.
 *
 * @param int    $user_id      User ID.
 * @param string $challenge_id Challenge ID.
 * @param string $code         Plain code.
 * @return string
 */
function ame_2fa_code_hash( $user_id, $challenge_id, $code ) {
	return hash_hmac( 'sha256', $user_id . '|' . $challenge_id . '|' . $code, wp_salt( 'auth' ) );
}

/**
 * Return whether this is a supported interactive credential login.
 *
 * XML-RPC, REST, application passwords, CLI, and cron are intentionally not
 * given an interactive email challenge.
 *
 * @return bool
 */
function ame_2fa_is_login_request() {
	if ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		return false;
	}
	if ( ame_login_is_wp_login_submission() ) {
		return true;
	}
	global $pagenow;
	return 'admin-post.php' === $pagenow && 'ame_frontend_login' === ame_login_post_value( 'action' );
}

/**
 * Capture and validate the login flow context without storing credentials.
 *
 * @return array<string, string|bool>
 */
function ame_2fa_login_context() {
	global $auth_secure_cookie;

	$native = ame_login_is_wp_login_submission();
	$return = ame_login_post_value( 'ame_login_return' );
	if ( ! $return ) {
		$return = $native ? site_url( 'wp-login.php', 'login' ) : ame_login_custom_page_url();
	}
	if ( ! $return ) {
		$return = home_url( '/' );
	}
	$return = wp_validate_redirect( esc_url_raw( $return ), home_url( '/' ) );

	$redirect = ame_login_post_value( 'redirect_to' );
	if ( ! $redirect ) {
		$redirect = $native ? admin_url() : $return;
	}
	$redirect_fallback = $native ? admin_url() : $return;
	$redirect          = wp_validate_redirect( esc_url_raw( $redirect ), $redirect_fallback );

	return array(
		'return'        => $return,
		'redirect_to'   => $redirect,
		'remember'      => (bool) ame_login_post_value( 'rememberme' ),
		'native'        => $native,
		'secure_cookie' => isset( $auth_secure_cookie ) ? (bool) $auth_secure_cookie : is_ssl(),
	);
}

/**
 * Enforce a per-user email rate limit and consume one send slot.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function ame_2fa_consume_mail_slot( $user_id ) {
	$key   = 'ame_2fa_mail_rate_' . (int) $user_id;
	$count = get_transient( $key );
	$count = false === $count ? 0 : (int) $count;
	if ( $count >= 5 ) {
		return false;
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return true;
}

/**
 * Send a plain-text login code using WordPress mail.
 *
 * @param WP_User $user User object.
 * @param string  $code Plain code.
 * @return bool
 */
function ame_2fa_send_email( $user, $code ) {
	if ( ! is_email( $user->user_email ) ) {
		return false;
	}
	$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject   = sprintf( __( '[%s] Kode verifikasi login Anda', 'andalan-mutu-energi' ), $site_name );
	$message   = sprintf(
		__( "Halo %1\$s,\n\nKode verifikasi login Anda adalah: %2\$s\n\nKode berlaku selama 10 menit. Jangan berikan kode ini kepada siapa pun.\n\nJika Anda tidak mencoba masuk, abaikan email ini dan segera periksa keamanan akun Anda.\n\n%3\$s\n%4\$s", 'andalan-mutu-energi' ),
		$user->display_name,
		$code,
		$site_name,
		home_url( '/' )
	);
	return (bool) wp_mail( $user->user_email, $subject, $message );
}

/**
 * Create a challenge and send its first code.
 *
 * @param WP_User $user Authenticated user awaiting second factor.
 * @return array<string, mixed>|WP_Error
 */
function ame_2fa_create_challenge( $user ) {
	if ( ! is_email( $user->user_email ) ) {
		return new WP_Error( 'ame_2fa_email_failed', __( 'Akun tidak memiliki alamat email yang valid untuk verifikasi.', 'andalan-mutu-energi' ) );
	}
	if ( ! ame_2fa_consume_mail_slot( $user->ID ) ) {
		return new WP_Error( 'ame_2fa_email_limit', __( 'Terlalu banyak permintaan kode verifikasi. Silakan coba lagi dalam satu jam.', 'andalan-mutu-energi' ) );
	}

	$challenge_id = wp_generate_password( 40, false, false );
	$code         = ame_2fa_generate_code();
	if ( ! ame_2fa_send_email( $user, $code ) ) {
		return new WP_Error( 'ame_2fa_email_failed', __( 'Kode verifikasi tidak dapat dikirim. Periksa konfigurasi email/SMTP atau hubungi administrator.', 'andalan-mutu-energi' ) );
	}

	$context   = ame_2fa_login_context();
	$challenge = array_merge(
		$context,
		array(
			'id'         => $challenge_id,
			'user_id'    => (int) $user->ID,
			'code_hash'  => ame_2fa_code_hash( $user->ID, $challenge_id, $code ),
			'created'    => time(),
			'last_sent'  => time(),
			'expires'    => time() + ( 10 * MINUTE_IN_SECONDS ),
			'attempts'   => 0,
			'send_count' => 1,
		)
	);
	set_transient( ame_2fa_key( $challenge_id ), $challenge, 10 * MINUTE_IN_SECONDS );
	return $challenge;
}

/**
 * Return the verification URL for a challenge.
 *
 * @param array<string, mixed> $challenge Challenge data.
 * @return string
 */
function ame_2fa_verification_url( $challenge ) {
	$challenge_id = isset( $challenge['id'] ) && is_string( $challenge['id'] ) ? $challenge['id'] : '';
	if ( ! ame_2fa_valid_id( $challenge_id ) ) {
		return ame_login_page_url();
	}
	if ( ! empty( $challenge['native'] ) ) {
		return add_query_arg( 'ame_2fa', $challenge_id, site_url( 'wp-login.php', 'login' ) );
	}
	$custom_url = ame_login_custom_page_url();
	if ( $custom_url ) {
		$return = ! empty( $challenge['return'] ) ? wp_validate_redirect( $challenge['return'], $custom_url ) : $custom_url;
		$path   = wp_parse_url( $return, PHP_URL_PATH );
		if ( $path && 'wp-login.php' === basename( $path ) ) {
			$return = $custom_url;
		}
		if ( strpos( $return, admin_url() ) === 0 ) {
			$return = $custom_url;
		}
		return add_query_arg( 'ame_2fa', $challenge_id, $return );
	}
	return add_query_arg( 'ame_2fa', $challenge_id, site_url( 'wp-login.php', 'login' ) );
}

/**
 * Redirect to the verification form.
 *
 * @param mixed $challenge Challenge data.
 * @return void
 */
function ame_2fa_redirect_to_verification( $challenge ) {
	if ( ! is_array( $challenge ) ) {
		$challenge = array();
	}
	wp_safe_redirect( ame_2fa_verification_url( $challenge ) );
	exit;
}

/**
 * Intercept a successful password/CAPTCHA authentication with email 2FA.
 *
 * @param WP_User|WP_Error|null $user     Authentication result.
 * @param string                $username Submitted username, unused.
 * @param string                $password Submitted password, unused.
 * @return WP_User|WP_Error|null
 */
function ame_2fa_authenticate( $user, $username, $password ) {
	if ( ! ame_2fa_is_enabled() || ! ( $user instanceof WP_User ) || ! ame_2fa_is_login_request() ) {
		return $user;
	}
	$challenge = ame_2fa_create_challenge( $user );
	if ( is_wp_error( $challenge ) ) {
		return $challenge;
	}
	return new WP_Error( 'ame_2fa_required', __( 'Verifikasi kode email diperlukan untuk menyelesaikan login.', 'andalan-mutu-energi' ), $challenge );
}
add_filter( 'authenticate', 'ame_2fa_authenticate', 110, 3 );

/**
 * Redirect native wp-login.php failures that represent a 2FA challenge.
 *
 * @param string        $username Submitted username, unused.
 * @param WP_Error|null $error    Authentication error.
 * @return void
 */
function ame_2fa_native_redirect( $username, $error = null ) {
	if ( is_wp_error( $error ) && 'ame_2fa_required' === $error->get_error_code() ) {
		ame_2fa_redirect_to_verification( $error->get_error_data() );
	}
}
add_action( 'wp_login_failed', 'ame_2fa_native_redirect', 10, 2 );

/**
 * Return a translated verification error.
 *
 * @param string $code Error code.
 * @return string
 */
function ame_2fa_error_message( $code ) {
	$messages = array(
		'invalid'      => __( 'Kode verifikasi tidak sesuai. Periksa kembali email Anda.', 'andalan-mutu-energi' ),
		'expired'      => __( 'Sesi verifikasi sudah kedaluwarsa. Silakan masuk kembali.', 'andalan-mutu-energi' ),
		'attempts'     => __( 'Terlalu banyak percobaan kode. Silakan masuk kembali untuk mendapatkan kode baru.', 'andalan-mutu-energi' ),
		'email'        => __( 'Kode baru tidak dapat dikirim. Periksa konfigurasi email/SMTP atau coba lagi nanti.', 'andalan-mutu-energi' ),
		'rate'         => __( 'Batas pengiriman email tercapai. Silakan coba lagi dalam satu jam.', 'andalan-mutu-energi' ),
		'cooldown'     => __( 'Tunggu 60 detik sebelum meminta kode baru.', 'andalan-mutu-energi' ),
		'resend_limit' => __( 'Batas pengiriman ulang untuk sesi ini sudah tercapai. Silakan masuk kembali.', 'andalan-mutu-energi' ),
		'invalid_user' => __( 'Akun tidak lagi tersedia. Silakan hubungi administrator.', 'andalan-mutu-energi' ),
		'request'      => __( 'Permintaan verifikasi tidak valid. Muat ulang halaman lalu coba lagi.', 'andalan-mutu-energi' ),
	);
	return isset( $messages[ $code ] ) ? $messages[ $code ] : $messages['invalid'];
}

/**
 * Redirect a verification error while retaining the challenge.
 *
 * @param string               $code      Error code.
 * @param array<string, mixed> $challenge Challenge data.
 * @return void
 */
function ame_2fa_redirect_challenge_error( $code, $challenge ) {
	$url = add_query_arg( 'ame_2fa_error', sanitize_key( $code ), ame_2fa_verification_url( $challenge ) );
	wp_safe_redirect( $url );
	exit;
}

/**
 * Redirect an expired/invalid challenge to the login screen or native error.
 *
 * @param string $code Error code.
 * @return void
 */
function ame_2fa_redirect_expired( $code ) {
	$custom_url = ame_login_custom_page_url();
	if ( $custom_url ) {
		ame_login_redirect_error( '2fa_' . sanitize_key( $code ), $custom_url );
	}
	wp_die( esc_html( ame_2fa_error_message( $code ) ), esc_html__( 'Verifikasi login', 'andalan-mutu-energi' ), array( 'response' => 403, 'back_link' => true ) );
}

/**
 * Render the verification form for either the shortcode or native login page.
 *
 * @param string $challenge_id Challenge ID.
 * @return string
 */
function ame_2fa_form_html( $challenge_id ) {
	$challenge = ame_2fa_get_challenge( $challenge_id );
	if ( ! $challenge ) {
		return '<div class="ame-login-card ame-2fa-card"><div class="ame-login-error" role="alert">' . esc_html( ame_2fa_error_message( 'expired' ) ) . '</div><p class="ame-login-help"><a href="' . esc_url( ame_login_page_url() ) . '">' . esc_html__( 'Kembali ke halaman Masuk', 'andalan-mutu-energi' ) . '</a></p></div>';
	}

	$error = isset( $_GET['ame_2fa_error'] ) && is_string( $_GET['ame_2fa_error'] ) ? sanitize_key( wp_unslash( $_GET['ame_2fa_error'] ) ) : '';
	$sent  = isset( $_GET['ame_2fa_sent'] ) && '1' === $_GET['ame_2fa_sent'];
	$id    = wp_unique_id( 'ame-2fa-' );
	$nonce = wp_create_nonce( 'ame_2fa_' . $challenge_id );
	ob_start();
	?>
	<div class="ame-login-card ame-2fa-card">
		<h2 class="ame-login-title"><?php esc_html_e( 'Verifikasi kode email', 'andalan-mutu-energi' ); ?></h2>
		<p class="ame-2fa-description"><?php esc_html_e( 'Kami telah mengirim kode 6 digit ke email akun Anda. Masukkan kode untuk menyelesaikan login.', 'andalan-mutu-energi' ); ?></p>
		<?php if ( $sent ) : ?><div class="ame-login-success" role="status"><?php esc_html_e( 'Kode baru telah dikirim. Periksa email Anda.', 'andalan-mutu-energi' ); ?></div><?php endif; ?>
		<?php if ( $error ) : ?><div class="ame-login-error" role="alert"><?php echo esc_html( ame_2fa_error_message( $error ) ); ?></div><?php endif; ?>
		<form class="ame-login-form ame-2fa-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ame_2fa_verify">
			<input type="hidden" name="ame_2fa_challenge" value="<?php echo esc_attr( $challenge_id ); ?>">
			<input type="hidden" name="ame_2fa_nonce" value="<?php echo esc_attr( $nonce ); ?>">
			<div class="ame-login-field ame-2fa-code">
				<label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Kode verifikasi 6 digit', 'andalan-mutu-energi' ); ?></label>
				<input id="<?php echo esc_attr( $id ); ?>" type="text" name="ame_2fa_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
			</div>
			<button class="ame-login-button" type="submit"><?php esc_html_e( 'Verifikasi & Masuk', 'andalan-mutu-energi' ); ?></button>
		</form>
		<div class="ame-2fa-secondary-actions">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ame_2fa_resend">
				<input type="hidden" name="ame_2fa_challenge" value="<?php echo esc_attr( $challenge_id ); ?>">
				<input type="hidden" name="ame_2fa_nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<button class="ame-2fa-link-button" type="submit"><?php esc_html_e( 'Kirim ulang kode', 'andalan-mutu-energi' ); ?></button>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ame_2fa_cancel">
				<input type="hidden" name="ame_2fa_challenge" value="<?php echo esc_attr( $challenge_id ); ?>">
				<input type="hidden" name="ame_2fa_nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<button class="ame-2fa-link-button" type="submit"><?php esc_html_e( 'Batalkan', 'andalan-mutu-energi' ); ?></button>
			</form>
		</div>
		<p class="ame-2fa-note"><?php esc_html_e( 'Kode berlaku 10 menit. Maksimal 5 percobaan dan 3 pengiriman per sesi.', 'andalan-mutu-energi' ); ?></p>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render a native wp-login.php verification page when no custom page exists.
 *
 * This runs before the default-login replacement hook.
 *
 * @return void
 */
function ame_2fa_native_page() {
	global $pagenow;
	$method = isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
	$action = isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
	if ( 'wp-login.php' !== $pagenow || 'GET' !== $method || ( $action && 'login' !== $action ) || ! isset( $_GET['ame_2fa'] ) || ! is_string( $_GET['ame_2fa'] ) ) {
		return;
	}
	$challenge_id = sanitize_text_field( wp_unslash( $_GET['ame_2fa'] ) );
	if ( ! ame_2fa_valid_id( $challenge_id ) ) {
		return;
	}
	if ( ! function_exists( 'login_header' ) || ! function_exists( 'login_footer' ) ) {
		return;
	}
	login_header( __( 'Verifikasi Login', 'andalan-mutu-energi' ), '', null );
	echo ame_2fa_form_html( $challenge_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Internally escaped form markup.
	login_footer();
	exit;
}
add_action( 'login_init', 'ame_2fa_native_page', 0 );

/**
 * Read a challenge ID from a verification POST.
 *
 * @return string
 */
function ame_2fa_post_challenge_id() {
	return trim( sanitize_text_field( ame_login_post_value( 'ame_2fa_challenge' ) ) );
}

/**
 * Require a valid nonce for a challenge state change.
 *
 * @param string               $challenge_id Challenge ID.
 * @param array<string, mixed> $challenge    Challenge data.
 * @return void
 */
function ame_2fa_require_nonce( $challenge_id, $challenge ) {
	$nonce = ame_login_post_value( 'ame_2fa_nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'ame_2fa_' . $challenge_id ) ) {
		ame_2fa_redirect_challenge_error( 'request', $challenge );
	}
}

/**
 * Verify a submitted code and create the WordPress auth cookie only on success.
 *
 * @return void
 */
function ame_2fa_verify() {
	$challenge_id = ame_2fa_post_challenge_id();
	$challenge    = ame_2fa_get_challenge( $challenge_id );
	if ( ! $challenge ) {
		ame_2fa_redirect_expired( 'expired' );
	}
	ame_2fa_require_nonce( $challenge_id, $challenge );

	$code    = trim( sanitize_text_field( ame_login_post_value( 'ame_2fa_code' ) ) );
	$valid   = (bool) preg_match( '/^[0-9]{6}$/', $code );
	$valid   = $valid && hash_equals( $challenge['code_hash'], ame_2fa_code_hash( $challenge['user_id'], $challenge_id, $code ) );
	$challenge['attempts'] = (int) $challenge['attempts'] + 1;

	if ( ! $valid ) {
		if ( $challenge['attempts'] >= 5 ) {
			ame_2fa_delete_challenge( $challenge_id );
			ame_2fa_redirect_expired( 'attempts' );
		}
		set_transient( ame_2fa_key( $challenge_id ), $challenge, max( 1, (int) $challenge['expires'] - time() ) );
		ame_2fa_redirect_challenge_error( 'invalid', $challenge );
	}

	$user = get_userdata( $challenge['user_id'] );
	if ( ! $user ) {
		ame_2fa_delete_challenge( $challenge_id );
		ame_2fa_redirect_expired( 'invalid_user' );
	}
	ame_2fa_delete_challenge( $challenge_id );
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, ! empty( $challenge['remember'] ), ! empty( $challenge['secure_cookie'] ) );

	// Preserve the activation-key cleanup normally performed by wp_signon().
	if ( ! empty( $user->user_activation_key ) ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->users,
			array( 'user_activation_key' => '' ),
			array( 'ID' => $user->ID )
		);
		$user->user_activation_key = '';
	}
	do_action( 'wp_login', $user->user_login, $user );

	$fallback = ! empty( $challenge['native'] ) ? admin_url() : $challenge['return'];
	$redirect = wp_validate_redirect( $challenge['redirect_to'], $fallback );
	if ( ! empty( $challenge['native'] ) ) {
		$requested_redirect = $redirect;
		$redirect           = apply_filters( 'login_redirect', $redirect, $requested_redirect, $user );
		$redirect           = wp_validate_redirect( $redirect, $fallback );
	}
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_ame_2fa_verify', 'ame_2fa_verify' );
add_action( 'admin_post_nopriv_ame_2fa_verify', 'ame_2fa_verify' );

/**
 * Resend a code with a cooldown, per-session limit, and per-user email limit.
 *
 * @return void
 */
function ame_2fa_resend() {
	$challenge_id = ame_2fa_post_challenge_id();
	$challenge    = ame_2fa_get_challenge( $challenge_id );
	if ( ! $challenge ) {
		ame_2fa_redirect_expired( 'expired' );
	}
	ame_2fa_require_nonce( $challenge_id, $challenge );
	if ( time() - (int) $challenge['last_sent'] < MINUTE_IN_SECONDS ) {
		ame_2fa_redirect_challenge_error( 'cooldown', $challenge );
	}
	if ( (int) $challenge['send_count'] >= 3 ) {
		ame_2fa_redirect_challenge_error( 'resend_limit', $challenge );
	}
	$user = get_userdata( $challenge['user_id'] );
	if ( ! $user ) {
		ame_2fa_delete_challenge( $challenge_id );
		ame_2fa_redirect_expired( 'invalid_user' );
	}
	if ( ! ame_2fa_consume_mail_slot( $user->ID ) ) {
		ame_2fa_redirect_challenge_error( 'rate', $challenge );
	}

	$code = ame_2fa_generate_code();
	if ( ! ame_2fa_send_email( $user, $code ) ) {
		ame_2fa_redirect_challenge_error( 'email', $challenge );
	}
	$challenge['code_hash']  = ame_2fa_code_hash( $user->ID, $challenge_id, $code );
	$challenge['last_sent']  = time();
	$challenge['expires']    = time() + ( 10 * MINUTE_IN_SECONDS );
	$challenge['send_count'] = (int) $challenge['send_count'] + 1;
	set_transient( ame_2fa_key( $challenge_id ), $challenge, 10 * MINUTE_IN_SECONDS );

	$url = add_query_arg( 'ame_2fa_sent', '1', ame_2fa_verification_url( $challenge ) );
	wp_safe_redirect( $url );
	exit;
}
add_action( 'admin_post_ame_2fa_resend', 'ame_2fa_resend' );
add_action( 'admin_post_nopriv_ame_2fa_resend', 'ame_2fa_resend' );

/**
 * Cancel a challenge without creating an auth cookie.
 *
 * @return void
 */
function ame_2fa_cancel() {
	$challenge_id = ame_2fa_post_challenge_id();
	$challenge    = ame_2fa_get_challenge( $challenge_id );
	if ( $challenge ) {
		ame_2fa_require_nonce( $challenge_id, $challenge );
		$return = wp_validate_redirect( $challenge['return'], home_url( '/' ) );
		ame_2fa_delete_challenge( $challenge_id );
		wp_safe_redirect( $return );
		exit;
	}
	wp_safe_redirect( ame_login_page_url() );
	exit;
}
add_action( 'admin_post_ame_2fa_cancel', 'ame_2fa_cancel' );
add_action( 'admin_post_nopriv_ame_2fa_cancel', 'ame_2fa_cancel' );

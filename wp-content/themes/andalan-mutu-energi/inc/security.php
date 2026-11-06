<?php
/**
 * Conservative browser security headers for the public site.
 *
 * Server-level configuration is still recommended. No restrictive CSP is
 * imposed by the theme because WordPress plugins may require inline assets.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send safe, filterable security headers before any page output.
 *
 * @return void
 */
function ame_send_security_headers() {
	if ( is_admin() || headers_sent() || ! get_theme_mod( 'ame_security_headers_enabled', true ) ) {
		return;
	}

	$headers = apply_filters(
		'ame_security_headers',
		array(
			'X-Content-Type-Options' => 'nosniff',
			'Referrer-Policy'        => 'strict-origin-when-cross-origin',
			'X-Frame-Options'        => 'SAMEORIGIN',
			'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=()',
		)
	);

	foreach ( (array) $headers as $name => $value ) {
		if ( $value ) {
			header( sanitize_text_field( $name ) . ': ' . sanitize_text_field( $value ), true );
		}
	}
}
add_action( 'send_headers', 'ame_send_security_headers' );

<?php
/**
 * Inline SVG icon helper.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return a UI icon. Icons are decorative and hidden from assistive technology.
 *
 * @param string $name  Icon name.
 * @param string $class Optional CSS class.
 * @return string
 */
function ame_icon( $name, $class = '' ) {
	$icons = array(
		'bolt'          => '<path class="icon-fill" d="M13.2 2 5.8 13h5.1L10.6 22 18.4 10h-5.2V2Z"/>',
		'search'        => '<circle cx="10.8" cy="10.8" r="6.6"/><path d="m16 16 4.4 4.4"/>',
		'phone'         => '<path d="M7.2 3.5 10 8.8 7.8 11c1.4 2.7 3.5 4.8 6.2 6.2l2.2-2.2 5.3 2.8-.8 3.1c-.2.7-.8 1.1-1.5 1.1C10.3 21.5 2.5 13.7 2 4.8c0-.7.4-1.3 1.1-1.5l4.1.2Z"/>',
		'user'          => '<circle cx="12" cy="8" r="4"/><path d="M4.5 21c.6-4.2 3.1-6.3 7.5-6.3s6.9 2.1 7.5 6.3"/>',
		'logout'        => '<path d="M14 4H5v16h9M10 12h10M17 8l4 4-4 4"/>',
		'chevron-right' => '<path d="m9 5 7 7-7 7"/>',
		'chevron-left'  => '<path d="m15 5-7 7 7 7"/>',
		'arrow-right'   => '<path d="M4 12h15M14 6l6 6-6 6"/>',
		'arrow-up'      => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'play'          => '<path class="icon-fill" d="m8 5 11 7-11 7V5Z"/>',
		'plus'          => '<path d="M12 5v14M5 12h14"/>',
		'menu'          => '<path d="M5 8h14M5 16h14"/>',
		'close'         => '<path d="m6 6 12 12M18 6 6 18"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg class="icon icon-%1$s %2$s" viewBox="0 0 24 24" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">%3$s</svg>',
		esc_attr( $name ),
		esc_attr( $class ),
		$icons[ $name ] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hard-coded SVG markup.
	);
}

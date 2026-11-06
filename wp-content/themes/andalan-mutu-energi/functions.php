<?php
/**
 * Andalan Mutu Energi theme setup.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AME_VERSION', '1.9.2' );

require_once get_template_directory() . '/inc/icons.php';
require_once get_template_directory() . '/inc/content-types.php';
require_once get_template_directory() . '/inc/corporate.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/security.php';
require_once get_template_directory() . '/inc/firewall.php';
require_once get_template_directory() . '/inc/login.php';
require_once get_template_directory() . '/inc/two-factor.php';
require_once get_template_directory() . '/inc/analytics.php';
require_once get_template_directory() . '/inc/customizer.php';
if ( is_admin() ) {
	require_once get_template_directory() . '/inc/demo-content.php';
}

/**
 * Configure theme defaults and WordPress feature support.
 *
 * @return void
 */
function ame_setup() {
	load_theme_textdomain( 'andalan-mutu-energi', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 44,
			'width'       => 120,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_editor_style( 'assets/css/editor.css' );
}
add_action( 'after_setup_theme', 'ame_setup' );

/**
 * Install editable demo records when the theme is first activated.
 *
 * @return void
 */
function ame_activate() {
	ame_register_content();
	require_once get_template_directory() . '/inc/demo-content.php';
	ame_install_demo_content();
	ame_analytics_install();
	ame_firewall_install();
	ame_login_install();
}
add_action( 'after_switch_theme', 'ame_activate' );

/**
 * Set the content width used by media embeds.
 *
 * @return void
 */
function ame_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'ame_content_width', 1024 );
}
add_action( 'after_setup_theme', 'ame_content_width', 0 );

/**
 * Enqueue public assets.
 *
 * @return void
 */
function ame_assets() {
	wp_enqueue_style( 'andalan-mutu-energi-style', get_stylesheet_uri(), array(), AME_VERSION );
	wp_enqueue_style(
		'andalan-mutu-energi-theme',
		get_template_directory_uri() . '/assets/css/theme.css',
		array( 'andalan-mutu-energi-style' ),
		AME_VERSION
	);
	wp_add_inline_style( 'andalan-mutu-energi-theme', ame_get_custom_color_css() );

	wp_enqueue_script(
		'andalan-mutu-energi-navigation',
		get_template_directory_uri() . '/assets/js/navigation.js',
		array(),
		AME_VERSION,
		true
	);

	if ( is_front_page() ) {
		wp_enqueue_script(
			'andalan-mutu-energi-gallery',
			get_template_directory_uri() . '/assets/js/gallery.js',
			array(),
			AME_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ame_assets' );

/**
 * Keep excerpts concise in archive templates.
 *
 * @return string
 */
function ame_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'ame_excerpt_more' );

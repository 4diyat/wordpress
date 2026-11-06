<?php
/**
 * Small corporate-site performance and UX enhancements.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preload the first hero image, which is normally the largest above-the-fold
 * asset on the homepage.
 *
 * @return void
 */
function ame_preload_primary_hero() {
	if ( ! is_front_page() || ! get_theme_mod( 'ame_preload_hero', true ) ) {
		return;
	}

	$heroes = ame_get_sections( 'ame_hero' );
	if ( empty( $heroes[0] ) ) {
		return;
	}
	$image_id = get_post_thumbnail_id( $heroes[0]->ID );
	$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
	if ( $image ) {
		echo '<link rel="preload" as="image" href="' . esc_url( $image ) . '" fetchpriority="high">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL is escaped above.
	}
}
add_action( 'wp_head', 'ame_preload_primary_hero', 1 );

/**
 * Render a keyboard-accessible back-to-top control when enabled.
 *
 * @return void
 */
function ame_render_back_to_top() {
	if ( ! get_theme_mod( 'ame_enable_back_to_top', true ) ) {
		return;
	}
	?>
	<button class="back-to-top" type="button" data-back-to-top aria-label="<?php esc_attr_e( 'Kembali ke atas', 'andalan-mutu-energi' ); ?>" hidden>
		<?php echo ame_icon( 'arrow-up' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</button>
	<?php
}

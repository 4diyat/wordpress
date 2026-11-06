<?php
/**
 * Site footer. Menu names and links are managed in Appearance > Menus.
 *
 * @package Andalan_Mutu_Energi
 */

// This array describes layout only; all displayed content comes from WordPress.
$footer_columns = array(
	array( 'footer_company', 'footer_services' ),
	array( 'footer_guides', 'footer_support' ),
	array( 'footer_contact' ),
	array( 'footer_scope', 'footer_sectors', 'footer_management', 'footer_legal_info' ),
	array( 'footer_news', 'footer_social' ),
);
$footer_notes  = get_theme_mod( 'ame_footer_notes', '' );
$contact_info   = get_theme_mod( 'ame_contact_info', '' );
$copyright     = get_theme_mod( 'ame_copyright', '' );
$locale_label  = get_theme_mod( 'ame_locale_label', '' );
$locale_url    = get_theme_mod( 'ame_locale_url', '' );
$legal_menu    = ame_get_assigned_menu( 'legal' );
?>
<footer class="site-footer">
	<div class="footer-inner">
		<?php if ( $footer_notes ) : ?>
			<div class="footer-notes">
				<?php foreach ( preg_split( '/\r\n|\r|\n/', $footer_notes ) as $note ) : ?>
					<?php if ( trim( $note ) ) : ?><p><?php echo esc_html( trim( $note ) ); ?></p><?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="footer-columns">
			<?php foreach ( $footer_columns as $column ) : ?>
				<div class="footer-column">
					<?php foreach ( $column as $location ) : ?>
						<?php $menu = ame_get_assigned_menu( $location ); ?>
						<?php if ( ! $menu ) { continue; } ?>
						<section class="footer-group">
							<h2 class="footer-heading">
								<button class="footer-heading-button" type="button" aria-expanded="true" aria-controls="footer-<?php echo esc_attr( $location ); ?>">
									<span><?php echo esc_html( $menu->name ); ?></span><?php echo ame_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</button>
							</h2>
							<?php
							wp_nav_menu(
								array(
									'menu'        => $menu->term_id,
									'container'   => false,
									'items_wrap'  => '<ul id="footer-' . esc_attr( $location ) . '" class="footer-links">%3$s</ul>',
									'fallback_cb' => false,
									'depth'       => 1,
								)
							);
							?>
						</section>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $contact_info ) : ?>
			<div class="footer-contact"><p><?php echo wp_kses_post( $contact_info ); ?></p></div>
		<?php endif; ?>

		<div class="footer-legal">
			<?php if ( $copyright ) : ?>
				<p class="footer-copyright"><?php echo esc_html( str_replace( '[year]', gmdate( 'Y' ), $copyright ) ); ?></p>
			<?php endif; ?>
			<?php
			if ( $legal_menu ) {
				wp_nav_menu(
					array(
						'menu'        => $legal_menu->term_id,
						'container'   => false,
						'items_wrap'  => '<ul class="footer-legal-links">%3$s</ul>',
						'fallback_cb' => false,
						'depth'       => 1,
					)
				);
			}
			?>
			<?php if ( $locale_label && $locale_url ) : ?>
				<a class="footer-locale" href="<?php echo esc_url( $locale_url ); ?>"><?php echo esc_html( $locale_label ); ?></a>
			<?php elseif ( $locale_label ) : ?>
				<span class="footer-locale"><?php echo esc_html( $locale_label ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</footer>
<?php ame_render_back_to_top(); ?>
<?php wp_footer(); ?>
</body>
</html>

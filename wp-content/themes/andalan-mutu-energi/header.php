<?php
/**
 * Site header and global navigation.
 *
 * @package Andalan_Mutu_Energi
 */

$search_placeholder  = get_theme_mod( 'ame_search_placeholder', '' );
$quick_links_heading = get_theme_mod( 'ame_quick_links_heading', '' );
$contact_title           = get_theme_mod( 'ame_contact_title', '' );
$contact_message         = get_theme_mod( 'ame_contact_message', '' );
$quick_links_menu    = ame_get_assigned_menu( 'quick_links' );
$contact_links_menu      = ame_get_assigned_menu( 'contact_links' );
if ( ! $search_placeholder ) {
	$search_placeholder = __( 'Cari', 'andalan-mutu-energi' );
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Langsung ke konten', 'andalan-mutu-energi' ); ?></a>
<header class="site-header">
	<nav class="global-nav" aria-label="<?php esc_attr_e( 'Navigasi utama', 'andalan-mutu-energi' ); ?>">
		<div class="nav-inner">
			<a class="nav-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php
				$custom_logo_id = get_theme_mod( 'custom_logo' );
				if ( $custom_logo_id ) {
					echo wp_get_attachment_image( $custom_logo_id, 'full', false, array( 'class' => 'custom-logo' ) );
				} else {
					?>
					<span class="brand-fallback"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
					<?php
				}
				?>
			</a>

			<div class="desktop-menu">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'items_wrap'     => '<ul id="primary-menu">%3$s</ul>',
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
				?>
			</div>

			<div class="nav-actions">
				<?php if ( get_theme_mod( 'ame_header_login_enabled', false ) ) : ?>
					<?php
					$header_login_label = is_user_logged_in() ? __( 'Keluar', 'andalan-mutu-energi' ) : __( 'Masuk', 'andalan-mutu-energi' );
					$header_login_url   = is_user_logged_in() ? wp_logout_url( home_url( '/' ) ) : ame_login_page_url();
					?>
					<a class="nav-icon-button nav-login-link" href="<?php echo esc_url( $header_login_url ); ?>" aria-label="<?php echo esc_attr( $header_login_label ); ?>" title="<?php echo esc_attr( $header_login_label ); ?>">
						<?php echo ame_icon( is_user_logged_in() ? 'logout' : 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				<?php endif; ?>
				<button class="nav-icon-button" type="button" data-nav-trigger="search" aria-expanded="false" aria-controls="global-search" aria-label="<?php echo esc_attr( $search_placeholder ); ?>">
					<?php echo ame_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<button class="nav-icon-button" type="button" data-nav-trigger="contact" aria-expanded="false" aria-controls="contact-panel" aria-label="<?php esc_attr_e( 'Kontak', 'andalan-mutu-energi' ); ?>">
					<?php echo ame_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<button class="nav-icon-button menu-toggle" type="button" data-nav-trigger="menu" aria-expanded="false" aria-controls="mobile-menu" aria-label="<?php esc_attr_e( 'Menu', 'andalan-mutu-energi' ); ?>">
					<?php echo ame_icon( 'menu', 'icon-open' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo ame_icon( 'close', 'icon-close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
		</div>

		<div id="global-search" class="nav-panel search-panel" data-nav-panel="search">
			<div class="search-panel-inner">
				<form class="search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php echo ame_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<label class="screen-reader-text" for="nav-search-field"><?php echo esc_html( $search_placeholder ); ?></label>
					<input id="nav-search-field" type="search" name="s" placeholder="<?php echo esc_attr( $search_placeholder ); ?>" autocomplete="off">
				</form>
			<?php if ( $quick_links_menu ) : ?>
				<div class="quick-links">
					<?php if ( $quick_links_heading ) : ?><h2><?php echo esc_html( $quick_links_heading ); ?></h2><?php endif; ?>
					<?php
					wp_nav_menu(
						array(
							'menu'        => $quick_links_menu->term_id,
							'container'   => false,
							'items_wrap'  => '<ul>%3$s</ul>',
							'fallback_cb' => false,
							'depth'       => 1,
							'link_before' => ame_icon( 'arrow-right' ),
						)
					);
					?>
				</div>
			<?php endif; ?>
			</div>
		</div>

		<aside id="contact-panel" class="nav-panel contact-panel" data-nav-panel="contact" aria-label="<?php esc_attr_e( 'Kontak', 'andalan-mutu-energi' ); ?>">
			<div class="contact-panel-inner">
				<?php if ( $contact_title ) : ?><h2><?php echo esc_html( $contact_title ); ?></h2><?php endif; ?>
				<?php if ( $contact_message ) : ?><p><?php echo esc_html( $contact_message ); ?></p><?php endif; ?>
				<?php
				if ( $contact_links_menu ) {
					wp_nav_menu(
						array(
							'menu'        => $contact_links_menu->term_id,
							'container'   => false,
							'items_wrap'  => '<ul>%3$s</ul>',
							'fallback_cb' => false,
							'depth'       => 1,
						)
					);
				}
				?>
			</div>
		</aside>
	</nav>

	<div id="mobile-menu" class="mobile-menu-panel" data-nav-panel="menu">
			<div class="mobile-menu">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'items_wrap'     => '<ul id="mobile-primary-menu">%3$s</ul>',
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
				?>
			</div>
		</div>
	<div class="nav-scrim" data-nav-scrim aria-hidden="true"></div>
</header>

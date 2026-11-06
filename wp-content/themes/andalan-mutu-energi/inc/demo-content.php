<?php
/**
 * One-time demo content installer.
 *
 * This file is only used on theme activation or from the setup screen in
 * wp-admin. Front-end templates query the resulting database content instead.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import a bundled image into the Media Library.
 *
 * @param string $relative_path Theme-relative image path.
 * @return int Attachment ID, or zero on failure.
 */
function ame_import_demo_image( $relative_path ) {
	$source = get_template_directory() . '/' . ltrim( $relative_path, '/' );
	if ( ! file_exists( $source ) ) {
		return 0;
	}

	$upload = wp_upload_dir();
	if ( ! empty( $upload['error'] ) || ! wp_is_writable( $upload['path'] ) ) {
		return 0;
	}

	$filename = wp_unique_filename( $upload['path'], basename( $source ) );
	$target   = trailingslashit( $upload['path'] ) . $filename;
	if ( ! copy( $source, $target ) ) {
		return 0;
	}

	$filetype  = wp_check_filetype( $filename );
	$attach_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => ucwords( str_replace( array( '-', '_' ), ' ', pathinfo( $filename, PATHINFO_FILENAME ) ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$target
	);

	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	$metadata = wp_generate_attachment_metadata( $attach_id, $target );
	if ( ! is_wp_error( $metadata ) ) {
		wp_update_attachment_metadata( $attach_id, $metadata );
	}
	update_post_meta( $attach_id, '_ame_demo_asset', $relative_path );

	return (int) $attach_id;
}

/**
 * Insert demo posts of a single type.
 *
 * @param string $post_type Target post type.
 * @param array  $items     Demo items.
 * @return void
 */
function ame_insert_demo_sections( $post_type, $items ) {
	foreach ( $items as $index => $item ) {
		$is_show = 'ame_show' === $post_type;
		$post_id = wp_insert_post(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'post_title'     => wp_slash( $item['title'] ),
				'post_excerpt'   => wp_slash( $is_show ? $item['description'] : $item['subtitle'] ),
				'menu_order'     => (int) $index,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		update_post_meta( $post_id, '_ame_demo_content', '1' );
		if ( ! empty( $item['image'] ) ) {
			$attachment_id = ame_import_demo_image( $item['image'] );
			if ( $attachment_id ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}

		if ( $is_show ) {
			update_post_meta( $post_id, '_ame_genre', $item['genre'] );
			update_post_meta( $post_id, '_ame_primary_label', $item['action'] );
			update_post_meta( $post_id, '_ame_primary_url', $item['url'] );
			continue;
		}

		update_post_meta( $post_id, '_ame_availability', $item['availability'] );
		update_post_meta( $post_id, '_ame_primary_label', $item['primary'][0] );
		update_post_meta( $post_id, '_ame_primary_url', $item['primary'][1] );
		update_post_meta( $post_id, '_ame_secondary_label', $item['secondary'][0] );
		update_post_meta( $post_id, '_ame_secondary_url', $item['secondary'][1] );
		update_post_meta( $post_id, '_ame_theme', $item['theme'] );

		if ( 'ame_promo' === $post_type ) {
			update_post_meta( $post_id, '_ame_footnote', isset( $item['footnote'] ) ? $item['footnote'] : '' );
		}
	}
}

/**
 * Create one menu and assign it to a location without replacing existing menus.
 *
 * @param string $location  Menu location.
 * @param array  $menu_data Menu name and items.
 * @return void
 */
function ame_insert_demo_menu( $location, $menu_data ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( ! empty( $locations[ $location ] ) ) {
		return;
	}

	$menu_id = wp_create_nav_menu( $menu_data['name'] );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}

	$position = 1;
	foreach ( $menu_data['items'] as $label => $url ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'    => $label,
				'menu-item-url'      => $url,
				'menu-item-status'   => 'publish',
				'menu-item-position' => $position,
				'menu-item-type'     => 'custom',
				'menu-item-object'   => 'custom',
			)
		);
		++$position;
	}

	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Install all demo records once.
 *
 * @return bool Whether installation ran successfully.
 */
function ame_install_demo_content() {
	if ( get_option( 'ame_demo_installed' ) ) {
		return false;
	}

	$data_file = get_template_directory() . '/demo-content.json';
	if ( ! is_readable( $data_file ) ) {
		return false;
	}

	$data = json_decode( (string) file_get_contents( $data_file ), true );
	if ( ! is_array( $data ) || empty( $data['heroes'] ) ) {
		return false;
	}

	ame_insert_demo_sections( 'ame_hero', $data['heroes'] );
	ame_insert_demo_sections( 'ame_promo', $data['promos'] );
	ame_insert_demo_sections( 'ame_show', $data['shows'] );

	foreach ( $data['menus'] as $location => $menu_data ) {
		ame_insert_demo_menu( $location, $menu_data );
	}

	foreach ( $data['settings'] as $key => $value ) {
		if ( null === get_theme_mod( $key, null ) ) {
			set_theme_mod( $key, $value );
		}
	}

	if ( ! empty( $data['brand']['logo'] ) && ! get_theme_mod( 'custom_logo' ) ) {
		$logo_id = ame_import_demo_image( $data['brand']['logo'] );
		if ( $logo_id ) {
			set_theme_mod( 'custom_logo', $logo_id );
		}
	}
	if ( ! empty( $data['brand']['favicon'] ) && ! get_option( 'site_icon' ) ) {
		$icon_id = ame_import_demo_image( $data['brand']['favicon'] );
		if ( $icon_id ) {
			update_option( 'site_icon', $icon_id, false );
		}
	}

	update_option( 'ame_demo_installed', AME_VERSION, false );
	return true;
}

/**
 * Add a setup page below Appearance.
 *
 * @return void
 */
function ame_add_setup_page() {
	add_theme_page(
		__( 'Setup Beranda AME', 'andalan-mutu-energi' ),
		__( 'Setup Beranda AME', 'andalan-mutu-energi' ),
		'edit_theme_options',
		'andalan-mutu-energi-setup',
		'ame_render_setup_page'
	);
}
add_action( 'admin_menu', 'ame_add_setup_page' );

/**
 * Render the content-management setup screen.
 *
 * @return void
 */
function ame_render_setup_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$installed = (bool) get_option( 'ame_demo_installed' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Setup Beranda AME', 'andalan-mutu-energi' ); ?></h1>
		<p><?php esc_html_e( 'Seluruh konten beranda tersimpan di WordPress. Edit melalui tipe konten, Menu, Media Library, dan Customizer.', 'andalan-mutu-energi' ); ?></p>
		<?php if ( isset( $_GET['installed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Konten demo telah ditambahkan ke WordPress.', 'andalan-mutu-energi' ); ?></p></div>
		<?php endif; ?>
		<div class="card" style="max-width:760px;padding:20px;">
			<h2><?php esc_html_e( 'Kelola konten', 'andalan-mutu-energi' ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ame_hero' ) ); ?>"><?php esc_html_e( 'Hero Beranda', 'andalan-mutu-energi' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ame_promo' ) ); ?>"><?php esc_html_e( 'Kartu Layanan', 'andalan-mutu-energi' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ame_show' ) ); ?>"><?php esc_html_e( 'Slide Informasi', 'andalan-mutu-energi' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'Menu Navigasi dan Footer', 'andalan-mutu-energi' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=ame_home_panel' ) ); ?>"><?php esc_html_e( 'Pengaturan Teks Beranda', 'andalan-mutu-energi' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ame-visitor-dashboard' ) ); ?>"><?php esc_html_e( 'Dashboard Pengunjung', 'andalan-mutu-energi' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ame-security-dashboard' ) ); ?>"><?php esc_html_e( 'Keamanan AME', 'andalan-mutu-energi' ); ?></a></li>
				<li><a href="<?php echo esc_url( ame_login_page_url() ); ?>"><?php esc_html_e( 'Halaman Masuk', 'andalan-mutu-energi' ); ?></a></li>
			</ul>
			<?php if ( ! $installed ) : ?>
				<hr>
				<h2><?php esc_html_e( 'Pasang konten demo', 'andalan-mutu-energi' ); ?></h2>
				<p><?php esc_html_e( 'Proses ini membuat post, gambar Media Library, menu, dan pengaturan yang dapat diedit. Proses hanya berjalan sekali dan tidak mengganti menu yang sudah ditetapkan.', 'andalan-mutu-energi' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ame_install_demo">
					<?php wp_nonce_field( 'ame_install_demo' ); ?>
					<?php submit_button( __( 'Pasang Konten Demo', 'andalan-mutu-energi' ), 'primary', 'submit', false ); ?>
				</form>
			<?php else : ?>
				<p><strong><?php esc_html_e( 'Konten demo sudah terpasang. Setiap record kini dapat diedit atau dihapus secara terpisah.', 'andalan-mutu-energi' ); ?></strong></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Handle the one-time demo installer form.
 *
 * @return void
 */
function ame_handle_demo_installer() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Anda tidak memiliki izin untuk memasang konten theme.', 'andalan-mutu-energi' ) );
	}
	check_admin_referer( 'ame_install_demo' );
	ame_register_content();
	ame_install_demo_content();
	wp_safe_redirect( admin_url( 'themes.php?page=andalan-mutu-energi-setup&installed=1' ) );
	exit;
}
add_action( 'admin_post_ame_install_demo', 'ame_handle_demo_installer' );

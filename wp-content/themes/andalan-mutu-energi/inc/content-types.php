<?php
/**
 * Database-backed homepage content and navigation.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom post types used on the homepage.
 *
 * @return array<string, string>
 */
function ame_section_types() {
	return array(
		'ame_hero'  => __( 'Hero Beranda', 'andalan-mutu-energi' ),
		'ame_promo' => __( 'Kartu Layanan', 'andalan-mutu-energi' ),
		'ame_show'  => __( 'Slide Informasi', 'andalan-mutu-energi' ),
	);
}

/**
 * Navigational menu locations. Footer groups are menus so both their heading
 * (the menu name) and their links can be edited without touching code.
 *
 * @return array<string, string>
 */
function ame_menu_locations() {
	return array(
		'primary'           => __( 'Navigasi Utama', 'andalan-mutu-energi' ),
		'quick_links'       => __( 'Header — Tautan Cepat', 'andalan-mutu-energi' ),
		'contact_links'     => __( 'Header — Tautan Kontak', 'andalan-mutu-energi' ),
		'footer_company'    => __( 'Footer 1 — Perusahaan', 'andalan-mutu-energi' ),
		'footer_services'   => __( 'Footer 1 — Layanan', 'andalan-mutu-energi' ),
		'footer_guides'     => __( 'Footer 2 — Panduan SLO', 'andalan-mutu-energi' ),
		'footer_support'    => __( 'Footer 2 — Dukungan', 'andalan-mutu-energi' ),
		'footer_contact'    => __( 'Footer 3 — Kontak', 'andalan-mutu-energi' ),
		'footer_scope'      => __( 'Footer 4 — Lingkup Usaha', 'andalan-mutu-energi' ),
		'footer_sectors'    => __( 'Footer 4 — Sektor', 'andalan-mutu-energi' ),
		'footer_management' => __( 'Footer 4 — Sistem Manajemen', 'andalan-mutu-energi' ),
		'footer_legal_info' => __( 'Footer 4 — Informasi', 'andalan-mutu-energi' ),
		'footer_news'       => __( 'Footer 5 — Berita', 'andalan-mutu-energi' ),
		'footer_social'     => __( 'Footer 5 — Media Sosial', 'andalan-mutu-energi' ),
		'legal'             => __( 'Footer — Tautan Legal', 'andalan-mutu-energi' ),
	);
}

/**
 * Register content types and menu locations.
 *
 * @return void
 */
function ame_register_content() {
	$labels = array(
		'ame_hero'  => array(
			'name'          => __( 'Hero Beranda', 'andalan-mutu-energi' ),
			'singular_name' => __( 'Hero Beranda', 'andalan-mutu-energi' ),
			'add_new_item'  => __( 'Tambah Hero', 'andalan-mutu-energi' ),
			'edit_item'     => __( 'Edit Hero', 'andalan-mutu-energi' ),
		),
		'ame_promo' => array(
			'name'          => __( 'Kartu Layanan', 'andalan-mutu-energi' ),
			'singular_name' => __( 'Kartu Layanan', 'andalan-mutu-energi' ),
			'add_new_item'  => __( 'Tambah Kartu Layanan', 'andalan-mutu-energi' ),
			'edit_item'     => __( 'Edit Kartu Layanan', 'andalan-mutu-energi' ),
		),
		'ame_show'  => array(
			'name'          => __( 'Slide Informasi', 'andalan-mutu-energi' ),
			'singular_name' => __( 'Slide Informasi', 'andalan-mutu-energi' ),
			'add_new_item'  => __( 'Tambah Slide', 'andalan-mutu-energi' ),
			'edit_item'     => __( 'Edit Slide', 'andalan-mutu-energi' ),
		),
	);

	foreach ( ame_section_types() as $post_type => $label ) {
		$type_labels = array_merge(
			$labels[ $post_type ],
			array(
				'menu_name'     => $label,
				'add_new'       => __( 'Add New', 'andalan-mutu-energi' ),
				'new_item'      => $labels[ $post_type ]['singular_name'],
				'view_item'     => __( 'View Content Item', 'andalan-mutu-energi' ),
				'search_items'  => __( 'Cari Konten', 'andalan-mutu-energi' ),
				'not_found'     => __( 'No content found.', 'andalan-mutu-energi' ),
				'not_found_in_trash' => __( 'No content found in Trash.', 'andalan-mutu-energi' ),
			)
		);

		register_post_type(
			$post_type,
			array(
				'labels'             => $type_labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => false,
				'show_in_rest'       => true,
				'exclude_from_search' => true,
				'has_archive'        => false,
				'rewrite'            => false,
				'query_var'          => false,
				'menu_position'      => 25,
				'menu_icon'          => 'ame_hero' === $post_type ? 'dashicons-cover-image' : ( 'ame_promo' === $post_type ? 'dashicons-screenoptions' : 'dashicons-video-alt3' ),
				'supports'           => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
			)
		);
	}

	register_nav_menus( ame_menu_locations() );
}
add_action( 'init', 'ame_register_content' );

/**
 * Return public posts of one homepage content type.
 *
 * @param string $post_type Homepage post type.
 * @return WP_Post[]
 */
function ame_get_sections( $post_type ) {
	if ( ! array_key_exists( $post_type, ame_section_types() ) ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'orderby'          => 'menu_order date',
			'order'            => 'ASC',
			'suppress_filters' => false,
		)
	);
}

/**
 * Render a featured image as decorative section artwork.
 *
 * @param int    $post_id  Post ID.
 * @param string $class    CSS class.
 * @param bool   $priority Whether the image should load immediately.
 * @return void
 */
function ame_section_artwork( $post_id, $class, $priority = false ) {
	$image_id = get_post_thumbnail_id( $post_id );
	if ( ! $image_id ) {
		return;
	}

	$attributes = array(
		'class'    => $class,
		'alt'      => '',
		'loading'  => $priority ? 'eager' : 'lazy',
		'decoding' => 'async',
	);
	if ( $priority ) {
		$attributes['fetchpriority'] = 'high';
	}

	echo wp_get_attachment_image( $image_id, 'full', false, $attributes );
}

/**
 * Get the menu assigned to a location.
 *
 * @param string $location Menu location key.
 * @return WP_Term|false
 */
function ame_get_assigned_menu( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return false;
	}
	return wp_get_nav_menu_object( $locations[ $location ] );
}

/**
 * Register the shared section meta box.
 *
 * @return void
 */
function ame_add_section_meta_boxes() {
	foreach ( ame_section_types() as $post_type => $label ) {
		add_meta_box(
			'ame-section-settings',
			__( 'Pengaturan Bagian Beranda', 'andalan-mutu-energi' ),
			'ame_render_section_meta_box',
			$post_type,
			'normal',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'ame_add_section_meta_boxes' );

/**
 * Print a text or URL field inside the section meta box.
 *
 * @param WP_Post $post    Current post.
 * @param string  $key     Meta key without the prefix.
 * @param string  $label   Field label.
 * @param string  $type    Input type.
 * @return void
 */
function ame_render_meta_input( $post, $key, $label, $type = 'text' ) {
	$meta_key = '_ame_' . $key;
	$value    = get_post_meta( $post->ID, $meta_key, true );
	?>
	<p>
		<label for="<?php echo esc_attr( $meta_key ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
		<input class="widefat" id="<?php echo esc_attr( $meta_key ); ?>" name="ame_meta[<?php echo esc_attr( $key ); ?>]" type="<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $value ); ?>">
	</p>
	<?php
}

/**
 * Render section fields.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function ame_render_section_meta_box( $post ) {
	wp_nonce_field( 'ame_save_section', 'ame_section_nonce' );
	$is_show = 'ame_show' === $post->post_type;
	?>
	<p class="description">
		<?php esc_html_e( 'Use the Title for the heading, Excerpt for the subtitle or description, Featured Image for artwork, and Order to control the sequence.', 'andalan-mutu-energi' ); ?>
	</p>
	<?php
	if ( $is_show ) {
		ame_render_meta_input( $post, 'genre', __( 'Kategori / label kecil', 'andalan-mutu-energi' ) );
		ame_render_meta_input( $post, 'primary_label', __( 'Label aksi (contoh: Baca selengkapnya)', 'andalan-mutu-energi' ) );
		ame_render_meta_input( $post, 'primary_url', __( 'Destination URL', 'andalan-mutu-energi' ), 'url' );
		return;
	}

	ame_render_meta_input( $post, 'availability', __( 'Availability note (optional)', 'andalan-mutu-energi' ) );
	ame_render_meta_input( $post, 'primary_label', __( 'Primary link label', 'andalan-mutu-energi' ) );
	ame_render_meta_input( $post, 'primary_url', __( 'Primary link URL', 'andalan-mutu-energi' ), 'url' );
	ame_render_meta_input( $post, 'secondary_label', __( 'Secondary link label (optional)', 'andalan-mutu-energi' ) );
	ame_render_meta_input( $post, 'secondary_url', __( 'Secondary link URL (optional)', 'andalan-mutu-energi' ), 'url' );

	$theme = get_post_meta( $post->ID, '_ame_theme', true );
	?>
	<p>
		<label for="_ame_theme"><strong><?php esc_html_e( 'Color scheme', 'andalan-mutu-energi' ); ?></strong></label><br>
		<select id="_ame_theme" name="ame_meta[theme]">
			<option value="light" <?php selected( $theme, 'light' ); ?>><?php esc_html_e( 'Light', 'andalan-mutu-energi' ); ?></option>
			<option value="dark" <?php selected( $theme, 'dark' ); ?>><?php esc_html_e( 'Dark', 'andalan-mutu-energi' ); ?></option>
		</select>
	</p>
	<?php
	if ( 'ame_promo' === $post->post_type ) {
		ame_render_meta_input( $post, 'footnote', __( 'Footnote number (optional)', 'andalan-mutu-energi' ) );
	}
}

/**
 * Save section metadata.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function ame_save_section_meta( $post_id ) {
	if ( ! isset( $_POST['ame_section_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ame_section_nonce'] ) ), 'ame_save_section' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['ame_meta'] ) ) {
		return;
	}

	$post_type = get_post_type( $post_id );
	if ( ! array_key_exists( $post_type, ame_section_types() ) ) {
		return;
	}

	$input = (array) wp_unslash( $_POST['ame_meta'] );
	$text_fields = array( 'availability', 'primary_label', 'secondary_label', 'genre', 'footnote' );
	foreach ( $text_fields as $key ) {
		if ( isset( $input[ $key ] ) ) {
			update_post_meta( $post_id, '_ame_' . $key, sanitize_text_field( $input[ $key ] ) );
		}
	}
	foreach ( array( 'primary_url', 'secondary_url' ) as $key ) {
		if ( isset( $input[ $key ] ) ) {
			update_post_meta( $post_id, '_ame_' . $key, esc_url_raw( $input[ $key ] ) );
		}
	}
	if ( isset( $input['theme'] ) ) {
		update_post_meta( $post_id, '_ame_theme', 'dark' === $input['theme'] ? 'dark' : 'light' );
	}
}
add_action( 'save_post', 'ame_save_section_meta' );

/**
 * Add useful columns to the content lists.
 *
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function ame_section_admin_columns( $columns ) {
	$columns['ame_artwork'] = __( 'Artwork', 'andalan-mutu-energi' );
	$columns['menu_order']    = __( 'Order', 'andalan-mutu-energi' );
	return $columns;
}

/**
 * Render custom content-list columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 * @return void
 */
function ame_section_admin_column_content( $column, $post_id ) {
	if ( 'ame_artwork' === $column ) {
		echo get_the_post_thumbnail( $post_id, array( 80, 60 ), array( 'style' => 'width:64px;height:48px;object-fit:cover;' ) );
	}
	if ( 'menu_order' === $column ) {
		echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
	}
}

/**
 * Register custom-column hooks after translation loading is safe.
 *
 * @return void
 */
function ame_register_section_admin_columns() {
	foreach ( array_keys( ame_section_types() ) as $ame_post_type ) {
		add_filter( 'manage_' . $ame_post_type . '_posts_columns', 'ame_section_admin_columns' );
		add_action( 'manage_' . $ame_post_type . '_posts_custom_column', 'ame_section_admin_column_content', 10, 2 );
	}
}
add_action( 'init', 'ame_register_section_admin_columns', 20 );

<?php
/**
 * Customizer settings for global homepage text.
 *
 * Individual heroes, tiles, slides, images, and menus are managed through
 * their respective WordPress content screens rather than this file.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize a Customizer checkbox value.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function ame_sanitize_checkbox( $value ) {
	return ! empty( $value ) ? 1 : 0;
}

/**
 * Sanitize the analytics retention period.
 *
 * @param mixed $value Submitted number of days.
 * @return int
 */
function ame_sanitize_retention( $value ) {
	return max( 7, min( 365, absint( $value ) ) );
}

/**
 * Sanitize the login failure threshold.
 *
 * @param mixed $value Submitted threshold.
 * @return int
 */
function ame_sanitize_login_limit( $value ) {
	return max( 3, min( 20, absint( $value ) ) );
}

/**
 * Sanitize the 404 scan threshold.
 *
 * @param mixed $value Submitted threshold.
 * @return int
 */
function ame_sanitize_404_limit( $value ) {
	return max( 5, min( 100, absint( $value ) ) );
}

/**
 * Sanitize the temporary lockout duration.
 *
 * @param mixed $value Submitted minutes.
 * @return int
 */
function ame_sanitize_lockout( $value ) {
	return max( 5, min( 120, absint( $value ) ) );
}

/**
 * Register global settings. Text defaults are intentionally empty; the
 * optional demo installer populates them as editable Theme Mods on activation.
 * Feature toggles use safe, enabled-by-default values.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function ame_customize_register( $wp_customize ) {
	$wp_customize->add_panel(
		'ame_home_panel',
		array(
			'title'       => __( 'Pengaturan Beranda AME', 'andalan-mutu-energi' ),
			'description' => __( 'Pengaturan teks global. Gunakan menu Hero Beranda, Kartu Layanan, dan Slide Informasi untuk mengelola setiap bagian.', 'andalan-mutu-energi' ),
			'priority'    => 30,
		)
	);

	$wp_customize->add_section(
		'ame_content_section',
		array(
			'title' => __( 'Konten Beranda', 'andalan-mutu-energi' ),
			'panel' => 'ame_home_panel',
		)
	);
	$wp_customize->add_section(
		'ame_header_section',
		array(
			'title' => __( 'Panel Header', 'andalan-mutu-energi' ),
			'panel' => 'ame_home_panel',
		)
	);
	$wp_customize->add_section(
		'ame_footer_section',
		array(
			'title' => __( 'Konten Footer', 'andalan-mutu-energi' ),
			'panel' => 'ame_home_panel',
		)
	);
	$wp_customize->add_section(
		'ame_seo_section',
		array(
			'title'       => __( 'SEO & Data Perusahaan', 'andalan-mutu-energi' ),
			'description' => __( 'Metadata ringan untuk situs korporat. Output otomatis dinonaktifkan bila plugin SEO yang dikenali aktif.', 'andalan-mutu-energi' ),
			'panel'       => 'ame_home_panel',
		)
	);
	$wp_customize->add_section(
		'ame_security_section',
		array(
			'title'       => __( 'Keamanan & Performa', 'andalan-mutu-energi' ),
			'description' => __( 'Perlindungan browser dasar dan optimasi aset. Konfigurasi keamanan server tetap disarankan.', 'andalan-mutu-energi' ),
			'panel'       => 'ame_home_panel',
		)
	);
	$wp_customize->add_section(
		'ame_firewall_section',
		array(
			'title'       => __( 'Deteksi Serangan', 'andalan-mutu-energi' ),
			'description' => __( 'Respons otomatis bersifat sementara dan dapat dipulihkan dari menu Keamanan AME. Gunakan WAF/server rate limit untuk perlindungan lapisan jaringan.', 'andalan-mutu-energi' ),
			'panel'       => 'ame_home_panel',
		)
	);
	$wp_customize->add_section(
		'ame_login_section',
		array(
			'title'       => __( 'Login & CAPTCHA', 'andalan-mutu-energi' ),
			'description' => __( 'Form login native dengan CAPTCHA aritmatika lokal dan verifikasi kode email 2FA. Login default dapat dialihkan ke halaman Masuk; logout dan recovery WordPress tetap dipertahankan.', 'andalan-mutu-energi' ),
			'panel'       => 'ame_home_panel',
		)
	);
	$wp_customize->add_section(
		'ame_ux_section',
		array(
			'title' => __( 'Navigasi & UX', 'andalan-mutu-energi' ),
			'panel' => 'ame_home_panel',
		)
	);
	$wp_customize->add_section(
		'ame_colors_section',
		array(
			'title'       => __( 'Warna & Identitas', 'andalan-mutu-energi' ),
			'description' => __( 'Perubahan warna tampil langsung di preview. Gunakan warna utama yang cukup gelap dan periksa kontras teks setelah melakukan perubahan.', 'andalan-mutu-energi' ),
			'panel'       => 'ame_home_panel',
		)
	);
	$wp_customize->add_section(
		'ame_analytics_section',
		array(
			'title'       => __( 'Pengunjung & Privasi', 'andalan-mutu-energi' ),
			'description' => __( 'Statistik lokal tanpa cookie. IP/user-agent tidak disimpan mentah, DNT dihormati, dan data memiliki masa retensi.', 'andalan-mutu-energi' ),
			'panel'       => 'ame_home_panel',
		)
	);

	$fields = array(
		'ame_gallery_heading' => array(
			'label'   => __( 'Judul galeri informasi', 'andalan-mutu-energi' ),
			'section' => 'ame_content_section',
			'type'    => 'text',
		),
		'ame_search_placeholder' => array(
			'label'   => __( 'Teks placeholder pencarian', 'andalan-mutu-energi' ),
			'section' => 'ame_header_section',
			'type'    => 'text',
		),
		'ame_quick_links_heading' => array(
			'label'   => __( 'Judul tautan cepat', 'andalan-mutu-energi' ),
			'section' => 'ame_header_section',
			'type'    => 'text',
		),
		'ame_contact_title' => array(
			'label'   => __( 'Judul panel kontak', 'andalan-mutu-energi' ),
			'section' => 'ame_header_section',
			'type'    => 'text',
		),
		'ame_contact_message' => array(
			'label'   => __( 'Pesan panel kontak', 'andalan-mutu-energi' ),
			'section' => 'ame_header_section',
			'type'    => 'textarea',
		),
		'ame_footer_notes' => array(
			'label'       => __( 'Catatan footer (satu paragraf per baris)', 'andalan-mutu-energi' ),
			'section'     => 'ame_footer_section',
			'type'        => 'textarea',
			'sanitize'    => 'sanitize_textarea_field',
			'description' => __( 'Setiap baris yang terisi menjadi paragraf catatan tersendiri.', 'andalan-mutu-energi' ),
		),
		'ame_contact_info' => array(
			'label'       => __( 'Informasi kontak perusahaan', 'andalan-mutu-energi' ),
			'section'     => 'ame_footer_section',
			'type'        => 'textarea',
			'sanitize'    => 'wp_kses_post',
			'description' => __( 'HTML dasar, termasuk tautan, diperbolehkan.', 'andalan-mutu-energi' ),
		),
		'ame_copyright' => array(
			'label'       => __( 'Baris hak cipta', 'andalan-mutu-energi' ),
			'section'     => 'ame_footer_section',
			'type'        => 'text',
			'description' => __( 'Gunakan [year] untuk menampilkan tahun berjalan secara otomatis.', 'andalan-mutu-energi' ),
		),
		'ame_locale_label' => array(
			'label'   => __( 'Label wilayah', 'andalan-mutu-energi' ),
			'section' => 'ame_footer_section',
			'type'    => 'text',
		),
		'ame_locale_url' => array(
			'label'    => __( 'URL wilayah', 'andalan-mutu-energi' ),
			'section'  => 'ame_footer_section',
			'type'     => 'url',
			'sanitize' => 'esc_url_raw',
		),
		'ame_seo_description' => array(
			'label'       => __( 'Deskripsi SEO homepage', 'andalan-mutu-energi' ),
			'description' => __( 'Dipakai untuk meta description dan ringkasan berbagi sosial.', 'andalan-mutu-energi' ),
			'section'     => 'ame_seo_section',
			'type'        => 'textarea',
			'sanitize'    => 'sanitize_textarea_field',
		),
		'ame_seo_phone' => array(
			'label'   => __( 'Telepon untuk Schema Organization', 'andalan-mutu-energi' ),
			'section' => 'ame_seo_section',
			'type'    => 'text',
		),
		'ame_seo_email' => array(
			'label'    => __( 'Email untuk Schema Organization', 'andalan-mutu-energi' ),
			'section'  => 'ame_seo_section',
			'type'     => 'email',
			'sanitize' => 'sanitize_email',
		),
		'ame_seo_address' => array(
			'label'    => __( 'Alamat untuk Schema Organization', 'andalan-mutu-energi' ),
			'section'  => 'ame_seo_section',
			'type'     => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
		),
		'ame_security_headers_enabled' => array(
			'label'       => __( 'Aktifkan security headers dasar', 'andalan-mutu-energi' ),
			'description' => __( 'nosniff, Referrer-Policy, SAMEORIGIN, dan Permissions-Policy. Tidak menerapkan CSP agresif.', 'andalan-mutu-energi' ),
			'section'     => 'ame_security_section',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'ame_sanitize_checkbox',
		),
		'ame_firewall_enabled' => array(
			'label'       => __( 'Aktifkan deteksi & respons otomatis', 'andalan-mutu-energi' ),
			'description' => __( 'Menolak pola request berbahaya dan menerapkan blokir sementara. Administrator tetap dapat membuka blokir.', 'andalan-mutu-energi' ),
			'section'     => 'ame_firewall_section',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'ame_sanitize_checkbox',
		),
		'ame_login_protection_enabled' => array(
			'label'       => __( 'Lindungi login dari brute-force', 'andalan-mutu-energi' ),
			'description' => __( 'Menggunakan hash IP dan kegagalan autentikasi WordPress; username/password tidak disimpan.', 'andalan-mutu-energi' ),
			'section'     => 'ame_firewall_section',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'ame_sanitize_checkbox',
		),
		'ame_login_attempt_limit' => array(
			'label'       => __( 'Ambang kegagalan login', 'andalan-mutu-energi' ),
			'description' => __( 'Jumlah kegagalan dalam 15 menit sebelum blokir (3–20).', 'andalan-mutu-energi' ),
			'section'     => 'ame_firewall_section',
			'type'        => 'number',
			'default'     => 5,
			'sanitize'    => 'ame_sanitize_login_limit',
			'input_attrs' => array(
				'min'  => 3,
				'max'  => 20,
				'step' => 1,
			),
		),
		'ame_404_protection_enabled' => array(
			'label'       => __( 'Deteksi pemindaian 404', 'andalan-mutu-energi' ),
			'description' => __( 'Menghitung 404 publik dalam 10 menit; pengguna login tidak dihitung.', 'andalan-mutu-energi' ),
			'section'     => 'ame_firewall_section',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'ame_sanitize_checkbox',
		),
		'ame_404_attempt_limit' => array(
			'label'       => __( 'Ambang pemindaian 404', 'andalan-mutu-energi' ),
			'description' => __( 'Jumlah 404 sebelum blokir (5–100).', 'andalan-mutu-energi' ),
			'section'     => 'ame_firewall_section',
			'type'        => 'number',
			'default'     => 20,
			'sanitize'    => 'ame_sanitize_404_limit',
			'input_attrs' => array(
				'min'  => 5,
				'max'  => 100,
				'step' => 1,
			),
		),
		'ame_security_lockout_minutes' => array(
			'label'       => __( 'Durasi blokir sementara (menit)', 'andalan-mutu-energi' ),
			'description' => __( 'Berlaku untuk semua blokir otomatis (5–120 menit).', 'andalan-mutu-energi' ),
			'section'     => 'ame_firewall_section',
			'type'        => 'number',
			'default'     => 15,
			'sanitize'    => 'ame_sanitize_lockout',
			'input_attrs' => array(
				'min'  => 5,
				'max'  => 120,
				'step' => 1,
			),
		),
		'ame_frontend_login_enabled' => array(
			'label'       => __( 'Aktifkan form login front-end', 'andalan-mutu-energi' ),
			'description' => __( 'Halaman Masuk memakai shortcode [ame_login_form]. Jika nonaktif, tautan diarahkan ke wp-login.php.', 'andalan-mutu-energi' ),
			'section'     => 'ame_login_section',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'ame_sanitize_checkbox',
		),
		'ame_replace_wp_login' => array(
			'label'       => __( 'Jadikan halaman Masuk sebagai login default', 'andalan-mutu-energi' ),
			'description' => __( 'Alihkan tautan login dan wp-login.php ke halaman Masuk. Logout, lupa/reset password, registrasi, dan recovery tetap memakai endpoint WordPress.', 'andalan-mutu-energi' ),
			'section'     => 'ame_login_section',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'ame_sanitize_checkbox',
		),
		'ame_header_login_enabled' => array(
			'label'       => __( 'Tampilkan ikon Masuk/Keluar di header', 'andalan-mutu-energi' ),
			'description' => __( 'Nonaktif secara default agar susunan navigasi existing tidak berubah.', 'andalan-mutu-energi' ),
			'section'     => 'ame_login_section',
			'type'        => 'checkbox',
			'default'     => false,
			'sanitize'    => 'ame_sanitize_checkbox',
		),
		'ame_login_captcha_enabled' => array(
			'label'       => __( 'Aktifkan CAPTCHA pada form web login', 'andalan-mutu-energi' ),
			'description' => __( 'Soal aritmatika lokal dengan masa berlaku 2 jam dan honeypot. Tanpa request ke layanan CAPTCHA eksternal.', 'andalan-mutu-energi' ),
			'section'     => 'ame_login_section',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'ame_sanitize_checkbox',
		),
		'ame_2fa_email_enabled' => array(
			'label'       => __( 'Aktifkan verifikasi kode email (2FA)', 'andalan-mutu-energi' ),
			'description' => __( 'Setelah password dan CAPTCHA benar, kode 6 digit dikirim via wp_mail. Kode berlaku 10 menit; maksimal 5 percobaan dan 3 pengiriman per sesi.', 'andalan-mutu-energi' ),
			'section'     => 'ame_login_section',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'ame_sanitize_checkbox',
		),
		'ame_preload_hero' => array(
			'label'    => __( 'Preload gambar hero utama', 'andalan-mutu-energi' ),
			'section'  => 'ame_security_section',
			'type'     => 'checkbox',
			'default'  => true,
			'sanitize' => 'ame_sanitize_checkbox',
		),
		'ame_enable_breadcrumbs' => array(
			'label'    => __( 'Tampilkan breadcrumb pada halaman dalam', 'andalan-mutu-energi' ),
			'section'  => 'ame_ux_section',
			'type'     => 'checkbox',
			'default'  => true,
			'sanitize' => 'ame_sanitize_checkbox',
		),
		'ame_enable_back_to_top' => array(
			'label'    => __( 'Tampilkan tombol kembali ke atas', 'andalan-mutu-energi' ),
			'section'  => 'ame_ux_section',
			'type'     => 'checkbox',
			'default'  => true,
			'sanitize' => 'ame_sanitize_checkbox',
		),
		'ame_color_primary' => array(
			'label'    => __( 'Warna utama (navigasi & brand)', 'andalan-mutu-energi' ),
			'section'  => 'ame_colors_section',
			'type'     => 'color',
			'default'  => '#0b2d25',
			'sanitize' => 'sanitize_hex_color',
			'transport' => 'postMessage',
		),
		'ame_color_accent' => array(
			'label'    => __( 'Warna aksen (tombol & tautan)', 'andalan-mutu-energi' ),
			'section'  => 'ame_colors_section',
			'type'     => 'color',
			'default'  => '#b91f32',
			'sanitize' => 'sanitize_hex_color',
			'transport' => 'postMessage',
		),
		'ame_color_accent_dark' => array(
			'label'    => __( 'Warna aksen pada section gelap', 'andalan-mutu-energi' ),
			'section'  => 'ame_colors_section',
			'type'     => 'color',
			'default'  => '#ff7b87',
			'sanitize' => 'sanitize_hex_color',
			'transport' => 'postMessage',
		),
		'ame_color_text' => array(
			'label'    => __( 'Warna teks utama', 'andalan-mutu-energi' ),
			'section'  => 'ame_colors_section',
			'type'     => 'color',
			'default'  => '#10251f',
			'sanitize' => 'sanitize_hex_color',
			'transport' => 'postMessage',
		),
		'ame_color_muted' => array(
			'label'    => __( 'Warna teks sekunder', 'andalan-mutu-energi' ),
			'section'  => 'ame_colors_section',
			'type'     => 'color',
			'default'  => '#64736c',
			'sanitize' => 'sanitize_hex_color',
			'transport' => 'postMessage',
		),
		'ame_color_background' => array(
			'label'    => __( 'Warna background section terang & footer', 'andalan-mutu-energi' ),
			'section'  => 'ame_colors_section',
			'type'     => 'color',
			'default'  => '#f1f5f3',
			'sanitize' => 'sanitize_hex_color',
			'transport' => 'postMessage',
		),
		'ame_analytics_enabled' => array(
			'label'       => __( 'Aktifkan pencatatan kunjungan', 'andalan-mutu-energi' ),
			'description' => __( 'Pengguna login, bot, DNT/Global Privacy Control, dan permintaan non-GET tidak dicatat.', 'andalan-mutu-energi' ),
			'section'     => 'ame_analytics_section',
			'type'        => 'checkbox',
			'default'     => true,
			'sanitize'    => 'ame_sanitize_checkbox',
		),
		'ame_analytics_retention_days' => array(
			'label'       => __( 'Retensi data pengunjung (hari)', 'andalan-mutu-energi' ),
			'description' => __( 'Masukkan 7–365 hari. Data yang lebih lama dihapus otomatis.', 'andalan-mutu-energi' ),
			'section'     => 'ame_analytics_section',
			'type'        => 'number',
			'default'     => 90,
			'sanitize'    => 'ame_sanitize_retention',
			'input_attrs' => array(
				'min'  => 7,
				'max'  => 365,
				'step' => 1,
			),
		),
	);

	foreach ( $fields as $setting_id => $field ) {
		$wp_customize->add_setting(
			$setting_id,
			array(
				'default'           => isset( $field['default'] ) ? $field['default'] : '',
				'sanitize_callback' => isset( $field['sanitize'] ) ? $field['sanitize'] : 'sanitize_text_field',
				'transport'         => isset( $field['transport'] ) ? $field['transport'] : 'refresh',
			)
		);
		$control_args = array(
			'label'       => $field['label'],
			'description' => isset( $field['description'] ) ? $field['description'] : '',
			'section'     => $field['section'],
			'type'        => $field['type'],
			'settings'    => $setting_id,
			'input_attrs' => isset( $field['input_attrs'] ) ? $field['input_attrs'] : array(),
		);
		if ( 'color' === $field['type'] ) {
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $setting_id, $control_args ) );
		} else {
			$wp_customize->add_control( $setting_id, $control_args );
		}
	}
}
add_action( 'customize_register', 'ame_customize_register' );

/**
 * Return the built-in brand palette.
 *
 * @return array<string, string>
 */
function ame_color_defaults() {
	return array(
		'ame_color_primary'    => '#0b2d25',
		'ame_color_accent'     => '#b91f32',
		'ame_color_accent_dark' => '#ff7b87',
		'ame_color_text'       => '#10251f',
		'ame_color_muted'      => '#64736c',
		'ame_color_background' => '#f1f5f3',
	);
}

/**
 * Read and sanitize all color Theme Mods.
 *
 * @return array<string, string>
 */
function ame_get_color_settings() {
	$colors = array();
	foreach ( ame_color_defaults() as $key => $default ) {
		$value         = sanitize_hex_color( get_theme_mod( $key, $default ) );
		$colors[ $key ] = $value ? $value : $default;
	}
	return $colors;
}

/**
 * Create a darker companion color for dark sections.
 *
 * @param string $color Hex color.
 * @return string
 */
function ame_color_dark_variant( $color ) {
	$color = ltrim( (string) $color, '#' );
	if ( 3 === strlen( $color ) ) {
		$color = preg_replace( '/(.)/', '$1$1', $color );
	}
	if ( ! preg_match( '/^[0-9a-f]{6}$/i', (string) $color ) ) {
		return '#071d18';
	}
	$red   = (int) round( hexdec( substr( $color, 0, 2 ) ) * 0.65 );
	$green = (int) round( hexdec( substr( $color, 2, 2 ) ) * 0.65 );
	$blue  = (int) round( hexdec( substr( $color, 4, 2 ) ) * 0.65 );
	return sprintf( '#%02x%02x%02x', $red, $green, $blue );
}

/**
 * Build the inline CSS variables used by the front end.
 *
 * @return string
 */
function ame_get_custom_color_css() {
	$colors = ame_get_color_settings();
	$values = array(
		'--ame-black'      => ame_color_dark_variant( $colors['ame_color_primary'] ),
		'--ame-graphite'   => $colors['ame_color_primary'],
		'--ame-text'       => $colors['ame_color_text'],
		'--ame-muted'      => $colors['ame_color_muted'],
		'--ame-gray'       => $colors['ame_color_background'],
		'--ame-blue'       => $colors['ame_color_accent'],
		'--ame-blue-dark'  => $colors['ame_color_accent_dark'],
	);
	$rules = array();
	foreach ( $values as $name => $value ) {
		$rules[] = $name . ':' . $value . ';';
	}
	return ':root{' . implode( '', $rules ) . '}';
}

/**
 * Enqueue live color updates inside the Customizer preview.
 *
 * @return void
 */
function ame_customize_preview_assets() {
	wp_enqueue_script(
		'andalan-mutu-energi-customize-preview',
		get_template_directory_uri() . '/assets/js/customize-preview.js',
		array( 'customize-preview' ),
		AME_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'ame_customize_preview_assets' );

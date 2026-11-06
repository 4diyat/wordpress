<?php
/**
 * Not-found template.
 *
 * @package Andalan_Mutu_Energi
 */

get_header();
?>
<main id="primary" class="content-shell">
	<div class="singular-content">
		<header class="content-header">
			<h1 class="page-title"><?php esc_html_e( 'Halaman yang Anda cari tidak ditemukan.', 'andalan-mutu-energi' ); ?></h1>
			<p class="page-description"><?php esc_html_e( 'Periksa alamat atau coba cari dengan kata kunci lain.', 'andalan-mutu-energi' ); ?></p>
			<?php get_search_form(); ?>
		</header>
	</div>
</main>
<?php
get_footer();

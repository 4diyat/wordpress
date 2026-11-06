<?php
/**
 * Main posts template.
 *
 * @package Andalan_Mutu_Energi
 */

get_header();
?>
<main id="primary" class="content-shell">
	<?php ame_render_breadcrumbs(); ?>
	<header class="content-header">
		<h1 class="page-title"><?php esc_html_e( 'Informasi & berita', 'andalan-mutu-energi' ); ?></h1>
		<p class="page-description"><?php esc_html_e( 'Berita, panduan, dan pembaruan dari PT Andalan Mutu Energi.', 'andalan-mutu-energi' ); ?></p>
	</header>
	<?php if ( have_posts() ) : ?>
		<div class="post-list">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', get_post_type() );
			endwhile;
			?>
		</div>
		<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => __( 'Sebelumnya', 'andalan-mutu-energi' ), 'next_text' => __( 'Berikutnya', 'andalan-mutu-energi' ) ) ); ?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
	<?php endif; ?>
</main>
<?php
get_footer();

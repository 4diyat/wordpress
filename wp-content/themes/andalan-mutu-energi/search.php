<?php
/**
 * Template hasil pencarian.
 *
 * @package Andalan_Mutu_Energi
 */

get_header();
?>
<main id="primary" class="content-shell">
	<?php ame_render_breadcrumbs(); ?>
	<header class="content-header">
		<h1 class="page-title"><?php printf( esc_html__( 'Hasil pencarian untuk “%s”', 'andalan-mutu-energi' ), esc_html( get_search_query() ) ); ?></h1>
		<?php get_search_form(); ?>
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
		<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
	<?php endif; ?>
</main>
<?php
get_footer();

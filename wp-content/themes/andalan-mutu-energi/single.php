<?php
/**
 * Single post template.
 *
 * @package Andalan_Mutu_Energi
 */

get_header();
?>
<main id="primary" class="content-shell">
	<?php ame_render_breadcrumbs(); ?>
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'singular-content' ); ?>>
			<header class="content-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				<div class="entry-meta"><?php echo esc_html( get_the_date() ); ?> · <?php the_author(); ?></div>
			</header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="post-thumbnail"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>
			<div class="entry-content">
				<?php the_content(); ?>
				<?php wp_link_pages( array( 'before' => '<nav class="page-links">', 'after' => '</nav>' ) ); ?>
			</div>
		</article>
		<?php
		the_post_navigation(
			array(
				'prev_text' => '<span aria-hidden="true">←</span> %title',
				'next_text' => '%title <span aria-hidden="true">→</span>',
			)
		);
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
	endwhile;
	?>
</main>
<?php
get_footer();

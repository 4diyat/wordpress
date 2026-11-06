<?php
/**
 * Post card used in archives.
 *
 * @package Andalan_Mutu_Energi
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1"><?php the_post_thumbnail( 'large' ); ?></a>
	<?php endif; ?>
	<header>
		<?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' ); ?>
		<div class="entry-meta"><?php echo esc_html( get_the_date() ); ?> · <?php the_author(); ?></div>
	</header>
	<div class="entry-summary"><?php the_excerpt(); ?></div>
	<a class="read-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Baca selengkapnya', 'andalan-mutu-energi' ); ?><?php echo ame_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
</article>

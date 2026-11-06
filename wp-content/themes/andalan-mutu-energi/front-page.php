<?php
/**
 * Company front page. All section content comes from WordPress.
 *
 * @package Andalan_Mutu_Energi
 */

$heroes = ame_get_sections( 'ame_hero' );
$promos = ame_get_sections( 'ame_promo' );
$shows  = ame_get_sections( 'ame_show' );

get_header();
?>
<main id="primary">
	<?php if ( ! $heroes && ! $promos && ! $shows && current_user_can( 'edit_theme_options' ) ) : ?>
		<section class="content-shell setup-notice">
			<h1><?php esc_html_e( 'Bangun beranda Anda', 'andalan-mutu-energi' ); ?></h1>
			<p><?php esc_html_e( 'Tambahkan hero, kartu layanan, dan slide informasi, atau pasang konten demo yang dapat diedit.', 'andalan-mutu-energi' ); ?></p>
			<p><a class="setup-button" href="<?php echo esc_url( admin_url( 'themes.php?page=andalan-mutu-energi-setup' ) ); ?>"><?php esc_html_e( 'Buka Setup Beranda', 'andalan-mutu-energi' ); ?></a></p>
		</section>
	<?php endif; ?>

	<?php foreach ( $heroes as $index => $hero ) : ?>
		<?php
		$hero_id        = $hero->ID;
		$hero_theme     = 'dark' === get_post_meta( $hero_id, '_ame_theme', true ) ? 'dark' : 'light';
		$availability   = get_post_meta( $hero_id, '_ame_availability', true );
		$primary_label  = get_post_meta( $hero_id, '_ame_primary_label', true );
		$primary_url    = get_post_meta( $hero_id, '_ame_primary_url', true );
		$secondary_label = get_post_meta( $hero_id, '_ame_secondary_label', true );
		$secondary_url  = get_post_meta( $hero_id, '_ame_secondary_url', true );
		?>
		<section class="hero-section hero-section-<?php echo esc_attr( $index + 1 ); ?> section-<?php echo esc_attr( $hero_theme ); ?>" aria-labelledby="hero-title-<?php echo esc_attr( $hero_id ); ?>">
			<?php ame_section_artwork( $hero_id, 'hero-artwork', 0 === $index ); ?>
			<div class="hero-content">
				<?php if ( 0 === $index ) : ?>
					<h1 id="hero-title-<?php echo esc_attr( $hero_id ); ?>" class="hero-title"><?php echo esc_html( get_the_title( $hero_id ) ); ?></h1>
				<?php else : ?>
					<h2 id="hero-title-<?php echo esc_attr( $hero_id ); ?>" class="hero-title"><?php echo esc_html( get_the_title( $hero_id ) ); ?></h2>
				<?php endif; ?>
				<?php if ( $hero->post_excerpt ) : ?>
					<p class="hero-subtitle"><?php echo esc_html( $hero->post_excerpt ); ?></p>
				<?php endif; ?>
				<?php if ( $availability ) : ?>
					<p class="availability"><?php echo esc_html( $availability ); ?></p>
				<?php endif; ?>
				<div class="section-links">
					<?php if ( $primary_label && $primary_url ) : ?>
						<a class="text-link" href="<?php echo esc_url( $primary_url ); ?>"><?php echo esc_html( $primary_label ); ?><?php echo ame_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endif; ?>
					<?php if ( $secondary_label && $secondary_url ) : ?>
						<a class="text-link" href="<?php echo esc_url( $secondary_url ); ?>"><?php echo esc_html( $secondary_label ); ?><?php echo ame_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endforeach; ?>

	<?php if ( $promos ) : ?>
		<div class="promo-grid" id="layanan">
			<?php foreach ( $promos as $promo ) : ?>
				<?php
				$promo_id       = $promo->ID;
				$promo_title    = get_the_title( $promo_id );
				$promo_theme    = 'dark' === get_post_meta( $promo_id, '_ame_theme', true ) ? 'dark' : 'light';
				$footnote       = get_post_meta( $promo_id, '_ame_footnote', true );
				$availability   = get_post_meta( $promo_id, '_ame_availability', true );
				$primary_label  = get_post_meta( $promo_id, '_ame_primary_label', true );
				$primary_url    = get_post_meta( $promo_id, '_ame_primary_url', true );
				$secondary_label = get_post_meta( $promo_id, '_ame_secondary_label', true );
				$secondary_url  = get_post_meta( $promo_id, '_ame_secondary_url', true );
				?>
				<section class="promo-card section-<?php echo esc_attr( $promo_theme ); ?>" aria-labelledby="promo-title-<?php echo esc_attr( $promo_id ); ?>">
					<?php ame_section_artwork( $promo_id, 'promo-artwork' ); ?>
					<div class="promo-content">
						<h2 id="promo-title-<?php echo esc_attr( $promo_id ); ?>" class="promo-title"><?php echo esc_html( $promo_title ); ?></h2>

						<?php if ( $promo->post_excerpt ) : ?>
							<p class="promo-subtitle"><?php echo esc_html( $promo->post_excerpt ); ?><?php if ( $footnote ) : ?><sup><?php echo esc_html( $footnote ); ?></sup><?php endif; ?></p>
						<?php endif; ?>
						<?php if ( $availability ) : ?>
							<p class="availability"><?php echo esc_html( $availability ); ?></p>
						<?php endif; ?>
						<div class="section-links">
							<?php if ( $primary_label && $primary_url ) : ?>
								<a class="text-link" href="<?php echo esc_url( $primary_url ); ?>"><?php echo esc_html( $primary_label ); ?><?php echo ame_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
							<?php endif; ?>
							<?php if ( $secondary_label && $secondary_url ) : ?>
								<a class="text-link" href="<?php echo esc_url( $secondary_url ); ?>"><?php echo esc_html( $secondary_label ); ?><?php echo ame_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
							<?php endif; ?>
						</div>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $shows ) : ?>
		<?php $gallery_heading = get_theme_mod( 'ame_gallery_heading', '' ); ?>
		<section id="informasi" class="entertainment-section" aria-label="<?php esc_attr_e( 'Galeri informasi', 'andalan-mutu-energi' ); ?>">
			<?php if ( $gallery_heading ) : ?>
				<h2 class="entertainment-heading"><?php echo esc_html( $gallery_heading ); ?></h2>
			<?php endif; ?>
			<div class="gallery" data-gallery>
				<div class="gallery-viewport" data-gallery-viewport tabindex="0" role="region" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Slide informasi', 'andalan-mutu-energi' ); ?>">
					<ul class="gallery-track">
						<?php foreach ( $shows as $index => $show ) : ?>
							<?php
							$show_id      = $show->ID;
							$show_title   = get_the_title( $show_id );
							$action_label = get_post_meta( $show_id, '_ame_primary_label', true );
							$show_url     = get_post_meta( $show_id, '_ame_primary_url', true );
							$genre        = get_post_meta( $show_id, '_ame_genre', true );
							?>
							<li id="gallery-slide-<?php echo esc_attr( $show_id ); ?>" class="gallery-card" data-gallery-card data-gallery-title="<?php echo esc_attr( $show_title ); ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( sprintf( '%1$d dari %2$d', $index + 1, count( $shows ) ) ); ?>">
								<?php if ( $show_url ) : ?><a class="gallery-card-link" href="<?php echo esc_url( $show_url ); ?>" aria-label="<?php echo esc_attr( trim( $action_label . ': ' . $show_title, ': ' ) ); ?>"><?php else : ?><div class="gallery-card-link"><?php endif; ?>
									<?php ame_section_artwork( $show_id, 'gallery-artwork' ); ?>
									<div class="gallery-card-content">
										<div class="card-brand" aria-hidden="true"><?php echo ame_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span></div>
										<h3 class="show-title"><?php echo esc_html( $show_title ); ?></h3>
										<?php if ( $action_label ) : ?>
											<span class="card-action"><?php echo ame_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $action_label ); ?></span>
										<?php endif; ?>
										<p class="gallery-meta"><?php if ( $genre ) : ?><span class="gallery-genre"><?php echo esc_html( $genre ); ?></span><?php endif; ?><?php echo esc_html( $show->post_excerpt ); ?></p>
									</div>
								<?php if ( $show_url ) : ?></a><?php else : ?></div><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<button class="gallery-arrow gallery-prev" type="button" data-gallery-previous aria-label="<?php esc_attr_e( 'Slide sebelumnya', 'andalan-mutu-energi' ); ?>" disabled><?php echo ame_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<button class="gallery-arrow gallery-next" type="button" data-gallery-next aria-label="<?php esc_attr_e( 'Slide berikutnya', 'andalan-mutu-energi' ); ?>"><?php echo ame_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<div class="gallery-pagination" aria-label="<?php esc_attr_e( 'Pilih slide', 'andalan-mutu-energi' ); ?>">
					<?php foreach ( $shows as $index => $show ) : ?>
						<button class="gallery-dot<?php echo 0 === $index ? ' is-active' : ''; ?>" type="button" data-gallery-dot aria-controls="gallery-slide-<?php echo esc_attr( $show->ID ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Buka slide %1$d: %2$s', 'andalan-mutu-energi' ), $index + 1, get_the_title( $show->ID ) ) ); ?>"<?php echo 0 === $index ? ' aria-current="true"' : ''; ?>></button>
					<?php endforeach; ?>
				</div>
				<p class="screen-reader-text" aria-live="polite" aria-atomic="true" data-gallery-status></p>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();

<?php
/**
 * Lightweight SEO, social metadata, structured data, and breadcrumbs.
 *
 * The module intentionally steps aside when a dedicated SEO plugin is active.
 *
 * @package Andalan_Mutu_Energi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check for common SEO plugins that already output the same metadata.
 *
 * @return bool
 */
function ame_seo_plugin_active() {
	foreach ( array( 'WPSEO_VERSION', 'RANK_MATH_VERSION', 'SEOPRESS_VERSION', 'AIOSEO_VERSION' ) as $constant ) {
		if ( defined( $constant ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether the theme should output its own SEO metadata.
 *
 * @return bool
 */
function ame_seo_output_enabled() {
	return (bool) apply_filters( 'ame_seo_output_enabled', ! ame_seo_plugin_active() );
}

/**
 * Get a clean description for the current request.
 *
 * @return string
 */
function ame_get_seo_description() {
	$description = '';

	if ( is_front_page() ) {
		$description = get_theme_mod( 'ame_seo_description', '' );
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$description = get_post_meta( $post->ID, '_ame_seo_description', true );
			if ( ! $description ) {
				$description = $post->post_excerpt;
			}
			if ( ! $description ) {
				$description = wp_trim_words( $post->post_content, 30, '' );
			}
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$description = term_description( get_queried_object_id() );
	}

	if ( ! $description && ! is_front_page() ) {
		$description = get_theme_mod( 'ame_seo_description', '' );
	}
	if ( ! $description ) {
		$description = get_bloginfo( 'description' );
	}
	if ( ! $description ) {
		$description = get_bloginfo( 'name' );
	}

	$description = wp_strip_all_tags( strip_shortcodes( (string) $description ) );
	return trim( (string) preg_replace( '/\s+/', ' ', $description ) );
}

/**
 * Get the most useful social image for the current request.
 *
 * @return string
 */
function ame_get_seo_image() {
	if ( is_singular() ) {
		$image = get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
		if ( $image ) {
			return $image;
		}
	}

	if ( is_front_page() ) {
		$heroes = ame_get_sections( 'ame_hero' );
		if ( ! empty( $heroes[0] ) ) {
			$image = get_the_post_thumbnail_url( $heroes[0]->ID, 'large' );
			if ( $image ) {
				return $image;
			}
		}
	}

	$logo_id = get_theme_mod( 'custom_logo' );
	return $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'full' ) : '';
}

/**
 * Get the canonical-ish URL used by social metadata. WordPress core remains
 * responsible for the actual canonical link element.
 *
 * @return string
 */
function ame_get_seo_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return get_permalink( get_queried_object_id() );
	}
	return get_pagenum_link();
}

/**
 * Return social profile URLs from the assigned social menu.
 *
 * @return string[]
 */
function ame_get_social_profiles() {
	$profiles = array();
	$menu     = ame_get_assigned_menu( 'footer_social' );
	if ( ! $menu ) {
		return $profiles;
	}

	$items = wp_get_nav_menu_items( $menu->term_id );
	foreach ( (array) $items as $item ) {
		if ( ! empty( $item->url ) && filter_var( $item->url, FILTER_VALIDATE_URL ) ) {
			$profiles[] = esc_url_raw( $item->url );
		}
	}
	return array_values( array_unique( $profiles ) );
}

/**
 * Build breadcrumb items for both visible navigation and JSON-LD.
 *
 * @return array<int, array{label:string,url:string}>
 */
function ame_get_breadcrumb_items() {
	if ( is_front_page() ) {
		return array();
	}

	$items = array(
		array(
			'label' => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_home() ) {
		$page_id = (int) get_option( 'page_for_posts' );
		$items[] = array(
			'label' => $page_id ? get_the_title( $page_id ) : __( 'Informasi & berita', 'andalan-mutu-energi' ),
			'url'   => $page_id ? get_permalink( $page_id ) : home_url( '/' ),
		);
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			if ( is_page() && $post->post_parent ) {
				$ancestors = array_reverse( get_post_ancestors( $post ) );
				foreach ( $ancestors as $ancestor_id ) {
					$items[] = array(
						'label' => get_the_title( $ancestor_id ),
						'url'   => get_permalink( $ancestor_id ),
					);
				}
			} elseif ( is_single() ) {
				$categories = get_the_category( $post->ID );
				if ( ! empty( $categories[0] ) ) {
					$items[] = array(
						'label' => $categories[0]->name,
						'url'   => get_category_link( $categories[0] ),
					);
				}
			}
			$items[] = array(
				'label' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
			);
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$term_link = get_term_link( $term );
			$items[]   = array(
				'label' => $term->name,
				'url'   => is_wp_error( $term_link ) ? get_pagenum_link() : $term_link,
			);
		}
	} elseif ( is_search() ) {
		$items[] = array(
			'label' => sprintf( __( 'Pencarian: %s', 'andalan-mutu-energi' ), get_search_query() ),
			'url'   => get_search_link(),
		);
	} elseif ( is_404() ) {
		$items[] = array(
			'label' => __( 'Halaman tidak ditemukan', 'andalan-mutu-energi' ),
			'url'   => get_pagenum_link(),
		);
	} elseif ( is_archive() ) {
		$items[] = array(
			'label' => wp_strip_all_tags( get_the_archive_title() ),
			'url'   => get_pagenum_link(),
		);
	}

	return apply_filters( 'ame_breadcrumb_items', $items );
}

/**
 * Render visible breadcrumb navigation on non-homepage templates.
 *
 * @return void
 */
function ame_render_breadcrumbs() {
	if ( ! get_theme_mod( 'ame_enable_breadcrumbs', true ) || is_front_page() ) {
		return;
	}
	$items = ame_get_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return;
	}
	?>
	<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'andalan-mutu-energi' ); ?>">
		<ol>
			<?php foreach ( $items as $index => $item ) : ?>
				<li<?php echo count( $items ) - 1 === $index ? ' aria-current="page"' : ''; ?>>
					<?php if ( count( $items ) - 1 === $index ) : ?>
						<span><?php echo esc_html( $item['label'] ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Output description and social metadata.
 *
 * @return void
 */
function ame_output_seo_metadata() {
	if ( ! ame_seo_output_enabled() ) {
		return;
	}

	$description = ame_get_seo_description();
	$title       = wp_get_document_title();
	$url         = ame_get_seo_url();
	$image       = ame_get_seo_image();
	$type        = is_single() ? 'article' : 'website';
	?>
	<?php if ( ! is_singular() && ! is_search() && ! is_404() ) : ?>
	<link rel="canonical" href="<?php echo esc_url( $url ); ?>">
	<?php endif; ?>
	<meta name="description" content="<?php echo esc_attr( $description ); ?>">
	<meta property="og:locale" content="<?php echo esc_attr( get_locale() ); ?>">
	<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
	<meta property="og:url" content="<?php echo esc_url( $url ); ?>">
	<meta property="og:type" content="<?php echo esc_attr( $type ); ?>">
	<?php if ( $image ) : ?>
	<meta property="og:image" content="<?php echo esc_url( $image ); ?>">
	<meta property="og:image:alt" content="<?php echo esc_attr( $title ); ?>">
	<?php endif; ?>
	<meta name="twitter:card" content="<?php echo $image ? 'summary_large_image' : 'summary'; ?>">
	<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
	<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>">
	<?php if ( $image ) : ?>
	<meta name="twitter:image" content="<?php echo esc_url( $image ); ?>">
	<?php endif; ?>
	<?php
}
add_action( 'wp_head', 'ame_output_seo_metadata', 2 );

/**
 * Output Organization, WebSite, WebPage/Article, and breadcrumb schema.
 *
 * @return void
 */
function ame_output_structured_data() {
	if ( ! ame_seo_output_enabled() ) {
		return;
	}

	$home          = home_url( '/' );
	$organization  = $home . '#organization';
	$website       = $home . '#website';
	$description   = ame_get_seo_description();
	$company_description = get_theme_mod( 'ame_seo_description', get_bloginfo( 'description' ) );
	$company_description = trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $company_description ) ) );
	$image         = ame_get_seo_image();
	$logo_id       = get_theme_mod( 'custom_logo' );
	$logo          = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	$phone         = get_theme_mod( 'ame_seo_phone', '' );
	$email         = get_theme_mod( 'ame_seo_email', '' );
	$address       = get_theme_mod( 'ame_seo_address', '' );
	$social        = ame_get_social_profiles();
	$graph         = array();
	$organization_data = array(
		'@type'       => 'Organization',
		'@id'         => $organization,
		'name'        => get_bloginfo( 'name' ),
		'url'         => $home,
		'description' => $company_description,
	);
	if ( $logo ) {
		$organization_data['logo'] = array(
			'@type' => 'ImageObject',
			'url'   => $logo,
		);
	}
	if ( $social ) {
		$organization_data['sameAs'] = $social;
	}
	if ( $address ) {
		$organization_data['address'] = $address;
	}
	if ( $phone || $email ) {
		$contact = array(
			'@type'       => 'ContactPoint',
			'contactType' => 'customer support',
		);
		if ( $phone ) {
			$contact['telephone'] = $phone;
		}
		if ( $email ) {
			$contact['email'] = $email;
		}
		$organization_data['contactPoint'] = $contact;
	}
	$graph[] = $organization_data;
	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => $website,
		'url'             => $home,
		'name'            => get_bloginfo( 'name' ),
		'description'     => get_bloginfo( 'description' ),
		'publisher'       => array( '@id' => $organization ),
		'inLanguage'      => get_locale(),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	$page = array(
		'@type'       => 'WebPage',
		'@id'         => ame_get_seo_url() . '#webpage',
		'url'         => ame_get_seo_url(),
		'name'        => wp_get_document_title(),
		'description' => $description,
		'isPartOf'    => array( '@id' => $website ),
		'inLanguage'  => get_locale(),
	);
	if ( $image ) {
		$page['primaryImageOfPage'] = array(
			'@type' => 'ImageObject',
			'url'   => $image,
		);
	}
	$graph[] = $page;

	if ( is_single() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$author = get_the_author_meta( 'display_name', $post->post_author );
			$graph[] = array(
				'@type'            => 'Article',
				'@id'              => get_permalink( $post ) . '#article',
				'headline'         => get_the_title( $post ),
				'description'      => $description,
				'mainEntityOfPage' => array( '@id' => $page['@id'] ),
				'datePublished'    => get_the_date( 'c', $post ),
				'dateModified'     => get_the_modified_date( 'c', $post ),
				'author'           => array(
					'@type' => 'Person',
					'name'  => $author ? $author : get_bloginfo( 'name' ),
				),
				'publisher'        => array( '@id' => $organization ),
			);
		}
	}

	$breadcrumbs = ame_get_breadcrumb_items();
	if ( count( $breadcrumbs ) > 1 ) {
		$breadcrumb_elements = array();
		foreach ( $breadcrumbs as $index => $item ) {
			$breadcrumb_elements[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $item['label'],
				'item'     => $item['url'],
			);
		}
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => ame_get_seo_url() . '#breadcrumb',
			'itemListElement' => $breadcrumb_elements,
		);
	}

	$json = wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
	// Prevent a user-controlled value from closing the script element.
	$json = str_replace( '</', '<\/', (string) $json );
	echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is encoded and script-closing sequences are escaped above.
}
add_action( 'wp_head', 'ame_output_structured_data', 30 );

/**
 * Keep internal search results and error pages out of search indexes when the
 * theme is responsible for SEO output.
 *
 * @param array<string, mixed> $robots Robots directives.
 * @return array<string, mixed>
 */
function ame_seo_robots( $robots ) {
	if ( ame_seo_output_enabled() && ( is_search() || is_404() ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'ame_seo_robots' );

/**
 * Add a description field to normal posts and pages.
 *
 * @return void
 */
function ame_add_seo_meta_boxes() {
	foreach ( array( 'post', 'page' ) as $post_type ) {
		add_meta_box(
			'ame-seo-settings',
			__( 'SEO & Berbagi Sosial', 'andalan-mutu-energi' ),
			'ame_render_seo_meta_box',
			$post_type,
			'normal',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'ame_add_seo_meta_boxes' );

/**
 * Render the SEO description field.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function ame_render_seo_meta_box( $post ) {
	wp_nonce_field( 'ame_save_seo_meta', 'ame_seo_nonce' );
	$value = get_post_meta( $post->ID, '_ame_seo_description', true );
	?>
	<p>
		<label for="ame_seo_description"><strong><?php esc_html_e( 'Deskripsi SEO', 'andalan-mutu-energi' ); ?></strong></label><br>
		<textarea class="widefat" id="ame_seo_description" name="ame_seo_description" rows="4" maxlength="300"><?php echo esc_textarea( $value ); ?></textarea>
	</p>
	<p class="description"><?php esc_html_e( 'Gunakan ringkasan yang jelas, idealnya sekitar 140–160 karakter. Jika kosong, theme memakai kutipan atau isi post secara otomatis.', 'andalan-mutu-energi' ); ?></p>
	<?php
}

/**
 * Save the SEO description securely.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function ame_save_seo_meta( $post_id ) {
	if ( ! isset( $_POST['ame_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ame_seo_nonce'] ) ), 'ame_save_seo_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['ame_seo_description'] ) ) {
		update_post_meta( $post_id, '_ame_seo_description', sanitize_textarea_field( wp_unslash( $_POST['ame_seo_description'] ) ) );
	}
}
add_action( 'save_post', 'ame_save_seo_meta' );

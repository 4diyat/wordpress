<?php
/**
 * Form pencarian untuk template standar.
 *
 * @package Andalan_Mutu_Energi
 */
?>
<form class="inline-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="template-search-field"><?php esc_html_e( 'Cari:', 'andalan-mutu-energi' ); ?></label>
	<input id="template-search-field" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Cari', 'andalan-mutu-energi' ); ?>">
	<button type="submit"><?php esc_html_e( 'Cari', 'andalan-mutu-energi' ); ?></button>
</form>

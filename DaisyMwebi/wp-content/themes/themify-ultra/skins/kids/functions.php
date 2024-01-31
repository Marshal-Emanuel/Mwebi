<?php
/**
 * Custom functions specific to the skin
 *
 * @package Themify Ultra
 */

/**
 * Load Google web fonts required for the skin
 *
 * @since 1.4.9
 * @return array
 */
function themify_theme_kids_google_fonts( $fonts ) {
	if ( 'off' !== _x( 'on', 'Open Sans font: on or off', 'themify' ) ) {
		$fonts['OpenSans'] = 'Open+Sans:300,400,400i,600,700';
	}
	if ( 'off' !== _x( 'on', 'Quicksand font: on or off', 'themify' ) ) {
		$fonts['Quicksand'] = 'Quicksand:300,400,400i,500,600,700';
	}
	return $fonts;
}
add_filter( 'themify_theme_google_fonts', 'themify_theme_kids_google_fonts' );
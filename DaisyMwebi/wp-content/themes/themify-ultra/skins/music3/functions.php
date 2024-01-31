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
function themify_theme_music3_google_fonts( $fonts ){
	if ( 'off' !== _x( 'on', 'Jost font: on or off', 'themify' ) ){
		$fonts['jost'] = 'Jost:ital,wght@0,400;0,500;0,600;0,700;1,400;1,700,700italic';
	}
	return $fonts;
}
add_filter( 'themify_theme_google_fonts', 'themify_theme_music3_google_fonts' );
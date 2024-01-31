<?php
/**
 * Themify Compatibility Code
 *
 * @package Themify
 */

/**
 * Client Portal
 * @link https://client-portal.io
 */
class Themify_Compat_lecoClientPortal {

	static function init() {
		if ( ! is_admin() ) {
			add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ), 1 );
		}

		/* disable Builder for Client Portal post type */
		add_filter( 'tb_is_disabled_for_leco_client', '__return_true' );
	}

	public static function template_redirect() {
		if ( is_singular( 'leco_client' ) ) {
			remove_action( 'template_redirect', array( 'Themify_Enqueue_Assets', 'start_buffer' ) );
		}
	}
}
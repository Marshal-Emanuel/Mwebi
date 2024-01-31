<?php
/**
 * Themify Compatibility Code
 *
 * @package Themify
 */

/**
 * Web Stories
 * @link https://wordpress.org/plugins/web-stories/
 */
class Themify_Compat_webstories {

	static function init() {
		if ( ! is_admin() ) {
			add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ), 1 );
		}

		/* disable Builder for Web Stories post type */
		add_filter( 'tb_is_disabled_for_web-story', '__return_true' );
	}

	/*
	 * @ref #9540
	 */
	public static function template_redirect() {
		if ( is_singular( 'web-story' ) ) {
			remove_action( 'template_redirect', array( 'Themify_Enqueue_Assets', 'start_buffer' ) );
		}
	}
}
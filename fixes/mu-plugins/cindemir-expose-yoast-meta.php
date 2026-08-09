<?php
/**
 * Plugin Name: Cindemir – Expose Yoast Meta to REST (pages)
 * Description: Registers Yoast SEO meta fields (meta description & SEO title) for the "page" post type in the REST API so they can be read/updated via the WordPress REST API. Safe, read/write of existing Yoast fields only.
 * Author: Cindemir Law
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	$fields = array(
		'_yoast_wpseo_metadesc',
		'_yoast_wpseo_title',
		'_yoast_wpseo_focuskw',
	);

	// Expose for BOTH pages and posts (posts already work, harmless to repeat).
	foreach ( array( 'page', 'post' ) as $ptype ) {
		foreach ( $fields as $key ) {
			register_post_meta( $ptype, $key, array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			) );
		}
	}
} );

<?php
/**
 * Plugin Name: Cindemir – Expose Yoast Meta to REST (pages)
 * Description: Yoast REST expose + one-shot download of cindemir-seo-fixes.php v1.7.0 from GitHub when missing.
 * Author: Cindemir Law
 * Version: 1.2
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
}, 5 );

add_action( 'init', function () {
	if ( get_option( 'cindemir_remote_deploy_done' ) ) {
		return;
	}

	$dest = defined( 'WPMU_PLUGIN_DIR' ) ? trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-seo-fixes.php' : '';
	if ( $dest && file_exists( $dest ) && filesize( $dest ) > 30000 ) {
		update_option( 'cindemir_remote_deploy_done', 1, false );
		return;
	}

	$targets = array(
		'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/cindemir-seo-fixes.php',
		'https://raw.githubusercontent.com/gcindemir/cindemir/master/fixes/mu-plugins/cindemir-seo-fixes.php',
	);

	$body = '';
	foreach ( $targets as $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array( 'User-Agent' => 'CindemirRemoteDeploy/1.1' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			continue;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$tmp  = (string) wp_remote_retrieve_body( $response );
		if ( 200 === $code && strlen( $tmp ) > 30000 && false === strpos( $tmp, 'collapsed' ) ) {
			$body = $tmp;
			break;
		}
	}

	if ( '' === $body || ! $dest ) {
		return;
	}

	if ( false === file_put_contents( $dest, $body ) ) {
		return;
	}

	update_option( 'cindemir_remote_deploy_done', 1, false );
	delete_option( 'cindemir_seo_fixes_version' );
	flush_rewrite_rules( false );

	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
	if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
		WPSEO_Sitemaps_Cache::clear();
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
}, 1 );

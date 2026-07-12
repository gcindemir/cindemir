<?php
/**
 * Plugin Name: Cindemir Remote Deploy (one-shot)
 * Description: Downloads clean cindemir-seo-fixes.php v1.7.0 from GitHub and replaces corrupt local copy.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	if ( get_option( 'cindemir_remote_deploy_done' ) ) {
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
				'headers' => array( 'User-Agent' => 'CindemirRemoteDeploy/1.0' ),
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

	if ( '' === $body || ! defined( 'WPMU_PLUGIN_DIR' ) ) {
		return;
	}

	$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-seo-fixes.php';
	$ok   = file_put_contents( $dest, $body );
	if ( false === $ok ) {
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

<?php
/**
 * Plugin Name: Cindemir Force Purge v4
 * Description: One-shot WP Rocket purge on shutdown (after all plugins load).
 * Version: 4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'shutdown', function () {
	if ( get_option( 'cindemir_force_purge_v4_done' ) ) {
		return;
	}
	update_option( 'cindemir_force_purge_v4_done', 1, false );
	delete_option( 'cindemir_seo_fixes_version' );
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'rocket_clean_minify' ) ) {
		rocket_clean_minify();
	}
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
}, 0 );

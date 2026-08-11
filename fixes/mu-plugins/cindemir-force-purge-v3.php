<?php
/**
 * Plugin Name: Cindemir Force Purge v3
 * Description: One-shot WP Rocket purge after deploy (runs on wp_loaded).
 * Version: 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_loaded', function () {
	if ( get_option( 'cindemir_force_purge_v3_done' ) ) {
		return;
	}
	update_option( 'cindemir_force_purge_v3_done', 1, false );
	delete_option( 'cindemir_seo_fixes_version' );
	flush_rewrite_rules( false );
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'rocket_clean_minify' ) ) {
		rocket_clean_minify();
	}
}, 99 );

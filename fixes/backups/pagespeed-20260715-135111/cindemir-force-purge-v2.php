<?php
/**
 * Plugin Name: Cindemir Force Purge v2
 * Description: One-shot WP Rocket + object cache purge after deploy (2026-07-12).
 * Version: 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_loaded', function () {
	if ( get_option( 'cindemir_force_purge_v2_done' ) ) {
		return;
	}
	update_option( 'cindemir_force_purge_v2_done', 1, false );
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
	if ( function_exists( 'rocket_clean_minify' ) ) {
		rocket_clean_minify();
	}
	if ( function_exists( 'run_rocket_bot' ) ) {
		run_rocket_bot( 'cache-purge' );
	}
}, 99 );

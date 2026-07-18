<?php
/**
 * Plugin Name: Cindemir Cache Purge (one-shot)
 * Description: Purges WP Rocket, Yoast sitemap cache, and rewrite rules once after upload.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	if ( get_option( 'cindemir_purge_done_v1' ) ) {
		return;
	}
	update_option( 'cindemir_purge_done_v1', 1, false );
	flush_rewrite_rules( false );
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
	if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
		WPSEO_Sitemaps_Cache::clear();
	}
	delete_transient( 'wpseo_sitemap_cache_validator_page' );
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
		LiteSpeed_Cache_API::purge_all();
	}
}, 1 );

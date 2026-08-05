<?php
/**
 * Plugin Name: Cindemir Bypass Rocket Cache
 * Description: Forces WP Rocket to skip page cache once and purge stale HTML.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'rocket_override_donotcachepage', '__return_true', 1 );
add_filter( 'do_rocket_generate_caching_files', '__return_false', 1 );

add_action( 'plugins_loaded', function () {
	if ( get_option( 'cindemir_bypass_rocket_done' ) ) {
		return;
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'rocket_clean_minify' ) ) {
		rocket_clean_minify();
	}
	delete_option( 'cindemir_seo_fixes_version' );
	update_option( 'cindemir_bypass_rocket_done', 1, false );
}, 99 );

add_action( 'wp_footer', function () {
	echo "\n<!-- cindemir-bypass-rocket active -->\n";
}, 999 );

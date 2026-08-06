<?php
/**
 * Plugin Name: Cindemir Diag
 * Description: Temporary deploy diagnostic — confirms mu-plugins load.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'send_headers', function () {
	if ( ! headers_sent() ) {
		header( 'X-Cindemir-Diag: active', false );
	}
}, 0 );

add_action( 'wp_footer', function () {
	echo "\n<!-- cindemir-diag active -->\n";
}, 999 );

add_action( 'shutdown', function () {
	if ( get_option( 'cindemir_diag_purge_done' ) ) {
		return;
	}
	update_option( 'cindemir_diag_purge_done', 1, false );
	delete_option( 'cindemir_seo_fixes_version' );
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
}, 0 );

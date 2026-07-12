<?php
/**
 * One-shot: pull cindemir-footer-rocket.php v1.0.2 from GitHub, purge cache, self-delete.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function () {
		if ( get_option( 'cindemir_pull_footer_rocket_102' ) ) {
			return;
		}

		$url  = 'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/footer-email-social-baro-917b/fixes/mu-plugins/cindemir-footer-rocket.php';
		$resp = wp_remote_get( $url, array( 'timeout' => 45, 'headers' => array( 'User-Agent' => 'CindemirPull/1.0.2' ) ) );
		if ( is_wp_error( $resp ) ) {
			return;
		}

		$body = (string) wp_remote_retrieve_body( $resp );
		if ( 200 !== (int) wp_remote_retrieve_response_code( $resp ) || strlen( $body ) < 3000 || false === strpos( $body, 'preg_replace_callback' ) ) {
			return;
		}

		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-footer-rocket.php';
		if ( false === file_put_contents( $dest, $body ) ) {
			return;
		}

		delete_option( 'cindemir_footer_rocket_v1' );
		delete_option( 'cindemir_footer_rocket_v101' );
		delete_option( 'cindemir_footer_rocket_v102' );

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		update_option( 'cindemir_pull_footer_rocket_102', 1, false );
		@unlink( __FILE__ );
	},
	0
);

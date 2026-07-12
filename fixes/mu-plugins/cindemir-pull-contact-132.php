<?php
/**
 * One-shot: pull contact-fixes v1.3.2 from GitHub, purge cache, self-delete.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function () {
		if ( get_option( 'cindemir_pull_contact_132_done' ) ) {
			return;
		}

		$url  = 'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/footer-email-social-baro-917b/fixes/mu-plugins/cindemir-contact-fixes.php';
		$resp = wp_remote_get(
			$url,
			array(
				'timeout' => 45,
				'headers' => array( 'User-Agent' => 'CindemirPull/1.3.2' ),
			)
		);

		if ( is_wp_error( $resp ) ) {
			return;
		}

		$body = (string) wp_remote_retrieve_body( $resp );
		if ( 200 !== (int) wp_remote_retrieve_response_code( $resp ) || strlen( $body ) < 20000 || false === strpos( $body, '1.3.2' ) ) {
			return;
		}

		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-contact-fixes.php';
		if ( false === file_put_contents( $dest, $body ) ) {
			return;
		}

		@unlink( WPMU_PLUGIN_DIR . '/cindemir-footer-live.php' );
		@unlink( WPMU_PLUGIN_DIR . '/cindemir-deploy-footer-once.php' );
		@unlink( WPMU_PLUGIN_DIR . '/zzz-deploy-test-917b.php' );

		delete_option( 'cindemir_contact_fixes_version' );
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		update_option( 'cindemir_pull_contact_132_done', 1, false );
		@unlink( __FILE__ );
	},
	0
);

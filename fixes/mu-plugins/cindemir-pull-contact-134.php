<?php
/**
 * One-shot: pull contact-fixes v1.3.4 (rocket_buffer footer), purge cache, self-delete.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function () {
		if ( get_option( 'cindemir_pull_contact_134_done' ) ) {
			return;
		}

		$url  = 'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/footer-email-social-baro-917b/fixes/mu-plugins/cindemir-contact-fixes.php';
		$resp = wp_remote_get(
			$url,
			array(
				'timeout' => 45,
				'headers' => array( 'User-Agent' => 'CindemirPull/1.3.4' ),
			)
		);

		if ( is_wp_error( $resp ) ) {
			return;
		}

		$body = (string) wp_remote_retrieve_body( $resp );
		if ( 200 !== (int) wp_remote_retrieve_response_code( $resp ) || strlen( $body ) < 20000 || false === strpos( $body, 'enhance_page_html' ) ) {
			return;
		}

		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-contact-fixes.php';
		if ( false === file_put_contents( $dest, $body ) ) {
			return;
		}

		delete_option( 'cindemir_contact_fixes_version' );

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$dir = WP_CONTENT_DIR . '/cache/wp-rocket';
			if ( is_dir( $dir ) ) {
				$it = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
					RecursiveIteratorIterator::CHILD_FIRST
				);
				foreach ( $it as $file ) {
					$file->isDir() ? @rmdir( $file->getRealPath() ) : @unlink( $file->getRealPath() );
				}
			}
		}

		update_option( 'cindemir_pull_contact_134_done', 1, false );
		@unlink( __FILE__ );
	},
	0
);

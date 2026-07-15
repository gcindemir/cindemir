<?php
/**
 * One-shot: pull cindemir-seo-fixes.php v1.6.0 from GitHub, purge caches, self-delete.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function () {
		if ( get_option( 'cindemir_pull_seo_160_done' ) ) {
			return;
		}

		$url  = 'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/gsc-seo-quick-wins-917b/fixes/mu-plugins/cindemir-seo-fixes.php';
		$resp = wp_remote_get(
			$url,
			array(
				'timeout' => 45,
				'headers' => array( 'User-Agent' => 'CindemirPull/1.6.0' ),
			)
		);
		if ( is_wp_error( $resp ) ) {
			return;
		}

		$body = (string) wp_remote_retrieve_body( $resp );
		if ( 200 !== (int) wp_remote_retrieve_response_code( $resp ) || strlen( $body ) < 20000 || false === strpos( $body, 'fix_og_tags_html' ) ) {
			return;
		}

		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-seo-fixes.php';
		if ( false === file_put_contents( $dest, $body ) ) {
			return;
		}

		delete_option( 'cindemir_seo_fixes_version' );
		delete_option( 'cindemir_seo_titles_v160_applied' );

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
			WPSEO_Sitemaps_Cache::clear();
		}

		update_option( 'cindemir_pull_seo_160_done', 1, false );
		@unlink( __FILE__ );
	},
	0
);

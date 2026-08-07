<?php
/**
 * Plugin Name: Cindemir Cache Purge + Services Blank Rescue
 * Description: Purges caches; emergency-disables broken Services rewrite; pulls fixed mu-plugins.
 * Version: 1.3
 * SERVICES_BLANK_FIX_20260715
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Before Services start_buffer (0 or 2): disable broken rewrite that blanked /services/.
 * Safe once Cindemir_Services_Page::VERSION >= 1.0.3 (PCRE-safe inject + buffer order).
 */
add_action(
	'template_redirect',
	static function () {
		if ( ! class_exists( 'Cindemir_Services_Page', false ) ) {
			return;
		}
		$ver = (string) Cindemir_Services_Page::VERSION;
		if ( version_compare( $ver, '1.0.3', '>=' ) ) {
			return;
		}
		remove_action( 'template_redirect', array( 'Cindemir_Services_Page', 'start_buffer' ), 2 );
		remove_action( 'template_redirect', array( 'Cindemir_Services_Page', 'start_buffer' ), 0 );
		remove_action( 'wp_head', array( 'Cindemir_Services_Page', 'print_assets' ), 40 );
		$GLOBALS['cindemir_services_rescue_active'] = true;
	},
	-1
);

add_action(
	'wp_head',
	static function () {
		if ( empty( $GLOBALS['cindemir_services_rescue_active'] ) ) {
			return;
		}
		if ( ! function_exists( 'is_page' ) || ! is_page( array( 18, 2638, 2637, 56 ) ) ) {
			return;
		}
		echo '<style id="cindemir-services-rescue-undo">#top.page-id-18 #main > *,#top.page-id-2638 #main > *,#top.page-id-2637 #main > *,#top.page-id-56 #main > *{display:revert!important}</style>';
	},
	99
);

add_action(
	'init',
	static function () {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return;
		}
		$done_key = 'cindemir_elena_ru_bio_20260718';
		if ( get_option( $done_key ) ) {
			// Still allow one-shot cache purge flag path below once.
		} else {
			$branch = 'cursor/cindemirlaw-seo-tasks-d204';
			$files  = array(
				'cindemir-contact-fixes.php' => 20000,
				'cindemir-services-page.php' => 10000,
				'cindemir-seo-fixes.php'     => 148000,
			);
			$bases  = array(
				'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/',
				'https://raw.githack.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/',
				'https://cdn.jsdelivr.net/gh/gcindemir/cindemir@' . $branch . '/fixes/mu-plugins/',
			);

			$ok = 0;
			foreach ( $files as $name => $min ) {
				$body = '';
				foreach ( $bases as $base ) {
					$url      = $base . $name . '?v=ELENA_ZARA_RU_BIO_20260718';
					$response = wp_remote_get(
						$url,
						array(
							'timeout' => 60,
							'headers' => array(
								'User-Agent'    => 'CindemirPurgeRescue/1.2',
								'Cache-Control' => 'no-cache',
							),
						)
					);
					if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
						continue;
					}
					$tmp = (string) wp_remote_retrieve_body( $response );
					if ( strlen( $tmp ) < $min || false === strpos( $tmp, 'ELENA_ZARA_RU_BIO_20260718' ) ) {
						continue;
					}
					$body = $tmp;
					break;
				}
				if ( '' === $body ) {
					continue;
				}
				$dest = trailingslashit( WPMU_PLUGIN_DIR ) . $name;
				if ( false !== file_put_contents( $dest, $body ) ) {
					if ( function_exists( 'opcache_invalidate' ) ) {
						@opcache_invalidate( $dest, true );
					}
					$ok++;
				}
			}

			if ( $ok >= 2 ) {
				update_option( $done_key, 1, false );
				delete_option( 'cindemir_seo_fixes_version' );
			}
		}

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
	},
	1
);

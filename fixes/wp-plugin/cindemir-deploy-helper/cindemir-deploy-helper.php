<?php
/**
 * Plugin Name: Cindemir Deploy Helper
 * Description: Restores mu-plugins from GitHub and applies mobile header branding. Upload via wp-admin, activate once, then deactivate.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Cindemir_Deploy_Helper {

	const VERSION = '1.0.0';

	const BRANCH = 'cursor/mobile-header-branding-adcd';

	/** @var array<string, string> */
	private static $files = array(
		'cindemir-seo-fixes.php'             => 'fixes/mu-plugins/cindemir-seo-fixes.php',
		'cindemir-expose-yoast-meta.php'     => 'fixes/mu-plugins/cindemir-expose-yoast-meta.php',
		'cindemir-contact-fixes.php'         => 'fixes/mu-plugins/cindemir-contact-fixes.php',
		'cindemir-mobile-brand.php'          => 'fixes/mu-plugins/cindemir-mobile-brand.php',
		'cindemir-mobile-header-branding.php'=> 'fixes/mu-plugins/cindemir-mobile-header-branding.php',
		'cindemir-purge-cache.php'           => 'fixes/mu-plugins/cindemir-purge-cache.php',
	);

	public static function boot() {
		register_activation_hook( __FILE__, array( __CLASS__, 'deploy_all' ) );
		add_action( 'init', array( __CLASS__, 'deploy_all' ), 0 );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		add_action( 'wp_head', array( __CLASS__, 'mobile_brand_fallback' ), 40 );
	}

	public static function deploy_all() {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return;
		}

		$done = (array) get_option( 'cindemir_deploy_helper_done', array() );
		$base = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . self::BRANCH . '/';
		$ok   = 0;

		foreach ( self::$files as $name => $path ) {
			$dest = trailingslashit( WPMU_PLUGIN_DIR ) . $name;
			if ( isset( $done[ $name ] ) && file_exists( $dest ) && filesize( $dest ) > 100 ) {
				continue;
			}

			$response = wp_remote_get(
				$base . $path,
				array(
					'timeout' => 45,
					'headers' => array( 'User-Agent' => 'CindemirDeployHelper/' . self::VERSION ),
				)
			);
			if ( is_wp_error( $response ) ) {
				continue;
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );
			if ( 200 !== $code || strlen( $body ) < 200 || false !== strpos( $body, 'collapsed' ) ) {
				continue;
			}
			if ( false === file_put_contents( $dest, $body ) ) {
				continue;
			}
			$done[ $name ] = strlen( $body );
			$ok++;
		}

		if ( $ok > 0 ) {
			update_option( 'cindemir_deploy_helper_done', $done, false );
			delete_option( 'cindemir_seo_fixes_version' );
			self::purge_caches();
		}
	}

	private static function purge_caches() {
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
			WPSEO_Sitemaps_Cache::clear();
		}
	}

	public static function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$done = (array) get_option( 'cindemir_deploy_helper_done', array() );
		if ( empty( $done ) ) {
			echo '<div class="notice notice-warning"><p>Cindemir Deploy Helper: mu-plugins not restored yet. Reload this page or check file permissions.</p></div>';
			return;
		}
		$list = implode( ', ', array_map( 'esc_html', array_keys( $done ) ) );
		echo '<div class="notice notice-success"><p>Cindemir Deploy Helper: restored ' . esc_html( (string) count( $done ) ) . ' file(s): ' . $list . '. You may deactivate this plugin.</p></div>';
	}

	public static function mobile_brand_fallback() {
		if ( is_admin() || wp_is_mobile() === false ) {
			return;
		}
		if ( false !== strpos( (string) ob_get_status(), 'cindemir' ) ) {
			return;
		}

		$lang = 'en';
		if ( function_exists( 'pll_current_language' ) ) {
			$pll = pll_current_language( 'slug' );
			if ( is_string( $pll ) && '' !== $pll ) {
				$lang = $pll;
			}
		} elseif ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
			$lang = ICL_LANGUAGE_CODE;
		} elseif ( ! empty( $_GET['lang'] ) ) {
			$lang = sanitize_key( wp_unslash( $_GET['lang'] ) );
		}

		$label = ( 'tr' === $lang ) ? 'Cindemir Hukuk Bürosu' : 'Cindemir Law Office';
		echo '<style id="cindemir-deploy-helper-brand">@media(max-width:989px){#header .logo a{display:inline-flex!important;align-items:center;gap:8px;max-width:calc(100vw - 110px)}#header .logo img{max-height:38px!important;max-width:38px!important}#header .logo.bg-logo img[src*="themes/enfold/images/layout/logo.png"]{display:none!important}#header .logo a::after{content:"' . esc_attr( $label ) . '";font-size:13px;font-weight:600;line-height:1.2;color:#336666;max-width:170px}}</style>';
	}
}

Cindemir_Deploy_Helper::boot();

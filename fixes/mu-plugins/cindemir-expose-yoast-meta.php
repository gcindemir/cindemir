<?php
/**
 * Plugin Name: Cindemir – Expose Yoast Meta to REST (pages)
 * Description: Yoast REST expose + one-shot download of cindemir-seo-fixes.php v1.7.0 from GitHub when missing.
 * Author: Cindemir Law
 * Version: 1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	$fields = array(
		'_yoast_wpseo_metadesc',
		'_yoast_wpseo_title',
		'_yoast_wpseo_focuskw',
	);

	foreach ( array( 'page', 'post' ) as $ptype ) {
		foreach ( $fields as $key ) {
			register_post_meta( $ptype, $key, array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			) );
		}
	}
}, 5 );

add_action( 'init', function () {
	if ( get_option( 'cindemir_remote_deploy_done' ) ) {
		return;
	}

	$dest = defined( 'WPMU_PLUGIN_DIR' ) ? trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-seo-fixes.php' : '';
	if ( $dest && file_exists( $dest ) && filesize( $dest ) > 30000 ) {
		update_option( 'cindemir_remote_deploy_done', 1, false );
		return;
	}

	$targets = array(
		'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/cindemir-seo-fixes.php',
		'https://raw.githubusercontent.com/gcindemir/cindemir/master/fixes/mu-plugins/cindemir-seo-fixes.php',
	);

	$body = '';
	foreach ( $targets as $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array( 'User-Agent' => 'CindemirRemoteDeploy/1.1' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			continue;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$tmp  = (string) wp_remote_retrieve_body( $response );
		if ( 200 === $code && strlen( $tmp ) > 30000 && false === strpos( $tmp, 'collapsed' ) ) {
			$body = $tmp;
			break;
		}
	}

	if ( '' === $body || ! $dest ) {
		return;
	}

	if ( false === file_put_contents( $dest, $body ) ) {
		return;
	}

	update_option( 'cindemir_remote_deploy_done', 1, false );
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
}, 1 );

if ( ! defined( 'CINDEMIR_MOBILE_HEADER_BRANDING_LOADED' ) ) {
	define( 'CINDEMIR_MOBILE_HEADER_BRANDING_LOADED', true );

	final class Cindemir_Mobile_Header_Branding {

		private static $labels = array(
			'en'      => 'Cindemir Law Office',
			'ru'      => 'Cindemir Law Office',
			'zh-hans' => 'Cindemir Law Office',
			'zh'      => 'Cindemir Law Office',
			'tr'      => 'Cindemir Hukuk Bürosu',
		);

		public static function boot() {
			add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 0 );
			add_action( 'wp_head', array( __CLASS__, 'print_styles' ), 50 );
		}

		public static function start_buffer() {
			if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
				return;
			}
			ob_start( array( __CLASS__, 'inject_branding' ) );
		}

		public static function inject_branding( $html ) {
			if ( ! is_string( $html ) || '' === $html || false !== strpos( $html, 'cindemir-mobile-brand' ) ) {
				return $html;
			}

			$markup = '<span class="cindemir-mobile-brand" aria-hidden="false">' . esc_html( self::brand_label() ) . '</span>';
			$patterns = array(
				"/(<span class='logo[^']*'[^>]*>.*?<\/a><\/span>)(<nav class='main_menu')/s",
				'/(<span class="logo[^"]*"[^>]*>.*?<\/a><\/span>)(<nav class="main_menu")/s',
			);

			foreach ( $patterns as $pattern ) {
				$count = 0;
				$new   = preg_replace( $pattern, '$1' . $markup . '$2', $html, 1, $count );
				if ( $count > 0 ) {
					return $new;
				}
			}

			return $html;
		}

		private static function brand_label() {
			$lang = self::current_lang();
			return isset( self::$labels[ $lang ] ) ? self::$labels[ $lang ] : self::$labels['en'];
		}

		private static function current_lang() {
			if ( function_exists( 'pll_current_language' ) ) {
				$lang = pll_current_language( 'slug' );
				if ( is_string( $lang ) && '' !== $lang ) {
					return $lang;
				}
			}
			if ( defined( 'ICL_LANGUAGE_CODE' ) && is_string( ICL_LANGUAGE_CODE ) && '' !== ICL_LANGUAGE_CODE ) {
				return ICL_LANGUAGE_CODE;
			}
			if ( ! empty( $_GET['lang'] ) ) {
				return sanitize_key( wp_unslash( $_GET['lang'] ) );
			}
			return 'en';
		}

		public static function print_styles() {
			if ( is_admin() ) {
				return;
			}
			echo '<style id="cindemir-mobile-header-branding">@media only screen and (max-width:989px){#header #header_main .av-logo-container .inner-container{display:flex;align-items:center}#header .logo{display:flex;align-items:center;max-width:calc(100vw - 110px)}#header .logo a{display:inline-flex!important;align-items:center;gap:10px;max-width:100%;text-decoration:none}#header .logo img{max-height:38px!important;max-width:38px!important;width:auto!important;height:auto!important;flex-shrink:0}#header .logo.bg-logo img[src*="themes/enfold/images/layout/logo.png"]{display:none!important}#header .cindemir-mobile-brand{display:inline-block;font-size:13px;font-weight:600;line-height:1.2;color:#336666;letter-spacing:.01em;white-space:normal;max-width:170px}}@media only screen and (min-width:990px){#header .cindemir-mobile-brand{display:none!important}}</style>';
		}
	}

	Cindemir_Mobile_Header_Branding::boot();
}

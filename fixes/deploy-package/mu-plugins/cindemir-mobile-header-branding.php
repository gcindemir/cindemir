<?php
/**
 * Plugin Name: Cindemir Mobile Header Branding
 * Description: Shows the Cindemir Law Office site name in the mobile header across all languages.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_MOBILE_HEADER_BRANDING_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_MOBILE_HEADER_BRANDING_LOADED', true );

final class Cindemir_Mobile_Header_Branding {

	const VERSION = '1.0.0';

	/** @var array<string, string> */
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
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( false !== strpos( $html, 'cindemir-mobile-brand' ) ) {
			return $html;
		}

		$label  = esc_html( self::brand_label() );
		$markup = '<span class="cindemir-mobile-brand" aria-hidden="false">' . $label . '</span>';

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
		if ( isset( self::$labels[ $lang ] ) ) {
			return self::$labels[ $lang ];
		}
		return self::$labels['en'];
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
		?>
<style id="cindemir-mobile-header-branding">
@media only screen and (max-width: 989px) {
	#header #header_main .av-logo-container .inner-container {
		display: flex;
		align-items: center;
	}
	#header .logo {
		display: flex;
		align-items: center;
		max-width: calc(100vw - 110px);
	}
	#header .logo a {
		display: inline-flex !important;
		align-items: center;
		gap: 10px;
		max-width: 100%;
		text-decoration: none;
	}
	#header .logo img {
		max-height: 38px !important;
		max-width: 38px !important;
		width: auto !important;
		height: auto !important;
		flex-shrink: 0;
	}
	#header .logo.bg-logo img[src*="themes/enfold/images/layout/logo.png"] {
		display: none !important;
	}
	#header .cindemir-mobile-brand {
		display: inline-block;
		font-size: 13px;
		font-weight: 600;
		line-height: 1.2;
		color: #336666;
		letter-spacing: 0.01em;
		white-space: normal;
		max-width: 170px;
	}
}
@media only screen and (min-width: 990px) {
	#header .cindemir-mobile-brand {
		display: none !important;
	}
}
</style>
		<?php
	}
}

Cindemir_Mobile_Header_Branding::boot();

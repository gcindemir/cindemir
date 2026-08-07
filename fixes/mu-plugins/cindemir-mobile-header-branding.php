<?php
/**
 * Plugin Name: Cindemir Mobile Header Branding
 * Description: Fallback site-name in header when SEO pack is not loaded.
 * Version: 1.0.4
 * Author: Cindemir Law Office
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( defined( 'CINDEMIR_MOBILE_HEADER_BRANDING_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_MOBILE_HEADER_BRANDING_LOADED', true );

add_action(
	'wp_head',
	static function () {
		if ( is_admin() ) {
			return;
		}
		// SEO pack owns branding (i18n + fit). Do not inject a second truncated ::after.
		if ( defined( 'CINDEMIR_SEO_FIXES_LOADED' ) ) {
			return;
		}
		$lang = 'en';
		if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
			$lang = (string) ICL_LANGUAGE_CODE;
		} elseif ( ! empty( $_GET['lang'] ) ) {
			$lang = sanitize_key( wp_unslash( $_GET['lang'] ) );
		}
		$labels = array(
			'en'      => 'Cindemir Law Office',
			'tr'      => 'Cindemir Hukuk Bürosu',
			'ru'      => 'Юридическая фирма\\A Cindemir',
			'zh-hans' => '辛德米尔\\A 律师事务所',
			'zh'      => '辛德米尔\\A 律师事务所',
		);
		$label = isset( $labels[ $lang ] ) ? $labels[ $lang ] : $labels['en'];
		echo '<style id="cindemir-mobile-brand">'
			. '#header .logo a{display:inline-flex!important;align-items:center;gap:8px}'
			. '#header .logo a::after{content:"' . $label . '";font-size:15px;font-weight:700;'
			. 'line-height:1.15;color:#244f4f;white-space:pre-line;overflow:visible;'
			. 'text-overflow:clip;max-width:min(220px,52vw)}'
			. '@media(max-width:989px){#header .logo img{max-height:36px!important;max-width:36px!important}}'
			. '</style>';
	},
	50
);

<?php
/**
 * Plugin Name: Cindemir Header Brand Fit
 * Description: Fits long i18n header brand labels (RU/ZH/TR) on mobile and desktop without ellipsis truncation.
 * Version: 1.1.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_HEADER_BRAND_FIT_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_HEADER_BRAND_FIT_LOADED', true );

/**
 * Late CSS beats cindemir-header-brand max-width/ellipsis rules (mobile + desktop).
 *
 * @return string
 */
function cindemir_header_brand_fit_css_tag() {
	return '<style id="cindemir-header-brand-fit" data-cindemir-brand-fit="1.1.0">'
		/* —— Desktop: kill ellipsis, give brand full natural width —— */
		. '@media only screen and (min-width:990px){'
		. '#top #header .logo,'
		. '#header .logo{'
		. 'max-width:min(380px,40vw)!important;'
		. 'overflow:visible!important;flex:0 1 auto!important;min-width:0!important}'
		. '#top #header .logo a,'
		. '#header .logo a{'
		. 'max-width:100%!important;overflow:visible!important;min-width:0!important}'
		. '#top #header .logo .cindemir-logo-text,'
		. '#header .logo .cindemir-logo-text,'
		. '#top #header .logo a::after,'
		. '#header .logo a::after{'
		. 'max-width:none!important;'
		. 'width:max-content!important;'
		. 'font-size:15px!important;font-weight:700!important;'
		. 'line-height:1.15!important;'
		. 'white-space:nowrap!important;'
		. 'overflow:visible!important;'
		. 'text-overflow:clip!important;'
		. 'display:inline-block!important}'
		. '#top #header .logo a:has(.cindemir-logo-text)::after,'
		. '#header .logo a:has(.cindemir-logo-text)::after{'
		. 'content:none!important;display:none!important}'
		/* Long locales: slightly tighter nav so brand + menu coexist */
		. 'html[lang^="ru"] #top #header .av-main-nav > li > a,'
		. 'html[lang^="zh"] #top #header .av-main-nav > li > a{'
		. 'padding-left:9px!important;padding-right:9px!important;'
		. 'font-size:13px!important}'
		. 'html[lang^="ru"] #top #header .av-main-nav > li.cindemir-lang-item > a,'
		. 'html[lang^="zh"] #top #header .av-main-nav > li.cindemir-lang-item > a{'
		. 'padding-left:6px!important;padding-right:6px!important}'
		. '}'
		/* —— Mobile: wrap up to 2 lines —— */
		. '@media only screen and (max-width:989px){'
		. '#top #header .logo,'
		. '#header .logo{'
		. 'max-width:calc(100vw - 68px)!important;'
		. 'flex:1 1 auto!important;min-width:0!important;'
		. 'height:auto!important;max-height:56px!important;overflow:visible!important}'
		. '#top #header .logo a,'
		. '#header .logo a{'
		. 'max-width:100%!important;width:auto!important;min-width:0!important;'
		. 'overflow:visible!important;gap:7px!important}'
		. '#top #header .logo .cindemir-logo-text,'
		. '#header .logo .cindemir-logo-text,'
		. '#top #header .logo a::after,'
		. '#header .logo a::after{'
		. 'max-width:calc(100vw - 96px)!important;'
		. 'width:auto!important;'
		. 'font-size:clamp(10.5px,2.85vw,12.5px)!important;'
		. 'font-weight:700!important;letter-spacing:0!important;'
		. 'line-height:1.2!important;'
		. 'white-space:normal!important;'
		. 'overflow:visible!important;'
		. 'text-overflow:clip!important;'
		. 'display:-webkit-box!important;'
		. '-webkit-box-orient:vertical!important;'
		. '-webkit-line-clamp:2!important;'
		. 'hyphens:manual}'
		. '#top #header .logo a:has(.cindemir-logo-text)::after,'
		. '#header .logo a:has(.cindemir-logo-text)::after{'
		. 'content:none!important;display:none!important}'
		. '#top #header .logo img,#top #header .logo picture{'
		. 'width:32px!important;height:32px!important;max-width:32px!important;max-height:32px!important;'
		. 'flex:0 0 32px!important}'
		. '}'
		. '</style>' . "\n";
}

add_action(
	'wp_head',
	static function () {
		if ( is_admin() ) {
			return;
		}
		echo cindemir_header_brand_fit_css_tag();
	},
	9999
);

add_filter(
	'rocket_buffer',
	static function ( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$tag = cindemir_header_brand_fit_css_tag();
		if ( false !== strpos( $html, 'id="cindemir-header-brand-fit"' ) ) {
			$html = preg_replace( '/<style id="cindemir-header-brand-fit"[^>]*>.*?<\/style>/is', $tag, $html, 1 );
		} else {
			$html = preg_replace( '/<\/head>/i', $tag . '</head>', $html, 1 );
		}
		return $html;
	},
	1000
);

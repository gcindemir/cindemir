<?php
/**
 * Plugin Name: Cindemir CLS Fix
 * Description: CLS + mobile LCP optimizations (WebP hero/team/logo, font-display, rocket_buffer) for cindemirlaw.com
 * Version: 1.4.8
 * Author: Cindemir Law Office
 */
defined( 'ABSPATH' ) || exit;

/**
 * Early head hints (buffer re-injects if optimizers strip them).
 */
add_action(
	'wp_head',
	static function () {
		if ( is_admin() ) {
			return;
		}
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">' . "\n";
		echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
		if ( is_front_page() || is_home() ) {
			echo '<link rel="preload" as="image" href="https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg.webp" type="image/webp" fetchpriority="high">' . "\n";
			echo '<link rel="preload" as="image" href="https://cindemirlaw.com/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg.webp" type="image/webp" fetchpriority="high">' . "\n";
		}
		echo "<!-- cindemir-cls-fix-head-v148 -->\n";
	},
	0
);

add_action(
	'wp_head',
	static function () {
		if ( is_admin() ) {
			return;
		}
		$css = <<<'CSS'
/* cindemir-cls-fix v1.4.8 */
@font-face{font-family:'entypo-fontello';font-weight:normal;font-style:normal;font-display:optional;src:url('https://cindemirlaw.com/wp-content/themes/enfold/config-templatebuilder/avia-template-builder/assets/fonts/entypo-fontello/entypo-fontello.woff2') format('woff2')}
@font-face{font-family:'entypo-fontello-enfold';font-weight:normal;font-style:normal;font-display:optional;src:url('https://cindemirlaw.com/wp-content/themes/enfold/config-templatebuilder/avia-template-builder/assets/fonts/entypo-fontello/entypo-fontello.woff2') format('woff2')}
/* Lock top meta bar so entypo icon font cannot push the hero (CLS) */
#top #header{min-height:98px!important}
#header_meta,.container_wrap_meta{min-height:34px!important;height:34px!important;max-height:34px!important;box-sizing:border-box!important;overflow:hidden!important}
#header_meta .social_bookmarks,#header_meta .phone-info,#header_meta .sub_menu{line-height:34px!important;margin:0!important}
#header_meta .social_bookmarks li,#header_meta .social_bookmarks a{display:inline-block!important;width:28px!important;height:34px!important;line-height:34px!important;min-width:28px!important}
#top #header #header_main,#top #header #header_main .container,#top #header #header_main .inner-container{min-height:64px!important;height:64px!important;max-height:64px!important;box-sizing:border-box!important;overflow:hidden!important}
#top #header .logo,span.logo.avia-standard-logo{display:flex!important;align-items:center!important;min-height:44px!important;height:44px!important;max-width:min(300px,42vw)!important}
#top #header .logo a{display:inline-flex!important;align-items:center!important;gap:10px!important;min-height:44px!important;height:44px!important}
#top #header .logo img,#top #header .logo picture{display:block!important;width:44px!important;height:44px!important;max-width:44px!important;max-height:44px!important;aspect-ratio:1/1!important;object-fit:contain!important;flex:0 0 44px!important}
#top #header .logo a::after,#header .logo a::after,.cindemir-logo-text{font-family:Georgia,"Times New Roman",Times,serif!important}
#top #header .logo a::after,#header .logo a::after{content:"Cindemir Law Office"!important;display:inline-block!important;box-sizing:border-box!important;width:min(200px,30vw)!important;min-width:min(180px,28vw)!important;max-width:min(220px,30vw)!important;font-size:18px!important;font-weight:700!important;line-height:1.15!important;color:#244f4f!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important}
@media (max-width:767px){#top #header .logo a::after{content:""!important;width:0!important;min-width:0!important}}
img:is([sizes="auto" i],[sizes^="auto," i]){contain-intrinsic-size:800px 450px!important}
.entry-content img[width][height]{height:auto;max-width:100%}
/* Force WebP even when Debloat CSS cache still references JPEG */
body.home #av_section_1.avia-section,body.home #av_section_1,body.home .avia-section.av-kb0cqels-258634c332e7b841ab39cd0403bc5dac,body.home #av_section_1 .av-section-color-overlay-wrap:not(:has(.cindemir-mobile-hero-photo))::before{background-image:url("https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg.webp")!important}
.flex_column[style*="team-4person"],.flex_column.av-kb0bnfzj-6ba9d0091795d881907fb18f16b60710,.flex_column.av-9w3v-11c2e6404523999c03ee02cbb906fb9e,#av_section_2 .flex_column.av-kb0bnfzj-6b756727d2887e26a4cf2233375d0c98{background-image:url("https://cindemirlaw.com/wp-content/uploads/2026/07/team-4person.jpg.webp")!important;background-size:cover!important;background-position:center center!important}
/* Critical mobile hero geometry (prevents CLS before async Debloat CSS) */
@media only screen and (max-width:989px){
body.home #av_section_1,body.home #av_section_1.avia-section{background-image:none!important;background-color:#1f4f4f!important;min-height:0!important;padding-top:0!important;padding-bottom:0!important}
body.home .cindemir-mobile-hero-photo{display:block!important;width:100%;line-height:0;margin:0;padding:0;min-height:52vh;height:52vh;max-height:560px}
body.home .cindemir-mobile-hero-photo img{display:block;width:100%;height:52vh;min-height:280px;max-height:560px;object-fit:cover;object-position:center 28%;aspect-ratio:auto;animation:none!important;opacity:1!important;transform:none!important}
}
CSS;
		echo '<style id="cindemir-cls-fix">' . $css . '</style>' . "\n";
		// Force Google Fonts display=optional even when Enfold injects the link later.
		echo '<script id="cindemir-font-display-guard">(function(){function f(l){try{if(!l||!l.href||l.href.indexOf("fonts.googleapis.com")<0)return;var u=l.href;if(/display=/.test(u))u=u.replace(/display=[a-z]+/i,"display=optional");else u+=(u.indexOf("?")>=0?"&":"?")+"display=optional";if(u!==l.href)l.href=u;}catch(e){}}document.querySelectorAll("link[href*=\\"fonts.googleapis.com\\"]").forEach(f);new MutationObserver(function(ms){ms.forEach(function(m){m.addedNodes&&m.addedNodes.forEach(function(n){if(n&&n.tagName==="LINK")f(n);});});}).observe(document.documentElement,{childList:true,subtree:true});})();</script>' . "\n";
	},
	1
);

/**
 * Swap known JPEG URLs to WebP without double-suffixing.
 *
 * @param string $html HTML.
 * @param string $jpg  Absolute JPEG URL.
 * @param string $webp Absolute WebP URL.
 * @return string
 */
function cindemir_cls_fix_swap_url( $html, $jpg, $webp ) {
	$token = '<!--CINDEMIR_WEBP_' . md5( $webp ) . '-->';
	$html  = str_replace( $webp, $token, $html );
	$html  = str_replace( $jpg, $webp, $html );
	// Escaped JSON/JS variants.
	$html  = str_replace( str_replace( '/', '\/', $jpg ), str_replace( '/', '\/', $webp ), $html );
	$html  = str_replace( $token, $webp, $html );
	return $html;
}

/**
 * Transform HTML for LCP/CLS (WP Rocket buffer + output buffer).
 *
 * @param string $html HTML document.
 * @return string
 */
function cindemir_cls_fix_transform_html( $html ) {
	if ( ! is_string( $html ) || '' === $html || false === stripos( $html, '<html' ) ) {
		return $html;
	}

	$hero_jpg  = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg';
	$hero_webp = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg.webp';
	$team_jpg  = 'https://cindemirlaw.com/wp-content/uploads/2026/07/team-4person.jpg';
	$team_webp = 'https://cindemirlaw.com/wp-content/uploads/2026/07/team-4person.jpg.webp';
	$logo_jpg  = 'https://cindemirlaw.com/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg';
	$logo_webp = 'https://cindemirlaw.com/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg.webp';

	// Drop competing JPEG preloads.
	$html = preg_replace( '#<link[^>]+rel=["\']preload["\'][^>]+540664430\.jpg(?!\.webp)[^>]*>#i', '', $html );
	$html = preg_replace( '#<link[^>]+rel=["\']preload["\'][^>]+logoicon-1-1-300x300\.jpg(?!\.webp)[^>]*>#i', '', $html );

	$html = cindemir_cls_fix_swap_url( $html, $hero_jpg, $hero_webp );
	$html = cindemir_cls_fix_swap_url( $html, $team_jpg, $team_webp );
	$html = cindemir_cls_fix_swap_url( $html, $logo_jpg, $logo_webp );

	// Lock mobile hero geometry in markup (prevents 800x533 → 52vh CLS).
	$html = preg_replace(
		'#(<div class="cindemir-mobile-hero-photo">\s*<img)([^>]*?)(/?>)#i',
		'$1$2 style="width:100%;height:52vh;min-height:280px;max-height:560px;object-fit:cover;object-position:center 28%;display:block" $3',
		$html,
		1
	);

	// Google Fonts display=optional.
	$html = preg_replace_callback(
		'#https://fonts\.googleapis\.com/css2?\?[^"\'\s<]+#i',
		static function ( $m ) {
			$url = $m[0];
			if ( false !== stripos( $url, 'display=' ) ) {
				return preg_replace( '/display=[a-z]+/i', 'display=optional', $url );
			}
			return $url . ( false !== strpos( $url, '?' ) ? '&' : '?' ) . 'display=optional';
		},
		$html
	);
	$html = str_replace(
		array( 'font-display: auto;', 'font-display:auto;', 'font-display: swap;', 'font-display:swap;' ),
		array( 'font-display: optional;', 'font-display:optional;', 'font-display: optional;', 'font-display:optional;' ),
		$html
	);

	if ( false === stripos( $html, 'rel="preconnect"' ) || false === stripos( $html, 'fonts.gstatic.com' ) ) {
		$hints = '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' .
			'<link rel="preconnect" href="https://fonts.googleapis.com">' .
			'<link rel="dns-prefetch" href="//fonts.gstatic.com">' .
			'<link rel="dns-prefetch" href="//fonts.googleapis.com">';
		$html = preg_replace( '/<head([^>]*)>/i', '<head$1>' . $hints, $html, 1 );
	}

	if ( ! preg_match( '#rel=["\']preload["\'][^>]+540664430\.jpg\.webp#i', $html ) ) {
		$preload = '<link rel="preload" as="image" href="' . $hero_webp . '" type="image/webp" fetchpriority="high">';
		$html    = preg_replace( '/<head([^>]*)>/i', '<head$1>' . $preload, $html, 1 );
	}

	// Route Debloat CSS through plugin proxy (WebP + font-display:optional; bypasses Rocket HTML cache).
	$html = preg_replace_callback(
		'#(https://cindemirlaw\.com/wp-content/cache/debloat/css/)([a-f0-9]+\.css)#i',
		static function ( $m ) {
			return 'https://cindemirlaw.com/wp-content/plugins/cindemir-cls-fix/css.php?f=' . rawurlencode( $m[2] );
		},
		$html
	);

	if ( false === strpos( $html, 'cindemir-cls-buf-v148' ) ) {
		$html .= "\n<!-- cindemir-cls-buf-v148 -->\n";
	}

	return $html;
}

// Run last on Rocket's HTML so Debloat/other buffer filters cannot reintroduce JPEGs after us.
add_filter( 'rocket_buffer', 'cindemir_cls_fix_transform_html', PHP_INT_MAX );

// Always buffer too (idempotent). Covers nowprocket / Rocket misses.
add_action(
	'wp',
	static function () {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( 'cindemir_cls_fix_transform_html' );
	},
	PHP_INT_MAX
);

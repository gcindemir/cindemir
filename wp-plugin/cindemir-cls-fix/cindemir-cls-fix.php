<?php
/**
 * Plugin Name: Cindemir CLS Fix
 * Description: CLS + mobile LCP optimizations (WebP hero, font-display, logo eager) for cindemirlaw.com
 * Version: 1.3.3
 * Author: Cindemir Law Office
 */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
	if ( is_front_page() || is_home() ) {
		echo '<link rel="preload" as="image" href="https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg.webp" type="image/webp" fetchpriority="high">' . "\n";
	}
	echo "<!-- cindemir-cls-fix-head-v133 -->\n";
}, 0 );

add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	$css = <<<'CSS'
/* cindemir-cls-fix v1.3.3 */
#top #header #header_main,#top #header #header_main .container,#top #header #header_main .inner-container{min-height:64px!important;height:64px!important;box-sizing:border-box!important}
#top #header .logo,span.logo.avia-standard-logo{display:flex!important;align-items:center!important;min-height:44px!important;height:44px!important;max-width:min(300px,42vw)!important}
#top #header .logo a{display:inline-flex!important;align-items:center!important;gap:10px!important;min-height:44px!important;height:44px!important}
#top #header .logo img,#top #header .logo picture{display:block!important;width:44px!important;height:44px!important;max-width:44px!important;max-height:44px!important;aspect-ratio:1/1!important;object-fit:contain!important;flex:0 0 44px!important}
#top #header .logo a::after,#header .logo a::after{content:"Cindemir Law Office"!important;display:inline-block!important;box-sizing:border-box!important;width:min(200px,30vw)!important;min-width:min(180px,28vw)!important;max-width:min(220px,30vw)!important;font-family:Georgia,"Times New Roman",Times,serif!important;font-size:18px!important;font-weight:700!important;line-height:1.15!important;color:#244f4f!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important}
@media (max-width:767px){#top #header .logo a::after{content:""!important;width:0!important;min-width:0!important}}
img:is([sizes="auto" i],[sizes^="auto," i]){contain-intrinsic-size:800px 450px!important}
.entry-content img[width][height]{height:auto;max-width:100%}
body.home #av_section_1.avia-section,body.home #av_section_1,body.home #av_section_1 .av-section-color-overlay-wrap:not(:has(.cindemir-mobile-hero-photo))::before{background-image:url("https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg.webp")!important}
@font-face{font-display:optional!important}
CSS;
	echo '<style id="cindemir-cls-fix">' . $css . '</style>' . "\n";
}, 1 );

add_action(
	'wp',
	function () {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( 'cindemir_cls_fix_buffer_v133' );
	},
	PHP_INT_MAX
);

function cindemir_cls_fix_buffer_v133( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}

	$hero_jpg  = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg';
	$hero_webp = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg.webp';
	$team_jpg  = 'https://cindemirlaw.com/wp-content/uploads/2026/07/team-4person.jpg';
	$team_webp = 'https://cindemirlaw.com/wp-content/uploads/2026/07/team-4person.jpg.webp';

	$html  = preg_replace( '#<link[^>]+rel=["\']preload["\'][^>]+540664430\.jpg(?!\.webp)[^>]*>#i', '', $html );
	$html .= "\n<!-- cindemir-cls-buf-v133 -->\n";

	$html = preg_replace( '#' . preg_quote( $hero_jpg, '#' ) . '(?!\.webp)#', $hero_webp, $html );
	$html = preg_replace( '#' . preg_quote( $team_jpg, '#' ) . '(?!\.webp)#', $team_webp, $html );

	$html = str_replace( 'font-display: auto;', 'font-display: optional;', $html );
	$html = str_replace( 'font-display:auto;', 'font-display:optional;', $html );

	return $html;
}

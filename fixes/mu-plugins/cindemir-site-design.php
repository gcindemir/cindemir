<?php
/**
 * Plugin Name: Cindemir Site Design
 * Description: Law-firm visual system + EN/RU/ZH homepage unify. Design/performance only — no SEO/meta/schema changes. Keeps WhatsApp/Joinchat.
 * Version: 1.0.1
 * SITE_DESIGN_20260807
 * HOME_LIKE_EN_20260807
 * ELENA_ZARA_RU_BIO_20260718
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_SITE_DESIGN_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_SITE_DESIGN_LOADED', true );

final class Cindemir_Site_Design {

	const VERSION = '1.0.1';
	const MARKER  = 'HOME_LIKE_EN_20260807';

	const HERO_WEBP = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg.webp';
	const TEAM_WEBP = 'https://cindemirlaw.com/wp-content/uploads/2026/07/team-4person.jpg.webp';

	public static function boot() {
		add_action( 'wp_head', array( __CLASS__, 'print_design_css' ), 40 );
		add_filter( 'rocket_buffer', array( __CLASS__, 'transform_html' ), 1005 );
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 0 );
	}

	/** Always buffer — Rocket may skip some URLs while still registering rocket_buffer. */
	public static function start_buffer() {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'transform_html' ) );
	}

	public static function print_design_css() {
		if ( is_admin() ) {
			return;
		}
		$hero = esc_url( self::HERO_WEBP );
		$team = esc_url( self::TEAM_WEBP );
		echo '<style id="cindemir-site-design">' . self::css( $hero, $team ) . '</style>' . "\n";
		echo '<!-- cindemir-site-design ' . esc_html( self::VERSION ) . ' ' . esc_html( self::MARKER ) . ' -->' . "\n";
	}

	/**
	 * @param string $html Full HTML.
	 * @return string
	 */
	public static function transform_html( $html ) {
		if ( ! is_string( $html ) || '' === $html || false === stripos( $html, '<body' ) ) {
			return $html;
		}
		// Never alter SEO machine tags.
		// (titles/meta/canonical/hreflang/json-ld left untouched.)

		$is_home = (bool) preg_match( '/<body[^>]*\bclass="[^"]*\bhome\b/i', $html );
		if ( $is_home ) {
			$html = self::unify_homepage( $html );
		}

		$html = self::ensure_design_marker( $html );
		return $html;
	}

	/**
	 * @param string $html HTML.
	 * @return string
	 */
	private static function unify_homepage( $html ) {
		$html = self::strip_home_slideshow( $html );
		$html = self::mark_home_hero_section( $html );
		$html = self::promote_lang_about_hero( $html );

		// Fix known broken RU About CTA (EN slug 404s under ?lang=ru).
		$html = str_replace(
			array(
				"href='https://cindemirlaw.com/about-us/?lang=ru'",
				'href="https://cindemirlaw.com/about-us/?lang=ru"',
			),
			array(
				"href='https://cindemirlaw.com/onas/?lang=ru'",
				'href="https://cindemirlaw.com/onas/?lang=ru"',
			),
			$html
		);

		if ( false === strpos( $html, 'cindemir-design-unify' ) ) {
			$html = preg_replace( '/<\/body>/i', '<!-- cindemir-design-unify HOME_LIKE_EN_20260807 -->' . "\n</body>", $html, 1 );
		}

		return $html;
	}

	/**
	 * ZH home: Welcome/Team sit above About — hide them and keep Chinese About as first screen.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private static function promote_lang_about_hero( $html ) {
		// ZH homepage builder page.
		if ( ! preg_match( '/<body[^>]*\bclass="[^"]*\bpage-id-2568\b/i', $html ) ) {
			return $html;
		}
		$out = preg_replace(
			'#(<div[^>]*id=[\'"]av_section_[123][\'"][^>]*class=[\'"])([^\'"]*)([\'"])#i',
			'$1$2 cindemir-hide-prehero$3',
			$html,
			3
		);
		return is_string( $out ) ? $out : $html;
	}

	/**
	 * Tag the About full-bleed overlay section.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private static function mark_home_hero_section( $html ) {
		// CSS selectors also contain this token — only skip if already applied to markup.
		if ( preg_match( '/\bclass=[\'"][^\'"]*\bcindemir-home-hero-section\b/i', $html ) ) {
			return $html;
		}

		// Prefer known Enfold About hashes (EN + RU/ZH).
		foreach ( array( 'av-kb0cqels-258634c332e7b841ab39cd0403bc5dac', 'av-kb0cqels-17c76a10768f591d25a945de8c928701' ) as $hash ) {
			if ( false === strpos( $html, $hash ) ) {
				continue;
			}
			$html2 = preg_replace(
				'#(\bclass=[\'"][^\'"]*\b' . preg_quote( $hash, '#' ) . '\b[^\'"]*)([\'"])#i',
				'$1 cindemir-home-hero-section$2',
				$html,
				1
			);
			if ( is_string( $html2 ) && $html2 !== $html ) {
				return $html2;
			}
		}

		// Generic overlay + full-stretch About section.
		$html2 = preg_replace_callback(
			'#<div([^>]*\bclass=[\'"](?=[^\'"]*\bavia-section\b)(?=[^\'"]*\bav-section-color-overlay-active\b)(?=[^\'"]*\bavia-full-stretch\b)[^\'"]*[\'"][^>]*)>#i',
			static function ( $m ) {
				$attrs = $m[1];
				if ( false !== stripos( $attrs, 'cindemir-home-hero-section' ) ) {
					return '<div' . $attrs . '>';
				}
				if ( preg_match( '/\bclass=([\'"])(.*?)\1/i', $attrs, $cm ) ) {
					$q     = $cm[1];
					$class = $cm[2] . ' cindemir-home-hero-section';
					$attrs = preg_replace( '/\bclass=([\'"]).*?\1/i', 'class=' . $q . $class . $q, $attrs, 1 );
				}
				return '<div' . $attrs . '>';
			},
			$html,
			1
		);
		return is_string( $html2 ) ? $html2 : $html;
	}

	/**
	 * Remove full_slider_1 block (nested divs) before first av_section_*.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private static function strip_home_slideshow( $html ) {
		$pos = stripos( $html, "id='full_slider_1'" );
		if ( false === $pos ) {
			$pos = stripos( $html, 'id="full_slider_1"' );
		}
		if ( false === $pos ) {
			return $html;
		}
		$open = strrpos( substr( $html, 0, $pos ), '<div' );
		if ( false === $open ) {
			return $html;
		}
		if ( ! preg_match( '/<div[^>]*id=[\'"]av_section_/i', $html, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
			return $html;
		}
		$end = (int) $m[0][1];
		return substr( $html, 0, $open ) . substr( $html, $end );
	}

	/**
	 * @param string $html HTML.
	 * @return string
	 */
	private static function ensure_design_marker( $html ) {
		if ( false !== strpos( $html, 'cindemir-site-design ' . self::VERSION ) ) {
			return $html;
		}
		$mark = '<!-- cindemir-site-design ' . self::VERSION . ' ' . self::MARKER . ' -->';
		if ( false !== stripos( $html, '</head>' ) ) {
			return preg_replace( '/<\/head>/i', $mark . "\n</head>", $html, 1 );
		}
		return $html;
	}

	/**
	 * @param string $hero Hero WebP URL.
	 * @param string $team Team WebP URL.
	 * @return string
	 */
	private static function css( $hero, $team ) {
		$hero = esc_url( self::HERO_WEBP );
		$team = esc_url( self::TEAM_WEBP );
		return <<<CSS
/* Cindemir Site Design — law firm visual layer */
:root{
  --cin-ink:#0b1f28;
  --cin-teal:#286060;
  --cin-teal-deep:#1f4f4f;
  --cin-teal-soft:#3a7a7a;
  --cin-sand:#f3f1ec;
  --cin-paper:#fbfaf7;
  --cin-line:rgba(11,31,40,.12);
  --cin-text:#1a2a32;
  --cin-muted:#5a6b73;
  --cin-header-h:64px;
  --cin-display:"Iowan Old Style","Palatino Linotype",Palatino,"Book Antiqua",Georgia,serif;
  --cin-sans:"Avenir Next","Segoe UI","Helvetica Neue",Arial,sans-serif;
}
html{scroll-behavior:smooth}
body.home,#top,#wrap_all{
  font-family:var(--cin-sans);
  color:var(--cin-text);
  background:var(--cin-paper);
}
#top .av-special-heading-tag,
#top h1,#top h2,#top h3{
  font-family:var(--cin-display);
  letter-spacing:.01em;
  font-weight:600;
}

/* —— Header polish (all pages) —— */
#top #header{
  background:rgba(251,250,247,.92)!important;
  backdrop-filter:saturate(1.1) blur(8px);
  border-bottom:1px solid var(--cin-line);
  box-shadow:none!important;
}
#top #header .av-main-nav > li > a .avia-menu-text{
  font-family:var(--cin-sans);
  font-weight:600;
  letter-spacing:.02em;
  text-transform:none;
}
#top #header .logo img,#top #header .logo svg{
  border-radius:6px;
}

/* —— Hide RU/ZH pre-hero clutter —— */
body.home #full_slider_1,
body.home .avia-fullwidth-slider.avia-builder-el-first,
body.home .cindemir-hide-prehero,
body.home.page-id-2568 #av_section_1,
body.home.page-id-2568 #av_section_2,
body.home.page-id-2568 #av_section_3{
  display:none!important;
  height:0!important;min-height:0!important;max-height:0!important;
  overflow:hidden!important;margin:0!important;padding:0!important;
  border:0!important;
}
/* ZH: About hero is #av_section_4 — keep it first on screen like EN. */
body.home.page-id-2568 #main{
  display:flex!important;
  flex-direction:column!important;
}
body.home.page-id-2568 #av_section_4,
body.home.page-id-2568 .cindemir-home-hero-section{
  order:-20!important;
}
body.home.page-id-2568 #av_section_5{order:20!important}

/* —— Unified homepage hero (EN/RU/ZH) —— */
body.home .cindemir-home-hero-section,
body.home #av_section_1.cindemir-home-hero-section,
body.home .avia-section.av-kb0cqels-258634c332e7b841ab39cd0403bc5dac,
body.home .avia-section.av-kb0cqels-17c76a10768f591d25a945de8c928701{
  background-image:url({$hero})!important;
  background-size:cover!important;
  background-position:50% 38%!important;
  background-attachment:scroll!important;
  background-color:var(--cin-ink)!important;
}
@media only screen and (min-width:990px){
  body.home .cindemir-home-hero-section.avia-section,
  body.home .avia-section.av-kb0cqels-258634c332e7b841ab39cd0403bc5dac,
  body.home .avia-section.av-kb0cqels-17c76a10768f591d25a945de8c928701{
    min-height:78vh!important;
  }
  body.home .cindemir-home-hero-section .av-section-color-overlay,
  body.home .avia-section.av-kb0cqels-258634c332e7b841ab39cd0403bc5dac .av-section-color-overlay,
  body.home .avia-section.av-kb0cqels-17c76a10768f591d25a945de8c928701 .av-section-color-overlay{
    opacity:1!important;
    background:linear-gradient(165deg,rgba(11,31,40,.25) 0%,rgba(11,31,40,.45) 40%,rgba(15,45,48,.88) 100%)!important;
  }
  body.home .cindemir-home-hero-section .av-special-heading-tag,
  body.home .avia-section.av-kb0cqels-258634c332e7b841ab39cd0403bc5dac .av-special-heading-tag,
  body.home .avia-section.av-kb0cqels-17c76a10768f591d25a945de8c928701 .av-special-heading-tag{
    font-size:clamp(2.4rem,4.2vw,3.6rem)!important;
    line-height:1.08!important;
    color:#fff!important;
    text-shadow:0 2px 24px rgba(0,0,0,.25);
  }
  body.home .cindemir-home-hero-section .avia_textblock,
  body.home .cindemir-home-hero-section .avia_textblock p,
  body.home .cindemir-home-hero-section .avia_textblock a{
    color:#fff!important;
  }
  body.home .cindemir-home-hero-section .avia_textblock p:nth-of-type(n+3){display:none!important}
  body.home .cindemir-home-hero-section .avia_textblock p:nth-of-type(2){
    display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;max-height:5.2em;
    font-size:1.12rem;line-height:1.5;opacity:.95;
  }
  body.home .cindemir-home-hero-section .avia-button{
    background:#fff!important;color:var(--cin-teal-deep)!important;border-color:#fff!important;
    border-radius:2px!important;padding:1rem 1.6rem!important;
    box-shadow:0 10px 30px rgba(0,0,0,.18);
    transition:transform .25s ease, box-shadow .25s ease;
  }
  body.home .cindemir-home-hero-section .avia-button:hover{
    transform:translateY(-2px);box-shadow:0 14px 34px rgba(0,0,0,.22);
  }
  body.home .cindemir-home-hero-section .avia-button .avia_iconbox_title{
    color:var(--cin-teal-deep)!important;font-weight:700!important;letter-spacing:.03em;
  }
}
@media only screen and (max-width:989px){
  body.home .cindemir-home-hero-section,
  body.home .cindemir-home-hero-section.avia-section,
  body.home .avia-section.av-kb0cqels-17c76a10768f591d25a945de8c928701,
  body.home .avia-section.av-kb0cqels-258634c332e7b841ab39cd0403bc5dac{
    background-image:none!important;
    background-color:var(--cin-teal-deep)!important;
    min-height:0!important;padding-top:0!important;padding-bottom:0!important;
  }
  body.home .cindemir-home-hero-section .av-parallax,
  body.home .cindemir-home-hero-section .av-section-color-overlay{display:none!important}
  body.home .cindemir-home-hero-section .av-section-color-overlay-wrap{
    display:flex!important;flex-direction:column!important;
  }
  body.home .cindemir-mobile-hero-photo{display:block!important;width:100%;order:-1;line-height:0;margin:0;padding:0}
  body.home .cindemir-mobile-hero-photo img{
    display:block;width:100%;height:52vh;min-height:280px;max-height:560px;
    object-fit:cover;object-position:center 28%;
  }
  body.home .cindemir-home-hero-section .container{
    background:var(--cin-teal-deep)!important;color:#fff!important;
    padding:1.15rem 1.2rem 1.6rem!important;width:100%!important;max-width:100%!important;
  }
  body.home .cindemir-home-hero-section .av-special-heading-tag{
    font-size:clamp(1.6rem,5.4vw,2.2rem)!important;line-height:1.12!important;color:#fff!important;
  }
  body.home .cindemir-home-hero-section .avia_textblock,
  body.home .cindemir-home-hero-section .avia_textblock p,
  body.home .cindemir-home-hero-section .avia_textblock a{color:#fff!important}
  body.home .cindemir-home-hero-section .avia_textblock p:nth-of-type(n+2){display:none!important}
  body.home .cindemir-home-hero-section .avia_textblock p:first-of-type{
    display:-webkit-box!important;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;
    max-height:3.2em;font-size:1.02rem;line-height:1.45;margin:0 0 .85rem!important;text-align:center!important;
  }
  body.home .cindemir-home-hero-section .flex_column.av_one_fifth{display:none!important}
  body.home .cindemir-home-hero-section .flex_column.av_three_fifth{
    width:100%!important;margin:0!important;left:auto!important;right:auto!important;
  }
  body.home .cindemir-home-hero-section .avia-button{
    background:#fff!important;color:var(--cin-teal-deep)!important;border-color:#fff!important;
    border-radius:2px!important;padding:.85rem 1.4rem!important;
  }
  body.home .cindemir-home-hero-section .avia-button .avia_iconbox_title{
    color:var(--cin-teal-deep)!important;font-weight:700!important;
  }
}

/* —— Content sections —— */
body.home .avia-section:not(.cindemir-home-hero-section):not(.cindemir-hide-prehero){
  background:var(--cin-paper)!important;
}
body.home .avia-section .av-special-heading-tag{
  color:var(--cin-ink)!important;
}
body.home .flex_column.av_one_third,
body.home .flex_column.av_one_half{
  transition:transform .28s ease;
}
@media (prefers-reduced-motion:no-preference){
  @keyframes cinRise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
  body.home .cindemir-mobile-hero-photo img{animation:cinRise .7s ease-out both}
  body.home .cindemir-home-hero-section .av-special-heading{animation:cinRise .7s .08s ease-out both}
  body.home .cindemir-home-hero-section .avia_textblock{animation:cinRise .7s .16s ease-out both}
  body.home .cindemir-home-hero-section .avia-button-wrap{animation:cinRise .7s .24s ease-out both}
  body.home .flex_column.av_one_third:hover{transform:translateY(-3px)}
}

/* Team photo columns */
.flex_column[style*="team-4person"],
.flex_column.av-kb0bnfzj-6ba9d0091795d881907fb18f16b60710,
.flex_column.av-kb0bnfzj-757c444f068caee82d34f767d5f676fb,
.flex_column.av-kb0bnfzj-6b756727d2887e26a4cf2233375d0c98{
  background-image:url({$team})!important;
  background-size:cover!important;
  background-position:center 28%!important;
  background-repeat:no-repeat!important;
}

/* —— Inner pages: calmer reading surface —— */
body:not(.home) #main{
  background:linear-gradient(180deg,var(--cin-sand) 0%,var(--cin-paper) 220px,var(--cin-paper) 100%);
}
body:not(.home) .entry-content-wrapper{
  font-size:1.05rem;line-height:1.7;
}
body:not(.home) .av-special-heading-tag,
body:not(.home) .entry-content-wrapper h1,
body:not(.home) .entry-content-wrapper h2{
  color:var(--cin-ink);
}

/* Buttons / links */
#top .avia-button.avia-color-theme-color,
#top a.avia-button{
  border-radius:2px!important;
}
#top a{text-underline-offset:2px}
#top #main a:hover{color:var(--cin-teal)!important}

/* Footer / socket already branded teal — keep cohesive */
#socket{
  background:var(--cin-teal)!important;
  border-top:0!important;
}
#socket a,#socket .copyright{color:rgba(255,255,255,.92)!important}

/* Do not fight WhatsApp FAB */
#cindemir-wa-fallback{z-index:99999}
CSS;
	}
}

Cindemir_Site_Design::boot();

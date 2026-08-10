<?php
/**
 * Plugin Name: Cindemir LCP Safe
 * Description: Design-safe LCP speedups — WebP hero img, no opacity:0 entrance on LCP nodes, clean robots.txt. Does not change layout/colors.
 * Version: 1.0.1
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_LCP_SAFE_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_LCP_SAFE_LOADED', true );

final class Cindemir_LCP_Safe {
	const VERSION = '1.0.1';
	const HERO_JPG  = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg';
	const HERO_WEBP = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg.webp';
	const HERO_768  = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430-768x512.jpg.webp';
	const HERO_800  = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430-800x430.jpg.webp';

	public static function boot() {
		add_action( 'wp_head', array( __CLASS__, 'print_css' ), 100 );
		add_action( 'wp_head', array( __CLASS__, 'print_preload' ), 1 );
		// Early output buffer so we still rewrite when Rocket is bypassed.
		// Must start BEFORE SEO (-999) / menu (-1000) so this callback runs LAST
		// and can convert the JPEG hero SEO injects.
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), -1001 );
		// Register rocket_buffer AFTER cindemir-seo-fixes (PHP_INT_MAX) so we win.
		add_action( 'wp_loaded', array( __CLASS__, 'register_late_buffers' ), 9999 );
		add_filter( 'robots_txt', array( __CLASS__, 'clean_robots_txt' ), 99999 );
		add_action( 'init', array( __CLASS__, 'maybe_clean_robots_request' ), 0 );
	}

	public static function register_late_buffers() {
		add_filter( 'rocket_buffer', array( __CLASS__, 'buffer' ), PHP_INT_MAX );
	}

	/**
	 * Keep entrance motion, but never hold LCP nodes at opacity:0 (fill-mode both).
	 * Same translate distance / timing as site-design — only opacity path changes.
	 */
	public static function print_css() {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="cindemir-lcp-safe" data-v="' . esc_attr( self::VERSION ) . '">'
			. '@media (prefers-reduced-motion:no-preference){'
			. '@keyframes cinRise{from{opacity:1;transform:translateY(14px)}to{opacity:1;transform:none}}'
			. '@keyframes cinRiseLcp{from{transform:translateY(10px)}to{transform:none}}'
			. 'body.home .cindemir-mobile-hero-photo img{'
			. 'opacity:1!important;animation:cinRiseLcp .55s ease-out both!important}'
			. 'body.home .cindemir-home-hero-section .av-special-heading,'
			. 'body.home .cindemir-home-hero-section .avia_textblock,'
			. 'body.home .cindemir-home-hero-section .avia-button-wrap{'
			. 'opacity:1!important}'
			. '}'
			. '@media (prefers-reduced-motion:reduce){'
			. 'body.home .cindemir-mobile-hero-photo img,'
			. 'body.home .cindemir-home-hero-section .av-special-heading,'
			. 'body.home .cindemir-home-hero-section .avia_textblock,'
			. 'body.home .cindemir-home-hero-section .avia-button-wrap{'
			. 'animation:none!important;opacity:1!important;transform:none!important}'
			. '}'
			. '</style>' . "\n";
	}

	/** Ensure homepage preloads the WebP that paint uses (not the JPEG). */
	public static function print_preload() {
		if ( is_admin() || ! ( is_front_page() || is_home() ) ) {
			return;
		}
		echo '<link rel="preload" as="image" href="' . esc_url( self::HERO_768 ) . '" type="image/webp" '
			. 'imagesrcset="' . esc_attr( self::HERO_800 . ' 800w, ' . self::HERO_768 . ' 768w, ' . self::HERO_WEBP . ' 1200w' ) . '" '
			. 'imagesizes="100vw" fetchpriority="high" data-cindemir-lcp-safe="' . esc_attr( self::VERSION ) . '">' . "\n";
	}

	public static function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'buffer' ) );
	}

	/**
	 * @param string $html HTML.
	 * @return string
	 */
	public static function buffer( $html ) {
		if ( ! is_string( $html ) || $html === '' ) {
			return $html;
		}

		$mark = '<!--cindemir-lcp-safe-v' . self::VERSION . '-->';
		if ( false === strpos( $html, $mark ) && false !== stripos( $html, '<body' ) ) {
			$html = preg_replace( '/<body\b[^>]*>/i', '$0' . $mark, $html, 1 );
		}

		// Unconditional hero JPEG → WebP (homepage band + any leftover).
		$html = self::upgrade_hero_img( $html );

		$html = preg_replace(
			'#<link\b[^>]*rel=(["\'])preload\1[^>]*540664430\.jpg(?!\.webp)[^>]*>\s*#i',
			'',
			$html
		);

		// Prefer existing WebP siblings for common above-fold JPEGs (same path + .webp).
		$replaced = preg_replace_callback(
			'#\bsrc=(["\'])(https?://(?:www\.)?cindemirlaw\.com/wp-content/uploads/[^"\']+\.(?:jpe?g|png))\1#i',
			static function ( $m ) {
				$quote = $m[1];
				$url   = $m[2];
				if ( preg_match( '/\.webp$/i', $url ) ) {
					return $m[0];
				}
				$candidates = array( $url . '.webp', preg_replace( '/\.(jpe?g|png)$/i', '.webp', $url ) );
				foreach ( $candidates as $webp ) {
					if ( ! is_string( $webp ) || $webp === '' ) {
						continue;
					}
					$path = wp_parse_url( $webp, PHP_URL_PATH );
					$abs  = $path ? ( rtrim( ABSPATH, '/' ) . $path ) : '';
					if ( $abs && is_readable( $abs ) ) {
						return 'src=' . $quote . esc_url( $webp ) . $quote;
					}
				}
				return $m[0];
			},
			$html
		);
		if ( is_string( $replaced ) ) {
			$html = $replaced;
		}

		if ( self::is_robots_request() || ( false !== strpos( $html, 'User-agent:' ) && false !== strpos( $html, '<link rel="alternate"' ) ) ) {
			$html = self::clean_robots_txt( $html );
		}

		return $html;
	}

	/**
	 * Swap injected mobile-hero JPEG for responsive WebP — same object-fit CSS, smaller bytes.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private static function upgrade_hero_img( $html ) {
		$img = '<img src="' . esc_url( self::HERO_WEBP ) . '" '
			. 'srcset="' . esc_attr( self::HERO_800 . ' 800w, ' . self::HERO_768 . ' 768w, ' . self::HERO_WEBP . ' 1200w' ) . '" '
			. 'sizes="100vw" '
			. 'alt="Cindemir Law Office legal team in Istanbul" '
			. 'width="800" height="533" decoding="async" fetchpriority="high" '
			. 'data-cindemir-lcp-safe="' . esc_attr( self::VERSION ) . '" />';

		$html = preg_replace(
			'#(<div class="cindemir-mobile-hero-photo">)\s*<img\b[^>]*>#i',
			'$1' . $img,
			$html,
			1
		);

		// Any remaining bare hero JPEG src/url → WebP.
		$html = str_replace( self::HERO_JPG . '"', self::HERO_WEBP . '"', $html );
		$html = str_replace( self::HERO_JPG . "'", self::HERO_WEBP . "'", $html );
		$html = str_replace( self::HERO_JPG . ')', self::HERO_WEBP . ')', $html );

		return $html;
	}

	/**
	 * @param string $output robots.txt body.
	 * @return string
	 */
	public static function clean_robots_txt( $output ) {
		if ( ! is_string( $output ) || $output === '' ) {
			return $output;
		}
		$output = preg_replace( '/<link\b[^>]*>\s*/i', '', $output );
		$output = preg_replace( '/<\/?(?:head|body|html)[^>]*>\s*/i', '', $output );
		return $output;
	}

	/** @return bool */
	private static function is_robots_request() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		return (bool) preg_match( '#/robots\.txt/?(\?|$)#i', $uri );
	}

	/**
	 * Catch WPML post-processing that appends hreflang HTML after robots_txt filter.
	 */
	public static function maybe_clean_robots_request() {
		if ( ! self::is_robots_request() ) {
			return;
		}
		ob_start(
			static function ( $out ) {
				return Cindemir_LCP_Safe::clean_robots_txt( $out );
			}
		);
	}
}

Cindemir_LCP_Safe::boot();

<?php
/**
 * Plugin Name: Cindemir SEO Fixes
 * Description: H1 fixes, orphan footer links, empty image alts, and one-hop press/link9 redirects.
 * Version: 1.2.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_SEO_FIXES_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_SEO_FIXES_LOADED', true );

final class Cindemir_SEO_Fixes {

	private static $flatten = array(
		'/link9' => 'https://cindemir.av.tr/en/we-are-in-news/',
		'/press' => 'https://cindemir.av.tr/en/we-are-in-news/',
	);

	private static $missing_h1 = array(
		3874   => 'Family Heritage',
		3884   => 'Who is Hafız Hüseyin Hüsnü Efendi?',
		51     => 'News & Events',
		43     => 'Our Videos',
		378    => 'Appointment',
		4665   => 'Embed List',
		2      => 'О нас',
		105    => 'Статьи',
		2427   => 'Наша команда',
		2446   => 'Контакты',
		103    => 'Поддержка',
		56     => 'Услуги',
		900030 => 'Assistant',
	);

	private static $alt_map = array(
		'white-1-copy'                  => 'Cindemir Law Office',
		'white-2-copy'                  => 'Cindemir Law Office',
		'white-5-copy'                  => 'Cindemir Law Office',
		'white3-copy'                   => 'Cindemir Law Office',
		'footlaw_banner'                => 'Cindemir Law Office legal services banner',
		'540664430'                     => 'Istanbul skyline representing Cindemir Law Office',
		'Gokhan_Cindemir_AttorneyAtLaw' => 'Gökhan Cindemir, Attorney at Law',
		'Hakan_Cindemir_AttorneyatLaw'  => 'Dr. Hakan Cindemir, Attorney at Law',
		'2e20a321-6694-44e0-ae3e'       => 'Legal scales and gavel artwork',
	);

	private static $h1_done = false;

	public static function boot() {
		add_action( 'template_redirect', array( __CLASS__, 'flatten_redirects' ), 0 );
		add_filter( 'the_content', array( __CLASS__, 'fix_headings' ), 12 );
		add_action( 'wp_footer', array( __CLASS__, 'orphan_links' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'noindex_utility' ), 1 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'fix_alt_attr' ), 10, 2 );
		add_filter( 'the_content', array( __CLASS__, 'fix_empty_alts' ), 20 );
	}

	public static function flatten_redirects() {
		if ( is_admin() ) {
			return;
		}
		$path = self::path();
		if ( isset( self::$flatten[ $path ] ) ) {
			wp_redirect( self::$flatten[ $path ], 301 );
			exit;
		}
	}

	public static function fix_headings( $content ) {
		if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$id = get_the_ID();
		if ( isset( self::$missing_h1[ $id ] ) && ! preg_match( '/<h1[\s>]/i', $content ) && ! self::$h1_done ) {
			$content       = '<h1 class="cindemir-seo-h1">' . esc_html( self::$missing_h1[ $id ] ) . '</h1>' . "\n" . $content;
			self::$h1_done = true;
			return $content;
		}

		if ( ! preg_match_all( '/<h1([\s>][^>]*)>.*?<\/h1>/is', $content, $m ) ) {
			return $content;
		}
		if ( count( $m[0] ) <= 1 ) {
			return $content;
		}

		$seen = 0;
		return preg_replace_callback(
			'/<h1([\s>][^>]*)>(.*?)<\/h1>/is',
			function ( $match ) use ( &$seen ) {
				$seen++;
				if ( 1 === $seen ) {
					return $match[0];
				}
				return '<h2' . $match[1] . '>' . $match[2] . '</h2>';
			},
			$content
		);
	}

	public static function orphan_links() {
		if ( is_admin() ) {
			return;
		}
		echo "\n<nav class=\"cindemir-orphan-links\" aria-label=\"Additional pages\" style=\"max-width:1200px;margin:0 auto 1rem;padding:0 20px;font-size:14px;\">";
		echo '<a href="' . esc_url( home_url( '/our-videos/' ) ) . '">Our Videos</a> · ';
		echo '<a href="' . esc_url( home_url( '/appointment/' ) ) . '">Book an Appointment</a>';
		echo "</nav>\n";
	}

	public static function noindex_utility() {
		if ( function_exists( 'is_page' ) && is_page( array( 'antimanual-assistant', 'embed-list' ) ) ) {
			echo "<meta name=\"robots\" content=\"noindex,follow\" />\n";
		}
	}

	public static function fix_alt_attr( $attr, $attachment ) {
		if ( ! empty( $attr['alt'] ) ) {
			return $attr;
		}
		$url = wp_get_attachment_url( $attachment->ID );
		$alt = self::alt_for( $url );
		if ( ! $alt && ! empty( $attachment->post_title ) ) {
			$alt = sanitize_text_field( $attachment->post_title );
		}
		if ( $alt ) {
			$attr['alt'] = $alt;
		}
		return $attr;
	}

	public static function fix_empty_alts( $content ) {
		if ( false === strpos( $content, 'alt=""' ) && false === stripos( $content, "alt=''" ) ) {
			return $content;
		}
		return preg_replace_callback(
			'/<img(\s[^>]*?)>/is',
			function ( $m ) {
				$tag = $m[0];
				if ( ! preg_match( '/\salt\s*=\s*([\'"])\s*\1/i', $tag ) ) {
					return $tag;
				}
				$src = '';
				if ( preg_match( '/\ssrc\s*=\s*[\'"]([^\'"]+)[\'"]/i', $tag, $sm ) ) {
					$src = $sm[1];
				}
				$alt = self::alt_for( $src );
				if ( ! $alt ) {
					$alt = 'Cindemir Law Office';
				}
				return preg_replace( '/\salt\s*=\s*([\'"])\s*\1/i', 'alt="' . esc_attr( $alt ) . '"', $tag, 1 );
			},
			$content
		);
	}

	private static function path() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$p   = wp_parse_url( $uri, PHP_URL_PATH );
		$p   = is_string( $p ) ? rawurldecode( $p ) : '/';
		$p   = untrailingslashit( $p );
		return '' === $p ? '/' : $p;
	}

	private static function alt_for( $url ) {
		if ( ! $url ) {
			return '';
		}
		foreach ( self::$alt_map as $needle => $alt ) {
			if ( false !== stripos( $url, $needle ) ) {
				return $alt;
			}
		}
		return '';
	}
}

Cindemir_SEO_Fixes::boot();

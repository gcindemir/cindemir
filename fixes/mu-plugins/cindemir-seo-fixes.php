<?php
/**
 * Plugin Name: Cindemir SEO Fixes
 * Description: Fixes wrong redirect chains, missing/multiple H1s, orphan page links, and empty image alts on cindemirlaw.com.
 * Version: 1.0.1
 * Author: Cindemir Law Office
 *
 * Install: copy this file to wp-content/mu-plugins/cindemir-seo-fixes.php
 * (create the mu-plugins folder if it does not exist)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Cindemir_SEO_Fixes {

	/** EN posts that Redirection currently sends to unrelated RU pages. */
	private const BROKEN_REDIRECT_PATHS = array(
		'/how-to-lift-entry-ban-to-turkey',
		'/exemptions-on-the-legislation-of-the-documents-in-turkey',
	);

	/** Paths that should 301 in one hop to the final destination. */
	private const FLATTEN_REDIRECTS = array(
		'/link9' => 'https://cindemir.av.tr/en/we-are-in-news/',
		'/press' => 'https://cindemir.av.tr/en/we-are-in-news/',
	);

	/** Page IDs that need an injected H1 (theme hides title). */
	private const MISSING_H1_BY_ID = array(
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

	/** Empty alt → descriptive alt by filename fragment. */
	private const ALT_BY_FILENAME = array(
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

	/** @var bool */
	private static $h1_injected = false;

	public static function init() {
		add_filter( 'redirection_url_target', array( __CLASS__, 'fix_redirection_target' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'flatten_simple_redirects' ), 0 );

		add_filter( 'the_content', array( __CLASS__, 'fix_content_headings' ), 12 );

		add_action( 'wp_footer', array( __CLASS__, 'print_orphan_footer_links' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'noindex_utility_pages' ), 1 );

		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'fix_attachment_alt' ), 10, 3 );
		add_filter( 'the_content', array( __CLASS__, 'fix_empty_alts_in_content' ), 20 );
	}

	/**
	 * Cancel bad Redirection rules; flatten known chains.
	 *
	 * @param string|false $target Target URL.
	 * @param string       $url    Requested URL.
	 * @return string|false
	 */
	public static function fix_redirection_target( $target, $url ) {
		$path = self::normalize_path( $url );

		if ( in_array( $path, self::BROKEN_REDIRECT_PATHS, true ) ) {
			return false;
		}

		if ( isset( self::FLATTEN_REDIRECTS[ $path ] ) ) {
			return self::FLATTEN_REDIRECTS[ $path ];
		}

		if ( '/fde1068e3' === $path ) {
			return home_url( '/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru' );
		}

		return $target;
	}

	/**
	 * Safety net single-hop 301s when Redirection filter is not enough.
	 */
	public static function flatten_simple_redirects() {
		if ( is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) {
			return;
		}

		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = self::normalize_path( $uri );

		if ( isset( self::FLATTEN_REDIRECTS[ $path ] ) ) {
			// External host (cindemir.av.tr) — wp_safe_redirect would block it.
			wp_redirect( self::FLATTEN_REDIRECTS[ $path ], 301 );
			exit;
		}
	}

	/**
	 * Inject missing H1; demote extra H1s to H2.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function fix_content_headings( $content ) {
		if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();

		if ( isset( self::MISSING_H1_BY_ID[ $post_id ] ) && ! preg_match( '/<h1\b/i', $content ) && ! self::$h1_injected ) {
			$h1                = esc_html( self::MISSING_H1_BY_ID[ $post_id ] );
			$content           = '<h1 class="cindemir-seo-h1">' . $h1 . '</h1>' . "\n" . $content;
			self::$h1_injected = true;
			return $content;
		}

		if ( ! preg_match_all( '/<h1\b[^>]*>.*?<\/h1>/is', $content, $all ) ) {
			return $content;
		}

		if ( count( $all[0] ) <= 1 ) {
			return $content;
		}

		$seen    = 0;
		$content = preg_replace_callback(
			'/<h1(\b[^>]*)>(.*?)<\/h1>/is',
			static function ( $m ) use ( &$seen ) {
				$seen++;
				if ( 1 === $seen ) {
					return $m[0];
				}
				return '<h2' . $m[1] . '>' . $m[2] . '</h2>';
			},
			$content
		);

		return $content;
	}

	/**
	 * Footer links so orphan pages have internal inlinks.
	 */
	public static function print_orphan_footer_links() {
		if ( is_admin() ) {
			return;
		}

		$links = array(
			'/our-videos/'  => 'Our Videos',
			'/appointment/' => 'Book an Appointment',
		);

		echo "\n<nav class=\"cindemir-orphan-links\" aria-label=\"Additional pages\" style=\"max-width:1200px;margin:0 auto 1rem;padding:0 20px;font-size:14px;\">";
		$parts = array();
		foreach ( $links as $path => $label ) {
			$parts[] = '<a href="' . esc_url( home_url( $path ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo implode( ' · ', $parts );
		echo "</nav>\n";
	}

	/**
	 * Utility / junk pages should not be indexed.
	 */
	public static function noindex_utility_pages() {
		if ( is_page( array( 'antimanual-assistant', 'embed-list' ) ) ) {
			echo "<meta name=\"robots\" content=\"noindex,follow\" />\n";
		}
	}

	/**
	 * @param array   $attr       Attributes.
	 * @param WP_Post $attachment Attachment.
	 * @param mixed   $size       Size.
	 * @return array
	 */
	public static function fix_attachment_alt( $attr, $attachment, $size ) {
		if ( ! empty( $attr['alt'] ) ) {
			return $attr;
		}
		$url = wp_get_attachment_url( $attachment->ID );
		$alt = self::alt_for_url( $url );
		if ( ! $alt && ! empty( $attachment->post_title ) ) {
			$alt = sanitize_text_field( $attachment->post_title );
		}
		if ( $alt ) {
			$attr['alt'] = $alt;
		}
		return $attr;
	}

	/**
	 * @param string $content Content.
	 * @return string
	 */
	public static function fix_empty_alts_in_content( $content ) {
		if ( false === strpos( $content, 'alt=""' ) && false === stripos( $content, "alt=''" ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/<img\b([^>]*?)>/is',
			static function ( $m ) {
				$tag = $m[0];
				if ( preg_match( '/\balt\s*=\s*([\'"])\s*\1/i', $tag ) ) {
					$src = '';
					if ( preg_match( '/\bsrc\s*=\s*[\'"]([^\'"]+)[\'"]/i', $tag, $sm ) ) {
						$src = $sm[1];
					}
					$alt = self::alt_for_url( $src );
					if ( ! $alt ) {
						$alt = 'Cindemir Law Office';
					}
					return preg_replace( '/\balt\s*=\s*([\'"])\s*\1/i', 'alt="' . esc_attr( $alt ) . '"', $tag, 1 );
				}
				return $tag;
			},
			$content
		);
	}

	/**
	 * Normalize to path without trailing slash (except root).
	 *
	 * @param string $url URL or request URI.
	 * @return string
	 */
	private static function normalize_path( $url ) {
		$parts = wp_parse_url( $url );
		$path  = isset( $parts['path'] ) ? $parts['path'] : '/';
		$path  = rawurldecode( $path );
		$path  = untrailingslashit( $path );
		return '' === $path ? '/' : $path;
	}

	/**
	 * @param string $url Image URL.
	 * @return string
	 */
	private static function alt_for_url( $url ) {
		if ( ! $url ) {
			return '';
		}
		foreach ( self::ALT_BY_FILENAME as $needle => $alt ) {
			if ( false !== stripos( $url, $needle ) ) {
				return $alt;
			}
		}
		return '';
	}
}

Cindemir_SEO_Fixes::init();

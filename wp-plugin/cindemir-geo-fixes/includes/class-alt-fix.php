<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fill empty alt on language flags and attachment images where safe.
 */
class Cindemir_GEO_Alt_Fix {

	public static function init() {
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'attachment_alt' ), 20, 3 );
		add_filter( 'pll_get_flag_html', array( __CLASS__, 'pll_flag_alt' ), 20, 3 );
		add_filter( 'the_content', array( __CLASS__, 'content_empty_alts' ), 25 );
		add_filter( 'widget_text', array( __CLASS__, 'content_empty_alts' ), 25 );
	}

	public static function attachment_alt( $attr, $attachment, $size ) {
		if ( empty( $attr['alt'] ) ) {
			$title = get_the_title( $attachment );
			if ( $title ) {
				$attr['alt'] = wp_strip_all_tags( $title );
			}
		}
		return $attr;
	}

	public static function pll_flag_alt( $html, $flag, $slug = '' ) {
		if ( ! is_string( $html ) || false === strpos( $html, '<img' ) ) {
			return $html;
		}
		if ( preg_match( '/\balt\s*=\s*["\'][^"\']+["\']/', $html ) ) {
			return $html;
		}
		$name = $slug;
		if ( function_exists( 'PLL' ) && isset( PLL()->model ) ) {
			$lang = PLL()->model->get_language( $slug );
			if ( $lang && ! empty( $lang->name ) ) {
				$name = $lang->name;
			}
		}
		$alt = esc_attr( $name ? $name : 'language' );
		if ( preg_match( '/\balt\s*=\s*["\']\s*["\']/', $html ) ) {
			return preg_replace( '/\balt\s*=\s*["\']\s*["\']/', 'alt="' . $alt . '"', $html, 1 );
		}
		return preg_replace( '/<img\b/i', '<img alt="' . $alt . '"', $html, 1 );
	}

	public static function content_empty_alts( $content ) {
		if ( ! is_string( $content ) || false === stripos( $content, '<img' ) ) {
			return $content;
		}
		return preg_replace_callback(
			'/<img\b[^>]*>/i',
			static function ( $m ) {
				$tag = $m[0];
				if ( preg_match( '/\balt\s*=\s*["\'][^"\']+["\']/', $tag ) ) {
					return $tag;
				}
				$src = '';
				if ( preg_match( '/\b(?:data-lazy-src|data-src|src)\s*=\s*["\']([^"\']+)["\']/', $tag, $sm ) ) {
					$src = $sm[1];
				}
				// Skip lazyload JS youtube template placeholders.
				if ( false !== strpos( $src, '/vi/ID/' ) ) {
					return $tag;
				}
				$alt = '';
				if ( preg_match( '/\/flags\/([a-z]{2})\.png/i', $src, $fm ) ) {
					$alt = strtoupper( $fm[1] );
				} elseif ( $src && false === strpos( $src, 'data:image' ) ) {
					$base = pathinfo( wp_parse_url( $src, PHP_URL_PATH ), PATHINFO_FILENAME );
					$alt  = trim( preg_replace( '/[-_]+/', ' ', (string) $base ) );
				} else {
					$alt = 'Cindemir Hukuk Bürosu';
				}
				$alt_attr = 'alt="' . esc_attr( $alt ) . '"';
				if ( preg_match( '/\balt\s*=\s*["\']\s*["\']/', $tag ) ) {
					return preg_replace( '/\balt\s*=\s*["\']\s*["\']/', $alt_attr, $tag, 1 );
				}
				return preg_replace( '/<img\b/i', '<img ' . $alt_attr, $tag, 1 );
			},
			$content
		);
	}
}

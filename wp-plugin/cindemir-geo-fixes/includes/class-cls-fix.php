<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cumulative Layout Shift fixes for Enfold / WP Rocket lazyload sites.
 * Targets: header logo, logo ::after brand text, font swap, entry-content images.
 */
class Cindemir_GEO_Cls_Fix {

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'critical_css' ), 1 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'logo_attrs' ), 99, 3 );
		add_filter( 'the_content', array( __CLASS__, 'content_img_dimensions' ), 30 );
		// Rocket LazyLoad / Enfold often rewrite logo markup late.
		add_action( 'template_redirect', array( __CLASS__, 'buffer_start' ), 0 );
	}

	public static function buffer_start() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'buffer_fix' ) );
	}

	/**
	 * Critical CSS: reserve header + logo + brand text space before paint.
	 */
	public static function critical_css() {
		?>
<style id="cindemir-cls-fix"><?php echo self::css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
		<?php
	}

	public static function css() {
		return <<<'CSS'
/* === Header shell: stop 64px → content jump === */
#top #header #header_main,
#top #header #header_main .container,
#top #header #header_main .inner-container {
  min-height: 64px !important;
  height: 64px !important;
  box-sizing: border-box !important;
}

/* Logo box: fixed slot matching rendered size (max-height 44px, square asset) */
#top #header .logo,
#header .logo,
span.logo.avia-standard-logo {
  display: flex !important;
  align-items: center !important;
  flex: 0 0 auto !important;
  min-height: 44px !important;
  height: 44px !important;
  max-width: min(300px, 42vw) !important;
  overflow: visible !important;
}

#top #header .logo a,
#header .logo a {
  display: inline-flex !important;
  align-items: center !important;
  gap: 10px !important;
  min-height: 44px !important;
  height: 44px !important;
  text-decoration: none !important;
}

/* Image: aspect-ratio 1/1 with display height 44px → width 44px reserved */
#top #header .logo img,
#top #header .logo picture,
#header .logo img,
span.logo img {
  display: block !important;
  width: 44px !important;
  height: 44px !important;
  max-width: 44px !important;
  max-height: 44px !important;
  aspect-ratio: 1 / 1 !important;
  object-fit: contain !important;
  flex: 0 0 44px !important;
}

/* Brand text ::after: reserve width so text does not push menu on paint */
#top #header .logo a::after,
#header .logo a::after {
  content: "Cindemir Law Office" !important;
  display: inline-block !important;
  box-sizing: border-box !important;
  min-width: min(180px, 28vw) !important;
  max-width: min(220px, 30vw) !important;
  width: min(200px, 30vw) !important;
  min-height: 1.15em !important;
  font-family: Georgia, "Times New Roman", Times, serif !important;
  font-size: 18px !important;
  font-weight: 700 !important;
  line-height: 1.15 !important;
  color: #244f4f !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

@media (max-width: 767px) {
  #top #header .logo a::after,
  #header .logo a::after {
    min-width: 0 !important;
    width: 0 !important;
    max-width: 0 !important;
    overflow: hidden !important;
    content: "" !important;
  }
}

/* Soften web-font reflow (Enfold / Google webfonts) */
@font-face { font-display: optional; }
body, .entry-content, .av-special-heading, h1, h2, h3, h4 {
  font-synthesis: none;
}

/* Entry content: avoid huge intrinsic guess; size common WP thumbs */
.entry-content img:not([width]):not([height]) {
  max-width: 100%;
  height: auto;
}
.entry-content img[width][height] {
  aspect-ratio: attr(width) / attr(height);
  height: auto;
  max-width: 100%;
}
.entry-content figure {
  margin-top: 0;
  margin-bottom: 1em;
}
/* Override WP 6+ auto-sizes absurd intrinsic box when present */
img:is([sizes="auto" i], [sizes^="auto," i]) {
  contain-intrinsic-size: 800px 450px !important;
}
CSS;
	}

	/**
	 * Prefer non-lazy logo when WordPress outputs custom logo.
	 */
	public static function logo_attrs( $attr, $attachment, $size ) {
		$src = isset( $attr['src'] ) ? $attr['src'] : '';
		if ( $src && false !== strpos( $src, 'logoicon' ) ) {
			$attr['loading']       = 'eager';
			$attr['fetchpriority'] = 'high';
			$attr['decoding']      = 'async';
			$attr['width']         = '300';
			$attr['height']        = '300';
			unset( $attr['data-lazy-src'] );
		}
		return $attr;
	}

	/**
	 * HTML buffer: de-lazy header logo + fix width/height mismatch (300x100 vs 300x300).
	 */
	public static function buffer_fix( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		// 1) Header logo: eager + correct square dimensions + unwrap rocket placeholder.
		$html = preg_replace_callback(
			'/(<span[^>]*class=["\'][^"\']*\blogo\b[^"\']*["\'][^>]*>)(.*?)(<\/span>)/is',
			static function ( $m ) {
				$inner = $m[2];
				// Prefer real src over data-lazy-src SVG placeholder.
				$inner = preg_replace_callback(
					'/<img\b([^>]*)>/i',
					static function ( $im ) {
						$attrs = $im[1];
						$lazy  = '';
						if ( preg_match( '/\bdata-lazy-src=["\']([^"\']+)["\']/', $attrs, $lm ) ) {
							$lazy = $lm[1];
						}
						if ( $lazy && false !== strpos( $lazy, 'logoicon' ) ) {
							$safe = htmlspecialchars( $lazy, ENT_QUOTES, 'UTF-8' );
							$attrs = preg_replace( '/\bsrc=["\'][^"\']+["\']/', 'src="' . $safe . '"', $attrs, 1 );
							$attrs = preg_replace( '/\sdata-lazy-src=["\'][^"\']+["\']/', '', $attrs );
							$attrs = preg_replace( '/\sloading=["\']lazy["\']/', ' loading="eager" fetchpriority="high"', $attrs );
							if ( ! preg_match( '/\bloading=/', $attrs ) ) {
								$attrs .= ' loading="eager" fetchpriority="high"';
							}
							// Fix aspect: asset is 300x300.
							$attrs = preg_replace( '/\bwidth=["\'][^"\']+["\']/', 'width="300"', $attrs );
							$attrs = preg_replace( '/\bheight=["\'][^"\']+["\']/', 'height="300"', $attrs );
							if ( ! preg_match( '/\bwidth=/', $attrs ) ) {
								$attrs .= ' width="300" height="300"';
							}
						}
						return '<img' . $attrs . '>';
					},
					$inner
				);
				// Drop empty webp <source data-lazy-srcset> that delays paint.
				$inner = preg_replace( '/<source[^>]*data-lazy-srcset[^>]*>/i', '', $inner );
				return $m[1] . $inner . $m[3];
			},
			$html,
			1
		);

		return $html;
	}

	/**
	 * Add width/height to content images missing them when attachment meta known.
	 */
	public static function content_img_dimensions( $content ) {
		if ( ! is_string( $content ) || false === stripos( $content, '<img' ) ) {
			return $content;
		}
		return preg_replace_callback(
			'/<img\b[^>]*>/i',
			static function ( $m ) {
				$tag = $m[0];
				if ( preg_match( '/\bwidth\s*=/', $tag ) && preg_match( '/\bheight\s*=/', $tag ) ) {
					return $tag;
				}
				if ( ! preg_match( '/wp-image-(\d+)/', $tag, $idm ) ) {
					return $tag;
				}
				$meta = wp_get_attachment_metadata( (int) $idm[1] );
				if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
					return $tag;
				}
				$w = (int) $meta['width'];
				$h = (int) $meta['height'];
				if ( ! preg_match( '/\bwidth\s*=/', $tag ) ) {
					$tag = preg_replace( '/<img\b/i', '<img width="' . $w . '"', $tag, 1 );
				}
				if ( ! preg_match( '/\bheight\s*=/', $tag ) ) {
					$tag = preg_replace( '/<img\b/i', '<img height="' . $h . '"', $tag, 1 );
				}
				return $tag;
			},
			$content
		);
	}
}

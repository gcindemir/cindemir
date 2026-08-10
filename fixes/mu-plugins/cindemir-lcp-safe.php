<?php
/**
 * Plugin Name: Cindemir LCP Safe
 * Description: Design-safe site-wide LCP speedups — WebP, first-viewport opacity unlock, smart image preload. Does not change layout/colors.
 * Version: 1.2.1
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
	const VERSION   = '1.2.1';
	const HERO_JPG  = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg';
	const HERO_WEBP = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg.webp';
	const HERO_768  = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430-768x512.jpg.webp';
	const HERO_800  = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430-800x430.jpg.webp';
	const TEAM_WEBP = 'https://cindemirlaw.com/wp-content/uploads/2026/06/3S9A8705.webp';

	public static function boot() {
		add_action( 'wp_head', array( __CLASS__, 'print_css' ), 100 );
		add_action( 'wp_head', array( __CLASS__, 'print_preload' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), -1001 );
		add_action( 'wp_loaded', array( __CLASS__, 'register_late_buffers' ), 9999 );
		add_filter( 'robots_txt', array( __CLASS__, 'clean_robots_txt' ), 99999 );
		add_action( 'init', array( __CLASS__, 'maybe_clean_robots_request' ), 0 );
	}

	public static function register_late_buffers() {
		add_filter( 'rocket_buffer', array( __CLASS__, 'buffer' ), PHP_INT_MAX );
	}

	/**
	 * Site-wide: first viewport must never sit at opacity:0 waiting for Enfold/JS.
	 * Lower sections keep scroll motion — only the first section / known heroes unlock.
	 */
	public static function print_css() {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="cindemir-lcp-safe" data-v="' . esc_attr( self::VERSION ) . '">'
			/* Text fonts: prefer swap so LCP text paints (icon fonts stay optional via Enfold). */
			. '@font-face{font-display:swap!important}'
			. '.avia-font-entypo-fontello,.av-icon-char{'
			. 'font-display:optional}'
			/* —— First section unlock (all pages) —— */
			. '#top #main .avia-section:first-of-type,'
			. '#top #main .avia-section:first-of-type .av-special-heading,'
			. '#top #main .avia-section:first-of-type .av-special-heading-tag,'
			. '#top #main .avia-section:first-of-type .avia_textblock,'
			. '#top #main .avia-section:first-of-type .avia_textblock p,'
			. '#top #main .avia-section:first-of-type .avia_image,'
			. '#top #main .avia-section:first-of-type img,'
			. '#top #main .avia-section:first-of-type .flex_column,'
			. '#top #main .avia-section:first-of-type .avia_start_animation,'
			. '#top #main .avia-section:first-of-type .av-animated-when,'
			. '#top #main .avia-section:first-of-type .av-animated-when-visible,'
			. '#top #main h1,'
			. '#top #main .entry-content-wrapper > .avia-section:first-of-type *{'
			. 'opacity:1!important;visibility:visible!important}'
			/* Services / RU press custom heroes */
			. '#top #main .cindemir-services__hero,'
			. '#top #main .cindemir-services__hero-media,'
			. '#top #main .cindemir-services__hero-inner,'
			. '#top #main .cindemir-services__hero-inner *,'
			. '#top #main .cin-ru-press,'
			. '#top #main .cin-ru-press__hero,'
			. '#top #main .cin-ru-press__h1,'
			. '#top #main .cin-ru-press__lead,'
			. '#top #main .cin-ru-press__intro,'
			. '#top #main .cin-ru-press__intro p{'
			. 'opacity:1!important;visibility:visible!important;'
			. 'animation:none!important;transform:none!important}'
			/* Homepage motion kept, without opacity hold */
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
			/* Services CSS-background hero: real <img> twin for LCP; keep cover crop */
			. '.cindemir-services__hero-media{position:absolute;inset:0;overflow:hidden}'
			. '.cindemir-services__hero-lcp{'
			. 'position:absolute;inset:0;width:100%!important;height:100%!important;'
			. 'object-fit:cover!important;object-position:center 35%!important;'
			. 'display:block!important;margin:0!important;padding:0!important;'
			. 'border:0!important;opacity:1!important;z-index:0;pointer-events:none;'
			. 'transform:none!important;animation:none!important}'
			. '.cindemir-services__hero-media{'
			. 'background-image:linear-gradient(120deg,rgba(8,35,34,.55),rgba(12,60,58,.25))!important;'
			. 'background-size:cover!important;background-position:center 35%!important;'
			. 'transform:none!important;animation:none!important}'
			. '</style>' . "\n";
	}

	/** Page-aware image preload for the likely LCP candidate. */
	public static function print_preload() {
		if ( is_admin() ) {
			return;
		}

		if ( is_front_page() || is_home() ) {
			echo '<link rel="preload" as="image" href="' . esc_url( self::HERO_768 ) . '" type="image/webp" '
				. 'imagesrcset="' . esc_attr( self::HERO_800 . ' 800w, ' . self::HERO_768 . ' 768w, ' . self::HERO_WEBP . ' 1200w' ) . '" '
				. 'imagesizes="100vw" fetchpriority="high" data-cindemir-lcp-safe="' . esc_attr( self::VERSION ) . '">' . "\n";
			return;
		}

		// About / Team use the group portrait as LCP.
		if ( function_exists( 'is_page' ) && is_page( array( 16, 2, 2629, 19, 2427, 2641 ) ) ) {
			echo '<link rel="preload" as="image" href="' . esc_url( self::TEAM_WEBP ) . '" type="image/webp" fetchpriority="high" data-cindemir-lcp-safe="' . esc_attr( self::VERSION ) . '">' . "\n";
			return;
		}

		// Services pages use the Istanbul hero as CSS background.
		if ( function_exists( 'is_page' ) && is_page( array( 18, 56, 2637 ) ) ) {
			echo '<link rel="preload" as="image" href="' . esc_url( self::HERO_WEBP ) . '" type="image/webp" fetchpriority="high" data-cindemir-lcp-safe="' . esc_attr( self::VERSION ) . '">' . "\n";
		}
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

		$html = self::upgrade_hero_img( $html );
		$html = self::promote_first_content_image( $html );
		$html = self::rewrite_css_hero_jpg( $html );
		$html = self::inject_services_hero_img( $html );
		$html = self::inject_head_preloads( $html );

		$html = preg_replace(
			'#<link\b[^>]*rel=(["\'])preload\1[^>]*540664430\.jpg(?!\.webp)[^>]*>\s*#i',
			'',
			$html
		);
		// Logo 300x300 preload steals bandwidth from real LCP images.
		$html = preg_replace(
			'#<link\b[^>]*rel=(["\'])preload\1[^>]*cropped-logoicon[^>]*>\s*#i',
			'',
			$html
		);

		// Prefer existing WebP siblings for upload rasters.
		$replaced = preg_replace_callback(
			'#\b(?:src|data-lazy-src|data-src)=(["\'])(https?://(?:www\.)?cindemirlaw\.com/wp-content/uploads/[^"\']+\.(?:jpe?g|png))\1#i',
			static function ( $m ) {
				$attr  = substr( $m[0], 0, strpos( $m[0], '=' ) );
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
						return $attr . '=' . $quote . esc_url( $webp ) . $quote;
					}
				}
				return $m[0];
			},
			$html
		);
		if ( is_string( $replaced ) ) {
			$html = $replaced;
		}

		// Override SEO's font-display:optional on body text faces → swap (keeps LCP text visible).
		$html = preg_replace(
			'/#top\s*\{[^}]*font-display:optional[^}]*\}|@font-face\{font-display:optional!important\}/i',
			'@font-face{font-display:swap!important}',
			$html,
			1
		);
		$html = str_replace(
			'@font-face{font-display:optional!important}',
			'@font-face{font-display:swap!important}',
			$html
		);

		if ( self::is_robots_request() || ( false !== strpos( $html, 'User-agent:' ) && false !== strpos( $html, '<link rel="alternate"' ) ) ) {
			$html = self::clean_robots_txt( $html );
		}

		return $html;
	}

	/**
	 * Ensure the first meaningful content image is eager + high priority (about/team/etc).
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private static function promote_first_content_image( $html ) {
		$done = false;
		$out  = preg_replace_callback(
			'#<img\b[^>]*>#i',
			static function ( $m ) use ( &$done ) {
				if ( $done ) {
					return $m[0];
				}
				$tag = $m[0];
				if ( preg_match( '/\b(?:cindemir-lang-flag|cropped-logoicon|res\/flags|uploads\/flags|cindemir-badge)\b/i', $tag ) ) {
					return $tag;
				}
				if ( ! preg_match( '#(?:src|data-lazy-src|data-src)=(["\'])[^"\']*/wp-content/uploads/[^"\']+\1#i', $tag ) ) {
					return $tag;
				}
				// Skip tiny icons.
				if ( preg_match( '/\b(?:width|height)=(["\']?)(?:[1-9]|[1-4]\d|5[0-9])\1/i', $tag )
					&& ! preg_match( '/\b(?:width|height)=(["\']?)(?:[6-9]\d|\d{3,})\1/i', $tag ) ) {
					return $tag;
				}

				$done = true;
				// Hydrate lazy placeholders.
				if ( preg_match( '/\bdata-lazy-src=(["\'])([^"\']+)\1/i', $tag, $lm ) ) {
					$tag = preg_replace( '/\bsrc=(["\'])[^"\']*\1/i', 'src=' . $lm[1] . esc_url( $lm[2] ) . $lm[1], $tag, 1 );
					$tag = preg_replace( '/\sdata-lazy-src=(["\'])[^"\']*\1/i', '', $tag );
				}
				if ( preg_match( '/\bdata-lazy-srcset=(["\'])([^"\']+)\1/i', $tag, $sm ) ) {
					if ( preg_match( '/\bsrcset=/i', $tag ) ) {
						$tag = preg_replace( '/\bsrcset=(["\'])[^"\']*\1/i', 'srcset=' . $sm[1] . $sm[2] . $sm[1], $tag, 1 );
					} else {
						$tag = preg_replace( '/<img\b/i', '<img srcset="' . esc_attr( $sm[2] ) . '"', $tag, 1 );
					}
					$tag = preg_replace( '/\sdata-lazy-srcset=(["\'])[^"\']*\1/i', '', $tag );
				}
				$tag = preg_replace( '/\sloading=(["\'])[^"\']*\1/i', '', $tag );
				$tag = preg_replace( '/\sfetchpriority=(["\'])[^"\']*\1/i', '', $tag );
				$tag = preg_replace( '/<img\b/i', '<img fetchpriority="high" loading="eager" decoding="async"', $tag, 1 );
				return $tag;
			},
			$html,
			40
		);
		return is_string( $out ) ? $out : $html;
	}

	/**
	 * @param string $html HTML.
	 * @return string
	 */
	private static function rewrite_css_hero_jpg( $html ) {
		$html = str_replace( self::HERO_JPG . ')', self::HERO_WEBP . ')', $html );
		$html = str_replace( "url('" . self::HERO_JPG . "')", "url('" . self::HERO_WEBP . "')", $html );
		$html = str_replace( 'url("' . self::HERO_JPG . '")', 'url("' . self::HERO_WEBP . '")', $html );
		return $html;
	}

	/**
	 * Turn services CSS-background hero into a real image for faster LCP discovery.
	 * Visual stays cover-cropped; slow Ken-Burns zoom disabled for paint speed.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private static function inject_services_hero_img( $html ) {
		if ( false === strpos( $html, 'cindemir-services__hero-media' ) ) {
			return $html;
		}
		if ( false !== strpos( $html, 'cindemir-services__hero-lcp' ) ) {
			return $html;
		}
		$img = '<img class="cindemir-services__hero-lcp" src="' . esc_url( self::HERO_WEBP ) . '" '
			. 'srcset="' . esc_attr( self::HERO_800 . ' 800w, ' . self::HERO_768 . ' 768w, ' . self::HERO_WEBP . ' 1200w' ) . '" '
			. 'sizes="100vw" alt="" width="1200" height="800" decoding="async" fetchpriority="high" />';
		$out = preg_replace(
			'#(<div class="cindemir-services__hero-media"\b[^>]*>)(\s*</div>)#i',
			'$1' . $img . '$2',
			$html,
			1
		);
		if ( is_string( $out ) && false !== strpos( $out, 'cindemir-services__hero-lcp' ) ) {
			return $out;
		}
		// Fallback: insert before closing tag of first hero-media div.
		$pos = strpos( $html, 'cindemir-services__hero-media' );
		if ( false === $pos ) {
			return $html;
		}
		$gt = strpos( $html, '>', $pos );
		if ( false === $gt ) {
			return $html;
		}
		return substr( $html, 0, $gt + 1 ) . $img . substr( $html, $gt + 1 );
	}

	/**
	 * Inject page-aware preloads from final HTML (more reliable than is_page in wp_head).
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private static function inject_head_preloads( $html ) {
		if ( false === stripos( $html, '</head>' ) ) {
			return $html;
		}
		$links = '';
		if ( false !== strpos( $html, 'cindemir-mobile-hero-photo' ) || false !== strpos( $html, 'cindemir-services__hero' ) || (bool) preg_match( '/<body[^>]*\bhome\b/i', $html ) ) {
			$links .= '<link rel="preload" as="image" href="' . esc_url( self::HERO_768 ) . '" type="image/webp" '
				. 'imagesrcset="' . esc_attr( self::HERO_800 . ' 800w, ' . self::HERO_768 . ' 768w, ' . self::HERO_WEBP . ' 1200w' ) . '" '
				. 'imagesizes="100vw" fetchpriority="high" data-cindemir-lcp-safe="' . esc_attr( self::VERSION ) . '">' . "\n";
		}
		if ( false !== strpos( $html, '3S9A8705' ) || (bool) preg_match( '/page-id-(?:16|2|19|2427|2629|2641)\b/', $html ) ) {
			$links .= '<link rel="preload" as="image" href="' . esc_url( self::TEAM_WEBP ) . '" type="image/webp" fetchpriority="high" data-cindemir-lcp-safe="' . esc_attr( self::VERSION ) . '">' . "\n";
		}
		if ( $links === '' ) {
			return $html;
		}
		// Avoid duplicate preloads from this version.
		if ( false !== strpos( $html, 'rel="preload" as="image" href="' . self::HERO_768 . '"' )
			|| false !== strpos( $html, 'data-cindemir-lcp-safe="' . self::VERSION . '"' ) && false !== strpos( $html, 'rel="preload"' ) && false !== strpos( $html, self::HERO_768 ) ) {
			// Still allow team preload if missing.
			if ( false !== strpos( $links, self::TEAM_WEBP ) && false === strpos( $html, self::TEAM_WEBP . '" type="image/webp"' ) ) {
				$links = '<link rel="preload" as="image" href="' . esc_url( self::TEAM_WEBP ) . '" type="image/webp" fetchpriority="high" data-cindemir-lcp-safe="' . esc_attr( self::VERSION ) . '">' . "\n";
			} else {
				return $html;
			}
		}
		$out = preg_replace( '/<\/head>/i', $links . '</head>', $html, 1 );
		return is_string( $out ) ? $out : $html;
	}

	/**
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

		$html = str_replace( self::HERO_JPG . '"', self::HERO_WEBP . '"', $html );
		$html = str_replace( self::HERO_JPG . "'", self::HERO_WEBP . "'", $html );
		$html = self::rewrite_css_hero_jpg( $html );

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

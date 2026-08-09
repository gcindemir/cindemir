<?php
/**
 * Plugin Name: Cindemir Menu Fix
 * Description: Fixes main-nav language switcher hrefs on all pages + keeps RU/ZH menus pointing at real translated pages.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_MENU_FIX_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_MENU_FIX_LOADED', true );

final class Cindemir_Menu_Fix {

	const PRESS_URL = 'https://cindemir.av.tr/en/we-are-in-news/';

	/** @var array<string,array{label:string,flag:string}> */
	private static $langs = array(
		'en'      => array(
			'label' => 'EN',
			'flag'  => 'https://cindemirlaw.com/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png',
		),
		'zh-hans' => array(
			'label' => '中文',
			'flag'  => 'https://cindemirlaw.com/wp-content/uploads/flags/china-flag-xs.png',
		),
		'ru'      => array(
			'label' => 'RU',
			'flag'  => 'https://cindemirlaw.com/wp-content/plugins/sitepress-multilingual-cms/res/flags/ru.png',
		),
	);

	public static function boot() {
		add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'fix_menu_objects' ), 200, 2 );
		add_filter( 'wp_nav_menu', array( __CLASS__, 'fix_menu_html' ), 1000, 2 );
		// Start BEFORE cindemir-seo-fixes (-999) so this buffer is outer and
		// rewrites language-switcher hrefs AFTER seo stamp/lang passes.
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), -1000 );
		add_action( 'init', array( __CLASS__, 'maybe_repair_menus' ), 30 );
	}

	/** One-shot repair flag for WP menus (RU/ZH targets + ZH labels). */
	public static function maybe_repair_menus() {
		if ( get_option( 'cindemir_menu_fix_v1' ) === '1' ) {
			return;
		}
		if ( ! function_exists( 'wp_update_nav_menu_item' ) ) {
			return;
		}
		self::repair_ru_menu();
		self::repair_zh_menu();
		update_option( 'cindemir_menu_fix_v1', '1', false );
	}

	/** Force repair from WP-CLI: wp eval 'Cindemir_Menu_Fix::force_repair();' */
	public static function force_repair() {
		delete_option( 'cindemir_menu_fix_v1' );
		self::repair_ru_menu();
		self::repair_zh_menu();
		update_option( 'cindemir_menu_fix_v1', '1', false );
		return true;
	}

	private static function repair_ru_menu() {
		$menu_id = 1060; // Main Menu R
		$map     = array(
			2597 => array( 'title' => 'Главная', 'object_id' => 2570 ),
			2674 => array( 'title' => 'О нас', 'object_id' => 2 ),
			2675 => array( 'title' => 'Статьи', 'object_id' => 105 ),
			2676 => array( 'title' => 'Услуги', 'object_id' => 56 ),
			2677 => array( 'title' => 'Команда', 'object_id' => 2427 ),
			2679 => array( 'title' => 'Контакты', 'object_id' => 2446 ),
		);
		foreach ( $map as $item_id => $info ) {
			$page = get_post( $info['object_id'] );
			if ( ! $page || 'publish' !== $page->post_status ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				$item_id,
				array(
					'menu-item-title'     => $info['title'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $info['object_id'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		// Press stays custom external.
		if ( get_post( 2678 ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				2678,
				array(
					'menu-item-title'  => 'О нас в прессе',
					'menu-item-type'   => 'custom',
					'menu-item-url'    => self::PRESS_URL,
					'menu-item-status' => 'publish',
				)
			);
		}
	}

	private static function repair_zh_menu() {
		$menu_id = 1061; // Main Menu Ch
		$map     = array(
			2610 => array( 'title' => '首页', 'object_id' => 2568 ),
			2669 => array( 'title' => '关于我们', 'object_id' => 2629 ),
			2670 => array( 'title' => '文章', 'object_id' => 2633 ),
			2671 => array( 'title' => '服务', 'object_id' => 2637 ),
			2672 => array( 'title' => '团队', 'object_id' => 2641 ),
		);
		foreach ( $map as $item_id => $info ) {
			$page = get_post( $info['object_id'] );
			if ( ! $page || 'publish' !== $page->post_status ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				$item_id,
				array(
					'menu-item-title'     => $info['title'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $info['object_id'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		// Press custom → media.
		if ( get_post( 2673 ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				2673,
				array(
					'menu-item-title'  => '媒体报道',
					'menu-item-type'   => 'custom',
					'menu-item-url'    => self::PRESS_URL,
					'menu-item-status' => 'publish',
				)
			);
		}
		// Ensure Contacts exists.
		$has_contacts = false;
		$items        = wp_get_nav_menu_items( $menu_id );
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( (int) $item->object_id === 900095 || false !== strpos( (string) $item->title, '联系' ) ) {
					$has_contacts = true;
					break;
				}
			}
		}
		if ( ! $has_contacts && get_post( 900095 ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => '联系我们',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => 900095,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => 7,
				)
			);
		}
	}

	/**
	 * When WPML falls back to missing/deleted translation targets, retarget
	 * menu objects to the known good page IDs for the active language.
	 *
	 * @param array    $items Menu items.
	 * @param stdClass $args  Menu args.
	 * @return array
	 */
	public static function fix_menu_objects( $items, $args ) {
		if ( ! is_array( $items ) || ! $items ) {
			return $items;
		}
		$lang = self::front_lang();
		if ( 'ru' === $lang ) {
			$by_en = array(
				15 => 2570,
				16 => 2,
				17 => 105,
				18 => 56,
				19 => 2427,
				20 => 2446,
			);
			$labels = array(
				2570  => 'Главная',
				2     => 'О нас',
				105   => 'Статьи',
				56    => 'Услуги',
				2427  => 'Команда',
				2446  => 'Контакты',
			);
		} elseif ( in_array( $lang, array( 'zh-hans', 'zh' ), true ) ) {
			$by_en = array(
				15 => 2568,
				16 => 2629,
				17 => 2633,
				18 => 2637,
				19 => 2641,
				20 => 900095,
			);
			$labels = array(
				2568   => '首页',
				2629   => '关于我们',
				2633   => '文章',
				2637   => '服务',
				2641   => '团队',
				900095 => '联系我们',
			);
		} else {
			return $items;
		}

		foreach ( $items as $item ) {
			if ( empty( $item->type ) || 'post_type' !== $item->type || empty( $item->object_id ) ) {
				// Press custom link.
				if ( ! empty( $item->url ) && ( false !== strpos( $item->url, '/press' ) || false !== strpos( (string) $item->title, 'Press' ) || false !== strpos( (string) $item->title, 'пресс' ) || false !== strpos( (string) $item->title, '媒体' ) || false !== strpos( (string) $item->title, '支持' ) ) ) {
					$item->url   = self::PRESS_URL;
					$item->title = ( 'ru' === $lang ) ? 'О нас в прессе' : ( in_array( $lang, array( 'zh-hans', 'zh' ), true ) ? '媒体报道' : $item->title );
				}
				continue;
			}
			$oid = (int) $item->object_id;
			// Already a good target.
			if ( isset( $labels[ $oid ] ) ) {
				$item->title = $labels[ $oid ];
				$item->url   = get_permalink( $oid );
				continue;
			}
			// Map from EN source via WPML, or known EN IDs.
			$en_id = 0;
			$trid  = apply_filters( 'wpml_element_trid', null, $oid, 'post_page' );
			if ( $trid ) {
				$translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_page' );
				if ( is_array( $translations ) && isset( $translations['en']->element_id ) ) {
					$en_id = (int) $translations['en']->element_id;
				}
			}
			if ( ! $en_id && isset( $by_en[ $oid ] ) ) {
				$en_id = $oid;
			}
			if ( $en_id && isset( $by_en[ $en_id ] ) ) {
				$new_id = $by_en[ $en_id ];
				$page   = get_post( $new_id );
				if ( $page && 'publish' === $page->post_status ) {
					$item->object_id = $new_id;
					$item->url       = get_permalink( $new_id );
					if ( isset( $labels[ $new_id ] ) ) {
						$item->title = $labels[ $new_id ];
					}
				}
			}
		}
		return $items;
	}

	public static function fix_menu_html( $html, $args = null ) {
		return self::rewrite_lang_switcher_hrefs( $html );
	}

	public static function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'rewrite_html' ) );
	}

	public static function rewrite_html( $html ) {
		$html = self::rewrite_lang_switcher_hrefs( $html );
		$html = self::rewrite_press_hrefs( $html );
		$html = self::fix_zh_menu_labels( $html );
		return $html;
	}

	/** Force Press menu/content links to the external news URL (no /press hop). */
	private static function rewrite_press_hrefs( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$html = preg_replace(
			'#\bhref=(["\'])https?://(?:www\.)?cindemirlaw\.com/press/??(?:\?[^"\']*)?\1#i',
			'href=$1' . esc_attr( self::PRESS_URL ) . '$1',
			$html
		);
		return is_string( $html ) ? $html : '';
	}

	/** Correct wrong Chinese menu labels left in cached HTML. */
	private static function fix_zh_menu_labels( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( ! in_array( self::front_lang(), array( 'zh-hans', 'zh' ), true ) ) {
			return $html;
		}
		$replacements = array(
			'>研讨<'     => '>文章<',
			'>招聘信息<' => '>团队<',
			'>支持<'     => '>媒体报道<',
			'>招聘<'     => '>团队<',
		);
		foreach ( $replacements as $from => $to ) {
			$html = str_replace( $from, $to, $html );
		}
		return $html;
	}

	/**
	 * Rewrite every language switcher <a href> (WPML + injected cindemir-lang-item)
	 * to the correct target for that language on the current path.
	 */
	private static function rewrite_lang_switcher_hrefs( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( false === strpos( $html, 'cindemir-lang-item' )
			&& false === strpos( $html, 'language_' )
			&& false === strpos( $html, 'wpml-ls-' )
			&& false === strpos( $html, 'avia_wpml_language_switch' ) ) {
			return $html;
		}

		$path = self::request_path();
		$path = ( ! $path || '/' === $path ) ? '/' : trailingslashit( $path );
		$base = 'https://cindemirlaw.com' . ( '/' === $path ? '/' : $path );
		$urls = array(
			'en'      => self::lang_url( 'en', $base ),
			'ru'      => self::lang_url( 'ru', $base ),
			'zh-hans' => self::lang_url( 'zh-hans', $base ),
			'zh'      => self::lang_url( 'zh-hans', $base ),
		);

		$out = preg_replace_callback(
			'#<li\b([^>]*\b(?:cindemir-lang-item|language_[a-z0-9\-]+|wpml-ls-item-[a-z0-9\-]+)[^>]*)>(.*?)</li>#is',
			static function ( $m ) use ( $urls ) {
				$attrs = $m[1];
				$inner = $m[2];
				$code  = '';
				if ( preg_match( '/\blanguage_([a-z0-9\-]+)\b/i', $attrs, $cm ) ) {
					$code = strtolower( $cm[1] );
				} elseif ( preg_match( '/\bwpml-ls-item-([a-z0-9\-]+)\b/i', $attrs, $cm ) ) {
					$code = strtolower( $cm[1] );
				}
				if ( '' === $code || ! isset( $urls[ $code ] ) ) {
					return $m[0];
				}
				$url   = $urls[ $code ];
				$inner = preg_replace(
					'#(\bhref=)(["\'])([^"\']*)\2#i',
					'$1$2' . esc_attr( $url ) . '$2',
					$inner,
					1
				);
				return '<li' . $attrs . '>' . $inner . '</li>';
			},
			$html
		);
		return is_string( $out ) ? $out : $html;
	}

	private static function lang_url( $code, $base ) {
		$code = strtolower( (string) $code );
		$base = preg_replace( '/([?&])lang=[^&]*/', '$1', $base );
		$base = preg_replace( '/([?&])cindemir_lang=[^&]*/', '$1', $base );
		$base = rtrim( $base, '?&' );
		if ( in_array( $code, array( 'en', 'en-us', 'en_us' ), true ) ) {
			$current = self::front_lang();
			if ( $current && ! in_array( $current, array( 'en', 'en-us', 'en_us' ), true ) ) {
				$sep = ( false === strpos( $base, '?' ) ) ? '?' : '&';
				return $base . $sep . 'cindemir_lang=en';
			}
			return $base;
		}
		$map = array(
			'ru'      => 'ru',
			'zh-hans' => 'zh-hans',
			'zh'      => 'zh-hans',
		);
		if ( ! isset( $map[ $code ] ) ) {
			return $base;
		}
		$sep = ( false === strpos( $base, '?' ) ) ? '?' : '&';
		return $base . $sep . 'lang=' . rawurlencode( $map[ $code ] );
	}

	private static function front_lang() {
		if ( ! empty( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$get = sanitize_key( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $get ) {
				return $get;
			}
		}
		if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
			return strtolower( (string) ICL_LANGUAGE_CODE );
		}
		$wpml = apply_filters( 'wpml_current_language', null );
		if ( is_string( $wpml ) && $wpml ) {
			return strtolower( $wpml );
		}
		return 'en';
	}

	private static function request_path() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		return $path ? $path : '/';
	}
}

Cindemir_Menu_Fix::boot();

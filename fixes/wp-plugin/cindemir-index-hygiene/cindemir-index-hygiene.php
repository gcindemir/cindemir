<?php
/**
 * Plugin Name: Cindemir Index Hygiene
 * Description: Ahrefs cleanup: noindex utilities, hreflang fixes, language-switcher repair, canonical self-consistency, footer/baro link rewrites.
 * Version: 2.0.1
 * Author: Cindemir Law Office
 */

if (!defined('ABSPATH')) {
	exit;
}

final class Cindemir_Index_Hygiene {
	const VERSION = '2.0.1';

	const NOINDEX_SLUGS = array(
		'appointment',
		'online-appointment-booking',
		'antimanual-assistant',
		'embed-list',
	);

	const RU_PATH_PREFIXES = array(
		'/onas',
		'/stati',
		'/komanda',
		'/kontak',
		'/pod',
		'/nashiyurist',
	);

	/** EN path => RU path (no trailing slash). */
	const HREFLANG_PAIRS = array(
		'/about-us'  => '/onas',
		'/articles'  => '/stati',
		'/team'      => '/komanda',
		'/contacts'  => '/kontak',
		'/support'   => '/pod',
		'/services'  => '/nashiyurist',
	);

	/** Exact external href rewrites (Ahrefs 3xx/4xx footer + broken attorney search). */
	const EXTERNAL_HREF_MAP = array(
		'https://www.istanbulbarosu.org.tr/' => 'https://istanbulbarosu.org.tr/',
		'http://www.istanbulbarosu.org.tr/' => 'https://istanbulbarosu.org.tr/',
		'http://istanbulbarosu.org.tr/' => 'https://istanbulbarosu.org.tr/',
		'https://istanbulbarosu.org.tr/AttorneySearch.aspx' => 'https://baronet.istanbulbarosu.org.tr/',
		'http://istanbulbarosu.org.tr/AttorneySearch.aspx' => 'https://baronet.istanbulbarosu.org.tr/',
		'https://www.istanbulbarosu.org.tr/AttorneySearch.aspx' => 'https://baronet.istanbulbarosu.org.tr/',
		'https://www.barobirlik.org.tr/en' => 'https://www.barobirlik.org.tr/',
		'http://www.barobirlik.org.tr/' => 'https://www.barobirlik.org.tr/',
		'http://barobirlik.org.tr/' => 'https://www.barobirlik.org.tr/',
		'https://barobirlik.org.tr/' => 'https://www.barobirlik.org.tr/',
	);

	public static function init() {
		add_action('template_redirect', array(__CLASS__, 'maybe_send_noindex_header'), 0);
		add_filter('wp_robots', array(__CLASS__, 'filter_wp_robots'), 99);
		add_action('wp_head', array(__CLASS__, 'print_noindex_meta'), 1);

		add_filter('wpseo_exclude_from_sitemap_by_post_ids', array(__CLASS__, 'exclude_noindex_from_sitemap'));
		add_filter('wpseo_sitemap_entry', array(__CLASS__, 'filter_sitemap_entry'), 10, 3);

		add_filter('language_attributes', array(__CLASS__, 'filter_language_attributes'), 20);

		add_filter('wpseo_canonical', array(__CLASS__, 'filter_canonical'), 99);
		add_filter('get_canonical_url', array(__CLASS__, 'filter_canonical'), 99);

		add_filter('wpseo_frontend_presenter_classes', array(__CLASS__, 'remove_yoast_hreflang_presenter'), 99);
		add_filter('wpseo_frontend_presenters', array(__CLASS__, 'remove_yoast_hreflang_presenter_objects'), 99);

		// Always buffer front-end HTML for hreflang / switcher / external link cleanup.
		add_action('template_redirect', array(__CLASS__, 'start_buffer'), 0);
		add_action('wp_footer', array(__CLASS__, 'print_version_marker'), 99);
	}

	private static function current_path() {
		$uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
		$path = (string) parse_url($uri, PHP_URL_PATH);
		$path = untrailingslashit($path);
		return $path === '' ? '/' : $path;
	}

	private static function current_query_lang() {
		$uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
		$query = (string) parse_url($uri, PHP_URL_QUERY);
		if ($query === '') {
			return '';
		}
		parse_str($query, $params);
		$lang = isset($params['lang']) ? strtolower((string) $params['lang']) : '';
		return $lang;
	}

	private static function path_matches_prefix($path, $prefix) {
		$path = untrailingslashit($path);
		$prefix = untrailingslashit($prefix);
		if ($path === $prefix) {
			return true;
		}
		return strpos($path, $prefix . '/') === 0;
	}

	private static function is_noindex_request() {
		$path = self::current_path();
		foreach (self::NOINDEX_SLUGS as $slug) {
			if (self::path_matches_prefix($path, '/' . $slug)) {
				return true;
			}
		}
		if (is_tag() || is_tax('post_tag')) {
			return true;
		}
		return false;
	}

	private static function is_russian_path($path = null) {
		$path = $path === null ? self::current_path() : untrailingslashit($path);
		if (self::current_query_lang() === 'ru') {
			return true;
		}
		foreach (self::RU_PATH_PREFIXES as $prefix) {
			if (self::path_matches_prefix($path, $prefix)) {
				return true;
			}
		}
		// Cyrillic slug ≈ Russian content.
		if (preg_match('/[А-Яа-яЁё]/u', urldecode($path))) {
			return true;
		}
		return false;
	}

	private static function pair_for_current_path() {
		$path = self::current_path();
		foreach (self::HREFLANG_PAIRS as $en => $ru) {
			if (self::path_matches_prefix($path, $en) || self::path_matches_prefix($path, $ru)) {
				return array($en, $ru);
			}
		}
		return null;
	}

	public static function maybe_send_noindex_header() {
		if (!self::is_noindex_request() || headers_sent()) {
			return;
		}
		header('X-Robots-Tag: noindex, follow', true);
	}

	public static function filter_wp_robots($robots) {
		if (!self::is_noindex_request()) {
			return $robots;
		}
		if (!is_array($robots)) {
			$robots = array();
		}
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset($robots['index']);
		return $robots;
	}

	public static function print_noindex_meta() {
		if (!self::is_noindex_request()) {
			return;
		}
		echo '<meta name="robots" content="noindex, follow" />' . "\n";
		echo '<meta name="googlebot" content="noindex, follow" />' . "\n";
	}

	public static function exclude_noindex_from_sitemap($excluded_ids) {
		if (!is_array($excluded_ids)) {
			$excluded_ids = array();
		}
		foreach (self::NOINDEX_SLUGS as $slug) {
			$page = get_page_by_path($slug);
			if ($page && !empty($page->ID)) {
				$excluded_ids[] = (int) $page->ID;
			}
		}
		return array_values(array_unique(array_map('intval', $excluded_ids)));
	}

	public static function filter_sitemap_entry($url, $type, $object) {
		if (empty($url) || !is_array($url)) {
			return $url;
		}
		$loc = isset($url['loc']) ? (string) $url['loc'] : '';
		if ($loc === '') {
			return $url;
		}
		$path = untrailingslashit((string) parse_url($loc, PHP_URL_PATH));
		foreach (self::NOINDEX_SLUGS as $slug) {
			if (self::path_matches_prefix($path, '/' . $slug)) {
				return false;
			}
		}
		return $url;
	}

	public static function filter_language_attributes($output) {
		if (!self::is_russian_path()) {
			return $output;
		}
		if (preg_match('/\blang=("|\')[^"\']*\1/', $output)) {
			return preg_replace('/\blang=("|\')[^"\']*\1/', 'lang=$1ru$1', $output, 1);
		}
		return trim($output . ' lang="ru"');
	}

	/**
	 * Keep canonical self-consistent: never point at a URL that 301s to ?lang=.
	 * For ?lang=ru requests, canonical must include ?lang=ru.
	 */
	public static function filter_canonical($canonical) {
		if (!is_string($canonical) || $canonical === '') {
			return $canonical;
		}
		$lang = self::current_query_lang();
		if ($lang === 'ru') {
			$parts = wp_parse_url($canonical);
			$path = isset($parts['path']) ? $parts['path'] : '/';
			$query = array();
			if (!empty($parts['query'])) {
				parse_str($parts['query'], $query);
			}
			$query['lang'] = 'ru';
			return home_url($path . '?' . http_build_query($query));
		}
		// Bare EN slug that only exists as RU redirect target in Ahrefs — keep query-less self URL.
		return $canonical;
	}

	public static function remove_yoast_hreflang_presenter($presenters) {
		if (!is_array($presenters)) {
			return $presenters;
		}
		return array_values(array_filter($presenters, function ($presenter) {
			if (!is_string($presenter)) {
				return true;
			}
			return strpos($presenter, 'Rel_Alternate_Presenter') === false
				&& stripos($presenter, 'Hreflang') === false;
		}));
	}

	public static function remove_yoast_hreflang_presenter_objects($presenters) {
		if (!is_array($presenters)) {
			return $presenters;
		}
		return array_values(array_filter($presenters, function ($presenter) {
			if (!is_object($presenter)) {
				return true;
			}
			$class = get_class($presenter);
			return strpos($class, 'Rel_Alternate_Presenter') === false
				&& stripos($class, 'Hreflang') === false
				&& stripos($class, 'Alternate') === false;
		}));
	}

	public static function start_buffer() {
		if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('DOING_AJAX') && DOING_AJAX) || (defined('DOING_CRON') && DOING_CRON)) {
			return;
		}
		ob_start(array(__CLASS__, 'filter_html_buffer'));
	}

	public static function filter_html_buffer($html) {
		if (!is_string($html) || $html === '') {
			return $html;
		}

		$html = self::rewrite_external_hrefs($html);
		$html = self::fix_language_switcher_anchors($html);
		$html = self::rebuild_hreflang_tags($html);

		return $html;
	}

	private static function rewrite_external_hrefs($html) {
		foreach (self::EXTERNAL_HREF_MAP as $from => $to) {
			$html = str_replace(
				array(
					'href="' . $from . '"',
					"href='" . $from . "'",
					'href="' . esc_url($from) . '"',
				),
				'href="' . esc_url($to) . '"',
				$html
			);
		}
		// Catch encoded ampersands variants for attorney search leftovers.
		$html = preg_replace(
			'#https?://(?:www\.)?istanbulbarosu\.org\.tr/AttorneySearch\.aspx#i',
			'https://baronet.istanbulbarosu.org.tr/',
			$html
		);
		return $html;
	}

	/**
	 * Enfold/Polylang bug: every language flag points to ?lang=ru#top with wrong hreflang.
	 * Rebuild anchors so each hreflang points at the correct language URL for the current path.
	 */
	private static function fix_language_switcher_anchors($html) {
		$path = self::current_path();
		$path_url = $path === '/' ? home_url('/') : home_url($path . '/');

		$targets = array(
			'en'      => $path_url,
			'x-default' => $path_url,
			'ru'      => self::add_lang_query($path_url, 'ru'),
			'zh-hans' => self::add_lang_query($path_url, 'zh-hans'),
			'zh'      => self::add_lang_query($path_url, 'zh-hans'),
		);

		// Known dedicated RU landing paths beat ?lang=ru for cornerstone pages.
		$pair = self::pair_for_current_path();
		if ($pair !== null) {
			list($en, $ru) = $pair;
			$targets['en'] = home_url($en . '/');
			$targets['x-default'] = $targets['en'];
			$targets['ru'] = home_url($ru . '/');
		}

		return preg_replace_callback(
			'/<a\b([^>]*\bhreflang=(["\'])([^"\']+)\2[^>]*)>/i',
			function ($m) use ($targets) {
				$attrs = $m[1];
				$lang = strtolower($m[3]);
				if (!isset($targets[$lang])) {
					return $m[0];
				}
				$href = $targets[$lang];
				if (preg_match('/\bhref=(["\'])[^"\']*\1/i', $attrs)) {
					$attrs = preg_replace('/\bhref=(["\'])[^"\']*\1/i', 'href="' . esc_url($href) . '"', $attrs, 1);
				} else {
					$attrs .= ' href="' . esc_url($href) . '"';
				}
				// Drop #top noise from language switcher.
				$attrs = preg_replace('/#top/i', '', $attrs);
				return '<a' . $attrs . '>';
			},
			$html
		);
	}

	private static function add_lang_query($url, $lang) {
		$sep = (strpos($url, '?') === false) ? '?' : '&';
		return $url . $sep . 'lang=' . rawurlencode($lang);
	}

	/**
	 * Strip all link[rel=alternate][hreflang], then inject a clean set.
	 */
	private static function rebuild_hreflang_tags($html) {
		$html = preg_replace('/\s*<link[^>]+rel=(["\'])alternate\1[^>]*hreflang=[^>]+>/i', '', $html);
		$html = preg_replace('/\s*<link[^>]+hreflang=[^>]+rel=(["\'])alternate\1[^>]*>/i', '', $html);

		$tags = self::hreflang_tags_for_request();
		if ($tags === '') {
			return $html;
		}
		if (stripos($html, '</head>') !== false) {
			return preg_replace('/<\/head>/i', $tags . '</head>', $html, 1);
		}
		return $html . $tags;
	}

	private static function hreflang_tags_for_request() {
		$pair = self::pair_for_current_path();
		if ($pair !== null) {
			list($en_path, $ru_path) = $pair;
			$en = home_url($en_path . '/');
			$ru = home_url($ru_path . '/');
			return "\n"
				. '<link rel="alternate" href="' . esc_url($en) . '" hreflang="en" />' . "\n"
				. '<link rel="alternate" href="' . esc_url($ru) . '" hreflang="ru" />' . "\n"
				. '<link rel="alternate" href="' . esc_url($en) . '" hreflang="x-default" />' . "\n";
		}

		$path = self::current_path();
		$lang = self::current_query_lang();

		// Homepage: English only — do not claim zh/ru on the same URL.
		if ($path === '/') {
			if ($lang === 'ru' || $lang === 'zh-hans' || $lang === 'zh') {
				$en = home_url('/');
				$self = self::add_lang_query($en, $lang === 'zh' ? 'zh-hans' : $lang);
				$code = ($lang === 'zh') ? 'zh-hans' : $lang;
				return "\n"
					. '<link rel="alternate" href="' . esc_url($en) . '" hreflang="en" />' . "\n"
					. '<link rel="alternate" href="' . esc_url($self) . '" hreflang="' . esc_attr($code) . '" />' . "\n"
					. '<link rel="alternate" href="' . esc_url($en) . '" hreflang="x-default" />' . "\n";
			}
			$en = home_url('/');
			return "\n"
				. '<link rel="alternate" href="' . esc_url($en) . '" hreflang="en" />' . "\n"
				. '<link rel="alternate" href="' . esc_url($en) . '" hreflang="x-default" />' . "\n";
		}

		// Other pages: single language + x-default (no multi-lang self-refs).
		$self = home_url($path === '/' ? '/' : $path . '/');

		if ($lang === 'ru' || self::is_russian_path($path)) {
			$ru = ($lang === 'ru') ? self::add_lang_query(home_url($path . '/'), 'ru') : $self;
			$en = home_url($path . '/');
			return "\n"
				. '<link rel="alternate" href="' . esc_url($en) . '" hreflang="en" />' . "\n"
				. '<link rel="alternate" href="' . esc_url($ru) . '" hreflang="ru" />' . "\n"
				. '<link rel="alternate" href="' . esc_url($en) . '" hreflang="x-default" />' . "\n";
		}

		if ($lang === 'zh-hans' || $lang === 'zh') {
			$en = home_url($path . '/');
			$zh = self::add_lang_query($en, 'zh-hans');
			return "\n"
				. '<link rel="alternate" href="' . esc_url($en) . '" hreflang="en" />' . "\n"
				. '<link rel="alternate" href="' . esc_url($zh) . '" hreflang="zh-hans" />' . "\n"
				. '<link rel="alternate" href="' . esc_url($en) . '" hreflang="x-default" />' . "\n";
		}

		// Default English page: only en + x-default (fixes Ahrefs multi-lang self-reference).
		return "\n"
			. '<link rel="alternate" href="' . esc_url($self) . '" hreflang="en" />' . "\n"
			. '<link rel="alternate" href="' . esc_url($self) . '" hreflang="x-default" />' . "\n";
	}

	public static function print_version_marker() {
		echo '<!-- cindemir-index-hygiene ' . esc_html(self::VERSION) . ' -->' . "\n";
	}
}

Cindemir_Index_Hygiene::init();

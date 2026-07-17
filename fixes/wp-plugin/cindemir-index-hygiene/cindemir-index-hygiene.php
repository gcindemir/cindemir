<?php
/**
 * Plugin Name: Cindemir Index Hygiene
 * Description: Ahrefs cleanup: noindex, hreflang, switcher, canonical, baro links, Our Videos orphan, P0 hub links, lang-query href cleanup, safe cluster redirects.
 * Version: 2.2.1
 * Author: Cindemir Law Office
 */

if (!defined('ABSPATH')) {
	exit;
}

final class Cindemir_Index_Hygiene {
	const VERSION = '2.2.1';

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
		// Canonical Facebook page (replace legacy page id in footer/widgets).
		'https://www.facebook.com/Cindemir-Hukuk-Brosu-Cindemir-Law-Office-336218871992/' => 'https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/',
		'http://www.facebook.com/Cindemir-Hukuk-Brosu-Cindemir-Law-Office-336218871992/' => 'https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/',
	);

	public static function init() {
		add_action('template_redirect', array(__CLASS__, 'maybe_send_noindex_header'), 0);
		add_action('template_redirect', array(__CLASS__, 'maybe_cluster_redirect'), 1);
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
		add_filter('the_content', array(__CLASS__, 'append_p0_hub_links'), 25);
		add_action('wp_footer', array(__CLASS__, 'print_orphan_nav'), 20);
		add_action('wp_footer', array(__CLASS__, 'print_version_marker'), 99);
	}

	/**
	 * Thin / near-duplicate URLs → P0 (or clearer hub) 301s.
	 * Path without trailing slash => target path without trailing slash.
	 */
	const CLUSTER_REDIRECTS = array(
		'/obtaining-a-criminal-record-certificate-in-turkey-a-step-by-step-guide' => '/getting-criminal-record-in-turkey',
		'/debt-collection-service' => '/debt-recovery-in-turkey',
		'/establishing-a-commercial-enterprise-in-turkey' => '/opening-a-company-in-turkey-for-foreigners',
		'/methods-of-debt-collection-in-the-light-of-turkish-law' => '/debt-recovery-in-turkey',
	);

	public static function maybe_cluster_redirect() {
		if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('DOING_AJAX') && DOING_AJAX)) {
			return;
		}
		$path = self::current_path();
		if (!isset(self::CLUSTER_REDIRECTS[$path])) {
			return;
		}
		$target = home_url(self::CLUSTER_REDIRECTS[$path] . '/');
		// Preserve only non-lang query noise carefully — drop lang so equity lands on EN P0.
		wp_safe_redirect($target, 301);
		exit;
	}

	/**
	 * Money-page (P0) guides — push internal equity off the homepage/hubs.
	 * Paths without trailing slash.
	 */
	const P0_GUIDES = array(
		'/opening-a-company-in-turkey-for-foreigners' => 'Company formation for foreigners',
		'/consensual-divorce-in-turkey-uncontested-divorce' => 'Uncontested divorce in Turkey',
		'/deportation-law-in-turkey' => 'Deportation law in Turkey',
		'/debt-recovery-in-turkey' => 'Debt recovery in Turkey',
		'/getting-criminal-record-in-turkey' => 'Criminal record certificate in Turkey',
	);

	/** Hubs that should list all P0 guides. */
	const P0_HUB_SLUGS = array(
		'services',
		'about-us',
		'nashiyurist',
		'onas',
	);

	/**
	 * Related single posts: slug => subset of P0 paths to cross-link.
	 */
	const P0_RELATED = array(
		'can-russian-establish-a-company-in-turkey' => array(
			'/opening-a-company-in-turkey-for-foreigners',
		),
		'starting-a-company-in-turkey-a-comprehensive-guide-for-entrepreneurs' => array(
			'/opening-a-company-in-turkey-for-foreigners',
		),
		'how-to-divorce-in-turkey' => array(
			'/consensual-divorce-in-turkey-uncontested-divorce',
		),
		'consequences-of-a-divorce-decision-in-turkey' => array(
			'/consensual-divorce-in-turkey-uncontested-divorce',
		),
		'methods-of-debt-collection-in-the-light-of-turkish-law' => array(
			'/debt-recovery-in-turkey',
		),
		'debt-collection-service' => array(
			'/debt-recovery-in-turkey',
		),
		'obtaining-a-criminal-record-certificate-in-turkey-a-step-by-step-guide' => array(
			'/getting-criminal-record-in-turkey',
		),
		'criminal-record-deletion-in-turkey-for-foreign-nationals' => array(
			'/getting-criminal-record-in-turkey',
		),
		'principle-of-family-unity-deportation-procedures-and-protection-of-family-unity-for-deportees' => array(
			'/deportation-law-in-turkey',
		),
	);

	public static function append_p0_hub_links($content) {
		if (is_admin() || !is_singular() || (function_exists('is_feed') && is_feed())) {
			return $content;
		}
		if (strpos($content, 'cindemir-p0-hub-links') !== false) {
			return $content;
		}

		$post = get_post();
		if (!$post) {
			return $content;
		}
		$slug = $post->post_name;

		$paths = array();
		if (in_array($slug, self::P0_HUB_SLUGS, true)) {
			$paths = array_keys(self::P0_GUIDES);
		} elseif (isset(self::P0_RELATED[$slug])) {
			$paths = self::P0_RELATED[$slug];
		} else {
			return $content;
		}

		$items = '';
		foreach ($paths as $path) {
			if (!isset(self::P0_GUIDES[$path])) {
				continue;
			}
			// Skip if this page already links to the target in body.
			if (strpos($content, $path) !== false) {
				continue;
			}
			$url = home_url($path . '/');
			// Always point equity at clean EN P0 URLs (no ?lang= from Polylang context).
			$url = preg_replace('#\?.*$#', '', $url);
			$label = self::P0_GUIDES[$path];
			$items .= '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
		}
		if ($items === '') {
			return $content;
		}

		$heading = in_array($slug, self::P0_HUB_SLUGS, true)
			? 'Key practice guides'
			: 'Related guides';

		$block = "\n"
			. '<!-- cindemir-p0-hub-links -->'
			. '<aside class="cindemir-p0-hub-links" style="margin:1.5rem 0;padding:1rem 0;border-top:1px solid #ddd;">'
			. '<p style="margin:0 0 .5rem;font-weight:600;">' . esc_html($heading) . '</p>'
			. '<ul style="margin:0;padding-left:1.25rem;">' . $items . '</ul>'
			. '</aside>' . "\n";

		return $content . $block;
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
		if (isset(self::CLUSTER_REDIRECTS[$path])) {
			return false;
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
		$html = self::rewrite_lang_query_hrefs($html);
		$html = self::rebuild_hreflang_tags($html);
		$html = self::inject_p0_hub_into_html($html);

		return $html;
	}

	/**
	 * Rewrite internal ?lang=ru / ?lang=zh-hans hrefs toward dedicated RU paths when paired,
	 * and stop funneling everything at /?lang=ru|zh-hans.
	 */
	private static function rewrite_lang_query_hrefs($html) {
		$host = wp_parse_url(home_url('/'), PHP_URL_HOST);
		if (!$host) {
			return $html;
		}
		$host_re = preg_quote($host, '#');

		return preg_replace_callback(
			'#href=(["\'])(https?://' . $host_re . '[^"\']*|/[^\s"\']*)\1#i',
			function ($m) {
				$quote = $m[1];
				$url = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
				$parts = wp_parse_url($url);
				if ($parts === false) {
					return $m[0];
				}
				$query = array();
				if (!empty($parts['query'])) {
					parse_str($parts['query'], $query);
				}
				if (empty($query['lang'])) {
					return $m[0];
				}
				$lang = strtolower((string) $query['lang']);
				$path = isset($parts['path']) ? untrailingslashit($parts['path']) : '/';
				if ($path === '') {
					$path = '/';
				}

				$new = null;
				if ($lang === 'ru') {
					// Paired cornerstone: use dedicated RU URL.
					foreach (self::HREFLANG_PAIRS as $en => $ru) {
						if (self::path_matches_prefix($path, $en)) {
							$new = home_url($ru . '/');
							break;
						}
						if (self::path_matches_prefix($path, $ru)) {
							$new = home_url($ru . '/');
							break;
						}
					}
					// Homepage RU stays ?lang=ru (no dedicated RU home path).
					if ($new === null && ($path === '/' || $path === '')) {
						$new = home_url('/?lang=ru');
					}
					// Other pages: keep self-path ?lang=ru (canonical consistency) — no change needed.
				} elseif ($lang === 'zh-hans' || $lang === 'zh') {
					// No dedicated ZH paths — keep query on same path (not homepage-only funnel).
					if ($path === '/' || $path === '') {
						$new = home_url('/?lang=zh-hans');
					}
				}

				if ($new === null) {
					return $m[0];
				}
				return 'href=' . $quote . esc_url($new) . $quote;
			},
			$html
		);
	}

	/**
	 * Elementor / cached hubs may not expose the_content; inject before </body> by path.
	 */
	private static function inject_p0_hub_into_html($html) {
		if (strpos($html, 'cindemir-p0-hub-links') !== false) {
			return $html;
		}
		$path = self::current_path();
		$hub_paths = array('/services', '/about-us', '/nashiyurist', '/onas');
		if (!in_array($path, $hub_paths, true)) {
			return $html;
		}

		$items = '';
		foreach (self::P0_GUIDES as $guide_path => $label) {
			$url = preg_replace('#\?.*$#', '', home_url($guide_path . '/'));
			$items .= '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
		}
		$block = "\n"
			. '<!-- cindemir-p0-hub-links -->'
			. '<aside class="cindemir-p0-hub-links" style="max-width:1200px;margin:1.5rem auto;padding:1rem 20px;border-top:1px solid #ddd;">'
			. '<p style="margin:0 0 .5rem;font-weight:600;">Key practice guides</p>'
			. '<ul style="margin:0;padding-left:1.25rem;">' . $items . '</ul>'
			. '</aside>' . "\n";

		if (stripos($html, '</body>') !== false) {
			return preg_replace('/<\/body>/i', $block . '</body>', $html, 1);
		}
		return $html . $block;
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

	/** Fix orphan Our Videos with a real internal link (appointment stays noindex). */
	public static function print_orphan_nav() {
		if (is_admin()) {
			return;
		}
		echo "\n<nav class=\"cindemir-orphan-links\" aria-label=\"More\" style=\"max-width:1200px;margin:0 auto 1rem;padding:0 20px;font-size:13px;\">";
		echo '<a href="' . esc_url(home_url('/our-videos/')) . '">Our Videos</a>';
		echo "</nav>\n";
	}

	public static function print_version_marker() {
		echo '<!-- cindemir-index-hygiene ' . esc_html(self::VERSION) . ' -->' . "\n";
	}
}

Cindemir_Index_Hygiene::init();

<?php
/**
 * Plugin Name: Cindemir Index Hygiene
 * Description: Improves Google index ratio: noindex utility pages, fix RU html lang, replace broken EN↔RU Yoast hreflang pairs.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if (!defined('ABSPATH')) {
	exit;
}

final class Cindemir_Index_Hygiene {
	const VERSION = '1.0.0';

	/** Slugs that should never compete for Google index budget. */
	const NOINDEX_SLUGS = array(
		'appointment',
		'online-appointment-booking',
		'antimanual-assistant',
		'embed-list',
	);

	/**
	 * Path prefixes treated as Russian content (html lang + known pairs).
	 * Trailing slash optional; matched against request path.
	 */
	const RU_PATH_PREFIXES = array(
		'/onas',
		'/stati',
		'/komanda',
		'/kontak',
		'/pod',
		'/nashiyurist',
	);

	/**
	 * Canonical EN ↔ RU pairs where Yoast emitted broken self-referencing hreflangs.
	 * Keys and values are path prefixes without trailing slash.
	 */
	const HREFLANG_PAIRS = array(
		'/about-us'  => '/onas',
		'/articles'  => '/stati',
		'/team'      => '/komanda',
		'/contacts'  => '/kontak',
		'/support'   => '/pod',
		'/services'  => '/nashiyurist',
	);

	public static function init() {
		add_action('template_redirect', array(__CLASS__, 'maybe_send_noindex_header'), 0);
		add_filter('wp_robots', array(__CLASS__, 'filter_wp_robots'), 99);
		add_action('wp_head', array(__CLASS__, 'print_noindex_meta'), 1);

		add_filter('wpseo_exclude_from_sitemap_by_post_ids', array(__CLASS__, 'exclude_noindex_from_sitemap'));
		add_filter('wpseo_sitemap_entry', array(__CLASS__, 'filter_sitemap_entry'), 10, 3);

		add_filter('language_attributes', array(__CLASS__, 'filter_language_attributes'), 20);

		// Must register before Yoast renders head presenters.
		add_action('template_redirect', array(__CLASS__, 'maybe_prepare_hreflang_fix'), 5);
		add_action('wp_head', array(__CLASS__, 'maybe_print_hreflang'), 1);
	}

	private static function current_path() {
		$uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
		$path = (string) parse_url($uri, PHP_URL_PATH);
		$path = untrailingslashit($path);
		return $path === '' ? '/' : $path;
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
		foreach (self::RU_PATH_PREFIXES as $prefix) {
			if (self::path_matches_prefix($path, $prefix)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * If current path is part of a known EN↔RU pair, return [en_path, ru_path].
	 */
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
		if (!self::is_noindex_request()) {
			return;
		}
		if (!headers_sent()) {
			header('X-Robots-Tag: noindex, follow', true);
		}
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
		$path = (string) parse_url($loc, PHP_URL_PATH);
		$path = untrailingslashit($path);
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

	public static function maybe_prepare_hreflang_fix() {
		if (self::pair_for_current_path() === null) {
			return;
		}
		// Only on known broken EN↔RU pairs: drop Yoast's self-referencing alternates.
		add_filter('wpseo_frontend_presenter_classes', array(__CLASS__, 'remove_yoast_hreflang_presenter'), 20);
	}

	public static function maybe_print_hreflang() {
		$pair = self::pair_for_current_path();
		if ($pair === null) {
			return;
		}

		list($en_path, $ru_path) = $pair;
		$en = home_url($en_path . '/');
		$ru = home_url($ru_path . '/');

		echo '<link rel="alternate" href="' . esc_url($en) . '" hreflang="en" />' . "\n";
		echo '<link rel="alternate" href="' . esc_url($ru) . '" hreflang="ru" />' . "\n";
		echo '<link rel="alternate" href="' . esc_url($en) . '" hreflang="x-default" />' . "\n";
	}

	public static function remove_yoast_hreflang_presenter($presenters) {
		if (!is_array($presenters)) {
			return $presenters;
		}
		return array_values(array_filter($presenters, function ($presenter) {
			return $presenter !== 'Yoast\WP\SEO\Presenters\Rel_Alternate_Presenter';
		}));
	}
}

Cindemir_Index_Hygiene::init();

<?php
/**
 * Plugin Name: Cindemir Language Default
 * Description: Stop cindemir_lang cookie from forcing RU/ZH on bare URLs. Default remains English unless ?lang= is explicit.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_LANG_DEFAULT_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_LANG_DEFAULT_LOADED', true );
define( 'CINDEMIR_DISABLE_LANG_COOKIE', true );

/**
 * Must run before cindemir-seo-fixes.php (alphabetical load order).
 * SEO injects $_GET['lang'] from the cookie at file include time — clear it first.
 */
if ( ! empty( $_COOKIE['cindemir_lang'] ) ) {
	unset( $_COOKIE['cindemir_lang'] );
}

/**
 * Expire any lingering language cookie so bare cindemirlaw.com stays English.
 */
function cindemir_lang_default_expire_cookie() {
	if ( headers_sent() ) {
		return;
	}
	$secure = is_ssl();
	$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
	$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
	setcookie( 'cindemir_lang', '', time() - YEAR_IN_SECONDS, $path, $domain, $secure, true );
	setcookie( 'cindemir_lang', '', time() - YEAR_IN_SECONDS, '/', $domain, $secure, true );
	unset( $_COOKIE['cindemir_lang'] );
}

/**
 * Detach SEO cookie persistence / cookie→?lang= redirect hooks.
 */
function cindemir_lang_default_detach_seo_hooks() {
	if ( ! class_exists( 'Cindemir_SEO_Fixes', false ) ) {
		return;
	}
	remove_action( 'send_headers', array( 'Cindemir_SEO_Fixes', 'persist_lang_cookie' ), 0 );
	remove_action( 'template_redirect', array( 'Cindemir_SEO_Fixes', 'redirect_cookie_lang_to_query' ), 0 );
	remove_action( 'wpml_loaded', array( 'Cindemir_SEO_Fixes', 'switch_wpml_from_cookie' ), 0 );
	remove_action( 'plugins_loaded', array( 'Cindemir_SEO_Fixes', 'switch_wpml_from_cookie' ), 20 );
}

add_action( 'plugins_loaded', 'cindemir_lang_default_detach_seo_hooks', 1 );
add_action( 'init', 'cindemir_lang_default_detach_seo_hooks', 0 );
add_action( 'send_headers', 'cindemir_lang_default_expire_cookie', 0 );

/**
 * Neutralize client-side cookie writes from SEO language switcher JS in HTML buffers.
 *
 * @param string $html HTML buffer.
 * @return string
 */
function cindemir_lang_default_strip_cookie_js( $html ) {
	if ( ! is_string( $html ) || $html === '' ) {
		return $html;
	}
	// Expire immediately instead of persisting for a year.
	$html = str_replace(
		'document.cookie="cindemir_lang="+map[code]+";path=/;max-age=31536000;SameSite=Lax;Secure"',
		'document.cookie="cindemir_lang=;path=/;max-age=0;SameSite=Lax;Secure"',
		$html
	);
	$html = str_replace(
		'document.cookie="cindemir_lang="+map[code]+";path=/;max-age=31536000;SameSite=Lax"',
		'document.cookie="cindemir_lang=;path=/;max-age=0;SameSite=Lax"',
		$html
	);
	$html = preg_replace(
		'/document\.cookie="cindemir_lang="\+encodeURIComponent\(lang\)\+";path=\/;max-age=31536000;SameSite=Lax"/',
		'document.cookie="cindemir_lang=;path=/;max-age=0;SameSite=Lax"',
		$html
	);
	return $html;
}

add_filter( 'rocket_buffer', 'cindemir_lang_default_strip_cookie_js', 100 );

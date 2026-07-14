<?php
/**
 * Plugin Name: Cindemir SEO Fixes
 * Description: Full Ahrefs cleanup: redirect href rewrite, flatten hops, H1/alts/orphans, author disable, title trim.
 * Version: 1.9.10
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_SEO_FIXES_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_SEO_FIXES_LOADED', true );

/**
 * Persist active language across bare permalinks (Rocket-cached pages included).
 * WPML was not setting wp-wpml_current_language for ?lang= URLs, so menu clicks
 * to /about-us/ fell back to English. Propagate ?lang= from cookie before WPML boots.
 */
if ( ! empty( $_GET['cindemir_lang'] ) && 'en' === strtolower( (string) $_GET['cindemir_lang'] ) ) {
	if ( ! defined( 'CINDEMIR_CLEAR_LANG' ) ) {
		define( 'CINDEMIR_CLEAR_LANG', true );
	}
	unset( $_COOKIE['cindemir_lang'] );
} elseif ( empty( $_GET['lang'] ) && ! empty( $_COOKIE['cindemir_lang'] ) ) {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	if ( false === strpos( $uri, '/wp-admin' ) && false === strpos( $uri, 'wp-login.php' ) ) {
		$raw = $_COOKIE['cindemir_lang'];
		if ( function_exists( 'wp_unslash' ) ) {
			$raw = wp_unslash( $raw );
		}
		$cindemir_cookie_lang = strtolower( preg_replace( '/[^a-z0-9\-]/i', '', (string) $raw ) );
		if ( in_array( $cindemir_cookie_lang, array( 'ru', 'zh-hans', 'zh', 'tr' ), true ) ) {
			if ( ! defined( 'CINDEMIR_LANG_FROM_COOKIE' ) ) {
				define( 'CINDEMIR_LANG_FROM_COOKIE', $cindemir_cookie_lang );
			}
			$_GET['lang']     = $cindemir_cookie_lang;
			$_REQUEST['lang'] = $cindemir_cookie_lang;
			$qs               = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
			if ( '' === $qs ) {
				$_SERVER['QUERY_STRING'] = 'lang=' . $cindemir_cookie_lang;
			} elseif ( false === strpos( $qs, 'lang=' ) ) {
				$_SERVER['QUERY_STRING'] = $qs . '&lang=' . $cindemir_cookie_lang;
			}
			if ( false === strpos( $uri, 'lang=' ) ) {
				$_SERVER['REQUEST_URI'] = $uri . ( false === strpos( $uri, '?' ) ? '?' : '&' ) . 'lang=' . $cindemir_cookie_lang;
			}
		}
	}
}

final class Cindemir_SEO_Fixes {

	private static $broken = array(
		'/how-to-lift-entry-ban-to-turkey',
		'/exemptions-on-the-legislation-of-the-documents-in-turkey',
	);

	/** One-hop 301 + href rewrite map (path without trailing slash). */
	private static $redirects = array(
		'/russian' => 'https://cindemirlaw.com/?lang=ru',
		'/chinese' => 'https://cindemirlaw.com/?lang=zh-hans',
		'/zh' => 'https://cindemirlaw.com/?lang=zh-hans',
		'/zh-hans' => 'https://cindemirlaw.com/?lang=zh-hans',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbdfde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbdfde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-3' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-3/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd82fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd82fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-3' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-3/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb3fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb3fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb4fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb4fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb5fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb5fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb6fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb6fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb7fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb7fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbafde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbdfde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbdfde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd82fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd82fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/cindemir-hukuk-burosu-cindemir-law-office-kusdili-caddesi-osmanaga-mahallesi-artunc-apartmani-no173-34714-kadikoy-istanbul' => 'https://cindemirlaw.com/cindemir/',
		'/pig-butchering-cryptocurrency-scam-key-risks-and-legal-considerations-for-investors-in-turkey' => 'https://cindemirlaw.com/pig-butchering-cryptocurrency-scam-key-risks-and-legal-considerations-for-investors-in-turkey/?lang=ru',
		'/eu-ai-act-compliance-for-non-eu-companies-legal-requirements-under-the-destination-principle' => 'https://cindemirlaw.com/eu-ai-act-compliance-for-non-eu-companies-legal-requirements-under-the-destination-principle/?lang=ru',
		'/obtaining-an-e-devlet-password-in-turkey-through-a-power-of-attorney' => 'https://cindemirlaw.com/obtaining-an-e-devlet-password-in-turkey-through-a-power-of-attorney/?lang=ru',
		'/репатриация-активов-в-турцию-в-2026-году-п' => 'https://cindemirlaw.com/%d1%80%d0%b5%d0%bf%d0%b0%d1%82%d1%80%d0%b8%d0%b0%d1%86%d0%b8%d1%8f-%d0%b0%d0%ba%d1%82%d0%b8%d0%b2%d0%be%d0%b2-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d1%8e-%d0%b2-2026-%d0%b3%d0%be%d0%b4%d1%83-%d0%bf/?lang=ru',
		'/что-такое-заявление-в-еспч-кто-может-по' => 'https://cindemirlaw.com/%d1%87%d1%82%d0%be-%d1%82%d0%b0%d0%ba%d0%be%d0%b5-%d0%b7%d0%b0%d1%8f%d0%b2%d0%bb%d0%b5%d0%bd%d0%b8%d0%b5-%d0%b2-%d0%b5%d1%81%d0%bf%d1%87-%d0%ba%d1%82%d0%be-%d0%bc%d0%be%d0%b6%d0%b5%d1%82-%d0%bf%d0%be/?lang=ru',
		'/гуманитарный-вид-на-жительство-в-турц' => 'https://cindemirlaw.com/%d0%b3%d1%83%d0%bc%d0%b0%d0%bd%d0%b8%d1%82%d0%b0%d1%80%d0%bd%d1%8b%d0%b9-%d0%b2%d0%b8%d0%b4-%d0%bd%d0%b0-%d0%b6%d0%b8%d1%82%d0%b5%d0%bb%d1%8c%d1%81%d1%82%d0%b2%d0%be-%d0%b2-%d1%82%d1%83%d1%80%d1%86/?lang=ru',
		'/иск-об-установлении-отцовства-в-турци' => 'https://cindemirlaw.com/%d0%b8%d1%81%d0%ba-%d0%be%d0%b1-%d1%83%d1%81%d1%82%d0%b0%d0%bd%d0%be%d0%b2%d0%bb%d0%b5%d0%bd%d0%b8%d0%b8-%d0%be%d1%82%d1%86%d0%be%d0%b2%d1%81%d1%82%d0%b2%d0%b0-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8/?lang=ru',
		'/как-открыть-компанию-в-турции-пошагов' => 'https://cindemirlaw.com/%d0%ba%d0%b0%d0%ba-%d0%be%d1%82%d0%ba%d1%80%d1%8b%d1%82%d1%8c-%d0%ba%d0%be%d0%bc%d0%bf%d0%b0%d0%bd%d0%b8%d1%8e-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8-%d0%bf%d0%be%d1%88%d0%b0%d0%b3%d0%be%d0%b2/?lang=ru',
		'/как-получить-справку-о-наличии-судимо' => 'https://cindemirlaw.com/%d0%ba%d0%b0%d0%ba-%d0%bf%d0%be%d0%bb%d1%83%d1%87%d0%b8%d1%82%d1%8c-%d1%81%d0%bf%d1%80%d0%b0%d0%b2%d0%ba%d1%83-%d0%be-%d0%bd%d0%b0%d0%bb%d0%b8%d1%87%d0%b8%d0%b8-%d1%81%d1%83%d0%b4%d0%b8%d0%bc%d0%be/?lang=ru',
		'/удаление-судимости-в-турции-для-иност' => 'https://cindemirlaw.com/%d1%83%d0%b4%d0%b0%d0%bb%d0%b5%d0%bd%d0%b8%d0%b5-%d1%81%d1%83%d0%b4%d0%b8%d0%bc%d0%be%d1%81%d1%82%d0%b8-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8-%d0%b4%d0%bb%d1%8f-%d0%b8%d0%bd%d0%be%d1%81%d1%82/?lang=ru',
		'/задержание-в-аэропорту-турции-правов' => 'https://cindemirlaw.com/%d0%b7%d0%b0%d0%b4%d0%b5%d1%80%d0%b6%d0%b0%d0%bd%d0%b8%d0%b5-%d0%b2-%d0%b0%d1%8d%d1%80%d0%be%d0%bf%d0%be%d1%80%d1%82%d1%83-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8-%d0%bf%d1%80%d0%b0%d0%b2%d0%be%d0%b2/?lang=ru',
		'/открытие-банковского-счета-для-росси' => 'https://cindemirlaw.com/%d0%be%d1%82%d0%ba%d1%80%d1%8b%d1%82%d0%b8%d0%b5-%d0%b1%d0%b0%d0%bd%d0%ba%d0%be%d0%b2%d1%81%d0%ba%d0%be%d0%b3%d0%be-%d1%81%d1%87%d0%b5%d1%82%d0%b0-%d0%b4%d0%bb%d1%8f-%d1%80%d0%be%d1%81%d1%81%d0%b8/?lang=ru',
		'/создание-компании-с-ограниченной-отв' => 'https://cindemirlaw.com/%d1%81%d0%be%d0%b7%d0%b4%d0%b0%d0%bd%d0%b8%d0%b5-%d0%ba%d0%be%d0%bc%d0%bf%d0%b0%d0%bd%d0%b8%d0%b8-%d1%81-%d0%be%d0%b3%d1%80%d0%b0%d0%bd%d0%b8%d1%87%d0%b5%d0%bd%d0%bd%d0%be%d0%b9-%d0%be%d1%82%d0%b2/?lang=ru',
		'/юридическая-помощь-при-отправке-веще' => 'https://cindemirlaw.com/%d1%8e%d1%80%d0%b8%d0%b4%d0%b8%d1%87%d0%b5%d1%81%d0%ba%d0%b0%d1%8f-%d0%bf%d0%be%d0%bc%d0%be%d1%89%d1%8c-%d0%bf%d1%80%d0%b8-%d0%be%d1%82%d0%bf%d1%80%d0%b0%d0%b2%d0%ba%d0%b5-%d0%b2%d0%b5%d1%89%d0%b5/?lang=ru',
		'/компенсации-положенные-в-результате' => 'https://cindemirlaw.com/%d0%ba%d0%be%d0%bc%d0%bf%d0%b5%d0%bd%d1%81%d0%b0%d1%86%d0%b8%d0%b8-%d0%bf%d0%be%d0%bb%d0%be%d0%b6%d0%b5%d0%bd%d0%bd%d1%8b%d0%b5-%d0%b2-%d1%80%d0%b5%d0%b7%d1%83%d0%bb%d1%8c%d1%82%d0%b0%d1%82%d0%b5/?lang=ru',
		'/открытие-банковского-счета-в-турции' => 'https://cindemirlaw.com/%d0%be%d1%82%d0%ba%d1%80%d1%8b%d1%82%d0%b8%d0%b5-%d0%b1%d0%b0%d0%bd%d0%ba%d0%be%d0%b2%d1%81%d0%ba%d0%be%d0%b3%d0%be-%d1%81%d1%87%d0%b5%d1%82%d0%b0-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/открытие-банковского-счета-русскими' => 'https://cindemirlaw.com/%d0%be%d1%82%d0%ba%d1%80%d1%8b%d1%82%d0%b8%d0%b5-%d0%b1%d0%b0%d0%bd%d0%ba%d0%be%d0%b2%d1%81%d0%ba%d0%be%d0%b3%d0%be-%d1%81%d1%87%d0%b5%d1%82%d0%b0-%d1%80%d1%83%d1%81%d1%81%d0%ba%d0%b8%d0%bc%d0%b8/?lang=ru',
		'/посещение-иностранных-заключённых-в' => 'https://cindemirlaw.com/%d0%bf%d0%be%d1%81%d0%b5%d1%89%d0%b5%d0%bd%d0%b8%d0%b5-%d0%b8%d0%bd%d0%be%d1%81%d1%82%d1%80%d0%b0%d0%bd%d0%bd%d1%8b%d1%85-%d0%b7%d0%b0%d0%ba%d0%bb%d1%8e%d1%87%d1%91%d0%bd%d0%bd%d1%8b%d1%85-%d0%b2/?lang=ru',
		'/профессиональные-юридические-консул' => 'https://cindemirlaw.com/%d0%bf%d1%80%d0%be%d1%84%d0%b5%d1%81%d1%81%d0%b8%d0%be%d0%bd%d0%b0%d0%bb%d1%8c%d0%bd%d1%8b%d0%b5-%d1%8e%d1%80%d0%b8%d0%b4%d0%b8%d1%87%d0%b5%d1%81%d0%ba%d0%b8%d0%b5-%d0%ba%d0%be%d0%bd%d1%81%d1%83%d0%bb/?lang=ru',
		'/руководство-по-приобретению-иностра' => 'https://cindemirlaw.com/%d1%80%d1%83%d0%ba%d0%be%d0%b2%d0%be%d0%b4%d1%81%d1%82%d0%b2%d0%be-%d0%bf%d0%be-%d0%bf%d1%80%d0%b8%d0%be%d0%b1%d1%80%d0%b5%d1%82%d0%b5%d0%bd%d0%b8%d1%8e-%d0%b8%d0%bd%d0%be%d1%81%d1%82%d1%80%d0%b0/?lang=ru',
		'/русскоязычный-юрист-в-турции-юридич' => 'https://cindemirlaw.com/%d1%80%d1%83%d1%81%d1%81%d0%ba%d0%be%d1%8f%d0%b7%d1%8b%d1%87%d0%bd%d1%8b%d0%b9-%d1%8e%d1%80%d0%b8%d1%81%d1%82-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8-%d1%8e%d1%80%d0%b8%d0%b4%d0%b8%d1%87/?lang=ru',
		'/gokhan-cindemir-attorney-at-law-2-2' => 'https://cindemirlaw.com/gokhan-cindemir-attorney-at-law-2-2/?lang=zh-hans',
		'/как-получить-судимость-в-турции' => 'https://cindemirlaw.com/%d0%ba%d0%b0%d0%ba-%d0%bf%d0%be%d0%bb%d1%83%d1%87%d0%b8%d1%82%d1%8c-%d1%81%d1%83%d0%b4%d0%b8%d0%bc%d0%be%d1%81%d1%82%d1%8c-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/наследственное-право-турции-2' => 'https://cindemirlaw.com/%d0%bd%d0%b0%d1%81%d0%bb%d0%b5%d0%b4%d1%81%d1%82%d0%b2%d0%b5%d0%bd%d0%bd%d0%be%d0%b5-%d0%bf%d1%80%d0%b0%d0%b2%d0%be-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8-2/?lang=ru',
		'/как-открыть-филиал-в-турции' => 'https://cindemirlaw.com/%d0%ba%d0%b0%d0%ba-%d0%be%d1%82%d0%ba%d1%80%d1%8b%d1%82%d1%8c-%d1%84%d0%b8%d0%bb%d0%b8%d0%b0%d0%bb-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/наследственное-право-турции' => 'https://cindemirlaw.com/%d0%bd%d0%b0%d1%81%d0%bb%d0%b5%d0%b4%d1%81%d1%82%d0%b2%d0%b5%d0%bd%d0%bd%d0%be%d0%b5-%d0%bf%d1%80%d0%b0%d0%b2%d0%be-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/статус-условного-беженца' => 'https://cindemirlaw.com/%d1%81%d1%82%d0%b0%d1%82%d1%83%d1%81-%d1%83%d1%81%d0%bb%d0%be%d0%b2%d0%bd%d0%be%d0%b3%d0%be-%d0%b1%d0%b5%d0%b6%d0%b5%d0%bd%d1%86%d0%b0/?lang=ru',
		'/как-развестись-в-турции' => 'https://cindemirlaw.com/%d0%ba%d0%b0%d0%ba-%d1%80%d0%b0%d0%b7%d0%b2%d0%b5%d1%81%d1%82%d0%b8%d1%81%d1%8c-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/статус-беженца-в-турции' => 'https://cindemirlaw.com/%d1%81%d1%82%d0%b0%d1%82%d1%83%d1%81-%d0%b1%d0%b5%d0%b6%d0%b5%d0%bd%d1%86%d0%b0-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/виды-компании-в-турции' => 'https://cindemirlaw.com/%d0%b2%d0%b8%d0%b4%d1%8b-%d0%ba%d0%be%d0%bc%d0%bf%d0%b0%d0%bd%d0%b8%d0%b8-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/типы-компаний-в-турции' => 'https://cindemirlaw.com/%d1%82%d0%b8%d0%bf%d1%8b-%d0%ba%d0%be%d0%bc%d0%bf%d0%b0%d0%bd%d0%b8%d0%b9-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/права-и-обязанности' => 'https://cindemirlaw.com/%d0%bf%d1%80%d0%b0%d0%b2%d0%b0-%d0%b8-%d0%be%d0%b1%d1%8f%d0%b7%d0%b0%d0%bd%d0%bd%d0%be%d1%81%d1%82%d0%b8/?lang=ru',
		'/вторичная-защита' => 'https://cindemirlaw.com/%d0%b2%d1%82%d0%be%d1%80%d0%b8%d1%87%d0%bd%d0%b0%d1%8f-%d0%b7%d0%b0%d1%89%d0%b8%d1%82%d0%b0/?lang=ru',
		'/cindemir-hukuk' => 'https://cindemirlaw.com/cindemir-hukuk/?lang=zh-hans',
		'/cindemir-law-2' => 'https://cindemirlaw.com/cindemir-law-2/?lang=ru',
		'/author/admin' => 'https://cindemirlaw.com/',
		'/fde1068e3' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e' => 'https://cindemirlaw.com/fde1068e/?lang=ru',
		'/link11' => 'https://cindemirlaw.com/link11/?lang=zh-hans',
		'/link13' => 'https://cindemirlaw.com/link13/?lang=zh-hans',
		'/link15' => 'https://cindemirlaw.com/link15/?lang=zh-hans',
		'/link25' => 'https://cindemirlaw.com/link25/?lang=zh-hans',
		'/link9' => 'https://cindemirlaw.com/press/',
		'/link2' => 'https://cindemirlaw.com/about-us/',
		'/link3' => 'https://cindemirlaw.com/support/',
		'/link4' => 'https://cindemirlaw.com/services/',
		'/hakan' => 'https://cindemirlaw.com/hakan/?lang=zh-hans',
		'/contacts-2' => 'https://cindemirlaw.com/contacts/?lang=zh-hans',
		'/fde1' => 'https://cindemirlaw.com/fde1/?lang=ru',
	);

	private static $url_replace = array(
		'http://cindemir.av.tr/wp-content/uploads/2020/01/health-image-300x200.jpg' => 'https://cindemir.av.tr/wp-content/uploads/2020/01/health-image-300x200.jpg',
		'https://cindemir.av.tr/en/we-are-in-news/' => 'https://cindemirlaw.com/press/',
		'https://cindemir.av.tr/en/we-are-in-news' => 'https://cindemirlaw.com/press/',
		'https://cindemir.av.tr/ru/support-ru/' => 'https://cindemirlaw.com/press/?lang=ru',
		'https://cindemir.av.tr/ru/support-ru' => 'https://cindemirlaw.com/press/?lang=ru',
		'https://cindemir.av.tr/zh/support-zn/' => 'https://cindemirlaw.com/press/?lang=zh-hans',
		'https://cindemir.av.tr/zh/support-zn' => 'https://cindemirlaw.com/press/?lang=zh-hans',
		'https://cindemir.av.tr/basinda-biz/' => 'https://cindemirlaw.com/press/',
		'https://cindemir.av.tr/basinda-biz' => 'https://cindemirlaw.com/press/',
		'https://mersis.gtb.gov.tr/' => 'https://mersis.ticaret.gov.tr/',
		'https://mersis.gtb.gov.tr' => 'https://mersis.ticaret.gov.tr/',
		'https://turkodeme.com.tr/Tahsilat/Default.aspx?k=697795e3-b10e-4cbb-8251-e0c7a1b8ce76' => 'https://pos.param.com.tr/Tahsilat/Default.aspx?k=697795e3-b10e-4cbb-8251-e0c7a1b8ce76',
		'https://www.istanbulbarosu.org.tr/AttorneySearch.aspx' => 'https://istanbulbarosu.org.tr/AttorneySearch.aspx',
		'https://www.cindemirlaw.com/' => 'https://cindemirlaw.com/',
		'https://www.cindemirlaw.com' => 'https://cindemirlaw.com/',
		// Legacy multisite image paths (404) → current media library.
		'https://cindemirlaw.com/russian/wp-content/uploads/2014/11/white-2-copy-150x150.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'https://cindemirlaw.com/russian/wp-content/uploads/2014/11/white-5-copy-150x150.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-5-copy-300x300.jpg',
		'https://cindemirlaw.com/chinese/wp-content/uploads/2014/11/white-1-copy-150x150.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'https://cindemirlaw.com/chinese/wp-content/uploads/2014/11/white-2-copy-150x150.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'https://cindemirlaw.com/chinese/wp-content/uploads/2014/11/white-1-copy.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'https://cindemirlaw.com/chinese/wp-content/uploads/2014/11/white-2-copy.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'https://cindemirlaw.com/russian/wp-content/uploads/2014/11/white-1-copy.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'https://cindemirlaw.com/russian/wp-content/uploads/2014/11/white-2-copy.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'/russian/wp-content/uploads/2014/11/white-2-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'/russian/wp-content/uploads/2014/11/white-5-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-5-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-1-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-2-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-1-copy.jpg' => '/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-2-copy.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'/russian/wp-content/uploads/2014/11/white-1-copy.jpg' => '/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'/russian/wp-content/uploads/2014/11/white-2-copy.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
	);

	const VERSION = '1.9.10';

	const HEADER_LOGO = 'https://cindemirlaw.com/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg';

	/** Slug → neutral meta (110–160 chars, TBB-compliant). */
	private static $slug_metadesc = array(
		'services' => 'Information on legal service areas under Turkish law for foreign individuals and companies: civil, commercial, migration, and criminal law topics.',
	);

	private static $missing_h1 = array(
		3874 => 'Family Heritage',
		3884 => 'Who is Hafız Hüseyin Hüsnü Efendi?',
		51 => 'News & Events',
		43 => 'Our Videos',
		378 => 'Appointment',
		4665 => 'Embed List',
		2 => 'О нас',
		105 => 'Статьи',
		2427 => 'Наша команда',
		2446 => 'Контакты',
		103 => 'Поддержка',
		56 => 'Услуги',
		900030 => 'Assistant',
	);

	private static $alt_map = array(
		'white-1-copy' => 'Cindemir Law Office',
		'white-2-copy' => 'Cindemir Law Office',
		'white-5-copy' => 'Cindemir Law Office',
		'white3-copy' => 'Cindemir Law Office',
		'footlaw_banner' => 'Cindemir Law Office legal services banner',
		'540664430' => 'Istanbul skyline representing Cindemir Law Office',
		'Gokhan_Cindemir_AttorneyAtLaw' => 'Gökhan Cindemir, Attorney at Law',
		'Hakan_Cindemir_AttorneyatLaw' => 'Dr. Hakan Cindemir, Attorney at Law',
		'2e20a321-6694-44e0-ae3e' => 'Legal scales and gavel artwork',
	);

	private static $h1_done = false;

	/** Neutral Yoast meta descriptions (TBB-compliant, 110–160 chars). */
	private static $page_metadesc = array(
		43   => "Cindemir Law Office'in Türk hukuku ve yabancılara yönelik hukuki konular hakkında hazırladığı video içeriklerinin derlendiği sayfa.",
		2    => 'Cindemir Law Office — независимая юридическая фирма в Стамбуле, работающая с 2004 года в сфере турецкого и международного права.',
		105  => 'Статьи о турецком праве: гражданское, коммерческое, миграционное и уголовное право Турции для иностранных граждан и компаний.',
		3884 => "Hafız Hüseyin Hüsnü Efendi'nin biyografisi: 1847'de Batum'da doğan bu ismin hayatı, ilmî kişiliği ve tarihsel arka planı ele alınır.",
		16   => "Cindemir Law Office, 2004'ten bu yana İstanbul'da faaliyet gösteren, Türk ve uluslararası hukuk alanında çalışan bağımsız bir hukuk bürosudur.",
		2427 => 'Команда Cindemir Law Office: адвокаты и консультанты, работающие в области турецкого и международного права в Стамбуле.',
		392  => "Cindemir Law Office'in müvekkillerle iletişimi ve Türkiye'deki hukuki süreçlerde yabancılara sağladığı destek hakkında bilgi.",
		51   => "Cindemir Law Office'ten haberler ve etkinlikler: yabancı birey ve şirketleri ilgilendiren Türk hukukundaki gelişmelere dair güncellemeler.",
		19   => 'Cindemir Law Office ekibi: İstanbul\'da Türk ve uluslararası hukuk alanında çalışan avukatlar ve danışmanlar hakkında bilgi.',
		103  => 'О порядке общения адвоката с подзащитным в Турции: обмен информацией, права и обязанности сторон в уголовном процессе.',
		17   => 'Türk hukukuna dair makaleler: yabancı birey ve şirketleri ilgilendiren medeni, ticari, göç ve ceza hukuku konuları ele alınır.',
		390  => "Cindemir Law Office'in gizlilik politikası: web sitesi ziyaretçilerine ait kişisel verilerin nasıl toplandığı, kullanıldığı ve korunduğu açıklanır.",
		56   => 'Юридические услуги в Турции: корпоративное, миграционное, семейное и уголовное право для иностранных клиентов в Стамбуле.',
		3874 => "Cindemir Law Office'in tarihçesi: Osmanlı mahkemelerinden günümüze uzanan hukuki geçmişi İstanbul üzerinden anlatılır.",
	);

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'maybe_self_upgrade_from_github' ), 0 );
		add_action( 'init', array( __CLASS__, 'maybe_upgrade_sibling_plugins' ), 0 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_deploy_routes' ) );
		add_action( 'init', array( __CLASS__, 'maybe_purge_caches_on_upgrade' ), 1 );
		add_action( 'init', array( __CLASS__, 'ensure_local_badge_assets' ), 2 );
		add_action( 'init', array( __CLASS__, 'strip_yoast_press_redirects' ), 3 );
		add_action( 'init', array( __CLASS__, 'ensure_wpml_query_lang_mode' ), 4 );
		add_action( 'send_headers', array( __CLASS__, 'persist_lang_cookie' ), 0 );
		add_action( 'wpml_loaded', array( __CLASS__, 'switch_wpml_from_cookie' ), 0 );
		add_action( 'plugins_loaded', array( __CLASS__, 'switch_wpml_from_cookie' ), 20 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_cookie_lang_to_query' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'clear_lang_cookie_redirect' ), 0 );
		add_filter( 'option_polylang', array( __CLASS__, 'filter_polylang_options' ) );
		add_filter( 'redirection_url_target', array( __CLASS__, 'cancel_broken' ), 1, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'flatten_redirects' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'strip_default_lang_redirect' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'disable_author_archives' ), 0 );
		// Start FIRST so our rewrite is the outermost buffer (runs after WPML Absolute Links).
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), -999 );
		add_filter( 'the_content', array( __CLASS__, 'fix_headings' ), 12 );
		add_filter( 'the_content', array( __CLASS__, 'rewrite_content_hrefs' ), 25 );
		add_filter( 'the_content', array( __CLASS__, 'rewrite_legacy_media_in_content' ), 15 );
		add_action( 'wp_footer', array( __CLASS__, 'orphan_links' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'version_marker' ), 99 );
		add_action( 'wp_head', array( __CLASS__, 'header_brand_styles' ), 50 );
		add_action( 'wp_head', array( __CLASS__, 'language_switcher_boot_script' ), 0 );
		// Early head script with data-nowprocket so Delay JS cannot defer lang stamping.
		add_action( 'wp_head', array( __CLASS__, 'header_brand_script' ), 2 );
		add_action( 'wp_head', array( __CLASS__, 'noindex_utility' ), 1 );
		add_filter( 'wp_robots', array( __CLASS__, 'filter_wp_robots' ), 99 );
		add_filter( 'wpseo_robots', array( __CLASS__, 'filter_yoast_robots' ), 99 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'fix_alt_attr' ), 10, 2 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'fix_attachment_src_attrs' ), 11, 2 );
		add_filter( 'the_content', array( __CLASS__, 'fix_empty_alts' ), 20 );
		add_filter( 'author_link', array( __CLASS__, 'author_to_home' ), 20 );
		add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'nav_href' ), 20, 2 );
		add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'nav_href' ), 999, 2 );
		add_filter( 'wp_nav_menu', array( __CLASS__, 'stamp_lang_on_menu_html' ), 999 );
		add_filter( 'page_link', array( __CLASS__, 'filter_front_permalink' ), 99 );
		add_filter( 'post_link', array( __CLASS__, 'filter_front_permalink' ), 99 );
		add_filter( 'post_type_link', array( __CLASS__, 'filter_front_permalink' ), 99 );
		add_filter( 'term_link', array( __CLASS__, 'filter_front_permalink' ), 99 );
		add_filter( 'attachment_link', array( __CLASS__, 'filter_front_permalink' ), 99 );
		add_filter( 'year_link', array( __CLASS__, 'filter_front_permalink' ), 99 );
		add_filter( 'month_link', array( __CLASS__, 'filter_front_permalink' ), 99 );
		add_filter( 'day_link', array( __CLASS__, 'filter_front_permalink' ), 99 );
		add_filter( 'wpml_setting', array( __CLASS__, 'filter_wpml_setting' ), 1, 2 );
		add_filter( 'wpml_active_languages', array( __CLASS__, 'fix_active_language_urls' ), 99 );
		add_filter( 'icl_ls_languages', array( __CLASS__, 'fix_active_language_urls' ), 99 );
		add_filter( 'rocket_exclude_defer_js', array( __CLASS__, 'exclude_brand_js' ) );
		add_filter( 'rocket_delay_js_exclusions', array( __CLASS__, 'exclude_brand_js' ) );
		add_filter( 'rocket_exclude_js', array( __CLASS__, 'exclude_brand_js' ) );
		add_filter( 'rocket_excluded_inline_js_content', array( __CLASS__, 'exclude_brand_inline_js' ) );
		add_filter( 'rocket_cache_dynamic_cookies', array( __CLASS__, 'rocket_dynamic_lang_cookie' ) );
		add_filter( 'debloat_delay_js_exclusions', array( __CLASS__, 'exclude_brand_js' ) );
		add_filter( 'author_rewrite_rules', array( __CLASS__, 'kill_author_rewrites' ) );
		add_filter( 'wpseo_sitemap_entry', array( __CLASS__, 'filter_sitemap_entry' ), 10, 3 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'filter_page_metadesc' ), 20 );
		add_filter( 'wpseo_canonical', array( __CLASS__, 'filter_canonical_url' ), 20 );
		add_filter( 'get_canonical_url', array( __CLASS__, 'filter_canonical_url' ), 20 );
		add_filter( 'wpml_hreflangs', array( __CLASS__, 'filter_hreflang_urls' ), 99 );
		add_filter( 'wpseo_hreflang_links', array( __CLASS__, 'filter_hreflang_urls' ), 99 );
		add_filter( 'wpseo_opengraph_image', array( __CLASS__, 'rewrite_media_url' ) );
		add_filter( 'wpseo_twitter_image', array( __CLASS__, 'rewrite_media_url' ) );
	}

	/** Download TBB badge locally (Ahrefs bots get 403 from d.barobirlik.org.tr). */
	public static function ensure_local_badge_assets() {
		$local = get_option( 'cindemir_tbb_badge_local', '' );
		if ( $local && false !== strpos( $local, '/uploads/cindemir/' ) ) {
			return;
		}
		$dir = WP_CONTENT_DIR . '/uploads/cindemir';
		if ( ! wp_mkdir_p( $dir ) ) {
			return;
		}
		$file = trailingslashit( $dir ) . 'tbb_amblem_60.png';
		if ( ! file_exists( $file ) || filesize( $file ) < 100 ) {
			$response = wp_remote_get(
				'https://d.barobirlik.org.tr/amblem/tbb_amblem_60.png',
				array(
					'timeout' => 30,
					'headers' => array(
						'User-Agent' => 'Mozilla/5.0 (compatible; CindemirSEO/1.8)',
					),
				)
			);
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return;
			}
			$body = wp_remote_retrieve_body( $response );
			if ( strlen( $body ) < 100 ) {
				return;
			}
			file_put_contents( $file, $body );
		}
		if ( file_exists( $file ) && filesize( $file ) > 100 ) {
			update_option( 'cindemir_tbb_badge_local', content_url( 'uploads/cindemir/tbb_amblem_60.png' ), false );
		}
	}

	public static function maybe_purge_caches_on_upgrade() {
		$key  = 'cindemir_seo_fixes_version';
		$prev = get_option( $key, '' );
		if ( self::VERSION === $prev ) {
			return;
		}
		update_option( $key, self::VERSION, false );
		self::strip_yoast_press_redirects();
		self::ensure_wpml_query_lang_mode_force();
		self::ensure_rocket_lang_cookie_setting();
		flush_rewrite_rules( false );
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
			WPSEO_Sitemaps_Cache::clear();
		}
		delete_transient( 'wpseo_sitemap_cache_validator_page' );
		if ( function_exists( 'rocket_generate_config_file' ) ) {
			rocket_generate_config_file();
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
			LiteSpeed_Cache_API::purge_all();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
	}

	/** Pull newer mu-plugins from GitHub when this install lags behind. */
	public static function maybe_self_upgrade_from_github() {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return;
		}
		if ( get_transient( 'cindemir_seo_self_upgrade_lock' ) ) {
			return;
		}
		set_transient( 'cindemir_seo_self_upgrade_lock', 1, 15 * MINUTE_IN_SECONDS );

		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$url    = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/cindemir-seo-fixes.php';
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 45,
				'headers' => array( 'User-Agent' => 'CindemirSEOUpgrade/' . self::VERSION ),
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return;
		}
		$body = (string) wp_remote_retrieve_body( $response );
		if ( strlen( $body ) < 40000 || false === strpos( $body, 'Cindemir_SEO_Fixes' ) ) {
			return;
		}
		if ( ! preg_match( "/const\s+VERSION\s*=\s*'([^']+)'/", $body, $m ) ) {
			return;
		}
		if ( version_compare( $m[1], self::VERSION, '<=' ) ) {
			return;
		}
		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-seo-fixes.php';
		if ( false === file_put_contents( $dest, $body ) ) {
			return;
		}
		delete_option( 'cindemir_seo_fixes_version' );
		delete_transient( 'cindemir_seo_self_upgrade_lock' );
	}

	/** After seo-fixes updates, pull contact-fixes + helpers from GitHub. */
	public static function maybe_upgrade_sibling_plugins() {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) || get_transient( 'cindemir_sibling_upgrade_lock' ) ) {
			return;
		}
		set_transient( 'cindemir_sibling_upgrade_lock', 1, 15 * MINUTE_IN_SECONDS );

		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$base   = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/';
		$files  = array(
			'cindemir-contact-fixes.php'     => array( 'min' => 20000, 'ver' => '1.2.1' ),
			'cindemir-expose-yoast-meta.php' => array( 'min' => 2000, 'ver' => '1.2' ),
			'cindemir-purge-cache.php'       => array( 'min' => 500, 'ver' => '1.0' ),
		);

		foreach ( $files as $name => $spec ) {
			$dest = trailingslashit( WPMU_PLUGIN_DIR ) . $name;
			$local_ver = '';
			if ( file_exists( $dest ) && filesize( $dest ) > $spec['min'] ) {
				$local = file_get_contents( $dest );
				if ( is_string( $local ) && preg_match( '/\bVersion:\s*([0-9.]+)/', $local, $m ) ) {
					$local_ver = $m[1];
				}
				if ( $local_ver && version_compare( $local_ver, $spec['ver'], '>=' ) ) {
					continue;
				}
			}
			$response = wp_remote_get(
				$base . $name,
				array(
					'timeout' => 45,
					'headers' => array( 'User-Agent' => 'CindemirSiblingUpgrade/' . self::VERSION ),
				)
			);
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}
			$body = (string) wp_remote_retrieve_body( $response );
			if ( strlen( $body ) < $spec['min'] ) {
				continue;
			}
			file_put_contents( $dest, $body );
		}
		flush_rewrite_rules( false );
	}

	/** Fallback deploy routes when contact-fixes on server is outdated. */
	public static function register_deploy_routes() {
		register_rest_route(
			'cindemir/v1',
			'/pull-plugins',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'rest_pull_plugins' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function rest_pull_plugins( $request ) {
		$key = $request->get_param( 'key' );
		if ( 'seo-pack-2026' !== $key ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return new WP_REST_Response( array( 'error' => 'no mu dir' ), 500 );
		}
		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$base   = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/';
		$files  = array(
			'cindemir-seo-fixes.php'         => 40000,
			'cindemir-contact-fixes.php'     => 20000,
			'cindemir-expose-yoast-meta.php' => 2000,
			'cindemir-purge-cache.php'       => 500,
		);
		$out = array();
		foreach ( $files as $name => $min ) {
			$response = wp_remote_get(
				$base . $name,
				array(
					'timeout' => 60,
					'headers' => array( 'User-Agent' => 'CindemirPull/' . self::VERSION ),
				)
			);
			if ( is_wp_error( $response ) ) {
				$out[ $name ] = array( 'ok' => false, 'error' => $response->get_error_message() );
				continue;
			}
			$body  = (string) wp_remote_retrieve_body( $response );
			$bytes = strlen( $body );
			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || $bytes < $min ) {
				$out[ $name ] = array( 'ok' => false, 'bytes' => $bytes );
				continue;
			}
			file_put_contents( trailingslashit( WPMU_PLUGIN_DIR ) . $name, $body );
			$out[ $name ] = array( 'ok' => true, 'bytes' => $bytes );
		}
		delete_option( 'cindemir_seo_fixes_version' );
		wp_cache_flush();
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		return new WP_REST_Response( array( 'ok' => true, 'version' => self::VERSION, 'files' => $out ), 200 );
	}

	public static function filter_canonical_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}
		$url = str_replace( '/contacts-2/', '/contacts/', $url );
		$url = str_replace( '/contacts-2?', '/contacts?', $url );
		return $url;
	}

	public static function filter_hreflang_urls( $hreflangs ) {
		if ( ! is_array( $hreflangs ) ) {
			return $hreflangs;
		}
		foreach ( $hreflangs as $lang => $url ) {
			$hreflangs[ $lang ] = self::normalize_hreflang_url( $url, is_string( $lang ) ? $lang : null );
		}
		return $hreflangs;
	}

	private static function normalize_hreflang_url( $url, $lang = null ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}
		$url = str_replace( '/contacts-2/', '/contacts/', $url );
		$url = str_replace( '/contacts-2?', '/contacts?', $url );
		$parts = wp_parse_url( $url );
		$path  = isset( $parts['path'] ) ? $parts['path'] : '/';
		$q     = array();
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $q );
		}
		// Drop ?lang=en from default English URLs.
		if ( ! empty( $q['lang'] ) && in_array( $q['lang'], array( 'en', 'en-us', 'en_us' ), true ) ) {
			unset( $q['lang'] );
		}
		// Ensure non-English hreflang URLs keep a lang parameter (query-string WPML mode).
		$code = is_string( $lang ) ? $lang : '';
		if ( 'zh' === $code ) {
			$code = 'zh-hans';
		}
		if ( $code && ! in_array( $code, array( 'en', 'x-default' ), true ) && empty( $q['lang'] ) ) {
			$q['lang'] = $code;
		}
		$new = home_url( user_trailingslashit( $path ) );
		if ( ! empty( $q ) ) {
			$new = add_query_arg( $q, $new );
		}
		return $new;
	}

	/** Polylang: hide language param for default language URLs. */
	public static function filter_polylang_options( $options ) {
		if ( ! is_array( $options ) ) {
			return $options;
		}
		$options['hide_default'] = 1;
		$options['default_lang'] = isset( $options['default_lang'] ) ? $options['default_lang'] : 'en';
		return $options;
	}

	/** WPML: keep query-string language URLs (?lang=ru / ?lang=zh-hans). */
	public static function filter_wpml_setting( $value, $key ) {
		if ( 'language_negotiation_type' === $key ) {
			return '3';
		}
		return $value;
	}

	/**
	 * Ensure WPML/Avia language switcher URLs use correct ?lang= targets.
	 *
	 * @param array $langs Active languages list from WPML.
	 * @return array
	 */
	public static function fix_active_language_urls( $langs ) {
		if ( ! is_array( $langs ) || ! $langs ) {
			return $langs;
		}
		$path = self::path();
		$path = ( ! $path || '/' === $path ) ? '/' : user_trailingslashit( $path );
		$base = 'https://cindemirlaw.com' . ( '/' === $path ? '/' : $path );
		foreach ( $langs as $code => $info ) {
			if ( ! is_array( $info ) ) {
				continue;
			}
			$code = strtolower( (string) $code );
			$url  = self::language_target_url( $code, $base );
			$langs[ $code ]['url'] = $url;
			if ( isset( $langs[ $code ]['url'] ) ) {
				$langs[ $code ]['url'] = $url;
			}
		}
		return $langs;
	}

	/**
	 * Build absolute front URL for a language code on the current path.
	 */
	private static function language_target_url( $code, $base = null ) {
		$code = strtolower( (string) $code );
		if ( null === $base ) {
			$path = self::path();
			$path = ( ! $path || '/' === $path ) ? '/' : user_trailingslashit( $path );
			$base = 'https://cindemirlaw.com' . ( '/' === $path ? '/' : $path );
		}
		$base = preg_replace( '/([?&])lang=[^&]*/', '$1', $base );
		$base = preg_replace( '/([?&])cindemir_lang=[^&]*/', '$1', $base );
		$base = rtrim( $base, '?&' );
		if ( in_array( $code, array( 'en', 'en-us', 'en_us' ), true ) ) {
			// Leaving RU/ZH: bare "/" would be bounce-redirected by the sticky
			// language cookie. Force a clear-lang hop that does not need JS.
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
			'tr'      => 'tr',
		);
		if ( ! isset( $map[ $code ] ) ) {
			return $base;
		}
		return self::raw_append_lang( $base, $map[ $code ] );
	}

	/**
	 * Rewrite Avia/WPML language switcher <a href> to the correct ?lang= target.
	 * Must run AFTER stamp_lang_on_internal_hrefs so the current language is not
	 * incorrectly copied onto other-language flags.
	 */
	private static function fix_language_switcher_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( false === strpos( $html, 'language_' ) && false === strpos( $html, 'wpml-ls-' ) && false === strpos( $html, 'avia_wpml_language_switch' ) ) {
			return $html;
		}
		$path = self::path();
		$path = ( ! $path || '/' === $path ) ? '/' : user_trailingslashit( $path );
		$base = 'https://cindemirlaw.com' . ( '/' === $path ? '/' : $path );
		$en   = $base;
		$ru   = self::raw_append_lang( $base, 'ru' );
		$zh   = self::raw_append_lang( $base, 'zh-hans' );

		// Operate on each language-switcher <ul> block so we never miss quote variants.
		$out = preg_replace_callback(
			'#(<ul\b[^>]*\b(?:avia_wpml_language_switch|wpml-ls)[^>]*>)(.*?)(</ul>)#is',
			function ( $um ) use ( $en, $ru, $zh ) {
				$inner = $um[2];
				$inner = preg_replace_callback(
					'#(<li\b[^>]*\blanguage_([a-z0-9\-]+)[^>]*>\s*<a\b[^>]*\bhref=)(["\'])([^"\']*)\3#i',
					function ( $m ) use ( $en, $ru, $zh ) {
						$code = strtolower( $m[2] );
						$url  = $en;
						if ( 'ru' === $code ) {
							$url = $ru;
						} elseif ( 'zh-hans' === $code || 'zh' === $code ) {
							$url = $zh;
						} elseif ( 'tr' === $code ) {
							$url = self::raw_append_lang( $en, 'tr' );
						}
						return $m[1] . $m[3] . esc_attr( $url ) . $m[3];
					},
					$inner
				);
				$inner = preg_replace_callback(
					'#(<li\b[^>]*\bwpml-ls-item-([a-z0-9\-]+)[^>]*>\s*<a\b[^>]*\bhref=)(["\'])([^"\']*)\3#i',
					function ( $m ) use ( $en, $ru, $zh ) {
						$code = strtolower( $m[2] );
						$url  = $en;
						if ( 'ru' === $code ) {
							$url = $ru;
						} elseif ( 'zh-hans' === $code || 'zh' === $code ) {
							$url = $zh;
						}
						return $m[1] . $m[3] . esc_attr( $url ) . $m[3];
					},
					$inner
				);
				return $um[1] . $inner . $um[3];
			},
			$html
		);
		if ( null === $out ) {
			return $html;
		}
		if ( false === strpos( $out, 'cindemir-swfix' ) ) {
			$out = preg_replace(
				'/<head\b[^>]*>/i',
				'$0<!--cindemir-swfix:en=' . esc_attr( $en ) . ';ru=' . esc_attr( $ru ) . ';zh=' . esc_attr( $zh ) . '-->',
				$out,
				1
			);
		}
		return $out;
	}

	/** Persist WPML query-string mode in icl_sitepress_settings if drifted. */
	public static function ensure_wpml_query_lang_mode() {
		if ( ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			// Front requests: filter is enough; avoid option writes on every hit.
			return;
		}
		self::ensure_wpml_query_lang_mode_force();
	}

	/** Write language_negotiation_type=3 into WPML settings. */
	private static function ensure_wpml_query_lang_mode_force() {
		$settings = get_option( 'icl_sitepress_settings' );
		if ( ! is_array( $settings ) ) {
			return;
		}
		if ( isset( $settings['language_negotiation_type'] ) && (int) $settings['language_negotiation_type'] === 3 ) {
			return;
		}
		$settings['language_negotiation_type'] = 3;
		update_option( 'icl_sitepress_settings', $settings );
	}

	/** Remember active front language so bare permalinks keep serving it. */
	public static function persist_lang_cookie() {
		if ( is_admin() ) {
			return;
		}
		$lang = self::front_lang();
		if ( ! $lang ) {
			return;
		}
		$secure = is_ssl();
		if ( in_array( $lang, array( 'en', 'en-us', 'en_us' ), true ) ) {
			if ( ! empty( $_COOKIE['cindemir_lang'] ) ) {
				setcookie( 'cindemir_lang', '', time() - YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, true );
			}
			return;
		}
		if ( ! in_array( $lang, array( 'ru', 'zh-hans', 'zh', 'tr' ), true ) ) {
			return;
		}
		if ( ! empty( $_COOKIE['cindemir_lang'] ) && $_COOKIE['cindemir_lang'] === $lang ) {
			return;
		}
		setcookie( 'cindemir_lang', $lang, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, false );
		$_COOKIE['cindemir_lang'] = $lang;
	}

	/** Vary WP Rocket page cache by language cookie. */
	public static function rocket_dynamic_lang_cookie( $cookies ) {
		if ( ! is_array( $cookies ) ) {
			$cookies = array();
		}
		$cookies[] = 'cindemir_lang';
		return array_values( array_unique( $cookies ) );
	}

	/** Ask WPML to use the cookie/query language when it did not pick it up. */
	public static function switch_wpml_from_cookie() {
		static $done = false;
		if ( $done || is_admin() ) {
			return;
		}
		$lang = self::front_lang();
		if ( ! $lang || in_array( $lang, array( 'en', 'en-us', 'en_us' ), true ) ) {
			return;
		}
		$done = true;
		if ( has_action( 'wpml_switch_language' ) ) {
			do_action( 'wpml_switch_language', $lang );
		}
		if ( isset( $GLOBALS['sitepress'] ) && is_object( $GLOBALS['sitepress'] ) && method_exists( $GLOBALS['sitepress'], 'switch_lang' ) ) {
			$GLOBALS['sitepress']->switch_lang( $lang, true );
		}
	}

	/**
	 * When a language cookie is set but the browser URL lacks ?lang=, 302 to the
	 * same path with the query so WPML + caches see an explicit language.
	 */
	public static function redirect_cookie_lang_to_query() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		if ( defined( 'CINDEMIR_CLEAR_LANG' ) && CINDEMIR_CLEAR_LANG ) {
			return;
		}
		if ( ! defined( 'CINDEMIR_LANG_FROM_COOKIE' ) || ! CINDEMIR_LANG_FROM_COOKIE ) {
			return;
		}
		$lang = (string) CINDEMIR_LANG_FROM_COOKIE;
		if ( ! in_array( $lang, array( 'ru', 'zh-hans', 'zh', 'tr' ), true ) ) {
			return;
		}
		$path = self::path();
		$path = $path ? user_trailingslashit( $path ) : '/';
		$target = home_url( $path );
		$target = add_query_arg( 'lang', $lang, $target );
		wp_safe_redirect( $target, 302 );
		exit;
	}

	/** English flag: clear language cookie and bounce to a clean URL. */
	public static function clear_lang_cookie_redirect() {
		if ( is_admin() || ! defined( 'CINDEMIR_CLEAR_LANG' ) || ! CINDEMIR_CLEAR_LANG ) {
			return;
		}
		$secure = is_ssl();
		setcookie( 'cindemir_lang', '', time() - YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, false );
		unset( $_COOKIE['cindemir_lang'] );
		$path = self::path();
		$path = $path ? user_trailingslashit( $path ) : '/';
		wp_safe_redirect( home_url( $path ), 302 );
		exit;
	}

	/** Ensure Rocket settings list our language cookie (regenerate config). */
	private static function ensure_rocket_lang_cookie_setting() {
		$opt = get_option( 'wp_rocket_settings' );
		if ( ! is_array( $opt ) ) {
			return;
		}
		$changed = false;
		foreach ( array( 'cache_dynamic_cookies' ) as $key ) {
			if ( empty( $opt[ $key ] ) || ! is_array( $opt[ $key ] ) ) {
				$opt[ $key ] = array();
			}
			if ( ! in_array( 'cindemir_lang', $opt[ $key ], true ) ) {
				$opt[ $key ][] = 'cindemir_lang';
				$changed       = true;
			}
		}
		if ( $changed ) {
			update_option( 'wp_rocket_settings', $opt );
		}
		if ( function_exists( 'rocket_generate_config_file' ) ) {
			rocket_generate_config_file();
		}
	}

	/** Stamp lang onto rendered menu HTML (Enfold / WP walker). */
	public static function stamp_lang_on_menu_html( $html ) {
		return self::stamp_lang_on_internal_hrefs( $html );
	}

	/** Redirect ?lang=en away from canonical English URLs. */
	public static function strip_default_lang_redirect() {
		if ( is_admin() || empty( $_GET['lang'] ) ) {
			return;
		}
		$lang = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
		if ( ! in_array( $lang, array( 'en', 'en-us', 'en_us' ), true ) ) {
			return;
		}
		$path = self::path();
		$clean = home_url( user_trailingslashit( $path ) );
		wp_redirect( $clean, 301 );
		exit;
	}

	public static function version_marker() {
		echo "\n<!-- cindemir-seo-fixes " . esc_html( self::VERSION ) . " -->\n";
	}

	public static function rewrite_media_url( $url ) {
		return self::apply_url_replace( $url );
	}

	public static function rewrite_legacy_media_in_content( $content ) {
		return self::apply_url_replace( $content );
	}

	public static function fix_attachment_src_attrs( $attr, $attachment ) {
		foreach ( array( 'src', 'data-lazy-src', 'data-src' ) as $k ) {
			if ( ! empty( $attr[ $k ] ) ) {
				$attr[ $k ] = self::apply_url_replace( $attr[ $k ] );
			}
		}
		return $attr;
	}

	private static function apply_url_replace( $text ) {
		if ( ! is_string( $text ) || '' === $text ) {
			return $text;
		}
		$local_tbb = get_option( 'cindemir_tbb_badge_local', '' );
		if ( $local_tbb ) {
			$text = str_replace( 'https://d.barobirlik.org.tr/amblem/tbb_amblem_60.png', $local_tbb, $text );
			$text = str_replace( 'http://d.barobirlik.org.tr/amblem/tbb_amblem_60.png', $local_tbb, $text );
		}
		foreach ( self::$url_replace as $from => $to ) {
			$text = str_replace( $from, $to, $text );
		}
		return $text;
	}

	/** Override Yoast meta description for priority pages; pad short post/page metas. */
	public static function filter_page_metadesc( $desc ) {
		if ( ! is_singular( array( 'page', 'post' ) ) ) {
			return $desc;
		}
		$id   = get_queried_object_id();
		$post = get_post( $id );
		if ( ! $post ) {
			return $desc;
		}
		if ( isset( self::$page_metadesc[ $id ] ) ) {
			return self::$page_metadesc[ $id ];
		}
		$slug = $post->post_name;
		if ( isset( self::$slug_metadesc[ $slug ] ) ) {
			return self::$slug_metadesc[ $slug ];
		}
		return self::pad_short_metadesc( $desc, $post );
	}

	private static function pad_short_metadesc( $desc, $post ) {
		$desc = trim( (string) $desc );
		$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $desc ) : strlen( $desc );
		if ( $len >= 110 && $len <= 160 ) {
			return $desc;
		}
		if ( $len > 160 ) {
			$cut = function_exists( 'mb_substr' ) ? mb_substr( $desc, 0, 157 ) : substr( $desc, 0, 157 );
			return rtrim( $cut ) . '…';
		}
		$title = wp_strip_all_tags( get_the_title( $post ) );
		$title = trim( preg_replace( '/\s*[-|–—]\s*Cindemir.*$/u', '', $title ) );
		$base  = $desc ? $desc : $title;
		$suffix = ' Overview of relevant Turkish law topics, procedures, and legal context for foreign individuals and companies.';
		$out    = trim( $base . $suffix );
		$olen   = function_exists( 'mb_strlen' ) ? mb_strlen( $out ) : strlen( $out );
		if ( $olen > 160 ) {
			$out = function_exists( 'mb_substr' ) ? mb_substr( $out, 0, 157 ) : substr( $out, 0, 157 );
			$out = rtrim( $out ) . '…';
		}
		return $out;
	}

	public static function filter_sitemap_entry( $url, $type, $object ) {
		if ( ! is_array( $url ) || empty( $url['loc'] ) ) {
			return $url;
		}
		$loc = $url['loc'];
		$path = self::normalize_path( $loc );
		$skip = array( '/link9', '/link2', '/link3', '/link4', '/author/admin', '/russian', '/chinese', '/zh', '/zh-hans' );
		if ( in_array( $path, $skip, true ) ) {
			return false;
		}
		$parts = wp_parse_url( $loc );
		$query = isset( $parts['query'] ) ? $parts['query'] : '';
		if ( ! empty( $query ) && false !== strpos( $query, 'lang=en' ) ) {
			return false;
		}
		$dest = self::resolve_path_dest( $path, $query );
		if ( $dest ) {
			$url['loc'] = $dest;
		} elseif ( 'post' === $type && $query && false === strpos( $query, 'lang=' ) ) {
			$ru_dest = self::resolve_path_dest( $path, 'lang=ru' );
			if ( $ru_dest ) {
				$url['loc'] = $ru_dest;
			}
		}
		return $url;
	}

	public static function kill_author_rewrites( $rules ) {
		return array();
	}

	public static function disable_author_archives() {
		if ( is_author() ) {
			wp_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	public static function cancel_broken( $target, $url ) {
		$path = self::normalize_path( $url );
		if ( in_array( $path, self::$broken, true ) ) {
			return false;
		}
		$parts = wp_parse_url( $url );
		$q = isset( $parts['query'] ) ? $parts['query'] : '';
		$dest = self::resolve_path_dest( $path, $q );
		if ( $dest ) {
			return $dest;
		}
		return $target;
	}

	/**
	 * Yoast Premium was 301'ing /press/ → cindemir.av.tr (x-redirect-by: Yoast SEO Premium).
	 * Keep press local on law.com.
	 */
	public static function strip_yoast_press_redirects() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		$needles = array( '/press', 'we-are-in-news', '/link9', 'cindemir.av.tr/en/we-are-in-news' );
		$option_names = array(
			'wpseo-premium-redirects-base',
			'wpseo-premium-redirects-export-plain',
			'wpseo-premium-redirects-export-regex',
			'wpseo-premium-redirects-regex',
		);
		foreach ( $option_names as $opt ) {
			$redirects = get_option( $opt );
			if ( ! is_array( $redirects ) || ! $redirects ) {
				continue;
			}
			$changed = false;
			foreach ( $redirects as $key => $row ) {
				$origin = '';
				$target = '';
				if ( is_array( $row ) ) {
					$origin = isset( $row['origin'] ) ? (string) $row['origin'] : (string) $key;
					$target = isset( $row['url'] ) ? (string) $row['url'] : ( isset( $row['target'] ) ? (string) $row['target'] : '' );
				} elseif ( is_string( $row ) ) {
					$origin = (string) $key;
					$target = $row;
				} else {
					$origin = (string) $key;
				}
				$blob = strtolower( $origin . ' ' . $target . ' ' . (string) $key );
				foreach ( $needles as $n ) {
					if ( false !== strpos( $blob, strtolower( $n ) ) ) {
						unset( $redirects[ $key ] );
						$changed = true;
						break;
					}
				}
			}
			if ( $changed ) {
				update_option( $opt, $redirects, false );
			}
		}
	}

	public static function flatten_redirects() {
		if ( is_admin() ) {
			return;
		}
		$path = self::path();
		if ( in_array( $path, self::$broken, true ) ) {
			return;
		}
		$req  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$req_parts = wp_parse_url( $req );
		$req_q = isset( $req_parts['query'] ) ? $req_parts['query'] : '';
		$dest = self::resolve_path_dest( $path, $req_q );
		if ( ! $dest ) {
			return;
		}
		$dest_parts = wp_parse_url( $dest );
		$dest_path = isset( $dest_parts['path'] ) ? untrailingslashit( rawurldecode( $dest_parts['path'] ) ) : '';
		$dest_path = '' === $dest_path ? '/' : $dest_path;
		$dest_q = isset( $dest_parts['query'] ) ? $dest_parts['query'] : '';
		if ( $path === $dest_path && $dest_q && $req_q && false !== strpos( $req_q, $dest_q ) ) {
			return;
		}
		if ( $path === $dest_path && ! $dest_q ) {
			return;
		}
		wp_redirect( $dest, 301 );
		exit;
	}

	public static function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'rewrite_html' ) );
	}

	public static function rewrite_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$html = self::rewrite_hrefs_in_html( $html );
		$html = self::fix_header_logo_html( $html );
		$html = self::inject_header_brand_html( $html );
		$html = self::ensure_missing_h1_html( $html );
		$html = self::fill_empty_alts_html( $html );
		$html = self::strip_blocked_external_images( $html );
		$html = self::fix_hreflang_html( $html );
		$html = self::fix_canonical_html( $html );
		$html = self::shorten_title_tag( $html );
		$html = self::normalize_robots_meta( $html );
		// Final pass after other rewriters — keep menu/site links on active lang.
		$html = self::stamp_lang_on_internal_hrefs( $html );
		// Language switcher must point at TARGET language, not the current one.
		$html = self::fix_language_switcher_html( $html );
		return $html;
	}

	/**
	 * WPML RU/ZH were falling back to Enfold’s blank placeholder logo.
	 */
	private static function fix_header_logo_html( $html ) {
		$logo = esc_url( self::HEADER_LOGO );
		$html = preg_replace(
			'#https?://cindemirlaw\.com/wp-content/themes/enfold/images/layout/logo\.png#i',
			$logo,
			$html
		);
		$html = preg_replace(
			'#/wp-content/themes/enfold/images/layout/logo\.png#i',
			'/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg',
			$html
		);
		return $html;
	}

	/**
	 * Inject a left-side site brand that does not depend on Enfold’s logo_right positioning.
	 */
	private static function inject_header_brand_html( $html ) {
		if ( false !== strpos( $html, 'cindemir-site-brand' ) ) {
			return $html;
		}
		$label = esc_html( self::header_brand_label() );
		$logo  = esc_url( self::HEADER_LOGO );
		$home  = esc_url( home_url( '/' ) );
		$brand = '<a class="cindemir-site-brand" href="' . $home . '" aria-label="' . esc_attr( $label ) . '">'
			. '<img src="' . $logo . '" width="48" height="48" alt="' . esc_attr( $label ) . '" decoding="async" />'
			. '<span class="cindemir-site-brand__text">' . $label . '</span>'
			. '</a>';

		$patterns = array(
			'/(<div[^>]*class=["\'][^"\']*inner-container[^"\']*["\'][^>]*>)/i',
			'/(<(?:div|span)[^>]*class=["\'][^"\']*av-logo-container[^"\']*["\'][^>]*>)/i',
		);
		foreach ( $patterns as $pattern ) {
			$count = 0;
			$new   = preg_replace( $pattern, '$1' . $brand, $html, 1, $count );
			if ( null !== $new && $count > 0 ) {
				return $new;
			}
		}
		return $html;
	}

	private static function header_brand_label() {
		$lang = self::front_lang();
		$labels = array(
			'en'      => 'Cindemir Law Office',
			'tr'      => 'Cindemir Hukuk Bürosu',
			'ru'      => 'Юридическая фирма Cindemir',
			'zh-hans' => '辛德米尔律师事务所',
			'zh'      => '辛德米尔律师事务所',
		);
		return isset( $labels[ $lang ] ) ? $labels[ $lang ] : $labels['en'];
	}

	public static function header_brand_styles() {
		if ( is_admin() ) {
			return;
		}
		$label = esc_attr( self::header_brand_label() );
		// Theme "Additional CSS" contains `.logo{display:none !important}` which collapses
		// the mobile header (and hides the burger). Force logo/burger/header back.
		echo '<style id="cindemir-header-brand">'
			. '#top #header #header_main,'
			. '#top #header #header_main .container,'
			. '#top #header #header_main .inner-container{'
			. 'min-height:64px!important;height:auto!important}'
			. '#top #header .logo{'
			. 'display:flex!important;visibility:visible!important;opacity:1!important;'
			. 'position:relative!important;left:0!important;right:auto!important;float:none!important;'
			. 'z-index:50;align-items:center}'
			. '#top #header .logo a{'
			. 'display:inline-flex!important;align-items:center!important;gap:10px!important;'
			. 'text-decoration:none!important;max-height:none!important;height:auto!important}'
			. '#top #header .logo img,#top #header .logo picture{'
			. 'display:inline-block!important;max-height:44px!important;width:auto!important;'
			. 'height:auto!important;opacity:1!important;visibility:visible!important}'
			. '#top #header .logo a::after{'
			. 'content:"' . $label . '"!important;display:inline-block!important;'
			. 'font-family:Georgia,"Times New Roman",serif!important;font-size:18px!important;font-weight:700!important;'
			. 'line-height:1.15!important;color:#244f4f!important;white-space:nowrap;max-width:min(260px,58vw)}'
			. '#top #header .main_menu{display:block!important;visibility:visible!important;opacity:1!important}'
			. '#top #header .av-burger-menu-main{'
			. 'display:block!important;visibility:visible!important;opacity:1!important;'
			. 'min-width:44px!important;min-height:44px!important;line-height:44px!important}'
			. '#top #header .av-hamburger{display:inline-block!important;visibility:visible!important;'
			. 'min-width:28px!important;min-height:22px!important}'
			. '#top #header .cindemir-site-brand{'
			. 'display:inline-flex!important;align-items:center!important;gap:10px!important;'
			. 'text-decoration:none!important;z-index:60;margin-right:12px}'
			. '#top #header .cindemir-site-brand img{width:44px!important;height:44px!important;object-fit:contain}'
			. '#top #header .cindemir-site-brand__text{'
			. 'font-family:Georgia,"Times New Roman",serif!important;font-size:18px!important;font-weight:700!important;'
			. 'color:#244f4f!important;white-space:nowrap}'
			. '@media only screen and (min-width:990px){'
			. '#top #header #header_main .inner-container{'
			. 'display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:16px}'
			. '#top #header .logo a::after{font-size:20px!important;max-width:none}'
			. '#top #header .main_menu{'
			. 'position:relative!important;left:auto!important;right:auto!important;float:none!important;'
			. 'margin-left:auto!important;flex:1 1 auto;text-align:right!important}'
			. '}'
			. '@media only screen and (max-width:989px){'
			. '#top #header .logo a::after,#top #header .cindemir-site-brand__text{'
			. 'font-size:14px!important;white-space:normal!important;max-width:min(180px,48vw)}'
			. '#top #header .logo img{max-height:34px!important;max-width:34px!important}'
			. '}'
			. '</style>';
	}

	/**
	 * Tiny undelayable handler: language flags navigate to the correct ?lang=
	 * even when hrefs were stripped by an outer buffer / Delay JS.
	 */
	public static function language_switcher_boot_script() {
		if ( is_admin() ) {
			return;
		}
		echo '<script id="cindemir-lang-switch" data-nowprocket nowprocket data-no-minify="1" data-no-optimize="1">'
			. 'document.addEventListener("click",function(ev){'
			. 'var li=ev.target&&ev.target.closest&&ev.target.closest("li[class*=\\"language_\\"],li[class*=\\"wpml-ls-item-\\"]");'
			. 'if(!li)return;'
			. 'var m=(li.className||"").match(/language_([a-z0-9\\-]+)/i)||(li.className||"").match(/wpml-ls-item-([a-z0-9\\-]+)/i);'
			. 'if(!m)return;'
			. 'var map={en:"",ru:"ru","zh-hans":"zh-hans",zh:"zh-hans",tr:"tr"};'
			. 'var code=m[1].toLowerCase();'
			. 'if(!Object.prototype.hasOwnProperty.call(map,code))return;'
			. 'ev.preventDefault();ev.stopPropagation();'
			. 'try{if(map[code]){document.cookie="cindemir_lang="+map[code]+";path=/;max-age=31536000;SameSite=Lax;Secure";}'
			. 'else{document.cookie="cindemir_lang=;path=/;max-age=0;SameSite=Lax;Secure";document.cookie="cindemir_lang=;path=/;max-age=0;SameSite=Lax";}}catch(e){}'
			. 'var path=location.pathname||"/";'
			. 'location.href=map[code]?(path+((path.indexOf("?")>=0?"&":"?")+"lang="+map[code])):(path+((path.indexOf("?")>=0?"&":"?")+"cindemir_lang=en"));'
			. '},true);'
			. '</script>' . "\n";
	}

	/** Client-side sticky lang + brand inject. Must not be delayed by WP Rocket. */
	public static function header_brand_script() {
		if ( is_admin() ) {
			return;
		}
		static $printed = false;
		if ( $printed ) {
			return;
		}
		$printed = true;
		$label = esc_js( self::header_brand_label() );
		$logo  = esc_js( self::HEADER_LOGO );
		$home  = esc_js( self::with_front_lang( home_url( '/' ) ) );
		$lang  = esc_js( self::front_lang() );
		// data-nowprocket / nowprocket: skip Delay JS so stampLang runs before first click.
		echo '<script id="cindemir-header-brand-js" data-nowprocket nowprocket data-no-minify="1" data-no-optimize="1">'
			. '(function(){'
			. 'var lang="' . $lang . '";'
			. 'try{'
			. 'if(lang&&lang!=="en"&&lang!=="en-us"){document.cookie="cindemir_lang="+encodeURIComponent(lang)+";path=/;max-age=31536000;SameSite=Lax";}'
			. 'else{document.cookie="cindemir_lang=;path=/;max-age=0;SameSite=Lax";}'
			. '}catch(e){}'
			. 'function fixSwitcher(){'
			. 'var map={en:"",ru:"ru","zh-hans":"zh-hans",zh:"zh-hans",tr:"tr"};'
			. 'var nodes=document.querySelectorAll(".avia_wpml_language_switch li,.wpml-ls-item,li[class*=\\"language_\\"]");'
			. 'for(var i=0;i<nodes.length;i++){'
			. 'var li=nodes[i],cls=li.className||"";'
			. 'var m=cls.match(/language_([a-z0-9\\-]+)/i)||cls.match(/wpml-ls-item-([a-z0-9\\-]+)/i);'
			. 'if(!m)continue;'
			. 'var code=m[1].toLowerCase();'
			. 'if(!Object.prototype.hasOwnProperty.call(map,code))continue;'
			. 'var a=li.querySelector("a[href]");'
			. 'if(!a)continue;'
			. 'var path=location.pathname||"/";'
			. 'var href=path;'
			. 'if(map[code]){href=path+(path.indexOf("?")>=0?"&":"?")+"lang="+map[code];}'
			. 'a.setAttribute("href",href);'
			. '}'
			. '}'
			. 'function stampLang(){'
			. 'if(!lang||lang==="en"||lang==="en-us"||lang==="en_us")return;'
			. 'var links=document.querySelectorAll("a[href]");'
			. 'for(var i=0;i<links.length;i++){'
			. 'try{'
			. 'var a=links[i],raw=a.getAttribute("href");'
			. 'if(!raw)continue;'
			. 'if(a.closest&&a.closest(".avia_wpml_language_switch,.wpml-ls-item,.wpml-ls"))continue;'
			. 'var u=new URL(raw,location.origin);'
			. 'if(u.hostname!==location.hostname)continue;'
			. 'if(u.searchParams.get("lang"))continue;'
			. 'if(/\\/(wp-content|wp-includes|wp-admin|wp-json|feed)(\\/|$)/.test(u.pathname))continue;'
			. 'if(/\\.(css|js|jpe?g|png|gif|webp|svg|ico|woff2?|xml)(\\?|$)/i.test(u.pathname))continue;'
			. 'u.searchParams.set("lang",lang);a.setAttribute("href",u.pathname+u.search+u.hash);'
			. '}catch(e){}'
			. '}'
			. '}'
			. 'function runBrand(){'
			. 'if(document.querySelector(".cindemir-site-brand"))return;'
			. 'var inner=document.querySelector("#header_main .inner-container");'
			. 'if(!inner)return;'
			. 'var a=document.createElement("a");'
			. 'a.className="cindemir-site-brand";a.href="' . $home . '";a.setAttribute("aria-label","' . $label . '");'
			. 'a.innerHTML=\'<img src="' . $logo . '" width="48" height="48" alt="' . $label . '" decoding="async" />'
			. '<span class="cindemir-site-brand__text">' . $label . '</span>\';'
			. 'inner.insertBefore(a,inner.firstChild);'
			. '}'
			. 'function run(){fixSwitcher();stampLang();runBrand();}'
			. 'if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",run);else run();'
			. 'document.addEventListener("click",function(ev){'
			. 'var li=ev.target&&ev.target.closest&&ev.target.closest("li[class*=\\"language_\\"],li[class*=\\"wpml-ls-item-\\"]");'
			. 'if(li){'
			. 'var cls=li.className||"";'
			. 'var m=cls.match(/language_([a-z0-9\\-]+)/i)||cls.match(/wpml-ls-item-([a-z0-9\\-]+)/i);'
			. 'if(m){'
			. 'var map={en:"",ru:"ru","zh-hans":"zh-hans",zh:"zh-hans",tr:"tr"};'
			. 'var code=m[1].toLowerCase();'
			. 'if(Object.prototype.hasOwnProperty.call(map,code)){'
			. 'ev.preventDefault();'
			. 'var path=location.pathname||"/";'
			. 'try{'
			. 'if(map[code]){document.cookie="cindemir_lang="+map[code]+";path=/;max-age=31536000;SameSite=Lax;Secure";}'
			. 'else{document.cookie="cindemir_lang=;path=/;max-age=0;SameSite=Lax;Secure";document.cookie="cindemir_lang=;path=/;max-age=0;SameSite=Lax";}'
			. '}catch(e){}'
			. 'location.href=map[code]?(path+((path.indexOf("?")>=0?"&":"?")+"lang="+map[code])):(path+((path.indexOf("?")>=0?"&":"?")+"cindemir_lang=en"));'
			. 'return;'
			. '}'
			. '}'
			. '}'
			. 'var t=ev.target&&ev.target.closest&&ev.target.closest("a[href]");'
			. 'if(!t)return;'
			. 'try{'
			. 'if(!lang||lang==="en"||lang==="en-us"||lang==="en_us")return;'
			. 'if(t.closest&&t.closest(".avia_wpml_language_switch,.wpml-ls-item,.wpml-ls"))return;'
			. 'var u=new URL(t.getAttribute("href"),location.origin);'
			. 'if(u.hostname!==location.hostname)return;'
			. 'if(u.searchParams.get("lang"))return;'
			. 'if(/\\/(wp-content|wp-includes|wp-admin|wp-json|feed)(\\/|$)/.test(u.pathname))return;'
			. 'u.searchParams.set("lang",lang);t.setAttribute("href",u.pathname+u.search+u.hash);'
			. '}catch(e){}'
			. '},true);'
			. '})();</script>' . "\n";
	}

	/**
	 * Ensure utility/tag pages expose a single noindex robots directive
	 * even when Yoast/cache still emit index,follow.
	 */
	private static function normalize_robots_meta( $html ) {
		if ( ! self::should_noindex() ) {
			return $html;
		}
		$html = preg_replace(
			'/<meta\b[^>]*\bname=(["\'])robots\1[^>]*>\s*/i',
			'',
			$html
		);
		$html = preg_replace(
			'/<meta\b[^>]*\bcontent=(["\'])[^"\']*\1[^>]*\bname=(["\'])robots\2[^>]*>\s*/i',
			'',
			$html
		);
		$tag = '<meta name="robots" content="noindex, follow" />' . "\n";
		if ( preg_match( '/<head\b[^>]*>/i', $html ) ) {
			return preg_replace( '/<head\b[^>]*>/i', '$0' . "\n" . $tag, $html, 1 );
		}
		return $tag . $html;
	}

	public static function rewrite_content_hrefs( $content ) {
		return self::rewrite_hrefs_in_html( $content );
	}

	public static function author_to_home( $link ) {
		return home_url( '/' );
	}

	public static function exclude_brand_js( $exclude ) {
		if ( ! is_array( $exclude ) ) {
			$exclude = array();
		}
		$exclude[] = 'cindemir-header-brand-js';
		$exclude[] = 'cindemir-header-brand';
		$exclude[] = 'cindemir-lang-switch';
		$exclude[] = 'stampLang';
		$exclude[] = 'fixSwitcher';
		$exclude[] = 'data-nowprocket';
		return $exclude;
	}

	/** Exclude sticky-lang inline body from Rocket Delay JS content matching. */
	public static function exclude_brand_inline_js( $exclude ) {
		if ( ! is_array( $exclude ) ) {
			$exclude = array();
		}
		$exclude[] = 'stampLang';
		$exclude[] = 'fixSwitcher';
		$exclude[] = 'cindemir-header-brand-js';
		$exclude[] = 'cindemir-lang-switch';
		return $exclude;
	}

	public static function nav_href( $atts, $item ) {
		if ( empty( $atts['href'] ) ) {
			return $atts;
		}
		$atts['href'] = self::map_href( $atts['href'] );
		$atts['href'] = self::with_front_lang( $atts['href'] );
		return $atts;
	}

	/** Keep permalink filters on the active front-end language. */
	public static function filter_front_permalink( $url ) {
		return self::with_front_lang( $url );
	}

	/** Current front-end language slug (prefer ?lang= over ICL when both exist). */
	private static function front_lang() {
		if ( ! empty( $_GET['lang'] ) ) {
			$get = sanitize_key( wp_unslash( $_GET['lang'] ) );
			if ( $get ) {
				return $get;
			}
		}
		if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
			return (string) ICL_LANGUAGE_CODE;
		}
		if ( function_exists( 'apply_filters' ) ) {
			$wpml = apply_filters( 'wpml_current_language', null );
			if ( is_string( $wpml ) && '' !== $wpml ) {
				return $wpml;
			}
		}
		return 'en';
	}

	/**
	 * Append ?lang= without WordPress/WPML URL filters that may strip it.
	 */
	private static function raw_append_lang( $href, $lang ) {
		if ( ! is_string( $href ) || '' === $href || ! $lang ) {
			return $href;
		}
		if ( false !== strpos( $href, 'lang=' ) ) {
			return $href;
		}
		$hash = '';
		$hash_pos = strpos( $href, '#' );
		if ( false !== $hash_pos ) {
			$hash = substr( $href, $hash_pos );
			$href = substr( $href, 0, $hash_pos );
		}
		$sep = ( false === strpos( $href, '?' ) ) ? '?' : '&';
		return $href . $sep . 'lang=' . rawurlencode( $lang ) . $hash;
	}

	/**
	 * Keep internal links on the active language when WPML uses ?lang=.
	 */
	private static function with_front_lang( $href ) {
		if ( ! is_string( $href ) || '' === $href ) {
			return $href;
		}
		$lang = self::front_lang();
		if ( ! $lang || in_array( $lang, array( 'en', 'en-us', 'en_us' ), true ) ) {
			return $href;
		}
		if ( '#' === $href || 0 === strpos( $href, '#' ) || 0 === strpos( $href, 'mailto:' ) || 0 === strpos( $href, 'tel:' ) || 0 === strpos( $href, 'javascript:' ) ) {
			return $href;
		}
		if ( preg_match( '#^(https?:)?//#i', $href ) && false === stripos( $href, 'cindemirlaw.com' ) ) {
			return $href;
		}
		return self::raw_append_lang( $href, $lang );
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
		echo '<a href="' . esc_url( home_url( '/appointment/' ) ) . '">Book an Appointment</a> · ';
		echo '<a href="' . esc_url( home_url( '/about-us/' ) ) . '">About Us</a>';
		echo "</nav>\n";
	}

	/** True when utility/tag URLs must stay out of the index. */
	private static function should_noindex() {
		if ( is_tag() ) {
			return true;
		}
		if ( function_exists( 'is_page' ) && is_page( array( 'antimanual-assistant', 'embed-list' ) ) ) {
			return true;
		}
		$path = self::path();
		return in_array( $path, array( '/antimanual-assistant', '/embed-list' ), true );
	}

	public static function noindex_utility() {
		// Prefer wp_robots / Yoast filters; keep a late meta only as fallback
		// when those APIs are unavailable (should not duplicate index+noindex).
		if ( ! self::should_noindex() ) {
			return;
		}
		if ( has_filter( 'wp_robots' ) || has_filter( 'wpseo_robots' ) ) {
			return;
		}
		echo "<meta name=\"robots\" content=\"noindex,follow\" />\n";
	}

	public static function filter_wp_robots( $robots ) {
		if ( ! self::should_noindex() ) {
			return $robots;
		}
		if ( ! is_array( $robots ) ) {
			$robots = array();
		}
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
		return $robots;
	}

	public static function filter_yoast_robots( $robots ) {
		if ( ! self::should_noindex() ) {
			return $robots;
		}
		return 'noindex, follow';
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
		return self::fill_empty_alts_html( $content );
	}

	private static function rewrite_hrefs_in_html( $html ) {
		$html = self::apply_url_replace( $html );
		// og:image / twitter:image meta content attributes.
		$html = preg_replace_callback(
			'#(<meta\b[^>]*\b(?:property|name)=(["\'])(?:og:image|twitter:image)\2[^>]*\bcontent=(["\']))([^"\']*)(\3)#i',
			function ( $m ) {
				return $m[1] . esc_attr( self::apply_url_replace( $m[4] ) ) . $m[5];
			},
			$html
		);
		$html = preg_replace_callback(
			'#(\shref=(["\']))(https?://(?:www\.)?cindemirlaw\.com)?(/[^"\']*)(\2)#i',
			function ( $m ) {
				$quote = $m[2];
				$pathq = $m[4];
				$parts = wp_parse_url( 'https://cindemirlaw.com' . $pathq );
				$path  = isset( $parts['path'] ) ? untrailingslashit( rawurldecode( $parts['path'] ) ) : '';
				$path  = '' === $path ? '/' : $path;
				$q     = isset( $parts['query'] ) ? $parts['query'] : '';
				$dest  = self::resolve_path_dest( $path, $q );
				if ( $dest ) {
					return ' href=' . $quote . esc_attr( self::with_front_lang( $dest ) ) . $quote;
				}
				if ( 0 === strpos( $pathq, '/' ) ) {
					$kept = self::with_front_lang( 'https://cindemirlaw.com' . $pathq );
					return ' href=' . $quote . esc_attr( $kept ) . $quote;
				}
				return $m[0];
			},
			$html
		);
		$html = self::stamp_lang_on_internal_hrefs( $html );
		return $html;
	}

	/**
	 * Broad pass: ensure same-site hrefs keep the active ?lang= (menus, logos, switcher).
	 * Uses esc_attr (not esc_url) so WPML/clean_url cannot strip lang=.
	 */
	private static function stamp_lang_on_internal_hrefs( $html ) {
		$lang = self::front_lang();
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( ! $lang || in_array( $lang, array( 'en', 'en-us', 'en_us' ), true ) ) {
			return $html;
		}
		$stamped = 0;
		$out     = preg_replace_callback(
			'#\bhref=(["\'])(https?://(?:www\.)?cindemirlaw\.com(?:/[^"\']*)?|/[^"\']+)\1#i',
			function ( $m ) use ( $lang, &$stamped ) {
				$url = html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' );
				if ( false !== strpos( $url, 'lang=' ) ) {
					return $m[0];
				}
				if ( preg_match( '#/(?:wp-content|wp-includes|wp-json|wp-admin|feed|xmlrpc)(/|$|\?)#i', $url ) ) {
					return $m[0];
				}
				if ( preg_match( '#\.(?:css|js|jpe?g|png|gif|webp|svg|ico|woff2?|ttf|eot|map|xml)(?:\?|$)#i', $url ) ) {
					return $m[0];
				}
				$stamped++;
				return 'href=' . $m[1] . esc_attr( self::raw_append_lang( $url, $lang ) ) . $m[1];
			},
			$html
		);
		if ( null === $out ) {
			return $html;
		}
		if ( $stamped > 0 && false === strpos( $out, 'cindemir-lang-stamp' ) ) {
			$out = preg_replace(
				'/<head\b[^>]*>/i',
				'$0<!--cindemir-lang-stamp:' . esc_attr( $lang ) . ':' . (int) $stamped . '-->',
				$out,
				1
			);
		}
		return $out;
	}

	private static function map_href( $href ) {
		$path = self::normalize_path( $href );
		$parts = wp_parse_url( $href );
		$q = isset( $parts['query'] ) ? $parts['query'] : '';
		$dest = self::resolve_path_dest( $path, $q );
		if ( $dest ) {
			return $dest;
		}
		foreach ( self::$url_replace as $from => $to ) {
			if ( 0 === strpos( $href, $from ) ) {
				return $to;
			}
		}
		return $href;
	}

	/**
	 * Resolve a path to its final URL when it would otherwise 301.
	 *
	 * @param string $path Normalized path without trailing slash.
	 * @param string $query Existing query string (may already include lang=).
	 * @return string|false
	 */
	private static function resolve_path_dest( $path, $query = '' ) {
		if ( '/author/admin' === $path ) {
			return home_url( '/' );
		}
		if ( isset( self::$redirects[ $path ] ) ) {
			return self::$redirects[ $path ];
		}
		// WPML RU hash slugs and Cyrillic paths redirect to ?lang=ru when missing.
		if ( $query && false !== strpos( $query, 'lang=' ) ) {
			return false;
		}
		$is_fde = ( 0 === strpos( $path, '/fde' ) );
		$is_cyr = (bool) preg_match( '/[А-Яа-яЁё]/u', $path );
		if ( $is_fde || $is_cyr ) {
			return home_url( user_trailingslashit( $path ) . '?lang=ru' );
		}
		return false;
	}

	private static function ensure_missing_h1_html( $html ) {
		if ( preg_match( '/<h1[\s>]/i', $html ) ) {
			return $html;
		}
		$title = '';
		$id    = function_exists( 'get_queried_object_id' ) ? get_queried_object_id() : 0;
		if ( $id && isset( self::$missing_h1[ $id ] ) ) {
			$title = self::$missing_h1[ $id ];
		} elseif ( is_singular() ) {
			$title = wp_strip_all_tags( get_the_title( $id ) );
		} elseif ( preg_match( '/<title>(.*?)<\/title>/is', $html, $tm ) ) {
			$title = trim( preg_replace( '/\s*[-|].*$/', '', wp_strip_all_tags( $tm[1] ) ) );
		}
		if ( ! $title ) {
			return $html;
		}
		$h1 = '<h1 class="cindemir-seo-h1">' . esc_html( $title ) . '</h1>';
		$patterns = array(
			'/(<main\b[^>]*>)/i',
			'/(<div[^>]*id="main"[^>]*>)/i',
			'/(<div[^>]*class="[^"]*\bcontainer\b[^"]*"[^>]*>)/i',
			'/(<body\b[^>]*>)/i',
		);
		foreach ( $patterns as $pattern ) {
			$next = preg_replace( $pattern, '$1' . "\n" . $h1, $html, 1, $count );
			if ( $count ) {
				return $next;
			}
		}
		return $html;
	}

	private static function fix_canonical_html( $html ) {
		return preg_replace_callback(
			'#(<link\b[^>]*\brel=(["\'])canonical\2[^>]*\bhref=(["\']))([^"\']+)(\3)#i',
			function ( $m ) {
				$url = self::filter_canonical_url( $m[4] );
				return $m[1] . esc_url( $url ) . $m[5];
			},
			$html
		);
	}

	private static function fix_hreflang_html( $html ) {
		return preg_replace_callback(
			'#<link\b[^>]*\bhreflang=(["\'])([^"\']+)\1[^>]*\bhref=(["\'])([^"\']+)\3[^>]*>#i',
			function ( $m ) {
				$url = self::normalize_hreflang_url( $m[4] );
				return '<link rel="alternate" hreflang="' . esc_attr( $m[2] ) . '" href="' . esc_url( $url ) . '" />';
			},
			$html
		);
	}

	private static function strip_blocked_external_images( $html ) {
		$html = preg_replace(
			'#<img\b[^>]*\b(?:src|data-lazy-src)=(["\'])(?:https?:)?//idsb\.tmgrup\.com\.tr/[^"\']+\1[^>]*>#i',
			'',
			$html
		);
		if ( get_option( 'cindemir_tbb_badge_local', '' ) ) {
			return $html;
		}
		return preg_replace(
			'#<img\b[^>]*\b(?:src|data-lazy-src)=(["\'])(?:https?:)?//d\.barobirlik\.org\.tr/[^"\']+\1[^>]*>#i',
			'',
			$html
		);
	}

	private static function fill_empty_alts_html( $html ) {
		if ( false === strpos( $html, 'alt=""' ) && false === stripos( $html, "alt=''" ) ) {
			return $html;
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
			$html
		);
	}

	private static function shorten_title_tag( $html ) {
		return preg_replace_callback(
			'/<title>(.*?)<\/title>/is',
			function ( $m ) {
				$raw = wp_strip_all_tags( $m[1] );
				$raw = html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$raw = preg_replace( '/\s+/', ' ', trim( $raw ) );
				if ( function_exists( 'mb_strlen' ) ) {
					$len = mb_strlen( $raw );
				} else {
					$len = strlen( $raw );
				}
				if ( $len <= 60 ) {
					return '<title>' . esc_html( $raw ) . '</title>';
				}
				$brand = 'Cindemir Law Office';
				$base  = preg_replace( '/\s*[-|–—]\s*Cindemir Law Office\s*$/u', '', $raw );
				$base  = trim( $base );
				$max   = 55;
				if ( function_exists( 'mb_strlen' ) && mb_strlen( $base ) > $max ) {
					$cut = mb_substr( $base, 0, $max );
					$pos = mb_strrpos( $cut, ' ' );
					if ( false !== $pos ) {
						$cut = mb_substr( $cut, 0, $pos );
					}
					$base = $cut . '…';
				} elseif ( strlen( $base ) > $max ) {
					$cut = substr( $base, 0, $max );
					$pos = strrpos( $cut, ' ' );
					if ( false !== $pos ) {
						$cut = substr( $cut, 0, $pos );
					}
					$base = $cut . '...';
				}
				$new = $base . ' - ' . $brand;
				$new_len = function_exists( 'mb_strlen' ) ? mb_strlen( $new ) : strlen( $new );
				if ( $new_len > 60 ) {
					if ( function_exists( 'mb_substr' ) ) {
						$new = mb_substr( $base, 0, 48 ) . '… | Cindemir';
					} else {
						$new = substr( $base, 0, 48 ) . '... | Cindemir';
					}
				}
				return '<title>' . esc_html( $new ) . '</title>';
			},
			$html,
			1
		);
	}

	private static function path() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		return self::normalize_path( $uri );
	}

	private static function normalize_path( $url ) {
		$parts = wp_parse_url( $url );
		$path  = isset( $parts['path'] ) ? $parts['path'] : '/';
		$path  = rawurldecode( $path );
		$path  = untrailingslashit( $path );
		return '' === $path ? '/' : $path;
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

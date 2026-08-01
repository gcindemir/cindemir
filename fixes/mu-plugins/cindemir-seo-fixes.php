<?php
/* SERVICES_EMBED_DEPLOY_MARKER 1.9.76 + SERVICES_BLANK_FIX_20260715 + TEAM_PHOTO_SYNC_20260718B + ELENA_ZARA_RU_BIO_20260718 + ELENA_ZARA_BAR_SAFE_20260718 + SCHEMA_FIX_20260718 + BACKUP_WP_CRON_20260719 + HREFLANG_FIX_20260801 + HREFLANG_AFTER_STAMP_20260801 + NO_TITLE_META_OVERRIDE_20260801 + RU_SLUG_404_FIX_20260801 + CYR_REDIRECT_LOOP_FIX_20260801 + CYR_REDIRECTS_REMOVED_20260801 + REMAINING_AHREFS_20260801 */
/**
 * Plugin Name: Cindemir SEO Fixes
 * Description: Full Ahrefs cleanup: redirect href rewrite, flatten hops, H1/alts/orphans, author disable, title trim.
 * Version: 1.9.76
 * Version: 1.9.74
 * Version: 1.9.73
 * Version: 1.9.69
 * SERVICES_BLANK_FIX_20260715
 * HREFLANG_FIX_20260801
 * HREFLANG_AFTER_STAMP_20260801
 * NO_TITLE_META_OVERRIDE_20260801
 * RU_SLUG_404_FIX_20260801
 * CYR_REDIRECT_LOOP_FIX_20260801
 * REMAINING_AHREFS_20260801
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
		'/gokhan-cindemir-attorney-at-law-2-2' => 'https://cindemirlaw.com/gokhan-cindemir-attorney-at-law-2-2/?lang=zh-hans',
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
		// Dead WPML hash (Ahrefs 404) → RU home.
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbefde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/?lang=ru',
	);

	/**
	 * WPML RU translation slugs that 404 when ?lang=ru is appended.
	 * Map → English slug (works with ?lang=ru). Special: pod has no EN+lang=ru.
	 *
	 * @var array<string,string|null>
	 */
	private static $ru_slug_to_en = array(
		'/onas'         => '/about-us',
		'/kontak'       => '/contacts',
		'/komanda'      => '/team',
		'/stati'        => '/articles',
		'/nashiyurist'  => '/services',
		'/pod'          => null, // RU-only slug; bare /pod/ is 200, /pod/?lang=ru is 404.
	);

	/**
	 * Latin slugs that are RU-only (bare URL 301 → ?lang=ru). No real EN page.
	 * Hreflang: omit en; x-default + ru use ?lang=ru.
	 *
	 * @var string[]
	 */
	private static $ru_only_latin = array(
		'/proisshestvie-v-lifte-otelya-turtsii',
		'/yuridicheskaya-sila-smart-kontraktov-v-turtsii',
		'/dokazatelstva-posle-proisshestviya-v-otele-turtsii',
		'/otel-turoperator-strahovshchik-proisshestvie-turtsiya',
		'/otravlenie-v-otele-turtsii-chto-delat',
		'/padenie-v-otele-turtsii-chto-delat',
		'/padenie-s-balkona-otelya-v-turtsii-chto-delat',
		'/pravovoy-status-dao-v-turtsii',
		'/hotel-elevator-accident-turkey-what-to-do',
		'/iski-po-neschastnym-sluchayam-v-otelyakh-turtsii',
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

	const VERSION = '1.9.74';
	/** Pin pull-plugins to this commit so stale branch CDNs cannot win. */
	const DEPLOY_COMMIT = '07253fc';

	/** One-shot team photo refresh (remove departed colleague from group shot). */
	const TEAM_PHOTO_SYNC_KEY = 'cindemir_team_photo_sync_20260718f';
	const TEAM_PHOTO_CACHE_VER = '20260718f';

	/** Deploy freshness marker for pull-plugins. */
	const DEPLOY_MARKER = 'ELENA_ZARA_RU_BIO_20260718';

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
		'540664430' => 'Cindemir Law Office legal team in Istanbul',
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

	/** Neutral SEO titles (TBB-safe). */
	private static $page_titles = array(
		15 => 'Law Firms in Istanbul - Cindemir Law Office',
		16 => 'About Cindemir Law Office in Istanbul',
	);


	public static function boot() {
		add_action( 'init', array( __CLASS__, 'maybe_self_upgrade_from_github' ), 0 );
		add_action( 'init', array( __CLASS__, 'maybe_upgrade_sibling_plugins' ), 0 );
		add_action( 'init', array( __CLASS__, 'ensure_backup_mu_plugin' ), 0 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_deploy_routes' ) );
		add_action( 'init', array( __CLASS__, 'maybe_purge_caches_on_upgrade' ), 1 );
		add_action( 'init', array( __CLASS__, 'ensure_local_badge_assets' ), 2 );
		add_action( 'init', array( __CLASS__, 'ensure_office_lens_crop' ), 2 );
		add_action( 'init', array( __CLASS__, 'sync_updated_team_photo' ), 2 );
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
		add_filter( 'the_content', array( __CLASS__, 'append_elena_zara_ru_bio' ), 30 );
		add_action( 'wp_head', array( __CLASS__, 'elena_zara_bio_styles' ), 53 );
		add_filter( 'wpseo_meta_author', array( __CLASS__, 'filter_ru_meta_author' ), 20 );
		add_filter( 'wpseo_schema_graph', array( __CLASS__, 'filter_schema_graph' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'footer_meta_styles' ), 51 );
		add_action( 'wp_footer', array( __CLASS__, 'render_compact_footer_meta' ), 21 );
		add_action( 'wp_footer', array( __CLASS__, 'version_marker' ), 99 );
		add_action( 'wp_head', array( __CLASS__, 'header_brand_styles' ), 50 );
		add_action( 'wp_head', array( __CLASS__, 'homepage_hero_styles' ), 51 );
		add_action( 'wp_head', array( __CLASS__, 'team_photo_background_fix' ), 52 );
		add_action( 'wp_head', array( __CLASS__, 'pagespeed_head_hints' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'pagespeed_a11y_styles' ), 52 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'pagespeed_dequeue_heavy' ), 100 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'pagespeed_filter_script_tag' ), 20, 3 );
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
		add_filter( 'wp_get_nav_menu_items', array( __CLASS__, 'fix_nav_menu_items' ), 99, 3 );
		add_filter( 'nav_menu_item_title', array( __CLASS__, 'fix_nav_menu_item_title' ), 99, 4 );
		add_filter( 'wp_nav_menu_items', array( __CLASS__, 'append_lang_items_to_menu' ), 99, 2 );
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
		// Do not override Yoast title / meta description / focus keyword / keywords (or OG/Twitter copies).
		add_filter( 'robots_txt', array( __CLASS__, 'filter_robots_txt' ), 99, 2 );
		add_action( 'init', array( __CLASS__, 'maybe_rewrite_static_robots' ), 23 );
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( __CLASS__, 'exclude_utility_from_sitemap' ), 10 );
		add_filter( 'wpseo_canonical', array( __CLASS__, 'filter_canonical_url' ), 20 );
		add_filter( 'get_canonical_url', array( __CLASS__, 'filter_canonical_url' ), 20 );
		add_filter( 'wpml_hreflangs', array( __CLASS__, 'filter_hreflang_urls' ), 99 );
		add_filter( 'wpseo_hreflang_links', array( __CLASS__, 'filter_hreflang_urls' ), 99 );
		add_filter( 'wpseo_opengraph_image', array( __CLASS__, 'rewrite_media_url' ) );
		add_filter( 'wpseo_twitter_image', array( __CLASS__, 'rewrite_media_url' ) );
		// Enfold blog/masonry queries often set suppress_filters and leak cross-language posts.
		foreach ( array(
			'avia_blog_post_query',
			'avia_masonry_entries_query',
			'avia_post_grid_query',
			'avia_post_slide_query',
			'avf_magazine_entries_query',
		) as $avia_q ) {
			add_filter( $avia_q, array( __CLASS__, 'force_wpml_on_avia_query' ), 5 );
		}
		add_action( 'pre_get_posts', array( __CLASS__, 'force_wpml_pre_get_posts' ), 1 );
		add_filter( 'the_posts', array( __CLASS__, 'filter_posts_by_title_script' ), 20, 2 );
	}

	/**
	 * Force WPML-aware Avia blog queries (suppress_filters must stay false).
	 *
	 * @param array $query Query args from Enfold shortcodes.
	 * @return array
	 */
	public static function force_wpml_on_avia_query( $query ) {
		if ( ! is_array( $query ) ) {
			return $query;
		}
		$query['suppress_filters'] = false;
		return $query;
	}

	/** Keep secondary front-end post queries WPML-aware. */
	public static function force_wpml_pre_get_posts( $q ) {
		if ( is_admin() || ! ( $q instanceof WP_Query ) ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		$pt = $q->get( 'post_type' );
		$is_post = ( empty( $pt ) || 'post' === $pt || 'any' === $pt
			|| ( is_array( $pt ) && ( in_array( 'post', $pt, true ) || in_array( 'any', $pt, true ) ) ) );
		if ( ! $is_post ) {
			return;
		}
		if ( $q->get( 'suppress_filters' ) ) {
			$q->set( 'suppress_filters', false );
		}
	}

	/**
	 * Drop posts whose titles are clearly in another script than the active language.
	 * Needed when WPML language assignment does not match the actual title language
	 * (e.g. Cyrillic posts filed under English "uncategorized-en").
	 *
	 * @param WP_Post[] $posts Posts.
	 * @param WP_Query  $query Query.
	 * @return WP_Post[]
	 */
	public static function filter_posts_by_title_script( $posts, $query ) {
		if ( is_admin() || empty( $posts ) || ! is_array( $posts ) ) {
			return $posts;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $posts;
		}
		// Never hide the main singular document itself.
		if ( $query instanceof WP_Query && $query->is_main_query() && $query->is_singular() ) {
			return $posts;
		}
		$lang = self::normalize_front_lang( self::front_lang() );
		$out  = array();
		foreach ( $posts as $post ) {
			if ( ! ( $post instanceof WP_Post ) ) {
				$out[] = $post;
				continue;
			}
			if ( 'post' !== $post->post_type ) {
				$out[] = $post;
				continue;
			}
			if ( self::title_matches_lang( $post->post_title, $lang ) ) {
				$out[] = $post;
			}
		}
		return $out;
	}

	/** Normalize front language codes to en|ru|zh-hans. */
	private static function normalize_front_lang( $lang ) {
		$lang = is_string( $lang ) ? strtolower( $lang ) : 'en';
		if ( in_array( $lang, array( 'en', 'en-us', 'en_us' ), true ) ) {
			return 'en';
		}
		if ( in_array( $lang, array( 'zh', 'zh-hans', 'zh_cn', 'zh-cn' ), true ) ) {
			return 'zh-hans';
		}
		if ( 'ru' === $lang ) {
			return 'ru';
		}
		return $lang;
	}

	/**
	 * Whether a post title belongs on the active language listing.
	 *
	 * @param string $title Post title.
	 * @param string $lang  Normalized lang.
	 * @return bool
	 */
	private static function title_matches_lang( $title, $lang ) {
		$title = is_string( $title ) ? $title : '';
		if ( '' === $title ) {
			return true;
		}
		$has_cyr = (bool) preg_match( '/\p{Cyrillic}/u', $title );
		$has_cjk = (bool) preg_match( '/[\x{4E00}-\x{9FFF}]/u', $title );
		if ( 'en' === $lang ) {
			return ! $has_cyr && ! $has_cjk;
		}
		if ( 'ru' === $lang ) {
			return $has_cyr;
		}
		if ( 'zh-hans' === $lang ) {
			return $has_cjk;
		}
		return true;
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

	/**
	 * Overwrite the group portrait so the departed colleague on the far left
	 * is no longer shown. Source files live in the deploy branch under
	 * fixes/media/team-2026/ and are pulled via jsDelivr / GitHub raw.
	 */
	public static function sync_updated_team_photo() {
		if ( get_option( self::TEAM_PHOTO_SYNC_KEY ) ) {
			return;
		}
		if ( get_transient( 'cindemir_team_photo_sync_lock' ) ) {
			return;
		}
		set_transient( 'cindemir_team_photo_sync_lock', 1, 5 * MINUTE_IN_SECONDS );

		$dir = WP_CONTENT_DIR . '/uploads/2020/06';
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return;
		}

		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$bases  = array(
			'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/media/team-2026/',
			'https://cdn.jsdelivr.net/gh/gcindemir/cindemir@' . $branch . '/fixes/media/team-2026/',
			'https://fastly.jsdelivr.net/gh/gcindemir/cindemir@' . $branch . '/fixes/media/team-2026/',
		);
		$files  = array(
			'5295681199059.jpg'           => 40000,
			'5295681199059.webp'          => 20000,
			'5295681199059.jpg.webp'      => 20000,
			'5295681199059-300x135.jpg'   => 3000,
			'5295681199059-300x135.webp'  => 2000,
			'5295681199059-705x318.jpg'   => 8000,
			'5295681199059-705x318.webp'  => 5000,
			'5295681199059-768x346.jpg'   => 8000,
			'5295681199059-768x346.webp'  => 5000,
		);

		$ok = 0;
		foreach ( $files as $name => $min ) {
			$body = '';
			foreach ( $bases as $base ) {
				$response = wp_remote_get(
					$base . $name . '?v=' . rawurlencode( self::TEAM_PHOTO_CACHE_VER ),
					array(
						'timeout' => 60,
						'headers' => array(
							'User-Agent'    => 'CindemirTeamPhoto/' . self::VERSION,
							'Cache-Control' => 'no-cache',
						),
					)
				);
				if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
					continue;
				}
				$tmp = (string) wp_remote_retrieve_body( $response );
				if ( strlen( $tmp ) < $min ) {
					continue;
				}
				// Reject HTML error pages from CDNs.
				if ( 0 === strpos( ltrim( $tmp ), '<' ) ) {
					continue;
				}
				$body = $tmp;
				break;
			}
			if ( '' === $body ) {
				continue;
			}
			$dest = trailingslashit( $dir ) . $name;
			if ( false !== file_put_contents( $dest, $body ) ) {
				$ok++;
			}
		}

		// Require the main JPG + WebP before marking complete.
		$main_jpg  = trailingslashit( $dir ) . '5295681199059.jpg';
		$main_webp = trailingslashit( $dir ) . '5295681199059.webp';
		if ( $ok < 2 || ! file_exists( $main_jpg ) || filesize( $main_jpg ) < 20000 || ! file_exists( $main_webp ) || filesize( $main_webp ) < 10000 ) {
			return;
		}

		update_option( self::TEAM_PHOTO_SYNC_KEY, self::TEAM_PHOTO_CACHE_VER, false );
		delete_option( 'cindemir_seo_fixes_version' );
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'rocket_clean_files' ) ) {
			rocket_clean_files(
				array(
					'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059.jpg',
					'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059.webp',
					'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059.jpg.webp',
				)
			);
		}
	}


	/** Install/refresh WordPress-native daily backup mu-plugin from GitHub. */
	public static function ensure_backup_mu_plugin() {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return;
		}
		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-backup.php';
		$need = true;
		if ( file_exists( $dest ) ) {
			$local = (string) file_get_contents( $dest );
			if ( false !== strpos( $local, "VERSION     = '1.1.0'" ) || false !== strpos( $local, "const VERSION     = '1.1.0'" ) || false !== strpos( $local, 'BACKUP_WP_CRON_20260719' ) ) {
				$need = false;
			}
		}
		if ( ! $need ) {
			return;
		}
		$commit = self::DEPLOY_COMMIT;
		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$bases  = array(
			'https://cdn.jsdelivr.net/gh/gcindemir/cindemir@' . $commit . '/fixes/mu-plugins/cindemir-backup.php',
			'https://raw.githubusercontent.com/gcindemir/cindemir/' . $commit . '/fixes/mu-plugins/cindemir-backup.php',
			'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/cindemir-backup.php',
		);
		foreach ( $bases as $url ) {
			$res = wp_remote_get(
				$url . '?v=BACKUP_WP_CRON_20260719',
				array(
					'timeout' => 60,
					'headers' => array(
						'User-Agent'    => 'CindemirBackupEnsure/' . self::VERSION,
						'Cache-Control' => 'no-cache',
					),
				)
			);
			if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
				continue;
			}
			$body = (string) wp_remote_retrieve_body( $res );
			if ( strlen( $body ) < 5000 || false === strpos( $body, 'BACKUP_WP_CRON_20260719' ) ) {
				continue;
			}
			if ( false !== file_put_contents( $dest, $body ) ) {
				if ( function_exists( 'opcache_invalidate' ) ) {
					@opcache_invalidate( $dest, true );
				}
				break;
			}
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
		set_transient( 'cindemir_seo_self_upgrade_lock', 1, 2 * MINUTE_IN_SECONDS );

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
		set_transient( 'cindemir_sibling_upgrade_lock', 1, 2 * MINUTE_IN_SECONDS );

		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$base   = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/';
		$files  = array(
			'cindemir-contact-fixes.php'     => array( 'min' => 20000, 'ver' => '1.3.9' ),
			'cindemir-expose-yoast-meta.php' => array( 'min' => 2000, 'ver' => '1.2' ),
			'cindemir-purge-cache.php'       => array( 'min' => 500, 'ver' => '1.0' ),
			'cindemir-services-page.php'     => array( 'min' => 10000, 'ver' => '1.0.2' ),
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
		register_rest_route(
			'cindemir/v1',
			'/sync-team-photo',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'rest_sync_team_photo' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function rest_sync_team_photo( $request ) {
		$key = $request->get_param( 'key' );
		if ( 'seo-pack-2026' !== $key ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		delete_option( self::TEAM_PHOTO_SYNC_KEY );
		delete_transient( 'cindemir_team_photo_sync_lock' );
		self::sync_updated_team_photo();
		$dir  = WP_CONTENT_DIR . '/uploads/2020/06/';
		$main = $dir . '5295681199059.jpg';
		$webp = $dir . '5295681199059.webp';
		return new WP_REST_Response(
			array(
				'ok'      => (bool) get_option( self::TEAM_PHOTO_SYNC_KEY ),
				'version' => self::VERSION,
				'flag'    => get_option( self::TEAM_PHOTO_SYNC_KEY ),
				'jpg'     => file_exists( $main ) ? filesize( $main ) : 0,
				'webp'    => file_exists( $webp ) ? filesize( $webp ) : 0,
			),
			200
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
		$marker = 'ELENA_ZARA_RU_BIO_20260718';
		$commit = $request->get_param( 'commit' );
		if ( ! is_string( $commit ) || ! preg_match( '/^[a-f0-9]{7,40}$/', $commit ) ) {
			$commit = self::DEPLOY_COMMIT;
		}
		// Commit-pinned first: Bluehost often sees a stale raw.githubusercontent branch tip.
		// Full SHA → never fall back to the legacy d204 branch (it wins the VERSION gate wrongly).
		$bases = array(
			'https://cdn.jsdelivr.net/gh/gcindemir/cindemir@' . $commit . '/fixes/mu-plugins/',
			'https://fastly.jsdelivr.net/gh/gcindemir/cindemir@' . $commit . '/fixes/mu-plugins/',
			'https://raw.githubusercontent.com/gcindemir/cindemir/' . $commit . '/fixes/mu-plugins/',
		);
		if ( strlen( $commit ) < 40 ) {
			$bases[] = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/';
			$bases[] = 'https://github.com/gcindemir/cindemir/raw/' . $branch . '/fixes/mu-plugins/';
		}
		$files  = array(
			'cindemir-seo-fixes.php'         => 155000,
			'cindemir-contact-fixes.php'     => 20000,
			'cindemir-expose-yoast-meta.php' => 2000,
			'cindemir-purge-cache.php'       => 500,
			'cindemir-services-page.php'     => 10000,
			'cindemir-backup.php'            => 8000,
		);
		$out = array();
		foreach ( $files as $name => $min ) {
			$body = '';
			$src  = '';
			foreach ( $bases as $base ) {
				$response = wp_remote_get(
					$base . $name . '?v=' . rawurlencode( $marker . '-' . self::VERSION ),
					array(
						'timeout' => 90,
						'headers' => array(
							'User-Agent'    => 'CindemirPull/' . self::VERSION,
							'Cache-Control' => 'no-cache',
							'Pragma'        => 'no-cache',
						),
					)
				);
				if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
					continue;
				}
				$tmp = (string) wp_remote_retrieve_body( $response );
				if ( strlen( $tmp ) < $min || false === strpos( $tmp, '<?php' ) ) {
					continue;
				}
				if ( false === strpos( $tmp, $marker ) ) {
					continue;
				}
				if ( 'cindemir-seo-fixes.php' === $name
					&& ( ( false === strpos( $tmp, 'Version: 1.9.69' )
						&& false === strpos( $tmp, 'Version: 1.9.74' )
						&& false === strpos( $tmp, 'Version: 1.9.75' )
						&& false === strpos( $tmp, 'Version: 1.9.76' ) )
						|| false === strpos( $tmp, 'BACKUP_WP_CRON_20260719' )
						|| false === strpos( $tmp, 'HREFLANG_AFTER_STAMP_20260801' ) ) ) {
					continue;
				}
				$body = $tmp;
				$src  = $base;
				break;
			}
			if ( '' === $body ) {
				$out[ $name ] = array( 'ok' => false, 'error' => 'no-fresh-source' );
				continue;
			}
			$dest = trailingslashit( WPMU_PLUGIN_DIR ) . $name;
			file_put_contents( $dest, $body );
			if ( function_exists( 'opcache_invalidate' ) ) {
				@opcache_invalidate( $dest, true );
			}
			$out[ $name ] = array( 'ok' => true, 'bytes' => strlen( $body ), 'src' => $src );
		}
		delete_option( 'cindemir_seo_fixes_version' );
		wp_cache_flush();
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'version' => self::VERSION,
				'marker'  => $marker,
				'commit'  => $commit,
				'files'   => $out,
			),
			200
		);
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
			$fixed = self::normalize_hreflang_url( $url, is_string( $lang ) ? $lang : null );
			if ( ! is_string( $fixed ) || '' === $fixed ) {
				unset( $hreflangs[ $lang ] );
				continue;
			}
			$hreflangs[ $lang ] = $fixed;
		}
		return $hreflangs;
	}

	/**
	 * Force distinct, valid hreflang targets for WPML query-string mode.
	 * Fixes Ahrefs: same ?lang=ru URL used for en + ru + x-default, and lang=ru&lang=ru.
	 * Also avoids RU translation slugs + ?lang=ru (those 404; bare slug or EN+lang=ru works).
	 */
	private static function normalize_hreflang_url( $url, $lang = null ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}
		$url = html_entity_decode( $url, ENT_QUOTES, 'UTF-8' );
		$url = str_replace( '/contacts-2/', '/contacts/', $url );
		$url = str_replace( '/contacts-2?', '/contacts?', $url );
		$parts = wp_parse_url( $url );
		$path  = isset( $parts['path'] ) ? rawurldecode( $parts['path'] ) : '/';
		$q     = array();
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $q );
		}
		// Always clear lang first (drops duplicates like lang=ru&lang=ru).
		unset( $q['lang'] );

		$code = is_string( $lang ) ? strtolower( $lang ) : '';
		if ( 'zh' === $code || 0 === strpos( $code, 'zh' ) ) {
			$code = 'zh-hans';
		}

		$path_key = untrailingslashit( $path );
		if ( '' === $path_key ) {
			$path_key = '/';
		}

		// Map broken RU slugs (onas/kontak/…) to EN equivalents for all languages.
		// array_key_exists: /pod maps to null (bare slug only).
		if ( array_key_exists( $path_key, self::$ru_slug_to_en ) ) {
			$en_path = self::$ru_slug_to_en[ $path_key ];
			if ( null === $en_path ) {
				// /pod/: RU page lives at bare slug only.
				if ( 'ru' === $code ) {
					return self::build_unfiltered_url( $path, array() );
				}
				// EN/x-default → /support/ (EN counterpart).
				return self::build_unfiltered_url( '/support/', array() );
			}
			$path     = $en_path;
			$path_key = untrailingslashit( $path );
		}

		$is_cyr = (bool) preg_match( '/[А-Яа-яЁё]/u', $path );

		$ru_only_latin = in_array( $path_key, self::$ru_only_latin, true );
		if ( ! $ru_only_latin && isset( self::$redirects[ $path_key ] ) ) {
			$rd = self::$redirects[ $path_key ];
			if ( is_string( $rd ) && false !== strpos( $rd, 'lang=ru' ) && ! preg_match( '/[А-Яа-яЁё]/u', $path_key ) ) {
				$ru_only_latin = true;
			}
		}

		if ( in_array( $code, array( 'en', 'en-us', 'en_us', 'x-default' ), true ) ) {
			if ( $is_cyr ) {
				// No real EN URL for Cyrillic-only posts; omit fake en, x-default = RU+lang.
				if ( in_array( $code, array( 'en', 'en-us', 'en_us' ), true ) ) {
					return '';
				}
				return self::build_unfiltered_url( $path, array( 'lang' => 'ru' ) );
			}
			// Latin RU-only: bare URL 301s to ?lang=ru — omit en, x-default = ru URL.
			if ( $ru_only_latin ) {
				if ( in_array( $code, array( 'en', 'en-us', 'en_us' ), true ) ) {
					return '';
				}
				return self::build_unfiltered_url( $path, array( 'lang' => 'ru' ) );
			}
			// Default language / x-default: clean URL without ?lang=.
		} elseif ( 'ru' === $code ) {
			$q['lang'] = 'ru';
		} elseif ( 'zh-hans' === $code ) {
			$q['lang'] = 'zh-hans';
		} elseif ( '' !== $code ) {
			$q['lang'] = $code;
		}

		// Build without home_url()/add_query_arg() — WPML re-injects current ?lang= on RU pages.
		return self::build_unfiltered_url( $path, $q );
	}

	/**
	 * Canonicalize WPML RU translation slugs to working EN+?lang=ru URLs.
	 * Also fixes /onas/?lang=ru 404s and missing self-hreflang on bare /onas/.
	 *
	 * @param string $path Normalized path (may be decoded).
	 * @param string $query Raw query string.
	 * @return string|false Absolute redirect target or false.
	 */
	private static function fix_ru_slug_lang_404( $path, $query ) {
		$path     = rawurldecode( (string) $path );
		$path_key = untrailingslashit( $path );
		if ( '' === $path_key ) {
			$path_key = '/';
		}
		$has_ru = is_string( $query ) && false !== strpos( $query, 'lang=ru' );

		if ( array_key_exists( $path_key, self::$ru_slug_to_en ) ) {
			$en = self::$ru_slug_to_en[ $path_key ];
			if ( null === $en ) {
				// /pod/: bare slug is the working RU URL; ?lang=ru 404s.
				return $has_ru ? ( 'https://cindemirlaw.com' . user_trailingslashit( $path_key ) ) : false;
			}
			// /onas/, /kontak/, … → /about-us/?lang=ru (with or without ?lang= on the RU slug).
			return 'https://cindemirlaw.com' . user_trailingslashit( $en ) . '?lang=ru';
		}

		// Do NOT strip ?lang=ru from Cyrillic paths here — WPML (or others) may
		// 301 bare Cyrillic → ?lang=ru; stripping creates a redirect loop.

		return false;
	}

	/** Materialize missing press crop so Apache 404s become 200 (HTML rewrite alone is not enough). */
	public static function ensure_office_lens_crop() {
		if ( get_option( 'cindemir_office_lens_722_v1' ) ) {
			return;
		}
		$dir = trailingslashit( WP_CONTENT_DIR ) . 'uploads/2020/06/';
		$src = $dir . 'Office-Lens-20160311-153101-scaled.jpg';
		$dst = $dir . 'Office-Lens-20160311-153101-722x1030.jpg';
		if ( is_readable( $src ) && ! file_exists( $dst ) ) {
			@copy( $src, $dst );
		}
		// Belt-and-suspenders for other missing crops of the same original.
		$ht = $dir . '.htaccess';
		$rule = "\n# Cindemir Office-Lens crop fallback\n"
			. "RedirectMatch 301 (?i)^/wp-content/uploads/2020/06/Office-Lens-20160311-153101-\\d+x\\d+\\.jpg$ "
			. "/wp-content/uploads/2020/06/Office-Lens-20160311-153101-scaled.jpg\n";
		if ( is_dir( $dir ) ) {
			$existing = is_readable( $ht ) ? (string) file_get_contents( $ht ) : '';
			if ( false === strpos( $existing, 'Office-Lens-20160311-153101' ) ) {
				@file_put_contents( $ht, $existing . $rule );
			}
		}
		if ( file_exists( $dst ) || ( is_readable( $ht ) && false !== strpos( (string) file_get_contents( $ht ), 'Office-Lens-20160311-153101' ) ) ) {
			update_option( 'cindemir_office_lens_722_v1', 1, false );
		}
	}

	/** Absolute URL from option home + path + query (no WPML language injection). */
	private static function build_unfiltered_url( $path, $query = array() ) {
		$home = (string) get_option( 'home' );
		$home = preg_replace( '/[?&]lang=[^&]*/', '', $home );
		$home = untrailingslashit( explode( '#', $home, 2 )[0] );
		$home = rtrim( $home, '?&' );
		if ( ! is_string( $path ) || '' === $path ) {
			$path = '/';
		}
		if ( '/' !== $path[0] ) {
			$path = '/' . $path;
		}
		if ( ! preg_match( '/\.(?:pdf|jpe?g|png|gif|webp|svg|zip)$/i', $path ) ) {
			$path = user_trailingslashit( $path );
		}
		$url = $home . $path;
		if ( ! empty( $query ) && is_array( $query ) ) {
			$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
		}
		return $url;
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
		$en   = self::language_target_url( 'en', $base );
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
		$lang = self::normalize_front_lang( self::front_lang() );
		if ( ! $lang ) {
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

	/**
	 * Early resource hints: preconnect fonts + preload homepage LCP hero WebP.
	 */
	public static function pagespeed_head_hints() {
		if ( is_admin() ) {
			return;
		}
		echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		echo '<link rel="dns-prefetch" href="https://www.googletagmanager.com">' . "\n";
		echo '<link rel="dns-prefetch" href="https://fonts.googleapis.com">' . "\n";
		if ( is_front_page() || is_page( 15 ) ) {
			// Preload the actual on-disk hero (WebP is uploaded alongside when available).
			$hero_webp = WP_CONTENT_DIR . '/uploads/2020/10/540664430.webp';
			if ( file_exists( $hero_webp ) ) {
				echo '<link rel="preload" as="image" href="' . esc_url( content_url( 'uploads/2020/10/540664430.webp' ) ) . '" type="image/webp" fetchpriority="high">' . "\n";
			} else {
				echo '<link rel="preload" as="image" href="' . esc_url( content_url( 'uploads/2020/10/540664430.jpg' ) ) . '" fetchpriority="high">' . "\n";
			}
		}
		echo '<style id="cindemir-font-display">@font-face{font-display:swap!important}'
			. '.avia-font-entypo-fontello,.av-icon-char{font-display:swap}'
			. '</style>' . "\n";
	}

	/** Contrast, link underline, and sticky-safe a11y polish for PageSpeed. */
	public static function pagespeed_a11y_styles() {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="cindemir-pagespeed-a11y">'
			. ':root{'
			. '--enfold-socket-color-bg:#286060!important;'
			. '--enfold-socket-color-border:#286060!important;'
			. '--enfold-main-color-secondary:#286060!important'
			. '}'
			/* Teal #409090 on white fails WCAG; use darker brand teal. */
			. '#top #header .av-main-nav > li > a,'
			. '#top #header .av-main-nav > li > a .avia-menu-text,'
			. '#top #header .av-main-nav > li.cindemir-lang-item .avia-menu-text{'
			. 'color:#286060!important}'
			. '#top #header .av-main-nav > li.current-menu-item > a,'
			. '#top #header .av-main-nav > li.current_page_item > a,'
			. '#top #header .av-main-nav > li.avia_current_lang > a .avia-menu-text{'
			. 'color:#1f4f4f!important}'
			/* White text on teal socket / cards needs darker teal for 4.5:1. */
			. '#socket,.html_stretched #socket,.container_wrap#socket{'
			. 'background-color:#286060!important;color:#ffffff!important}'
			. '#socket .copyright,#socket a,#socket .cindemir-footer-meta,'
			. '#socket .cindemir-footer-meta a,#cindemir-baro-verification-bar a{'
			. 'color:#ffffff!important}'
			. '.avia_textblock a{text-decoration:underline!important;text-underline-offset:2px;'
			. 'color:#1f4f4f!important}'
			/* White text blocks on teal cards: keep light links, still underlined. */
			. '.avia_textblock.av_inherit_color a,'
			. '.av_textblock_section .avia_textblock.av_inherit_color a{'
			. 'color:#ffffff!important;text-decoration:underline!important;text-underline-offset:2px}'
			. '.avia-section.alternate_color{background-color:#286060!important}'
			. '.avia-section.alternate_color .avia_textblock,'
			. '.avia-section.alternate_color .avia_textblock h3,'
			. '.avia-section.alternate_color .avia_textblock p,'
			. '.avia-section.alternate_color .avia_textblock a{color:#ffffff!important}'
			. '</style>' . "\n";
	}

	/** Drop unused Google Identity Services on the public front-end (~98KB + console errors). */
	public static function pagespeed_dequeue_heavy() {
		if ( is_admin() ) {
			return;
		}
		global $wp_scripts;
		if ( ! ( $wp_scripts instanceof WP_Scripts ) ) {
			return;
		}
		foreach ( (array) $wp_scripts->registered as $handle => $obj ) {
			$src = isset( $obj->src ) ? (string) $obj->src : '';
			$hay = $src . '|' . (string) $handle;
			if ( false !== strpos( $hay, 'accounts.google.com/gsi' )
				|| false !== strpos( $hay, 'gsi/client' )
				|| false !== strpos( $hay, 'google-one-tap' )
				|| false !== strpos( $hay, 'googlesitekit-signin' )
				|| false !== strpos( $hay, 'googlesitekit-events-provider' )
				|| ( false !== strpos( $hay, 'googlesitekit' ) && false !== strpos( $hay, 'signin' ) )
				|| false !== strpos( $hay, 'AW-1027764587' )
				|| false !== strpos( $hay, 'googleads.g.doubleclick.net' )
				|| false !== strpos( $hay, 'pagead/viewthroughconversion' )
				|| false !== strpos( $hay, 'GTM-T6PQ95' )
				|| ( false !== strpos( $hay, 'googletagmanager.com/gtm.js' ) )
				|| false !== strpos( $hay, 'google_gtagjs' )
				|| false !== strpos( $hay, 'GT-WV3LSZHW' )
				|| false !== strpos( $hay, 'googlesitekit' ) ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}
	}

	public static function pagespeed_filter_script_tag( $tag, $handle, $src ) {
		if ( is_admin() ) {
			return $tag;
		}
		$hay = (string) $src . (string) $handle . (string) $tag;
		if ( false !== strpos( $hay, 'accounts.google.com/gsi' )
			|| false !== strpos( $hay, 'gsi/client' )
			|| false !== strpos( $hay, 'AW-1027764587' )
			|| false !== strpos( $hay, 'googleads.g.doubleclick.net' )
			|| false !== strpos( $hay, 'GTM-T6PQ95' )
			|| false !== strpos( $hay, 'googletagmanager.com/gtm.js' )
			|| false !== strpos( $hay, 'GT-WV3LSZHW' )
			|| false !== strpos( $hay, 'google_gtagjs' ) ) {
			return '';
		}
		return $tag;
	}

	/**
	 * HTML rewrite pass for PageSpeed: drop GSI, fix menu ARIA, descriptive CTAs, WebP backgrounds.
	 */
	private static function pagespeed_rewrite_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		// Strip Google Identity Services client (unused on public pages; triggers console errors).
		$html = preg_replace(
			'#<script\b[^>]*(?:accounts\.google\.com/gsi/client|gsi/client)[^>]*>\s*</script>#i',
			'',
			$html
		);
		if ( null === $html ) {
			return '';
		}
		// Site Kit "Sign in with Google" front-end block (~console errors + unused JS).
		$html = preg_replace(
			'#<!--\s*Sign in with Google.*?</script>\s*<!--\s*End Sign in with Google[^>]*-->#is',
			'',
			$html
		);
		if ( null === $html ) {
			return '';
		}
		$html = preg_replace(
			'#<script\b[^>]*(?:data-siwg-config|googlesitekit-sign-in)[^>]*>.*?</script>#is',
			'',
			$html
		);
		if ( null === $html ) {
			return '';
		}
		// Drop broken relativedns prefetch remnants and Ads conversion scripts that
		// set third-party cookies / deprecated APIs (crush Best Practices score).
		$html = preg_replace( '#<link[^>]+href=[\"\']https?://cindemirlaw\.com/+www\.[^\"\']+[\"\'][^>]*>#i', '', $html );
		$html = preg_replace( '#<script\b[^>]*(?:gtag/js\?id=AW-|googleads\.g\.doubleclick\.net|pagead/viewthroughconversion|GTM-T6PQ95|googletagmanager\.com/gtm\.js|gtag/js\?id=GT-WV3LSZHW|google_gtagjs)[^>]*>.*?</script>#is', '', $html );
		$html = preg_replace( '#\(function\(w,d,s,l,i\)\{[\s\S]*?GTM-T6PQ95[\s\S]*?\}\)\(window,document,\'script\',\'dataLayer\',\'GTM-T6PQ95\'\);#', '', $html );
		$html = preg_replace( '#<noscript>\s*<iframe[^>]+googletagmanager\.com/ns\.html\?id=GTM-T6PQ95[^>]*>[\s\S]*?</iframe>\s*</noscript>#i', '', $html );
		$html = preg_replace( '#<!-- Google tag \(gtag\.js\) -->[\s\S]*?GT-WV3LSZHW[\s\S]*?</script>#i', '', $html );
		$html = preg_replace( '#<!--\s*Global site tag \(gtag\.js\) - Google Ads:[\s\S]*?</script>#i', '', $html );
		$html = preg_replace( '#<!--\s*Event snippet for[\s\S]*?</script>#i', '', $html );
		$html = preg_replace( '#<!--\s*Google Tag Manager snippet added by Site Kit\s*-->[\s\S]*?</script>#i', '', $html );
		$html = preg_replace( '#<!--\s*End Google Tag Manager[^>]*-->#i', '', $html );
		// Debloat/Rocket often base64-encode Ads/GTM snippets — drop those script tags by decoded payload.
		$html = preg_replace_callback(
			'#<script\b([^>]*)>(.*?)</script>#is',
			static function ( $m ) {
				$open = $m[1];
				$inner = $m[2];
				$blob  = $open . ' ' . $inner;
				if ( preg_match( '/src=[\"\']data:text\\/javascript;base64,([A-Za-z0-9+\\/=]+)[\"\']/i', $open, $bm ) ) {
					$decoded = base64_decode( $bm[1], true );
					if ( is_string( $decoded ) ) {
						$blob .= ' ' . $decoded;
					}
				}
				if ( false !== strpos( $blob, 'cindemir-contact-form-fallback' )
					|| false !== strpos( $blob, 'cindemir-whatsapp-fallback' )
					|| false !== strpos( $blob, 'cindemir-header-brand' )
					|| false !== strpos( $blob, 'cindemir-lang-switch' )
					|| false !== strpos( $open, 'data-nowprocket' )
					|| false !== strpos( $open, 'nowprocket' ) ) {
					return $m[0];
				}
				if ( false !== strpos( $blob, 'AW-1027764587' )
					|| false !== strpos( $blob, 'GTM-T6PQ95' )
					|| false !== strpos( $blob, 'GT-WV3LSZHW' )
					|| ( false !== strpos( $blob, 'googletagmanager.com/gtm.js' ) && false !== strpos( $blob, 'gtm.start' ) ) ) {
					return '';
				}
				return $m[0];
			},
			$html
		);
		if ( null === $html ) {
			return '';
		}

		// Avia marks the main nav as role="menu" with role="menuitem" children —
		// that conflicts with list semantics. Strip menu roles site-wide in header nav.
		$html = preg_replace( '/\srole=[\"\']menu[\"\']/i', '', $html );
		$html = preg_replace( '/\srole=[\"\']menuitem[\"\']/i', '', $html );

		// Prefer WebP only when the optimized file already exists on disk (avoids 404s).
		$webp_map = array(
			'/uploads/2020/10/540664430.jpg'     => '/uploads/2020/10/540664430.webp',
			'/uploads/2020/06/5295681199059.jpg' => '/uploads/2020/06/5295681199059.webp',
		);
		foreach ( $webp_map as $jpg_rel => $webp_rel ) {
			$abs = WP_CONTENT_DIR . $webp_rel;
			if ( ! file_exists( $abs ) ) {
				continue;
			}
			$jpg  = '/wp-content' . $jpg_rel;
			$webp = '/wp-content' . $webp_rel;
			$html = str_replace(
				array(
					'https://cindemirlaw.com' . $jpg,
					'http://cindemirlaw.com' . $jpg,
					$jpg,
				),
				array(
					'https://cindemirlaw.com' . $webp,
					'https://cindemirlaw.com' . $webp,
					$webp,
				),
				$html
			);
		}

		// Cache-bust team portrait URLs (token swap avoids double ?v= and PCRE).
		if ( get_option( self::TEAM_PHOTO_SYNC_KEY ) && false !== strpos( $html, '5295681199059' ) ) {
			$ver = self::TEAM_PHOTO_CACHE_VER;
			$html = str_replace( array( '?v=' . $ver . '?v=' . $ver, '?v=' . $ver . '.webp' ), array( '?v=' . $ver, '.webp?v=' . $ver ), $html );
			foreach ( array(
				'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059.jpg',
				'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059.webp',
				'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059.jpg.webp',
				'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059-300x135.jpg',
				'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059-300x135.webp',
				'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059-705x318.jpg',
				'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059-705x318.webp',
				'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059-768x346.jpg',
				'https://cindemirlaw.com/wp-content/uploads/2020/06/5295681199059-768x346.webp',
			) as $abs ) {
				if ( false === strpos( $html, $abs ) ) {
					continue;
				}
				$token = '%%CINDEMIR_TEAM_PHOTO_' . md5( $abs ) . '%%';
				$html  = str_replace( $abs . '?v=' . $ver, $token, $html );
				$html  = str_replace( $abs, $token, $html );
				$html  = str_replace( $token, $abs . '?v=' . $ver, $html );
			}
		}

		// Darken low-contrast Enfold teal (#409090 ≈ 3.74:1 with white) to WCAG-safe #286060.
		$html = str_replace(
			array(
				'background-color:#409090',
				'background:#409090',
				'--enfold-socket-color-bg:#409090',
				'--enfold-socket-color-border:#409090',
				'--enfold-main-color-secondary:#409090',
				'color:#409090',
			),
			array(
				'background-color:#286060',
				'background:#286060',
				'--enfold-socket-color-bg:#286060',
				'--enfold-socket-color-border:#286060',
				'--enfold-main-color-secondary:#286060',
				'color:#286060',
			),
			$html
		);

		// Descriptive CTA labels (SEO link-text audit flags generic "Read More").
		$cta_map = array(
			'/about-us/'            => 'About Our Law Office',
			'/services/#debt'       => 'Debt Collection Services',
			'/services/#due'        => 'Due Diligence Services',
			'/services/#energy'     => 'Energy Law Services',
			'/services/#corporate'  => 'Corporate and Commercial Law',
			'/services/#escrow'     => 'Escrow Services',
			'/services/#real'       => 'Real Estate Law Services',
		);
		foreach ( $cta_map as $path => $label ) {
			$html = preg_replace_callback(
				'#(<a\b[^>]*href=[\"\']https?://cindemirlaw\.com' . preg_quote( $path, '#' ) . '[\"\'][^>]*>)(.*?)(</a>)#is',
				static function ( $m ) use ( $label ) {
					$open = $m[1];
					$inner = $m[2];
					if ( ! preg_match( '/read\s*more/i', wp_strip_all_tags( $inner ) ) && ! preg_match( '/aria-label=[\"\']Read More[\"\']/i', $open ) ) {
						return $m[0];
					}
					$open = preg_replace( '/aria-label=[\"\'][^\"\']*[\"\']/i', 'aria-label="' . esc_attr( $label ) . '"', $open, 1 );
					if ( null === $open || ! preg_match( '/aria-label=/i', $open ) ) {
						$open = preg_replace( '/<a\b/i', '<a aria-label="' . esc_attr( $label ) . '"', $m[1], 1 );
					}
					$inner = preg_replace( '/>\s*Read More\s*</i', '>' . esc_html( $label ) . '<', $inner );
					$inner = preg_replace( '/\bRead More\b/i', esc_html( $label ), $inner );
					return $open . $inner . $m[3];
				},
				$html
			);
		}

		return $html;
	}


	/** True on Russian single blog posts (WPML ?lang=ru / ICL). */
	private static function is_ru_single_post() {
		if ( ! is_singular( 'post' ) ) {
			return false;
		}
		$lang = self::front_lang();
		if ( 'ru' === $lang ) {
			return true;
		}
		// Fallback: body/html already rendered with ru locale markers during buffer.
		return false;
	}

	/**
	 * Bar-safe author byline on Russian articles (identity only).
	 * No photo, email, education pitch, or consultancy solicitation.
	 */
	public static function elena_zara_bio_html() {
		$team = 'https://cindemirlaw.com/team/?lang=ru';
		return '<aside class="cindemir-elena-bio" aria-label="Автор">'
			. '<p class="cindemir-elena-bio__byline">'
			. '<span class="cindemir-elena-bio__name">Ав. Елена Зара</span>'
			. ' — адвокат Стамбульской коллегии адвокатов'
			. ' · '
			. '<a href="' . esc_url( $team ) . '">Команда бюро</a>'
			. '</p>'
			. '</aside>';
	}

	public static function append_elena_zara_ru_bio( $content ) {
		if ( is_admin() || ! self::is_ru_single_post() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}
		if ( false !== strpos( $content, 'cindemir-elena-bio' ) ) {
			return $content;
		}
		return $content . self::elena_zara_bio_html();
	}

	public static function elena_zara_bio_styles() {
		if ( ! self::is_ru_single_post() ) {
			return;
		}
		echo '<style id="cindemir-elena-bio-css">'
			. '.cindemir-elena-bio{margin:1.75rem 0 1rem;padding:0.85rem 0 0;'
			. 'border-top:1px solid rgba(31,79,79,.18);max-width:40rem}'
			. '.cindemir-elena-bio__byline{margin:0;font-size:.95rem;line-height:1.45;color:#333}'
			. '.cindemir-elena-bio__name{font-weight:600;color:#1f4f4f}'
			. '.cindemir-elena-bio__byline a{color:#1f4f4f;text-decoration:underline;text-underline-offset:2px}'
			. '</style>' . "\n";
	}

	public static function filter_ru_meta_author( $author ) {
		if ( self::is_ru_single_post() ) {
			return 'Av. Elena Zara';
		}
		return $author;
	}

	/**
	 * Site-wide Yoast schema cleanup (bar-safe, factual).
	 * - RU posts: author Person → Av. Elena Zara, /team/ URL, no Gravatar
	 * - All posts: strip admin author leftovers / Gravatar on Person
	 * - Organization: ensure logo
	 * - Front: ensure WebSite
	 * - Team / services pages: ensure WebPage + Organization publisher link
	 */
	public static function filter_schema_graph( $graph ) {
		if ( ! is_array( $graph ) ) {
			return $graph;
		}
		foreach ( $graph as $i => $node ) {
			$graph[ $i ] = self::normalize_schema_node( $node );
		}
		$graph = self::ensure_organization_in_graph( $graph );
		if ( function_exists( 'is_front_page' ) && is_front_page() ) {
			$graph = self::ensure_website_in_graph( $graph );
		}
		if ( self::is_team_or_services_page() ) {
			$graph = self::ensure_webpage_in_graph( $graph );
		}
		return $graph;
	}

	/** @return bool */
	private static function is_team_or_services_page() {
		if ( ! function_exists( 'is_page' ) ) {
			return false;
		}
		// EN/RU/ZH team + services page IDs used elsewhere in this plugin.
		return is_page( array( 19, 2427, 18, 2638, 2637, 56 ) );
	}

	/**
	 * Recursively normalize schema nodes (Person / Organization / Article author).
	 *
	 * @param mixed $node Schema node.
	 * @return mixed
	 */
	private static function normalize_schema_node( $node ) {
		if ( ! is_array( $node ) ) {
			return $node;
		}
		$type  = isset( $node['@type'] ) ? $node['@type'] : '';
		$types = is_array( $type ) ? $type : array( $type );

		if ( in_array( 'Person', $types, true ) ) {
			$node = self::normalize_person_schema( $node );
		}
		if ( in_array( 'Organization', $types, true ) ) {
			$node = self::normalize_organization_schema( $node );
		}
		if ( in_array( 'SiteNavigationElement', $types, true ) ) {
			$node = self::normalize_nav_schema( $node );
		}
		if ( in_array( 'Article', $types, true ) || in_array( 'BlogPosting', $types, true ) || in_array( 'VideoObject', $types, true ) ) {
			if ( isset( $node['author'] ) ) {
				$node['author'] = self::normalize_schema_node( $node['author'] );
			}
			if ( isset( $node['publisher'] ) && is_array( $node['publisher'] ) ) {
				$node['publisher'] = self::normalize_organization_schema( $node['publisher'] );
			}
		}

		foreach ( $node as $k => $v ) {
			if ( '@graph' === $k && is_array( $v ) ) {
				foreach ( $v as $gi => $gn ) {
					$node[ $k ][ $gi ] = self::normalize_schema_node( $gn );
				}
			} elseif ( is_array( $v ) && isset( $v['@type'] ) ) {
				$node[ $k ] = self::normalize_schema_node( $v );
			}
		}
		return $node;
	}

	/**
	 * @param array $person Person node.
	 * @return array
	 */
	private static function normalize_person_schema( $person ) {
		$name = isset( $person['name'] ) ? (string) $person['name'] : '';
		// Buffer pass may run when query flags are odd; also key off front lang + single-post body class via is_singular.
		$is_ru_post = self::is_ru_single_post()
			|| ( function_exists( 'is_singular' ) && is_singular( 'post' ) && 'ru' === self::front_lang() );
		if ( $is_ru_post && ( '' === $name || 0 === strcasecmp( $name, 'admin' ) || 0 === strcasecmp( $name, 'Av. Elena Zara' ) ) ) {
			$person['name'] = 'Av. Elena Zara';
			$lang_q = ( 'ru' === self::front_lang() ) ? '?lang=ru' : '';
			$person['url']  = 'https://cindemirlaw.com/team/' . $lang_q;
			// Identity only — no promotional Person enrichments / wrong Gravatar.
			unset( $person['image'], $person['jobTitle'], $person['worksFor'] );
			$person['sameAs'] = array( 'https://cindemirlaw.com/team/' . $lang_q );
		} elseif ( '' === $name || 0 === strcasecmp( $name, 'admin' ) ) {
			// Non-RU (or unknown): attribute to the firm, not the WP "admin" user.
			$person['@type'] = 'Organization';
			$person['name']  = 'Cindemir Law Office';
			$person['url']   = 'https://cindemirlaw.com/';
			unset( $person['image'], $person['jobTitle'], $person['worksFor'], $person['sameAs'] );
		} else {
			// Drop Gravatar leftovers on any Person node.
			if ( isset( $person['image'] ) ) {
				$img = $person['image'];
				$img_url = '';
				if ( is_string( $img ) ) {
					$img_url = $img;
				} elseif ( is_array( $img ) && isset( $img['url'] ) ) {
					$img_url = (string) $img['url'];
				}
				if ( false !== stripos( $img_url, 'gravatar.com' ) ) {
					unset( $person['image'] );
				}
			}
		}
		return $person;
	}

	/**
	 * Fix Yoast SiteNavigationElement junk (@id /#slug) and off-site press URL.
	 *
	 * @param array $nav Navigation node.
	 * @return array
	 */
	private static function normalize_nav_schema( $nav ) {
		$url = isset( $nav['url'] ) ? (string) $nav['url'] : '';
		if ( $url && false !== strpos( $url, 'cindemir.av.tr' ) ) {
			$lang = self::front_lang();
			$nav['url'] = ( in_array( $lang, array( 'ru', 'zh-hans' ), true ) )
				? 'https://cindemirlaw.com/press/?lang=' . rawurlencode( $lang )
				: 'https://cindemirlaw.com/press/';
			$url = $nav['url'];
		}
		if ( $url ) {
			$name = isset( $nav['name'] ) ? (string) $nav['name'] : 'item';
			$slug = function_exists( 'sanitize_title' ) ? sanitize_title( $name ) : preg_replace( '/[^a-z0-9]+/i', '-', strtolower( $name ) );
			$nav['@id'] = $url . '#nav-' . $slug;
		}
		return $nav;
	}

	/**
	 * @param array $org Organization node.
	 * @return array
	 */
	private static function normalize_organization_schema( $org ) {
		if ( empty( $org['name'] ) ) {
			$org['name'] = 'Cindemir Law Office';
		}
		if ( empty( $org['url'] ) ) {
			$org['url'] = 'https://cindemirlaw.com/';
		}
		$logo_url = '';
		if ( ! empty( $org['logo'] ) ) {
			if ( is_string( $org['logo'] ) ) {
				$logo_url = $org['logo'];
			} elseif ( is_array( $org['logo'] ) && ! empty( $org['logo']['url'] ) ) {
				$logo_url = (string) $org['logo']['url'];
			}
		}
		if ( '' === $logo_url ) {
			$org['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => self::HEADER_LOGO,
			);
		}
		return $org;
	}

	/**
	 * @param array $graph Schema graph pieces.
	 * @return array
	 */
	private static function ensure_organization_in_graph( $graph ) {
		$has_org = false;
		foreach ( $graph as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			$type  = isset( $node['@type'] ) ? $node['@type'] : '';
			$types = is_array( $type ) ? $type : array( $type );
			if ( in_array( 'Organization', $types, true ) ) {
				$has_org = true;
				break;
			}
		}
		if ( $has_org ) {
			return $graph;
		}
		if ( ! ( function_exists( 'is_front_page' ) && is_front_page() ) && ! self::is_team_or_services_page() ) {
			return $graph;
		}
		$graph[] = self::normalize_organization_schema(
			array(
				'@type' => 'Organization',
				'@id'   => 'https://cindemirlaw.com/#organization',
				'name'  => 'Cindemir Law Office',
				'url'   => 'https://cindemirlaw.com/',
			)
		);
		return $graph;
	}

	/**
	 * @param array $graph Schema graph pieces.
	 * @return array
	 */
	private static function ensure_website_in_graph( $graph ) {
		foreach ( $graph as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			$type  = isset( $node['@type'] ) ? $node['@type'] : '';
			$types = is_array( $type ) ? $type : array( $type );
			if ( in_array( 'WebSite', $types, true ) ) {
				return $graph;
			}
		}
		$graph[] = array(
			'@type'     => 'WebSite',
			'@id'       => 'https://cindemirlaw.com/#website',
			'url'       => 'https://cindemirlaw.com/',
			'name'      => 'Cindemir Law Office',
			'publisher' => array(
				'@id' => 'https://cindemirlaw.com/#organization',
			),
			'inLanguage' => array( 'en', 'ru', 'zh-Hans' ),
		);
		return $graph;
	}

	/**
	 * @param array $graph Schema graph pieces.
	 * @return array
	 */
	private static function ensure_webpage_in_graph( $graph ) {
		foreach ( $graph as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			$type  = isset( $node['@type'] ) ? $node['@type'] : '';
			$types = is_array( $type ) ? $type : array( $type );
			if ( in_array( 'WebPage', $types, true ) ) {
				return $graph;
			}
		}
		$url = function_exists( 'get_permalink' ) ? get_permalink() : '';
		if ( ! is_string( $url ) || '' === $url ) {
			return $graph;
		}
		$title = function_exists( 'wp_get_document_title' ) ? wp_get_document_title() : get_the_title();
		$graph[] = array(
			'@type'     => 'WebPage',
			'@id'       => trailingslashit( $url ) . '#webpage',
			'url'       => $url,
			'name'      => is_string( $title ) ? wp_strip_all_tags( $title ) : '',
			'isPartOf'  => array( '@id' => 'https://cindemirlaw.com/#website' ),
			'about'     => array( '@id' => 'https://cindemirlaw.com/#organization' ),
			'publisher' => array( '@id' => 'https://cindemirlaw.com/#organization' ),
		);
		return $graph;
	}

	/** HTML-buffer fallbacks for RU author attribution + bio placement. */
	private static function rewrite_ru_article_seo( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$is_ru = ( 'ru' === self::front_lang() )
			|| false !== strpos( $html, 'og:locale" content="ru_RU"' )
			|| false !== strpos( $html, "og:locale' content='ru_RU'" )
			|| false !== strpos( $html, 'language_ru' );
		$is_post = false !== strpos( $html, 'single-post' ) || false !== strpos( $html, 'post-template-default' );
		if ( ! $is_ru || ! $is_post ) {
			return $html;
		}

		// Author meta.
		$html = preg_replace(
			'/<meta\s+name=["\']author["\']\s+content=["\']admin["\']\s*\/?>/i',
			'<meta name="author" content="Av. Elena Zara" />',
			$html,
			1
		);
		if ( false === stripos( $html, 'name="author"' ) ) {
			$html = preg_replace(
				'/(<meta\s+property=["\']og:locale["\'][^>]*>)/i',
				'$1' . "\n" . '<meta name="author" content="Av. Elena Zara" />',
				$html,
				1
			);
		}

		// Inject bio before closing post-entry article if the_content filter missed builder layouts.
		if ( false === strpos( $html, 'cindemir-elena-bio' ) ) {
			$bio = self::elena_zara_bio_html();
			$replaced = preg_replace(
				'#(</article>)#i',
				$bio . '$1',
				$html,
				1,
				$count
			);
			if ( is_string( $replaced ) && $count > 0 ) {
				$html = $replaced;
			} else {
				$replaced = preg_replace(
					'#(</div>\s*<!--\s*close post-entry|id=["\']after_section)#i',
					$bio . '$1',
					$html,
					1,
					$count2
				);
				if ( is_string( $replaced ) && ! empty( $count2 ) ) {
					$html = $replaced;
				}
			}
		}

		return $html;
	}

	/**
	 * Final JSON-LD pass in the HTML buffer (covers nested Article.author Person
	 * that Yoast emits outside the flat @graph pieces).
	 *
	 * @param string $html Full page HTML.
	 * @return string
	 */
	private static function rewrite_jsonld_html( $html ) {
		if ( ! is_string( $html ) || '' === $html || false === stripos( $html, 'application/ld+json' ) ) {
			return $html;
		}
		// Per-script replace avoids PCRE failures on huge pages / VideoObject transcripts.
		if ( preg_match_all( '#<script\b[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$raw = trim( $m[1] );
				if ( '' === $raw ) {
					continue;
				}
				$data = json_decode( $raw, true );
				if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
					continue;
				}
				$fixed = self::normalize_schema_data_public( $data );
				$json  = wp_json_encode( $fixed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				if ( ! is_string( $json ) || '' === $json ) {
					continue;
				}
				$open = substr( $m[0], 0, strpos( $m[0], '>' ) + 1 );
				$html = str_replace( $m[0], $open . $json . '</script>', $html );
			}
		}
		return self::append_missing_schema_script( $html );
	}

	/**
	 * Inject WebSite / WebPage / Organization when Yoast omitted them.
	 * Detects page type from body classes (reliable in the HTML buffer).
	 *
	 * @param string $html Full page HTML.
	 * @return string
	 */
	private static function append_missing_schema_script( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		// Match body classes only — Enfold CSS contains ".single-post" on every page.
		$is_home        = (bool) preg_match( '/<body[^>]*\bclass="[^"]*\bhome\b/i', $html );
		$is_single_post = (bool) preg_match( '/<body[^>]*\bclass="[^"]*\bsingle-post\b/i', $html );
		$is_wp_page     = (bool) preg_match( '/<body[^>]*\bclass="[^"]*\bpage-id-\d+\b/i', $html ) && ! $is_single_post;
		if ( ! $is_home && ! $is_wp_page ) {
			return $html;
		}

		$has_website = ( false !== stripos( $html, '"@type":"WebSite"' ) || false !== stripos( $html, '"@type": "WebSite"' ) );
		$has_webpage = ( false !== stripos( $html, '"@type":"WebPage"' ) || false !== stripos( $html, '"@type": "WebPage"' ) );
		$has_org     = ( false !== stripos( $html, '"@type":"Organization"' ) || false !== stripos( $html, '"@type": "Organization"' ) );

		$nodes = array();
		if ( ( $is_home || $is_wp_page ) && ! $has_org ) {
			$nodes[] = self::normalize_organization_schema(
				array(
					'@type' => 'Organization',
					'@id'   => 'https://cindemirlaw.com/#organization',
					'name'  => 'Cindemir Law Office',
					'url'   => 'https://cindemirlaw.com/',
				)
			);
		}
		if ( $is_home && ! $has_website ) {
			$nodes[] = array(
				'@type'      => 'WebSite',
				'@id'        => 'https://cindemirlaw.com/#website',
				'url'        => 'https://cindemirlaw.com/',
				'name'       => 'Cindemir Law Office',
				'publisher'  => array( '@id' => 'https://cindemirlaw.com/#organization' ),
				'inLanguage' => array( 'en', 'ru', 'zh-Hans' ),
			);
		}
		if ( $is_wp_page && ! $is_home && ! $has_webpage ) {
			$canon = '';
			if ( preg_match( '/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $cm ) ) {
				$canon = $cm[1];
			} elseif ( preg_match( '/<meta[^>]+property=["\']og:url["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $om ) ) {
				$canon = $om[1];
			}
			$title = '';
			if ( preg_match( '/<title>(.*?)<\/title>/is', $html, $tm ) ) {
				$title = wp_strip_all_tags( $tm[1] );
			}
			if ( $canon ) {
				$nodes[] = array(
					'@type'     => 'WebPage',
					'@id'       => untrailingslashit( $canon ) . '/#webpage',
					'url'       => $canon,
					'name'      => $title,
					'isPartOf'  => array( '@id' => 'https://cindemirlaw.com/#website' ),
					'about'     => array( '@id' => 'https://cindemirlaw.com/#organization' ),
					'publisher' => array( '@id' => 'https://cindemirlaw.com/#organization' ),
				);
			}
		}
		if ( ! $nodes ) {
			return $html;
		}
		$payload = array(
			'@context' => 'https://schema.org',
			'@graph'   => $nodes,
		);
		$json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $json ) || '' === $json ) {
			return $html;
		}
		$script = "\n<script type=\"application/ld+json\" id=\"cindemir-schema-fix\">" . $json . "</script>\n";
		if ( false !== stripos( $html, '</head>' ) ) {
			return preg_replace( '/<\/head>/i', $script . '</head>', $html, 1 );
		}
		return $html . $script;
	}

	/**
	 * Public wrapper for JSON-LD normalize used by buffer callback.
	 *
	 * @param mixed $data Decoded JSON-LD.
	 * @return mixed
	 */
	public static function normalize_schema_data_public( $data ) {
		if ( is_array( $data ) ) {
			// Root list of Yoast script payloads (graph + Article/Organization).
			$is_list = array_keys( $data ) === range( 0, count( $data ) - 1 );
			if ( $is_list ) {
				foreach ( $data as $i => $item ) {
					$data[ $i ] = self::normalize_schema_node( $item );
				}
				return $data;
			}
			return self::normalize_schema_node( $data );
		}
		return $data;
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
		if ( 'ru' === self::front_lang() ) {
			// Neutral topic padding only — no author/firm solicitation in SERP snippets.
			$suffix = ' Краткий обзор норм и процедур турецкого права.';
		} else {
			$suffix = ' Overview of relevant Turkish law topics, procedures, and legal context for foreign individuals and companies.';
		}
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

		// Ahrefs 404s: RU translation slug + ?lang=ru (bare slug is 200).
		$ru_fix = self::fix_ru_slug_lang_404( $path, $req_q );
		if ( $ru_fix ) {
			wp_redirect( $ru_fix, 301 );
			exit;
		}

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
		$html = self::fix_canonical_html( $html );
		// Title / meta description left to Yoast — no shorten/override/OG sync from maps.
		$html = self::rewrite_ru_article_seo( $html );
		$html = self::rewrite_jsonld_html( $html );
		$html = self::normalize_robots_meta( $html );
		// Final pass after other rewriters — keep menu/site links on active lang.
		$html = self::stamp_lang_on_internal_hrefs( $html );
		$html = self::fix_menu_label_html( $html );
		// Language switcher must point at TARGET language, not the current one.
		$html = self::fix_language_switcher_html( $html );
		$html = self::filter_post_entries_by_lang( $html );
		$html = self::pagespeed_rewrite_html( $html );
		$html = self::polish_homepage_hero_html( $html );
		$html = self::ensure_contact_form_fallback_html( $html );
		// AFTER stamp_lang: that pass otherwise paints ?lang=ru onto en/x-default hreflang hrefs.
		$html = self::fix_hreflang_html( $html );
		return $html;
	}

	/**
	 * Mobile homepage: put the legal team photo in a real image band so faces
	 * are not cropped away by background-size:cover + top-left positioning.
	 *
	 * @param string $html Full page HTML.
	 * @return string
	 */
	private static function polish_homepage_hero_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$is_home = ( function_exists( 'is_front_page' ) && is_front_page() )
			|| false !== strpos( $html, 'post-entry-15' )
			|| false !== strpos( $html, 'page-id-15' )
			|| (bool) preg_match( '/<body[^>]*\bclass=(["\'])[^"\']*\bhome\b/i', $html );
		if ( ! $is_home ) {
			return $html;
		}
		if ( false !== strpos( $html, 'class="cindemir-mobile-hero-photo"' ) || false !== strpos( $html, "class='cindemir-mobile-hero-photo'" ) ) {
			return $html;
		}
		$needle = 'id=\'av_section_1\'';
		$pos    = strpos( $html, $needle );
		if ( false === $pos ) {
			$needle = 'id="av_section_1"';
			$pos    = strpos( $html, $needle );
		}
		if ( false === $pos ) {
			return $html;
		}
		$wrap = strpos( $html, 'av-section-color-overlay-wrap', $pos );
		if ( false === $wrap ) {
			return $html;
		}
		$gt = strpos( $html, '>', $wrap );
		if ( false === $gt ) {
			return $html;
		}
		$src  = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg';
		$band = '<div class="cindemir-mobile-hero-photo">'
			. '<img src="' . esc_url( $src ) . '" alt="Cindemir Law Office legal team in Istanbul" '
			. 'width="800" height="533" decoding="async" fetchpriority="high" />'
			. '</div>';
		return substr( $html, 0, $gt + 1 ) . $band . substr( $html, $gt + 1 );
	}

	/**
	 * Inject/replace an undelayable contact-form submit handler when an Enfold
	 * form is present. Debloat/Rocket often base64-delays or drops the mu-plugin
	 * footer script (especially on RU/ZH contact pages).
	 *
	 * @param string $html Full page HTML.
	 * @return string
	 */
	private static function ensure_contact_form_fallback_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( false === stripos( $html, 'avia_ajax_form' ) ) {
			return $html;
		}
		$original = $html;
		$script   = self::contact_form_fallback_script_tag();
		// Drop any prior copy (inline or Debloat data-URI) so only one handler remains.
		$html = preg_replace(
			'#<script\b[^>]*(?:id=["\']cindemir-contact-form-fallback-js["\']|cindemir-contact-form-fallback)[^>]*>[\s\S]*?</script>#i',
			'',
			$html
		);
		if ( null === $html ) {
			$html = $original;
		}
		if ( false !== stripos( $html, '</body>' ) ) {
			$next = preg_replace( '#</body>#i', $script . "\n</body>", $html, 1 );
			return is_string( $next ) ? $next : ( $html . $script );
		}
		return $html . $script;
	}

	/** ASCII-only contact submit fallback — must not be delayed by Rocket/Debloat. */
	private static function contact_form_fallback_script_tag() {
		$js = <<<'JS'
(function () {
	if (window.__cindemirContactBound) return;
	window.__cindemirContactBound = true;
	function ready(fn) {
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
		else fn();
	}
	ready(function () {
		var forms = document.querySelectorAll('form.avia_ajax_form');
		if (!forms.length) return;

		function qs(form, sel) { return form.querySelector(sel); }
		function qsa(form, sel) { return Array.prototype.slice.call(form.querySelectorAll(sel)); }

		function validate(form) {
			var errors = [];
			qsa(form, 'input[type="text"], input[type="email"], textarea').forEach(function (el) {
				if (el.type === 'hidden' || (el.className || '').indexOf('hidden') !== -1) return;
				var cls = el.className || '';
				var label = form.querySelector('label[for="' + el.id + '"]');
				var name = label ? label.textContent.replace(/\*/g, '').trim() : (el.name || 'field');
				var val = (el.value || '').trim();
				var required = cls.indexOf('is_empty') !== -1 || cls.indexOf('is_email') !== -1 || el.required;
				if (required && !val) { errors.push(name); return; }
				if (cls.indexOf('is_email') !== -1 && val && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) errors.push(name);
				if (cls.indexOf('is_phone') !== -1 && val && val.replace(/\D/g, '').length < 7) errors.push(name);
			});
			return errors;
		}

		function showMessage(form, html, isError) {
			var box = (form.parentElement && form.parentElement.querySelector('.ajaxresponse')) || form.nextElementSibling;
			if (!box || !(box.className || '').match(/ajaxresponse/)) {
				box = document.createElement('div');
				box.className = 'ajaxresponse';
				form.insertAdjacentElement('afterend', box);
			}
			box.classList.remove('hidden');
			box.style.cssText = 'display:block!important;margin:1rem 0;padding:1rem 1.25rem;border-radius:4px;font-size:16px;line-height:1.5;'
				+ (isError
					? 'background:#fdecea;color:#611a15;border:1px solid #f5c2c0;'
					: 'background:#e8f5e9;color:#1b5e20;border:1px solid #a5d6a7;');
			box.innerHTML = isError
				? '<div class="av-form-error-container"><p>' + html + '</p></div>'
				: html;
			try { box.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
			if (!isError) form.style.display = 'none';
		}

		Array.prototype.forEach.call(forms, function (form) {
			if (form.dataset.cindemirBound === '1') return;
			form.dataset.cindemirBound = '1';

			form.addEventListener('submit', function (ev) {
				ev.preventDefault();
				if (ev.stopImmediatePropagation) ev.stopImmediatePropagation();

				var btn = qs(form, 'input[type="submit"], button[type="submit"]');
				var sending = btn && btn.getAttribute('data-sending-label');
				var original = btn ? (btn.value || btn.textContent) : '';
				var errs = validate(form);
				if (errs.length) {
					showMessage(form, 'Please check these fields: ' + errs.join(', '), true);
					return;
				}

				var body = new URLSearchParams();
				body.append('ajax', 'true');
				qsa(form, 'input, textarea, select').forEach(function (el) {
					if (!el.name || el.type === 'submit') return;
					if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
					body.append(el.name, el.value || '');
				});

				if (btn) {
					btn.disabled = true;
					if (btn.value !== undefined) btn.value = sending || 'Sending...';
					else btn.textContent = sending || 'Sending...';
				}

				var action = form.getAttribute('action') || window.location.href;
				var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
				var timeout = setTimeout(function () { if (controller) controller.abort(); }, 25000);
				var opts = {
					method: 'POST',
					body: body,
					credentials: 'same-origin',
					headers: { 'X-Requested-With': 'XMLHttpRequest' }
				};
				if (controller) opts.signal = controller.signal;

				fetch(action, opts)
					.then(function (res) {
						clearTimeout(timeout);
						if (!res.ok) throw new Error('HTTP ' + res.status);
						return res.text();
					})
					.then(function (html) {
						var doc = new DOMParser().parseFromString(html, 'text/html');
						var fragment = doc.querySelector('.ajaxresponse');
						if (!fragment) throw new Error('missing response');
						var msg = fragment.innerHTML;
						var isErr = /av-form-error|error-container/i.test(msg) && !/avia-form-success/i.test(msg);
						showMessage(form, msg, isErr);
						if (!isErr) {
							qsa(form, 'input[type="text"], input[type="email"], textarea').forEach(function (el) {
								if ((el.className || '').indexOf('hidden') === -1) el.value = '';
							});
						}
					})
					.catch(function () {
						showMessage(
							form,
							'Message could not be sent. Please try again or email gokhan@cindemir.av.tr directly.',
							true
						);
					})
					.finally(function () {
						if (btn) {
							btn.disabled = false;
							if (btn.value !== undefined) btn.value = original;
							else btn.textContent = original;
						}
					});
			}, true);
		});
	});
})();
JS;
		return '<script id="cindemir-contact-form-fallback-js" data-nowprocket nowprocket data-no-minify="1" data-no-optimize="1" data-cfasync="false">'
			. "\n" . $js . "\n</script>";
	}

	/**
	 * Last-resort: strip blog list entries whose titles are in the wrong script
	 * for the active front language (covers WP Rocket HTML cache + Avia loops).
	 *
	 * @param string $html Full page HTML.
	 * @return string
	 */
	private static function filter_post_entries_by_lang( $html ) {
		if ( ! is_string( $html ) || '' === $html || false === stripos( $html, 'post-entry' ) ) {
			return $html;
		}
		$lang = self::normalize_front_lang( self::front_lang() );
		return (string) preg_replace_callback(
			'#<article\b[^>]*\bclass="[^"]*\bpost-entry\b[^"]*"[^>]*>.*?</article>#is',
			function ( $m ) use ( $lang ) {
				$block = $m[0];
				$title = '';
				if ( preg_match( '/<(?:h[1-6])[^>]*>\s*<a\b[^>]*>(.*?)<\/a>/is', $block, $tm ) ) {
					$title = wp_strip_all_tags( $tm[1] );
				} elseif ( preg_match( '/aria-label="Post:\s*([^"]+)"/i', $block, $am ) ) {
					$title = html_entity_decode( $am[1], ENT_QUOTES, 'UTF-8' );
				}
				if ( '' === $title ) {
					return $block;
				}
				return self::title_matches_lang( $title, $lang ) ? $block : '';
			},
			$html
		);
	}

	/**
	 * Last-resort rewrite of wrong Chinese menu labels in rendered HTML
	 * (covers WP Rocket full-page cache generation and Avia walkers).
	 */
	private static function fix_menu_label_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$lang = self::front_lang();
		if ( ! in_array( $lang, array( 'zh-hans', 'zh' ), true ) ) {
			return $html;
		}
		$replacements = array(
			'>研讨<'     => '>文章<',
			'>招聘信息<' => '>我们的团队<',
			'>支持<'     => '>媒体报道<',
			'>招聘<'     => '>我们的团队<',
		);
		foreach ( $replacements as $from => $to ) {
			$html = str_replace( $from, $to, $html );
		}
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
		// Keep a stable Latin brand in the header so RU/ZH labels do not inflate
		// the logo row and shove the menu/banner around.
		return 'Cindemir Law Office';
	}

	/**
	 * Friendlier homepage entrance. Applies through Enfold's burger breakpoint (989px)
	 * so phones AND tablets see the team photo; desktop also gets a cleaner crop/copy.
	 */
		/** Keep Enfold team-photo background columns from exploding with non-landscape assets. */
	public static function team_photo_background_fix() {
		echo '<style id="cindemir-team-photo-bg">'
			. '.flex_column.av-kb0bnfzj-6b756727d2887e26a4cf2233375d0c98,'
			. '.flex_column[style*="5295681199059"],'
			. '.avia-section .flex_column[class*="av-"][style*="529568"]{'
			. 'background-size:cover!important;background-position:center center!important;'
			. 'background-repeat:no-repeat!important}'
			. '</style>\n';
	}

public static function homepage_hero_styles() {
		if ( is_admin() ) {
			return;
		}
		if ( ! ( is_front_page() || is_home() ) ) {
			return;
		}
		$photo = esc_url( 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg' );
		echo '<style id="cindemir-home-hero">'
			. '.cindemir-mobile-hero-photo{display:none}'
			/* Phones + tablets (Enfold burger range). */
			. '@media only screen and (max-width:989px){'
			. 'body.home #av_section_1,'
			. 'body.home #av_section_1.avia-section{'
			. 'background-image:none!important;background-color:#1f4f4f!important;'
			. 'background-attachment:scroll!important;min-height:0!important;'
			. 'padding-top:0!important;padding-bottom:0!important}'
			. 'body.home #av_section_1 .av-parallax,'
			. 'body.home #av_section_1 .av-section-color-overlay{display:none!important}'
			. 'body.home #av_section_1 .av-section-color-overlay-wrap{'
			. 'display:flex!important;flex-direction:column!important}'
			. 'body.home #av_section_1 .av-section-color-overlay-wrap:not(:has(.cindemir-mobile-hero-photo))::before{'
			. 'content:"";display:block;width:100%;min-height:52vh;height:52vh;order:-1;'
			. 'background:url(' . $photo . ') center 28%/cover no-repeat}'
			. 'body.home .cindemir-mobile-hero-photo{'
			. 'display:block!important;width:100%;order:-1;line-height:0;margin:0;padding:0}'
			. 'body.home .cindemir-mobile-hero-photo img{'
			. 'display:block;width:100%;height:52vh;min-height:280px;max-height:560px;'
			. 'object-fit:cover;object-position:center 28%}'
			. 'body.home #av_section_1 .container{'
			. 'background:#1f4f4f!important;color:#fff!important;'
			. 'padding:1.1rem 1.15rem 1.5rem!important;width:100%!important;max-width:100%!important}'
			. 'body.home #av_section_1 .av-special-heading{margin-bottom:.35rem!important}'
			. 'body.home #av_section_1 .av-special-heading-tag{'
			. 'font-size:clamp(1.55rem,5.2vw,2.1rem)!important;line-height:1.15!important;color:#fff!important}'
			. 'body.home #av_section_1 .avia_textblock,'
			. 'body.home #av_section_1 .avia_textblock p,'
			. 'body.home #av_section_1 .avia_textblock strong,'
			. 'body.home #av_section_1 .avia_textblock a{color:#fff!important}'
			. 'body.home #av_section_1 .avia_textblock a{'
			. 'text-decoration:none!important;border-bottom:1px solid rgba(255,255,255,.45)}'
			/* One short supporting line only — not a wall of text. */
			. 'body.home #av_section_1 .avia_textblock p:nth-of-type(n+2){display:none!important}'
			. 'body.home #av_section_1 .avia_textblock p:first-of-type{'
			. 'display:-webkit-box!important;-webkit-box-orient:vertical;-webkit-line-clamp:2;'
			. 'overflow:hidden;max-height:3.2em;font-size:1.02rem;line-height:1.45;margin:0 0 .85rem!important;'
			. 'text-align:center!important}'
			. 'body.home #av_section_1 .flex_column.av_one_fifth{display:none!important}'
			. 'body.home #av_section_1 .flex_column.av_three_fifth{'
			. 'width:100%!important;margin:0!important;left:auto!important;right:auto!important}'
			. 'body.home #av_section_1 .hr.hr-invisible,'
			. 'body.home #av_section_1 .hr{display:none!important}'
			. 'body.home #av_section_1 .avia-button-wrap{margin-top:.25rem!important}'
			. 'body.home #av_section_1 .avia-button{'
			. 'background:#fff!important;color:#1f4f4f!important;border-color:#fff!important;'
			. 'padding:.85rem 1.4rem!important}'
			. 'body.home #av_section_1 .avia-button .avia_iconbox_title{'
			. 'color:#1f4f4f!important;font-weight:700!important}'
			. 'body.home #av_section_1 .hr-inner,'
			. 'body.home #av_section_1 .special-heading-inner-border{border-color:rgba(255,255,255,.35)!important}'
			. '#av_section_2 .flex_column_table{'
			. 'display:flex!important;flex-direction:column!important}'
			. '#av_section_2 .flex_column.av-kb0bnfzj-6b756727d2887e26a4cf2233375d0c98{'
			. 'display:block!important;width:100%!important;min-height:260px!important;'
			. 'height:56vw!important;max-height:340px!important;order:-1!important;'
			. 'background-size:cover!important;background-position:center 28%!important;'
			. 'background-repeat:no-repeat!important;margin:0 0 1rem!important}'
			. '#av_section_2 .flex_column.av-kb0bmzrq-e6911c2abcf32f33876fb4dfa882da5a{'
			. 'width:100%!important;padding:0 4%!important}'
			. '@media (prefers-reduced-motion:no-preference){'
			. '@keyframes cindemirHeroIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}'
			. 'body.home .cindemir-mobile-hero-photo img,'
			. 'body.home #av_section_1 .av-section-color-overlay-wrap::before{animation:cindemirHeroIn .65s ease-out both}'
			. 'body.home #av_section_1 .av-special-heading{animation:cindemirHeroIn .65s .1s ease-out both}'
			. 'body.home #av_section_1 .avia_textblock{animation:cindemirHeroIn .65s .18s ease-out both}'
			. 'body.home #av_section_1 .avia-button-wrap{animation:cindemirHeroIn .65s .26s ease-out both}'
			. '}'
			. '}'
			/* Desktop: keep full-bleed team photo, show faces, cut the text wall. */
			. '@media only screen and (min-width:990px){'
			. 'body.home #av_section_1.avia-section{'
			. 'background-position:50% 38%!important;background-size:cover!important;'
			. 'background-attachment:scroll!important;min-height:78vh!important}'
			. 'body.home #av_section_1 .av-section-color-overlay{'
			. 'opacity:1!important;'
			. 'background:linear-gradient(180deg,rgba(0,0,0,.18) 0%,rgba(0,0,0,.42) 42%,rgba(15,40,40,.82) 100%)!important}'
			. 'body.home #av_section_1 .avia_textblock p:nth-of-type(n+3){display:none!important}'
			. 'body.home #av_section_1 .avia_textblock p:nth-of-type(2){'
			. 'display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;max-height:5.2em}'
			. 'body.home #av_section_1 .avia_textblock a{text-decoration:none!important;'
			. 'border-bottom:1px solid rgba(255,255,255,.4)}'
			. '}'
			. '</style>' . "\n";
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
			. 'min-height:64px!important;height:auto!important;max-height:none}'
			. '#top #header .logo{'
			. 'display:flex!important;visibility:visible!important;opacity:1!important;'
			. 'position:relative!important;left:0!important;right:auto!important;float:none!important;'
			. 'z-index:50;align-items:center;flex:0 1 auto;max-width:min(280px,34vw)!important}'
			. '#top #header .logo a{'
			. 'display:inline-flex!important;align-items:center!important;gap:10px!important;'
			. 'text-decoration:none!important;max-height:none!important;height:auto!important;min-width:0!important}'
			. '#top #header .logo img,#top #header .logo picture{'
			. 'display:inline-block!important;max-height:44px!important;width:auto!important;'
			. 'height:auto!important;opacity:1!important;visibility:visible!important;flex:0 0 auto}'
			. '#top #header .logo a::after{'
			. 'content:"' . $label . '"!important;display:inline-block!important;'
			. 'font-family:Georgia,"Times New Roman",serif!important;font-size:18px!important;font-weight:700!important;'
			. 'line-height:1.15!important;color:#244f4f!important;white-space:nowrap;'
			. 'overflow:hidden;text-overflow:ellipsis;max-width:min(200px,26vw)!important}'
			. '#top #header .main_menu{display:block!important;visibility:visible!important;opacity:1!important}'
			. '#top #header .av-burger-menu-main{'
			. 'display:block!important;visibility:visible!important;opacity:1!important;'
			. 'min-width:44px!important;min-height:44px!important;line-height:44px!important}'
			. '#top #header .av-hamburger{display:inline-block!important;visibility:visible!important;'
			. 'min-width:28px!important;min-height:22px!important}'
			. '#top #header .cindemir-site-brand{display:none!important}'
			/* Drop the entire top meta strip (socials + old lang bar). Socials already live in the footer. */
			. '#top #header #header_meta,'
			. '#header_meta{display:none!important;height:0!important;min-height:0!important;overflow:hidden!important;padding:0!important;margin:0!important;border:0!important}'
			. '#top #header .av-main-nav > li.cindemir-lang-item{'
			. 'display:inline-flex!important;align-items:center;margin-left:2px}'
			. '#top #header .av-main-nav > li.cindemir-lang-item:first-of-type,'
			. '#top #header .av-main-nav > li.cindemir-lang-item.cindemir-lang-first{'
			. 'margin-left:14px;padding-left:14px;border-left:1px solid rgba(36,79,79,.18)}'
			. '#top #header .av-main-nav > li.cindemir-lang-item > a{'
			. 'display:inline-flex!important;align-items:center!important;gap:6px!important;'
			. 'padding:0 8px!important;min-height:44px}'
			. '#top #header .av-main-nav > li.cindemir-lang-item .cindemir-lang-flag{'
			. 'display:inline-block;width:18px;height:12px;object-fit:cover;border-radius:2px;'
			. 'box-shadow:0 0 0 1px rgba(0,0,0,.08);flex:0 0 auto}'
			. '#top #header .av-main-nav > li.cindemir-lang-item .avia-menu-text{'
			. 'font-size:12px!important;font-weight:600!important;letter-spacing:.02em;opacity:.88}'
			. '#top #header .av-main-nav > li.cindemir-lang-item.avia_current_lang .avia-menu-text,'
			. '#top #header .av-main-nav > li.cindemir-lang-item.current-menu-item .avia-menu-text{opacity:1;font-weight:700!important}'
			. '#top #header .av-main-nav > li.cindemir-lang-item .avia-menu-fx{display:none!important}'
			. '@media only screen and (min-width:990px){'
			. '#top #header #header_main .inner-container{'
			. 'display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:12px;'
			. 'flex-wrap:nowrap!important;overflow:hidden}'
			. '#top #header .logo a::after{font-size:18px!important;max-width:min(220px,22vw)!important}'
			. '#top #header .main_menu{'
			. 'position:relative!important;left:auto!important;right:auto!important;float:none!important;'
			. 'margin-left:auto!important;flex:1 1 auto;min-width:0;text-align:right!important}'
			. '#top #header .av-main-nav{'
			. 'display:flex!important;flex-wrap:nowrap!important;align-items:center!important;justify-content:flex-end!important}'
			. '#top #header .av-main-nav > li{flex:0 0 auto}'
			. '}'
			. '@media only screen and (max-width:989px){'
			. '#top #header .logo a::after{font-size:14px!important;max-width:min(150px,42vw)!important}'
			. '#top #header .logo{max-width:min(200px,58vw)!important}'
			. '#top #header .logo img{max-height:34px!important;max-width:34px!important}'
			. '#top #header .av-main-nav > li.cindemir-lang-item{display:none!important}'
			/* Enfold forces #header {position:relative} under .responsive — restore sticky so the burger follows scroll. */
			. '.responsive.html_header_sticky #top #wrap_all #header,'
			. '.responsive #top #wrap_all #header.av_header_sticky,'
			. 'html.responsive #top #wrap_all #header{'
			. 'position:fixed!important;top:0!important;left:0!important;right:0!important;'
			. 'width:100%!important;float:none!important;z-index:1001!important;margin:0!important;'
			. 'background-color:#fff!important;box-shadow:0 1px 0 rgba(0,0,0,.06)}'
			. '.responsive.html_header_sticky #top #main,'
			. 'html.responsive #top #main{'
			. 'padding-top:var(--cindemir-header-h,82px)!important;margin-top:0!important}'
			. '.html_av-overlay-active .cindemir-lang-item,'
			. '#av-burger-menu-ul .cindemir-lang-item{'
			. 'display:block!important;border-top:1px solid rgba(0,0,0,.08);margin-top:8px;padding-top:4px}'
			. '.html_av-overlay-active .cindemir-lang-item a,'
			. '#av-burger-menu-ul .cindemir-lang-item a{'
			. 'display:flex!important;align-items:center;gap:10px;font-weight:700!important}'
			. '.html_av-overlay-active .cindemir-lang-flag,'
			. '#av-burger-menu-ul .cindemir-lang-flag{width:22px;height:15px;border-radius:2px}'
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
			. 'var li=ev.target&&ev.target.closest&&ev.target.closest("li[class*=\\"language_\\"],li[class*=\\"wpml-ls-item-\\"],li.cindemir-lang-item");'
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
			. 'function cindemirEnsureBurgerLangs(){'
			. 'var ul=document.querySelector("#av-burger-menu-ul");'
			. 'if(!ul||ul.querySelector(".cindemir-lang-item"))return;'
			. 'var path=location.pathname||"/";'
			. 'var origin=location.origin||"https://cindemirlaw.com";'
			. 'var langs=['
			. '["en","EN",origin+"/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png"],'
			. '["zh-hans","中文",origin+"/wp-content/uploads/flags/china-flag-xs.png"],'
			. '["ru","RU",origin+"/wp-content/plugins/sitepress-multilingual-cms/res/flags/ru.png"]'
			. '];'
			. 'langs.forEach(function(pair,idx){'
			. 'var li=document.createElement("li");'
			. 'li.className="menu-item cindemir-lang-item language_"+pair[0]+(idx===0?" cindemir-lang-first":"");'
			. 'var a=document.createElement("a");'
			. 'a.href=pair[0]==="en"?(path+"?cindemir_lang=en"):(path+((path.indexOf("?")>=0?"&":"?")+"lang="+pair[0]));'
			. 'a.setAttribute("hreflang",pair[0]);'
			. 'a.innerHTML="<img class=\\"cindemir-lang-flag\\" src=\\""+pair[2]+"\\" alt=\\"\\" width=\\"18\\" height=\\"12\\" loading=\\"lazy\\" decoding=\\"async\\"><span class=\\"avia-menu-text\\">"+pair[1]+"</span>";'
			. 'li.appendChild(a);ul.appendChild(li);'
			. '});'
			. '}'
			. 'document.addEventListener("click",function(ev){'
			. 'if(ev.target&&ev.target.closest&&ev.target.closest(".av-burger-menu-main,.av-hamburger")){'
			. 'setTimeout(cindemirEnsureBurgerLangs,50);'
			. '}'
			. '},true);'
			. 'if(document.readyState!=="loading")cindemirEnsureBurgerLangs();'
			. 'else document.addEventListener("DOMContentLoaded",cindemirEnsureBurgerLangs);'
			. 'function cindemirSyncHeaderH(){'
			. 'try{var hdr=document.querySelector("#header");if(!hdr)return;'
			. 'document.documentElement.style.setProperty("--cindemir-header-h",Math.round(hdr.getBoundingClientRect().height||82)+"px");}catch(e){}'
			. '}'
			. 'cindemirSyncHeaderH();'
			. 'if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",cindemirSyncHeaderH);'
			. 'window.addEventListener("resize",cindemirSyncHeaderH);'
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
			. 'if(a.closest&&a.closest(".avia_wpml_language_switch,.wpml-ls-item,.wpml-ls,.cindemir-lang-item"))continue;'
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
			. 'function syncHeaderHeight(){'
			. 'try{'
			. 'var hdr=document.querySelector("#header");'
			. 'if(!hdr)return;'
			. 'var h=Math.round(hdr.getBoundingClientRect().height)||82;'
			. 'document.documentElement.style.setProperty("--cindemir-header-h",h+"px");'
			. '}catch(e){}'
			. '}'
			. 'function run(){fixSwitcher();stampLang();runBrand();syncHeaderHeight();}'
			. 'if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",run);else run();'
			. 'window.addEventListener("resize",syncHeaderHeight);'
			. 'window.addEventListener("orientationchange",syncHeaderHeight);'
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
			. 'if(t.closest&&t.closest(".avia_wpml_language_switch,.wpml-ls-item,.wpml-ls,.cindemir-lang-item"))return;'
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
		$exclude[] = 'cindemir-contact-form-fallback';
		$exclude[] = 'cindemir-whatsapp-fallback';
		$exclude[] = 'cindemir-privacy-form';
		$exclude[] = 'stampLang';
		$exclude[] = 'fixSwitcher';
		$exclude[] = '__cindemirContactBound';
		$exclude[] = 'data-nowprocket';
		$exclude[] = 'avia_ajax_form';
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
		$exclude[] = 'cindemir-contact-form-fallback';
		$exclude[] = '__cindemirContactBound';
		$exclude[] = 'avia_ajax_form';
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

	/**
	 * Correct mistranslated Chinese (and normalize) front menu labels.
	 * Source of truth: EN slugs ↔ av.tr/zh menu wording.
	 */
	private static function menu_title_map() {
		return array(
			'zh-hans' => array(
				'home'      => '首页',
				'about-us'  => '关于我们',
				'articles'  => '文章',
				'services'  => '服务',
				'team'      => '团队',
				'contacts'  => '联系我们',
				'press'     => '媒体',
			),
			'zh'      => array(
				'home'      => '首页',
				'about-us'  => '关于我们',
				'articles'  => '文章',
				'services'  => '服务',
				'team'      => '团队',
				'contacts'  => '联系我们',
				'press'     => '媒体',
			),
			'ru'      => array(
				'home'      => 'Главная',
				'about-us'  => 'О нас',
				'articles'  => 'Статьи',
				'services'  => 'Услуги',
				'team'      => 'Команда',
				'contacts'  => 'Контакты',
				'press'     => 'Пресса',
			),
		);
	}

	/** Map a menu item URL to a stable EN slug key. */
	private static function menu_item_slug_key( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return '';
		}
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$path = untrailingslashit( strtolower( rawurldecode( $path ) ) );
		if ( '' === $path || '/' === $path ) {
			return 'home';
		}
		$base = basename( $path );
		$map  = array(
			'about-us'  => 'about-us',
			'articles'  => 'articles',
			'services'  => 'services',
			'team'      => 'team',
			'contacts'  => 'contacts',
			'contacts-2'=> 'contacts',
			'press'     => 'press',
			'support-zn'=> 'press',
			'support'   => 'press',
		);
		return isset( $map[ $base ] ) ? $map[ $base ] : $base;
	}

	public static function fix_nav_menu_item_title( $title, $item, $args = null, $depth = 0 ) {
		if ( is_admin() ) {
			return $title;
		}
		$lang = self::front_lang();
		$maps = self::menu_title_map();
		if ( ! isset( $maps[ $lang ] ) ) {
			return $title;
		}
		$url = '';
		if ( is_object( $item ) && ! empty( $item->url ) ) {
			$url = $item->url;
		}
		$key = self::menu_item_slug_key( $url );
		if ( $key && isset( $maps[ $lang ][ $key ] ) ) {
			return $maps[ $lang ][ $key ];
		}
		return $title;
	}

	/**
	 * Fix ZH/RU menu item titles and ensure Contacts exists for Chinese menu.
	 *
	 * @param array    $items Menu items.
	 * @param WP_Term  $menu  Menu object.
	 * @param stdClass $args  Args.
	 * @return array
	 */
	public static function fix_nav_menu_items( $items, $menu = null, $args = null ) {
		if ( is_admin() || ! is_array( $items ) || ! $items ) {
			return $items;
		}
		$lang = self::front_lang();
		$maps = self::menu_title_map();
		if ( ! isset( $maps[ $lang ] ) ) {
			return $items;
		}
		$have_contacts = false;
		$max_id        = 0;
		$top_order     = 0;
		foreach ( $items as $item ) {
			if ( ! is_object( $item ) ) {
				continue;
			}
			$max_id = max( $max_id, (int) $item->ID );
			if ( empty( $item->menu_item_parent ) || '0' === (string) $item->menu_item_parent ) {
				$top_order = max( $top_order, (int) $item->menu_order );
			}
			$key = self::menu_item_slug_key( isset( $item->url ) ? $item->url : '' );
			if ( 'contacts' === $key ) {
				$have_contacts = true;
			}
			if ( $key && isset( $maps[ $lang ][ $key ] ) ) {
				$item->title = $maps[ $lang ][ $key ];
				if ( isset( $item->post_title ) ) {
					$item->post_title = $maps[ $lang ][ $key ];
				}
			}
		}
		// Chinese primary menu historically omitted Contacts — add it.
		if ( ! $have_contacts && in_array( $lang, array( 'zh-hans', 'zh' ), true ) ) {
			$new             = new stdClass();
			$new->ID         = $max_id + 91001;
			$new->db_id      = $new->ID;
			$new->object_id  = $new->ID;
			$new->object     = 'custom';
			$new->type       = 'custom';
			$new->type_label = 'Custom';
			$new->title      = $maps[ $lang ]['contacts'];
			$new->url        = self::with_front_lang( 'https://cindemirlaw.com/contacts/' );
			$new->menu_order = $top_order + 1;
			$new->menu_item_parent = 0;
			$new->target     = '';
			$new->attr_title = '';
			$new->description = '';
			$new->classes    = array( 'menu-item', 'menu-item-type-custom', 'menu-item-object-custom', 'menu-item-top-level' );
			$new->xfn        = '';
			$new->status     = 'publish';
			$new->post_parent = 0;
			$items[]         = $new;
		}
		return $items;
	}

	/**
	 * Append compact flag + code language entries to the primary Avia menu.
	 * Meta-bar WPML flags are hidden; languages live in the main nav / burger.
	 */
	public static function append_lang_items_to_menu( $items, $args ) {
		if ( is_admin() || ! is_string( $items ) ) {
			return $items;
		}
		$menu_id    = isset( $args->menu_id ) ? (string) $args->menu_id : '';
		$menu_class = isset( $args->menu_class ) ? (string) $args->menu_class : '';
		$theme_loc  = isset( $args->theme_location ) ? (string) $args->theme_location : '';
		$is_main    = ( 'avia-menu' === $menu_id )
			|| false !== strpos( $menu_class, 'av-main-' )
			|| false !== strpos( $menu_class, 'avia-menu' )
			|| in_array( $theme_loc, array( 'avia', 'primary', 'main' ), true );
		if ( ! $is_main ) {
			return $items;
		}
		if ( false !== strpos( $items, 'cindemir-lang-item' ) ) {
			return $items;
		}
		$current = self::front_lang();
		$path    = self::path();
		$path    = ( ! $path || '/' === $path ) ? '/' : user_trailingslashit( $path );
		$base    = 'https://cindemirlaw.com' . ( '/' === $path ? '/' : $path );
		$langs   = array(
			'en'      => array(
				'label' => 'EN',
				'url'   => self::language_target_url( 'en', $base ),
				'flag'  => 'https://cindemirlaw.com/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png',
			),
			'zh-hans' => array(
				'label' => '中文',
				'url'   => self::language_target_url( 'zh-hans', $base ),
				'flag'  => 'https://cindemirlaw.com/wp-content/uploads/flags/china-flag-xs.png',
			),
			'ru'      => array(
				'label' => 'RU',
				'url'   => self::language_target_url( 'ru', $base ),
				'flag'  => 'https://cindemirlaw.com/wp-content/plugins/sitepress-multilingual-cms/res/flags/ru.png',
			),
		);
		$html  = '';
		$first = true;
		foreach ( $langs as $code => $info ) {
			$active = ( $current === $code || ( 'zh' === $current && 'zh-hans' === $code ) ) ? ' avia_current_lang current-menu-item' : '';
			$extra  = $first ? ' cindemir-lang-first' : '';
			$first  = false;
			$html  .= '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-top-level cindemir-lang-item language_' . esc_attr( $code ) . $extra . $active . '">'
				. '<a href="' . esc_attr( $info['url'] ) . '" hreflang="' . esc_attr( $code ) . '">'
				. '<img class="cindemir-lang-flag" src="' . esc_attr( $info['flag'] ) . '" alt="" width="18" height="12" loading="lazy" decoding="async" />'
				. '<span class="avia-menu-text">' . esc_html( $info['label'] ) . '</span>'
				. '</a></li>';
		}
		return $items . $html;
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

	public static function footer_meta_styles() {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="cindemir-footer-meta-css">'
			. '#socket .container{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:4px 12px;padding-top:10px;padding-bottom:10px}'
			. '#socket .copyright{display:block;width:100%;text-align:center;margin:0;line-height:1.35}'
			. '#socket .cindemir-footer-meta{display:block;width:100%;margin:0;padding:0;font-size:12px;line-height:1.4;text-align:center;opacity:1}'
			. '#socket .cindemir-footer-meta a{color:inherit;text-decoration:underline;text-underline-offset:2px}'
			. '#socket .cindemir-footer-meta a:hover{opacity:.85}'
			. '#socket .cindemir-footer-meta .cindemir-footer-note{opacity:.95}'
			. '@media (max-width:767px){#socket .container{padding-top:12px;padding-bottom:12px}#socket .cindemir-footer-meta{font-size:11px}}'
			. '</style>' . "\n";
	}

	/** Compact footer row inside theme socket — KVKK link + short note only. */
	public static function render_compact_footer_meta() {
		if ( is_admin() ) {
			return;
		}
		$s = self::privacy_strings();
		?>
<div class="cindemir-footer-meta" hidden aria-hidden="true">
	<a href="<?php echo esc_url( self::with_front_lang( home_url( '/privacy-policy/' ) ) ); ?>"><?php echo esc_html( $s['link'] ); ?></a>
	<span class="cindemir-footer-note"> <?php echo esc_html( $s['note'] ); ?></span>
</div>
<script>
(function () {
	var meta = document.querySelector('.cindemir-footer-meta');
	var socket = document.querySelector('#socket .container');
	if (!meta || !socket) return;
	meta.removeAttribute('hidden');
	meta.setAttribute('aria-hidden', 'false');
	socket.appendChild(meta);
})();
</script>
		<?php
	}

	/** Short KVKK / privacy disclosure labels (no consent checkbox). */
	private static function privacy_strings() {
		$lang = self::front_lang();
		$map  = array(
			'en'      => array(
				'link' => 'KVKK / Privacy Policy',
				'note' => '— Personal data is processed under KVKK. Essential cookies only.',
			),
			'ru'      => array(
				'link' => 'KVKK / Политика конфиденциальности',
				'note' => '— Персональные данные обрабатываются по KVKK. Только необходимые cookie.',
			),
			'zh-hans' => array(
				'link' => 'KVKK / 隐私政策',
				'note' => '— 个人数据依 KVKK 处理。仅使用必要 Cookie。',
			),
			'zh'      => array(
				'link' => 'KVKK / 隐私政策',
				'note' => '— 个人数据依 KVKK 处理。仅使用必要 Cookie。',
			),
		);
		if ( isset( $map[ $lang ] ) ) {
			return $map[ $lang ];
		}
		return $map['en'];
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
		// Protect alternate/hreflang link tags — stamping would collapse en + ru to the same URL.
		$hreflang_slots = array();
		$html             = preg_replace_callback(
			'#<link\b[^>]*\bhreflang=[^>]*>#i',
			static function ( $m ) use ( &$hreflang_slots ) {
				$key                    = '<!--CINDEMIR_HREFLANG_' . count( $hreflang_slots ) . '-->';
				$hreflang_slots[ $key ] = $m[0];
				return $key;
			},
			$html
		);
		if ( ! is_string( $html ) ) {
			return '';
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
				// RU translation slugs / Cyrillic permalinks break when ?lang=ru is appended.
				$path = (string) ( wp_parse_url( $url, PHP_URL_PATH ) ?: '' );
				$path = rawurldecode( $path );
				$key  = untrailingslashit( $path );
				if ( array_key_exists( $key, self::$ru_slug_to_en ) || preg_match( '/[А-Яа-яЁё]/u', $path ) ) {
					$en = array_key_exists( $key, self::$ru_slug_to_en ) ? self::$ru_slug_to_en[ $key ] : null;
					if ( null !== $en && 'ru' === $lang ) {
						$stamped++;
						return 'href=' . $m[1] . esc_attr( 'https://cindemirlaw.com' . user_trailingslashit( $en ) . '?lang=ru' ) . $m[1];
					}
					return $m[0];
				}
				$stamped++;
				return 'href=' . $m[1] . esc_attr( self::raw_append_lang( $url, $lang ) ) . $m[1];
			},
			$html
		);
		if ( null === $out ) {
			return $hreflang_slots
				? str_replace( array_keys( $hreflang_slots ), array_values( $hreflang_slots ), $html )
				: $html;
		}
		if ( $hreflang_slots ) {
			$out = str_replace( array_keys( $hreflang_slots ), array_values( $hreflang_slots ), $out );
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
			$dest = self::$redirects[ $path ];
			// Cyrillic bare pages are already 200 RU. Mapping them to ?lang=ru
			// loops with fix_ru_slug_lang_404() which strips lang=ru again.
			if ( is_string( $dest ) && preg_match( '/[А-Яа-яЁё]/u', $path ) && false !== strpos( $dest, 'lang=ru' ) ) {
				return false;
			}
			return $dest;
		}
		// Already has a language query — do not re-target (Cyrillic+lang=ru is a 404).
		if ( $query && false !== strpos( $query, 'lang=' ) ) {
			return false;
		}
		// Do not auto-append ?lang=ru to unknown /fde* paths — many 404 with lang=.
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
			'#<link\b(?=[^>]*\bhreflang=)(?=[^>]*\bhref=)[^>]*>#i',
			static function ( $m ) {
				$tag = $m[0];
				if ( ! preg_match( '#\bhreflang=(["\'])([^"\']+)\1#i', $tag, $lm ) ) {
					return $tag;
				}
				if ( ! preg_match( '#\bhref=(["\'])([^"\']+)\1#i', $tag, $um ) ) {
					return $tag;
				}
				$lang = strtolower( $lm[2] );
				$url  = self::normalize_hreflang_url( $um[2], $lang );
				// Omit bogus en targets (no real EN page / would only 301→ru).
				if ( ! is_string( $url ) || '' === $url ) {
					return '';
				}
				return '<link rel="alternate" hreflang="' . esc_attr( $lang ) . '" href="' . esc_attr( $url ) . '" />';
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
				$is_ru = ( 'ru' === self::front_lang() );
				$len   = function_exists( 'mb_strlen' ) ? mb_strlen( $raw ) : strlen( $raw );
				if ( $len <= 60 ) {
					return '<title>' . esc_html( $raw ) . '</title>';
				}
				$brand = $is_ru ? 'Cindemir' : 'Cindemir Law Office';
				$base  = preg_replace( '/\s*[-|–—]\s*Cindemir(?: Law Office)?\s*$/u', '', $raw );
				$base  = preg_replace( '/\s*\|\s*Cindemir(?: Law Office)?\s*$/u', '', $base );
				$base  = trim( $base );
				// Leave more room for Cyrillic titles; brand is short.
				$max   = $is_ru ? 50 : 55;
				$blen  = function_exists( 'mb_strlen' ) ? mb_strlen( $base ) : strlen( $base );
				if ( $blen > $max ) {
					$cut = function_exists( 'mb_substr' ) ? mb_substr( $base, 0, $max ) : substr( $base, 0, $max );
					$pos = function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );
					if ( false !== $pos && $pos > (int) ( $max * 0.55 ) ) {
						$cut = function_exists( 'mb_substr' ) ? mb_substr( $cut, 0, $pos ) : substr( $cut, 0, $pos );
					}
					$base = rtrim( $cut, ' :,—-–—' ) . '…';
				}
				$sep = $is_ru ? ' | ' : ' - ';
				$new = $base . $sep . $brand;
				$new_len = function_exists( 'mb_strlen' ) ? mb_strlen( $new ) : strlen( $new );
				if ( $new_len > 60 ) {
					$keep = 60 - ( function_exists( 'mb_strlen' ) ? mb_strlen( $sep . $brand ) : strlen( $sep . $brand ) ) - 1;
					if ( $keep < 24 ) {
						$keep = 24;
					}
					$cut = function_exists( 'mb_substr' ) ? mb_substr( $base, 0, $keep ) : substr( $base, 0, $keep );
					$pos = function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );
					if ( false !== $pos && $pos > 16 ) {
						$cut = function_exists( 'mb_substr' ) ? mb_substr( $cut, 0, $pos ) : substr( $cut, 0, $pos );
					}
					$new = rtrim( $cut, ' :,—-–—' ) . '…' . $sep . $brand;
				}
				return '<title>' . esc_html( $new ) . '</title>';
			},
			$html,
			1
		);
	}


	public static function filter_page_title( $title ) {
		$id = self::current_page_id_for_seo();
		if ( $id && isset( self::$page_titles[ $id ] ) ) {
			return self::$page_titles[ $id ];
		}
		return $title;
	}

	public static function filter_page_og_title( $title ) {
		$id = self::current_page_id_for_seo();
		if ( $id && isset( self::$page_titles[ $id ] ) ) {
			return self::$page_titles[ $id ];
		}
		$seo = self::filter_page_title( $title );
		return ( is_string( $seo ) && '' !== trim( $seo ) ) ? $seo : $title;
	}

	public static function filter_page_og_desc( $desc ) {
		$id = self::current_page_id_for_seo();
		if ( $id && isset( self::$page_metadesc[ $id ] ) ) {
			return self::$page_metadesc[ $id ];
		}
		$meta = $id ? get_post_meta( $id, '_yoast_wpseo_metadesc', true ) : '';
		if ( is_string( $meta ) && '' !== trim( $meta ) ) {
			return $meta;
		}
		return $desc;
	}


	/** Overwrite physical /robots.txt if present (Bluehost static file bypasses robots_txt filter). */
	public static function maybe_rewrite_static_robots() {
		if ( get_option( 'cindemir_robots_txt_v1935' ) ) {
			return;
		}
		$path = trailingslashit( ABSPATH ) . 'robots.txt';
		$body = "User-agent: *\nAllow: /\n\n"
			. "User-agent: OAI-SearchBot\nAllow: /\n\n"
			. "User-agent: GPTBot\nAllow: /\n\n"
			. "User-agent: ChatGPT-User\nAllow: /\n\n"
			. "User-agent: AhrefsBot\nAllow: /\n\n"
			. "User-agent: Yandex\nAllow: /\n\n"
			. "Sitemap: https://cindemirlaw.com/sitemap_index.xml\n";
		if ( file_exists( $path ) ) {
			if ( ! @unlink( $path ) ) {
				@file_put_contents( $path, $body );
			}
		}
		update_option( 'cindemir_robots_txt_v1935', 1, false );
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
	}

	public static function filter_robots_txt( $output, $public ) {
		$sitemap = 'Sitemap: https://cindemirlaw.com/sitemap_index.xml';
		$output  = preg_replace( '/^Sitemap:\s*.+$/mi', '', (string) $output );
		return trim( $output ) . "\n\n" . $sitemap . "\n";
	}

	public static function exclude_utility_from_sitemap( $ids ) {
		if ( ! is_array( $ids ) ) {
			$ids = array();
		}
		foreach ( array( 'antimanual-assistant', 'embed-list', 'press' ) as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page ) {
				$ids[] = (int) $page->ID;
			}
		}
		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}

	public static function apply_title_overrides_once() {
		if ( get_option( 'cindemir_seo_titles_v1934_applied' ) ) {
			return;
		}
		foreach ( self::$page_titles as $id => $title ) {
			if ( get_post( $id ) ) {
				update_post_meta( (int) $id, '_yoast_wpseo_title', $title );
			}
		}
		update_option( 'cindemir_seo_titles_v1934_applied', 1, false );
	}

	private static function current_page_id_for_seo() {
		if ( function_exists( 'is_front_page' ) && is_front_page() ) {
			$front = (int) get_option( 'page_on_front' );
			if ( $front ) {
				return $front;
			}
		}
		return function_exists( 'get_queried_object_id' ) ? (int) get_queried_object_id() : 0;
	}

	private static function apply_title_overrides_html( $html ) {
		$id = self::current_page_id_for_seo();
		if ( ! $id || ! isset( self::$page_titles[ $id ] ) ) {
			return $html;
		}
		$title = esc_html( self::$page_titles[ $id ] );
		return preg_replace( '/<title>.*?<\/title>/is', '<title>' . $title . '</title>', $html, 1 );
	}

	private static function fix_og_tags_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$title = '';
		if ( preg_match( '/<title>(.*?)<\/title>/is', $html, $tm ) ) {
			$title = trim( wp_strip_all_tags( html_entity_decode( $tm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
		}
		$desc = '';
		if ( preg_match( '/<meta\s+name=(["\'])description\1[^>]*content=(["\'])(.*?)\2/is', $html, $dm ) ) {
			$desc = html_entity_decode( $dm[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		} elseif ( preg_match( '/<meta\s+content=(["\'])(.*?)\1[^>]*name=(["\'])description\3/is', $html, $dm ) ) {
			$desc = html_entity_decode( $dm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}
		$id = self::current_page_id_for_seo();
		if ( $id && isset( self::$page_titles[ $id ] ) ) {
			$title = self::$page_titles[ $id ];
		}
		if ( $id && isset( self::$page_metadesc[ $id ] ) ) {
			$desc = self::$page_metadesc[ $id ];
		}
		if ( '' === $title && '' === $desc ) {
			return $html;
		}
		if ( '' !== $title ) {
			$et = esc_attr( $title );
			$html = self::upsert_meta_tag( $html, 'property', 'og:title', $et );
			$html = self::upsert_meta_tag( $html, 'name', 'twitter:title', $et );
		}
		if ( '' !== $desc ) {
			$ed = esc_attr( $desc );
			$html = self::upsert_meta_tag( $html, 'property', 'og:description', $ed );
			$html = self::upsert_meta_tag( $html, 'name', 'twitter:description', $ed );
		}
		return $html;
	}

	private static function upsert_meta_tag( $html, $attr, $key, $content ) {
		$pattern = '/<meta\s+' . $attr . '=(["\'])' . preg_quote( $key, '/' ) . '\1[^>]*>/i';
		$tag     = '<meta ' . $attr . '="' . esc_attr( $key ) . '" content="' . $content . '" />';
		if ( preg_match( $pattern, $html ) ) {
			return preg_replace( $pattern, $tag, $html, 1 );
		}
		$pattern2 = '/<meta\s+content=(["\'])[^"\']*\1[^>]*' . $attr . '=(["\'])' . preg_quote( $key, '/' ) . '\2[^>]*>/i';
		if ( preg_match( $pattern2, $html ) ) {
			return preg_replace( $pattern2, $tag, $html, 1 );
		}
		if ( preg_match( '/<\/head>/i', $html ) ) {
			return preg_replace( '/<\/head>/i', $tag . "\n</head>", $html, 1 );
		}
		return $html;
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

	public static function boot_services_emergency() {
		add_action( 'init', array( __CLASS__, 'install_services_plugin_from_jsdelivr' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'undo_broken_services_hide_css' ), 99 );
	}

	/** If Services redesign hide-CSS is active without markup, restore visibility. */
	public static function undo_broken_services_hide_css() {
		if ( ! function_exists( 'is_page' ) || ! is_page( array( 18, 2638, 2637, 56 ) ) ) {
			return;
		}
		echo '<style id="cindemir-services-undo">#top.page-id-18 #main > *,#top.page-id-2638 #main > *,#top.page-id-2637 #main > *,#top.page-id-56 #main > *{display:revert!important}</style>';
	}

	/** Write/overwrite services mu-plugin from jsDelivr (avoids Bluehost raw.githubusercontent staleness). */
	public static function install_services_plugin_from_jsdelivr() {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return;
		}
		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-services-page.php';
		$need = true;
		if ( file_exists( $dest ) && filesize( $dest ) > 10000 ) {
			$local = file_get_contents( $dest );
			if ( is_string( $local ) && false !== strpos( $local, 'SERVICES_BLANK_FIX_20260715' ) && false !== strpos( $local, "VERSION = '1.0.3'" ) ) {
				$need = false;
			}
		}
		if ( ! $need ) {
			return;
		}
		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$urls   = array(
			'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/cindemir-services-page.php?v=SERVICES_BLANK_FIX_20260715',
			'https://cdn.jsdelivr.net/gh/gcindemir/cindemir@' . $branch . '/fixes/mu-plugins/cindemir-services-page.php',
		);
		$body = '';
		foreach ( $urls as $url ) {
			$response = wp_remote_get( $url, array( 'timeout' => 45, 'headers' => array( 'User-Agent' => 'CindemirServicesInstall/' . self::VERSION, 'Cache-Control' => 'no-cache' ) ) );
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}
			$tmp = (string) wp_remote_retrieve_body( $response );
			if ( strlen( $tmp ) < 10000 || false === strpos( $tmp, 'Cindemir_Services_Page' ) || false === strpos( $tmp, 'SERVICES_BLANK_FIX_20260715' ) ) {
				continue;
			}
			$body = $tmp;
			break;
		}
		if ( '' === $body ) {
			return;
		}
		file_put_contents( $dest, $body );
		if ( function_exists( 'opcache_invalidate' ) ) {
			@opcache_invalidate( $dest, true );
		}
	}


}

Cindemir_SEO_Fixes::boot();
Cindemir_SEO_Fixes::boot_services_emergency();

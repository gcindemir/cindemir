<?php
/**
 * Plugin Name: Cindemir Header Brand i18n
 * Description: Makes the header firm name follow the active language (EN/RU/ZH/TR) instead of always showing "Cindemir Law Office".
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_HEADER_BRAND_I18N_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_HEADER_BRAND_I18N_LOADED', true );

final class Cindemir_Header_Brand_I18n {

	const VERSION = '1.0.0';

	/** @var array<string, string> */
	private static $labels = array(
		'en'      => 'Cindemir Law Office',
		'ru'      => 'Юридическая фирма Cindemir',
		'zh-hans' => '辛德米尔律师事务所',
		'zh'      => '辛德米尔律师事务所',
		'tr'      => 'Cindemir Hukuk Bürosu',
	);

	public static function boot() {
		add_action( 'wp_head', array( __CLASS__, 'print_styles' ), 999 );
		add_action( 'wp_footer', array( __CLASS__, 'print_script' ), 5 );
	}

	public static function current_lang() {
		if ( ! empty( $_GET['lang'] ) ) {
			return self::normalize_lang( sanitize_key( wp_unslash( $_GET['lang'] ) ) );
		}
		if ( ! empty( $_GET['cindemir_lang'] ) ) {
			return self::normalize_lang( sanitize_key( wp_unslash( $_GET['cindemir_lang'] ) ) );
		}
		if ( ! empty( $_COOKIE['cindemir_lang'] ) ) {
			return self::normalize_lang( sanitize_key( wp_unslash( $_COOKIE['cindemir_lang'] ) ) );
		}
		$wpml = apply_filters( 'wpml_current_language', null );
		if ( is_string( $wpml ) && '' !== $wpml ) {
			return self::normalize_lang( $wpml );
		}
		if ( function_exists( 'pll_current_language' ) ) {
			$pll = pll_current_language( 'slug' );
			if ( is_string( $pll ) && '' !== $pll ) {
				return self::normalize_lang( $pll );
			}
		}
		if ( defined( 'ICL_LANGUAGE_CODE' ) && is_string( ICL_LANGUAGE_CODE ) && '' !== ICL_LANGUAGE_CODE ) {
			return self::normalize_lang( ICL_LANGUAGE_CODE );
		}
		$locale = strtolower( (string) get_locale() );
		return self::normalize_lang( $locale );
	}

	public static function normalize_lang( $lang ) {
		$lang = strtolower( str_replace( '_', '-', (string) $lang ) );
		if ( 0 === strpos( $lang, 'tr' ) ) {
			return 'tr';
		}
		if ( 0 === strpos( $lang, 'zh' ) ) {
			return 'zh-hans';
		}
		if ( 0 === strpos( $lang, 'ru' ) ) {
			return 'ru';
		}
		if ( 0 === strpos( $lang, 'en' ) ) {
			return 'en';
		}
		return $lang;
	}

	public static function label_for( $lang = null ) {
		$lang = $lang ? self::normalize_lang( $lang ) : self::current_lang();
		if ( isset( self::$labels[ $lang ] ) ) {
			return self::$labels[ $lang ];
		}
		return self::$labels['en'];
	}

	public static function print_styles() {
		if ( is_admin() ) {
			return;
		}
		$label = self::label_for();
		$css_label = str_replace( array( '\\', '"', "\n", "\r" ), array( '\\\\', '\\"', '', '' ), $label );
		?>
<style id="cindemir-header-brand-i18n">
/* Override hardcoded English brand text from cindemir-header-brand */
#top #header .logo a::after,
#header .logo a::after {
	content: "<?php echo esc_attr( $css_label ); ?>" !important;
}
</style>
		<?php
	}

	public static function print_script() {
		if ( is_admin() ) {
			return;
		}
		$lang  = self::current_lang();
		$label = self::label_for( $lang );
		$map   = self::$labels;
		?>
<script id="cindemir-header-brand-i18n-js" data-no-optimize="1" data-cfasync="false" data-no-defer="1" data-no-minify="1">
(function () {
	var lang = <?php echo wp_json_encode( $lang ); ?>;
	var label = <?php echo wp_json_encode( $label ); ?>;
	var map = <?php echo wp_json_encode( $map ); ?>;
	function pick() {
		var code = lang;
		try {
			var htmlLang = (document.documentElement.lang || '').toLowerCase();
			if (htmlLang.indexOf('ru') === 0) code = 'ru';
			else if (htmlLang.indexOf('zh') === 0) code = 'zh-hans';
			else if (htmlLang.indexOf('tr') === 0) code = 'tr';
			else if (htmlLang.indexOf('en') === 0) code = 'en';
		} catch (e) {}
		return map[code] || map.en || label;
	}
	function apply() {
		var text = pick();
		document.querySelectorAll('.cindemir-site-brand__text, .cindemir-mobile-brand').forEach(function (el) {
			el.textContent = text;
		});
		document.querySelectorAll('.cindemir-site-brand, #header .logo a').forEach(function (el) {
			el.setAttribute('aria-label', text);
			var img = el.querySelector('img');
			if (img) img.setAttribute('alt', text);
		});
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', apply);
	} else {
		apply();
	}
	window.addEventListener('load', apply);
})();
</script>
		<?php
	}
}

Cindemir_Header_Brand_I18n::boot();

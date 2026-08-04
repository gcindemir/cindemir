<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Strengthen weak front-page meta / Open Graph descriptions.
 */
class Cindemir_GEO_Meta {

	public static function init() {
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'metadesc' ), 20 );
		add_filter( 'wpseo_opengraph_desc', array( __CLASS__, 'metadesc' ), 20 );
		add_filter( 'document_title_parts', array( __CLASS__, 'title_parts' ), 20 );
	}

	public static function descriptions() {
		return array(
			'tr' => 'Cindemir Hukuk Bürosu, İstanbul merkezli hukuk bürosudur. Ticaret, miras, yabancılar hukuku, tazminat ve uluslararası davalarda Türkçe, İngilizce ve Rusça hizmet.',
			'en' => 'Cindemir Law Office is an Istanbul-based international law firm serving clients worldwide. Corporate, immigration, real estate, family, arbitration — in English, Turkish, Russian and Chinese.',
			'ru' => 'Адвокатское бюро Cindemir в Стамбуле: русскоязычные адвокаты для граждан РФ и СНГ — ВНЖ, недвижимость, компании, наследство и признание решений судов РФ.',
			'zh' => 'Cindemir律师事务所位于伊斯坦布尔，为中国公民与华语客户提供居留、房产、公司设立及跨境法律服务。',
		);
	}

	public static function locale_key() {
		if ( class_exists( 'Cindemir_GEO_Schema' ) ) {
			return Cindemir_GEO_Schema::current_locale_key();
		}
		return 'tr';
	}

	public static function metadesc( $desc ) {
		if ( ! is_front_page() && ! is_home() ) {
			return $desc;
		}
		$weak = (
			! is_string( $desc )
			|| '' === trim( $desc )
			|| false !== strpos( $desc, 'Anasayfa' )
			|| $desc === 'Cindemir Law Office'
			|| mb_strlen( $desc ) < 40
		);
		if ( ! $weak ) {
			return $desc;
		}
		$map = self::descriptions();
		$key = self::locale_key();
		return isset( $map[ $key ] ) ? $map[ $key ] : $map['tr'];
	}

	public static function title_parts( $parts ) {
		if ( ! is_front_page() ) {
			return $parts;
		}
		$key = self::locale_key();
		$titles = array(
			'tr' => 'Cindemir Hukuk Bürosu | İstanbul avukatlık bürosu',
			'en' => 'Cindemir Law Office | International lawyers in Istanbul',
			'ru' => 'Адвокатское бюро Джиндемир | Русскоязычный адвокат Стамбул',
			'zh' => 'Cindemir律师事务所 | 土耳其中文律师 伊斯坦布尔',
		);
		if ( isset( $titles[ $key ] ) ) {
			$parts['title'] = $titles[ $key ];
			$parts['site']  = '';
			$parts['tagline'] = '';
		}
		return $parts;
	}
}

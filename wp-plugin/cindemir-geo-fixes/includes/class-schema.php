<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prints locale-specific JSON-LD and suppresses conflicting Organization/LegalService graphs.
 */
class Cindemir_GEO_Schema {

	public static function init() {
		// Disable SASWP frontend JSON-LD (duplicate empty Organization).
		add_filter( 'saswp_disable_schema_markup', array( __CLASS__, 'disable_saswp' ), 10, 1 );
		add_filter( 'saswp_modify_organization_output', '__return_empty_array', 99 );

		// Disable Meta Tag Manager front-page Organization schema.
		add_filter( 'mtm_get_data', array( __CLASS__, 'strip_mtm_schema_flag' ), 10, 2 );
		add_action( 'wp', array( __CLASS__, 'maybe_disable_mtm_schema' ), 1 );

		// Remove Elementor/page-embedded LegalService/Organization graphs that conflict.
		add_filter( 'the_content', array( __CLASS__, 'strip_embedded_business_schema' ), 20 );
		add_filter( 'elementor/frontend/the_content', array( __CLASS__, 'strip_embedded_business_schema' ), 20 );

		// Canonical locale schema.
		add_action( 'wp_head', array( __CLASS__, 'print_schema' ), 5 );

		// Team pages: reinforce Person graph.
		add_action( 'wp_head', array( __CLASS__, 'print_team_schema' ), 6 );

		// Landing FAQ schemas.
		add_action( 'wp_head', array( __CLASS__, 'print_landing_faq_schema' ), 7 );
	}

	public static function disable_saswp( $conditionals ) {
		// Empty / false short-circuits saswp_schema_output().
		return false;
	}

	public static function maybe_disable_mtm_schema() {
		add_action( 'wp_head', array( __CLASS__, 'ob_start_head' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'ob_end_head' ), 999 );
	}

	public static function ob_start_head() {
		ob_start( array( __CLASS__, 'ob_strip_mtm_organization' ) );
	}

	public static function strip_mtm_schema_flag( $meta_tags, $mtm_data ) {
		return $meta_tags;
	}

	public static function ob_strip_mtm_organization( $html ) {
		// Remove Meta Tag Manager / theme Organization-only JSON-LD in the buffered head chunk.
		return preg_replace_callback(
			'/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>/is',
			static function ( $m ) {
				$chunk = $m[0];
				if ( false !== stripos( $chunk, 'saswp-schema-markup-output' ) ) {
					return '';
				}
				if ( preg_match( '/"@type"\s*:\s*"Organization"/', $chunk )
					&& false === stripos( $chunk, 'LegalService' )
					&& false === stripos( $chunk, '@graph' ) ) {
					return '';
				}
				return $chunk;
			},
			$html
		);
	}

	public static function ob_end_head() {
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}

	public static function strip_embedded_business_schema( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}
		return preg_replace_callback(
			'/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>/is',
			static function ( $m ) {
				$chunk = $m[0];
				$is_business = ( false !== stripos( $chunk, 'LegalService' ) )
					|| ( false !== stripos( $chunk, '#lawoffice' ) )
					|| (
						preg_match( '/"@type"\s*:\s*"Organization"/', $chunk )
						&& false === stripos( $chunk, 'Person' )
						&& false === stripos( $chunk, 'FAQPage' )
					);
				if ( $is_business ) {
					return '<!-- cindemir-geo: stripped embedded business schema -->';
				}
				return $chunk;
			},
			$content
		);
	}

	public static function current_locale_key() {
		if ( function_exists( 'pll_current_language' ) ) {
			$slug = pll_current_language( 'slug' );
			if ( $slug ) {
				return $slug;
			}
		}
		$locale = get_locale();
		if ( 0 === strpos( $locale, 'ru' ) ) {
			return 'ru';
		}
		if ( 0 === strpos( $locale, 'zh' ) ) {
			return 'zh';
		}
		if ( 0 === strpos( $locale, 'en' ) ) {
			return 'en';
		}
		return 'tr';
	}

	public static function schema_file_for_locale( $key ) {
		$map = array(
			'tr' => 'homepage-tr-lawoffice.json',
			'ru' => 'homepage-ru-lawoffice.json',
			'en' => 'homepage-en-lawoffice.json',
			'zh' => 'homepage-zh-lawoffice.json',
		);
		$file = isset( $map[ $key ] ) ? $map[ $key ] : $map['tr'];
		$path = CINDEMIR_GEO_DIR . 'schema/' . $file;
		return file_exists( $path ) ? $path : '';
	}

	public static function print_json_file( $path ) {
		if ( ! $path || ! file_exists( $path ) ) {
			return;
		}
		$raw = file_get_contents( $path );
		$data = json_decode( $raw, true );
		if ( ! $data ) {
			return;
		}
		echo '<script type="application/ld+json" class="cindemir-geo-schema">'
			. wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			. "</script>\n";
	}

	public static function print_schema() {
		if ( is_admin() ) {
			return;
		}
		$key  = self::current_locale_key();
		$path = self::schema_file_for_locale( $key );
		if ( ! $path || ! file_exists( $path ) ) {
			return;
		}
		$raw  = file_get_contents( $path );
		$data = json_decode( $raw, true );
		if ( ! $data ) {
			return;
		}
		// On non-front pages keep entity NAP; drop homepage WebPage node to avoid wrong @id.
		if ( ! is_front_page() && isset( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
			$data['@graph'] = array_values(
				array_filter(
					$data['@graph'],
					static function ( $node ) {
						return ! ( is_array( $node ) && isset( $node['@type'] ) && 'WebPage' === $node['@type'] );
					}
				)
			);
		}
		echo '<script type="application/ld+json" class="cindemir-geo-schema">'
			. wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			. "</script>\n";
	}

	public static function print_team_schema() {
		if ( ! is_page() ) {
			return;
		}
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		$team_slugs = array( 'avukatlarimiz', 'our-team', 'our-team-ru', 'our-team-zn' );
		if ( ! in_array( $slug, $team_slugs, true ) ) {
			return;
		}
		self::print_json_file( CINDEMIR_GEO_DIR . 'schema/team-persons.json' );
	}

	public static function print_landing_faq_schema() {
		if ( ! is_page() ) {
			return;
		}
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		$map  = array(
			'rusca-bilen-avukat'              => 'rusca-bilen-avukat-faq.json',
			'russkoyazychnyy-advokat-stambul' => 'russkoyazychnyy-advokat-faq.json',
			'international-lawyer-turkey'    => 'international-lawyer-en-faq.json',
			'zhongwen-lushi-tuerqi'           => 'zhongwen-lushi-zh-faq.json',
		);
		if ( ! isset( $map[ $slug ] ) ) {
			return;
		}
		self::print_json_file( CINDEMIR_GEO_DIR . 'schema/' . $map[ $slug ] );
	}
}

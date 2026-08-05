<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates locale intent landing pages (idempotent).
 */
class Cindemir_GEO_Landings {

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_ensure_pages' ) );
	}

	public static function maybe_ensure_pages() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( get_option( 'cindemir_geo_landings_v1' ) === CINDEMIR_GEO_VERSION ) {
			return;
		}
		self::ensure_pages();
		update_option( 'cindemir_geo_landings_v1', CINDEMIR_GEO_VERSION );
	}

	public static function definitions() {
		return array(
			array(
				'slug'     => 'rusca-bilen-avukat',
				'lang'     => 'tr',
				'title'    => "Türkiye'de Rusça bilen avukat | İstanbul",
				'file'     => 'rusca-bilen-avukat.tr.html',
				'parent'   => 0,
			),
			array(
				'slug'     => 'russkoyazychnyy-advokat-stambul',
				'lang'     => 'ru',
				'title'    => 'Русскоязычный адвокат в Турции | Стамбул',
				'file'     => 'russkoyazychnyy-advokat.ru.html',
				'parent'   => 0,
			),
			array(
				'slug'     => 'international-lawyer-turkey',
				'lang'     => 'en',
				'title'    => 'International lawyer in Turkey | Istanbul',
				'file'     => 'international-lawyer.en.html',
				'parent'   => 0,
			),
			array(
				'slug'     => 'zhongwen-lushi-tuerqi',
				'lang'     => 'zh',
				'title'    => '土耳其中文律师 | 伊斯坦布尔',
				'file'     => 'zhongwen-lushi.zh.html',
				'parent'   => 0,
			),
		);
	}

	public static function load_page_html( $file ) {
		$path = CINDEMIR_GEO_DIR . 'pages/' . $file;
		if ( ! file_exists( $path ) ) {
			return '';
		}
		$html = file_get_contents( $path );
		// Strip HTML comment header block.
		$html = preg_replace( '/^<!--.*?-->\s*/s', '', $html, 1 );
		return $html;
	}

	public static function ensure_pages() {
		$created = array();
		foreach ( self::definitions() as $def ) {
			$existing = get_page_by_path( $def['slug'] );
			if ( $existing ) {
				$created[ $def['lang'] ] = (int) $existing->ID;
				continue;
			}

			// Polylang: prefer creating under language-specific URL prefix via pll_set_post_language.
			$content = self::load_page_html( $def['file'] );
			$page_id = wp_insert_post(
				array(
					'post_title'   => $def['title'],
					'post_name'    => $def['slug'],
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_author'  => 1,
				),
				true
			);
			if ( is_wp_error( $page_id ) || ! $page_id ) {
				continue;
			}
			if ( function_exists( 'pll_set_post_language' ) ) {
				pll_set_post_language( $page_id, $def['lang'] );
			}
			$created[ $def['lang'] ] = (int) $page_id;
		}

		// Link TR↔RU translations if Polylang available.
		if ( function_exists( 'pll_save_post_translations' )
			&& ! empty( $created['tr'] )
			&& ! empty( $created['ru'] ) ) {
			pll_save_post_translations(
				array(
					'tr' => $created['tr'],
					'ru' => $created['ru'],
				)
			);
		}

		return $created;
	}
}

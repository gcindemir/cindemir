<?php
/**
 * Plugin Name: Cindemir Restore RU Pages
 * Description: One-shot restore of empty official RU WPML pages from orphan Russian Avia layouts. Self-disables after success.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_RESTORE_RU_PAGES_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_RESTORE_RU_PAGES_LOADED', true );

/**
 * Official RU translation ID => orphan page that still holds Russian Avia content.
 *
 * @return array<int,int>
 */
function cindemir_restore_ru_page_map() {
	return array(
		2    => 2630, // О нас
		56   => 2638, // Услуги
		105  => 2634, // Статьи
		2427 => 2643, // Команда
		2446 => 2647, // Контакты
	);
}

/**
 * Meta keys required for Enfold Avia Layout Builder pages.
 *
 * @return string[]
 */
function cindemir_restore_ru_avia_meta_keys() {
	return array(
		'_aviaLayoutBuilderCleanData',
		'_aviaLayoutBuilder_active',
		'_avia_sc_parser_state',
		'_avia_builder_shortcode_tree',
		'layout',
		'_thumbnail_id',
	);
}

/**
 * Copy content + Avia meta from orphan source into the official RU page.
 *
 * @param int $dst Destination (official RU) post ID.
 * @param int $src Source orphan post ID.
 * @return bool True when content was written.
 */
function cindemir_restore_ru_copy_page( $dst, $src ) {
	$dst = (int) $dst;
	$src = (int) $src;
	$src_post = get_post( $src );
	$dst_post = get_post( $dst );
	if ( ! $src_post || ! $dst_post || $src_post->post_status !== 'publish' ) {
		return false;
	}

	$src_content = (string) $src_post->post_content;
	$src_avia    = get_post_meta( $src, '_aviaLayoutBuilderCleanData', true );
	if ( ! is_string( $src_avia ) || $src_avia === '' ) {
		$src_avia = $src_content;
	}
	if ( $src_avia === '' && $src_content === '' ) {
		return false;
	}

	// Skip if destination already has real Avia content.
	$dst_avia = get_post_meta( $dst, '_aviaLayoutBuilderCleanData', true );
	$dst_len  = is_string( $dst_avia ) ? strlen( $dst_avia ) : strlen( (string) $dst_post->post_content );
	if ( $dst_len > 500 ) {
		return false;
	}

	$content = $src_content !== '' ? $src_content : $src_avia;
	// Normalize curly quotes that break Contact Form 7 shortcodes.
	$content = str_replace(
		array( "\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", '″', '′' ),
		array( '"', '"', "'", "'", '"', "'" ),
		$content
	);
	$src_avia_norm = str_replace(
		array( "\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", '″', '′' ),
		array( '"', '"', "'", "'", '"', "'" ),
		is_string( $src_avia ) ? $src_avia : $content
	);

	wp_update_post(
		array(
			'ID'           => $dst,
			'post_content' => $content,
		)
	);

	foreach ( cindemir_restore_ru_avia_meta_keys() as $key ) {
		if ( $key === '_aviaLayoutBuilderCleanData' ) {
			update_post_meta( $dst, $key, $src_avia_norm );
			continue;
		}
		if ( metadata_exists( 'post', $src, $key ) ) {
			update_post_meta( $dst, $key, get_post_meta( $src, $key, true ) );
		}
	}

	if ( ! get_post_meta( $dst, '_aviaLayoutBuilder_active', true ) ) {
		update_post_meta( $dst, '_aviaLayoutBuilder_active', 'active' );
	}

	return true;
}

/**
 * Run restore once, then mark done.
 */
function cindemir_restore_ru_pages_run() {
	if ( get_option( 'cindemir_restore_ru_pages_done' ) === '1.0.0' ) {
		return;
	}
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		return;
	}

	$done = array();
	foreach ( cindemir_restore_ru_page_map() as $dst => $src ) {
		if ( cindemir_restore_ru_copy_page( $dst, $src ) ) {
			$done[] = $dst;
		}
	}

	update_option( 'cindemir_restore_ru_pages_done', '1.0.0', false );
	update_option( 'cindemir_restore_ru_pages_log', $done, false );

	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
}

add_action( 'init', 'cindemir_restore_ru_pages_run', 20 );

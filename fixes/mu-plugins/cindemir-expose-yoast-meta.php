<?php
/**
 * Plugin Name: Cindemir – Expose Yoast Meta to REST (pages)
 * Description: Yoast REST expose + one-shot download of cindemir-seo-fixes.php v1.7.0 from GitHub when missing.
 * Author: Cindemir Law
 * Version: 1.3
 * SERVICES_BLANK_FIX_20260715 + TEAM_PHOTO_SYNC_20260718A
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Emergency: stop broken Services rewrite (pre-1.0.3) that returned empty HTML.
add_action(
	'template_redirect',
	static function () {
		if ( ! class_exists( 'Cindemir_Services_Page', false ) ) {
			return;
		}
		if ( version_compare( (string) Cindemir_Services_Page::VERSION, '1.0.3', '>=' ) ) {
			return;
		}
		remove_action( 'template_redirect', array( 'Cindemir_Services_Page', 'start_buffer' ), 2 );
		remove_action( 'template_redirect', array( 'Cindemir_Services_Page', 'start_buffer' ), 0 );
		remove_action( 'wp_head', array( 'Cindemir_Services_Page', 'print_assets' ), 40 );
		$GLOBALS['cindemir_services_rescue_active'] = true;
	},
	-1
);
add_action(
	'wp_head',
	static function () {
		if ( empty( $GLOBALS['cindemir_services_rescue_active'] ) ) {
			return;
		}
		if ( ! function_exists( 'is_page' ) || ! is_page( array( 18, 2638, 2637, 56 ) ) ) {
			return;
		}
		echo '<style id="cindemir-services-rescue-undo">#top.page-id-18 #main > *,#top.page-id-2638 #main > *,#top.page-id-2637 #main > *,#top.page-id-56 #main > *{display:revert!important}</style>';
	},
	99
);

add_action( 'init', function () {
	$fields = array(
		'_yoast_wpseo_metadesc',
		'_yoast_wpseo_title',
		'_yoast_wpseo_focuskw',
	);

	foreach ( array( 'page', 'post' ) as $ptype ) {
		foreach ( $fields as $key ) {
			register_post_meta( $ptype, $key, array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			) );
		}
	}
}, 5 );

add_action( 'init', function () {
	if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
		return;
	}

	$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-seo-fixes.php';
	if ( ! $dest ) {
		return;
	}

	$local_version = '';
	if ( file_exists( $dest ) && filesize( $dest ) > 30000 ) {
		$local = file_get_contents( $dest );
		if ( is_string( $local ) && preg_match( "/const\s+VERSION\s*=\s*'([^']+)'/", $local, $m ) ) {
			$local_version = $m[1];
		}
	}

	$targets = array(
		'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/cindemir-seo-fixes.php',
		'https://raw.githubusercontent.com/gcindemir/cindemir/master/fixes/mu-plugins/cindemir-seo-fixes.php',
	);

	$body = '';
	$remote_version = '';
	foreach ( $targets as $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array( 'User-Agent' => 'CindemirRemoteDeploy/1.2' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			continue;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$tmp  = (string) wp_remote_retrieve_body( $response );
		if ( 200 !== $code || strlen( $tmp ) < 30000 || false !== strpos( $tmp, 'collapsed' ) ) {
			continue;
		}
		if ( ! preg_match( "/const\s+VERSION\s*=\s*'([^']+)'/", $tmp, $m ) ) {
			continue;
		}
		if ( '' !== $local_version && version_compare( $m[1], $local_version, '<=' ) ) {
			update_option( 'cindemir_remote_deploy_done', 1, false );
			return;
		}
		$body           = $tmp;
		$remote_version = $m[1];
		break;
	}

	if ( '' === $body ) {
		return;
	}

	if ( false === file_put_contents( $dest, $body ) ) {
		return;
	}

	update_option( 'cindemir_remote_deploy_done', 1, false );
	delete_option( 'cindemir_seo_fixes_version' );
	flush_rewrite_rules( false );

	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
	if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
		WPSEO_Sitemaps_Cache::clear();
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
}, 1 );

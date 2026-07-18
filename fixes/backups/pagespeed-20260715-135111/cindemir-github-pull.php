<?php
/**
 * Plugin Name: Cindemir GitHub Pull (one-shot)
 * Description: Downloads mu-plugins from GitHub when missing or empty.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	if ( get_option( 'cindemir_github_pull_v1_done' ) ) {
		return;
	}

	if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
		return;
	}

	$base = 'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/';
	$files = array(
		'cindemir-seo-fixes.php'      => 40000,
		'cindemir-expose-yoast-meta.php' => 2000,
		'cindemir-contact-fixes.php'  => 20000,
	);

	$ok = 0;
	foreach ( $files as $name => $min ) {
		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . $name;
		if ( file_exists( $dest ) && filesize( $dest ) > $min ) {
			$ok++;
			continue;
		}
		$response = wp_remote_get(
			$base . $name,
			array(
				'timeout' => 60,
				'headers' => array( 'User-Agent' => 'CindemirGitHubPull/1.0' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			continue;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		if ( 200 !== $code || strlen( $body ) < $min || false !== strpos( $body, 'collapsed' ) ) {
			continue;
		}
		if ( false !== file_put_contents( $dest, $body ) ) {
			$ok++;
		}
	}

	if ( $ok < count( $files ) ) {
		return;
	}

	update_option( 'cindemir_github_pull_v1_done', 1, false );
	delete_option( 'cindemir_seo_fixes_version' );
	flush_rewrite_rules( false );
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
}, 1 );

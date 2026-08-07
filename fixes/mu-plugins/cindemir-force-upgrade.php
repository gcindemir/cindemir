<?php
/**
 * Plugin Name: Cindemir Force Upgrade (one-shot)
 * Description: Pulls latest mu-plugins from GitHub; removes itself after success.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	function () {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return;
		}

		$key = isset( $_GET['cindemir_upgrade'] ) ? sanitize_text_field( wp_unslash( $_GET['cindemir_upgrade'] ) ) : '';
		if ( 'seo-pack-2026' !== $key && get_option( 'cindemir_force_upgrade_done' ) ) {
			return;
		}

		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$base   = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/';
		$files  = array(
			'cindemir-seo-fixes.php'         => 40000,
			'cindemir-contact-fixes.php'     => 20000,
			'cindemir-expose-yoast-meta.php' => 2000,
			'cindemir-purge-cache.php'       => 500,
		);

		$ok = 0;
		foreach ( $files as $name => $min ) {
			$response = wp_remote_get(
				$base . $name,
				array(
					'timeout' => 60,
					'headers' => array( 'User-Agent' => 'CindemirForceUpgrade/1.0' ),
				)
			);
			if ( is_wp_error( $response ) ) {
				continue;
			}
			$body  = (string) wp_remote_retrieve_body( $response );
			$bytes = strlen( $body );
			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || $bytes < $min ) {
				continue;
			}
			$dest = trailingslashit( WPMU_PLUGIN_DIR ) . $name;
			if ( false !== file_put_contents( $dest, $body ) ) {
				$ok++;
			}
		}

		if ( $ok < count( $files ) ) {
			return;
		}

		delete_option( 'cindemir_seo_fixes_version' );
		delete_option( 'cindemir_remote_deploy_done' );
		update_option( 'cindemir_force_upgrade_done', 1, false );

		$self = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-force-upgrade.php';
		if ( file_exists( $self ) ) {
			@unlink( $self );
		}

		flush_rewrite_rules( false );
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
	},
	0
);

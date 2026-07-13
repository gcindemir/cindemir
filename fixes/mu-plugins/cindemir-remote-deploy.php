<?php
/**
 * Plugin Name: Cindemir Remote Deploy (one-shot)
 * Description: Downloads all mu-plugins from GitHub; removes itself after success.
 * Version: 1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	function () {
		if ( get_option( 'cindemir_remote_deploy_v11_done' ) ) {
			return;
		}
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
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
					'headers' => array( 'User-Agent' => 'CindemirRemoteDeploy/1.1' ),
				)
			);
			if ( is_wp_error( $response ) ) {
				continue;
			}
			$body = (string) wp_remote_retrieve_body( $response );
			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || strlen( $body ) < $min ) {
				continue;
			}
			if ( false !== file_put_contents( trailingslashit( WPMU_PLUGIN_DIR ) . $name, $body ) ) {
				$ok++;
			}
		}

		if ( $ok < count( $files ) ) {
			return;
		}

		update_option( 'cindemir_remote_deploy_v11_done', 1, false );
		delete_option( 'cindemir_remote_deploy_done' );
		delete_option( 'cindemir_seo_fixes_version' );

		$self = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-remote-deploy.php';
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

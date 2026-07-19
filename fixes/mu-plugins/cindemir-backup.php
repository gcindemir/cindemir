<?php
/**
 * Plugin Name: Cindemir Daily Backup Status
 * Description: REST status for daily SSH/cron backups (does not create backups in PHP).
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'cindemir/v1',
			'/backup-status',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'permission_callback' => '__return_true',
				'callback'            => static function ( $request ) {
					$key = $request->get_param( 'key' );
					if ( 'seo-pack-2026' !== $key ) {
						return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
					}

					$path = trailingslashit( WP_CONTENT_DIR ) . 'cindemir-backup-latest.json';
					if ( ! is_readable( $path ) ) {
						return new WP_REST_Response(
							array(
								'ok'      => false,
								'error'   => 'no-status-file',
								'hint'    => 'Run scripts/backup/run-daily-backup.sh or install server cron.',
								'path'    => 'wp-content/cindemir-backup-latest.json',
							),
							404
						);
					}

					$raw = file_get_contents( $path );
					$data = json_decode( (string) $raw, true );
					if ( ! is_array( $data ) ) {
						return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid-json' ), 500 );
					}

					// Age check (warn if older than 36 hours).
					$utc   = isset( $data['utc'] ) ? (string) $data['utc'] : '';
					$age_h = null;
					if ( $utc ) {
						$ts = strtotime( $utc );
						if ( $ts ) {
							$age_h = round( ( time() - $ts ) / 3600, 1 );
						}
					}
					$data['age_hours'] = $age_h;
					$data['stale']     = ( null !== $age_h && $age_h > 36 );

					return new WP_REST_Response( $data, 200 );
				},
			)
		);
	}
);

// Block direct HTTP access to the status JSON if the web server serves wp-content files.
add_action(
	'init',
	static function () {
		$deny = trailingslashit( WP_CONTENT_DIR ) . '.htaccess-cindemir-backup';
		$ht   = trailingslashit( WP_CONTENT_DIR ) . '.htaccess';
		$marker = '# cindemir-backup-latest';
		if ( ! file_exists( $ht ) ) {
			@file_put_contents(
				$ht,
				$marker . "\n<Files \"cindemir-backup-latest.json\">\nRequire all denied\n</Files>\n"
			);
			return;
		}
		$cur = (string) file_get_contents( $ht );
		if ( false === strpos( $cur, $marker ) ) {
			@file_put_contents(
				$ht,
				rtrim( $cur ) . "\n\n" . $marker . "\n<Files \"cindemir-backup-latest.json\">\nRequire all denied\n</Files>\n"
			);
		}
	},
	20
);

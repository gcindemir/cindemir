<?php
/**
 * Plugin Name: Cindemir Daily Backup (WordPress)
 * Description: Daily WP-Cron backups of database + critical files into wp-content/cindemir-backups/.
 * Version: 1.1.0
 * ELENA_ZARA_RU_BIO_20260718
 * SCHEMA_FIX_20260718
 * BACKUP_WP_CRON_20260719
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_BACKUP_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_BACKUP_LOADED', true );

/**
 * WordPress-native daily backups (no SSH required).
 */
final class Cindemir_Backup {
	const VERSION     = '1.1.0';
	const CRON_HOOK   = 'cindemir_daily_backup_event';
	const OPTION_LAST = 'cindemir_backup_last';
	const KEEP_DAYS   = 14;
	const REST_KEY    = 'seo-pack-2026';

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'ensure_schedule' ), 20 );
		add_action( 'init', array( __CLASS__, 'ensure_deny_htaccess' ), 21 );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_backup' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest' ) );
		// Catch missed cron on front traffic (shared hosting often disables real cron).
		add_action( 'init', array( __CLASS__, 'maybe_run_if_due' ), 30 );
	}

	/** @return string Absolute backup root (not web-listable). */
	public static function backup_root() {
		$dir = trailingslashit( WP_CONTENT_DIR ) . 'cindemir-backups';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		return $dir;
	}

	public static function ensure_deny_htaccess() {
		$root = self::backup_root();
		$ht   = trailingslashit( $root ) . '.htaccess';
		if ( ! file_exists( $ht ) ) {
			@file_put_contents( $ht, "Require all denied\nDeny from all\n" );
		}
		$idx = trailingslashit( $root ) . 'index.php';
		if ( ! file_exists( $idx ) ) {
			@file_put_contents( $idx, "<?php\n// Silence.\n" );
		}
		// Also protect status JSON in wp-content root.
		$marker = '# cindemir-backup-latest';
		$wc_ht  = trailingslashit( WP_CONTENT_DIR ) . '.htaccess';
		$rule   = $marker . "\n<Files \"cindemir-backup-latest.json\">\nRequire all denied\n</Files>\n";
		if ( ! file_exists( $wc_ht ) ) {
			@file_put_contents( $wc_ht, $rule );
		} else {
			$cur = (string) file_get_contents( $wc_ht );
			if ( false === strpos( $cur, $marker ) ) {
				@file_put_contents( $wc_ht, rtrim( $cur ) . "\n\n" . $rule );
			}
		}
	}

	public static function ensure_schedule() {
		if ( wp_next_scheduled( self::CRON_HOOK ) ) {
			return;
		}
		// ~02:00 site-local (wp timezone).
		$ts = self::next_two_am_timestamp();
		wp_schedule_event( $ts, 'daily', self::CRON_HOOK );
	}

	/** @return int */
	private static function next_two_am_timestamp() {
		$tz  = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$now = new DateTimeImmutable( 'now', $tz );
		$run = $now->setTime( 2, 0, 0 );
		if ( $run <= $now ) {
			$run = $run->modify( '+1 day' );
		}
		return $run->getTimestamp();
	}

	/**
	 * If last backup older than 26h and WP-Cron is lazy, run once (locked).
	 */
	public static function maybe_run_if_due() {
		if ( is_admin() && ! wp_doing_cron() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			// Still allow on front; skip heavy admin screens only when not cron.
		}
		$last = get_option( self::OPTION_LAST, array() );
		$utc  = is_array( $last ) && ! empty( $last['utc'] ) ? strtotime( (string) $last['utc'] ) : 0;
		if ( $utc && ( time() - $utc ) < DAY_IN_SECONDS + 2 * HOUR_IN_SECONDS ) {
			return;
		}
		if ( get_transient( 'cindemir_backup_lock' ) ) {
			return;
		}
		// Only once per request path for anonymous/cron traffic.
		if ( ! wp_doing_cron() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			// Probability throttle: 1 in 20 page views when overdue, to avoid pile-up.
			if ( wp_rand( 1, 20 ) !== 1 ) {
				return;
			}
		}
		self::run_backup();
	}

	public static function register_rest() {
		register_rest_route(
			'cindemir/v1',
			'/backup-status',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_status' ),
			)
		);
		register_rest_route(
			'cindemir/v1',
			'/backup-run',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_run' ),
			)
		);
		register_rest_route(
			'cindemir/v1',
			'/backup-list',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_list' ),
			)
		);
	}

	private static function require_key( $request ) {
		$key = $request->get_param( 'key' );
		return self::REST_KEY === $key;
	}

	public static function rest_status( $request ) {
		if ( ! self::require_key( $request ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		$last = get_option( self::OPTION_LAST, null );
		$path = trailingslashit( WP_CONTENT_DIR ) . 'cindemir-backup-latest.json';
		$file = is_readable( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : null;
		$data = is_array( $file ) ? $file : ( is_array( $last ) ? $last : array() );
		$utc  = isset( $data['utc'] ) ? strtotime( (string) $data['utc'] ) : 0;
		$age  = $utc ? round( ( time() - $utc ) / 3600, 1 ) : null;
		return new WP_REST_Response(
			array(
				'ok'           => ! empty( $data['ok'] ),
				'version'      => self::VERSION,
				'engine'       => 'wordpress-cron',
				'next_cron'    => wp_next_scheduled( self::CRON_HOOK ),
				'age_hours'    => $age,
				'stale'        => ( null !== $age && $age > 36 ),
				'last'         => $data,
				'backup_root'  => 'wp-content/cindemir-backups/',
			),
			200
		);
	}

	public static function rest_list( $request ) {
		if ( ! self::require_key( $request ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		$root = self::backup_root();
		$dirs = glob( trailingslashit( $root ) . '20*', GLOB_ONLYDIR );
		$out  = array();
		if ( is_array( $dirs ) ) {
			rsort( $dirs );
			foreach ( array_slice( $dirs, 0, 30 ) as $d ) {
				$status = trailingslashit( $d ) . 'STATUS.json';
				$item   = array(
					'date' => basename( $d ),
					'path' => 'wp-content/cindemir-backups/' . basename( $d ),
				);
				if ( is_readable( $status ) ) {
					$j = json_decode( (string) file_get_contents( $status ), true );
					if ( is_array( $j ) ) {
						$item['status'] = $j;
					}
				}
				$out[] = $item;
			}
		}
		return new WP_REST_Response( array( 'ok' => true, 'backups' => $out ), 200 );
	}

	public static function rest_run( $request ) {
		if ( ! self::require_key( $request ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		$result = self::run_backup();
		$code   = ! empty( $result['ok'] ) ? 200 : 500;
		return new WP_REST_Response( $result, $code );
	}

	/**
	 * Create today's backup. Safe to call from cron or REST.
	 *
	 * @return array
	 */
	public static function run_backup() {
		if ( get_transient( 'cindemir_backup_lock' ) ) {
			return array( 'ok' => false, 'error' => 'locked' );
		}
		set_transient( 'cindemir_backup_lock', 1, 15 * MINUTE_IN_SECONDS );

		@set_time_limit( 300 );
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}

		$date = gmdate( 'Y-m-d' );
		$dir  = trailingslashit( self::backup_root() ) . $date;
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$utc     = gmdate( 'c' );
		$files   = array();
		$errors  = array();

		// 1) Database
		$db_file = trailingslashit( $dir ) . 'database.sql.gz';
		$db_ok   = self::export_database_gz( $db_file );
		if ( $db_ok ) {
			$files['database.sql.gz'] = filesize( $db_file );
		} else {
			$errors[] = 'database-export-failed';
		}

		// 2) wp-config
		$cfg_src = ABSPATH . 'wp-config.php';
		$cfg_dst = trailingslashit( $dir ) . 'wp-config.php.gz';
		if ( is_readable( $cfg_src ) ) {
			$raw = file_get_contents( $cfg_src );
			if ( is_string( $raw ) && self::write_gz( $cfg_dst, $raw ) ) {
				$files['wp-config.php.gz'] = filesize( $cfg_dst );
			} else {
				$errors[] = 'wp-config-failed';
			}
		}

		// 3) Critical code dirs (mu-plugins + plugins + themes) — skip bulky uploads by default.
		$zip_path = trailingslashit( $dir ) . 'code.zip';
		$zip_ok   = self::zip_code_dirs( $zip_path );
		if ( $zip_ok ) {
			$files['code.zip'] = filesize( $zip_path );
		} else {
			$errors[] = 'code-zip-failed';
		}

		// 4) Optional uploads zip (best-effort; may skip if too large / ZipArchive missing).
		$uploads_zip = trailingslashit( $dir ) . 'uploads.zip';
		$up_ok       = self::zip_uploads( $uploads_zip );
		if ( $up_ok ) {
			$files['uploads.zip'] = filesize( $uploads_zip );
		}

		$manifest = array(
			'site'    => home_url( '/' ),
			'date'    => $date,
			'utc'     => $utc,
			'version' => self::VERSION,
			'wp'      => get_bloginfo( 'version' ),
			'php'     => PHP_VERSION,
			'files'   => $files,
			'errors'  => $errors,
		);
		file_put_contents( trailingslashit( $dir ) . 'manifest.json', wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

		$total = 0;
		foreach ( $files as $b ) {
			$total += (int) $b;
		}
		$status = array(
			'ok'          => $db_ok && empty( $errors ),
			'site'        => 'cindemirlaw.com',
			'date'        => $date,
			'utc'         => $utc,
			'engine'      => 'wordpress-cron',
			'version'     => self::VERSION,
			'dir'         => 'wp-content/cindemir-backups/' . $date,
			'files'       => $files,
			'total_bytes' => $total,
			'errors'      => $errors,
			'keep_days'   => self::KEEP_DAYS,
		);

		file_put_contents( trailingslashit( $dir ) . 'STATUS.json', wp_json_encode( $status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		file_put_contents( trailingslashit( WP_CONTENT_DIR ) . 'cindemir-backup-latest.json', wp_json_encode( $status ) );
		update_option( self::OPTION_LAST, $status, false );

		self::prune_old_backups();
		delete_transient( 'cindemir_backup_lock' );

		return $status;
	}

	/** @return bool */
	private static function write_gz( $path, $data ) {
		$gz = gzopen( $path, 'wb9' );
		if ( ! $gz ) {
			return false;
		}
		gzwrite( $gz, $data );
		gzclose( $gz );
		return file_exists( $path ) && filesize( $path ) > 0;
	}

	/**
	 * Dump all tables to gzipped SQL.
	 *
	 * @param string $path Destination .sql.gz
	 * @return bool
	 */
	private static function export_database_gz( $path ) {
		global $wpdb;
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return false;
		}

		$gz = gzopen( $path, 'wb9' );
		if ( ! $gz ) {
			return false;
		}

		gzwrite( $gz, "-- Cindemir WP backup " . gmdate( 'c' ) . "\n" );
		gzwrite( $gz, "SET NAMES utf8mb4;\nSET foreign_key_checks = 0;\n\n" );

		$tables = $wpdb->get_col( 'SHOW TABLES' );
		if ( ! is_array( $tables ) || ! $tables ) {
			gzclose( $gz );
			return false;
		}

		foreach ( $tables as $table ) {
			$table = (string) $table;
			$create = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
			if ( ! is_array( $create ) || empty( $create[1] ) ) {
				continue;
			}
			gzwrite( $gz, "DROP TABLE IF EXISTS `{$table}`;\n" );
			gzwrite( $gz, $create[1] . ";\n\n" );

			$offset = 0;
			$chunk  = 200;
			while ( true ) {
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` LIMIT %d OFFSET %d", $chunk, $offset ), ARRAY_A );
				if ( ! is_array( $rows ) || ! $rows ) {
					break;
				}
				foreach ( $rows as $row ) {
					$vals = array();
					foreach ( $row as $v ) {
						if ( null === $v ) {
							$vals[] = 'NULL';
						} else {
							$vals[] = "'" . $wpdb->_real_escape( (string) $v ) . "'";
						}
					}
					$cols = '`' . implode( '`,`' , array_keys( $row ) ) . '`';
					gzwrite( $gz, "INSERT INTO `{$table}` ({$cols}) VALUES (" . implode( ',', $vals ) . ");\n" );
				}
				$offset += $chunk;
				if ( count( $rows ) < $chunk ) {
					break;
				}
			}
			gzwrite( $gz, "\n" );
		}

		gzwrite( $gz, "SET foreign_key_checks = 1;\n" );
		gzclose( $gz );
		return file_exists( $path ) && filesize( $path ) > 1000;
	}

	/**
	 * Zip mu-plugins, plugins, themes.
	 *
	 * @param string $zip_path Destination.
	 * @return bool
	 */
	private static function zip_code_dirs( $zip_path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return self::fallback_tar_gz_code( $zip_path );
		}
		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return false;
		}
		$dirs = array(
			'mu-plugins' => trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins',
			'plugins'    => trailingslashit( WP_CONTENT_DIR ) . 'plugins',
			'themes'     => trailingslashit( WP_CONTENT_DIR ) . 'themes',
		);
		foreach ( $dirs as $prefix => $abs ) {
			if ( ! is_dir( $abs ) ) {
				continue;
			}
			self::zip_add_dir( $zip, $abs, $prefix );
		}
		$zip->close();
		return file_exists( $zip_path ) && filesize( $zip_path ) > 100;
	}

	/**
	 * @param string $zip_path Destination uploads.zip
	 * @return bool
	 */
	private static function zip_uploads( $zip_path ) {
		$uploads = wp_upload_dir();
		$basedir = isset( $uploads['basedir'] ) ? $uploads['basedir'] : '';
		if ( ! $basedir || ! is_dir( $basedir ) ) {
			return false;
		}
		// Skip if uploads tree is huge (> 400MB) to avoid shared-host timeouts.
		$size = self::dir_size_bytes( $basedir, 450 * 1024 * 1024 );
		if ( $size < 0 ) {
			return false;
		}
		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}
		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return false;
		}
		self::zip_add_dir( $zip, $basedir, 'uploads', array( 'wpcf7_uploads', 'wc-logs', 'wc-exports' ) );
		$zip->close();
		return file_exists( $zip_path ) && filesize( $zip_path ) > 0;
	}

	/**
	 * @param ZipArchive $zip Zip handle.
	 * @param string     $abs Absolute dir.
	 * @param string     $prefix Path prefix inside zip.
	 * @param array      $skip_dir_names Directory basenames to skip.
	 */
	private static function zip_add_dir( $zip, $abs, $prefix, $skip_dir_names = array() ) {
		$abs = rtrim( $abs, '/\\' );
		$iter = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $abs, FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $iter as $file ) {
			/** @var SplFileInfo $file */
			if ( ! $file->isFile() ) {
				continue;
			}
			$path = $file->getPathname();
			$rel  = substr( $path, strlen( $abs ) + 1 );
			$rel  = str_replace( '\\', '/', $rel );
			$parts = explode( '/', $rel );
			$skip  = false;
			foreach ( $parts as $part ) {
				if ( in_array( $part, $skip_dir_names, true ) ) {
					$skip = true;
					break;
				}
				if ( in_array( $part, array( 'cache', 'et-cache', 'wflogs' ), true ) ) {
					$skip = true;
					break;
				}
			}
			if ( $skip ) {
				continue;
			}
			if ( preg_match( '/\.(log|tmp)$/i', $rel ) ) {
				continue;
			}
			$zip->addFile( $path, $prefix . '/' . $rel );
		}
	}

	/**
	 * @param string $dir Dir.
	 * @param int    $limit Stop counting above this; return -1 if exceeded.
	 * @return int
	 */
	private static function dir_size_bytes( $dir, $limit ) {
		$size = 0;
		try {
			$iter = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
			);
			foreach ( $iter as $file ) {
				if ( $file->isFile() ) {
					$size += $file->getSize();
					if ( $size > $limit ) {
						return -1;
					}
				}
			}
		} catch ( Exception $e ) {
			return -1;
		}
		return $size;
	}

	/** Fallback when ZipArchive missing: copy critical PHP trees into a .tar is hard without Phar; skip. */
	private static function fallback_tar_gz_code( $zip_path ) {
		// Minimal: dump mu-plugins only as concatenated listing + copy key files into a folder archive via PharData if available.
		if ( class_exists( 'PharData' ) ) {
			$tar = preg_replace( '/\.zip$/', '.tar', $zip_path );
			try {
				if ( file_exists( $tar ) ) {
					@unlink( $tar );
				}
				if ( file_exists( $tar . '.gz' ) ) {
					@unlink( $tar . '.gz' );
				}
				$phar = new PharData( $tar );
				$mu   = trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
				if ( is_dir( $mu ) ) {
					$phar->buildFromDirectory( $mu );
				}
				$phar->compress( Phar::GZ );
				unset( $phar );
				@unlink( $tar );
				$gz = $tar . '.gz';
				if ( file_exists( $gz ) ) {
					@rename( $gz, preg_replace( '/\.zip$/', '.tar.gz', $zip_path ) );
					return true;
				}
			} catch ( Exception $e ) {
				return false;
			}
		}
		return false;
	}

	private static function prune_old_backups() {
		$root = self::backup_root();
		$dirs = glob( trailingslashit( $root ) . '20*', GLOB_ONLYDIR );
		if ( ! is_array( $dirs ) ) {
			return;
		}
		$cutoff = time() - ( self::KEEP_DAYS * DAY_IN_SECONDS );
		foreach ( $dirs as $d ) {
			$name = basename( $d );
			$ts   = strtotime( $name . ' UTC' );
			if ( $ts && $ts < $cutoff ) {
				self::rrmdir( $d );
			}
		}
	}

	private static function rrmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = scandir( $dir );
		if ( ! is_array( $items ) ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) ) {
				self::rrmdir( $path );
			} else {
				@unlink( $path );
			}
		}
		@rmdir( $dir );
	}
}

Cindemir_Backup::boot();

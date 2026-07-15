<?php
/**
 * Plugin Name: Cindemir LLMs.txt
 * Description: Serves a curated, practice-area llms.txt (and llms-full.txt) for AI discovery. Replaces Yoast's thin auto-generated file.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_LLMS_TXT_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_LLMS_TXT_LOADED', true );

final class Cindemir_Llms_Txt {

	const VERSION = '1.0.0';

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'maybe_disable_yoast_llms' ), 5 );
		add_action( 'init', array( __CLASS__, 'sync_root_files' ), 20 );
		add_action( 'template_redirect', array( __CLASS__, 'serve_files' ), 0 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'disable_canonical_for_llms' ), 10, 2 );
	}

	/**
	 * Stop Yoast from regenerating the thin auto file on top of ours.
	 */
	public static function maybe_disable_yoast_llms() {
		if ( ! class_exists( 'WPSEO_Options' ) ) {
			return;
		}

		$keys = array( 'enable_llms_txt', 'llms_txt', 'llms_txt_enabled' );
		foreach ( $keys as $key ) {
			$current = WPSEO_Options::get( $key, null );
			if ( null === $current ) {
				continue;
			}
			if ( true === $current || 1 === $current || 'on' === $current ) {
				WPSEO_Options::set( $key, false );
			}
		}

		// Older / Premium option bag shape.
		$wpseo = get_option( 'wpseo', array() );
		if ( is_array( $wpseo ) && ! empty( $wpseo['enable_llms_txt'] ) ) {
			$wpseo['enable_llms_txt'] = false;
			update_option( 'wpseo', $wpseo );
		}
	}

	public static function content_dir() {
		return dirname( __DIR__ );
	}

	public static function bundled_path( $name ) {
		$candidates = array(
			plugin_dir_path( __FILE__ ) . $name,
			dirname( __FILE__ ) . '/' . $name,
			WP_CONTENT_DIR . '/mu-plugins/' . $name,
			WP_CONTENT_DIR . '/plugins/cindemir-llms-txt/' . $name,
			ABSPATH . $name,
		);
		foreach ( $candidates as $path ) {
			if ( is_readable( $path ) ) {
				return $path;
			}
		}
		return '';
	}

	public static function fetch_remote( $name ) {
		$urls = array(
			'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/strengthen-llms-txt-adcd/fixes/mu-plugins/' . $name,
			'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/strengthen-llms-txt-adcd/fixes/' . $name,
			'https://raw.githubusercontent.com/gcindemir/cindemir/master/fixes/' . $name,
		);
		foreach ( $urls as $url ) {
			$res = wp_remote_get(
				$url,
				array(
					'timeout' => 20,
					'headers' => array( 'User-Agent' => 'CindemirLlmsTxt/' . self::VERSION ),
				)
			);
			if ( is_wp_error( $res ) ) {
				continue;
			}
			if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
				continue;
			}
			$body = (string) wp_remote_retrieve_body( $res );
			if ( strlen( $body ) > 200 && false !== strpos( $body, 'Cindemir' ) ) {
				return $body;
			}
		}
		return '';
	}

	public static function curated_body( $name ) {
		$path = self::bundled_path( $name );
		if ( $path ) {
			$body = file_get_contents( $path );
			if ( is_string( $body ) && '' !== trim( $body ) ) {
				return $body;
			}
		}
		$remote = self::fetch_remote( $name );
		if ( $remote ) {
			return $remote;
		}
		return 'llms-full.txt' === $name ? self::fallback_llms() : self::fallback_llms();
	}

	public static function fallback_llms() {
		return "# Cindemir Law Office\n\n> Istanbul-based international law firm.\n\n- [Home](https://cindemirlaw.com/)\n- [Services](https://cindemirlaw.com/services/)\n- [Contact](https://cindemirlaw.com/contacts/)\n- [Sitemap](https://cindemirlaw.com/sitemap_index.xml)\n";
	}

	/**
	 * Physical root files beat Yoast's generator priority.
	 */
	public static function sync_root_files() {
		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			return;
		}

		$map = array(
			'llms.txt'      => 'llms.txt',
			'llms-full.txt' => 'llms-full.txt',
		);

		foreach ( $map as $root_name => $bundled_name ) {
			$src = self::bundled_path( $bundled_name );
			if ( ! $src ) {
				continue;
			}
			$dest = trailingslashit( ABSPATH ) . $root_name;
			$body = file_get_contents( $src );
			if ( ! is_string( $body ) || '' === trim( $body ) ) {
				continue;
			}
			$existing = is_readable( $dest ) ? file_get_contents( $dest ) : '';
			if ( $existing === $body ) {
				continue;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			@file_put_contents( $dest, $body );
		}
	}

	public static function requested_llms_file() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = wp_parse_url( $uri, PHP_URL_PATH );
		$path = is_string( $path ) ? untrailingslashit( $path ) : '';
		if ( '/llms.txt' === $path || 'llms.txt' === ltrim( $path, '/' ) ) {
			return 'llms.txt';
		}
		if ( '/llms-full.txt' === $path || 'llms-full.txt' === ltrim( $path, '/' ) ) {
			return 'llms-full.txt';
		}
		return '';
	}

	public static function disable_canonical_for_llms( $redirect, $requested ) {
		if ( self::requested_llms_file() ) {
			return false;
		}
		return $redirect;
	}

	public static function serve_files() {
		$file = self::requested_llms_file();
		if ( ! $file ) {
			return;
		}

		$body = self::curated_body( $file );
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=UTF-8' );
		header( 'X-Cindemir-Llms: ' . self::VERSION );
		header( 'Cache-Control: public, max-age=3600' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $body;
		exit;
	}
}

Cindemir_Llms_Txt::boot();

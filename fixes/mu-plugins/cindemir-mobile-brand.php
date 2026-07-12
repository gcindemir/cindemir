<?php
/**
 * Plugin Name: Cindemir Mobile Brand + SEO Restore
 * Description: Mobile header site name (CSS) and one-shot restore of cindemir-seo-fixes.php from GitHub.
 * Version: 1.0.1
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	$dest = defined( 'WPMU_PLUGIN_DIR' ) ? trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-seo-fixes.php' : '';
	if ( ! $dest || ( file_exists( $dest ) && filesize( $dest ) > 30000 ) ) {
		return;
	}
	$urls = array(
		'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/mobile-header-branding-adcd/fixes/mu-plugins/cindemir-seo-fixes.php',
		'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/cindemir-seo-fixes.php',
	);
	foreach ( $urls as $url ) {
		$r = wp_remote_get( $url, array( 'timeout' => 30, 'headers' => array( 'User-Agent' => 'CindemirRestore/1.0' ) ) );
		if ( is_wp_error( $r ) ) {
			continue;
		}
		$body = (string) wp_remote_retrieve_body( $r );
		if ( 200 === (int) wp_remote_retrieve_response_code( $r ) && strlen( $body ) > 30000 ) {
			file_put_contents( $dest, $body );
			delete_option( 'cindemir_seo_fixes_version' );
			if ( function_exists( 'rocket_clean_domain' ) ) {
				rocket_clean_domain();
			}
			break;
		}
	}
}, 1 );

add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	$lang = 'en';
	if ( ! empty( $_GET['lang'] ) ) {
		$lang = sanitize_key( wp_unslash( $_GET['lang'] ) );
	} else {
		$wpml = apply_filters( 'wpml_current_language', null );
		if ( is_string( $wpml ) && '' !== $wpml ) {
			$lang = $wpml;
		} elseif ( function_exists( 'pll_current_language' ) ) {
			$pll = pll_current_language( 'slug' );
			if ( is_string( $pll ) && '' !== $pll ) {
				$lang = $pll;
			}
		} elseif ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
			$lang = ICL_LANGUAGE_CODE;
		}
	}
	if ( 0 === strpos( strtolower( (string) $lang ), 'tr' ) ) {
		$lang = 'tr';
	}
	$label = ( 'tr' === $lang ) ? 'Cindemir Hukuk Bürosu' : 'Cindemir Law Office';
	echo '<style id="cindemir-mobile-brand">@media(max-width:989px){#header .logo a{display:inline-flex!important;align-items:center;gap:8px;max-width:calc(100vw - 110px)}#header .logo img{max-height:38px!important;max-width:38px!important}#header .logo.bg-logo img[src*="themes/enfold/images/layout/logo.png"]{display:none!important}#header .logo a::after{content:"' . esc_attr( $label ) . '";font-size:13px;font-weight:600;line-height:1.2;color:#336666;max-width:170px}}</style>';
}, 50 );

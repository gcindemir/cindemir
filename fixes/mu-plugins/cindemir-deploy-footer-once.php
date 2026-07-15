<?php
/**
 * Plugin Name: Cindemir Deploy Footer (one-shot)
 * Description: Downloads contact-fixes v1.3.0 from GitHub, purges cache, then disables itself.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	if ( get_option( 'cindemir_footer_deploy_done' ) ) {
		return;
	}

	$sources = array(
		'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/footer-email-social-baro-917b/fixes/mu-plugins/cindemir-contact-fixes.php',
		'https://raw.githubusercontent.com/gcindemir/cindemir/master/fixes/mu-plugins/cindemir-contact-fixes.php',
	);

	$body = '';
	foreach ( $sources as $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 45,
				'headers' => array( 'User-Agent' => 'CindemirFooterDeploy/1.0' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			continue;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$tmp  = (string) wp_remote_retrieve_body( $response );
		if ( 200 === $code && strlen( $tmp ) > 20000 && false !== strpos( $tmp, 'enhance_footer_html' ) ) {
			$body = $tmp;
			break;
		}
	}

	if ( '' === $body || ! defined( 'WPMU_PLUGIN_DIR' ) ) {
		return;
	}

	$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-contact-fixes.php';
	if ( false === file_put_contents( $dest, $body ) ) {
		return;
	}

	delete_option( 'cindemir_contact_fixes_version' );

	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}

	update_option( 'cindemir_footer_deploy_done', 1, false );

	$self = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-deploy-footer-once.php';
	if ( is_writable( $self ) ) {
		@unlink( $self );
	}
}, 1 );

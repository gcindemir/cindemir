<?php
/**
 * Plugin Name: Cindemir WhatsApp Unify
 * Description: Forces all Joinchat and wa.me links to +90 216 550 6775 on every page.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_WHATSAPP_UNIFY_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_WHATSAPP_UNIFY_LOADED', true );

final class Cindemir_WhatsApp_Unify {

	const PHONE = '902165506775';

	public static function boot() {
		add_filter( 'joinchat_get_settings', array( __CLASS__, 'force_phone' ), 99 );
		add_filter( 'joinchat_get_settings_site', array( __CLASS__, 'force_phone' ), 99 );
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 0 );
	}

	public static function force_phone( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['telephone'] = self::PHONE;
		return $settings;
	}

	public static function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'normalize_html' ) );
	}

	public static function normalize_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$phone = self::PHONE;
		$wa    = 'https://wa.me/' . $phone;

		$html = preg_replace( '#https?://wa\.me/[0-9]+#i', $wa, $html );
		$html = preg_replace( '#"telephone"\s*:\s*"[0-9]+"#', '"telephone":"' . $phone . '"', $html );
		$html = preg_replace( '#data-phone="[0-9]+"#', 'data-phone="' . $phone . '"', $html );

		return $html;
	}
}

Cindemir_WhatsApp_Unify::boot();

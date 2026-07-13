<?php
/**
 * Plugin Name: Cindemir Avukatlarımız Styles
 * Description: Avukatlarımız sayfasında tüm avukat kartlarının renk ve stil tutarlılığını sağlar.
 * Version: 1.0.1
 * Author: Cindemir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cindemir_Avukatlarimiz_Styles {

	const VERSION = '1.0.1';

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 999 );
		add_action( 'wp_footer', array( $this, 'footer_fallback' ), 999 );
	}

	public function enqueue_styles() {
		if ( ! $this->is_avukatlarimiz_page() ) {
			return;
		}

		wp_enqueue_style(
			'cindemir-avukatlarimiz-team',
			plugin_dir_url( __FILE__ ) . 'avukatlarimiz-team.css',
			array(),
			self::VERSION
		);
	}

	/**
	 * WP Rocket RUCSS wp_head içindeki inline stilleri silebiliyor;
	 * harici CSS dosyası + footer yedek ile tutarlılık sağlanır.
	 */
	public function footer_fallback() {
		if ( ! $this->is_avukatlarimiz_page() ) {
			return;
		}

		$css_file = plugin_dir_path( __FILE__ ) . 'avukatlarimiz-team.css';
		if ( ! is_readable( $css_file ) ) {
			return;
		}

		echo '<style id="cindemir-avukatlarimiz-team-fix">' . "\n";
		echo file_get_contents( $css_file );
		echo "\n" . '</style>' . "\n";
	}

	private function is_avukatlarimiz_page() {
		if ( ! is_page() ) {
			return false;
		}

		$slug = get_post_field( 'post_name', get_queried_object_id() );
		if ( in_array( $slug, array( 'avukatlarimiz', 'our-lawyers', 'nashi-yuristy' ), true ) ) {
			return true;
		}

		return (int) get_queried_object_id() === 440;
	}
}

new Cindemir_Avukatlarimiz_Styles();

<?php
/**
 * Plugin Name: Cindemir Avukatlarımız Styles
 * Description: Avukatlarımız sayfasında tüm avukat kartlarının renk ve stil tutarlılığını sağlar.
 * Version: 1.0.2
 * Author: Cindemir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cindemir_Avukatlarimiz_Styles {

	const VERSION = '1.0.2';

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 999 );
		add_action( 'template_redirect', array( $this, 'maybe_start_buffer' ), 0 );
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

	public function maybe_start_buffer() {
		if ( ! $this->is_avukatlarimiz_page() || is_admin() ) {
			return;
		}

		ob_start( array( $this, 'normalize_team_cards' ) );
	}

	/**
	 * Alt avukat kartları gri inline kutularla geliyor; üstteki clo-team-card
	 * sınıfıyla aynı görünümü ver. clo-team-card CSS zaten sayfada mevcut.
	 */
	public function normalize_team_cards( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		$html = preg_replace(
			'#<div style="background-color:\s*#f2f2f2;[^"]*">(.*?)</div>#is',
			'<article class="clo-team-card">$1</article>',
			$html
		);

		$html = preg_replace(
			'#<h2 style="margin:\s*0 0 15px 0;\s*text-align:\s*center;">#i',
			'<h2 class="clo-team-name">',
			$html
		);

		$html = preg_replace(
			'#<p style="text-align:\s*justify;\s*font-size:\s*15px;\s*line-height:\s*1\.6;\s*color:\s*#333;">#i',
			'<p class="clo-team-text">',
			$html
		);

		$html = preg_replace(
			'#<img([^>]*?)style="display:\s*block;\s*margin:\s*0 auto 15px auto;\s*border:\s*1px solid #ccc;"#i',
			'<img$1class="clo-team-photo"',
			$html
		);

		$html = preg_replace(
			'#<h2 class="elementor-heading-title elementor-size-default" style="text-align: justify;">\s*</h2>#',
			'',
			$html
		);

		return $html;
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

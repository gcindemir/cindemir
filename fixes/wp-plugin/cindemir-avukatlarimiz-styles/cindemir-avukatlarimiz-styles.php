<?php
/**
 * Plugin Name: Cindemir Avukatlarımız Styles
 * Description: Avukatlarımız sayfasında tüm avukat kartlarının renk ve stil tutarlılığını sağlar.
 * Version: 1.0.3
 * Author: Cindemir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cindemir_Avukatlarimiz_Styles {

	const VERSION = '1.0.4';

	public function __construct() {
		add_action( 'wp_footer', array( $this, 'print_team_card_fix' ), 999 );
		add_filter( 'rocket_delay_js_exclusions', array( $this, 'rocket_js_exclusions' ) );
	}

	public function rocket_js_exclusions( $exclusions ) {
		if ( ! is_array( $exclusions ) ) {
			$exclusions = array();
		}
		$exclusions[] = 'cindemir-avukatlarimiz-team-fix';
		return $exclusions;
	}

	/**
	 * WP Rocket inline CSS'i siliyor; clo-team-card stilleri sayfada zaten var.
	 * Gri inline kutuları JS ile aynı sınıflara dönüştürüyoruz.
	 */
	public function print_team_card_fix() {
		if ( ! $this->is_avukatlarimiz_page() ) {
			return;
		}
		?>
<script id="cindemir-avukatlarimiz-team-fix" data-no-optimize="1" data-cfasync="false" data-no-defer="1" data-no-minify="1">
(function () {
	'use strict';
	function normalizeCards() {
		document.querySelectorAll('.elementor-widget-text-editor div[style*="f2f2f2"]').forEach(function (card) {
			card.classList.add('clo-team-card');
			card.removeAttribute('style');
			var heading = card.querySelector('h2');
			if (heading) {
				heading.classList.add('clo-team-name');
				heading.removeAttribute('style');
			}
			card.querySelectorAll('p').forEach(function (p) {
				p.classList.add('clo-team-text');
				p.removeAttribute('style');
			});
			card.querySelectorAll('img').forEach(function (img) {
				img.classList.add('clo-team-photo');
				img.removeAttribute('style');
			});
		});
		var emptyHeading = document.querySelector('.elementor-element-a89e60e h2.elementor-heading-title');
		if (emptyHeading && !emptyHeading.textContent.trim()) {
			emptyHeading.style.display = 'none';
		}
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', normalizeCards);
	} else {
		normalizeCards();
	}
})();
</script>
		<?php
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

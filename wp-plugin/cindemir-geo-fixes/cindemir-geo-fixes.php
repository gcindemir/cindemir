<?php
/**
 * Plugin Name: Cindemir GEO Fixes
 * Description: Locale-aware LegalService schema (TR→TR, RU→RU, EN→global, ZH→CN), removes duplicate SASWP/MTM Organization, creates intent landings, improves empty flag alts and front-page meta.
 * Version: 1.1.0
 * Author: Cindemir Hukuk Bürosu
 * Text Domain: cindemir-geo-fixes
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CINDEMIR_GEO_VERSION', '1.1.0' );
define( 'CINDEMIR_GEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'CINDEMIR_GEO_URL', plugin_dir_url( __FILE__ ) );

require_once CINDEMIR_GEO_DIR . 'includes/class-schema.php';
require_once CINDEMIR_GEO_DIR . 'includes/class-alt-fix.php';
require_once CINDEMIR_GEO_DIR . 'includes/class-meta.php';
require_once CINDEMIR_GEO_DIR . 'includes/class-landings.php';

final class Cindemir_GEO_Fixes {
	public static function init() {
		Cindemir_GEO_Schema::init();
		Cindemir_GEO_Alt_Fix::init();
		Cindemir_GEO_Meta::init();
		Cindemir_GEO_Landings::init();
	}

	public static function activate() {
		Cindemir_GEO_Landings::ensure_pages();
		flush_rewrite_rules();
	}
}

add_action( 'plugins_loaded', array( 'Cindemir_GEO_Fixes', 'init' ) );
register_activation_hook( __FILE__, array( 'Cindemir_GEO_Fixes', 'activate' ) );

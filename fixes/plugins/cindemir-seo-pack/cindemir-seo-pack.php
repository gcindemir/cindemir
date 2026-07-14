<?php
/**
 * Plugin Name: Cindemir SEO Pack
 * Description: Ahrefs fixes — barobirlik badge, sitemap, hreflang, contacts. Deactivate from Plugins if needed.
 * Version: 1.9.2
 * Author: Cindemir Law Office
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CINDEMIR_SEO_PACK_DIR', plugin_dir_path( __FILE__ ) );

require_once CINDEMIR_SEO_PACK_DIR . 'includes/cindemir-contact-fixes.php';
require_once CINDEMIR_SEO_PACK_DIR . 'includes/cindemir-seo-fixes.php';

add_action(
	'admin_menu',
	function () {
		add_management_page(
			'Cindemir SEO',
			'Cindemir SEO',
			'manage_options',
			'cindemir-seo-pack',
			'cindemir_seo_pack_admin_page'
		);
	}
);

function cindemir_seo_pack_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$ran = false;
	$out = null;
	if ( isset( $_POST['cindemir_run_ahrefs'] ) && check_admin_referer( 'cindemir_seo_pack' ) ) {
		$req = new WP_REST_Request( 'GET', '/cindemir/v1/fix-ahrefs' );
		$req->set_param( 'key', 'seo-pack-2026' );
		if ( class_exists( 'Cindemir_Contact_Fixes' ) ) {
			$out = Cindemir_Contact_Fixes::fix_ahrefs( $req );
			$ran = true;
		}
	}
	?>
	<div class="wrap">
		<h1>Cindemir SEO Pack</h1>
		<p>Version <?php echo esc_html( Cindemir_SEO_Fixes::VERSION ); ?></p>
		<?php if ( $ran && $out instanceof WP_REST_Response ) : ?>
			<div class="notice notice-success"><pre><?php echo esc_html( wp_json_encode( $out->get_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'cindemir_seo_pack' ); ?>
			<p><input type="submit" name="cindemir_run_ahrefs" class="button button-primary" value="Run Ahrefs fixes" /></p>
		</form>
		<p>Or open: <code><?php echo esc_html( rest_url( 'cindemir/v1/fix-ahrefs?key=seo-pack-2026' ) ); ?></code></p>
	</div>
	<?php
}

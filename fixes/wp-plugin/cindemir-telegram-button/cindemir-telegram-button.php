<?php
/**
 * Plugin Name: Cindemir Telegram Button
 * Description: Floating Telegram button on Russian pages only — https://t.me/Cindemir_Law_Office — paired with WhatsApp (left).
 * Version: 1.1.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_TELEGRAM_BUTTON_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_TELEGRAM_BUTTON_LOADED', true );

final class Cindemir_Telegram_Button {

	const USERNAME = 'Cindemir_Law_Office';
	const URL      = 'https://t.me/Cindemir_Law_Office';

	public static function boot() {
		add_action( 'wp_footer', array( __CLASS__, 'render' ), 30 );
	}

	/**
	 * Russian site only (WPML / ?lang=ru / locale).
	 */
	private static function is_russian() {
		if ( defined( 'ICL_LANGUAGE_CODE' ) && 'ru' === ICL_LANGUAGE_CODE ) {
			return true;
		}

		$wpml = apply_filters( 'wpml_current_language', null );
		if ( 'ru' === $wpml ) {
			return true;
		}

		if ( isset( $_GET['lang'] ) && 'ru' === sanitize_text_field( wp_unslash( $_GET['lang'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		if ( is_string( $locale ) && 0 === stripos( $locale, 'ru' ) ) {
			return true;
		}

		return false;
	}

	public static function render() {
		if ( is_admin() || ! self::is_russian() ) {
			return;
		}

		$url   = esc_url( apply_filters( 'cindemir_telegram_url', self::URL ) );
		$label = esc_attr(
			apply_filters(
				'cindemir_telegram_label',
				'Написать в Telegram'
			)
		);
		?>
<style id="cindemir-telegram-button-css">
/* Pair with #cindemir-wa-fallback (left): Telegram stays on the right. */
#cindemir-tg-button{
	position:fixed;
	z-index:999989;
	right:20px;
	left:auto;
	bottom:20px;
	width:60px;
	height:60px;
	border-radius:50%;
	background:#229ED9;
	box-shadow:0 4px 12px rgba(0,0,0,.25);
	display:flex;
	align-items:center;
	justify-content:center;
	text-decoration:none!important;
	transition:transform .2s ease, box-shadow .2s ease, background .2s ease;
}
#cindemir-tg-button:hover,
#cindemir-tg-button:focus{
	transform:scale(1.06);
	box-shadow:0 6px 16px rgba(0,0,0,.3);
	background:#1b8fc4;
}
#cindemir-tg-button svg{
	width:30px;
	height:30px;
	fill:#fff;
	display:block;
	margin-left:2px;
}
/* If Joinchat ever shows on the right, stack Telegram above WhatsApp on the left. */
#cindemir-tg-button.cindemir-tg--with-joinchat{
	right:auto;
	left:20px;
	bottom:96px;
}
@media (max-width:767px){
	#cindemir-tg-button{
		width:56px;
		height:56px;
		right:14px;
		bottom:14px;
	}
	#cindemir-tg-button.cindemir-tg--with-joinchat{
		right:auto;
		left:14px;
		bottom:86px;
	}
	#cindemir-tg-button svg{
		width:28px;
		height:28px;
	}
}
</style>
<a id="cindemir-tg-button" href="<?php echo $url; ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo $label; ?>" title="<?php echo $label; ?>">
	<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.78 15.54 9.55 18.7c.33 0 .47-.14.64-.3l1.54-1.48 3.2 2.35c.59.33 1.01.16 1.17-.54l2.12-9.96h.01c.19-.86-.31-1.2-.9-.99L3.94 10.1c-.82.32-.81.78-.14.99l3.67 1.15 8.53-5.38c.4-.27.77-.12.47.15z"/></svg>
</a>
<script id="cindemir-telegram-button-js">
(function () {
	var btn = document.getElementById('cindemir-tg-button');
	if (!btn) return;

	function isVisible(el) {
		if (!el) return false;
		var style = window.getComputedStyle(el);
		if (style.display === 'none' || style.visibility === 'hidden' || Number(style.opacity) === 0) {
			return false;
		}
		var rect = el.getBoundingClientRect();
		return rect.width > 0 && rect.height > 0;
	}

	function place() {
		var jc = document.querySelector('.joinchat.joinchat--show') || document.querySelector('.joinchat');
		if (isVisible(jc)) {
			btn.classList.add('cindemir-tg--with-joinchat');
		} else {
			btn.classList.remove('cindemir-tg--with-joinchat');
		}
	}

	place();
	setTimeout(place, 500);
	setTimeout(place, 4500);
	document.addEventListener('joinchat:show', place);
	window.addEventListener('resize', place);
})();
</script>
		<?php
	}
}

Cindemir_Telegram_Button::boot();

<?php
/**
 * Plugin Name: Cindemir Telegram Button
 * Description: Floating Telegram contact button (t.me/gcindemir) on every front-end page.
 * Version: 1.0.0
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

	const USERNAME = 'gcindemir';
	const URL      = 'https://t.me/gcindemir';

	public static function boot() {
		add_action( 'wp_footer', array( __CLASS__, 'render' ), 30 );
	}

	public static function render() {
		if ( is_admin() ) {
			return;
		}

		$url   = esc_url( apply_filters( 'cindemir_telegram_url', self::URL ) );
		$label = esc_attr(
			apply_filters(
				'cindemir_telegram_label',
				__( 'Telegram ile yazın', 'cindemir' )
			)
		);
		?>
<style id="cindemir-telegram-button-css">
#cindemir-tg-button{
	position:fixed;
	z-index:999989;
	right:20px;
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
	transition:transform .2s ease, box-shadow .2s ease, bottom .2s ease, left .2s ease, right .2s ease;
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
#cindemir-tg-button.cindemir-tg--left{
	right:auto;
	left:20px;
}
#cindemir-tg-button.cindemir-tg--above{
	bottom:96px;
}
@media (max-width:767px){
	#cindemir-tg-button{
		width:56px;
		height:56px;
		right:14px;
		bottom:14px;
	}
	#cindemir-tg-button.cindemir-tg--left{
		right:auto;
		left:14px;
	}
	#cindemir-tg-button.cindemir-tg--above{
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
		if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
			return false;
		}
		var rect = el.getBoundingClientRect();
		return rect.width > 0 && rect.height > 0;
	}

	function place() {
		btn.classList.remove('cindemir-tg--left', 'cindemir-tg--above');

		var wa = document.getElementById('cindemir-wa-fallback');
		var jc = document.querySelector('.joinchat.joinchat--show') || document.querySelector('.joinchat');
		var waVisible = isVisible(wa);
		var jcVisible = isVisible(jc);

		// Prefer right side. If Joinchat (WhatsApp) occupies the right, move to left.
		if (jcVisible) {
			btn.classList.add('cindemir-tg--left');
			// If custom WA fallback is also on the left, stack Telegram above it.
			if (waVisible) {
				btn.classList.add('cindemir-tg--above');
			}
			return;
		}

		// Joinchat hidden / absent: keep Telegram on the right.
		// If something else sits on the right bottom later, leave room above WA only when WA is right (rare).
		if (waVisible) {
			// WA is left on this site — Telegram stays right; no stack needed.
			return;
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

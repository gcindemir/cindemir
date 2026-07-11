<?php
/**
 * Plugin Name: Cindemir Contact & WhatsApp Fixes
 * Description: Reliable Enfold contact form submit + Joinchat/WhatsApp fallback when Debloat delays JS.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_CONTACT_FIXES_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_CONTACT_FIXES_LOADED', true );

final class Cindemir_Contact_Fixes {

	const WHATSAPP_PHONE = '905325680647';
	const MAIL_TO        = 'gokhan@cindemir.av.tr';

	public static function boot() {
		add_filter( 'debloat_delay_js_exclusions', array( __CLASS__, 'debloat_exclusions' ) );
		add_filter( 'rocket_delay_js_exclusions', array( __CLASS__, 'debloat_exclusions' ) );
		add_filter( 'script_loader_tag', array( __CLASS__, 'exclude_critical_scripts' ), 20, 3 );

		add_action( 'wp_footer', array( __CLASS__, 'render_fallback_assets' ), 5 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_mail_failure' ) );

		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/** Keep jQuery, Enfold bundle, and Joinchat from Debloat/Rocket delay. */
	public static function debloat_exclusions( $exclusions ) {
		if ( ! is_array( $exclusions ) ) {
			$exclusions = array();
		}
		$patterns = array(
			'jquery',
			'debloat/js/bb1fbc',
			'debloat/js/bcab097',
			'debloat/js/f9b9af',
			'joinchat',
			'avia_ajax_form',
			'avia-framework',
		);
		return array_values( array_unique( array_merge( $exclusions, $patterns ) ) );
	}

	/** Remove delay-load attributes from scripts required for forms/chat. */
	public static function exclude_critical_scripts( $tag, $handle, $src ) {
		if ( ! $src ) {
			return $tag;
		}
		$needles = array( 'debloat/js/bb1fbc', 'debloat/js/bcab097', 'joinchat', 'jquery' );
		$match   = false;
		foreach ( $needles as $needle ) {
			if ( false !== strpos( $src, $needle ) ) {
				$match = true;
				break;
			}
		}
		if ( ! $match ) {
			return $tag;
		}
		$tag = preg_replace( '/\sdata-rocketlazyloadscript=(["\']).*?\1/i', '', $tag );
		$tag = str_replace( ' data-cfasync="false"', '', $tag );
		if ( false === strpos( $tag, 'data-cfasync' ) ) {
			$tag = str_replace( '<script ', '<script data-cfasync="false" ', $tag );
		}
		return $tag;
	}

	public static function register_rest_routes() {
		register_rest_route(
			'joinchat/v1',
			'/track-click',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'joinchat_track_stub' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function joinchat_track_stub( $request ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	public static function log_mail_failure( $error ) {
		if ( ! is_wp_error( $error ) ) {
			return;
		}
		error_log( 'cindemirlaw wp_mail failed: ' . $error->get_error_message() );
	}

	private static function is_contacts_page() {
		if ( function_exists( 'is_page' ) && is_page( 'contacts' ) ) {
			return true;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		return ( false !== strpos( $path, '/contacts' ) );
	}

	public static function render_fallback_assets() {
		if ( is_admin() || ! self::is_contacts_page() ) {
			self::render_whatsapp_fallback_only();
			return;
		}
		self::render_whatsapp_fallback_only();
		self::render_contact_form_fallback_script();
	}

	private static function render_whatsapp_fallback_only() {
		if ( is_admin() ) {
			return;
		}
		$phone = apply_filters( 'cindemir_whatsapp_phone', self::WHATSAPP_PHONE );
		$text  = apply_filters(
			'cindemir_whatsapp_message',
			'Hallo, I found you through your website cindemirlaw.com'
		);
		$url   = 'https://wa.me/' . rawurlencode( preg_replace( '/\D+/', '', $phone ) );
		if ( $text ) {
			$url .= '?text=' . rawurlencode( $text );
		}
		?>
<style id="cindemir-whatsapp-fallback-css">
#cindemir-wa-fallback{position:fixed;z-index:999990;left:20px;bottom:20px;width:60px;height:60px;border-radius:50%;background:#25d366;box-shadow:0 4px 12px rgba(0,0,0,.25);display:none;align-items:center;justify-content:center;text-decoration:none}
#cindemir-wa-fallback svg{width:34px;height:34px;fill:#fff}
#cindemir-wa-fallback.is-visible{display:flex}
.joinchat.joinchat--show ~ #cindemir-wa-fallback{display:none!important}
</style>
<a id="cindemir-wa-fallback" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
	<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.516 3.516c4.686-4.686 12.284-4.686 16.97 0s4.686 12.283 0 16.97a12 12 0 0 1-13.754 2.299l-5.814.735a.392.392 0 0 1-.438-.44l.748-5.788A12 12 0 0 1 3.517 3.517zm3.61 17.043.3.158a9.85 9.85 0 0 0 11.534-1.758c3.843-3.843 3.843-10.074 0-13.918s-10.075-3.843-13.918 0a9.85 9.85 0 0 0-1.747 11.554l.16.303-.51 3.942a.196.196 0 0 0 .219.22zm6.534-7.003-.933 1.164a9.84 9.84 0 0 1-3.497-3.495l1.166-.933a.79.79 0 0 0 .23-.94L9.561 6.96a.79.79 0 0 0-.924-.445l-2.023.524a.797.797 0 0 0-.588.88 11.754 11.754 0 0 0 10.005 10.005.797.797 0 0 0 .88-.587l.525-2.023a.79.79 0 0 0-.445-.923L14.6 13.327a.79.79 0 0 0-.94.23z"/></svg>
</a>
<script id="cindemir-whatsapp-fallback-js">
(function () {
	var fb = document.getElementById('cindemir-wa-fallback');
	if (!fb) return;
	function showFallback() {
		var jc = document.querySelector('.joinchat.joinchat--show');
		if (!jc) fb.classList.add('is-visible');
	}
	setTimeout(showFallback, 4500);
	document.addEventListener('joinchat:show', function () {
		fb.classList.remove('is-visible');
	});
	if (window.joinchat_obj && typeof window.joinchat_obj.resume === 'function') {
		document.addEventListener('click', function () {
			setTimeout(function () {
				if (!document.querySelector('.joinchat.joinchat--show')) {
					window.joinchat_obj.resume();
				}
			}, 0);
		}, { once: true, capture: true });
	}
})();
</script>
		<?php
	}

	private static function render_contact_form_fallback_script() {
		?>
<script id="cindemir-contact-form-fallback-js">
(function () {
	var forms = document.querySelectorAll('form.avia_ajax_form');
	if (!forms.length) return;

	function qs(form, sel) { return form.querySelector(sel); }
	function qsa(form, sel) { return Array.prototype.slice.call(form.querySelectorAll(sel)); }

	function validate(form) {
		var errors = [];
		qsa(form, 'input[type="text"], input[type="email"], textarea').forEach(function (el) {
			var cls = el.className || '';
			var label = form.querySelector('label[for="' + el.id + '"]');
			var name = label ? label.textContent.replace(/\*/g, '').trim() : el.name;
			var val = (el.value || '').trim();
			if (cls.indexOf('is_empty') !== -1 && !val) errors.push(name);
			if (cls.indexOf('is_email') !== -1 && val && !/^[\w|\.|\-]+@\w[\w|\.|\-]*\.[a-zA-Z]{2,20}$/.test(val)) errors.push(name);
			if (cls.indexOf('is_phone') !== -1 && val && !/^(\d|\s|\-|\/|\(|\)|\[|\]|e|x|t|ension|\.|\+|\_|\,|\:|\;){3,}$/.test(val)) errors.push(name);
		});
		return errors;
	}

	function showMessage(form, html, isError) {
		var box = form.parentElement.querySelector('.ajaxresponse') || form.nextElementSibling;
		if (!box) {
			box = document.createElement('div');
			box.className = 'ajaxresponse hidden';
			form.insertAdjacentElement('afterend', box);
		}
		box.classList.remove('hidden');
		box.style.display = 'block';
		box.innerHTML = isError
			? '<div class="av-form-error-container"><p>' + html + '</p></div>'
			: html;
		if (!isError) form.style.display = 'none';
	}

	forms.forEach(function (form) {
		if (form.dataset.cindemirBound === '1') return;
		form.dataset.cindemirBound = '1';

		form.addEventListener('submit', function (ev) {
			ev.preventDefault();
			var btn = qs(form, 'input[type="submit"], button[type="submit"]');
			var sending = btn && btn.getAttribute('data-sending-label');
			var original = btn ? (btn.value || btn.textContent) : '';
			var errs = validate(form);
			if (errs.length) {
				showMessage(form, '<?php echo esc_js( __( 'Please check these fields:', 'cindemir' ) ); ?> ' + errs.join(', '), true);
				return;
			}

			var body = new URLSearchParams();
			body.append('ajax', 'true');
			qsa(form, 'input, textarea, select').forEach(function (el) {
				if (!el.name || el.type === 'submit') return;
				if (el.type === 'checkbox' && !el.checked) return;
				body.append(el.name, el.value || '');
			});

			if (btn) {
				btn.disabled = true;
				if (btn.value !== undefined) btn.value = sending || 'Sending...';
				else btn.textContent = sending || 'Sending...';
			}

			var action = form.getAttribute('action') || window.location.href;
			var controller = new AbortController();
			var timeout = setTimeout(function () { controller.abort(); }, 25000);

			fetch(action, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				signal: controller.signal,
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			})
				.then(function (res) {
					clearTimeout(timeout);
					if (!res.ok) throw new Error('HTTP ' + res.status);
					return res.text();
				})
				.then(function (html) {
					var doc = new DOMParser().parseFromString(html, 'text/html');
					var fragment = doc.querySelector('.ajaxresponse');
					if (!fragment) throw new Error('missing response');
					var msg = fragment.innerHTML;
					var isErr = /av-form-error|error-container/i.test(msg);
					showMessage(form, msg, isErr);
					if (!isErr) {
						qsa(form, 'input[type="text"], input[type="email"], textarea').forEach(function (el) {
							el.value = '';
						});
					}
				})
				.catch(function () {
					showMessage(
						form,
						'<?php echo esc_js( __( 'Message could not be sent. Please try again or email gokhan@cindemir.av.tr directly.', 'cindemir' ) ); ?>',
						true
					);
				})
				.finally(function () {
					if (btn) {
						btn.disabled = false;
						if (btn.value !== undefined) btn.value = original;
						else btn.textContent = original;
					}
				});
		});
	});
})();
</script>
		<?php
	}
}

Cindemir_Contact_Fixes::boot();

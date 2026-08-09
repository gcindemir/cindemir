<?php
/**
 * Plugin Name: Cindemir Footer Rocket
 * Description: Inject footer into WP Rocket cached HTML (mailto, social, baro, badges).
 * Version: 1.1.2
 * FOOTER_BADGE_CONTRAST_20260809
 * FOOTER_TIDY_20260809
 * FOOTER_BARO_I18N_20260807b
 * ELENA_ZARA_RU_BIO_20260718
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'rocket_buffer', 'cindemir_rocket_footer_buffer', 16 );
add_action( 'template_redirect', 'cindemir_rocket_footer_start_buffer', 0 );

/** Always buffer — Rocket may skip some URLs while still registering rocket_buffer. */
function cindemir_rocket_footer_start_buffer() {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}
	ob_start( 'cindemir_rocket_footer_buffer' );
}

function cindemir_rocket_footer_buffer( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	if ( false === stripos( $html, "id='socket'" ) && false === stripos( $html, 'id="socket"' ) ) {
		return $html;
	}
	$html = cindemir_rocket_linkify_copyright( $html );
	$html = cindemir_rocket_localize_baro_label( $html );
	return cindemir_rocket_inject_extras( $html );
}

/**
 * Current front language for footer copy.
 *
 * @param string $html Optional HTML for fallback detection.
 * @return string en|ru|zh-hans|tr
 */
function cindemir_rocket_front_lang( $html = '' ) {
	if ( ! empty( $_GET['lang'] ) ) {
		$get = sanitize_key( wp_unslash( $_GET['lang'] ) );
		if ( $get ) {
			return ( 'zh' === $get ) ? 'zh-hans' : $get;
		}
	}
	if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
		$code = (string) ICL_LANGUAGE_CODE;
		return ( 'zh' === $code ) ? 'zh-hans' : $code;
	}
	if ( function_exists( 'apply_filters' ) ) {
		$wpml = apply_filters( 'wpml_current_language', null );
		if ( is_string( $wpml ) && '' !== $wpml ) {
			return ( 'zh' === $wpml ) ? 'zh-hans' : $wpml;
		}
	}
	if ( is_string( $html ) && '' !== $html ) {
		if ( preg_match( '/[?&]lang=(ru|zh-hans|zh|en|tr)\b/i', $html, $m ) ) {
			$l = strtolower( $m[1] );
			return ( 'zh' === $l ) ? 'zh-hans' : $l;
		}
		if ( preg_match( '/<html[^>]+lang=["\']([a-z]{2}(?:-[a-z]+)?)/i', $html, $m ) ) {
			$l = strtolower( $m[1] );
			if ( 0 === strpos( $l, 'zh' ) ) {
				return 'zh-hans';
			}
			if ( 0 === strpos( $l, 'ru' ) ) {
				return 'ru';
			}
			if ( 0 === strpos( $l, 'tr' ) ) {
				return 'tr';
			}
		}
	}
	return 'en';
}

/**
 * Localized Bar verification CTA (never Turkish on EN/RU/ZH).
 *
 * @param string $lang Language slug.
 * @return array{label:string,baro_lang:string}
 */
function cindemir_rocket_baro_i18n( $lang ) {
	switch ( $lang ) {
		case 'ru':
			return array(
				'label'     => 'Нажмите, чтобы проверить регистрацию адвоката в коллегии',
				'baro_lang' => 'RU',
			);
		case 'zh-hans':
		case 'zh':
			return array(
				'label'     => '点击验证律师协会注册信息',
				'baro_lang' => 'EN',
			);
		case 'tr':
			return array(
				'label'     => 'Avukat Baro Doğrulama için Tıklayınız',
				'baro_lang' => 'TR',
			);
		case 'en':
		default:
			return array(
				'label'     => 'Click to verify attorney Bar registration',
				'baro_lang' => 'EN',
			);
	}
}

/**
 * Rewrite an already-injected Turkish (or stale) baro label to the active language.
 *
 * @param string $html HTML.
 * @return string
 */
function cindemir_rocket_localize_baro_label( $html ) {
	if ( ! is_string( $html ) || false === strpos( $html, 'cindemir-baro-verification-bar' ) ) {
		return $html;
	}
	$i18n  = cindemir_rocket_baro_i18n( cindemir_rocket_front_lang( $html ) );
	$label = $i18n['label'];
	$out   = preg_replace(
		'#(<div[^>]*id=["\']cindemir-baro-verification-bar["\'][^>]*>\s*<a\b[^>]*>)(.*?)(</a>)#is',
		'$1' . esc_html( $label ) . '$3',
		$html,
		1
	);
	if ( is_string( $out ) ) {
		$html = $out;
	}
	// Keep baronet UI language in sync when possible.
	$next = preg_replace(
		'#(baronet\.istanbulbarosu\.org\.tr/avukat/belge_dogrulama\?lang=)[A-Z]{2}#i',
		'$1' . $i18n['baro_lang'],
		$html,
		1
	);
	return is_string( $next ) ? $next : $html;
}

function cindemir_rocket_linkify_copyright( $html ) {
	return preg_replace_callback(
		'/(<footer[^>]*id=(["\'])socket\2[^>]*>.*?<span[^>]*class=(["\'])copyright\3[^>]*>)(.*?)(<\/span>)/is',
		static function ( $m ) {
			if ( false !== strpos( $m[4], 'cindemir-footer-copy' ) ) {
				return $m[0];
			}
			$structured = '<span class="cindemir-footer-copy">Copyright 2026 © Cindemir Law Office</span>'
				. '<span class="cindemir-footer-addr">Ritim Istanbul 44/18, Maltepe / Istanbul</span>'
				. '<span class="cindemir-footer-reach">'
				. '<a href="tel:+902165506775" class="cindemir-footer-phone">+90 216 550 67 75</a>'
				. '<span class="cindemir-footer-sep" aria-hidden="true">·</span>'
				. '<a href="mailto:cindemir@cindemir.av.tr" class="cindemir-footer-email">cindemir@cindemir.av.tr</a>'
				. '</span>';
			return $m[1] . $structured . $m[5];
		},
		$html,
		1
	);
}

function cindemir_rocket_inject_extras( $html ) {
	if ( false !== strpos( $html, 'cindemir-footer-rocket 1.1.2' ) ) {
		return $html;
	}
	// Replace older injected blocks so cached HTML picks up the tidy layout.
	if ( false !== strpos( $html, 'cindemir-socket-extras' ) ) {
		$stripped = preg_replace(
			'#<div[^>]*\bcindemir-socket-extras\b[^>]*>[\s\S]*?</div>\s*<style[^>]*id=["\']cindemir-footer-fixes-css["\'][^>]*>[\s\S]*?</style>\s*(?:<!--\s*cindemir-footer-rocket[^>]*-->)?#i',
			'',
			$html,
			1
		);
		if ( is_string( $stripped ) ) {
			$html = $stripped;
		}
	}
	$block    = cindemir_rocket_footer_markup( $html );
	$with_div = preg_replace_callback(
		'/(<footer[^>]*id=(["\'])socket\2[^>]*>.*?<span[^>]*class=(["\'])copyright\3[^>]*>.*?<\/span>)(\s*<\/div>)/is',
		static function ( $m ) use ( $block ) {
			return $m[1] . $block . $m[4];
		},
		$html,
		1,
		$c
	);
	if ( $c ) {
		return $with_div;
	}
	$with_span = preg_replace_callback(
		'/(<footer[^>]*id=(["\'])socket\2[^>]*>.*?<span[^>]*class=(["\'])copyright\3[^>]*>.*?<\/span>)/is',
		static function ( $m ) use ( $block ) {
			return $m[0] . $block;
		},
		$html,
		1,
		$c2
	);
	if ( $c2 ) {
		return $with_span;
	}
	$fallback = preg_replace(
		'/(<\/footer>)/i',
		$block . '$1',
		$html,
		1,
		$c3
	);
	return ( $c3 && is_string( $fallback ) ) ? $fallback : $html;
}

/**
 * @param string $html Page HTML for language detection.
 * @return string
 */
function cindemir_rocket_footer_markup( $html = '' ) {
	$i18n  = cindemir_rocket_baro_i18n( cindemir_rocket_front_lang( $html ) );
	$label = esc_html( $i18n['label'] );
	$baro  = 'https://baronet.istanbulbarosu.org.tr/avukat/belge_dogrulama?lang=' . rawurlencode( $i18n['baro_lang'] ) . '&onayno=HBE4U7ES3DM6C52&tck=58612509084';

	$social = array(
		array(
			'href'  => 'https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/',
			'label' => 'Facebook',
			'path'  => 'M13 10h3l-.4 4H13v9h-4v-9H7v-4h2V8.5C9 6.6 10.1 5 13 5h3v4h-2c-1.1 0-1 .6-1 1.5z',
			'ext'   => true,
		),
		array(
			'href'  => 'https://www.instagram.com/cindemir_law_office/',
			'label' => 'Instagram',
			'path'  => 'M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 3.5A5.5 5.5 0 1 1 6.5 13 5.5 5.5 0 0 1 12 7.5zm0 2A3.5 3.5 0 1 0 15.5 13 3.5 3.5 0 0 0 12 9.5zM17.8 6.2a1.2 1.2 0 1 1-1.2 1.2 1.2 1.2 0 0 1 1.2-1.2z',
			'ext'   => true,
		),
		array(
			'href'  => 'https://www.linkedin.com/company/cindemir-law-office/',
			'label' => 'LinkedIn',
			'path'  => 'M6.5 8.7H3.4V21h3.1zm-1.6-6A1.9 1.9 0 1 0 6.8 4.6 1.9 1.9 0 0 0 4.9 2.7zM9 8.7V21h3.1v-6c0-1.6.3-3.2 2.3-3.2s2 1.8 2 3.1V21H20v-6.8c0-3.4-1.8-5-4.2-5a3.6 3.6 0 0 0-3.3 1.8V8.7z',
			'ext'   => true,
		),
		array(
			'href'  => 'https://x.com/cindemirlegal',
			'label' => 'X (Twitter)',
			'path'  => 'M4 4l7.5 9.6L4.2 20h2.5l5.8-6.7L16.7 20H20l-7.9-10.2L19.3 4h-2.5l-5.3 6.1L7.8 4z',
			'ext'   => true,
		),
		array(
			'href'  => 'https://www.youtube.com/channel/UCHobIlbWxCMGTPBSZv_rM7Q',
			'label' => 'YouTube',
			'path'  => 'M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18 5 12 5 12 5s-6 0-7.8.4a2.5 2.5 0 0 0-1.8 1.8C2 9 2 12 2 12s0 3 .4 4.8a2.5 2.5 0 0 0 1.8 1.8C6 19 12 19 12 19s6 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.4-1.8.4-4.8.4-4.8s0-3-.4-4.8zM10 15.5v-7l6 3.5z',
			'ext'   => true,
		),
		array(
			'href'  => 'https://wa.me/905325680647',
			'label' => 'WhatsApp',
			'path'  => 'M12 2a10 10 0 0 0-8.7 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-2.8.8.8-2.7-.2-.3A8 8 0 1 1 12 20zm4.5-5.8c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.6.1-.7.8-.8 1-.3.1-.6 0a6.4 6.4 0 0 1-1.9-1.2 7.2 7.2 0 0 1-1.3-1.7c-.1-.3 0-.5.1-.6.1-.1.2-.3.3-.4.1-.1.1-.2.2-.3 0-.1 0-.2 0-.3s-.6-1.4-.8-1.9-.4-.5-.6-.5h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-1 2.2 5.2 5.2 0 0 0 1 2.6 9.7 9.7 0 0 0 3.7 3.2c.5.2 1 .4 1.3.4.3 0 .5 0 .7-.1.2-.1.6-.5.7-1 .1-.5.1-.9.1-1 0-.1-.1-.1-.3-.2z',
			'ext'   => true,
		),
		array(
			'href'  => 'https://t.me/gcindemir',
			'label' => 'Telegram',
			'path'  => 'M9.9 15.5 9.7 19c.5 0 .7-.2 1-.5l2.4-2.3 5 3.7c.9.5 1.5.2 1.7-.8l3.3-15.4h0c.3-1.2-.4-1.7-1.2-1.4L2.5 10c-1.2.5-1.2 1.2-.2 1.5l4.8 1.5L18.7 7c.6-.4 1.1-.2.7.2z',
			'ext'   => true,
		),
	);

	$items = '';
	foreach ( $social as $s ) {
		$rel = ! empty( $s['ext'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$items .= '<li><a href="' . esc_url( $s['href'] ) . '"' . $rel . ' title="' . esc_attr( $s['label'] ) . '" aria-label="' . esc_attr( $s['label'] ) . '">'
			. '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . $s['path'] . '"/></svg></a></li>';
	}

	$css = '#socket{padding:22px 16px 28px!important}'
		. '#socket .container{display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:0!important;max-width:640px!important;margin:0 auto!important;padding-top:6px!important;padding-bottom:18px!important;text-align:center}'
		. '#socket .copyright{display:flex!important;flex-direction:column!important;align-items:center!important;gap:5px!important;width:100%!important;margin:0!important;text-align:center!important;font-size:13px!important;line-height:1.45!important}'
		. '#socket .cindemir-footer-copy{display:block;font-weight:600;font-size:14px;letter-spacing:.01em;color:rgba(255,255,255,.96)}'
		. '#socket .cindemir-footer-addr{display:block;font-size:12.5px;color:rgba(255,255,255,.82)}'
		. '#socket .cindemir-footer-reach{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:6px 10px;font-size:13px}'
		. '#socket .cindemir-footer-sep{opacity:.55}'
		. '#socket .copyright a.cindemir-footer-email,#socket .copyright a.cindemir-footer-phone{color:inherit!important;text-decoration:underline;text-underline-offset:2px}'
		. '#socket .cindemir-socket-extras{width:100%;margin-top:14px;padding-top:14px;border-top:1px solid rgba(255,255,255,.14);display:flex;flex-direction:column;align-items:center;gap:12px;text-align:center}'
		. '#socket .cindemir-footer-social{margin:0;width:100%}'
		. '#socket .cindemir-footer-social-list{display:grid;grid-template-columns:repeat(7,34px);gap:8px;justify-content:center;margin:0 auto;padding:0;list-style:none;width:max-content;max-width:100%}'
		. '#socket .cindemir-footer-social-list a{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;text-decoration:none;transition:background .15s ease}'
		. '#socket .cindemir-footer-social-list a:hover{background:rgba(255,255,255,.22)}'
		. '#socket .cindemir-footer-social-list svg{width:16px;height:16px;fill:currentColor;display:block}'
		. '#socket .cindemir-footer-badges{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;margin:4px 0 2px}'
		/* White pads so dark baro seals stay readable on teal socket. */
		. '#socket .cindemir-footer-badges a{'
		. 'display:inline-flex!important;align-items:center!important;justify-content:center!important;'
		. 'box-sizing:border-box;background:#fff!important;'
		. 'box-shadow:0 1px 2px rgba(0,0,0,.18);text-decoration:none!important}'
		. '#socket .cindemir-footer-badges a.cindemir-badge-aea{'
		. 'width:auto;min-width:128px;height:72px;padding:10px 14px;border-radius:12px}'
		. '#socket .cindemir-footer-badges a.cindemir-badge-baro,'
		. '#socket .cindemir-footer-badges a.cindemir-badge-tbb{'
		. 'width:84px;min-width:84px;height:84px;padding:8px;border-radius:50%}'
		. '#socket .cindemir-footer-badges img{'
		. 'display:block!important;object-fit:contain!important;border:0!important;opacity:1!important;'
		. 'filter:none!important;-webkit-filter:none!important;background:transparent!important}'
		. '#socket .cindemir-footer-badges img.cindemir-badge-img-aea{'
		. 'height:50px!important;width:auto!important;max-width:140px!important;max-height:50px!important}'
		. '#socket .cindemir-footer-badges img.cindemir-badge-img-baro,'
		. '#socket .cindemir-footer-badges img.cindemir-badge-img-tbb{'
		. 'width:68px!important;height:68px!important;max-width:68px!important;max-height:68px!important}'
		. '#socket .cindemir-baro-verification-bar{margin:0;text-align:center;order:3}'
		. '#socket .cindemir-baro-verification-bar a{color:rgba(255,255,255,.78)!important;text-decoration:underline;text-underline-offset:2px;font-size:12px;line-height:1.35}'
		. '#socket .cindemir-footer-meta{width:100%;margin:4px 0 0;padding:0;font-size:11.5px;line-height:1.4;text-align:center;color:rgba(255,255,255,.78)}'
		. '#socket .cindemir-footer-meta a{color:inherit!important;text-decoration:underline;text-underline-offset:2px}'
		. '@media (max-width:520px){'
		. '#socket .cindemir-footer-social-list{grid-template-columns:repeat(4,34px)}'
		. '#socket .container{padding-bottom:72px!important}'
		. '#socket .cindemir-footer-copy{font-size:13.5px}'
		. '#socket .cindemir-footer-badges{gap:10px}'
		. '#socket .cindemir-footer-badges a.cindemir-badge-aea{min-width:108px;height:64px;padding:8px 10px}'
		. '#socket .cindemir-footer-badges a.cindemir-badge-baro,'
		. '#socket .cindemir-footer-badges a.cindemir-badge-tbb{width:72px;min-width:72px;height:72px;padding:6px}'
		. '#socket .cindemir-footer-badges img.cindemir-badge-img-aea{height:44px!important;max-height:44px!important}'
		. '#socket .cindemir-footer-badges img.cindemir-badge-img-baro,'
		. '#socket .cindemir-footer-badges img.cindemir-badge-img-tbb{width:60px!important;height:60px!important;max-width:60px!important;max-height:60px!important}'
		. '}';

	$aea  = 'https://cindemirlaw.com/wp-content/uploads/cindemir/aea.png';
	$ibar = 'https://cindemirlaw.com/wp-content/uploads/cindemir/baro.png';
	$tbb  = 'https://cindemirlaw.com/wp-content/uploads/cindemir/tbb_amblem.png';

	return '<div class="cindemir-socket-extras" id="cindemir-socket-extras">'
		. '<nav class="cindemir-footer-social" aria-label="Social media">'
		. '<ul class="cindemir-footer-social-list">' . $items . '</ul>'
		. '</nav>'
		. '<div class="cindemir-footer-badges" aria-label="Membership badges">'
		. '<a class="cindemir-badge-aea" href="https://www.aeuropea.com/" target="_blank" rel="noopener noreferrer" title="AEuropea">'
		. '<img class="cindemir-badge-img-aea" src="' . esc_url( $aea ) . '" alt="AEuropea International Lawyers Network" width="140" height="50" loading="eager" decoding="async" />'
		. '</a>'
		. '<a class="cindemir-badge-baro" href="https://istanbulbarosu.org.tr/" target="_blank" rel="noopener noreferrer" title="İstanbul Barosu">'
		. '<img class="cindemir-badge-img-baro" src="' . esc_url( $ibar ) . '" alt="İstanbul Barosu" width="68" height="68" loading="eager" decoding="async" />'
		. '</a>'
		. '<a class="cindemir-badge-tbb" href="https://www.barobirlik.org.tr/" target="_blank" rel="noopener noreferrer" title="Türkiye Barolar Birliği">'
		. '<img class="cindemir-badge-img-tbb" src="' . esc_url( $tbb ) . '" alt="Türkiye Barolar Birliği" width="68" height="68" loading="eager" decoding="async" />'
		. '</a>'
		. '</div>'
		. '<div id="cindemir-baro-verification-bar" class="cindemir-baro-verification-bar">'
		. '<a href="' . esc_url( $baro ) . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>'
		. '</div>'
		. '</div>'
		. '<style id="cindemir-footer-fixes-css">' . $css . '</style>'
		. '<!-- cindemir-footer-rocket 1.1.2 FOOTER_BADGE_CONTRAST_20260809 -->';
}

add_action(
	'init',
	static function () {
		if ( get_option( 'cindemir_footer_rocket_v112' ) ) {
			return;
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		update_option( 'cindemir_footer_rocket_v112', 1, false );
	},
	1
);

<?php
/**
 * Plugin Name: Cindemir Footer Rocket
 * Description: Inject footer into WP Rocket cached HTML (mailto, social, baro, badges).
 * Version: 1.0.4
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'rocket_buffer', 'cindemir_rocket_footer_buffer', 16 );

function cindemir_rocket_footer_buffer( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	if ( false === stripos( $html, "id='socket'" ) && false === stripos( $html, 'id="socket"' ) ) {
		return $html;
	}
	$html = cindemir_rocket_linkify_copyright( $html );
	return cindemir_rocket_inject_extras( $html );
}

function cindemir_rocket_linkify_copyright( $html ) {
	return preg_replace_callback(
		'/(<footer[^>]*id=(["\'])socket\2[^>]*>.*?<span[^>]*class=(["\'])copyright\3[^>]*>)(.*?)(<\/span>)/is',
		function ( $m ) {
			$inner = $m[4];
			if ( false !== stripos( $inner, 'cindemir-footer-email' ) || false === stripos( $inner, 'cindemir@cindemir.av.tr' ) ) {
				return $m[0];
			}
			$inner = preg_replace( '/cindemir@cindemir\.av\.tr/i', '<a href="mailto:cindemir@cindemir.av.tr" class="cindemir-footer-email">cindemir@cindemir.av.tr</a>', $inner, 1 );
			$inner = preg_replace( '/\+90 216 550 67 75/', '<a href="tel:+902165506775" class="cindemir-footer-phone">+90 216 550 67 75</a>', $inner, 1 );
			return $m[1] . $inner . $m[5];
		},
		$html,
		1
	);
}

function cindemir_rocket_inject_extras( $html ) {
	if ( false !== strpos( $html, 'cindemir-socket-extras' ) ) {
		return $html;
	}
	$block = cindemir_rocket_footer_markup();
	$with_div = preg_replace_callback(
		'/(<footer[^>]*id=(["\'])socket\2[^>]*>.*?<span[^>]*class=(["\'])copyright\3[^>]*>.*?<\/span>)(\s*<\/div>)/is',
		function ( $m ) use ( $block ) {
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
		function ( $m ) use ( $block ) {
			return $m[0] . $block;
		},
		$html,
		1,
		$c2
	);
	return $c2 ? $with_span : $html;
}

function cindemir_rocket_footer_markup() {
	$baro = 'https://baronet.istanbulbarosu.org.tr/avukat/belge_dogrulama?lang=EN&onayno=HBE4U7ES3DM6C52&tck=58612509084';
	return '<div class="cindemir-socket-extras" id="cindemir-socket-extras"><div id="cindemir-baro-verification-bar" class="cindemir-baro-verification-bar"><a href="' . esc_url( $baro ) . '" target="_blank" rel="noopener noreferrer">Avukat Baro Doğrulama için Tıklayınız</a></div><nav class="cindemir-footer-social" aria-label="Social media and contact"><ul class="cindemir-footer-social-list"><li><a href="mailto:cindemir@cindemir.av.tr" title="Email" aria-label="Email"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 2v.2l-8 5.2-8-5.2V6zm0 12H4V8.8l8 5.2 8-5.2z"/></svg></a></li><li><a href="https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/" target="_blank" rel="noopener noreferrer" title="Facebook" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 10h3l-.4 4H13v9h-4v-9H7v-4h2V8.5C9 6.6 10.1 5 13 5h3v4h-2c-1.1 0-1 .6-1 1.5z"/></svg></a></li><li><a href="https://www.instagram.com/cindemir_law_office/" target="_blank" rel="noopener noreferrer" title="Instagram" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 3.5A5.5 5.5 0 1 1 6.5 13 5.5 5.5 0 0 1 12 7.5zm0 2A3.5 3.5 0 1 0 15.5 13 3.5 3.5 0 0 0 12 9.5zM17.8 6.2a1.2 1.2 0 1 1-1.2 1.2 1.2 1.2 0 0 1 1.2-1.2z"/></svg></a></li><li><a href="https://www.linkedin.com/company/cindemir-law-office/" target="_blank" rel="noopener noreferrer" title="LinkedIn" aria-label="LinkedIn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 8.7H3.4V21h3.1zm-1.6-6A1.9 1.9 0 1 0 6.8 4.6 1.9 1.9 0 0 0 4.9 2.7zM9 8.7V21h3.1v-6c0-1.6.3-3.2 2.3-3.2s2 1.8 2 3.1V21H20v-6.8c0-3.4-1.8-5-4.2-5a3.6 3.6 0 0 0-3.3 1.8V8.7z"/></svg></a></li><li><a href="tel:+902165506775" title="Phone" aria-label="Phone"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.8 15.8 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.24 11 11 0 0 0 3.4.55 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11 11 0 0 0 .55 3.4 1 1 0 0 1-.24 1z"/></svg></a></li><li><a href="https://t.me/gcindemir" target="_blank" rel="noopener noreferrer" title="Telegram" aria-label="Telegram"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.9 15.5 9.7 19c.5 0 .7-.2 1-.5l2.4-2.3 5 3.7c.9.5 1.5.2 1.7-.8l3.3-15.4h0c.3-1.2-.4-1.7-1.2-1.4L2.5 10c-1.2.5-1.2 1.2-.2 1.5l4.8 1.5L18.7 7c.6-.4 1.1-.2.7.2z"/></svg></a></li><li><a href="https://x.com/cindemirlegal" target="_blank" rel="noopener noreferrer" title="X (Twitter)" aria-label="X (Twitter)"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4l7.5 9.6L4.2 20h2.5l5.8-6.7L16.7 20H20l-7.9-10.2L19.3 4h-2.5l-5.3 6.1L7.8 4z"/></svg></a></li><li><a href="https://wa.me/905325680647" target="_blank" rel="noopener noreferrer" title="WhatsApp" aria-label="WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.7 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-2.8.8.8-2.7-.2-.3A8 8 0 1 1 12 20zm4.5-5.8c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.6.1-.7.8-.8 1-.3.1-.6 0a6.4 6.4 0 0 1-1.9-1.2 7.2 7.2 0 0 1-1.3-1.7c-.1-.3 0-.5.1-.6.1-.1.2-.3.3-.4.1-.1.1-.2.2-.3 0-.1 0-.2 0-.3s-.6-1.4-.8-1.9-.4-.5-.6-.5h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-1 2.2 5.2 5.2 0 0 0 1 2.6 9.7 9.7 0 0 0 3.7 3.2c.5.2 1 .4 1.3.4.3 0 .5 0 .7-.1.2-.1.6-.5.7-1 .1-.5.1-.9.1-1 0-.1-.1-.1-.3-.2z"/></svg></a></li><li><a href="https://www.youtube.com/channel/UCHobIlbWxCMGTPBSZv_rM7Q" target="_blank" rel="noopener noreferrer" title="YouTube" aria-label="YouTube"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18 5 12 5 12 5s-6 0-7.8.4a2.5 2.5 0 0 0-1.8 1.8C2 9 2 12 2 12s0 3 .4 4.8a2.5 2.5 0 0 0 1.8 1.8C6 19 12 19 12 19s6 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.4-1.8.4-4.8.4-4.8s0-3-.4-4.8zM10 15.5v-7l6 3.5z"/></svg></a></li></ul></nav><div class="cindemir-footer-badges" aria-label="Cindemir Law verification and membership badges"><a href="https://www.aeuropea.com/" target="_blank" rel="noopener noreferrer" title="AEuropea"><img src="https://www.aeuropea.com/wp-content/uploads/2025/09/aea-01v001-ILN-small.png" alt="AEuropea" width="64" height="48" loading="lazy" decoding="async" /></a><a href="https://istanbulbarosu.org.tr/" target="_blank" rel="noopener noreferrer" title="İstanbul Barosu"><img src="https://istanbulbarosu.org.tr/images/baro_logo.png" alt="İstanbul Barosu" width="48" height="48" loading="lazy" decoding="async" /></a><a href="https://www.barobirlik.org.tr/" target="_blank" rel="noopener noreferrer" title="Türkiye Barolar Birliği"><img src="https://cindemirlaw.com/wp-content/uploads/cindemir/tbb_amblem_60.png" alt="Türkiye Barolar Birliği" width="48" height="48" loading="lazy" decoding="async" /></a></div></div><style id="cindemir-footer-fixes-css">#socket .container{text-align:center}#socket .copyright{display:block;width:100%;text-align:center;margin:0 auto}#socket .cindemir-socket-extras{width:100%;margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.15);text-align:center}#socket .cindemir-baro-verification-bar{margin-bottom:12px;text-align:center}#socket .cindemir-baro-verification-bar a{color:inherit;text-decoration:underline;font-size:14px}#socket .cindemir-footer-social{margin:10px 0 14px}#socket .cindemir-footer-social-list{display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;margin:0;padding:0;list-style:none}#socket .cindemir-footer-social-list a{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;text-decoration:none}#socket .cindemir-footer-social-list svg{width:18px;height:18px;fill:currentColor;display:block}#socket .cindemir-footer-badges{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap}#socket .cindemir-footer-badges img{height:48px;width:auto;max-height:48px;display:block;object-fit:contain;border:0}#socket .copyright a.cindemir-footer-email,#socket .copyright a.cindemir-footer-phone{color:inherit;text-decoration:underline}</style>';
}

add_action(
	'init',
	static function () {
		if ( get_option( 'cindemir_footer_rocket_v104' ) ) {
			return;
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		if ( defined( 'WP_CONTENT_DIR' ) && is_dir( WP_CONTENT_DIR . '/cache/wp-rocket' ) ) {
			$dir = WP_CONTENT_DIR . '/cache/wp-rocket';
			foreach ( scandir( $dir ) ?: array() as $item ) {
				if ( '.' === $item || '..' === $item ) {
					continue;
				}
				$path = $dir . '/' . $item;
				is_dir( $path ) ? @rmdir( $path ) : @unlink( $path );
			}
		}
		update_option( 'cindemir_footer_rocket_v104', 1, false );
	},
	1
);

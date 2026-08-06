<?php
/**
 * Plugin Name: Cindemir Contact & WhatsApp Fixes
 * Description: Reliable Enfold contact form submit + Joinchat/WhatsApp fallback when Debloat delays JS.
 * Version: 1.3.7
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

	/** Office WhatsApp — all JoinChat / wa.me links must use this number. */
	const WHATSAPP_PHONE     = '902165506775';
	const WHATSAPP_PHONE_OLD = '905325680647';
	const MAIL_TO            = 'gokhan@cindemir.av.tr';
	const MAIL_FROM          = 'wordpress@cindemirlaw.com';

	public static function boot() {
		add_filter( 'debloat_delay_js_exclusions', array( __CLASS__, 'debloat_exclusions' ) );
		add_filter( 'rocket_delay_js_exclusions', array( __CLASS__, 'debloat_exclusions' ) );
		add_filter( 'script_loader_tag', array( __CLASS__, 'exclude_critical_scripts' ), 20, 3 );

		add_action( 'wp_footer', array( __CLASS__, 'render_fallback_assets' ), 5 );
		add_action( 'wp_head', array( __CLASS__, 'print_hide_joinchat_css' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dequeue_joinchat_assets' ), 100 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_mail_failure' ) );
		add_action( 'phpmailer_init', array( __CLASS__, 'fix_phpmailer' ), 20 );

		add_filter( 'wp_mail_from', array( __CLASS__, 'mail_from_address' ), 20 );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'mail_from_name' ), 20 );
		add_filter( 'avf_form_sendto', array( __CLASS__, 'force_form_recipients' ), 20, 3 );
		add_filter( 'avf_form_from', array( __CLASS__, 'force_form_from' ), 20, 3 );
		add_filter( 'avf_contact_form_incoming_mail', array( __CLASS__, 'fix_incoming_mail' ), 20, 6 );

		// JoinChat stores settings in option "joinchat" (array). Stamp telephone only.
		add_filter( 'option_joinchat', array( __CLASS__, 'force_joinchat_phone' ), 20 );
		add_action( 'init', array( __CLASS__, 'persist_joinchat_phone' ), 20 );
		add_filter( 'joinchat_show', '__return_false', 100 );
		add_filter( 'joinchat_disable_front', '__return_true', 100 );

		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'template_redirect', array( __CLASS__, 'start_html_buffer' ), 1 );
		add_filter( 'wpseo_sitemap_entry', array( __CLASS__, 'filter_sitemap_entry' ), 10, 3 );
	}

	/** Early CSS so JoinChat cannot paint even before footer fallback loads. */
	public static function print_hide_joinchat_css() {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="cindemir-hide-joinchat">.joinchat,.joinchat--show{display:none!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important}</style>' . "\n";
	}

	/** Stop JoinChat CSS/JS from loading — one floating button only. */
	public static function dequeue_joinchat_assets() {
		if ( is_admin() ) {
			return;
		}
		$handles = array( 'joinchat', 'joinchat-js', 'joinchat-css', 'creame-whatsapp-me', 'whatsapp-me' );
		foreach ( $handles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}

	/** Normalize JoinChat settings array to the office WhatsApp number. */
	public static function force_joinchat_phone( $settings ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}
		$settings['telephone'] = self::whatsapp_digits();
		return $settings;
	}

	/** Persist the office number into the JoinChat option once so widget HTML is correct. */
	public static function persist_joinchat_phone() {
		remove_filter( 'option_joinchat', array( __CLASS__, 'force_joinchat_phone' ), 20 );
		$settings = get_option( 'joinchat', null );
		add_filter( 'option_joinchat', array( __CLASS__, 'force_joinchat_phone' ), 20 );

		if ( ! is_array( $settings ) ) {
			return;
		}
		$phone = self::whatsapp_digits();
		if ( isset( $settings['telephone'] ) && (string) $settings['telephone'] === $phone ) {
			return;
		}
		$settings['telephone'] = $phone;
		update_option( 'joinchat', $settings, false );
	}

	private static function whatsapp_digits() {
		$phone  = apply_filters( 'cindemir_whatsapp_phone', self::WHATSAPP_PHONE );
		$digits = preg_replace( '/\D+/', '', (string) $phone );
		if ( ! is_string( $digits ) || '' === $digits ) {
			return self::WHATSAPP_PHONE;
		}
		// Guard against intl-tel-input storing the office number under +1 (US/CA).
		if ( '1902165506775' === $digits || ( strlen( $digits ) > 12 && 0 === strpos( $digits, '1' ) && substr( $digits, -12 ) === self::WHATSAPP_PHONE ) ) {
			return self::WHATSAPP_PHONE;
		}
		if ( in_array( $digits, array( self::WHATSAPP_PHONE_OLD, '05325680647', '5325680647' ), true ) ) {
			return self::WHATSAPP_PHONE;
		}
		return $digits;
	}

	/** Use a domain mailbox as envelope sender (visitor email as From gets dropped on Bluehost). */
	public static function mail_from_address( $email ) {
		if ( self::is_enfold_contact_submit() ) {
			return self::MAIL_FROM;
		}
		return $email;
	}

	public static function mail_from_name( $name ) {
		if ( self::is_enfold_contact_submit() ) {
			return 'Cindemir Law Office';
		}
		return $name;
	}

	public static function fix_phpmailer( $phpmailer ) {
		if ( ! self::is_enfold_contact_submit() ) {
			return;
		}
		$phpmailer->setFrom( self::MAIL_FROM, 'Cindemir Law Office', false );
		$phpmailer->Sender = self::MAIL_FROM;
	}

	public static function force_form_recipients( $to, $new_post, $form_params ) {
		if ( ! self::is_enfold_contact_submit() ) {
			return $to;
		}
		return self::recipient_list();
	}

	public static function force_form_from( $from, $new_post, $form_params ) {
		if ( ! self::is_enfold_contact_submit() ) {
			return $from;
		}
		return self::MAIL_FROM;
	}

	public static function fix_incoming_mail( $mail_array, $new_post, $form_params, $form, $from, $from_filtered ) {
		if ( ! self::is_enfold_contact_submit() ) {
			return $mail_array;
		}

		$mail_array['To'] = self::recipient_list();

		$visitor = '';
		if ( ! empty( $from ) && is_email( $from ) ) {
			$visitor = $from;
		} else {
			foreach ( $new_post as $value ) {
				if ( is_string( $value ) && is_email( $value ) ) {
					$visitor = $value;
					break;
				}
			}
		}
		if ( $visitor ) {
			$mail_array['Reply-To'] = $visitor;
		}

		$mail_array['From'] = sprintf( '%s <%s>', 'Cindemir Law Office', self::MAIL_FROM );
		return $mail_array;
	}

	private static function recipient_list() {
		$list = array( self::MAIL_TO );
		$admin = get_option( 'admin_email' );
		if ( is_email( $admin ) && ! in_array( $admin, $list, true ) ) {
			$list[] = $admin;
		}
		return $list;
	}

	private static function is_enfold_contact_submit() {
		if ( empty( $_POST['ajax'] ) || empty( $_POST['avia_generated_form1'] ) ) {
			return false;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		return ( false !== strpos( $uri, '/contacts' ) );
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
			'cindemir-contact-form-fallback',
			'cindemir-whatsapp-fallback',
			'__cindemirContactBound',
			'data-nowprocket',
		);
		return array_values( array_unique( array_merge( $exclusions, $patterns ) ) );
	}

	/** Remove delay-load attributes from scripts required for forms/chat. */
	public static function exclude_critical_scripts( $tag, $handle, $src ) {
		if ( ! $src && false === strpos( (string) $tag, 'cindemir-' ) ) {
			return $tag;
		}
		$needles = array(
			'debloat/js/bb1fbc',
			'debloat/js/bcab097',
			'joinchat',
			'jquery',
			'cindemir-contact-form-fallback',
			'cindemir-whatsapp-fallback',
		);
		$match = false;
		$hay   = (string) $src . (string) $tag . (string) $handle;
		foreach ( $needles as $needle ) {
			if ( false !== strpos( $hay, $needle ) ) {
				$match = true;
				break;
			}
		}
		if ( ! $match ) {
			return $tag;
		}
		$tag = preg_replace( '/\sdata-rocketlazyloadscript=(["\']).*?\1/i', '', $tag );
		$tag = preg_replace( '/\stype=(["\'])rocketlazyloadscript\1/i', ' type="text/javascript"', $tag );
		$tag = str_replace( ' data-cfasync="false"', '', $tag );
		if ( false === strpos( $tag, 'data-cfasync' ) ) {
			$tag = str_replace( '<script ', '<script data-cfasync="false" data-nowprocket nowprocket data-no-minify="1" data-no-optimize="1" ', $tag );
		}
		return $tag;
	}

	public static function start_html_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'rewrite_legacy_assets' ) );
	}

	public static function rewrite_legacy_assets( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return is_string( $html ) ? $html : '';
		}
		$map = array(
			'https://cindemirlaw.com/chinese/wp-content/uploads/2014/11/white-2-copy.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
			'https://cindemirlaw.com/russian/wp-content/uploads/2014/11/white-2-copy.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
			'/chinese/wp-content/uploads/2014/11/white-2-copy.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
			'/russian/wp-content/uploads/2014/11/white-2-copy.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		);
		foreach ( $map as $from => $to ) {
			$html = str_replace( $from, $to, $html );
		}
		$replacements = array(
			'#(https?://(?:www\.)?cindemirlaw\.com)/(?:russian|chinese)/wp-content/#i' => '$1/wp-content/',
			'#((?:href|src)=(["\']))(?:https?://(?:www\.)?cindemirlaw\.com)?/(?:russian|chinese)/wp-content/#i' => '$1$2/wp-content/',
			'#((?:href|src|data-lazy-src)=(["\']))(?:https?:)?//d\.barobirlik\.org\.tr/amblem/tbb_amblem_60\.png\2#i' => '$1' . esc_url( get_option( 'cindemir_tbb_badge_local', 'https://cindemirlaw.com/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg' ) ) . '$2',
			'#<link\b[^>]*\bhreflang=(["\'])[^"\']+\1[^>]*\bhref=(["\'])[^"\']*contacts-2[^"\']*\2[^>]*>#i' => '',
		);
		foreach ( $replacements as $pattern => $replace ) {
			$next = preg_replace( $pattern, $replace, $html );
			if ( is_string( $next ) ) {
				$html = $next;
			}
		}
		$html = str_replace( '/contacts-2/?lang=zh-hans', '/contacts/?lang=zh-hans', $html );
		$html = str_replace( '/contacts-2?lang=zh-hans', '/contacts/?lang=zh-hans', $html );
		$html = self::rewrite_whatsapp_numbers( $html );
		$html = self::strip_joinchat_markup( $html );
		$next = preg_replace_callback(
			'#(\shref=(["\']))(https?://(?:www\.)?cindemirlaw\.com)(/[^"\']*?)(\?[^"\']*lang=[^"\']*)(\2)#i',
			function ( $m ) {
				$path = isset( $m[4] ) ? rawurldecode( $m[4] ) : '';
				if ( $path && ! preg_match( '/[А-Яа-яЁё]/u', $path ) && ! preg_match( '#^/fde#i', $path ) ) {
					return $m[1] . $m[3] . user_trailingslashit( $path ) . $m[6];
				}
				return $m[0];
			},
			$html
		);
		return is_string( $next ) ? $next : $html;
	}

	public static function filter_sitemap_entry( $url, $type, $object ) {
		if ( ! is_array( $url ) || empty( $url['loc'] ) ) {
			return $url;
		}
		$parts = wp_parse_url( $url['loc'] );
		if ( ! empty( $parts['query'] ) && preg_match( '/(?:^|&)lang=/', $parts['query'] ) ) {
			return false;
		}
		return $url;
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
		register_rest_route(
			'cindemir/v1',
			'/setup-zh-contacts',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'setup_zh_contacts' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'cindemir/v1',
			'/apply-seo-meta',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'apply_seo_meta' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'cindemir/v1',
			'/pull-plugins',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'pull_plugins' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'cindemir/v1',
			'/setup-press',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'setup_press' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'cindemir/v1',
			'/fix-ahrefs',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'fix_ahrefs' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'cindemir/v1',
			'/setup-privacy-i18n',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'setup_privacy_i18n' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function joinchat_track_stub( $request ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/** Create or update RU/ZH privacy-policy pages (WPML) — informational only, no consent UI. */
	public static function setup_privacy_i18n( $request ) {
		$key = $request->get_param( 'key' );
		if ( ! in_array( $key, array( 'seo-pack-2026', 'wpml-setup-zh-2026' ), true ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}

		$source_id = 390;
		$source    = get_post( $source_id );
		if ( ! $source ) {
			return new WP_REST_Response( array( 'error' => 'Source privacy page not found' ), 500 );
		}

		$trid = $source_id;
		if ( isset( $GLOBALS['sitepress'] ) && $GLOBALS['sitepress'] ) {
			$trid = (int) $GLOBALS['sitepress']->get_element_trid( $source_id, 'post_page' );
			if ( ! $trid ) {
				$GLOBALS['sitepress']->set_element_language_details( $source_id, 'post_page', null, 'en' );
				$trid = (int) $GLOBALS['sitepress']->get_element_trid( $source_id, 'post_page' );
			}
		}

		$langs = array(
			'ru'      => array(
				'title'   => 'Политика конфиденциальности',
				'meta'    => 'Политика конфиденциальности Cindemir Law Office: как обрабатываются персональные данные посетителей сайта cindemirlaw.com.',
				'canonic' => 'https://cindemirlaw.com/privacy-policy/?lang=ru',
			),
			'zh-hans' => array(
				'title'   => '隐私政策',
				'meta'    => '辛德米尔律师事务所隐私政策：说明 cindemirlaw.com 如何收集、使用和保护访客个人数据。',
				'canonic' => 'https://cindemirlaw.com/privacy-policy/?lang=zh-hans',
			),
		);

		global $wpdb;
		$created = array( 'en' => $source_id );
		foreach ( $langs as $lang => $cfg ) {
			$existing = 0;
			if ( $trid ) {
				$existing = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT p.ID FROM {$wpdb->posts} p
						 INNER JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = 'post_page'
						 WHERE t.trid = %d AND t.language_code = %s AND p.post_status IN ('publish','draft')",
						$trid,
						$lang
					)
				);
			}
			$body = self::privacy_page_body( $lang );
			if ( $existing ) {
				wp_update_post(
					array(
						'ID'           => $existing,
						'post_title'   => $cfg['title'],
						'post_name'    => 'privacy-policy',
						'post_content' => $body,
						'post_status'  => 'publish',
					)
				);
				$pid = $existing;
			} else {
				$pid = wp_insert_post(
					array(
						'post_title'   => $cfg['title'],
						'post_name'    => 'privacy-policy',
						'post_content' => $body,
						'post_status'  => 'publish',
						'post_type'    => 'page',
						'post_author'  => $source->post_author,
					),
					true
				);
				if ( is_wp_error( $pid ) ) {
					$created[ $lang ] = array( 'ok' => false, 'error' => $pid->get_error_message() );
					continue;
				}
				if ( isset( $GLOBALS['sitepress'] ) && $GLOBALS['sitepress'] && $trid ) {
					$GLOBALS['sitepress']->set_element_language_details( (int) $pid, 'post_page', $trid, $lang, 'en' );
				}
			}
			$wpdb->update( $wpdb->posts, array( 'post_name' => 'privacy-policy' ), array( 'ID' => (int) $pid ), array( '%s' ), array( '%d' ) );
			update_post_meta( (int) $pid, '_yoast_wpseo_canonical', $cfg['canonic'] );
			update_post_meta( (int) $pid, '_yoast_wpseo_title', $cfg['title'] . ' | Cindemir Law Office' );
			update_post_meta( (int) $pid, '_yoast_wpseo_metadesc', $cfg['meta'] );
			delete_post_meta( (int) $pid, '_wp_old_slug' );
			clean_post_cache( (int) $pid );
			$created[ $lang ] = array( 'ok' => true, 'post_id' => (int) $pid );
		}

		flush_rewrite_rules( false );
		wp_cache_flush();
		if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
			WPSEO_Sitemaps_Cache::clear();
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		return new WP_REST_Response(
			array(
				'ok'    => true,
				'pages' => $created,
				'urls'  => array(
					'en' => 'https://cindemirlaw.com/privacy-policy/',
					'ru' => 'https://cindemirlaw.com/privacy-policy/?lang=ru',
					'zh' => 'https://cindemirlaw.com/privacy-policy/?lang=zh-hans',
				),
			),
			200
		);
	}

	/** Privacy / KVKK page body per language (informational text only). */
	private static function privacy_page_body( $lang ) {
		$bodies = array(
			'ru'      => '<h2>Кто мы</h2>
<p>Владелец сайта cindemirlaw.com — Cindemir Law Office (İstanbul). По вопросам персональных данных: <a href="mailto:gokhan@cindemir.av.tr">gokhan@cindemir.av.tr</a>.</p>
<h2>Какие данные мы обрабатываем</h2>
<ul>
<li>данные, которые вы добровольно указываете в контактной форме (имя, e-mail, телефон, текст сообщения);</li>
<li>технические данные сервера (IP-адрес, тип браузера, дата и время запроса) — для безопасности и стабильной работы сайта;</li>
<li>необходимые технические cookie (например, язык интерфейса) — без них сайт не может работать корректно.</li>
</ul>
<h2>Цели и правовые основания</h2>
<p>Данные используются для ответа на запросы, оказания юридических услуг, выполнения договорных и законных обязанностей, а также для защиты законных интересов офиса. Отдельное согласие через флажок на сайте не требуется, если вы сами направляете нам сообщение.</p>
<h2>Передача третьим лицам</h2>
<p>Данные могут обрабатываться хостинг-провайдером, почтовыми сервисами и аналитическими инструментами (например, Google Analytics), только в объёме, необходимом для работы сайта. При переходе в WhatsApp действует политика Meta/WhatsApp.</p>
<h2>Срок хранения</h2>
<p>Контактные данные хранятся столько, сколько нужно для ответа на запрос и ведения дела, либо в сроки, установленные законом.</p>
<h2>Ваши права</h2>
<p>Вы можете запросить доступ, исправление, удаление или ограничение обработки данных, а также подать жалобу в KVKK (Турция). Для запроса напишите на <a href="mailto:gokhan@cindemir.av.tr">gokhan@cindemir.av.tr</a>.</p>',
			'zh-hans' => '<h2>我们是谁</h2>
<p>网站 cindemirlaw.com 由 Cindemir Law Office（伊斯坦布尔）运营。个人数据相关咨询：<a href="mailto:gokhan@cindemir.av.tr">gokhan@cindemir.av.tr</a>。</p>
<h2>我们处理哪些数据</h2>
<ul>
<li>您在联系表单中自愿提供的信息（姓名、电子邮箱、电话、留言内容）；</li>
<li>服务器技术日志（IP 地址、浏览器类型、访问时间）——用于安全与网站稳定运行；</li>
<li>必要的技术性 Cookie（例如语言偏好）——保障网站基本功能。</li>
</ul>
<h2>处理目的与法律依据</h2>
<p>数据用于回复咨询、提供法律服务、履行合同及法定义务，并维护律所的合法利益。当您主动提交表单时，无需额外勾选同意框。</p>
<h2>向第三方提供</h2>
<p>数据可能由主机服务商、邮件系统及分析工具（如 Google Analytics）在必要范围内处理。通过 WhatsApp 联系时，适用 Meta/WhatsApp 的相关政策。</p>
<h2>保存期限</h2>
<p>联系信息在回复咨询及办理案件所需期间内保存，或依照法律要求的期限保存。</p>
<h2>您的权利</h2>
<p>您可依法请求查阅、更正、删除或限制处理个人数据，亦可向土耳其 KVKK 机构投诉。请联系 <a href="mailto:gokhan@cindemir.av.tr">gokhan@cindemir.av.tr</a>。</p>',
		);
		return isset( $bodies[ $lang ] ) ? $bodies[ $lang ] : '';
	}

	private static function privacy_lang() {
		if ( ! empty( $_GET['lang'] ) ) {
			$get = sanitize_key( wp_unslash( $_GET['lang'] ) );
			if ( $get ) {
				return $get;
			}
		}
		if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
			return (string) ICL_LANGUAGE_CODE;
		}
		if ( function_exists( 'apply_filters' ) ) {
			$wpml = apply_filters( 'wpml_current_language', null );
			if ( is_string( $wpml ) && '' !== $wpml ) {
				return $wpml;
			}
		}
		return 'en';
	}

	private static function privacy_policy_url() {
		$lang = self::privacy_lang();
		$url  = home_url( '/privacy-policy/' );
		if ( $lang && ! in_array( $lang, array( 'en', 'en-us', 'en_us' ), true ) ) {
			$url = add_query_arg( 'lang', $lang, $url );
		}
		return $url;
	}

	/** Contact-form informational notice — no consent checkbox. */
	private static function privacy_form_notice_strings() {
		$lang = self::privacy_lang();
		$map  = array(
			'en'      => array(
				'text' => 'When you submit this form, personal data is processed under KVKK so we can respond to your enquiry. Details and your rights are in our Privacy Policy.',
				'link' => 'KVKK / Privacy Policy',
			),
			'ru'      => array(
				'text' => 'Отправляя форму, вы передаёте персональные данные Cindemir Law Office в соответствии с KVKK — только для ответа на ваш запрос. Подробнее и ваши права — в Политике конфиденциальности.',
				'link' => 'KVKK / Политика конфиденциальности',
			),
			'zh-hans' => array(
				'text' => '提交本表单时，我们将依 KVKK 处理您的个人数据，以便回复咨询。详情与您的权利见隐私政策。',
				'link' => 'KVKK / 隐私政策',
			),
			'zh'      => array(
				'text' => '提交本表单时，我们将依 KVKK 处理您的个人数据，以便回复咨询。详情与您的权利见隐私政策。',
				'link' => 'KVKK / 隐私政策',
			),
		);
		return isset( $map[ $lang ] ) ? $map[ $lang ] : $map['en'];
	}

	private static function render_privacy_form_notice() {
		if ( is_admin() || ! self::is_contacts_page() ) {
			return;
		}
		$s = self::privacy_form_notice_strings();
		?>
<p class="cindemir-privacy-form-notice" style="margin:1rem 0 1.5rem;font-size:14px;line-height:1.55;color:#555;">
	<?php echo esc_html( $s['text'] ); ?>
	<a href="<?php echo esc_url( self::privacy_policy_url() ); ?>"><?php echo esc_html( $s['link'] ); ?></a>.
</p>
<script data-nowprocket nowprocket data-no-minify="1" data-no-optimize="1" data-cfasync="false">
(function () {
	var note = document.querySelector('.cindemir-privacy-form-notice');
	if (!note) return;
	var form = document.querySelector('form.avia_ajax_form');
	if (form && form.parentNode) {
		form.parentNode.insertBefore(note, form);
	}
})();
</script>
		<?php
	}

	/** Stamp every JoinChat / wa.me telephone onto the office WhatsApp number. */
	private static function rewrite_whatsapp_numbers( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$phone = self::whatsapp_digits();
		$old   = self::WHATSAPP_PHONE_OLD;

		// Prefer plain string replace so a failed PCRE never nulls the whole page buffer.
		$html = str_replace(
			array(
				$old,
				'+' . $old,
				'wa.me/' . $old,
				'phone=' . $old,
				'"telephone":"' . $old . '"',
				"'telephone':'" . $old . "'",
			),
			array(
				$phone,
				'+' . $phone,
				'wa.me/' . $phone,
				'phone=' . $phone,
				'"telephone":"' . $phone . '"',
				"'telephone':'" . $phone . "'",
			),
			$html
		);

		return is_string( $html ) ? $html : '';
	}

	/** Remove the JoinChat floating widget markup so only #cindemir-wa-fallback remains. */
	private static function strip_joinchat_markup( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		$needle = 'class="joinchat';
		$pos    = stripos( $html, $needle );
		if ( false === $pos ) {
			$needle = "class='joinchat";
			$pos    = stripos( $html, $needle );
		}
		if ( false === $pos ) {
			return $html;
		}

		$start = strrpos( substr( $html, 0, $pos ), '<div' );
		if ( false === $start ) {
			return $html;
		}

		$depth = 0;
		$len   = strlen( $html );
		$i     = $start;
		$end   = null;
		while ( $i < $len ) {
			$next_open  = stripos( $html, '<div', $i );
			$next_close = stripos( $html, '</div>', $i );
			if ( false === $next_close ) {
				break;
			}
			if ( false !== $next_open && $next_open < $next_close ) {
				++$depth;
				$i = $next_open + 4;
				continue;
			}
			--$depth;
			$i = $next_close + 6;
			if ( 0 === $depth ) {
				$end = $i;
				break;
			}
		}

		if ( null === $end ) {
			return $html;
		}

		$html = substr( $html, 0, $start ) . substr( $html, $end );

		// Drop JoinChat CSS/JS leftovers that would otherwise re-create the button.
		$html = preg_replace( '#<style\b[^>]*(?:id|class)=(["\'])[^"\']*joinchat[^"\']*\1[^>]*>[\s\S]*?</style>#i', '', $html );
		$html = preg_replace( '#<script\b[^>]*(?:id|src)=(["\'])[^"\']*joinchat[^"\']*\1[^>]*>[\s\S]*?</script>#i', '', $html );
		$html = preg_replace( '#<link\b[^>]*href=(["\'])[^"\']*joinchat[^"\']*\1[^>]*>#i', '', $html );

		return is_string( $html ) ? $html : '';
	}

	/** One-time: create WPML Chinese translation for Contacts (EN post ID 20). */
	public static function setup_zh_contacts( $request ) {
		$key = $request->get_param( 'key' );
		if ( ! in_array( $key, array( 'wpml-setup-zh-2026', 'seo-pack-2026' ), true ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}

		$source_id = 20;
		$trid      = 20;
		$lang      = 'zh-hans';
		$source    = get_post( $source_id );

		if ( ! $source ) {
			return new WP_REST_Response( array( 'error' => 'Source post not found' ), 500 );
		}

		global $wpdb;
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = 'post_page'
				 WHERE t.trid = %d AND t.language_code = %s AND p.post_status = 'publish'",
				$trid,
				$lang
			)
		);

		if ( $existing ) {
			self::fix_zh_contacts_slug( (int) $existing );
			clean_post_cache( (int) $existing );
			wp_cache_flush();

			return new WP_REST_Response(
				array(
					'status'    => 'already_exists',
					'post_id'   => (int) $existing,
					'permalink' => get_permalink( (int) $existing ),
					'zh_url'    => 'https://cindemirlaw.com/contacts/?lang=zh-hans',
				),
				200
			);
		}

		$content      = $source->post_content;
		$replacements = self::zh_contacts_replacements();

		foreach ( $replacements as $from => $to ) {
			$content = str_replace( $from, $to, $content );
		}

		$new_id = wp_insert_post(
			array(
				'post_title'     => '联系我们',
				'post_name'      => 'contacts',
				'post_content'   => $content,
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_author'    => $source->post_author,
				'menu_order'     => $source->menu_order,
				'comment_status' => $source->comment_status,
				'ping_status'    => $source->ping_status,
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			return new WP_REST_Response( array( 'error' => $new_id->get_error_message() ), 500 );
		}

		$meta = get_post_meta( $source_id );
		foreach ( $meta as $meta_key => $values ) {
			if ( 0 === strpos( $meta_key, '_edit_' ) || '_wp_old_slug' === $meta_key ) {
				continue;
			}
			foreach ( $values as $value ) {
				$val = maybe_unserialize( $value );
				if ( is_string( $val ) ) {
					foreach ( $replacements as $from => $to ) {
						$val = str_replace( $from, $to, $val );
					}
				}
				update_post_meta( $new_id, $meta_key, $val );
			}
		}

		do_action(
			'wpml_set_element_language_details',
			array(
				'element_id'           => $new_id,
				'element_type'         => 'post_page',
				'trid'                 => $trid,
				'language_code'        => $lang,
				'source_language_code' => 'en',
			)
		);

		if ( isset( $GLOBALS['sitepress'] ) && $GLOBALS['sitepress'] ) {
			$GLOBALS['sitepress']->set_element_language_details( $new_id, 'post_page', $trid, $lang, 'en' );
		}

		self::fix_zh_contacts_slug( $new_id );
		clean_post_cache( $new_id );
		wp_cache_flush();

		return new WP_REST_Response(
			array(
				'status'    => 'created',
				'post_id'   => $new_id,
				'permalink' => get_permalink( $new_id ),
				'zh_url'    => 'https://cindemirlaw.com/contacts/?lang=zh-hans',
			),
			200
		);
	}

	/** Apply reklamsız Yoast meta descriptions for 14 pages (Görev 1). */
	public static function apply_seo_meta( $request ) {
		$key = $request->get_param( 'key' );
		if ( 'seo-pack-2026' !== $key ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		if ( $request->get_param( 'pull' ) ) {
			return self::pull_plugins( $request );
		}

		$pages = array(
			43   => "Cindemir Law Office'in Türk hukuku ve yabancılara yönelik hukuki konular hakkında hazırladığı video içeriklerinin derlendiği sayfa.",
			2    => 'Cindemir Law Office — независимая юридическая фирма в Стамбуле, работающая с 2004 года в сфере турецкого и международного права.',
			105  => 'Статьи о турецком праве: гражданское, коммерческое, миграционное и уголовное право Турции для иностранных граждан и компаний.',
			3884 => "Hafız Hüseyin Hüsnü Efendi'nin biyografisi: 1847'de Batum'da doğan bu ismin hayatı, ilmî kişiliği ve tarihsel arka planı ele alınır.",
			16   => "Cindemir Law Office, 2004'ten bu yana İstanbul'da faaliyet gösteren, Türk ve uluslararası hukuk alanında çalışan bağımsız bir hukuk bürosudur.",
			2427 => 'Команда Cindemir Law Office: адвокаты и консультанты, работающие в области турецкого и международного права в Стамбуле.',
			392  => "Cindemir Law Office'in müvekkillerle iletişimi ve Türkiye'deki hukuki süreçlerde yabancılara sağladığı destek hakkında bilgi.",
			51   => "Cindemir Law Office'ten haberler ve etkinlikler: yabancı birey ve şirketleri ilgilendiren Türk hukukundaki gelişmelere dair güncellemeler.",
			19   => 'Cindemir Law Office ekibi: İstanbul\'da Türk ve uluslararası hukuk alanında çalışan avukatlar ve danışmanlar hakkında bilgi.',
			103  => 'О порядке общения адвоката с подзащитным в Турции: обмен информацией, права и обязанности сторон в уголовном процессе.',
			17   => 'Türk hukukuna dair makaleler: yabancı birey ve şirketleri ilgilendiren medeni, ticari, göç ve ceza hukuku konuları ele alınır.',
			390  => "Cindemir Law Office'in gizlilik politikası: web sitesi ziyaretçilerine ait kişisel verilerin nasıl toplandığı, kullanıldığı ve korunduğu açıklanır.",
			56   => 'Юридические услуги в Турции: корпоративное, миграционное, семейное и уголовное право для иностранных клиентов в Стамбуле.',
			3874 => "Cindemir Law Office'in tarihçesi: Osmanlı mahkemelerinden günümüze uzanan hukuki geçmişi İstanbul üzerinden anlatılır.",
		);

		$results = array();
		foreach ( $pages as $id => $desc ) {
			if ( ! get_post( $id ) ) {
				$results[ $id ] = array( 'ok' => false, 'error' => 'not_found' );
				continue;
			}
			update_post_meta( (int) $id, '_yoast_wpseo_metadesc', $desc );
			$stored = get_post_meta( (int) $id, '_yoast_wpseo_metadesc', true );
			$results[ $id ] = array(
				'ok'  => ( $stored === $desc ),
				'len' => function_exists( 'mb_strlen' ) ? mb_strlen( (string) $stored ) : strlen( (string) $stored ),
			);
		}

		update_option( 'cindemir_seo_meta_v1_applied', 1, false );
		wp_cache_flush();

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'version' => '1.8.1',
				'pages'   => $results,
			),
			200
		);
	}

	/** Pull latest mu-plugins from GitHub (remote deploy without SSH). */
	public static function pull_plugins( $request ) {
		$key = $request->get_param( 'key' );
		if ( 'seo-pack-2026' !== $key ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return new WP_REST_Response( array( 'error' => 'no mu dir' ), 500 );
		}
		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$base   = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/';
		$files  = array(
			'cindemir-seo-fixes.php'         => 40000,
			'cindemir-contact-fixes.php'     => 20000,
			'cindemir-expose-yoast-meta.php' => 2000,
			'cindemir-purge-cache.php'       => 500,
		);
		$out = array();
		foreach ( $files as $name => $min ) {
			$dest = trailingslashit( WPMU_PLUGIN_DIR ) . $name;
			$response = wp_remote_get(
				$base . $name,
				array(
					'timeout' => 60,
					'headers' => array( 'User-Agent' => 'CindemirPull/1.2.1' ),
				)
			);
			if ( is_wp_error( $response ) ) {
				$out[ $name ] = array( 'ok' => false, 'error' => $response->get_error_message() );
				continue;
			}
			$body = (string) wp_remote_retrieve_body( $response );
			$bytes = strlen( $body );
			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || $bytes < $min ) {
				$out[ $name ] = array( 'ok' => false, 'bytes' => $bytes );
				continue;
			}
			file_put_contents( $dest, $body );
			$out[ $name ] = array( 'ok' => true, 'bytes' => $bytes );
		}
		delete_option( 'cindemir_seo_fixes_version' );
		wp_cache_flush();
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		return new WP_REST_Response( array( 'ok' => true, 'files' => $out ), 200 );
	}

	/** One-shot Ahrefs cleanup: WPML contacts + press + meta + cache. */
	public static function fix_ahrefs( $request ) {
		$key = $request->get_param( 'key' );
		if ( 'seo-pack-2026' !== $key ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		$zh    = self::setup_zh_contacts( $request );
		$press = self::setup_press( $request );
		$meta  = self::apply_seo_meta( $request );
		wp_cache_flush();
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		return new WP_REST_Response(
			array(
				'ok'    => true,
				'zh'    => $zh->get_data(),
				'press' => $press->get_data(),
				'meta'  => $meta->get_data(),
			),
			200
		);
	}

	/**
	 * Import press pages from cindemir.av.tr (EN kept locally; RU/ZH translated).
	 * Stops outsourcing /press/ to av.tr.
	 */
	public static function setup_press( $request ) {
		$key = $request->get_param( 'key' );
		if ( ! in_array( $key, array( 'seo-pack-2026', 'wpml-setup-zh-2026' ), true ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}

		$en = get_page_by_path( 'press' );
		if ( ! $en ) {
			$en_id = wp_insert_post(
				array(
					'post_title'   => 'Press',
					'post_name'    => 'press',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => self::build_press_html_from_remote( 'we-are-in-news', 'en' ),
				),
				true
			);
			if ( is_wp_error( $en_id ) ) {
				return new WP_REST_Response( array( 'error' => $en_id->get_error_message() ), 500 );
			}
			$en = get_post( $en_id );
		}

		$en_id = (int) $en->ID;
		update_post_meta( $en_id, '_yoast_wpseo_canonical', 'https://cindemirlaw.com/press/' );
		update_post_meta( $en_id, '_yoast_wpseo_title', 'Press & Media Appearances | Cindemir Law Office' );
		update_post_meta(
			$en_id,
			'_yoast_wpseo_metadesc',
			'Press coverage and media appearances of Cindemir Law Office: television, newspapers, and agency reports related to legal matters in Turkey.'
		);

		$trid = $en_id;
		if ( isset( $GLOBALS['sitepress'] ) && $GLOBALS['sitepress'] ) {
			$trid = $GLOBALS['sitepress']->get_element_trid( $en_id, 'post_page' );
			if ( ! $trid ) {
				$GLOBALS['sitepress']->set_element_language_details( $en_id, 'post_page', null, 'en' );
				$trid = $GLOBALS['sitepress']->get_element_trid( $en_id, 'post_page' );
			}
		}

		$langs = array(
			'ru'      => array(
				'slug'    => 'support-ru',
				'title'   => 'О нас в прессе',
				'meta'    => 'Публикации и выступления Cindemir Law Office в СМИ и на телевидении: комментарии по значимым делам в Турции.',
				'canonic' => 'https://cindemirlaw.com/press/?lang=ru',
			),
			'zh-hans' => array(
				'slug'    => 'support-zn',
				'title'   => '媒体报道',
				'meta'    => '辛德米尔律师事务所媒体与电视报道：就土耳其重大法律案件发表的专业评论与新闻稿。',
				'canonic' => 'https://cindemirlaw.com/press/?lang=zh-hans',
			),
		);

		$created = array( 'en' => $en_id );
		foreach ( $langs as $lang => $cfg ) {
			$existing = 0;
			global $wpdb;
			if ( $trid ) {
				$existing = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT p.ID FROM {$wpdb->posts} p
						 INNER JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = 'post_page'
						 WHERE t.trid = %d AND t.language_code = %s AND p.post_status IN ('publish','draft')",
						$trid,
						$lang
					)
				);
			}
			$body = self::build_press_html_from_remote( $cfg['slug'], $lang );
			if ( '' === $body ) {
				$created[ $lang ] = array( 'ok' => false, 'error' => 'empty remote content' );
				continue;
			}
			if ( $existing ) {
				wp_update_post(
					array(
						'ID'           => $existing,
						'post_title'   => $cfg['title'],
						'post_name'    => 'press',
						'post_content' => $body,
						'post_status'  => 'publish',
					)
				);
				$pid = $existing;
			} else {
				$pid = wp_insert_post(
					array(
						'post_title'   => $cfg['title'],
						'post_name'    => 'press',
						'post_content' => $body,
						'post_status'  => 'publish',
						'post_type'    => 'page',
					),
					true
				);
				if ( is_wp_error( $pid ) ) {
					$created[ $lang ] = array( 'ok' => false, 'error' => $pid->get_error_message() );
					continue;
				}
				if ( isset( $GLOBALS['sitepress'] ) && $GLOBALS['sitepress'] && $trid ) {
					$GLOBALS['sitepress']->set_element_language_details( (int) $pid, 'post_page', $trid, $lang, 'en' );
				}
			}
			$wpdb->update( $wpdb->posts, array( 'post_name' => 'press' ), array( 'ID' => (int) $pid ), array( '%s' ), array( '%d' ) );
			update_post_meta( (int) $pid, '_yoast_wpseo_canonical', $cfg['canonic'] );
			update_post_meta( (int) $pid, '_yoast_wpseo_title', $cfg['title'] . ' | Cindemir Law Office' );
			update_post_meta( (int) $pid, '_yoast_wpseo_metadesc', $cfg['meta'] );
			delete_post_meta( (int) $pid, '_wp_old_slug' );
			clean_post_cache( (int) $pid );
			$created[ $lang ] = array( 'ok' => true, 'post_id' => (int) $pid );
		}

		// Drop Redirection plugin rules that still send /press to av.tr when possible.
		self::remove_press_redirection_rules();

		flush_rewrite_rules( false );
		wp_cache_flush();
		if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
			WPSEO_Sitemaps_Cache::clear();
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		return new WP_REST_Response(
			array(
				'ok'    => true,
				'pages' => $created,
				'urls'  => array(
					'en' => 'https://cindemirlaw.com/press/',
					'ru' => 'https://cindemirlaw.com/press/?lang=ru',
					'zh' => 'https://cindemirlaw.com/press/?lang=zh-hans',
				),
			),
			200
		);
	}

	private static function build_press_html_from_remote( $slug, $lang ) {
		$url = 'https://cindemir.av.tr/wp-json/wp/v2/pages?slug=' . rawurlencode( $slug );
		$res = wp_remote_get(
			$url,
			array(
				'timeout' => 45,
				'headers' => array( 'User-Agent' => 'CindemirPressMigrate/1.0' ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return '';
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( empty( $data[0]['content']['rendered'] ) ) {
			return '';
		}
		return self::simplify_press_html( (string) $data[0]['content']['rendered'], $lang );
	}

	private static function simplify_press_html( $content, $lang ) {
		$parts = array();
		if ( preg_match( '/<h1[^>]*>(.*?)<\/h1>/is', $content, $m ) ) {
			$parts[] = '<h2>' . esc_html( wp_strip_all_tags( $m[1] ) ) . '</h2>';
		}
		if ( 'en' === $lang ) {
			if ( preg_match_all( '/<h5[^>]*>(.*?)<\/h5>/is', $content, $mm ) ) {
				foreach ( $mm[1] as $chunk ) {
					$t = trim( wp_strip_all_tags( $chunk ) );
					if ( $t ) {
						$parts[] = '<p><strong>' . esc_html( $t ) . '</strong></p>';
					}
				}
			}
			if ( preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $content, $mm ) ) {
				foreach ( $mm[1] as $chunk ) {
					$t = trim( wp_strip_all_tags( $chunk ) );
					if ( $t ) {
						$parts[] = '<h3>' . esc_html( $t ) . '</h3>';
					}
				}
			}
			if ( preg_match_all( '/<li[^>]*>([\s\S]*?)<\/li>/i', $content, $mm ) ) {
				foreach ( $mm[1] as $inner ) {
					if ( ! preg_match( '/<strong>(.*?)<\/strong>/is', $inner, $tm ) ) {
						continue;
					}
					$tit  = trim( wp_strip_all_tags( $tm[1] ) );
					$rest = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( preg_replace( '/<strong>.*?<\/strong>/is', '', $inner ) ) ) );
					$rest = trim( $rest, " \t\n\r\0\x0B–-" );
					$block = '<p><strong>' . esc_html( $tit ) . '</strong>';
					if ( $rest ) {
						$block .= ' — ' . esc_html( mb_substr( $rest, 0, 350 ) );
					}
					$block .= '</p>';
					if ( preg_match_all( '/href="(https?:\/\/[^"]+)"/i', $inner, $hm ) ) {
						foreach ( $hm[1] as $href ) {
							$block .= '<p><a href="' . esc_url( $href ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $href ) . '</a></p>';
						}
					}
					$parts[] = $block;
				}
			}
		} else {
			$tokens = preg_split( '/(<h3[^>]*>.*?<\/h3>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
			for ( $i = 0; $i < count( $tokens ); $i++ ) {
				if ( preg_match( '/<h3[^>]*>(.*?)<\/h3>/is', $tokens[ $i ], $hm ) ) {
					$parts[] = '<h3>' . esc_html( wp_strip_all_tags( $hm[1] ) ) . '</h3>';
					if ( isset( $tokens[ $i + 1 ] ) ) {
						$nxt = $tokens[ $i + 1 ];
						if ( preg_match_all( '/<p[^>]*>(.*?)<\/p>/is', $nxt, $pm ) ) {
							foreach ( $pm[1] as $phtml ) {
								$t = trim( wp_strip_all_tags( $phtml ) );
								if ( strlen( $t ) > 20 ) {
									$parts[] = '<p>' . esc_html( $t ) . '</p>';
								}
							}
						}
						if ( preg_match_all( '/<a[^>]+href="(https?:\/\/[^"]+)"[^>]*>(.*?)<\/a>/is', $nxt, $am ) ) {
							foreach ( $am[1] as $idx => $href ) {
								$label = trim( wp_strip_all_tags( $am[2][ $idx ] ) );
								$parts[] = '<p><a href="' . esc_url( $href ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ? $label : $href ) . '</a></p>';
							}
						}
					}
				}
			}
		}
		$out  = array();
		$prev = null;
		foreach ( $parts as $p ) {
			if ( $p !== $prev ) {
				$out[] = $p;
				$prev  = $p;
			}
		}
		return implode( "\n", $out );
	}

	private static function remove_press_redirection_rules() {
		global $wpdb;
		$table = $wpdb->prefix . 'redirection_items';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			$wpdb->query(
				"DELETE FROM {$table}
				 WHERE url LIKE '%/press%' OR url LIKE '%link9%'
				    OR action_data LIKE '%we-are-in-news%'
				    OR action_data LIKE '%cindemir.av.tr/en/we-are-in-news%'"
			);
		}

		// Yoast SEO Premium redirects (/press/ currently 301s with x-redirect-by: Yoast SEO Premium).
		self::remove_yoast_press_redirects();
	}

	private static function remove_yoast_press_redirects() {
		$needles = array(
			'/press',
			'/press/',
			'press',
			'/link9',
			'/link9/',
			'we-are-in-news',
			'cindemir.av.tr/en/we-are-in-news',
		);
		$option_names = array(
			'wpseo-premium-redirects-base',
			'wpseo-premium-redirects-export-plain',
			'wpseo-premium-redirects-export-regex',
			'wpseo-premium-redirects-regex',
		);
		foreach ( $option_names as $opt ) {
			$redirects = get_option( $opt );
			if ( ! is_array( $redirects ) || ! $redirects ) {
				continue;
			}
			$changed = false;
			foreach ( $redirects as $key => $row ) {
				$origin = '';
				$target = '';
				if ( is_array( $row ) ) {
					$origin = isset( $row['origin'] ) ? (string) $row['origin'] : (string) $key;
					$target = isset( $row['url'] ) ? (string) $row['url'] : ( isset( $row['target'] ) ? (string) $row['target'] : '' );
				} elseif ( is_string( $row ) ) {
					$origin = (string) $key;
					$target = $row;
				}
				$blob = strtolower( $origin . ' ' . $target . ' ' . (string) $key );
				foreach ( $needles as $n ) {
					if ( false !== strpos( $blob, strtolower( $n ) ) ) {
						unset( $redirects[ $key ] );
						$changed = true;
						break;
					}
				}
			}
			if ( $changed ) {
				update_option( $opt, $redirects, false );
			}
		}

		// Per-page Yoast “Redirect” meta on press translations.
		$ids = get_posts(
			array(
				'name'           => 'press',
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'fields'         => 'ids',
			)
		);
		foreach ( (array) $ids as $pid ) {
			delete_post_meta( (int) $pid, '_yoast_wpseo_redirect' );
			delete_post_meta( (int) $pid, 'wpseo-premium-redirects-base' );
		}
	}

	/** WPML translations share slug "contacts" per language; WP core may assign contacts-2. */
	private static function fix_zh_contacts_slug( $post_id ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			array( 'post_name' => 'contacts' ),
			array( 'ID' => (int) $post_id ),
			array( '%s' ),
			array( '%d' )
		);
		$canonical = 'https://cindemirlaw.com/contacts/?lang=zh-hans';
		update_post_meta( (int) $post_id, '_yoast_wpseo_canonical', $canonical );
		delete_post_meta( (int) $post_id, '_wp_old_slug' );
	}

	private static function zh_contacts_replacements() {
		return array(
			"heading='Contact Us'"               => "heading='联系我们'",
			"button='Submit'"                    => "button='立即发送'",
			"sent='Your message has been sent!'" => "sent='您的消息已发送！'",
			"label='Name'"                       => "label='姓名'",
			"label='E-Mail'"                     => "label='电子邮箱'",
			"label='Phone'"                      => "label='电话号码'",
			"label='Message'"                    => "label='留言'",
			'<h4>Cindemir Hukuk Bürosu / Cindemir Law Office</h4>' => '<h4>辛德米尔律师事务所 / Cindemir Law Office</h4>',
			'<strong>Adress:</strong>'           => '<strong>地址：</strong>',
			'<strong>Email:</strong>'            => '<strong>电子邮箱：</strong>',
			'<h4>Registered Electronic Mail (REM)</h4>' => '<h4>注册电子信箱 (KEP)</h4>',
			'<strong>Fax:</strong>'               => '<strong>传真：</strong>',
			'<strong>Phone:</strong>'             => '<strong>电话：</strong>',
			'<strong>Check İstanbul Ritim Residences in English </strong>' => '<strong>查看伊斯坦布尔 Ritim 住宅区（英文）</strong>',
			'<strong>Whatsapp:</strong>'          => '<strong>WhatsApp：</strong>',
		);
	}

	public static function log_mail_failure( $error ) {
		if ( ! is_wp_error( $error ) ) {
			return;
		}
		error_log( 'cindemirlaw wp_mail failed: ' . $error->get_error_message() );
	}

	private static function is_contacts_page() {
		if ( function_exists( 'is_page' ) && ( is_page( 'contacts' ) || is_page( array( 'contacts', 'contacts-2' ) ) ) ) {
			return true;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( false !== strpos( $path, '/contacts' ) ) {
			return true;
		}
		// Builder content may appear on non-/contacts paths; still enable fallback when form HTML is what we need.
		return false;
	}

	public static function render_fallback_assets() {
		if ( is_admin() ) {
			return;
		}
		// WhatsApp button site-wide; form notice/script only when contacts (or form present later via SEO inject).
		if ( ! self::is_contacts_page() ) {
			self::render_whatsapp_fallback_only();
			return;
		}
		self::render_privacy_form_notice();
		self::render_whatsapp_fallback_only();
		self::render_contact_form_fallback_script();
	}

	private static function render_whatsapp_fallback_only() {
		if ( is_admin() ) {
			return;
		}
		$phone = self::whatsapp_digits();
		$text  = apply_filters(
			'cindemir_whatsapp_message',
			'Hallo, I found you through your website cindemirlaw.com'
		);
		$url   = 'https://wa.me/' . $phone;
		if ( $text ) {
			$url .= '?text=' . rawurlencode( $text );
		}
		?>
<style id="cindemir-whatsapp-fallback-css">
/* Single WhatsApp button only. */
.joinchat,.joinchat--show{display:none!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important}
#cindemir-wa-fallback{position:fixed;z-index:999990;left:20px;right:auto;bottom:20px;width:60px;height:60px;border-radius:50%;background:#25d366;box-shadow:0 4px 12px rgba(0,0,0,.25);display:flex!important;align-items:center;justify-content:center;text-decoration:none}
#cindemir-wa-fallback svg{width:34px;height:34px;fill:#fff}
</style>
<a id="cindemir-wa-fallback" class="is-visible" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
	<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.516 3.516c4.686-4.686 12.284-4.686 16.97 0s4.686 12.283 0 16.97a12 12 0 0 1-13.754 2.299l-5.814.735a.392.392 0 0 1-.438-.44l.748-5.788A12 12 0 0 1 3.517 3.517zm3.61 17.043.3.158a9.85 9.85 0 0 0 11.534-1.758c3.843-3.843 3.843-10.074 0-13.918s-10.075-3.843-13.918 0a9.85 9.85 0 0 0-1.747 11.554l.16.303-.51 3.942a.196.196 0 0 0 .219.22zm6.534-7.003-.933 1.164a9.84 9.84 0 0 1-3.497-3.495l1.166-.933a.79.79 0 0 0 .23-.94L9.561 6.96a.79.79 0 0 0-.924-.445l-2.023.524a.797.797 0 0 0-.588.88 11.754 11.754 0 0 0 10.005 10.005.797.797 0 0 0 .88-.587l.525-2.023a.79.79 0 0 0-.445-.923L14.6 13.327a.79.79 0 0 0-.94.23z"/></svg>
</a>
<script id="cindemir-whatsapp-fallback-js" data-nowprocket nowprocket data-no-minify="1" data-no-optimize="1" data-cfasync="false">
(function () {
	var PHONE = '<?php echo esc_js( $phone ); ?>';
	function killJoinchat() {
		var nodes = document.querySelectorAll('.joinchat');
		for (var i = 0; i < nodes.length; i++) {
			if (nodes[i].parentNode) nodes[i].parentNode.removeChild(nodes[i]);
		}
	}
	function stampWaLinks() {
		var links = document.querySelectorAll('a[href*="wa.me/"], a[href*="whatsapp.com/send"]');
		for (var i = 0; i < links.length; i++) {
			links[i].href = links[i].href
				.replace(/wa\.me\/\+?\d+/i, 'wa.me/' + PHONE)
				.replace(/([?&]phone=)\+?\d+/i, '$1' + PHONE);
		}
	}
	killJoinchat();
	stampWaLinks();
	setInterval(function () { killJoinchat(); stampWaLinks(); }, 1500);
	if (window.MutationObserver) {
		new MutationObserver(function () { killJoinchat(); }).observe(document.documentElement, { childList: true, subtree: true });
	}
})();
</script>
		<?php
	}

	private static function render_contact_form_fallback_script() {
		// SEO-fixes HTML buffer also injects a hardened copy; keep a footer copy as backup.
		?>
<script id="cindemir-contact-form-fallback-js" data-nowprocket nowprocket data-no-minify="1" data-no-optimize="1" data-cfasync="false">
(function () {
	if (window.__cindemirContactBound) return;
	window.__cindemirContactBound = true;
	var forms = document.querySelectorAll('form.avia_ajax_form');
	if (!forms.length) return;

	function qs(form, sel) { return form.querySelector(sel); }
	function qsa(form, sel) { return Array.prototype.slice.call(form.querySelectorAll(sel)); }

	function validate(form) {
		var errors = [];
		qsa(form, 'input[type="text"], input[type="email"], textarea').forEach(function (el) {
			if (el.type === 'hidden' || (el.className || '').indexOf('hidden') !== -1) return;
			var cls = el.className || '';
			var label = form.querySelector('label[for="' + el.id + '"]');
			var name = label ? label.textContent.replace(/\*/g, '').trim() : (el.name || 'field');
			var val = (el.value || '').trim();
			var required = cls.indexOf('is_empty') !== -1 || cls.indexOf('is_email') !== -1 || el.required;
			if (required && !val) { errors.push(name); return; }
			if (cls.indexOf('is_email') !== -1 && val && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) errors.push(name);
			if (cls.indexOf('is_phone') !== -1 && val && val.replace(/\D/g, '').length < 7) errors.push(name);
		});
		return errors;
	}

	function showMessage(form, html, isError) {
		var box = (form.parentElement && form.parentElement.querySelector('.ajaxresponse')) || form.nextElementSibling;
		if (!box || !(box.className || '').match(/ajaxresponse/)) {
			box = document.createElement('div');
			box.className = 'ajaxresponse';
			form.insertAdjacentElement('afterend', box);
		}
		box.classList.remove('hidden');
		box.style.cssText = 'display:block!important;margin:1rem 0;padding:1rem 1.25rem;border-radius:4px;font-size:16px;line-height:1.5;'
			+ (isError
				? 'background:#fdecea;color:#611a15;border:1px solid #f5c2c0;'
				: 'background:#e8f5e9;color:#1b5e20;border:1px solid #a5d6a7;');
		box.innerHTML = isError
			? '<div class="av-form-error-container"><p>' + html + '</p></div>'
			: html;
		try { box.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
		if (!isError) form.style.display = 'none';
	}

	forms.forEach(function (form) {
		if (form.dataset.cindemirBound === '1') return;
		form.dataset.cindemirBound = '1';

		form.addEventListener('submit', function (ev) {
			ev.preventDefault();
			if (ev.stopImmediatePropagation) ev.stopImmediatePropagation();
			var btn = qs(form, 'input[type="submit"], button[type="submit"]');
			var sending = btn && btn.getAttribute('data-sending-label');
			var original = btn ? (btn.value || btn.textContent) : '';
			var errs = validate(form);
			if (errs.length) {
				showMessage(form, 'Please check these fields: ' + errs.join(', '), true);
				return;
			}

			var body = new URLSearchParams();
			body.append('ajax', 'true');
			qsa(form, 'input, textarea, select').forEach(function (el) {
				if (!el.name || el.type === 'submit') return;
				if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
				body.append(el.name, el.value || '');
			});

			if (btn) {
				btn.disabled = true;
				if (btn.value !== undefined) btn.value = sending || 'Sending...';
				else btn.textContent = sending || 'Sending...';
			}

			var action = form.getAttribute('action') || window.location.href;
			var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
			var timeout = setTimeout(function () { if (controller) controller.abort(); }, 25000);
			var opts = {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			};
			if (controller) opts.signal = controller.signal;

			fetch(action, opts)
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
					var isErr = /av-form-error|error-container/i.test(msg) && !/avia-form-success/i.test(msg);
					showMessage(form, msg, isErr);
					if (!isErr) {
						qsa(form, 'input[type="text"], input[type="email"], textarea').forEach(function (el) {
							if ((el.className || '').indexOf('hidden') === -1) el.value = '';
						});
					}
				})
				.catch(function () {
					showMessage(
						form,
						'Message could not be sent. Please try again or email gokhan@cindemir.av.tr directly.',
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
		}, true);
	});
})();
</script>
		<?php
	}
}

Cindemir_Contact_Fixes::boot();

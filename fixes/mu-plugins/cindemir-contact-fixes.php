<?php
/**
 * Plugin Name: Cindemir Contact & WhatsApp Fixes
 * Description: Reliable Enfold contact form submit + Joinchat/WhatsApp fallback when Debloat delays JS.
 * Version: 1.3.1
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
	const MAIL_FROM      = 'wordpress@cindemirlaw.com';

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'maybe_purge_after_upgrade' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'start_footer_buffer' ), 0 );
		add_filter( 'debloat_delay_js_exclusions', array( __CLASS__, 'debloat_exclusions' ) );
		add_filter( 'rocket_delay_js_exclusions', array( __CLASS__, 'debloat_exclusions' ) );
		add_filter( 'script_loader_tag', array( __CLASS__, 'exclude_critical_scripts' ), 20, 3 );

		add_action( 'wp_footer', array( __CLASS__, 'render_fallback_assets' ), 5 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_mail_failure' ) );
		add_action( 'phpmailer_init', array( __CLASS__, 'fix_phpmailer' ), 20 );

		add_filter( 'wp_mail_from', array( __CLASS__, 'mail_from_address' ), 20 );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'mail_from_name' ), 20 );
		add_filter( 'avf_form_sendto', array( __CLASS__, 'force_form_recipients' ), 20, 3 );
		add_filter( 'avf_form_from', array( __CLASS__, 'force_form_from' ), 20, 3 );
		add_filter( 'avf_contact_form_incoming_mail', array( __CLASS__, 'fix_incoming_mail' ), 20, 6 );

		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	public static function maybe_purge_after_upgrade() {
		$version = '1.3.1';
		if ( get_option( 'cindemir_contact_fixes_version' ) === $version ) {
			return;
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		update_option( 'cindemir_contact_fixes_version', $version, false );
	}

	public static function start_footer_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'enhance_footer_html' ) );
	}

	public static function enhance_footer_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( false === stripos( $html, "id='socket'" ) && false === stripos( $html, 'id="socket"' ) ) {
			return $html;
		}
		$html = preg_replace_callback(
			'/(<span[^>]*class=(["\'])copyright\2[^>]*>)(.*?)(<\/span>)/is',
			function ( $m ) {
				$inner = $m[3];
				if ( false === stripos( $inner, 'cindemir@cindemir.av.tr' ) ) {
					return $m[0];
				}
				$email = 'cindemir@cindemir.av.tr';
				$phone = '+90 216 550 67 75';
				$inner = preg_replace(
					'/' . preg_quote( $email, '/' ) . '/i',
					'<a href="mailto:' . esc_attr( $email ) . '" class="cindemir-footer-email">' . esc_html( $email ) . '</a>',
					$inner,
					1
				);
				$inner = preg_replace(
					'/' . preg_quote( $phone, '/' ) . '/',
					'<a href="tel:+902165506775" class="cindemir-footer-phone">' . esc_html( $phone ) . '</a>',
					$inner,
					1
				);
				return $m[1] . $inner . $m[4];
			},
			$html,
			1
		);
		if ( false !== strpos( $html, 'cindemir-socket-extras' ) ) {
			return $html;
		}
		$block = self::socket_footer_extras_markup();
		$with_div = preg_replace(
			'/(<footer[^>]*id=(["\'])socket\2[^>]*>.*?<span[^>]*class=(["\'])copyright\3[^>]*>.*?<\/span>)(\s*<\/div>)/is',
			'$1' . $block . '$4',
			$html,
			1,
			$count_div
		);
		if ( $count_div ) {
			return $with_div;
		}
		$with_span = preg_replace(
			'/(<footer[^>]*id=(["\'])socket\2[^>]*>.*?<span[^>]*class=(["\'])copyright\3[^>]*>.*?<\/span>)/is',
			'$1' . $block,
			$html,
			1,
			$count_span
		);
		return $count_span ? $with_span : $html;
	}

	private static function socket_footer_extras_markup() {
		$baro = 'https://baronet.istanbulbarosu.org.tr/avukat/belge_dogrulama?lang=EN&onayno=HBE4U7ES3DM6C52&tck=58612509084';
		ob_start();
		?>
<div class="cindemir-socket-extras" id="cindemir-socket-extras">
	<div id="cindemir-baro-verification-bar" class="cindemir-baro-verification-bar">
		<a href="<?php echo esc_url( $baro ); ?>" target="_blank" rel="noopener noreferrer">Avukat Baro Doğrulama için Tıklayınız</a>
	</div>
	<nav class="cindemir-footer-social" aria-label="Social media and contact">
		<ul class="cindemir-footer-social-list">
			<li><a href="mailto:cindemir@cindemir.av.tr" title="Email" aria-label="Email"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 2v.2l-8 5.2-8-5.2V6zm0 12H4V8.8l8 5.2 8-5.2z"/></svg></a></li>
			<li><a href="https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/" target="_blank" rel="noopener noreferrer" title="Facebook" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 10h3l-.4 4H13v9h-4v-9H7v-4h2V8.5C9 6.6 10.1 5 13 5h3v4h-2c-1.1 0-1 .6-1 1.5z"/></svg></a></li>
			<li><a href="https://www.instagram.com/cindemir_law_office/" target="_blank" rel="noopener noreferrer" title="Instagram" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 3.5A5.5 5.5 0 1 1 6.5 13 5.5 5.5 0 0 1 12 7.5zm0 2A3.5 3.5 0 1 0 15.5 13 3.5 3.5 0 0 0 12 9.5zM17.8 6.2a1.2 1.2 0 1 1-1.2 1.2 1.2 1.2 0 0 1 1.2-1.2z"/></svg></a></li>
			<li><a href="https://www.linkedin.com/company/cindemir-law-office/" target="_blank" rel="noopener noreferrer" title="LinkedIn" aria-label="LinkedIn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 8.7H3.4V21h3.1zm-1.6-6A1.9 1.9 0 1 0 6.8 4.6 1.9 1.9 0 0 0 4.9 2.7zM9 8.7V21h3.1v-6c0-1.6.3-3.2 2.3-3.2s2 1.8 2 3.1V21H20v-6.8c0-3.4-1.8-5-4.2-5a3.6 3.6 0 0 0-3.3 1.8V8.7z"/></svg></a></li>
			<li><a href="tel:+902165506775" title="Phone" aria-label="Phone"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.8 15.8 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.24 11 11 0 0 0 3.4.55 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11 11 0 0 0 .55 3.4 1 1 0 0 1-.24 1z"/></svg></a></li>
			<li><a href="https://t.me/gcindemir" target="_blank" rel="noopener noreferrer" title="Telegram" aria-label="Telegram"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.9 15.5 9.7 19c.5 0 .7-.2 1-.5l2.4-2.3 5 3.7c.9.5 1.5.2 1.7-.8l3.3-15.4h0c.3-1.2-.4-1.7-1.2-1.4L2.5 10c-1.2.5-1.2 1.2-.2 1.5l4.8 1.5L18.7 7c.6-.4 1.1-.2.7.2z"/></svg></a></li>
			<li><a href="https://x.com/cindemirlegal" target="_blank" rel="noopener noreferrer" title="X (Twitter)" aria-label="X (Twitter)"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4l7.5 9.6L4.2 20h2.5l5.8-6.7L16.7 20H20l-7.9-10.2L19.3 4h-2.5l-5.3 6.1L7.8 4z"/></svg></a></li>
			<li><a href="https://wa.me/905325680647" target="_blank" rel="noopener noreferrer" title="WhatsApp" aria-label="WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.7 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-2.8.8.8-2.7-.2-.3A8 8 0 1 1 12 20zm4.5-5.8c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.6.1-.7.8-.8 1-.3.1-.6 0a6.4 6.4 0 0 1-1.9-1.2 7.2 7.2 0 0 1-1.3-1.7c-.1-.3 0-.5.1-.6.1-.1.2-.3.3-.4.1-.1.1-.2.2-.3 0-.1 0-.2 0-.3s-.6-1.4-.8-1.9-.4-.5-.6-.5h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-1 2.2 5.2 5.2 0 0 0 1 2.6 9.7 9.7 0 0 0 3.7 3.2c.5.2 1 .4 1.3.4.3 0 .5 0 .7-.1.2-.1.6-.5.7-1 .1-.5.1-.9.1-1 0-.1-.1-.1-.3-.2z"/></svg></a></li>
			<li><a href="https://www.youtube.com/channel/UCHobIlbWxCMGTPBSZv_rM7Q" target="_blank" rel="noopener noreferrer" title="YouTube" aria-label="YouTube"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18 5 12 5 12 5s-6 0-7.8.4a2.5 2.5 0 0 0-1.8 1.8C2 9 2 12 2 12s0 3 .4 4.8a2.5 2.5 0 0 0 1.8 1.8C6 19 12 19 12 19s6 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.4-1.8.4-4.8.4-4.8s0-3-.4-4.8zM10 15.5v-7l6 3.5z"/></svg></a></li>
		</ul>
	</nav>
	<div class="cindemir-footer-badges" aria-label="Cindemir Law verification and membership badges">
		<a href="https://www.aeuropea.com/" target="_blank" rel="noopener noreferrer" title="AEuropea"><img src="https://www.aeuropea.com/wp-content/uploads/2025/09/aea-01v001-ILN-small.png" alt="AEuropea" loading="lazy" decoding="async" height="48" /></a>
		<a href="https://www.istanbulbarosu.org.tr/" target="_blank" rel="noopener noreferrer" title="İstanbul Barosu"><img src="https://www.istanbulbarosu.org.tr/_next/image?url=%2Fimages%2Fbaro_logo.png&amp;w=128&amp;q=75" alt="İstanbul Barosu" loading="lazy" decoding="async" height="48" /></a>
		<a href="https://www.barobirlik.org.tr/" target="_blank" rel="noopener noreferrer" title="Türkiye Barolar Birliği"><img src="https://d.barobirlik.org.tr/amblem/tbb_amblem_60.png" alt="Türkiye Barolar Birliği" loading="lazy" decoding="async" height="48" /></a>
	</div>
</div>
<style id="cindemir-footer-fixes-css">#socket .cindemir-socket-extras{width:100%;margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.15);text-align:center}#socket .cindemir-baro-verification-bar{margin-bottom:12px}#socket .cindemir-baro-verification-bar a{color:inherit;text-decoration:underline;font-size:14px}#socket .cindemir-footer-social{margin:10px 0 14px}#socket .cindemir-footer-social-list{display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;margin:0;padding:0;list-style:none}#socket .cindemir-footer-social-list a{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;text-decoration:none}#socket .cindemir-footer-social-list svg{width:18px;height:18px;fill:currentColor;display:block}#socket .cindemir-footer-badges{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap}#socket .cindemir-footer-badges img{height:48px;width:auto;display:block;object-fit:contain;border:0}#socket .copyright a.cindemir-footer-email,#socket .copyright a.cindemir-footer-phone{color:inherit;text-decoration:underline}</style>
		<?php
		return ob_get_clean();
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
			'/deploy-footer',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'deploy_footer_fixes' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/** Pull footer-enabled contact-fixes from GitHub and purge caches. */
	public static function deploy_footer_fixes( $request ) {
		$key = $request->get_param( 'key' );
		if ( 'footer-deploy-2026' !== $key ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}

		$sources = array(
			'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/footer-email-social-baro-917b/fixes/mu-plugins/cindemir-contact-fixes.php',
			'https://raw.githubusercontent.com/gcindemir/cindemir/master/fixes/mu-plugins/cindemir-contact-fixes.php',
		);

		$body = '';
		foreach ( $sources as $url ) {
			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 45,
					'headers' => array( 'User-Agent' => 'CindemirFooterDeploy/1.3' ),
				)
			);
			if ( is_wp_error( $response ) ) {
				continue;
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			$tmp  = (string) wp_remote_retrieve_body( $response );
			if ( 200 === $code && strlen( $tmp ) > 20000 && false !== strpos( $tmp, 'enhance_footer_html' ) ) {
				$body = $tmp;
				break;
			}
		}

		if ( '' === $body || ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return new WP_REST_Response( array( 'error' => 'download_failed' ), 500 );
		}

		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-contact-fixes.php';
		$ok   = file_put_contents( $dest, $body );
		if ( false === $ok ) {
			return new WP_REST_Response( array( 'error' => 'write_failed' ), 500 );
		}

		delete_option( 'cindemir_contact_fixes_version' );
		delete_option( 'cindemir_footer_deploy_done' );

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'version' => 'contact-footer-v1.3.0',
				'bytes'   => strlen( $body ),
			),
			200
		);
	}

	public static function joinchat_track_stub( $request ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/** One-time: create WPML Chinese translation for Contacts (EN post ID 20). */
	public static function setup_zh_contacts( $request ) {
		$key = $request->get_param( 'key' );
		if ( 'wpml-setup-zh-2026' !== $key ) {
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
				'version' => 'contact-seo-v1',
				'pages'   => $results,
			),
			200
		);
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
<?php self::render_footer_socket_extras(); ?>
		<?php
	}

	private static function render_footer_socket_extras() {
		?>
<script id="cindemir-footer-live">
(function(){
  var em='cindemir@cindemir.av.tr', ph='+90 216 550 67 75';
  var c=document.querySelector('#socket .copyright');
  if(c && c.innerHTML.indexOf('cindemir-footer-email')<0){
    c.innerHTML=c.innerHTML.replace(ph,'<a href="tel:+902165506775" class="cindemir-footer-email">'+ph+'</a>').replace(em,'<a href="mailto:'+em+'" class="cindemir-footer-email">'+em+'</a>');
  }
  if(document.getElementById('cindemir-socket-extras')) return;
  var box=document.querySelector('#socket .container');
  if(!box) return;
  box.insertAdjacentHTML('beforeend','<div class="cindemir-socket-extras" id="cindemir-socket-extras"><div id="cindemir-baro-verification-bar"><a href="https://baronet.istanbulbarosu.org.tr/avukat/belge_dogrulama?lang=EN&onayno=HBE4U7ES3DM6C52&tck=58612509084" target="_blank" rel="noopener">Avukat Baro Doğrulama için Tıklayınız</a></div><nav class="cindemir-footer-social" aria-label="Social"><ul class="cindemir-footer-social-list"><li><a href="mailto:cindemir@cindemir.av.tr" title="Email">✉</a></li><li><a href="https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/" target="_blank" rel="noopener" title="Facebook">f</a></li><li><a href="https://www.instagram.com/cindemir_law_office/" target="_blank" rel="noopener" title="Instagram">◎</a></li><li><a href="https://www.linkedin.com/company/cindemir-law-office/" target="_blank" rel="noopener" title="LinkedIn">in</a></li><li><a href="tel:+902165506775" title="Phone">☎</a></li><li><a href="https://t.me/gcindemir" target="_blank" rel="noopener" title="Telegram">✈</a></li><li><a href="https://x.com/cindemirlegal" target="_blank" rel="noopener" title="X">𝕏</a></li><li><a href="https://wa.me/905325680647" target="_blank" rel="noopener" title="WhatsApp">W</a></li><li><a href="https://www.youtube.com/channel/UCHobIlbWxCMGTPBSZv_rM7Q" target="_blank" rel="noopener" title="YouTube">▶</a></li></ul></nav><div class="cindemir-footer-badges"><a href="https://www.aeuropea.com/" target="_blank" rel="noopener"><img src="https://www.aeuropea.com/wp-content/uploads/2025/09/aea-01v001-ILN-small.png" alt="AEuropea" height="48" loading="lazy"></a><a href="https://www.istanbulbarosu.org.tr/" target="_blank" rel="noopener"><img src="https://www.istanbulbarosu.org.tr/_next/image?url=%2Fimages%2Fbaro_logo.png&amp;w=128&amp;q=75" alt="İstanbul Barosu" height="48" loading="lazy"></a><a href="https://www.barobirlik.org.tr/" target="_blank" rel="noopener"><img src="https://d.barobirlik.org.tr/amblem/tbb_amblem_60.png" alt="TBB" height="48" loading="lazy"></a></div></div><style>#socket .cindemir-socket-extras{width:100%;margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.15);text-align:center}#socket .cindemir-baro-verification-bar a{color:inherit;text-decoration:underline;font-size:14px}#socket .cindemir-footer-social-list{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;list-style:none;margin:10px 0;padding:0}#socket .cindemir-footer-social-list a{display:inline-flex;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;align-items:center;justify-content:center;text-decoration:none;font-size:14px}#socket .cindemir-footer-badges{display:flex;justify-content:center;gap:14px;flex-wrap:wrap}#socket .cindemir-footer-badges img{height:48px;width:auto}#socket .copyright a{color:inherit;text-decoration:underline}</style>');
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

<?php
/**
 * Plugin Name: Cindemir RU Press Page
 * Description: Builds the Russian "О нас в прессе" page from the English media list and points RU menus to the local page instead of cindemir.av.tr.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_RU_PRESS_PAGE_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_RU_PRESS_PAGE_LOADED', true );

final class Cindemir_RU_Press_Page {
	const VERSION     = '1.0.0';
	const OPTION_KEY  = 'cindemir_ru_press_page_version';
	const PAGE_ID     = 2658;
	const MARKER      = 'cindemir-ru-press-page';

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'ensure_page_and_menus' ), 30 );
		add_action( 'wp_head', array( __CLASS__, 'print_css' ), 40 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'rocket_buffer', array( __CLASS__, 'buffer_mark' ), 92 );
	}

	/**
	 * @return array<int, array{outlet:string,title:string,url:string,type:string}>
	 */
	private static function items() {
		return array(
			array(
				'outlet' => 'Demirören Haber Ajansı',
				'title'  => 'Заявление нашего клиента для прессы — видео',
				'url'    => 'https://youtu.be/Hqx8wI6Ku8Q?si=1SUx0f7IX0TlgeSp',
				'type'   => 'video',
			),
			array(
				'outlet' => 'CNN Türk',
				'title'  => 'Интервью у здания суда — видео',
				'url'    => 'https://www.youtube.com/watch?v=8EV9DobzacE',
				'type'   => 'video',
			),
			array(
				'outlet' => 'CNN Türk',
				'title'  => 'Прямое телефонное включение — видео',
				'url'    => 'https://www.youtube.com/watch?v=YvXFuwtqxvE',
				'type'   => 'video',
			),
			array(
				'outlet' => 'Anadolu Ajansı',
				'title'  => 'Решение по делу ирландского боксёра',
				'url'    => 'https://www.aa.com.tr/tr/turkiye/irlandali-boksor-davasinda-karar/859847',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Cumhuriyet',
				'title'  => 'Иск о компенсации ирландского туриста',
				'url'    => 'https://www.cumhuriyet.com.tr/haber/irlandali-boksorden-aksaray-esnafina-bir-yumruk-daha-874886',
				'type'   => 'press',
			),
			array(
				'outlet' => 'HaberTürk',
				'title'  => 'Комментарий по гражданскому делу ирландского туриста',
				'url'    => 'https://www.haberturk.com/irlandali-boksor-turistten-tazminat-davasi-1731609',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Hürriyet',
				'title'  => 'Дело ирландского туриста и предшествовавшее нападение на полицейских',
				'url'    => 'https://www.hurriyet.com.tr/gundem/irlandali-boksorden-once-polis-yine-dolap-yine-sise-40813976',
				'type'   => 'press',
			),
			array(
				'outlet' => 'İhlas Haber Ajansı',
				'title'  => 'Интервью — видео',
				'url'    => 'https://www.youtube.com/watch?v=Mk6_mIWHrCI&t=84s',
				'type'   => 'video',
			),
			array(
				'outlet' => 'Cumhuriyet',
				'title'  => 'Происшествие в отеле Hilton: ребёнок попал под ток',
				'url'    => 'https://www.cumhuriyet.com.tr/turkiye/unlu-otelde-korkunc-olay-elektrik-akimina-kapilan-cocuk-olumden-dondu-1912333',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Cumhuriyet',
				'title'  => 'Решение суда по делу об ударе током в отеле Hilton',
				'url'    => 'https://www.cumhuriyet.com.tr/turkiye/fatihte-luks-otelde-2-yasindaki-turist-cocuga-elektrik-carpmasina-2270434',
				'type'   => 'press',
			),
			array(
				'outlet' => 'DailyMotion',
				'title'  => 'Дело об аварии в отеле Büyük Londra (Taksim) — видео',
				'url'    => 'https://www.dailymotion.com/video/x6z58am',
				'type'   => 'video',
			),
			array(
				'outlet' => 'Sabah',
				'title'  => 'Владелец отеля выплатит 1,5 млн компенсации после гибели в вентиляционной шахте',
				'url'    => 'https://www.sabah.com.tr/yasam/otelde-havalandirma-bosluguna-duserek-olmustu-otel-sahibi-15-milyon-tazminat-odeyecek-6544044',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Hürriyet',
				'title'  => 'Вопрос Кассационного суда о размере компенсации',
				'url'    => 'https://www.hurriyet.com.tr/gundem/yargitay-mahkemeye-sordu-niye-12-100-tl-40946566',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Sözcü',
				'title'  => 'Оценка адвоката Гёкхана Джиндемира по делу о криптомошенничестве',
				'url'    => 'https://www.sozcu.com.tr/hukukcular-thodex-vurgununu-degerlendirdi-wp6389692',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Milli Gazete',
				'title'  => 'Комментарии юристов по делу Thodex',
				'url'    => 'https://www.milligazete.com.tr/haber/7007784/hukukcular-thodex-vurgununu-degerlendirdi-olay-bitmistir',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Milliyet',
				'title'  => 'Пресс-заявление с семьёй пострадавшего ребёнка — видео',
				'url'    => 'https://www.youtube.com/watch?v=TnWqcJjbsQU&t=3s',
				'type'   => 'video',
			),
			array(
				'outlet' => 'Sabah',
				'title'  => 'Американский ребёнок выпил кислоту вместо воды — заявление для прессы',
				'url'    => 'https://www.sabah.com.tr/yasam/abdli-cocuk-olumden-dondu-su-yerine-asit-icti-6450407',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Hürriyet',
				'title'  => 'Кадры аварии со смертельным исходом в Каппадокии — видео',
				'url'    => 'https://youtu.be/ftNRAnRU7ZQ',
				'type'   => 'video',
			),
			array(
				'outlet' => 'T24',
				'title'  => 'Решение суда по делу гибели китайского гида Hiu Fung Yeung (2018)',
				'url'    => 'https://t24.com.tr/haber/cinli-rehberin-oldugu-inanilmaz-kazada-karar-cikti,574376',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Hukuki Haber',
				'title'  => 'Компенсация 688 тыс. лир по делу гибели китайского гида (2018)',
				'url'    => 'https://www.hukukihaber.net/cinli-rehberin-oldugu-kazada-688-bin-lira-tazminat',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Yeni Alanya',
				'title'  => 'Дело Çimtur о таймшерах в Аланье может стать прецедентом',
				'url'    => 'https://www.yenialanya.com/haber/4041195/alanyadaki-cimtur-davasi-ornek-olacak',
				'type'   => 'press',
			),
			array(
				'outlet' => 'Show TV / Hürriyet',
				'title'  => 'Решение о компенсации по личному делу адвоката Джиндемира — видео',
				'url'    => 'https://youtu.be/wN98Fmb2FCg',
				'type'   => 'video',
			),
			array(
				'outlet' => 'Hürriyet',
				'title'  => 'Рекордная компенсация за утерянный багаж',
				'url'    => 'https://www.hurriyet.com.tr/gundem/kaybolan-bavula-rekor-tazminat-40803449',
				'type'   => 'press',
			),
			array(
				'outlet' => 'HaberTürk',
				'title'  => 'Комментарии по решению о компенсации на первой полосе',
				'url'    => 'https://www.haberturk.com/irlandali-boksor-turistten-tazminat-davasi-1731609',
				'type'   => 'press',
			),
			array(
				'outlet' => 'HaberTürk',
				'title'  => 'Жалоба в прокуратуру после смерти шведской гражданки после липосакции',
				'url'    => 'https://www.haberturk.com/yag-aldirirken-oldu-ailesi-sikyetci-oldu-1689201',
				'type'   => 'press',
			),
		);
	}

	/**
	 * @return string
	 */
	private static function page_html() {
		$items_html = '';
		foreach ( self::items() as $i => $item ) {
			$delay   = min( 0.45, 0.04 * $i );
			$label   = ( 'video' === $item['type'] ) ? 'Видео' : 'Пресса';
			$outlet  = esc_html( $item['outlet'] );
			$title   = esc_html( $item['title'] );
			$url     = esc_url( $item['url'] );
			$items_html .= '<a class="cin-ru-press__item" href="' . $url . '" target="_blank" rel="noopener noreferrer" style="--cin-delay:' . esc_attr( (string) $delay ) . 's">'
				. '<span class="cin-ru-press__meta"><span class="cin-ru-press__outlet">' . $outlet . '</span>'
				. '<span class="cin-ru-press__kind">' . esc_html( $label ) . '</span></span>'
				. '<span class="cin-ru-press__title">' . $title . '</span>'
				. '<span class="cin-ru-press__go" aria-hidden="true">↗</span>'
				. '</a>';
		}

		return '<!-- ' . self::MARKER . ' ' . self::VERSION . ' -->'
			. '<div class="cin-ru-press" data-cin-ru-press="' . esc_attr( self::VERSION ) . '">'
			. '<header class="cin-ru-press__hero">'
			. '<p class="cin-ru-press__eyebrow">Cindemir Law Office</p>'
			. '<h1 class="cin-ru-press__h1">О нас в прессе</h1>'
			. '<p class="cin-ru-press__lead">Выступления наших адвокатов на телевидении, в печати и в онлайн-СМИ — комментарии по уголовному, коммерческому, финансовому и трудовому праву.</p>'
			. '</header>'
			. '<section class="cin-ru-press__intro" aria-label="О разделе">'
			. '<p>Адвокаты юридической фирмы Cindemir регулярно делятся экспертизой в телепередачах, газетных материалах и цифровых изданиях, помогая общественности ориентироваться в правовых событиях Турции. Ниже — отобранные публикации и видео с участием нашей команды.</p>'
			. '</section>'
			. '<section class="cin-ru-press__list" aria-label="Публикации в СМИ">'
			. $items_html
			. '</section>'
			. '<footer class="cin-ru-press__foot">'
			. '<p>Нужна консультация по похожему делу? <a href="' . esc_url( home_url( '/kontak/?lang=ru' ) ) . '">Свяжитесь с нами</a>.</p>'
			. '</footer>'
			. '</div>';
	}

	public static function ensure_page_and_menus() {
		if ( is_admin() && ! wp_doing_cron() ) {
			// Still allow WP-CLI / front bootstrap.
		}
		if ( get_option( self::OPTION_KEY ) === self::VERSION ) {
			return;
		}

		$page = get_post( self::PAGE_ID );
		if ( ! $page || 'page' !== $page->post_type ) {
			return;
		}

		wp_update_post(
			array(
				'ID'           => self::PAGE_ID,
				'post_title'   => 'О нас в прессе',
				'post_name'    => 'press',
				'post_status'  => 'publish',
				'post_content' => self::page_html(),
			)
		);

		// Prefer classic content over empty Avia builder for this page.
		update_post_meta( self::PAGE_ID, '_aviaLayoutBuilder_active', '' );
		update_post_meta( self::PAGE_ID, '_aviaLayoutBuilderCleanData', self::page_html() );

		self::fix_menus();

		update_option( self::OPTION_KEY, self::VERSION, false );

		if ( function_exists( 'rocket_clean_files' ) ) {
			rocket_clean_files( array( get_permalink( self::PAGE_ID ), home_url( '/press/?lang=ru' ) ) );
		} elseif ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
	}

	private static function fix_menus() {
		$target = home_url( '/press/?lang=ru' );
		$bad    = array(
			'https://cindemir.av.tr/en/we-are-in-news/',
			'http://cindemir.av.tr/en/we-are-in-news/',
			'https://cindemir.av.tr/basinda-biz/',
			'http://cindemir.av.tr/basinda-biz/',
			home_url( '/pod/?lang=ru' ),
			'https://cindemirlaw.com/pod/?lang=ru',
		);

		$menus = wp_get_nav_menus();
		if ( ! $menus ) {
			return;
		}

		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! $items ) {
				continue;
			}
			foreach ( $items as $item ) {
				$url   = (string) $item->url;
				$title = (string) $item->title;
				$is_press_label = ( false !== stripos( $title, 'пресс' ) )
					|| ( false !== stripos( $title, 'press' ) )
					|| ( false !== stripos( $title, '媒体' ) )
					|| ( false !== stripos( $title, 'basinda' ) );

				$points_bad = false;
				foreach ( $bad as $b ) {
					if ( untrailingslashit( $url ) === untrailingslashit( $b ) || false !== stripos( $url, 'we-are-in-news' ) || false !== stripos( $url, 'basinda-biz' ) ) {
						$points_bad = true;
						break;
					}
				}

				if ( ! $points_bad && ! ( $is_press_label && false !== stripos( $url, 'cindemir.av.tr' ) ) ) {
					continue;
				}

				// RU / Chinese press labels → local RU or ZH press; EN → local /press/.
				$new = $target;
				if ( false !== stripos( $title, '媒体' ) ) {
					$new = home_url( '/press/?lang=zh-hans' );
				} elseif ( false === stripos( $title, 'пресс' ) && false !== stripos( $title, 'Press' ) ) {
					$new = home_url( '/press/' );
				}

				update_post_meta( $item->ID, '_menu_item_url', $new );
				wp_update_post(
					array(
						'ID'         => $item->ID,
						'post_title' => $title,
					)
				);
			}
		}

		// Explicit known RU custom item.
		if ( get_post( 2678 ) ) {
			update_post_meta( 2678, '_menu_item_url', $target );
		}
		if ( get_post( 2458 ) ) {
			update_post_meta( 2458, '_menu_item_url', $target );
		}
		if ( get_post( 35 ) ) {
			update_post_meta( 35, '_menu_item_url', home_url( '/press/' ) );
		}
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( self::is_ru_press() ) {
			$classes[] = 'cin-ru-press-page';
		}
		return $classes;
	}

	/**
	 * @return bool
	 */
	private static function is_ru_press() {
		if ( ! is_singular( 'page' ) ) {
			return false;
		}
		return (int) get_queried_object_id() === (int) self::PAGE_ID;
	}

	public static function print_css() {
		if ( ! self::is_ru_press() ) {
			return;
		}
		echo '<style id="cindemir-ru-press-page" data-v="' . esc_attr( self::VERSION ) . '">' . self::css() . '</style>';
	}

	/**
	 * @return string
	 */
	private static function css() {
		return <<<'CSS'
body.cin-ru-press-page #main .container,
body.cin-ru-press-page .content,
body.cin-ru-press-page .entry-content-wrapper{max-width:920px!important}
body.cin-ru-press-page .template-page .entry-content-wrapper{padding-top:0!important}
body.cin-ru-press-page #main{background:
  radial-gradient(1200px 480px at 10% -10%, rgba(40,96,96,.10), transparent 55%),
  linear-gradient(180deg,#f7f5f0 0%,#fbfaf7 42%,#fff 100%)!important}
.cin-ru-press{font-family:var(--cin-sans,"Avenir Next","Segoe UI",sans-serif);color:var(--cin-text,#1a2a32);padding:0 0 4rem}
.cin-ru-press__hero{
  margin:0 0 2rem;padding:clamp(2.2rem,5vw,3.6rem) 0 clamp(1.4rem,3vw,2rem);
  border-bottom:1px solid rgba(11,31,40,.12);
  animation:cinRuPressIn .7s ease both}
.cin-ru-press__eyebrow{
  margin:0 0 .55rem;font-size:.78rem;letter-spacing:.16em;text-transform:uppercase;
  color:var(--cin-teal,#286060);font-weight:700}
.cin-ru-press__h1{
  margin:0 0 .85rem;font-family:var(--cin-display,Georgia,serif);
  font-size:clamp(2rem,5vw,3.1rem);line-height:1.12;font-weight:600;color:var(--cin-ink,#0b1f28)}
.cin-ru-press__lead{
  margin:0;max-width:38rem;font-size:clamp(1.02rem,2.1vw,1.18rem);line-height:1.55;
  color:var(--cin-muted,#5a6b73)}
.cin-ru-press__intro{margin:0 0 1.75rem;animation:cinRuPressIn .75s ease .08s both}
.cin-ru-press__intro p{margin:0;max-width:42rem;font-size:1.02rem;line-height:1.7;color:var(--cin-text,#1a2a32)}
.cin-ru-press__list{display:flex;flex-direction:column;gap:0}
.cin-ru-press__item{
  display:grid;grid-template-columns:1fr auto;grid-template-rows:auto auto;gap:.35rem 1rem;
  align-items:center;padding:1.15rem 0;border-top:1px solid rgba(11,31,40,.10);
  text-decoration:none!important;color:inherit!important;
  transition:background .25s ease, padding .25s ease, transform .25s ease;
  animation:cinRuPressIn .65s ease var(--cin-delay,0s) both}
.cin-ru-press__item:last-child{border-bottom:1px solid rgba(11,31,40,.10)}
.cin-ru-press__item:hover{
  background:linear-gradient(90deg,rgba(40,96,96,.06),transparent 70%);
  padding-left:.65rem;transform:translateX(2px)}
.cin-ru-press__meta{grid-column:1;display:flex;flex-wrap:wrap;gap:.5rem .75rem;align-items:center}
.cin-ru-press__outlet{
  font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
  color:var(--cin-teal-deep,#1f4f4f)}
.cin-ru-press__kind{
  font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;
  color:var(--cin-muted,#5a6b73);border:1px solid rgba(11,31,40,.14);padding:.15rem .45rem}
.cin-ru-press__title{
  grid-column:1;font-family:var(--cin-display,Georgia,serif);
  font-size:clamp(1.05rem,2.2vw,1.28rem);line-height:1.35;font-weight:600;color:var(--cin-ink,#0b1f28)}
.cin-ru-press__go{
  grid-column:2;grid-row:1 / span 2;font-size:1.25rem;color:var(--cin-teal,#286060);
  opacity:.55;transition:opacity .2s ease, transform .2s ease}
.cin-ru-press__item:hover .cin-ru-press__go{opacity:1;transform:translate(2px,-2px)}
.cin-ru-press__foot{margin-top:2.25rem;padding-top:1.25rem;border-top:1px solid rgba(11,31,40,.12)}
.cin-ru-press__foot p{margin:0;color:var(--cin-muted,#5a6b73);font-size:.98rem}
.cin-ru-press__foot a{color:var(--cin-teal-deep,#1f4f4f)!important;font-weight:700;text-decoration:underline}
@keyframes cinRuPressIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){
  .cin-ru-press__hero,.cin-ru-press__intro,.cin-ru-press__item{animation:none!important}
  .cin-ru-press__item:hover{transform:none}
}
@media only screen and (max-width:640px){
  .cin-ru-press__item{grid-template-columns:1fr;gap:.4rem}
  .cin-ru-press__go{display:none}
}
CSS;
	}

	/**
	 * @param string $html Buffer.
	 * @return string
	 */
	public static function buffer_mark( $html ) {
		if ( ! is_string( $html ) || $html === '' || ! self::is_ru_press() ) {
			return $html;
		}
		$mark = '<!-- ' . self::MARKER . ' ' . self::VERSION . ' -->';
		if ( false === stripos( $html, $mark ) && false !== stripos( $html, '</head>' ) ) {
			$html = preg_replace( '/<\/head>/i', $mark . "\n</head>", $html, 1 );
		}
		return $html;
	}
}

Cindemir_RU_Press_Page::boot();

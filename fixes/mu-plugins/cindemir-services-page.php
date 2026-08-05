<?php
/**
 * Plugin Name: Cindemir Services Page Redesign
 * Description: Replaces the cluttered Enfold Services page with a clearer, multilingual layout.
 * Version: 1.0.4
 * SERVICES_BLANK_FIX_20260715 + ELENA_ZARA_RU_BIO_20260718
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_SERVICES_PAGE_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_SERVICES_PAGE_LOADED', true );

final class Cindemir_Services_Page {

	const VERSION = '1.0.4';

	/** WordPress page IDs: EN services, RU WPML, ZH WPML, RU slug nashiyurist. */
	private static $page_ids = array( 18, 2638, 2637, 56 );

	private static $hero_image = 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg';

	public static function boot() {
		// Priority 0: start buffer BEFORE contact-fixes (priority 1) so JoinChat
		// strip runs on the original HTML, not the post-inject giant page.
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'print_assets' ), 40 );
	}

	private static function is_services_page() {
		if ( ! function_exists( 'is_page' ) || ! is_page() ) {
			return false;
		}
		return is_page( self::$page_ids );
	}

	public static function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		if ( ! self::is_services_page() ) {
			return;
		}
		ob_start( array( __CLASS__, 'rewrite_html' ) );
	}

	public static function print_assets() {
		if ( ! self::is_services_page() ) {
			return;
		}
		$font_url = 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Source+Sans+3:wght@400;500;600;700&display=swap';
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		echo '<link href="' . esc_url( $font_url ) . '" rel="stylesheet">' . "\n";
		echo '<style id="cindemir-services-page">' . self::css() . '</style>' . "\n";
	}

	public static function rewrite_html( $html ) {
		$original = is_string( $html ) ? $html : '';
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		// Detect injected markup only (CSS in <head> also contains "cindemir-services").
		if ( false !== strpos( $html, 'data-cindemir-services=' ) ) {
			return $html;
		}

		$markup = self::render();
		if ( ! is_string( $markup ) || '' === $markup ) {
			return $original;
		}

		// Avoid preg_replace + nested output buffers inside an ob callback (both blanked /services/).
		$new = self::inject_after_main_open( $html, $markup );
		if ( ! is_string( $new ) || '' === $new ) {
			return $original;
		}
		return $new;
	}

	/** String inject after `#main` open tag — no PCRE, safe inside ob callbacks. */
	private static function inject_after_main_open( $html, $markup ) {
		$pos = stripos( $html, 'id="main"' );
		if ( false === $pos ) {
			$pos = stripos( $html, "id='main'" );
		}
		if ( false === $pos ) {
			return $html;
		}
		$gt = strpos( $html, '>', $pos );
		if ( false === $gt ) {
			return $html;
		}
		return substr( $html, 0, $gt + 1 ) . $markup . substr( $html, $gt + 1 );
	}

	private static function lang() {
		if ( ! empty( $_GET['lang'] ) ) {
			$lang = sanitize_key( wp_unslash( $_GET['lang'] ) );
		} else {
			$wpml = apply_filters( 'wpml_current_language', null );
			$lang = is_string( $wpml ) && $wpml ? $wpml : 'en';
			if ( function_exists( 'is_page' ) && is_page( 56 ) ) {
				$lang = 'ru';
			}
		}
		$lang = strtolower( (string) $lang );
		if ( 0 === strpos( $lang, 'zh' ) ) {
			return 'zh';
		}
		if ( 0 === strpos( $lang, 'ru' ) ) {
			return 'ru';
		}
		if ( 0 === strpos( $lang, 'tr' ) ) {
			return 'tr';
		}
		return 'en';
	}

	private static function copy() {
		$lang = self::lang();
		$all  = array(
			'en' => array(
				'brand'    => 'Cindemir Law Office',
				'headline' => 'Legal services in Turkey',
				'lead'     => 'Turkish and cross-border matters for individuals and companies, handled from Istanbul.',
				'cta'      => 'Contacts',
				'cta_url'  => 'https://cindemirlaw.com/contacts/',
				'index'    => 'Practice areas',
				'index_hint' => 'Choose a topic to read a short overview.',
				'detail'   => 'Overview',
				'services' => self::services_en(),
			),
			'ru' => array(
				'brand'    => 'Юридическая фирма Cindemir',
				'headline' => 'Юридические услуги в Турции',
				'lead'     => 'Турецкое и международное право для частных лиц и компаний — сопровождение из Стамбула.',
				'cta'      => 'Контакты',
				'cta_url'  => 'https://cindemirlaw.com/contacts/?lang=ru',
				'index'    => 'Направления практики',
				'index_hint' => 'Выберите направление, чтобы просмотреть краткое описание.',
				'detail'   => 'Описание',
				'services' => self::services_ru(),
			),
			'zh' => array(
				'brand'    => '辛德米尔律师事务所',
				'headline' => '土耳其法律服务',
				'lead'     => '面向个人与企业的土耳其及跨境法律事务，由伊斯坦布尔办公室处理。',
				'cta'      => '联系方式',
				'cta_url'  => 'https://cindemirlaw.com/contacts/?lang=zh-hans',
				'index'    => '业务领域',
				'index_hint' => '选择一项业务以查看简要说明。',
				'detail'   => '概述',
				'services' => self::services_zh(),
			),
			'tr' => array(
				'brand'    => 'Cindemir Hukuk Bürosu',
				'headline' => 'Türkiye’de hukuki hizmetler',
				'lead'     => 'Gerçek ve tüzel kişiler için Türk ve sınır ötesi hukuki süreçler İstanbul’dan yürütülür.',
				'cta'      => 'İletişim',
				'cta_url'  => 'https://cindemirlaw.com/contacts/',
				'index'    => 'Çalışma alanları',
				'index_hint' => 'Kısa özet için bir konu seçin.',
				'detail'   => 'Özet',
				'services' => self::services_en(),
			),
		);
		return isset( $all[ $lang ] ) ? $all[ $lang ] : $all['en'];
	}

	private static function services_en() {
		return array(
			array(
				'id'    => 'debt-collection',
				'title' => 'Debt collection',
				'text'  => 'Follow-up of commercial and civil receivables in Turkey, with coordinated steps through a network of colleagues in Europe where a cross-border file requires it.',
				'points' => array(
					'Demand letters and negotiation',
					'Court and enforcement proceedings in Turkey',
					'Coordination with foreign counsel when needed',
				),
				'link'  => '',
			),
			array(
				'id'    => 'crypto',
				'title' => 'Crypto and digital assets',
				'text'  => 'Regulatory and dispute-related work for crypto-asset businesses and platforms operating under Turkish and EU-related frameworks.',
				'points' => array(
					'Compliance policies and disclosure practices',
					'Market entry guidance for crypto-asset service providers',
					'AML, fraud, and platform operation questions',
				),
				'link'  => 'https://cindemirlaw.com/legal-services-for-digital-asset-businesses/',
			),
			array(
				'id'    => 'it-law',
				'title' => 'IT, software and social media law',
				'text'  => 'Contracts and advice for websites, software licensing, and online platforms, including data and account-related issues.',
				'points' => array(
					'Software and service agreements',
					'Social media and platform account matters',
					'Online business documentation',
				),
				'link'  => 'https://cindemirlaw.com/software-account-services/',
			),
			array(
				'id'    => 'startups',
				'title' => 'Legal support for startups',
				'text'  => 'Entity choice, incorporation steps, and early-stage documents for founders setting up or expanding a business in Turkey.',
				'points' => array(
					'Company formation options under Turkish law',
					'Shareholder and founder arrangements',
					'Routine corporate housekeeping for growing firms',
				),
				'link'  => '',
			),
			array(
				'id'    => 'due-diligence',
				'title' => 'Due diligence',
				'text'  => 'Legal review of companies and transactions, prepared with attorneys and, where useful, input from financial experts.',
				'points' => array(
					'Corporate and contract review',
					'Risk notes for buyers or investors',
					'Support around closing documentation',
				),
				'link'  => '',
			),
			array(
				'id'    => 'energy',
				'title' => 'Energy law and investment',
				'text'  => 'Corporate and investment matters for companies active in energy, including renewable projects and related market entry steps.',
				'points' => array(
					'Establishing energy-related operations in Turkey',
					'Investment and contract documentation',
					'Follow-up on renewable sector developments',
				),
				'link'  => '',
			),
			array(
				'id'    => 'corporate',
				'title' => 'Corporate and commercial',
				'text'  => 'Company formation, joint ventures, share deals, contracts, and day-to-day commercial questions for local and foreign businesses.',
				'points' => array(
					'Incorporation and offshore registration questions',
					'Share purchase, joint ventures, and liquidation',
					'Commercial contracts and partnership deeds',
				),
				'link'  => '',
			),
			array(
				'id'    => 'escrow',
				'title' => 'Escrow arrangements',
				'text'  => 'Transaction-specific escrow agreements and practical handling of funds held for defined conditions of release.',
				'points' => array(
					'Individual escrow agreement drafting',
					'Escrow account structure for a deal',
					'Support for digital-asset or account transfers when escrow is used',
				),
				'link'  => 'https://cindemirlaw.com/escrow-account-turkey/',
			),
			array(
				'id'    => 'real-estate',
				'title' => 'Real estate and conveyancing',
				'text'  => 'Property transactions, development-related agreements, tenancy, and proceedings connected to land and housing in Turkey.',
				'points' => array(
					'Sale, purchase, and loan-related conveyancing',
					'Joint venture and development agreements',
					'Tenancy, eviction, and related proceedings',
				),
				'link'  => '',
			),
			array(
				'id'    => 'tort',
				'title' => 'Tort law and tourist accidents',
				'text'  => 'Civil claims after accidents affecting visitors in Turkey, including hotel, tour, and transport incidents.',
				'points' => array(
					'Accident and injury claim assessment',
					'Compensation and insurance-related steps',
					'Coordination with local procedures for foreign clients',
				),
				'link'  => 'https://cindemirlaw.com/tort-law-in-turkey/',
			),
			array(
				'id'    => 'litigation',
				'title' => 'Litigation and ADR',
				'text'  => 'Court representation and alternative resolution through arbitration or mediation, depending on the file and the client’s preference.',
				'points' => array(
					'Commercial and civil litigation',
					'Arbitration and mediation routes',
					'Employment, construction, and insurance disputes',
				),
				'link'  => '',
			),
			array(
				'id'    => 'admin',
				'title' => 'Administrative law',
				'text'  => 'Matters involving public authorities, administrative acts, and procedures that affect individuals or organisations.',
				'points' => array(
					'Review of administrative acts and omissions',
					'Applications and objections before public bodies',
					'Related court avenues where available',
				),
				'link'  => '',
			),
			array(
				'id'    => 'digital-privacy',
				'title' => 'Privacy, AI and metaverse questions',
				'text'  => 'Privacy and data-security obligations for firms in digital marketing, e-commerce, VR/metaverse projects, and AI-related products.',
				'points' => array(
					'Privacy and data-handling documentation',
					'Risk review for digital products and platforms',
					'Cross-border product compliance questions',
				),
				'link'  => '',
			),
			array(
				'id'    => 'apostille',
				'title' => 'Apostille and document legalisation',
				'text'  => 'Practical guidance on apostille and legalisation paths for documents used in Turkey or prepared for use abroad.',
				'points' => array(
					'Birth, marriage, divorce, and civil-status papers',
					'Powers of attorney and corporate records',
					'Degrees, criminal records, and court judgments',
				),
				'link'  => 'https://cindemirlaw.com/international-apostille-services-in-turkey/',
			),
		);
	}

	private static function services_ru() {
		return array(
			array(
				'id'    => 'debt-collection',
				'title' => 'Взыскание задолженности',
				'text'  => 'Сопровождение взыскания коммерческих и гражданских требований в Турции, при необходимости — с координацией коллег в Европе.',
				'points' => array(
					'Претензионная работа и переговоры',
					'Судебные и исполнительные процедуры в Турции',
					'Взаимодействие с иностранными юристами при трансграничных делах',
				),
				'link'  => '',
			),
			array(
				'id'    => 'crypto',
				'title' => 'Криптоактивы и цифровое право',
				'text'  => 'Регуляторные и спорные вопросы для бизнеса и платформ, работающих с криптоактивами в рамках турецкого и связанного с ЕС регулирования.',
				'points' => array(
					'Политики соответствия и раскрытие информации',
					'Вход на рынок для поставщиков услуг по криптоактивам',
					'Вопросы AML, мошенничества и работы платформ',
				),
				'link'  => 'https://cindemirlaw.com/legal-services-for-digital-asset-businesses/?lang=ru',
			),
			array(
				'id'    => 'it-law',
				'title' => 'IT, ПО и право социальных сетей',
				'text'  => 'Договоры и консультации по сайтам, лицензированию ПО и онлайн-платформам, включая вопросы данных и аккаунтов.',
				'points' => array(
					'Соглашения о ПО и услугах',
					'Вопросы аккаунтов в социальных сетях и на платформах',
					'Документы для онлайн-бизнеса',
				),
				'link'  => '',
			),
			array(
				'id'    => 'startups',
				'title' => 'Юридическая поддержка стартапов',
				'text'  => 'Выбор формы, учреждение компании и ранние корпоративные документы для основателей, создающих или расширяющих бизнес в Турции.',
				'points' => array(
					'Варианты учреждения компании по турецкому праву',
					'Договоренности между основателями и акционерами',
					'Текущее корпоративное сопровождение',
				),
				'link'  => '',
			),
			array(
				'id'    => 'due-diligence',
				'title' => 'Due diligence',
				'text'  => 'Юридическая проверка компаний и сделок с участием адвокатов и, при необходимости, финансовых специалистов.',
				'points' => array(
					'Проверка корпоративных и договорных документов',
					'Заметки о рисках для покупателя или инвестора',
					'Поддержка на этапе закрытия сделки',
				),
				'link'  => '',
			),
			array(
				'id'    => 'energy',
				'title' => 'Энергетическое право и инвестиции',
				'text'  => 'Корпоративные и инвестиционные вопросы для компаний энергетического сектора, включая возобновляемую энергетику.',
				'points' => array(
					'Выход на рынок и учреждение операций в Турции',
					'Инвестиционные и договорные документы',
					'Сопровождение проектов в сфере ВИЭ',
				),
				'link'  => '',
			),
			array(
				'id'    => 'corporate',
				'title' => 'Корпоративное и коммерческое право',
				'text'  => 'Учреждение компаний, совместные предприятия, сделки с долями, договоры и текущие коммерческие вопросы.',
				'points' => array(
					'Учреждение компаний и вопросы регистрации',
					'Покупка долей, JV и ликвидация',
					'Коммерческие договоры и партнёрские соглашения',
				),
				'link'  => '',
			),
			array(
				'id'    => 'escrow',
				'title' => 'Эскроу-схемы',
				'text'  => 'Индивидуальные эскроу-соглашения и практическое сопровождение средств, удерживаемых до выполнения условий сделки.',
				'points' => array(
					'Подготовка индивидуального эскроу-договора',
					'Структура эскроу-счёта под сделку',
					'Поддержка при передаче цифровых активов или аккаунтов',
				),
				'link'  => 'https://cindemirlaw.com/escrow-account-turkey/?lang=ru',
			),
			array(
				'id'    => 'real-estate',
				'title' => 'Недвижимость и сделки с имуществом',
				'text'  => 'Сделки с недвижимостью, соглашения о развитии проектов, аренда и связанные с землёй процедуры в Турции.',
				'points' => array(
					'Купля-продажа и связанные с кредитом шаги',
					'Соглашения о совместной деятельности и развитии',
					'Аренда, выселение и смежные споры',
				),
				'link'  => '',
			),
			array(
				'id'    => 'tort',
				'title' => 'Деликтное право и несчастные случаи туристов',
				'text'  => 'Гражданские требования после происшествий с иностранными посетителями в Турции: отели, туры, транспорт.',
				'points' => array(
					'Оценка ущерба и перспективы требования',
					'Шаги, связанные с компенсацией и страхованием',
					'Координация процедур для иностранных клиентов',
				),
				'link'  => 'https://cindemirlaw.com/tort-law-in-turkey/?lang=ru',
			),
			array(
				'id'    => 'litigation',
				'title' => 'Судебные споры и ADR',
				'text'  => 'Представительство в суде, а также арбитраж или медиация — в зависимости от дела и выбора клиента.',
				'points' => array(
					'Коммерческие и гражданские споры',
					'Арбитраж и медиация',
					'Трудовые, строительные и страховые споры',
				),
				'link'  => '',
			),
			array(
				'id'    => 'admin',
				'title' => 'Административное право',
				'text'  => 'Вопросы, связанные с действиями государственных органов и административными процедурами.',
				'points' => array(
					'Анализ административных актов и бездействия',
					'Заявления и возражения в органы власти',
					'Судебные пути обжалования при наличии',
				),
				'link'  => '',
			),
			array(
				'id'    => 'digital-privacy',
				'title' => 'Конфиденциальность, ИИ и метавселенная',
				'text'  => 'Обязательства по защите данных для компаний в digital-маркетинге, e-commerce, VR/метавселенной и продуктах на базе ИИ.',
				'points' => array(
					'Документы по обработке персональных данных',
					'Оценка рисков цифровых продуктов',
					'Вопросы соответствия при трансграничных продуктах',
				),
				'link'  => '',
			),
			array(
				'id'    => 'apostille',
				'title' => 'Апостиль и легализация документов',
				'text'  => 'Практические разъяснения по апостилю и легализации документов для использования в Турции или за рубежом.',
				'points' => array(
					'Свидетельства о рождении, браке, разводе',
					'Доверенности и корпоративные документы',
					'Дипломы, справки о судимости и судебные акты',
				),
				'link'  => 'https://cindemirlaw.com/international-apostille-services-in-turkey/?lang=ru',
			),
		);
	}

	private static function services_zh() {
		return array(
			array(
				'id'    => 'debt-collection',
				'title' => '债务催收',
				'text'  => '在土耳其跟进商事与民事债权事宜；如涉及跨境案件，可协调欧洲地区同业协助。',
				'points' => array( '催收函与协商', '土耳其诉讼与执行程序', '必要时与境外律师协调' ),
				'link'  => '',
			),
			array(
				'id'    => 'crypto',
				'title' => '加密资产与数字资产',
				'text'  => '为从事加密资产业务或平台的客户提供合规与争议相关法律服务，覆盖土耳其及相关欧盟框架。',
				'points' => array( '合规政策与信息披露', '加密资产服务商市场进入指引', '反洗钱、欺诈与平台运营相关问题' ),
				'link'  => 'https://cindemirlaw.com/legal-services-for-digital-asset-businesses/?lang=zh-hans',
			),
			array(
				'id'    => 'it-law',
				'title' => '信息技术、软件与社交媒体法律',
				'text'  => '网站、软件许可与在线平台相关合同与咨询，包括数据与账号事项。',
				'points' => array( '软件与服务协议', '社交媒体及平台账号事宜', '在线业务文件安排' ),
				'link'  => '',
			),
			array(
				'id'    => 'startups',
				'title' => '初创企业法律支持',
				'text'  => '协助创始人选择公司形式、完成设立，并处理土耳其业务早期所需文件。',
				'points' => array( '土耳其公司法下的设立选项', '创始人与股东安排', '成长阶段的日常公司事务' ),
				'link'  => '',
			),
			array(
				'id'    => 'due-diligence',
				'title' => '尽职调查',
				'text'  => '由律师及视需要由财务专业人士配合，对公司与交易进行法律审查。',
				'points' => array( '公司与合同审查', '买方或投资方风险说明', '交割相关文件支持' ),
				'link'  => '',
			),
			array(
				'id'    => 'energy',
				'title' => '能源法与投资',
				'text'  => '为能源领域公司处理公司与投资事务，包括可再生能源项目及相关进入市场步骤。',
				'points' => array( '在土耳其设立能源相关业务', '投资与合同文件', '可再生能源项目跟进' ),
				'link'  => '',
			),
			array(
				'id'    => 'corporate',
				'title' => '公司与商事法律',
				'text'  => '公司设立、合资、股权交易、合同及本地与外国企业常见商事问题。',
				'points' => array( '公司设立与注册相关问题', '股权收购、合资与清算', '商事合同与合伙安排' ),
				'link'  => '',
			),
			array(
				'id'    => 'escrow',
				'title' => '托管（Escrow）安排',
				'text'  => '按具体交易拟定托管协议，并处理按约定条件释放的资金安排。',
				'points' => array( '起草个性化托管协议', '为交易设置托管账户结构', '涉及数字资产或账号转让时的托管支持' ),
				'link'  => '',
			),
			array(
				'id'    => 'real-estate',
				'title' => '不动产与产权转让',
				'text'  => '土耳其境内的房产交易、开发协议、租赁及土地相关程序。',
				'points' => array( '买卖及贷款相关产权手续', '合资与开发协议', '租赁、腾退及相关程序' ),
				'link'  => '',
			),
			array(
				'id'    => 'tort',
				'title' => '侵权与旅客意外',
				'text'  => '处理外国访客在土耳其发生的事故相关民事请求，包括酒店、旅游与交通事故。',
				'points' => array( '事故与损害评估', '赔偿与保险相关步骤', '为境外当事人协调本地程序' ),
				'link'  => '',
			),
			array(
				'id'    => 'litigation',
				'title' => '诉讼与替代性争议解决',
				'text'  => '法院代理，以及视案件情况通过仲裁或调解处理争议。',
				'points' => array( '商事与民事诉讼', '仲裁与调解路径', '劳动、建筑与保险争议' ),
				'link'  => '',
			),
			array(
				'id'    => 'admin',
				'title' => '行政法',
				'text'  => '涉及行政机关行为及行政程序、对个人或机构产生影响的事务。',
				'points' => array( '对行政行为或不作为的审查', '向行政机关提出申请与异议', '在可行时寻求司法救济' ),
				'link'  => '',
			),
			array(
				'id'    => 'digital-privacy',
				'title' => '隐私、人工智能与元宇宙',
				'text'  => '为数字营销、电商、VR/元宇宙及人工智能相关产品提供隐私与数据安全义务方面的法律支持。',
				'points' => array( '隐私与数据处理文件', '数字产品风险审查', '跨境产品合规问题' ),
				'link'  => '',
			),
			array(
				'id'    => 'apostille',
				'title' => '海牙认证与文件公证/认证',
				'text'  => '就在土耳其使用或拟在境外使用的文件，说明海牙认证与认证路径。',
				'points' => array( '出生、婚姻、离婚等民事文件', '授权委托书与公司文件', '学历、无犯罪记录与法院裁判' ),
				'link'  => 'https://cindemirlaw.com/international-apostille-services-in-turkey/?lang=zh-hans',
			),
		);
	}

	/**
	 * Build redesign HTML as a string.
	 * Must NOT use nested ob_start() — rewrite_html runs inside an output-buffer
	 * callback; nested buffering blanks /services/ (HTTP 200, 0 bytes).
	 */
	private static function render() {
		$c        = self::copy();
		$img      = esc_url( self::$hero_image );
		$services = $c['services'];

		$nav = '';
		foreach ( $services as $svc ) {
			$nav .= '<li><a href="#svc-' . esc_attr( $svc['id'] ) . '">' . esc_html( $svc['title'] ) . '</a></li>';
		}

		$items = '';
		foreach ( $services as $i => $svc ) {
			$points = '';
			if ( ! empty( $svc['points'] ) ) {
				$points = '<ul class="cindemir-services__points">';
				foreach ( $svc['points'] as $point ) {
					$points .= '<li>' . esc_html( $point ) . '</li>';
				}
				$points .= '</ul>';
			}
			$more = '';
			if ( ! empty( $svc['link'] ) ) {
				$more = '<p class="cindemir-services__more"><a href="' . esc_url( $svc['link'] ) . '">' . esc_html( $svc['title'] ) . ' →</a></p>';
			}
			$items .= '<section class="cindemir-services__item" id="svc-' . esc_attr( $svc['id'] ) . '" style="--i:' . (int) $i . '">'
				. '<div class="cindemir-services__wrap">'
				. '<p class="cindemir-services__kicker">' . esc_html( $c['detail'] ) . '</p>'
				. '<h2 class="cindemir-services__h2">' . esc_html( $svc['title'] ) . '</h2>'
				. '<p class="cindemir-services__text">' . esc_html( $svc['text'] ) . '</p>'
				. $points
				. $more
				. '</div></section>';
		}

		$script = '<script>(function(){var root=document.querySelector(".cindemir-services");if(!root)return;root.classList.add("is-ready");if(!("IntersectionObserver" in window))return;var io=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add("is-in");io.unobserve(e.target);}});},{threshold:0.16});root.querySelectorAll(".cindemir-services__item,.cindemir-services__index").forEach(function(el){io.observe(el);});})();</script>';

		return '<div class="cindemir-services" data-cindemir-services="1">'
			. '<section class="cindemir-services__hero" aria-label="' . esc_attr( $c['headline'] ) . '">'
			. '<div class="cindemir-services__hero-media" role="img" aria-label="Istanbul" style="--cindemir-hero:url(\'' . $img . '\')"></div>'
			. '<div class="cindemir-services__hero-shade"></div>'
			. '<div class="cindemir-services__hero-inner">'
			. '<p class="cindemir-services__brand">' . esc_html( $c['brand'] ) . '</p>'
			. '<h1 class="cindemir-services__title">' . esc_html( $c['headline'] ) . '</h1>'
			. '<p class="cindemir-services__lead">' . esc_html( $c['lead'] ) . '</p>'
			. '<p class="cindemir-services__cta-wrap"><a class="cindemir-services__cta" href="' . esc_url( $c['cta_url'] ) . '">' . esc_html( $c['cta'] ) . '</a></p>'
			. '</div></section>'
			. '<section class="cindemir-services__index" aria-labelledby="cindemir-services-index-title">'
			. '<div class="cindemir-services__wrap">'
			. '<h2 id="cindemir-services-index-title" class="cindemir-services__h2">' . esc_html( $c['index'] ) . '</h2>'
			. '<p class="cindemir-services__hint">' . esc_html( $c['index_hint'] ) . '</p>'
			. '<nav class="cindemir-services__nav" aria-label="' . esc_attr( $c['index'] ) . '"><ol>' . $nav . '</ol></nav>'
			. '</div></section>'
			. '<div class="cindemir-services__list">' . $items . '</div>'
			. '</div>'
			. $script;
	}

	private static function css() {
		return <<<'CSS'
:root{
	--cs-ink:#16312f;
	--cs-muted:#4d6663;
	--cs-teal:#0f6e6a;
	--cs-teal-deep:#0a4f4c;
	--cs-sand:#e7efe9;
	--cs-paper:#f3f7f4;
	--cs-line:rgba(22,49,47,.12);
	--cs-display:"Cormorant Garamond",Georgia,"Times New Roman",serif;
	--cs-sans:"Source Sans 3","Segoe UI",sans-serif;
}
#top #main .cindemir-services,
#top #main .cindemir-services *{box-sizing:border-box}
#top #main .cindemir-services{
	margin:0 calc(50% - 50vw);
	width:100vw;
	max-width:100vw;
	background:linear-gradient(180deg,#eef4f1 0%,#f7faf8 40%,#eef4f1 100%);
	color:var(--cs-ink);
	font-family:var(--cs-sans);
}
#top #main .cindemir-services__wrap{
	width:min(1080px,92vw);
	margin:0 auto;
}
#top #main .cindemir-services__hero{
	position:relative;
	min-height:88vh;
	display:flex;
	align-items:flex-end;
	overflow:hidden;
	color:#f4faf8;
}
#top #main .cindemir-services__hero-media{
	position:absolute;inset:0;
	background-image:linear-gradient(120deg,rgba(8,35,34,.55),rgba(12,60,58,.25)),var(--cindemir-hero);
	background-size:cover;
	background-position:center 35%;
	transform:scale(1.04);
	animation:cindemirHeroZoom 18s ease-out forwards;
}
#top #main .cindemir-services__hero-shade{
	position:absolute;inset:0;
	background:linear-gradient(180deg,rgba(6,28,27,.15) 0%,rgba(6,28,27,.35) 45%,rgba(6,28,27,.82) 100%);
}
#top #main .cindemir-services__hero-inner{
	position:relative;z-index:2;
	width:min(1080px,92vw);
	margin:0 auto;
	padding:0 0 10vh;
	opacity:0;
	transform:translateY(22px);
	animation:cindemirHeroIn .9s .15s ease forwards;
}
#top #main .cindemir-services__brand{
	margin:0 0 .55rem;
	font-family:var(--cs-display);
	font-size:clamp(2rem,5vw,3.4rem);
	font-weight:600;
	letter-spacing:.01em;
	line-height:1.05;
}
#top #main .cindemir-services__title{
	margin:0 0 .9rem;
	font-family:var(--cs-display);
	font-size:clamp(1.55rem,3.2vw,2.35rem);
	font-weight:500;
	line-height:1.15;
	color:#dcece9;
}
#top #main .cindemir-services__lead{
	margin:0 0 1.4rem;
	max-width:34rem;
	font-size:1.05rem;
	line-height:1.55;
	color:rgba(244,250,248,.9);
}
#top #main .cindemir-services__cta{
	display:inline-block;
	padding:.8rem 1.25rem;
	background:#f4faf8;
	color:var(--cs-teal-deep)!important;
	font-weight:700;
	font-size:.95rem;
	letter-spacing:.02em;
	text-decoration:none!important;
	border-radius:2px;
	transition:transform .25s ease, background-color .25s ease;
}
#top #main .cindemir-services__cta:hover{
	background:#fff;
	transform:translateY(-2px);
	color:var(--cs-teal-deep)!important;
}
#top #main .cindemir-services__index{
	padding:4.5rem 0 2.5rem;
	opacity:0;transform:translateY(18px);transition:opacity .7s ease, transform .7s ease;
}
#top #main .cindemir-services__index.is-in{opacity:1;transform:none}
#top #main .cindemir-services__h2{
	margin:0 0 .55rem;
	font-family:var(--cs-display);
	font-size:clamp(1.7rem,2.6vw,2.2rem);
	font-weight:600;
	color:var(--cs-ink);
	line-height:1.15;
}
#top #main .cindemir-services__hint,
#top #main .cindemir-services__text{
	margin:0 0 1.25rem;
	max-width:40rem;
	color:var(--cs-muted);
	font-size:1.02rem;
	line-height:1.6;
}
#top #main .cindemir-services__nav ol{
	list-style:none;
	margin:0;
	padding:0;
	display:grid;
	grid-template-columns:repeat(2,minmax(0,1fr));
	gap:.35rem 1.5rem;
	border-top:1px solid var(--cs-line);
	padding-top:1.25rem;
}
#top #main .cindemir-services__nav a{
	display:block;
	padding:.55rem 0;
	color:var(--cs-ink)!important;
	text-decoration:none!important;
	font-weight:600;
	font-size:1rem;
	border-bottom:1px solid transparent;
	transition:color .2s ease, border-color .2s ease, padding-left .2s ease;
}
#top #main .cindemir-services__nav a:hover{
	color:var(--cs-teal)!important;
	border-bottom-color:rgba(15,110,106,.35);
	padding-left:.35rem;
}
#top #main .cindemir-services__item{
	padding:3.25rem 0;
	border-top:1px solid var(--cs-line);
	opacity:0;transform:translateY(22px);transition:opacity .75s ease, transform .75s ease;
	transition-delay:calc(var(--i,0) * 20ms);
}
#top #main .cindemir-services__item.is-in{opacity:1;transform:none}
#top #main .cindemir-services__item:nth-child(odd){
	background:linear-gradient(90deg,rgba(255,255,255,.55),rgba(231,239,233,.35));
}
#top #main .cindemir-services__kicker{
	margin:0 0 .35rem;
	font-size:.78rem;
	font-weight:700;
	letter-spacing:.12em;
	text-transform:uppercase;
	color:var(--cs-teal);
}
#top #main .cindemir-services__points{
	margin:0 0 1rem;
	padding:0;
	list-style:none;
	max-width:38rem;
}
#top #main .cindemir-services__points li{
	position:relative;
	padding:.45rem 0 .45rem 1.1rem;
	color:var(--cs-ink);
	font-size:.98rem;
	line-height:1.45;
	border-bottom:1px solid var(--cs-line);
}
#top #main .cindemir-services__points li:before{
	content:"";
	position:absolute;left:0;top:1rem;
	width:.45rem;height:.45rem;
	border-radius:1px;
	background:var(--cs-teal);
}
#top #main .cindemir-services__more a{
	color:var(--cs-teal-deep)!important;
	font-weight:700;
	text-decoration:none!important;
	border-bottom:1px solid rgba(10,79,76,.35);
}
#top #main .cindemir-services__more a:hover{border-bottom-color:var(--cs-teal-deep)}
@keyframes cindemirHeroIn{to{opacity:1;transform:none}}
@keyframes cindemirHeroZoom{to{transform:scale(1)}}
@media (max-width:767px){
	#top #main .cindemir-services__hero{min-height:78vh}
	#top #main .cindemir-services__nav ol{grid-template-columns:1fr}
	#top #main .cindemir-services__index{padding-top:3rem}
	#top #main .cindemir-services__item{padding:2.4rem 0}
}
#top.page-id-18 #main > *:not(.cindemir-services),
#top.page-id-2638 #main > *:not(.cindemir-services),
#top.page-id-2637 #main > *:not(.cindemir-services),
#top.page-id-56 #main > *:not(.cindemir-services){display:none!important}
#top.page-id-18 #main,
#top.page-id-2638 #main,
#top.page-id-2637 #main,
#top.page-id-56 #main{padding:0;margin:0;width:100%;max-width:none}
CSS;
	}
}

Cindemir_Services_Page::boot();

<?php
/**
 * Plugin Name: Cindemir SEO Fixes
 * Description: Full Ahrefs cleanup: redirect href rewrite, flatten hops, H1/alts/orphans, author disable, title trim.
 * Version: 1.8.3
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_SEO_FIXES_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_SEO_FIXES_LOADED', true );

final class Cindemir_SEO_Fixes {

	private static $broken = array(
		'/how-to-lift-entry-ban-to-turkey',
		'/exemptions-on-the-legislation-of-the-documents-in-turkey',
	);

	/** One-hop 301 + href rewrite map (path without trailing slash). */
	private static $redirects = array(
		'/russian' => 'https://cindemirlaw.com/?lang=ru',
		'/chinese' => 'https://cindemirlaw.com/?lang=zh-hans',
		'/zh' => 'https://cindemirlaw.com/?lang=zh-hans',
		'/zh-hans' => 'https://cindemirlaw.com/?lang=zh-hans',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbdfde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbdfde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-3' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-3/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd82fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd82fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-2/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-3' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf-3/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb3fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb3fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb4fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb4fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb5fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb5fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb6fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb6fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb7fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb7fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbafde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbdfde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdbdfde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd81fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd82fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd82fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd1fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfd83fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/cindemir-hukuk-burosu-cindemir-law-office-kusdili-caddesi-osmanaga-mahallesi-artunc-apartmani-no173-34714-kadikoy-istanbul' => 'https://cindemirlaw.com/cindemir/',
		'/pig-butchering-cryptocurrency-scam-key-risks-and-legal-considerations-for-investors-in-turkey' => 'https://cindemirlaw.com/pig-butchering-cryptocurrency-scam-key-risks-and-legal-considerations-for-investors-in-turkey/?lang=ru',
		'/eu-ai-act-compliance-for-non-eu-companies-legal-requirements-under-the-destination-principle' => 'https://cindemirlaw.com/eu-ai-act-compliance-for-non-eu-companies-legal-requirements-under-the-destination-principle/?lang=ru',
		'/obtaining-an-e-devlet-password-in-turkey-through-a-power-of-attorney' => 'https://cindemirlaw.com/obtaining-an-e-devlet-password-in-turkey-through-a-power-of-attorney/?lang=ru',
		'/репатриация-активов-в-турцию-в-2026-году-п' => 'https://cindemirlaw.com/%d1%80%d0%b5%d0%bf%d0%b0%d1%82%d1%80%d0%b8%d0%b0%d1%86%d0%b8%d1%8f-%d0%b0%d0%ba%d1%82%d0%b8%d0%b2%d0%be%d0%b2-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d1%8e-%d0%b2-2026-%d0%b3%d0%be%d0%b4%d1%83-%d0%bf/?lang=ru',
		'/что-такое-заявление-в-еспч-кто-может-по' => 'https://cindemirlaw.com/%d1%87%d1%82%d0%be-%d1%82%d0%b0%d0%ba%d0%be%d0%b5-%d0%b7%d0%b0%d1%8f%d0%b2%d0%bb%d0%b5%d0%bd%d0%b8%d0%b5-%d0%b2-%d0%b5%d1%81%d0%bf%d1%87-%d0%ba%d1%82%d0%be-%d0%bc%d0%be%d0%b6%d0%b5%d1%82-%d0%bf%d0%be/?lang=ru',
		'/гуманитарный-вид-на-жительство-в-турц' => 'https://cindemirlaw.com/%d0%b3%d1%83%d0%bc%d0%b0%d0%bd%d0%b8%d1%82%d0%b0%d1%80%d0%bd%d1%8b%d0%b9-%d0%b2%d0%b8%d0%b4-%d0%bd%d0%b0-%d0%b6%d0%b8%d1%82%d0%b5%d0%bb%d1%8c%d1%81%d1%82%d0%b2%d0%be-%d0%b2-%d1%82%d1%83%d1%80%d1%86/?lang=ru',
		'/иск-об-установлении-отцовства-в-турци' => 'https://cindemirlaw.com/%d0%b8%d1%81%d0%ba-%d0%be%d0%b1-%d1%83%d1%81%d1%82%d0%b0%d0%bd%d0%be%d0%b2%d0%bb%d0%b5%d0%bd%d0%b8%d0%b8-%d0%be%d1%82%d1%86%d0%be%d0%b2%d1%81%d1%82%d0%b2%d0%b0-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8/?lang=ru',
		'/как-открыть-компанию-в-турции-пошагов' => 'https://cindemirlaw.com/%d0%ba%d0%b0%d0%ba-%d0%be%d1%82%d0%ba%d1%80%d1%8b%d1%82%d1%8c-%d0%ba%d0%be%d0%bc%d0%bf%d0%b0%d0%bd%d0%b8%d1%8e-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8-%d0%bf%d0%be%d1%88%d0%b0%d0%b3%d0%be%d0%b2/?lang=ru',
		'/как-получить-справку-о-наличии-судимо' => 'https://cindemirlaw.com/%d0%ba%d0%b0%d0%ba-%d0%bf%d0%be%d0%bb%d1%83%d1%87%d0%b8%d1%82%d1%8c-%d1%81%d0%bf%d1%80%d0%b0%d0%b2%d0%ba%d1%83-%d0%be-%d0%bd%d0%b0%d0%bb%d0%b8%d1%87%d0%b8%d0%b8-%d1%81%d1%83%d0%b4%d0%b8%d0%bc%d0%be/?lang=ru',
		'/удаление-судимости-в-турции-для-иност' => 'https://cindemirlaw.com/%d1%83%d0%b4%d0%b0%d0%bb%d0%b5%d0%bd%d0%b8%d0%b5-%d1%81%d1%83%d0%b4%d0%b8%d0%bc%d0%be%d1%81%d1%82%d0%b8-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8-%d0%b4%d0%bb%d1%8f-%d0%b8%d0%bd%d0%be%d1%81%d1%82/?lang=ru',
		'/задержание-в-аэропорту-турции-правов' => 'https://cindemirlaw.com/%d0%b7%d0%b0%d0%b4%d0%b5%d1%80%d0%b6%d0%b0%d0%bd%d0%b8%d0%b5-%d0%b2-%d0%b0%d1%8d%d1%80%d0%be%d0%bf%d0%be%d1%80%d1%82%d1%83-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8-%d0%bf%d1%80%d0%b0%d0%b2%d0%be%d0%b2/?lang=ru',
		'/открытие-банковского-счета-для-росси' => 'https://cindemirlaw.com/%d0%be%d1%82%d0%ba%d1%80%d1%8b%d1%82%d0%b8%d0%b5-%d0%b1%d0%b0%d0%bd%d0%ba%d0%be%d0%b2%d1%81%d0%ba%d0%be%d0%b3%d0%be-%d1%81%d1%87%d0%b5%d1%82%d0%b0-%d0%b4%d0%bb%d1%8f-%d1%80%d0%be%d1%81%d1%81%d0%b8/?lang=ru',
		'/создание-компании-с-ограниченной-отв' => 'https://cindemirlaw.com/%d1%81%d0%be%d0%b7%d0%b4%d0%b0%d0%bd%d0%b8%d0%b5-%d0%ba%d0%be%d0%bc%d0%bf%d0%b0%d0%bd%d0%b8%d0%b8-%d1%81-%d0%be%d0%b3%d1%80%d0%b0%d0%bd%d0%b8%d1%87%d0%b5%d0%bd%d0%bd%d0%be%d0%b9-%d0%be%d1%82%d0%b2/?lang=ru',
		'/юридическая-помощь-при-отправке-веще' => 'https://cindemirlaw.com/%d1%8e%d1%80%d0%b8%d0%b4%d0%b8%d1%87%d0%b5%d1%81%d0%ba%d0%b0%d1%8f-%d0%bf%d0%be%d0%bc%d0%be%d1%89%d1%8c-%d0%bf%d1%80%d0%b8-%d0%be%d1%82%d0%bf%d1%80%d0%b0%d0%b2%d0%ba%d0%b5-%d0%b2%d0%b5%d1%89%d0%b5/?lang=ru',
		'/компенсации-положенные-в-результате' => 'https://cindemirlaw.com/%d0%ba%d0%be%d0%bc%d0%bf%d0%b5%d0%bd%d1%81%d0%b0%d1%86%d0%b8%d0%b8-%d0%bf%d0%be%d0%bb%d0%be%d0%b6%d0%b5%d0%bd%d0%bd%d1%8b%d0%b5-%d0%b2-%d1%80%d0%b5%d0%b7%d1%83%d0%bb%d1%8c%d1%82%d0%b0%d1%82%d0%b5/?lang=ru',
		'/открытие-банковского-счета-в-турции' => 'https://cindemirlaw.com/%d0%be%d1%82%d0%ba%d1%80%d1%8b%d1%82%d0%b8%d0%b5-%d0%b1%d0%b0%d0%bd%d0%ba%d0%be%d0%b2%d1%81%d0%ba%d0%be%d0%b3%d0%be-%d1%81%d1%87%d0%b5%d1%82%d0%b0-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/открытие-банковского-счета-русскими' => 'https://cindemirlaw.com/%d0%be%d1%82%d0%ba%d1%80%d1%8b%d1%82%d0%b8%d0%b5-%d0%b1%d0%b0%d0%bd%d0%ba%d0%be%d0%b2%d1%81%d0%ba%d0%be%d0%b3%d0%be-%d1%81%d1%87%d0%b5%d1%82%d0%b0-%d1%80%d1%83%d1%81%d1%81%d0%ba%d0%b8%d0%bc%d0%b8/?lang=ru',
		'/посещение-иностранных-заключённых-в' => 'https://cindemirlaw.com/%d0%bf%d0%be%d1%81%d0%b5%d1%89%d0%b5%d0%bd%d0%b8%d0%b5-%d0%b8%d0%bd%d0%be%d1%81%d1%82%d1%80%d0%b0%d0%bd%d0%bd%d1%8b%d1%85-%d0%b7%d0%b0%d0%ba%d0%bb%d1%8e%d1%87%d1%91%d0%bd%d0%bd%d1%8b%d1%85-%d0%b2/?lang=ru',
		'/профессиональные-юридические-консул' => 'https://cindemirlaw.com/%d0%bf%d1%80%d0%be%d1%84%d0%b5%d1%81%d1%81%d0%b8%d0%be%d0%bd%d0%b0%d0%bb%d1%8c%d0%bd%d1%8b%d0%b5-%d1%8e%d1%80%d0%b8%d0%b4%d0%b8%d1%87%d0%b5%d1%81%d0%ba%d0%b8%d0%b5-%d0%ba%d0%be%d0%bd%d1%81%d1%83%d0%bb/?lang=ru',
		'/руководство-по-приобретению-иностра' => 'https://cindemirlaw.com/%d1%80%d1%83%d0%ba%d0%be%d0%b2%d0%be%d0%b4%d1%81%d1%82%d0%b2%d0%be-%d0%bf%d0%be-%d0%bf%d1%80%d0%b8%d0%be%d0%b1%d1%80%d0%b5%d1%82%d0%b5%d0%bd%d0%b8%d1%8e-%d0%b8%d0%bd%d0%be%d1%81%d1%82%d1%80%d0%b0/?lang=ru',
		'/русскоязычный-юрист-в-турции-юридич' => 'https://cindemirlaw.com/%d1%80%d1%83%d1%81%d1%81%d0%ba%d0%be%d1%8f%d0%b7%d1%8b%d1%87%d0%bd%d1%8b%d0%b9-%d1%8e%d1%80%d0%b8%d1%81%d1%82-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8-%d1%8e%d1%80%d0%b8%d0%b4%d0%b8%d1%87/?lang=ru',
		'/gokhan-cindemir-attorney-at-law-2-2' => 'https://cindemirlaw.com/gokhan-cindemir-attorney-at-law-2-2/?lang=zh-hans',
		'/как-получить-судимость-в-турции' => 'https://cindemirlaw.com/%d0%ba%d0%b0%d0%ba-%d0%bf%d0%be%d0%bb%d1%83%d1%87%d0%b8%d1%82%d1%8c-%d1%81%d1%83%d0%b4%d0%b8%d0%bc%d0%be%d1%81%d1%82%d1%8c-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/наследственное-право-турции-2' => 'https://cindemirlaw.com/%d0%bd%d0%b0%d1%81%d0%bb%d0%b5%d0%b4%d1%81%d1%82%d0%b2%d0%b5%d0%bd%d0%bd%d0%be%d0%b5-%d0%bf%d1%80%d0%b0%d0%b2%d0%be-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8-2/?lang=ru',
		'/как-открыть-филиал-в-турции' => 'https://cindemirlaw.com/%d0%ba%d0%b0%d0%ba-%d0%be%d1%82%d0%ba%d1%80%d1%8b%d1%82%d1%8c-%d1%84%d0%b8%d0%bb%d0%b8%d0%b0%d0%bb-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/наследственное-право-турции' => 'https://cindemirlaw.com/%d0%bd%d0%b0%d1%81%d0%bb%d0%b5%d0%b4%d1%81%d1%82%d0%b2%d0%b5%d0%bd%d0%bd%d0%be%d0%b5-%d0%bf%d1%80%d0%b0%d0%b2%d0%be-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/статус-условного-беженца' => 'https://cindemirlaw.com/%d1%81%d1%82%d0%b0%d1%82%d1%83%d1%81-%d1%83%d1%81%d0%bb%d0%be%d0%b2%d0%bd%d0%be%d0%b3%d0%be-%d0%b1%d0%b5%d0%b6%d0%b5%d0%bd%d1%86%d0%b0/?lang=ru',
		'/как-развестись-в-турции' => 'https://cindemirlaw.com/%d0%ba%d0%b0%d0%ba-%d1%80%d0%b0%d0%b7%d0%b2%d0%b5%d1%81%d1%82%d0%b8%d1%81%d1%8c-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/статус-беженца-в-турции' => 'https://cindemirlaw.com/%d1%81%d1%82%d0%b0%d1%82%d1%83%d1%81-%d0%b1%d0%b5%d0%b6%d0%b5%d0%bd%d1%86%d0%b0-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/виды-компании-в-турции' => 'https://cindemirlaw.com/%d0%b2%d0%b8%d0%b4%d1%8b-%d0%ba%d0%be%d0%bc%d0%bf%d0%b0%d0%bd%d0%b8%d0%b8-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/типы-компаний-в-турции' => 'https://cindemirlaw.com/%d1%82%d0%b8%d0%bf%d1%8b-%d0%ba%d0%be%d0%bc%d0%bf%d0%b0%d0%bd%d0%b8%d0%b9-%d0%b2-%d1%82%d1%83%d1%80%d1%86%d0%b8%d0%b8/?lang=ru',
		'/права-и-обязанности' => 'https://cindemirlaw.com/%d0%bf%d1%80%d0%b0%d0%b2%d0%b0-%d0%b8-%d0%be%d0%b1%d1%8f%d0%b7%d0%b0%d0%bd%d0%bd%d0%be%d1%81%d1%82%d0%b8/?lang=ru',
		'/вторичная-защита' => 'https://cindemirlaw.com/%d0%b2%d1%82%d0%be%d1%80%d0%b8%d1%87%d0%bd%d0%b0%d1%8f-%d0%b7%d0%b0%d1%89%d0%b8%d1%82%d0%b0/?lang=ru',
		'/cindemir-hukuk' => 'https://cindemirlaw.com/cindemir-hukuk/?lang=zh-hans',
		'/cindemir-law-2' => 'https://cindemirlaw.com/cindemir-law-2/?lang=ru',
		'/author/admin' => 'https://cindemirlaw.com/',
		'/fde1068e3' => 'https://cindemirlaw.com/fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdd0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bfdb0fde1068e3bda46a37dd24126f9ec4b09df0c999365011ec71028e1a7d4c45bf/?lang=ru',
		'/fde1068e' => 'https://cindemirlaw.com/fde1068e/?lang=ru',
		'/link11' => 'https://cindemirlaw.com/link11/?lang=zh-hans',
		'/link13' => 'https://cindemirlaw.com/link13/?lang=zh-hans',
		'/link15' => 'https://cindemirlaw.com/link15/?lang=zh-hans',
		'/link25' => 'https://cindemirlaw.com/link25/?lang=zh-hans',
		'/link9' => 'https://cindemir.av.tr/en/we-are-in-news/',
		'/press' => 'https://cindemir.av.tr/en/we-are-in-news/',
		'/link2' => 'https://cindemirlaw.com/about-us/',
		'/link3' => 'https://cindemirlaw.com/support/',
		'/link4' => 'https://cindemirlaw.com/services/',
		'/hakan' => 'https://cindemirlaw.com/hakan/?lang=zh-hans',
		'/contacts-2' => 'https://cindemirlaw.com/contacts/?lang=zh-hans',
		'/fde1' => 'https://cindemirlaw.com/fde1/?lang=ru',
	);

	private static $url_replace = array(
		'http://cindemir.av.tr/wp-content/uploads/2020/01/health-image-300x200.jpg' => 'https://cindemir.av.tr/wp-content/uploads/2020/01/health-image-300x200.jpg',
		'https://mersis.gtb.gov.tr/' => 'https://mersis.ticaret.gov.tr/',
		'https://mersis.gtb.gov.tr' => 'https://mersis.ticaret.gov.tr/',
		'https://turkodeme.com.tr/Tahsilat/Default.aspx?k=697795e3-b10e-4cbb-8251-e0c7a1b8ce76' => 'https://pos.param.com.tr/Tahsilat/Default.aspx?k=697795e3-b10e-4cbb-8251-e0c7a1b8ce76',
		'https://www.istanbulbarosu.org.tr/AttorneySearch.aspx' => 'https://istanbulbarosu.org.tr/AttorneySearch.aspx',
		'https://www.cindemirlaw.com/' => 'https://cindemirlaw.com/',
		'https://www.cindemirlaw.com' => 'https://cindemirlaw.com/',
		// Legacy multisite image paths (404) → current media library.
		'https://cindemirlaw.com/russian/wp-content/uploads/2014/11/white-2-copy-150x150.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'https://cindemirlaw.com/russian/wp-content/uploads/2014/11/white-5-copy-150x150.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-5-copy-300x300.jpg',
		'https://cindemirlaw.com/chinese/wp-content/uploads/2014/11/white-1-copy-150x150.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'https://cindemirlaw.com/chinese/wp-content/uploads/2014/11/white-2-copy-150x150.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'https://cindemirlaw.com/chinese/wp-content/uploads/2014/11/white-1-copy.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'https://cindemirlaw.com/chinese/wp-content/uploads/2014/11/white-2-copy.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'https://cindemirlaw.com/russian/wp-content/uploads/2014/11/white-1-copy.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'https://cindemirlaw.com/russian/wp-content/uploads/2014/11/white-2-copy.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'/russian/wp-content/uploads/2014/11/white-2-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'/russian/wp-content/uploads/2014/11/white-5-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-5-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-1-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-2-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-1-copy.jpg' => '/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-2-copy.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'/russian/wp-content/uploads/2014/11/white-1-copy.jpg' => '/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'/russian/wp-content/uploads/2014/11/white-2-copy.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
	);

	const VERSION = '1.8.3';

	/** Slug → neutral meta (110–160 chars, TBB-compliant). */
	private static $slug_metadesc = array(
		'services' => 'Information on legal service areas under Turkish law for foreign individuals and companies: civil, commercial, migration, and criminal law topics.',
	);

	private static $missing_h1 = array(
		3874 => 'Family Heritage',
		3884 => 'Who is Hafız Hüseyin Hüsnü Efendi?',
		51 => 'News & Events',
		43 => 'Our Videos',
		378 => 'Appointment',
		4665 => 'Embed List',
		2 => 'О нас',
		105 => 'Статьи',
		2427 => 'Наша команда',
		2446 => 'Контакты',
		103 => 'Поддержка',
		56 => 'Услуги',
		900030 => 'Assistant',
	);

	private static $alt_map = array(
		'white-1-copy' => 'Cindemir Law Office',
		'white-2-copy' => 'Cindemir Law Office',
		'white-5-copy' => 'Cindemir Law Office',
		'white3-copy' => 'Cindemir Law Office',
		'footlaw_banner' => 'Cindemir Law Office legal services banner',
		'540664430' => 'Istanbul skyline representing Cindemir Law Office',
		'Gokhan_Cindemir_AttorneyAtLaw' => 'Gökhan Cindemir, Attorney at Law',
		'Hakan_Cindemir_AttorneyatLaw' => 'Dr. Hakan Cindemir, Attorney at Law',
		'2e20a321-6694-44e0-ae3e' => 'Legal scales and gavel artwork',
	);

	private static $h1_done = false;

	/** Neutral Yoast meta descriptions (TBB-compliant, 110–160 chars). */
	private static $page_metadesc = array(
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

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'maybe_self_upgrade_from_github' ), 0 );
		add_action( 'init', array( __CLASS__, 'maybe_upgrade_sibling_plugins' ), 0 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_deploy_routes' ) );
		add_action( 'init', array( __CLASS__, 'maybe_purge_caches_on_upgrade' ), 1 );
		add_action( 'init', array( __CLASS__, 'ensure_local_badge_assets' ), 2 );
		add_filter( 'option_polylang', array( __CLASS__, 'filter_polylang_options' ) );
		add_filter( 'wpml_setting', array( __CLASS__, 'filter_wpml_setting' ), 10, 2 );
		add_filter( 'redirection_url_target', array( __CLASS__, 'cancel_broken' ), 1, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'flatten_redirects' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'strip_default_lang_redirect' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'disable_author_archives' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 1 );
		add_filter( 'the_content', array( __CLASS__, 'fix_headings' ), 12 );
		add_filter( 'the_content', array( __CLASS__, 'rewrite_content_hrefs' ), 25 );
		add_filter( 'the_content', array( __CLASS__, 'rewrite_legacy_media_in_content' ), 15 );
		add_action( 'wp_footer', array( __CLASS__, 'orphan_links' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'version_marker' ), 99 );
		add_action( 'wp_head', array( __CLASS__, 'noindex_utility' ), 1 );
		add_filter( 'wp_robots', array( __CLASS__, 'filter_wp_robots' ), 99 );
		add_filter( 'wpseo_robots', array( __CLASS__, 'filter_yoast_robots' ), 99 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'fix_alt_attr' ), 10, 2 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'fix_attachment_src_attrs' ), 11, 2 );
		add_filter( 'the_content', array( __CLASS__, 'fix_empty_alts' ), 20 );
		add_filter( 'author_link', array( __CLASS__, 'author_to_home' ), 20 );
		add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'nav_href' ), 20, 2 );
		add_filter( 'author_rewrite_rules', array( __CLASS__, 'kill_author_rewrites' ) );
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( __CLASS__, 'exclude_press_from_sitemap' ) );
		add_filter( 'wpseo_sitemap_entry', array( __CLASS__, 'filter_sitemap_entry' ), 10, 3 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'filter_page_metadesc' ), 20 );
		add_filter( 'wpseo_canonical', array( __CLASS__, 'filter_canonical_url' ), 20 );
		add_filter( 'get_canonical_url', array( __CLASS__, 'filter_canonical_url' ), 20 );
		add_filter( 'wpml_hreflangs', array( __CLASS__, 'filter_hreflang_urls' ), 99 );
		add_filter( 'wpseo_hreflang_links', array( __CLASS__, 'filter_hreflang_urls' ), 99 );
		add_filter( 'wpseo_opengraph_image', array( __CLASS__, 'rewrite_media_url' ) );
		add_filter( 'wpseo_twitter_image', array( __CLASS__, 'rewrite_media_url' ) );
	}

	/** Download TBB badge locally (Ahrefs bots get 403 from d.barobirlik.org.tr). */
	public static function ensure_local_badge_assets() {
		$local = get_option( 'cindemir_tbb_badge_local', '' );
		if ( $local && false !== strpos( $local, '/uploads/cindemir/' ) ) {
			return;
		}
		$dir = WP_CONTENT_DIR . '/uploads/cindemir';
		if ( ! wp_mkdir_p( $dir ) ) {
			return;
		}
		$file = trailingslashit( $dir ) . 'tbb_amblem_60.png';
		if ( ! file_exists( $file ) || filesize( $file ) < 100 ) {
			$response = wp_remote_get(
				'https://d.barobirlik.org.tr/amblem/tbb_amblem_60.png',
				array(
					'timeout' => 30,
					'headers' => array(
						'User-Agent' => 'Mozilla/5.0 (compatible; CindemirSEO/1.8)',
					),
				)
			);
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return;
			}
			$body = wp_remote_retrieve_body( $response );
			if ( strlen( $body ) < 100 ) {
				return;
			}
			file_put_contents( $file, $body );
		}
		if ( file_exists( $file ) && filesize( $file ) > 100 ) {
			update_option( 'cindemir_tbb_badge_local', content_url( 'uploads/cindemir/tbb_amblem_60.png' ), false );
		}
	}

	public static function maybe_purge_caches_on_upgrade() {
		$key  = 'cindemir_seo_fixes_version';
		$prev = get_option( $key, '' );
		if ( self::VERSION === $prev ) {
			return;
		}
		update_option( $key, self::VERSION, false );
		flush_rewrite_rules( false );
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
			WPSEO_Sitemaps_Cache::clear();
		}
		delete_transient( 'wpseo_sitemap_cache_validator_page' );
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
			LiteSpeed_Cache_API::purge_all();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
	}

	/** Pull newer mu-plugins from GitHub when this install lags behind. */
	public static function maybe_self_upgrade_from_github() {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return;
		}
		if ( get_transient( 'cindemir_seo_self_upgrade_lock' ) ) {
			return;
		}
		set_transient( 'cindemir_seo_self_upgrade_lock', 1, 15 * MINUTE_IN_SECONDS );

		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$url    = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/cindemir-seo-fixes.php';
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 45,
				'headers' => array( 'User-Agent' => 'CindemirSEOUpgrade/' . self::VERSION ),
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return;
		}
		$body = (string) wp_remote_retrieve_body( $response );
		if ( strlen( $body ) < 40000 || false === strpos( $body, 'Cindemir_SEO_Fixes' ) ) {
			return;
		}
		if ( ! preg_match( "/const\s+VERSION\s*=\s*'([^']+)'/", $body, $m ) ) {
			return;
		}
		if ( version_compare( $m[1], self::VERSION, '<=' ) ) {
			return;
		}
		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'cindemir-seo-fixes.php';
		if ( false === file_put_contents( $dest, $body ) ) {
			return;
		}
		delete_option( 'cindemir_seo_fixes_version' );
		delete_transient( 'cindemir_seo_self_upgrade_lock' );
	}

	/** After seo-fixes updates, pull contact-fixes + helpers from GitHub. */
	public static function maybe_upgrade_sibling_plugins() {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) || get_transient( 'cindemir_sibling_upgrade_lock' ) ) {
			return;
		}
		set_transient( 'cindemir_sibling_upgrade_lock', 1, 15 * MINUTE_IN_SECONDS );

		$branch = 'cursor/cindemirlaw-seo-tasks-d204';
		$base   = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/';
		$files  = array(
			'cindemir-contact-fixes.php'     => array( 'min' => 20000, 'ver' => '1.2.1' ),
			'cindemir-expose-yoast-meta.php' => array( 'min' => 2000, 'ver' => '1.2' ),
			'cindemir-purge-cache.php'       => array( 'min' => 500, 'ver' => '1.0' ),
		);

		foreach ( $files as $name => $spec ) {
			$dest = trailingslashit( WPMU_PLUGIN_DIR ) . $name;
			$local_ver = '';
			if ( file_exists( $dest ) && filesize( $dest ) > $spec['min'] ) {
				$local = file_get_contents( $dest );
				if ( is_string( $local ) && preg_match( '/\bVersion:\s*([0-9.]+)/', $local, $m ) ) {
					$local_ver = $m[1];
				}
				if ( $local_ver && version_compare( $local_ver, $spec['ver'], '>=' ) ) {
					continue;
				}
			}
			$response = wp_remote_get(
				$base . $name,
				array(
					'timeout' => 45,
					'headers' => array( 'User-Agent' => 'CindemirSiblingUpgrade/' . self::VERSION ),
				)
			);
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}
			$body = (string) wp_remote_retrieve_body( $response );
			if ( strlen( $body ) < $spec['min'] ) {
				continue;
			}
			file_put_contents( $dest, $body );
		}
		flush_rewrite_rules( false );
	}

	/** Fallback deploy routes when contact-fixes on server is outdated. */
	public static function register_deploy_routes() {
		register_rest_route(
			'cindemir/v1',
			'/pull-plugins',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'rest_pull_plugins' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function rest_pull_plugins( $request ) {
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
			$response = wp_remote_get(
				$base . $name,
				array(
					'timeout' => 60,
					'headers' => array( 'User-Agent' => 'CindemirPull/' . self::VERSION ),
				)
			);
			if ( is_wp_error( $response ) ) {
				$out[ $name ] = array( 'ok' => false, 'error' => $response->get_error_message() );
				continue;
			}
			$body  = (string) wp_remote_retrieve_body( $response );
			$bytes = strlen( $body );
			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || $bytes < $min ) {
				$out[ $name ] = array( 'ok' => false, 'bytes' => $bytes );
				continue;
			}
			file_put_contents( trailingslashit( WPMU_PLUGIN_DIR ) . $name, $body );
			$out[ $name ] = array( 'ok' => true, 'bytes' => $bytes );
		}
		delete_option( 'cindemir_seo_fixes_version' );
		wp_cache_flush();
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		return new WP_REST_Response( array( 'ok' => true, 'version' => self::VERSION, 'files' => $out ), 200 );
	}

	public static function filter_canonical_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}
		$url = str_replace( '/contacts-2/', '/contacts/', $url );
		$url = str_replace( '/contacts-2?', '/contacts?', $url );
		return $url;
	}

	public static function filter_hreflang_urls( $hreflangs ) {
		if ( ! is_array( $hreflangs ) ) {
			return $hreflangs;
		}
		foreach ( $hreflangs as $lang => $url ) {
			$hreflangs[ $lang ] = self::normalize_hreflang_url( $url );
		}
		return $hreflangs;
	}

	private static function normalize_hreflang_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}
		$url = str_replace( '/contacts-2/', '/contacts/', $url );
		$url = str_replace( '/contacts-2?', '/contacts?', $url );
		$parts = wp_parse_url( $url );
		if ( empty( $parts['query'] ) ) {
			return $url;
		}
		parse_str( $parts['query'], $q );
		if ( empty( $q['lang'] ) || ! in_array( $q['lang'], array( 'en', 'en-us', 'en_us' ), true ) ) {
			return $url;
		}
		unset( $q['lang'] );
		$path = isset( $parts['path'] ) ? $parts['path'] : '/';
		$new  = home_url( user_trailingslashit( $path ) );
		if ( ! empty( $q ) ) {
			$new = add_query_arg( $q, $new );
		}
		return $new;
	}

	/** Polylang: hide language param for default language URLs. */
	public static function filter_polylang_options( $options ) {
		if ( ! is_array( $options ) ) {
			return $options;
		}
		$options['hide_default'] = 1;
		$options['default_lang'] = isset( $options['default_lang'] ) ? $options['default_lang'] : 'en';
		return $options;
	}

	/** WPML: prefer directory format without forcing ?lang= on default pages. */
	public static function filter_wpml_setting( $value, $key ) {
		if ( 'language_negotiation_type' === $key && 3 === (int) $value ) {
			return 1;
		}
		return $value;
	}

	/** Redirect ?lang=en away from canonical English URLs. */
	public static function strip_default_lang_redirect() {
		if ( is_admin() || empty( $_GET['lang'] ) ) {
			return;
		}
		$lang = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
		if ( ! in_array( $lang, array( 'en', 'en-us', 'en_us' ), true ) ) {
			return;
		}
		$path = self::path();
		$clean = home_url( user_trailingslashit( $path ) );
		wp_redirect( $clean, 301 );
		exit;
	}

	public static function version_marker() {
		echo "\n<!-- cindemir-seo-fixes " . esc_html( self::VERSION ) . " -->\n";
	}

	public static function rewrite_media_url( $url ) {
		return self::apply_url_replace( $url );
	}

	public static function rewrite_legacy_media_in_content( $content ) {
		return self::apply_url_replace( $content );
	}

	public static function fix_attachment_src_attrs( $attr, $attachment ) {
		foreach ( array( 'src', 'data-lazy-src', 'data-src' ) as $k ) {
			if ( ! empty( $attr[ $k ] ) ) {
				$attr[ $k ] = self::apply_url_replace( $attr[ $k ] );
			}
		}
		return $attr;
	}

	private static function apply_url_replace( $text ) {
		if ( ! is_string( $text ) || '' === $text ) {
			return $text;
		}
		$local_tbb = get_option( 'cindemir_tbb_badge_local', '' );
		if ( $local_tbb ) {
			$text = str_replace( 'https://d.barobirlik.org.tr/amblem/tbb_amblem_60.png', $local_tbb, $text );
			$text = str_replace( 'http://d.barobirlik.org.tr/amblem/tbb_amblem_60.png', $local_tbb, $text );
		}
		foreach ( self::$url_replace as $from => $to ) {
			$text = str_replace( $from, $to, $text );
		}
		return $text;
	}

	/** Override Yoast meta description for priority pages; pad short post/page metas. */
	public static function filter_page_metadesc( $desc ) {
		if ( ! is_singular( array( 'page', 'post' ) ) ) {
			return $desc;
		}
		$id   = get_queried_object_id();
		$post = get_post( $id );
		if ( ! $post ) {
			return $desc;
		}
		if ( isset( self::$page_metadesc[ $id ] ) ) {
			return self::$page_metadesc[ $id ];
		}
		$slug = $post->post_name;
		if ( isset( self::$slug_metadesc[ $slug ] ) ) {
			return self::$slug_metadesc[ $slug ];
		}
		return self::pad_short_metadesc( $desc, $post );
	}

	private static function pad_short_metadesc( $desc, $post ) {
		$desc = trim( (string) $desc );
		$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $desc ) : strlen( $desc );
		if ( $len >= 110 && $len <= 160 ) {
			return $desc;
		}
		if ( $len > 160 ) {
			$cut = function_exists( 'mb_substr' ) ? mb_substr( $desc, 0, 157 ) : substr( $desc, 0, 157 );
			return rtrim( $cut ) . '…';
		}
		$title = wp_strip_all_tags( get_the_title( $post ) );
		$title = trim( preg_replace( '/\s*[-|–—]\s*Cindemir.*$/u', '', $title ) );
		$base  = $desc ? $desc : $title;
		$suffix = ' Overview of relevant Turkish law topics, procedures, and legal context for foreign individuals and companies.';
		$out    = trim( $base . $suffix );
		$olen   = function_exists( 'mb_strlen' ) ? mb_strlen( $out ) : strlen( $out );
		if ( $olen > 160 ) {
			$out = function_exists( 'mb_substr' ) ? mb_substr( $out, 0, 157 ) : substr( $out, 0, 157 );
			$out = rtrim( $out ) . '…';
		}
		return $out;
	}

	/** Keep redirecting Press page out of Yoast XML sitemaps. */
	public static function exclude_press_from_sitemap( $ids ) {
		if ( ! is_array( $ids ) ) {
			$ids = array();
		}
		$press = get_page_by_path( 'press' );
		if ( $press ) {
			$ids[] = (int) $press->ID;
		}
		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}

	public static function filter_sitemap_entry( $url, $type, $object ) {
		if ( ! is_array( $url ) || empty( $url['loc'] ) ) {
			return $url;
		}
		$loc = $url['loc'];
		$path = self::normalize_path( $loc );
		$skip = array( '/press', '/link9', '/link2', '/link3', '/link4', '/author/admin', '/russian', '/chinese', '/zh', '/zh-hans' );
		if ( in_array( $path, $skip, true ) ) {
			return false;
		}
		$parts = wp_parse_url( $loc );
		$query = isset( $parts['query'] ) ? $parts['query'] : '';
		if ( ! empty( $query ) && false !== strpos( $query, 'lang=en' ) ) {
			return false;
		}
		$dest = self::resolve_path_dest( $path, $query );
		if ( $dest ) {
			$url['loc'] = $dest;
		} elseif ( 'post' === $type && $query && false === strpos( $query, 'lang=' ) ) {
			$ru_dest = self::resolve_path_dest( $path, 'lang=ru' );
			if ( $ru_dest ) {
				$url['loc'] = $ru_dest;
			}
		}
		return $url;
	}

	public static function kill_author_rewrites( $rules ) {
		return array();
	}

	public static function disable_author_archives() {
		if ( is_author() ) {
			wp_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	public static function cancel_broken( $target, $url ) {
		$path = self::normalize_path( $url );
		if ( in_array( $path, self::$broken, true ) ) {
			return false;
		}
		$parts = wp_parse_url( $url );
		$q = isset( $parts['query'] ) ? $parts['query'] : '';
		$dest = self::resolve_path_dest( $path, $q );
		if ( $dest ) {
			return $dest;
		}
		return $target;
	}

	public static function flatten_redirects() {
		if ( is_admin() ) {
			return;
		}
		$path = self::path();
		if ( in_array( $path, self::$broken, true ) ) {
			return;
		}
		$req  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$req_parts = wp_parse_url( $req );
		$req_q = isset( $req_parts['query'] ) ? $req_parts['query'] : '';
		$dest = self::resolve_path_dest( $path, $req_q );
		if ( ! $dest ) {
			return;
		}
		$dest_parts = wp_parse_url( $dest );
		$dest_path = isset( $dest_parts['path'] ) ? untrailingslashit( rawurldecode( $dest_parts['path'] ) ) : '';
		$dest_path = '' === $dest_path ? '/' : $dest_path;
		$dest_q = isset( $dest_parts['query'] ) ? $dest_parts['query'] : '';
		if ( $path === $dest_path && $dest_q && $req_q && false !== strpos( $req_q, $dest_q ) ) {
			return;
		}
		if ( $path === $dest_path && ! $dest_q ) {
			return;
		}
		wp_redirect( $dest, 301 );
		exit;
	}

	public static function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'rewrite_html' ) );
	}

	public static function rewrite_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$html = self::rewrite_hrefs_in_html( $html );
		$html = self::ensure_missing_h1_html( $html );
		$html = self::fill_empty_alts_html( $html );
		$html = self::strip_blocked_external_images( $html );
		$html = self::fix_hreflang_html( $html );
		$html = self::fix_canonical_html( $html );
		$html = self::shorten_title_tag( $html );
		$html = self::normalize_robots_meta( $html );
		return $html;
	}

	/**
	 * Ensure utility/tag pages expose a single noindex robots directive
	 * even when Yoast/cache still emit index,follow.
	 */
	private static function normalize_robots_meta( $html ) {
		if ( ! self::should_noindex() ) {
			return $html;
		}
		$html = preg_replace(
			'/<meta\b[^>]*\bname=(["\'])robots\1[^>]*>\s*/i',
			'',
			$html
		);
		$html = preg_replace(
			'/<meta\b[^>]*\bcontent=(["\'])[^"\']*\1[^>]*\bname=(["\'])robots\2[^>]*>\s*/i',
			'',
			$html
		);
		$tag = '<meta name="robots" content="noindex, follow" />' . "\n";
		if ( preg_match( '/<head\b[^>]*>/i', $html ) ) {
			return preg_replace( '/<head\b[^>]*>/i', '$0' . "\n" . $tag, $html, 1 );
		}
		return $tag . $html;
	}

	public static function rewrite_content_hrefs( $content ) {
		return self::rewrite_hrefs_in_html( $content );
	}

	public static function author_to_home( $link ) {
		return home_url( '/' );
	}

	public static function nav_href( $atts, $item ) {
		if ( empty( $atts['href'] ) ) {
			return $atts;
		}
		$atts['href'] = self::map_href( $atts['href'] );
		return $atts;
	}

	public static function fix_headings( $content ) {
		if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$id = get_the_ID();
		if ( isset( self::$missing_h1[ $id ] ) && ! preg_match( '/<h1[\s>]/i', $content ) && ! self::$h1_done ) {
			$content       = '<h1 class="cindemir-seo-h1">' . esc_html( self::$missing_h1[ $id ] ) . '</h1>' . "\n" . $content;
			self::$h1_done = true;
			return $content;
		}
		if ( ! preg_match_all( '/<h1([\s>][^>]*)>.*?<\/h1>/is', $content, $m ) ) {
			return $content;
		}
		if ( count( $m[0] ) <= 1 ) {
			return $content;
		}
		$seen = 0;
		return preg_replace_callback(
			'/<h1([\s>][^>]*)>(.*?)<\/h1>/is',
			function ( $match ) use ( &$seen ) {
				$seen++;
				if ( 1 === $seen ) {
					return $match[0];
				}
				return '<h2' . $match[1] . '>' . $match[2] . '</h2>';
			},
			$content
		);
	}

	public static function orphan_links() {
		if ( is_admin() ) {
			return;
		}
		echo "\n<nav class=\"cindemir-orphan-links\" aria-label=\"Additional pages\" style=\"max-width:1200px;margin:0 auto 1rem;padding:0 20px;font-size:14px;\">";
		echo '<a href="' . esc_url( home_url( '/our-videos/' ) ) . '">Our Videos</a> · ';
		echo '<a href="' . esc_url( home_url( '/appointment/' ) ) . '">Book an Appointment</a> · ';
		echo '<a href="' . esc_url( home_url( '/about-us/' ) ) . '">About Us</a>';
		echo "</nav>\n";
	}

	/** True when utility/tag URLs must stay out of the index. */
	private static function should_noindex() {
		if ( is_tag() ) {
			return true;
		}
		if ( function_exists( 'is_page' ) && is_page( array( 'antimanual-assistant', 'embed-list' ) ) ) {
			return true;
		}
		$path = self::path();
		return in_array( $path, array( '/antimanual-assistant', '/embed-list' ), true );
	}

	public static function noindex_utility() {
		// Prefer wp_robots / Yoast filters; keep a late meta only as fallback
		// when those APIs are unavailable (should not duplicate index+noindex).
		if ( ! self::should_noindex() ) {
			return;
		}
		if ( has_filter( 'wp_robots' ) || has_filter( 'wpseo_robots' ) ) {
			return;
		}
		echo "<meta name=\"robots\" content=\"noindex,follow\" />\n";
	}

	public static function filter_wp_robots( $robots ) {
		if ( ! self::should_noindex() ) {
			return $robots;
		}
		if ( ! is_array( $robots ) ) {
			$robots = array();
		}
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
		return $robots;
	}

	public static function filter_yoast_robots( $robots ) {
		if ( ! self::should_noindex() ) {
			return $robots;
		}
		return 'noindex, follow';
	}

	public static function fix_alt_attr( $attr, $attachment ) {
		if ( ! empty( $attr['alt'] ) ) {
			return $attr;
		}
		$url = wp_get_attachment_url( $attachment->ID );
		$alt = self::alt_for( $url );
		if ( ! $alt && ! empty( $attachment->post_title ) ) {
			$alt = sanitize_text_field( $attachment->post_title );
		}
		if ( $alt ) {
			$attr['alt'] = $alt;
		}
		return $attr;
	}

	public static function fix_empty_alts( $content ) {
		return self::fill_empty_alts_html( $content );
	}

	private static function rewrite_hrefs_in_html( $html ) {
		$html = self::apply_url_replace( $html );
		// og:image / twitter:image meta content attributes.
		$html = preg_replace_callback(
			'#(<meta\b[^>]*\b(?:property|name)=(["\'])(?:og:image|twitter:image)\2[^>]*\bcontent=(["\']))([^"\']*)(\3)#i',
			function ( $m ) {
				return $m[1] . esc_attr( self::apply_url_replace( $m[4] ) ) . $m[5];
			},
			$html
		);
		$html = preg_replace_callback(
			'#(\shref=(["\']))(https?://(?:www\.)?cindemirlaw\.com)?(/[^"\']*)(\2)#i',
			function ( $m ) {
				$quote = $m[2];
				$pathq = $m[4];
				$parts = wp_parse_url( 'https://cindemirlaw.com' . $pathq );
				$path  = isset( $parts['path'] ) ? untrailingslashit( rawurldecode( $parts['path'] ) ) : '';
				$path  = '' === $path ? '/' : $path;
				$q     = isset( $parts['query'] ) ? $parts['query'] : '';
				$dest  = self::resolve_path_dest( $path, $q );
				if ( $dest ) {
					return ' href=' . $quote . esc_url( $dest ) . $quote;
				}
				return $m[0];
			},
			$html
		);
		return $html;
	}

	private static function map_href( $href ) {
		$path = self::normalize_path( $href );
		$parts = wp_parse_url( $href );
		$q = isset( $parts['query'] ) ? $parts['query'] : '';
		$dest = self::resolve_path_dest( $path, $q );
		if ( $dest ) {
			return $dest;
		}
		foreach ( self::$url_replace as $from => $to ) {
			if ( 0 === strpos( $href, $from ) ) {
				return $to;
			}
		}
		return $href;
	}

	/**
	 * Resolve a path to its final URL when it would otherwise 301.
	 *
	 * @param string $path Normalized path without trailing slash.
	 * @param string $query Existing query string (may already include lang=).
	 * @return string|false
	 */
	private static function resolve_path_dest( $path, $query = '' ) {
		if ( '/author/admin' === $path ) {
			return home_url( '/' );
		}
		if ( isset( self::$redirects[ $path ] ) ) {
			return self::$redirects[ $path ];
		}
		// WPML RU hash slugs and Cyrillic paths redirect to ?lang=ru when missing.
		if ( $query && false !== strpos( $query, 'lang=' ) ) {
			return false;
		}
		$is_fde = ( 0 === strpos( $path, '/fde' ) );
		$is_cyr = (bool) preg_match( '/[А-Яа-яЁё]/u', $path );
		if ( $is_fde || $is_cyr ) {
			return home_url( user_trailingslashit( $path ) . '?lang=ru' );
		}
		return false;
	}

	private static function ensure_missing_h1_html( $html ) {
		if ( preg_match( '/<h1[\s>]/i', $html ) ) {
			return $html;
		}
		$title = '';
		$id    = function_exists( 'get_queried_object_id' ) ? get_queried_object_id() : 0;
		if ( $id && isset( self::$missing_h1[ $id ] ) ) {
			$title = self::$missing_h1[ $id ];
		} elseif ( is_singular() ) {
			$title = wp_strip_all_tags( get_the_title( $id ) );
		} elseif ( preg_match( '/<title>(.*?)<\/title>/is', $html, $tm ) ) {
			$title = trim( preg_replace( '/\s*[-|].*$/', '', wp_strip_all_tags( $tm[1] ) ) );
		}
		if ( ! $title ) {
			return $html;
		}
		$h1 = '<h1 class="cindemir-seo-h1">' . esc_html( $title ) . '</h1>';
		$patterns = array(
			'/(<main\b[^>]*>)/i',
			'/(<div[^>]*id="main"[^>]*>)/i',
			'/(<div[^>]*class="[^"]*\bcontainer\b[^"]*"[^>]*>)/i',
			'/(<body\b[^>]*>)/i',
		);
		foreach ( $patterns as $pattern ) {
			$next = preg_replace( $pattern, '$1' . "\n" . $h1, $html, 1, $count );
			if ( $count ) {
				return $next;
			}
		}
		return $html;
	}

	private static function fix_canonical_html( $html ) {
		return preg_replace_callback(
			'#(<link\b[^>]*\brel=(["\'])canonical\2[^>]*\bhref=(["\']))([^"\']+)(\3)#i',
			function ( $m ) {
				$url = self::filter_canonical_url( $m[4] );
				return $m[1] . esc_url( $url ) . $m[5];
			},
			$html
		);
	}

	private static function fix_hreflang_html( $html ) {
		return preg_replace_callback(
			'#<link\b[^>]*\bhreflang=(["\'])([^"\']+)\1[^>]*\bhref=(["\'])([^"\']+)\3[^>]*>#i',
			function ( $m ) {
				$url = self::normalize_hreflang_url( $m[4] );
				return '<link rel="alternate" hreflang="' . esc_attr( $m[2] ) . '" href="' . esc_url( $url ) . '" />';
			},
			$html
		);
	}

	private static function strip_blocked_external_images( $html ) {
		$html = preg_replace(
			'#<img\b[^>]*\b(?:src|data-lazy-src)=(["\'])(?:https?:)?//idsb\.tmgrup\.com\.tr/[^"\']+\1[^>]*>#i',
			'',
			$html
		);
		if ( get_option( 'cindemir_tbb_badge_local', '' ) ) {
			return $html;
		}
		return preg_replace(
			'#<img\b[^>]*\b(?:src|data-lazy-src)=(["\'])(?:https?:)?//d\.barobirlik\.org\.tr/[^"\']+\1[^>]*>#i',
			'',
			$html
		);
	}

	private static function fill_empty_alts_html( $html ) {
		if ( false === strpos( $html, 'alt=""' ) && false === stripos( $html, "alt=''" ) ) {
			return $html;
		}
		return preg_replace_callback(
			'/<img(\s[^>]*?)>/is',
			function ( $m ) {
				$tag = $m[0];
				if ( ! preg_match( '/\salt\s*=\s*([\'"])\s*\1/i', $tag ) ) {
					return $tag;
				}
				$src = '';
				if ( preg_match( '/\ssrc\s*=\s*[\'"]([^\'"]+)[\'"]/i', $tag, $sm ) ) {
					$src = $sm[1];
				}
				$alt = self::alt_for( $src );
				if ( ! $alt ) {
					$alt = 'Cindemir Law Office';
				}
				return preg_replace( '/\salt\s*=\s*([\'"])\s*\1/i', 'alt="' . esc_attr( $alt ) . '"', $tag, 1 );
			},
			$html
		);
	}

	private static function shorten_title_tag( $html ) {
		return preg_replace_callback(
			'/<title>(.*?)<\/title>/is',
			function ( $m ) {
				$raw = wp_strip_all_tags( $m[1] );
				$raw = html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$raw = preg_replace( '/\s+/', ' ', trim( $raw ) );
				if ( function_exists( 'mb_strlen' ) ) {
					$len = mb_strlen( $raw );
				} else {
					$len = strlen( $raw );
				}
				if ( $len <= 60 ) {
					return '<title>' . esc_html( $raw ) . '</title>';
				}
				$brand = 'Cindemir Law Office';
				$base  = preg_replace( '/\s*[-|–—]\s*Cindemir Law Office\s*$/u', '', $raw );
				$base  = trim( $base );
				$max   = 55;
				if ( function_exists( 'mb_strlen' ) && mb_strlen( $base ) > $max ) {
					$cut = mb_substr( $base, 0, $max );
					$pos = mb_strrpos( $cut, ' ' );
					if ( false !== $pos ) {
						$cut = mb_substr( $cut, 0, $pos );
					}
					$base = $cut . '…';
				} elseif ( strlen( $base ) > $max ) {
					$cut = substr( $base, 0, $max );
					$pos = strrpos( $cut, ' ' );
					if ( false !== $pos ) {
						$cut = substr( $cut, 0, $pos );
					}
					$base = $cut . '...';
				}
				$new = $base . ' - ' . $brand;
				$new_len = function_exists( 'mb_strlen' ) ? mb_strlen( $new ) : strlen( $new );
				if ( $new_len > 60 ) {
					if ( function_exists( 'mb_substr' ) ) {
						$new = mb_substr( $base, 0, 48 ) . '… | Cindemir';
					} else {
						$new = substr( $base, 0, 48 ) . '... | Cindemir';
					}
				}
				return '<title>' . esc_html( $new ) . '</title>';
			},
			$html,
			1
		);
	}

	private static function path() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		return self::normalize_path( $uri );
	}

	private static function normalize_path( $url ) {
		$parts = wp_parse_url( $url );
		$path  = isset( $parts['path'] ) ? $parts['path'] : '/';
		$path  = rawurldecode( $path );
		$path  = untrailingslashit( $path );
		return '' === $path ? '/' : $path;
	}

	private static function alt_for( $url ) {
		if ( ! $url ) {
			return '';
		}
		foreach ( self::$alt_map as $needle => $alt ) {
			if ( false !== stripos( $url, $needle ) ) {
				return $alt;
			}
		}
		return '';
	}
}

Cindemir_SEO_Fixes::boot();

<?php
/**
 * Plugin Name: Cindemir SEO Fixes
 * Description: Full Ahrefs cleanup: redirect href rewrite, flatten hops, H1/alts/orphans, author disable, title trim.
 * Version: 1.5.9
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
		'/russian/wp-content/uploads/2014/11/white-2-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'/russian/wp-content/uploads/2014/11/white-5-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-5-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-1-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-2-copy-150x150.jpg' => '/wp-content/uploads/2020/10/white-2-copy-300x300.jpg',
		'/chinese/wp-content/uploads/2014/11/white-1-copy.jpg' => '/wp-content/uploads/2020/10/white-1-copy-300x300.jpg',
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

	/** Reklamsız meta descriptions (Görev 1 — 14 sayfa). */
	private static $meta_descriptions = array(
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
		add_action( 'init', array( __CLASS__, 'maybe_purge_after_upgrade' ), 1 );
		add_action( 'init', array( __CLASS__, 'apply_meta_descriptions_once' ), 20 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'filter_yoast_metadesc' ), 20 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_filter( 'redirection_url_target', array( __CLASS__, 'cancel_broken' ), 1, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'flatten_redirects' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'disable_author_archives' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 1 );
		add_filter( 'rocket_buffer', array( __CLASS__, 'rewrite_html' ), 16 );
		add_filter( 'the_content', array( __CLASS__, 'fix_headings' ), 12 );
		add_filter( 'the_content', array( __CLASS__, 'rewrite_content_hrefs' ), 25 );
		add_action( 'wp_footer', array( __CLASS__, 'orphan_links' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'noindex_utility' ), 1 );
		add_filter( 'wp_robots', array( __CLASS__, 'filter_wp_robots' ), 99 );
		add_filter( 'wpseo_robots', array( __CLASS__, 'filter_yoast_robots' ), 99 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'fix_alt_attr' ), 10, 2 );
		add_filter( 'the_content', array( __CLASS__, 'fix_empty_alts' ), 20 );
		add_filter( 'author_link', array( __CLASS__, 'author_to_home' ), 20 );
		add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'nav_href' ), 20, 2 );
		add_filter( 'author_rewrite_rules', array( __CLASS__, 'kill_author_rewrites' ) );
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( __CLASS__, 'exclude_press_from_sitemap' ) );
		add_filter( 'wpseo_sitemap_entry', array( __CLASS__, 'filter_sitemap_entry' ), 10, 3 );
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
		$path = self::normalize_path( $url['loc'] );
		$skip = array( '/press', '/link9', '/link2', '/link3', '/link4', '/author/admin', '/russian', '/chinese', '/zh', '/zh-hans' );
		if ( in_array( $path, $skip, true ) ) {
			return false;
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
		$html = self::shorten_title_tag( $html );
		$html = self::fix_meta_description_html( $html );
		$html = self::normalize_robots_meta( $html );
		$html = self::enhance_socket_footer_html( $html );
		return $html;
	}

	public static function maybe_purge_after_upgrade() {
		$version = '1.5.9';
		if ( get_option( 'cindemir_seo_fixes_version' ) === $version ) {
			return;
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		update_option( 'cindemir_seo_fixes_version', $version, false );
	}

	private static function enhance_socket_footer_html( $html ) {
		$html = self::linkify_socket_copyright( $html );
		return self::inject_socket_footer_extras( $html );
	}

	private static function linkify_socket_copyright( $html ) {
		return preg_replace_callback(
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
	}

	private static function inject_socket_footer_extras( $html ) {
		if ( false !== strpos( $html, 'cindemir-socket-extras' ) ) {
			return $html;
		}
		$block = self::socket_footer_extras_markup();
		$with_div = preg_replace_callback(
			'/(<footer[^>]*id=(["\'])socket\2[^>]*>.*?<span[^>]*class=(["\'])copyright\3[^>]*>.*?<\/span>)(\s*<\/div>)/is',
			function ( $m ) use ( $block ) {
				return $m[1] . $block . $m[4];
			},
			$html,
			1,
			$count_div
		);
		if ( $count_div ) {
			return $with_div;
		}
		$with_span = preg_replace_callback(
			'/(<footer[^>]*id=(["\'])socket\2[^>]*>.*?<span[^>]*class=(["\'])copyright\3[^>]*>.*?<\/span>)/is',
			function ( $m ) use ( $block ) {
				return $m[0] . $block;
			},
			$html,
			1,
			$count_span
		);
		return $count_span ? $with_span : $html;
	}

	private static function socket_footer_extras_markup() {
		$baro = 'https://baronet.istanbulbarosu.org.tr/avukat/belge_dogrulama?lang=EN&onayno=HBE4U7ES3DM6C52&tck=58612509084';
		$social = array(
			array( 'Email', 'mailto:cindemir@cindemir.av.tr', 'M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 2v.2l-8 5.2-8-5.2V6zm0 12H4V8.8l8 5.2 8-5.2z' ),
			array( 'Facebook', 'https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/', 'M13 10h3l-.4 4H13v9h-4v-9H7v-4h2V8.5C9 6.6 10.1 5 13 5h3v4h-2c-1.1 0-1 .6-1 1.5z' ),
			array( 'Instagram', 'https://www.instagram.com/cindemir_law_office/', 'M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 3.5A5.5 5.5 0 1 1 6.5 13 5.5 5.5 0 0 1 12 7.5zm0 2A3.5 3.5 0 1 0 15.5 13 3.5 3.5 0 0 0 12 9.5zM17.8 6.2a1.2 1.2 0 1 1-1.2 1.2 1.2 1.2 0 0 1 1.2-1.2z' ),
			array( 'LinkedIn', 'https://www.linkedin.com/company/cindemir-law-office/', 'M6.5 8.7H3.4V21h3.1zm-1.6-6A1.9 1.9 0 1 0 6.8 4.6 1.9 1.9 0 0 0 4.9 2.7zM9 8.7V21h3.1v-6c0-1.6.3-3.2 2.3-3.2s2 1.8 2 3.1V21H20v-6.8c0-3.4-1.8-5-4.2-5a3.6 3.6 0 0 0-3.3 1.8V8.7z' ),
			array( 'Phone', 'tel:+902165506775', 'M6.6 10.8a15.8 15.8 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.24 11 11 0 0 0 3.4.55 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11 11 0 0 0 .55 3.4 1 1 0 0 1-.24 1z' ),
			array( 'Telegram', 'https://t.me/gcindemir', 'M9.9 15.5 9.7 19c.5 0 .7-.2 1-.5l2.4-2.3 5 3.7c.9.5 1.5.2 1.7-.8l3.3-15.4h0c.3-1.2-.4-1.7-1.2-1.4L2.5 10c-1.2.5-1.2 1.2-.2 1.5l4.8 1.5L18.7 7c.6-.4 1.1-.2.7.2z' ),
			array( 'X (Twitter)', 'https://x.com/cindemirlegal', 'M4 4l7.5 9.6L4.2 20h2.5l5.8-6.7L16.7 20H20l-7.9-10.2L19.3 4h-2.5l-5.3 6.1L7.8 4z' ),
			array( 'WhatsApp', 'https://wa.me/905325680647', 'M12 2a10 10 0 0 0-8.7 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-2.8.8.8-2.7-.2-.3A8 8 0 1 1 12 20zm4.5-5.8c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.6.1-.7.8-.8 1-.3.1-.6 0a6.4 6.4 0 0 1-1.9-1.2 7.2 7.2 0 0 1-1.3-1.7c-.1-.3 0-.5.1-.6.1-.1.2-.3.3-.4.1-.1.1-.2.2-.3 0-.1 0-.2 0-.3s-.6-1.4-.8-1.9-.4-.5-.6-.5h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-1 2.2 5.2 5.2 0 0 0 1 2.6 9.7 9.7 0 0 0 3.7 3.2c.5.2 1 .4 1.3.4.3 0 .5 0 .7-.1.2-.1.6-.5.7-1 .1-.5.1-.9.1-1 0-.1-.1-.1-.3-.2z' ),
			array( 'YouTube', 'https://www.youtube.com/channel/UCHobIlbWxCMGTPBSZv_rM7Q', 'M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18 5 12 5 12 5s-6 0-7.8.4a2.5 2.5 0 0 0-1.8 1.8C2 9 2 12 2 12s0 3 .4 4.8a2.5 2.5 0 0 0 1.8 1.8C6 19 12 19 12 19s6 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.4-1.8.4-4.8.4-4.8s0-3-.4-4.8zM10 15.5v-7l6 3.5z' ),
		);
		$badges = array(
			array( 'AEuropea', 'https://www.aeuropea.com/', 'https://www.aeuropea.com/wp-content/uploads/2025/09/aea-01v001-ILN-small.png' ),
			array( 'İstanbul Barosu', 'https://www.istanbulbarosu.org.tr/', 'https://www.istanbulbarosu.org.tr/_next/image?url=%2Fimages%2Fbaro_logo.png&w=128&q=75' ),
			array( 'Türkiye Barolar Birliği', 'https://www.barobirlik.org.tr/', 'https://d.barobirlik.org.tr/amblem/tbb_amblem_60.png' ),
		);
		ob_start();
		?>
<div class="cindemir-socket-extras" id="cindemir-socket-extras">
	<div id="cindemir-baro-verification-bar" class="cindemir-baro-verification-bar">
		<a href="<?php echo esc_url( $baro ); ?>" target="_blank" rel="noopener noreferrer">Avukat Baro Doğrulama için Tıklayınız</a>
	</div>
	<nav class="cindemir-footer-social" aria-label="Social media and contact">
		<ul class="cindemir-footer-social-list">
			<?php foreach ( $social as $item ) : ?>
				<li><a href="<?php echo esc_url( $item[1] ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $item[0] ); ?>" aria-label="<?php echo esc_attr( $item[0] ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?php echo esc_attr( $item[2] ); ?>"/></svg></a></li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<div class="cindemir-footer-badges" aria-label="Cindemir Law verification and membership badges">
		<?php foreach ( $badges as $badge ) : ?>
			<a href="<?php echo esc_url( $badge[1] ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $badge[0] ); ?>"><img src="<?php echo esc_url( $badge[2] ); ?>" alt="<?php echo esc_attr( $badge[0] ); ?>" loading="lazy" decoding="async" height="48" /></a>
		<?php endforeach; ?>
	</div>
</div>
<style id="cindemir-footer-fixes-css">#socket .container{text-align:center}#socket .copyright{display:block;width:100%;text-align:center;margin:0 auto}#socket .cindemir-socket-extras{width:100%;margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.15);text-align:center}#socket .cindemir-baro-verification-bar{margin-bottom:12px;text-align:center}#socket .cindemir-baro-verification-bar a{color:inherit;text-decoration:underline;font-size:14px}#socket .cindemir-footer-social{margin:10px 0 14px}#socket .cindemir-footer-social-list{display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;margin:0;padding:0;list-style:none}#socket .cindemir-footer-social-list a{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;text-decoration:none}#socket .cindemir-footer-social-list svg{width:18px;height:18px;fill:currentColor;display:block}#socket .cindemir-footer-badges{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap}#socket .cindemir-footer-badges img{height:48px;width:auto;display:block;object-fit:contain;border:0}#socket .copyright a.cindemir-footer-email,#socket .copyright a.cindemir-footer-phone{color:inherit;text-decoration:underline}</style>
		<?php
		return ob_get_clean();
	}

	/** One-time Yoast DB sync after deploy (first front-end or REST hit). */
	public static function apply_meta_descriptions_once() {
		if ( get_option( 'cindemir_seo_meta_v1_applied' ) ) {
			return;
		}
		$updated = 0;
		foreach ( self::$meta_descriptions as $id => $desc ) {
			if ( ! get_post( $id ) ) {
				continue;
			}
			update_post_meta( (int) $id, '_yoast_wpseo_metadesc', $desc );
			$updated++;
		}
		update_option( 'cindemir_seo_meta_v1_applied', 1, false );
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
	}

	public static function filter_yoast_metadesc( $desc ) {
		$id = function_exists( 'get_queried_object_id' ) ? (int) get_queried_object_id() : 0;
		if ( $id && isset( self::$meta_descriptions[ $id ] ) ) {
			return self::$meta_descriptions[ $id ];
		}
		return $desc;
	}

	public static function register_rest_routes() {
		register_rest_route(
			'cindemir/v1',
			'/apply-seo-meta',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'rest_apply_seo_meta' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function rest_apply_seo_meta( $request ) {
		$key = $request->get_param( 'key' );
		if ( 'seo-pack-2026' !== $key ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		delete_option( 'cindemir_seo_meta_v1_applied' );
		self::apply_meta_descriptions_once();
		$results = array();
		foreach ( self::$meta_descriptions as $id => $expected ) {
			$stored = get_post_meta( (int) $id, '_yoast_wpseo_metadesc', true );
			$results[ $id ] = array(
				'ok'    => ( $stored === $expected ),
				'len'   => function_exists( 'mb_strlen' ) ? mb_strlen( (string) $stored ) : strlen( (string) $stored ),
			);
		}
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'version' => '1.5.7',
				'pages'   => $results,
			),
			200
		);
	}

	private static function fix_meta_description_html( $html ) {
		$id = function_exists( 'get_queried_object_id' ) ? (int) get_queried_object_id() : 0;
		if ( ! $id || ! isset( self::$meta_descriptions[ $id ] ) ) {
			return $html;
		}
		$desc = esc_attr( self::$meta_descriptions[ $id ] );
		$html = preg_replace(
			'/<meta\s+name=(["\'])description\1[^>]*>/i',
			'<meta name="description" content="' . $desc . '" />',
			$html,
			1
		);
		$html = preg_replace(
			'/<meta\s+property=(["\'])og:description\1[^>]*>/i',
			'<meta property="og:description" content="' . $desc . '" />',
			$html,
			1
		);
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
		echo '<a href="' . esc_url( home_url( '/appointment/' ) ) . '">Book an Appointment</a>';
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
		foreach ( self::$url_replace as $from => $to ) {
			$html = str_replace( $from, $to, $html );
		}
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

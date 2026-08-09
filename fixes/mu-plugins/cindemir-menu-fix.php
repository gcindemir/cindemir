<?php
/**
 * Plugin Name: Cindemir Menu Fix
 * Description: Fixes main-nav language switcher hrefs on all pages + keeps RU/ZH menus pointing at real translated pages.
 * Version: 1.6.0
 * Author: Cindemir Law Office
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CINDEMIR_MENU_FIX_LOADED' ) ) {
	return;
}
define( 'CINDEMIR_MENU_FIX_LOADED', true );

final class Cindemir_Menu_Fix {

	const PRESS_URL = 'https://cindemir.av.tr/en/we-are-in-news/';
	const VERSION   = '1.6.0';

	/** @var array<string,array{label:string,flag:string}> */
	private static $langs = array(
		'en'      => array(
			'label' => 'EN',
			'flag'  => 'https://cindemirlaw.com/wp-content/plugins/sitepress-multilingual-cms/res/flags/en.png',
		),
		'zh-hans' => array(
			'label' => '中文',
			'flag'  => 'https://cindemirlaw.com/wp-content/uploads/flags/china-flag-xs.png',
		),
		'ru'      => array(
			'label' => 'RU',
			'flag'  => 'https://cindemirlaw.com/wp-content/plugins/sitepress-multilingual-cms/res/flags/ru.png',
		),
	);

	public static function boot() {
		add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'fix_menu_objects' ), 200, 2 );
		add_filter( 'wp_nav_menu', array( __CLASS__, 'fix_menu_html' ), 1000, 2 );
		// Start BEFORE cindemir-seo-fixes (-999) so this buffer is outer and
		// rewrites language-switcher hrefs AFTER seo stamp/lang passes.
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), -1000 );
		add_action( 'init', array( __CLASS__, 'maybe_repair_menus' ), 30 );
		// Very early head — before Rocket/Debloat can drop footer scripts.
		add_action( 'wp_head', array( __CLASS__, 'print_burger_css' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'print_burger_js' ), 2 );
		add_action( 'wp_footer', array( __CLASS__, 'print_burger_js' ), 1 );
		add_action( 'send_headers', array( __CLASS__, 'no_store_html_headers' ), 0 );
		add_filter( 'rocket_delay_js_exclusions', array( __CLASS__, 'rocket_exclude' ) );
		add_filter( 'rocket_exclude_defer_js', array( __CLASS__, 'rocket_exclude' ) );
		add_filter( 'rocket_exclude_js', array( __CLASS__, 'rocket_exclude' ) );
		add_filter( 'debloat_delay_js_exclusions', array( __CLASS__, 'rocket_exclude' ) );
		// Ensure final Rocket HTML always contains the fix (Rocket was stripping footer/head JS on EN).
		add_filter( 'rocket_buffer', array( __CLASS__, 'inject_into_rocket_buffer' ), 1000 );
	}

	/**
	 * Re-inject burger CSS/JS into the final HTML Rocket writes to disk.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	public static function inject_into_rocket_buffer( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		// Always ensure CSS/JS strings are present (do not use print_* static guards —
		// those may already have fired during the request while Rocket later stripped them).
		if ( false === strpos( $html, 'cindemir-menu-fix-burger' ) ) {
			$html = preg_replace( '/<\/head>/i', self::burger_css_tag() . '</head>', $html, 1 );
		}
		if ( false === strpos( $html, 'cindemir-menu-fix-burger-js' ) ) {
			$html = preg_replace( '/<\/head>/i', self::burger_js_tag() . '</head>', $html, 1 );
		}
		// Visible cache-bust marker for view-source checks.
		if ( false === strpos( $html, 'cindemir-menu-fix-v' . self::VERSION ) ) {
			$html = preg_replace(
				'/<body\b[^>]*>/i',
				'$0<!--cindemir-menu-fix-v' . self::VERSION . '-->',
				$html,
				1
			);
		}
		return $html;
	}

	/** @return string */
	private static function burger_css_tag() {
		return '<style id="cindemir-menu-fix-burger" data-cfasync="false">'
			/* Keep hamburger tappable above logo/WhatsApp chrome. */
			. '#top #header .av-burger-menu-main,'
			. '#top #header .menu-item-avia-special.av-burger-menu-main{'
			. 'position:relative!important;z-index:2147483646!important;pointer-events:auto!important;'
			. 'display:block!important;visibility:visible!important;opacity:1!important}'
			. '#top #header .av-burger-menu-main > a,'
			. '#top #header .av-hamburger,'
			. '#top #header .av-hamburger-box,'
			. '#top #header .av-hamburger-inner{'
			. 'pointer-events:auto!important;cursor:pointer!important}'
			. '@media only screen and (min-width:990px){'
			. '#top #header .av-burger-menu-main,'
			. '#top #header .menu-item-avia-special.av-burger-menu-main,'
			. '#top #avia-menu .av-burger-menu-main,'
			. '#top #wrap_all #header .av-burger-menu-main{'
			. 'display:none!important;visibility:hidden!important;pointer-events:none!important;'
			. 'width:0!important;height:0!important;overflow:hidden!important;margin:0!important;padding:0!important}'
			. '}'
			/* Beat Enfold + header 64px lock: use viewport units, not % of header. */
			. 'html.av-burger-overlay-active #top #header,'
			. 'html.av-burger-overlay-active #top #wrap_all #header,'
			. 'html.av-burger-overlay-active #top #header #header_main,'
			. 'html.av-burger-overlay-active #top #header #header_main .container,'
			. 'html.av-burger-overlay-active #top #header #header_main .inner-container{'
			. 'max-height:none!important;height:auto!important;overflow:visible!important}'
			. '.av-burger-overlay,'
			. 'div.av-burger-overlay,'
			. '#top .av-burger-overlay,'
			. '#top #header .av-burger-overlay,'
			. '#top #wrap_all .av-burger-overlay,'
			. '#header .av-burger-overlay,'
			. 'body > .av-burger-overlay,'
			. 'body .av-burger-overlay{'
			. 'display:none!important;'
			. 'position:fixed!important;'
			. 'top:0!important;left:0!important;right:0!important;bottom:0!important;'
			. 'inset:0!important;'
			. 'width:100vw!important;width:100dvw!important;'
			. 'height:100vh!important;height:100dvh!important;'
			. 'min-width:100vw!important;min-height:100vh!important;min-height:100dvh!important;'
			. 'max-width:none!important;max-height:none!important;'
			. 'overflow:hidden!important;z-index:2147483000!important;'
			. 'transform:none!important;-webkit-transform:none!important;'
			. '}'
			. 'html.av-burger-overlay-active .av-burger-overlay,'
			. 'html.av-burger-overlay-active body > .av-burger-overlay{'
			. 'display:block!important;opacity:1!important;visibility:visible!important}'
			/* Compact floating panel (not full-height drawer) */
			. '.av-burger-overlay-scroll,'
			. '#top .av-burger-overlay-scroll,'
			. '#top #header .av-burger-overlay-scroll,'
			. '#header .av-burger-overlay-scroll,'
			. 'html.av-burger-overlay-active .av-burger-overlay-scroll{'
			. 'position:absolute!important;top:58px!important;right:12px!important;left:auto!important;bottom:auto!important;'
			. 'width:min(228px,78vw)!important;'
			. 'height:auto!important;min-height:0!important;max-height:min(72vh,520px)!important;'
			. 'overflow-x:hidden!important;overflow-y:auto!important;-webkit-overflow-scrolling:touch;'
			. 'z-index:10!important;'
			. 'background:#faf9f6!important;background-color:#faf9f6!important;'
			. 'background-image:none!important;'
			. 'border:1px solid rgba(26,63,63,.10)!important;'
			. 'border-radius:12px!important;'
			. 'box-shadow:0 14px 36px rgba(14,28,28,.16)!important;'
			. 'transform:none!important;-webkit-transform:none!important;'
			. '}'
			. '.av-burger-overlay-scroll::before{content:none!important;display:none!important}'
			. '.av-burger-overlay-bg{position:absolute!important;inset:0!important;width:100%!important;height:100%!important;min-height:100vh!important;z-index:3!important;opacity:.22!important;background:#0e1c1c!important;cursor:pointer!important}'
			. '.av-burger-overlay-inner{min-height:0!important;height:auto!important;display:block!important;position:relative!important;z-index:5!important;'
			. 'padding:0!important;background:transparent!important}'
			/* —— Compact menu list —— */
			. '#av-burger-menu-ul,'
			. '.av-burger-overlay #av-burger-menu-ul,'
			. '.av-burger-overlay ul.av-main-nav{'
			. 'padding:12px 10px 12px!important;height:auto!important;min-height:0!important;'
			. 'display:flex!important;flex-direction:column!important;flex-wrap:nowrap!important;'
			. 'align-items:stretch!important;justify-content:flex-start!important;gap:0!important;'
			. 'width:100%!important;max-width:100%!important;margin:0!important;list-style:none!important;'
			. 'float:none!important;text-align:left!important;box-sizing:border-box!important;'
			. 'background:transparent!important}'
			. '#top #wrap_all #av-burger-menu-ul > li,'
			. '#av-burger-menu-ul > li,'
			. '.av-burger-overlay #av-burger-menu-ul > li{'
			. 'opacity:1!important;top:0!important;left:0!important;right:auto!important;'
			. 'position:relative!important;display:block!important;float:none!important;'
			. 'width:100%!important;max-width:100%!important;flex:0 0 auto!important;'
			. 'transform:none!important;border:0!important;border-bottom:0!important;'
			. 'margin:0!important;padding:0!important;background:transparent!important;'
			. 'list-style:none!important}'
			. '#av-burger-menu-ul > li::before,'
			. '#av-burger-menu-ul > li::after,'
			. '#av-burger-menu-ul > li > a::before,'
			. '#av-burger-menu-ul > li > a::after{'
			. 'content:none!important;display:none!important}'
			. '#top #av-burger-menu-ul > li > a,'
			. '#av-burger-menu-ul > li > a,'
			. '.av-burger-overlay #av-burger-menu-ul > li > a,'
			. 'html.av-burger-overlay-active #top #wrap_all #av-burger-menu-ul > li > a{'
			. 'color:#1a3f3f!important;'
			. 'font-family:Georgia,"Times New Roman",Times,serif!important;'
			. 'font-size:14.5px!important;font-weight:400!important;letter-spacing:.01em!important;'
			. 'line-height:1.2!important;padding:8px 10px!important;'
			. 'display:block!important;width:auto!important;float:none!important;'
			. 'text-align:left!important;text-decoration:none!important;'
			. 'border:0!important;border-radius:6px!important;background:transparent!important;'
			. 'box-shadow:none!important;'
			. 'transition:background .15s ease,color .15s ease}'
			. '#av-burger-menu-ul > li > a:hover,'
			. '#av-burger-menu-ul > li > a:focus,'
			. '#av-burger-menu-ul > li.current-menu-item > a,'
			. '#av-burger-menu-ul > li.current_page_item > a,'
			. '#av-burger-menu-ul > li.current-menu-ancestor > a{'
			. 'background:rgba(31,74,74,.07)!important;color:#123232!important;'
			. 'transform:none}'
			. '#av-burger-menu-ul > li > a .avia-menu-text{'
			. 'font:inherit!important;color:inherit!important;border:0!important;padding:0!important;margin:0!important}'
			. '#av-burger-menu-ul > li.av-burger-menu-main,'
			. '#av-burger-menu-ul > li.menu-item-search-dropdown{display:none!important}'
			. '.av-burger-overlay .sub-menu,'
			. '.av-burger-overlay .avia-menu-fx,'
			. '.av-burger-overlay .av-menu-button,'
			. '.av-burger-overlay .dropdown-toggle,'
			. '.av-burger-overlay .av-btn-nav-submenu,'
			. '#av-burger-menu-ul .av-submenu-indicator,'
			. '#av-burger-menu-ul .avia-bullet,'
			. '#av-burger-menu-ul .avia-menu-subtext{'
			. 'display:none!important}'
			/* Hide raw language LIs — chips live in .cindemir-lang-row only */
			. '#av-burger-menu-ul > li.cindemir-lang-item,'
			. '#av-burger-menu-ul > li[class*="language_"],'
			. '#av-burger-menu-ul > li[class*="wpml-ls-item"]{display:none!important}'
			/* Language chips — quieter */
			. '#av-burger-menu-ul > li.cindemir-lang-row{'
			. 'display:block!important;margin-top:8px!important;padding-top:8px!important;'
			. 'border-top:1px solid rgba(31,74,74,.10)!important;'
			. 'list-style:none!important}'
			. '#av-burger-menu-ul .cindemir-lang-row-inner{'
			. 'display:flex!important;flex-wrap:nowrap!important;gap:4px!important;align-items:center!important;'
			. 'justify-content:flex-start!important;padding:0 2px!important}'
			. '#av-burger-menu-ul .cindemir-lang-row-inner > a,'
			. '#av-burger-menu-ul > li.cindemir-lang-item > a,'
			. '#av-burger-menu-ul > li[class*="language_"] > a,'
			. '#av-burger-menu-ul > li[class*="wpml-ls-item"] > a{'
			. 'display:inline-flex!important;align-items:center!important;gap:4px!important;'
			. 'padding:4px 7px!important;font-size:10px!important;font-weight:600!important;'
			. 'font-family:"Segoe UI",system-ui,-apple-system,sans-serif!important;'
			. 'letter-spacing:.04em!important;text-transform:uppercase!important;'
			. 'color:#1f4a4a!important;background:transparent!important;'
			. 'border:1px solid rgba(31,74,74,.12)!important;border-radius:999px!important;'
			. 'box-shadow:none!important;text-decoration:none!important;transform:none!important;'
			. 'line-height:1!important}'
			. '#av-burger-menu-ul .cindemir-lang-row-inner > a:hover,'
			. '#av-burger-menu-ul .cindemir-lang-row-inner > a:focus{'
			. 'background:rgba(31,74,74,.06)!important;border-color:rgba(31,74,74,.22)!important}'
			. '#av-burger-menu-ul .cindemir-lang-flag,'
			. '#av-burger-menu-ul img.avia_wpml_flag,'
			. '#av-burger-menu-ul .wpml-ls-flag{'
			. 'width:13px!important;height:9px!important;object-fit:cover!important;'
			. 'border-radius:1px!important;display:inline-block!important;flex:0 0 auto!important;'
			. 'opacity:.9!important}'
			/* Soften burger X while open */
			. 'html.av-burger-overlay-active #top .av-hamburger-inner,'
			. 'html.av-burger-overlay-active #top .av-hamburger-inner::before,'
			. 'html.av-burger-overlay-active #top .av-hamburger-inner::after{'
			. 'background-color:#1f4a4a!important}'
			/* Desktop nav: quieter, more refined */
			. '@media only screen and (min-width:990px){'
			. '#top #wrap_all #header .av-main-nav > li > a,'
			. '#top #header .av-main-nav > li > a{'
			. 'font-family:Georgia,"Times New Roman",Times,serif!important;'
			. 'font-size:14px!important;font-weight:400!important;letter-spacing:.03em!important;'
			. 'text-transform:none!important}'
			. '#top #wrap_all #header .av-main-nav > li > a .avia-menu-text,'
			. '#top #header .av-main-nav > li > a .avia-menu-text{border:0!important;font:inherit!important}'
			. '#top #header .avia-menu-fx{height:1px!important;bottom:18px!important;opacity:.85}'
			. '#top #wrap_all #header .av-main-nav > li.cindemir-lang-item > a,'
			. '#top #wrap_all #header .av-main-nav > li[class*="language_"] > a,'
			. '#top #wrap_all #header .av-main-nav > li[class*="wpml-ls-item"] > a{'
			. 'font-family:"Segoe UI",system-ui,sans-serif!important;'
			. 'font-size:11px!important;font-weight:600!important;letter-spacing:.08em!important;'
			. 'opacity:.85}'
			. '}'
			. 'html.av-burger-overlay-active,html.av-burger-overlay-active body{overflow:hidden!important}'
			. 'html.av-burger-overlay-active #top #header,'
			. 'html.av-burger-overlay-active #top #header .av-burger-menu-main{'
			. 'z-index:2147483647!important}'
			. '</style>' . "\n";
	}

	/** @return string */
	private static function burger_js_tag() {
		// Standalone open/close — does not depend on Enfold/Debloat/minified avia JS.
		return '<script id="cindemir-menu-fix-burger-js" data-nowprocket nowprocket data-no-minify="1" data-cfasync="false" data-pagespeed-no-defer>'
			. '(function(){'
			. 'var ROOT=document.documentElement;'
			. 'function isOpen(){return ROOT.classList.contains("av-burger-overlay-active");}'
			. 'function isLangLi(li){'
			. 'if(!li||!li.className)return false;'
			. 'var cls=String(li.className);'
			. 'return cls.indexOf("cindemir-lang-item")>=0||cls.indexOf("language_")>=0||cls.indexOf("wpml-ls-item")>=0;'
			. '}'
			. 'function polishMenu(ul){'
			. 'if(!ul)return;'
			. 'var junk=ul.querySelectorAll(".sub-menu,.avia-menu-fx,.av-menu-button,.dropdown-toggle,.av-btn-nav-submenu,.av-submenu-indicator,.avia-bullet");'
			. 'for(var j=0;j<junk.length;j++){junk[j].parentNode&&junk[j].parentNode.removeChild(junk[j]);}'
			. 'var existing=ul.querySelector(".cindemir-lang-row");'
			. 'var langs=[];'
			. 'var kids=[].slice.call(ul.children);'
			. 'for(var i=0;i<kids.length;i++){if(isLangLi(kids[i]))langs.push(kids[i]);}'
			. 'if(existing){'
			. 'for(var r=0;r<langs.length;r++){langs[r].parentNode&&langs[r].parentNode.removeChild(langs[r]);}'
			. 'return;'
			. '}'
			. 'if(!langs.length)return;'
			. 'var row=document.createElement("li");'
			. 'row.className="menu-item cindemir-lang-row";'
			. 'var inner=document.createElement("div");'
			. 'inner.className="cindemir-lang-row-inner";'
			. 'var seen={};'
			. 'for(var k=0;k<langs.length;k++){'
			. 'var a=langs[k].querySelector("a");'
			. 'if(!a)continue;'
			. 'var key=(a.getAttribute("href")||"")+"|"+(a.textContent||"").replace(/\\s+/g," ").trim();'
			. 'if(seen[key])continue;seen[key]=1;'
			. 'inner.appendChild(a.cloneNode(true));'
			. '}'
			. 'row.appendChild(inner);'
			. 'for(var r2=0;r2<langs.length;r2++){langs[r2].parentNode&&langs[r2].parentNode.removeChild(langs[r2]);}'
			. 'ul.appendChild(row);'
			. '}'
			. 'function cloneNavItems(ul){'
			. 'if(!ul||ul.getAttribute("data-cindemir-filled")==="1")return;'
			. 'var src=document.querySelector("#avia-menu")||document.querySelector(".av-main-nav");'
			. 'if(!src)return;'
			. 'ul.innerHTML="";'
			. 'var kids=src.children;'
			. 'for(var i=0;i<kids.length;i++){'
			. 'var li=kids[i];'
			. 'if(!li||!li.tagName||li.tagName!=="LI")continue;'
			. 'if(li.classList&&li.classList.contains("av-burger-menu-main"))continue;'
			. 'ul.appendChild(li.cloneNode(true));'
			. '}'
			. 'ul.setAttribute("data-cindemir-filled","1");'
			. 'polishMenu(ul);'
			. '}'
			. 'function ensureOverlay(){'
			. 'var o=document.querySelector(".av-burger-overlay");'
			. 'if(!o){'
			. 'o=document.createElement("div");'
			. 'o.className="av-burger-overlay";'
			. 'o.setAttribute("data-cindemir-built","1");'
			. 'o.innerHTML=\'<div class="av-burger-overlay-bg"></div><div class="av-burger-overlay-scroll"><div class="av-burger-overlay-inner"><ul id="av-burger-menu-ul" class="av-main-nav"></ul></div></div>\';'
			. 'document.body.appendChild(o);'
			. '}'
			. 'var ul=o.querySelector("#av-burger-menu-ul");'
			. 'if(ul&&ul.children.length<2){cloneNavItems(ul);}else if(ul){polishMenu(ul);}'
			. 'return o;'
			. '}'
			. 'function fixOverlay(o){'
			. 'if(!o||!document.body)return;'
			. 'if(o.parentElement!==document.body){try{document.body.appendChild(o);}catch(e){}}'
			. 'var vh=(window.innerHeight||document.documentElement.clientHeight||800)+"px";'
			. 'var vw=(window.innerWidth||document.documentElement.clientWidth||390)+"px";'
			. 'o.style.setProperty("position","fixed","important");'
			. 'o.style.setProperty("top","0","important");'
			. 'o.style.setProperty("left","0","important");'
			. 'o.style.setProperty("right","0","important");'
			. 'o.style.setProperty("bottom","0","important");'
			. 'o.style.setProperty("width",vw,"important");'
			. 'o.style.setProperty("height",vh,"important");'
			. 'o.style.setProperty("min-height",vh,"important");'
			. 'o.style.setProperty("max-height","none","important");'
			. 'o.style.setProperty("z-index","2147483000","important");'
			. 'o.style.setProperty("overflow","hidden","important");'
			. 'o.style.setProperty("transform","none","important");'
			. 'if(isOpen()){o.style.setProperty("display","block","important");o.style.setProperty("opacity","1","important");}'
			. 'var s=o.querySelector(".av-burger-overlay-scroll");'
			. 'if(s){'
			. 's.style.setProperty("position","absolute","important");'
			. 's.style.setProperty("top","58px","important");'
			. 's.style.setProperty("right","12px","important");'
			. 's.style.setProperty("left","auto","important");'
			. 's.style.setProperty("bottom","auto","important");'
			. 's.style.setProperty("width","min(228px, 78vw)","important");'
			. 's.style.setProperty("height","auto","important");'
			. 's.style.setProperty("min-height","0","important");'
			. 's.style.setProperty("max-height","min(72vh, 520px)","important");'
			. 's.style.setProperty("overflow","auto","important");'
			. 's.style.setProperty("background","#faf9f6","important");'
			. 's.style.setProperty("background-color","#faf9f6","important");'
			. 's.style.setProperty("background-image","none","important");'
			. 's.style.setProperty("border","1px solid rgba(26,63,63,.10)","important");'
			. 's.style.setProperty("border-radius","12px","important");'
			. 's.style.setProperty("box-shadow","0 14px 36px rgba(14,28,28,.16)","important");'
			. 's.style.setProperty("transform","none","important");'
			. '}'
			. 'var bg=o.querySelector(".av-burger-overlay-bg");'
			. 'if(bg){bg.style.setProperty("background","#0e1c1c","important");bg.style.setProperty("opacity","0.22","important");}'
			. 'var items=o.querySelectorAll("#av-burger-menu-ul > li");'
			. 'for(var i=0;i<items.length;i++){items[i].style.setProperty("opacity","1","important");items[i].style.setProperty("top","0","important");items[i].style.setProperty("transform","none","important");}'
			. '}'
			. 'function openMenu(){'
			. 'var o=ensureOverlay();'
			. 'if(!o)return;'
			. 'ROOT.classList.add("av-burger-overlay-active");'
			. 'ROOT.classList.add("html_av-overlay-active");'
			. 'ROOT.classList.add("av-burger-overlay-active-delayed");'
			. 'document.body&&document.body.classList.add("av-burger-overlay-active");'
			. 'fixOverlay(o);'
			. 'var burger=document.querySelector(".av-burger-menu-main .av-hamburger");'
			. 'if(burger){burger.classList.add("is-active");}'
			. '}'
			. 'function closeMenu(){'
			. 'ROOT.classList.remove("av-burger-overlay-active");'
			. 'ROOT.classList.remove("html_av-overlay-active");'
			. 'ROOT.classList.remove("av-burger-overlay-active-delayed");'
			. 'document.body&&document.body.classList.remove("av-burger-overlay-active");'
			. 'var o=document.querySelector(".av-burger-overlay");'
			. 'if(o){o.style.setProperty("display","none","important");}'
			. 'var burger=document.querySelector(".av-burger-menu-main .av-hamburger");'
			. 'if(burger){burger.classList.remove("is-active");}'
			. '}'
			. 'var lastToggle=0;'
			. 'function toggleMenu(){'
			. 'var now=Date.now();'
			. 'if(now-lastToggle<450)return;'
			. 'lastToggle=now;'
			. 'if(isOpen()){closeMenu();}else{openMenu();}'
			. '}'
			. 'function isBurgerTarget(t){return !!(t&&t.closest&&t.closest(".av-burger-menu-main,.av-hamburger,.av-hamburger-box,.av-hamburger-inner"));}'
			. 'function onPointer(ev){'
			. 'var t=ev.target;'
			. 'if(!t)return;'
			. 'if(t.closest&&t.closest(".av-burger-overlay-bg")){'
			. 'ev.preventDefault();'
			. 'ev.stopPropagation();'
			. 'if(typeof ev.stopImmediatePropagation==="function")ev.stopImmediatePropagation();'
			. 'if(isOpen()){var n=Date.now();if(n-lastToggle>=450){lastToggle=n;closeMenu();}}'
			. 'return;'
			. '}'
			. 'if(isBurgerTarget(t)){'
			. 'ev.preventDefault();'
			. 'ev.stopPropagation();'
			. 'if(typeof ev.stopImmediatePropagation==="function")ev.stopImmediatePropagation();'
			. 'toggleMenu();'
			. '}'
			. '}'
			/* click covers desktop + iOS synthesized click; debounce avoids touch+click double toggle */
			. 'document.addEventListener("click",onPointer,true);'
			. 'function arm(){'
			. 'var o=document.querySelector(".av-burger-overlay");'
			. 'if(o)fixOverlay(o);'
			. '}'
			. 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",arm);}else{arm();}'
			. '})();'
			. '</script>';
	}

	/** Stop browsers/CDN holding a broken 8-hour HTML snapshot. */
	public static function no_store_html_headers() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( ! headers_sent() ) {
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			header( 'Pragma: no-cache' );
		}
	}

	/**
	 * @param array $list Exclusion list.
	 * @return array
	 */
	public static function rocket_exclude( $list ) {
		if ( ! is_array( $list ) ) {
			$list = array();
		}
		$list[] = 'cindemir-menu-fix';
		$list[] = 'cindemir-menu-fix-burger';
		$list[] = 'cindemir-lang-switch';
		return $list;
	}

	/**
	 * Header is locked to 64px; Enfold nests .av-burger-overlay inside #header,
	 * so height:100% collapses to ~64px and menu items are clipped. Force viewport.
	 */
	public static function print_burger_css() {
		if ( is_admin() ) {
			return;
		}
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		echo self::burger_css_tag();
		echo '<!--cindemir-menu-fix-v' . self::VERSION . "-->\n";
	}

	/** Move overlay to <body> + inline !important sizes (survives Rocket delay). */
	public static function print_burger_js() {
		if ( is_admin() ) {
			return;
		}
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		echo self::burger_js_tag() . "\n";
	}

	/** One-shot repair flag for WP menus (RU/ZH targets + ZH labels). */
	public static function maybe_repair_menus() {
		if ( get_option( 'cindemir_menu_fix_v1' ) === '1' ) {
			return;
		}
		if ( ! function_exists( 'wp_update_nav_menu_item' ) ) {
			return;
		}
		self::repair_ru_menu();
		self::repair_zh_menu();
		update_option( 'cindemir_menu_fix_v1', '1', false );
	}

	/** Force repair from WP-CLI: wp eval 'Cindemir_Menu_Fix::force_repair();' */
	public static function force_repair() {
		delete_option( 'cindemir_menu_fix_v1' );
		self::repair_ru_menu();
		self::repair_zh_menu();
		update_option( 'cindemir_menu_fix_v1', '1', false );
		return true;
	}

	private static function repair_ru_menu() {
		$menu_id = 1060; // Main Menu R
		$map     = array(
			2597 => array( 'title' => 'Главная', 'object_id' => 2570 ),
			2674 => array( 'title' => 'О нас', 'object_id' => 2 ),
			2675 => array( 'title' => 'Статьи', 'object_id' => 105 ),
			2676 => array( 'title' => 'Услуги', 'object_id' => 56 ),
			2677 => array( 'title' => 'Команда', 'object_id' => 2427 ),
			2679 => array( 'title' => 'Контакты', 'object_id' => 2446 ),
		);
		foreach ( $map as $item_id => $info ) {
			$page = get_post( $info['object_id'] );
			if ( ! $page || 'publish' !== $page->post_status ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				$item_id,
				array(
					'menu-item-title'     => $info['title'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $info['object_id'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		// Press stays custom external.
		if ( get_post( 2678 ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				2678,
				array(
					'menu-item-title'  => 'О нас в прессе',
					'menu-item-type'   => 'custom',
					'menu-item-url'    => self::PRESS_URL,
					'menu-item-status' => 'publish',
				)
			);
		}
	}

	private static function repair_zh_menu() {
		$menu_id = 1061; // Main Menu Ch
		$map     = array(
			2610 => array( 'title' => '首页', 'object_id' => 2568 ),
			2669 => array( 'title' => '关于我们', 'object_id' => 2629 ),
			2670 => array( 'title' => '文章', 'object_id' => 2633 ),
			2671 => array( 'title' => '服务', 'object_id' => 2637 ),
			2672 => array( 'title' => '团队', 'object_id' => 2641 ),
		);
		foreach ( $map as $item_id => $info ) {
			$page = get_post( $info['object_id'] );
			if ( ! $page || 'publish' !== $page->post_status ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				$item_id,
				array(
					'menu-item-title'     => $info['title'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $info['object_id'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		// Press custom → media.
		if ( get_post( 2673 ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				2673,
				array(
					'menu-item-title'  => '媒体报道',
					'menu-item-type'   => 'custom',
					'menu-item-url'    => self::PRESS_URL,
					'menu-item-status' => 'publish',
				)
			);
		}
		// Ensure Contacts exists.
		$has_contacts = false;
		$items        = wp_get_nav_menu_items( $menu_id );
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( (int) $item->object_id === 900095 || false !== strpos( (string) $item->title, '联系' ) ) {
					$has_contacts = true;
					break;
				}
			}
		}
		if ( ! $has_contacts && get_post( 900095 ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => '联系我们',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => 900095,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => 7,
				)
			);
		}
	}

	/**
	 * When WPML falls back to missing/deleted translation targets, retarget
	 * menu objects to the known good page IDs for the active language.
	 *
	 * @param array    $items Menu items.
	 * @param stdClass $args  Menu args.
	 * @return array
	 */
	public static function fix_menu_objects( $items, $args ) {
		if ( ! is_array( $items ) || ! $items ) {
			return $items;
		}
		$lang = self::front_lang();
		if ( 'ru' === $lang ) {
			$by_en = array(
				15 => 2570,
				16 => 2,
				17 => 105,
				18 => 56,
				19 => 2427,
				20 => 2446,
			);
			$labels = array(
				2570  => 'Главная',
				2     => 'О нас',
				105   => 'Статьи',
				56    => 'Услуги',
				2427  => 'Команда',
				2446  => 'Контакты',
			);
		} elseif ( in_array( $lang, array( 'zh-hans', 'zh' ), true ) ) {
			$by_en = array(
				15 => 2568,
				16 => 2629,
				17 => 2633,
				18 => 2637,
				19 => 2641,
				20 => 900095,
			);
			$labels = array(
				2568   => '首页',
				2629   => '关于我们',
				2633   => '文章',
				2637   => '服务',
				2641   => '团队',
				900095 => '联系我们',
			);
		} else {
			return $items;
		}

		foreach ( $items as $item ) {
			if ( empty( $item->type ) || 'post_type' !== $item->type || empty( $item->object_id ) ) {
				// Press custom link.
				if ( ! empty( $item->url ) && ( false !== strpos( $item->url, '/press' ) || false !== strpos( (string) $item->title, 'Press' ) || false !== strpos( (string) $item->title, 'пресс' ) || false !== strpos( (string) $item->title, '媒体' ) || false !== strpos( (string) $item->title, '支持' ) ) ) {
					$item->url   = self::PRESS_URL;
					$item->title = ( 'ru' === $lang ) ? 'О нас в прессе' : ( in_array( $lang, array( 'zh-hans', 'zh' ), true ) ? '媒体报道' : $item->title );
				}
				continue;
			}
			$oid = (int) $item->object_id;
			// Already a good target.
			if ( isset( $labels[ $oid ] ) ) {
				$item->title = $labels[ $oid ];
				$item->url   = get_permalink( $oid );
				continue;
			}
			// Map from EN source via WPML, or known EN IDs.
			$en_id = 0;
			$trid  = apply_filters( 'wpml_element_trid', null, $oid, 'post_page' );
			if ( $trid ) {
				$translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_page' );
				if ( is_array( $translations ) && isset( $translations['en']->element_id ) ) {
					$en_id = (int) $translations['en']->element_id;
				}
			}
			if ( ! $en_id && isset( $by_en[ $oid ] ) ) {
				$en_id = $oid;
			}
			if ( $en_id && isset( $by_en[ $en_id ] ) ) {
				$new_id = $by_en[ $en_id ];
				$page   = get_post( $new_id );
				if ( $page && 'publish' === $page->post_status ) {
					$item->object_id = $new_id;
					$item->url       = get_permalink( $new_id );
					if ( isset( $labels[ $new_id ] ) ) {
						$item->title = $labels[ $new_id ];
					}
				}
			}
		}
		return $items;
	}

	public static function fix_menu_html( $html, $args = null ) {
		return self::rewrite_lang_switcher_hrefs( $html );
	}

	public static function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'rewrite_html' ) );
	}

	public static function rewrite_html( $html ) {
		$html = self::rewrite_lang_switcher_hrefs( $html );
		$html = self::rewrite_press_hrefs( $html );
		$html = self::fix_zh_menu_labels( $html );
		return $html;
	}

	/** Force Press menu/content links to the external news URL (no /press hop). */
	private static function rewrite_press_hrefs( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$html = preg_replace(
			'#\bhref=(["\'])https?://(?:www\.)?cindemirlaw\.com/press/??(?:\?[^"\']*)?\1#i',
			'href=$1' . esc_attr( self::PRESS_URL ) . '$1',
			$html
		);
		return is_string( $html ) ? $html : '';
	}

	/** Correct wrong Chinese menu labels left in cached HTML. */
	private static function fix_zh_menu_labels( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( ! in_array( self::front_lang(), array( 'zh-hans', 'zh' ), true ) ) {
			return $html;
		}
		$replacements = array(
			'>研讨<'     => '>文章<',
			'>招聘信息<' => '>团队<',
			'>支持<'     => '>媒体报道<',
			'>招聘<'     => '>团队<',
		);
		foreach ( $replacements as $from => $to ) {
			$html = str_replace( $from, $to, $html );
		}
		return $html;
	}

	/**
	 * Rewrite every language switcher <a href> (WPML + injected cindemir-lang-item)
	 * to the correct target for that language on the current path.
	 */
	private static function rewrite_lang_switcher_hrefs( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( false === strpos( $html, 'cindemir-lang-item' )
			&& false === strpos( $html, 'language_' )
			&& false === strpos( $html, 'wpml-ls-' )
			&& false === strpos( $html, 'avia_wpml_language_switch' ) ) {
			return $html;
		}

		$path = self::request_path();
		$path = ( ! $path || '/' === $path ) ? '/' : trailingslashit( $path );
		$base = 'https://cindemirlaw.com' . ( '/' === $path ? '/' : $path );
		$urls = array(
			'en'      => self::lang_url( 'en', $base ),
			'ru'      => self::lang_url( 'ru', $base ),
			'zh-hans' => self::lang_url( 'zh-hans', $base ),
			'zh'      => self::lang_url( 'zh-hans', $base ),
		);

		$out = preg_replace_callback(
			'#<li\b([^>]*\b(?:cindemir-lang-item|language_[a-z0-9\-]+|wpml-ls-item-[a-z0-9\-]+)[^>]*)>(.*?)</li>#is',
			static function ( $m ) use ( $urls ) {
				$attrs = $m[1];
				$inner = $m[2];
				$code  = '';
				if ( preg_match( '/\blanguage_([a-z0-9\-]+)\b/i', $attrs, $cm ) ) {
					$code = strtolower( $cm[1] );
				} elseif ( preg_match( '/\bwpml-ls-item-([a-z0-9\-]+)\b/i', $attrs, $cm ) ) {
					$code = strtolower( $cm[1] );
				}
				if ( '' === $code || ! isset( $urls[ $code ] ) ) {
					return $m[0];
				}
				$url   = $urls[ $code ];
				$inner = preg_replace(
					'#(\bhref=)(["\'])([^"\']*)\2#i',
					'$1$2' . esc_attr( $url ) . '$2',
					$inner,
					1
				);
				return '<li' . $attrs . '>' . $inner . '</li>';
			},
			$html
		);
		return is_string( $out ) ? $out : $html;
	}

	private static function lang_url( $code, $base ) {
		$code = strtolower( (string) $code );
		$base = preg_replace( '/([?&])lang=[^&]*/', '$1', $base );
		$base = preg_replace( '/([?&])cindemir_lang=[^&]*/', '$1', $base );
		$base = rtrim( $base, '?&' );
		if ( in_array( $code, array( 'en', 'en-us', 'en_us' ), true ) ) {
			$current = self::front_lang();
			if ( $current && ! in_array( $current, array( 'en', 'en-us', 'en_us' ), true ) ) {
				$sep = ( false === strpos( $base, '?' ) ) ? '?' : '&';
				return $base . $sep . 'cindemir_lang=en';
			}
			return $base;
		}
		$map = array(
			'ru'      => 'ru',
			'zh-hans' => 'zh-hans',
			'zh'      => 'zh-hans',
		);
		if ( ! isset( $map[ $code ] ) ) {
			return $base;
		}
		$sep = ( false === strpos( $base, '?' ) ) ? '?' : '&';
		return $base . $sep . 'lang=' . rawurlencode( $map[ $code ] );
	}

	private static function front_lang() {
		if ( ! empty( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$get = sanitize_key( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $get ) {
				return $get;
			}
		}
		if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
			return strtolower( (string) ICL_LANGUAGE_CODE );
		}
		$wpml = apply_filters( 'wpml_current_language', null );
		if ( is_string( $wpml ) && $wpml ) {
			return strtolower( $wpml );
		}
		return 'en';
	}

	private static function request_path() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		return $path ? $path : '/';
	}
}

Cindemir_Menu_Fix::boot();

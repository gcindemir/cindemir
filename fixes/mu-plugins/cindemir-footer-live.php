<?php
/**
 * Plugin Name: Cindemir Footer Live
 * Description: Footer mailto, social icons, baro verify, badges (minimal).
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
	if ( get_option( 'cindemir_footer_live_v1' ) ) return;
	if ( function_exists( 'rocket_clean_domain' ) ) rocket_clean_domain();
	if ( function_exists( 'wp_cache_flush' ) ) wp_cache_flush();
	update_option( 'cindemir_footer_live_v1', 1, false );
}, 1 );

add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	?>
<script id="cindemir-footer-live">
(function(){
  var em='cindemir@cindemir.av.tr', ph='+90 216 550 67 75';
  var c=document.querySelector('#socket .copyright');
  if(c && c.innerHTML.indexOf('cindemir-footer-email')<0){
    c.innerHTML=c.innerHTML
      .replace(ph,'<a href="tel:+902165506775" class="cindemir-footer-email">'+ph+'</a>')
      .replace(em,'<a href="mailto:'+em+'" class="cindemir-footer-email">'+em+'</a>');
  }
  if(document.getElementById('cindemir-socket-extras')) return;
  var box=document.querySelector('#socket .container');
  if(!box) return;
  box.insertAdjacentHTML('beforeend','<div class="cindemir-socket-extras" id="cindemir-socket-extras"><div id="cindemir-baro-verification-bar"><a href="https://baronet.istanbulbarosu.org.tr/avukat/belge_dogrulama?lang=EN&onayno=HBE4U7ES3DM6C52&tck=58612509084" target="_blank" rel="noopener">Avukat Baro Doğrulama için Tıklayınız</a></div><nav class="cindemir-footer-social" aria-label="Social"><ul class="cindemir-footer-social-list"><li><a href="mailto:cindemir@cindemir.av.tr" title="Email">✉</a></li><li><a href="https://www.facebook.com/p/Cindemir-Hukuk-B%C3%BCrosu-Cindemir-Law-Office-100066585793269/" target="_blank" rel="noopener" title="Facebook">f</a></li><li><a href="https://www.instagram.com/cindemir_law_office/" target="_blank" rel="noopener" title="Instagram">◎</a></li><li><a href="https://www.linkedin.com/company/cindemir-law-office/" target="_blank" rel="noopener" title="LinkedIn">in</a></li><li><a href="tel:+902165506775" title="Phone">☎</a></li><li><a href="https://t.me/gcindemir" target="_blank" rel="noopener" title="Telegram">✈</a></li><li><a href="https://x.com/cindemirlegal" target="_blank" rel="noopener" title="X">𝕏</a></li><li><a href="https://wa.me/905325680647" target="_blank" rel="noopener" title="WhatsApp">W</a></li><li><a href="https://www.youtube.com/channel/UCHobIlbWxCMGTPBSZv_rM7Q" target="_blank" rel="noopener" title="YouTube">▶</a></li></ul></nav><div class="cindemir-footer-badges"><a href="https://www.aeuropea.com/" target="_blank" rel="noopener"><img src="https://www.aeuropea.com/wp-content/uploads/2025/09/aea-01v001-ILN-small.png" alt="AEuropea" height="48" loading="lazy"></a><a href="https://www.istanbulbarosu.org.tr/" target="_blank" rel="noopener"><img src="https://www.istanbulbarosu.org.tr/_next/image?url=%2Fimages%2Fbaro_logo.png&amp;w=128&amp;q=75" alt="İstanbul Barosu" height="48" loading="lazy"></a><a href="https://www.barobirlik.org.tr/" target="_blank" rel="noopener"><img src="https://d.barobirlik.org.tr/amblem/tbb_amblem_60.png" alt="TBB" height="48" loading="lazy"></a></div></div><style>#socket .cindemir-socket-extras{width:100%;margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.15);text-align:center}#socket .cindemir-baro-verification-bar a{color:inherit;text-decoration:underline;font-size:14px}#socket .cindemir-footer-social-list{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;list-style:none;margin:10px 0;padding:0}#socket .cindemir-footer-social-list a{display:inline-flex;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;align-items:center;justify-content:center;text-decoration:none;font-size:14px}#socket .cindemir-footer-badges{display:flex;justify-content:center;gap:14px;flex-wrap:wrap}#socket .cindemir-footer-badges img{height:48px;width:auto}#socket .copyright a{color:inherit;text-decoration:underline}</style>');
})();
</script>
	<?php
}, 20 );

# Cindemirlaw.com — Cursor Görev Talimatı (SEO / Ahrefs düzeltmeleri)

Bu dosya kendi kendine yeter. Site: **cindemirlaw.com** — WordPress + Yoast SEO + çok dilli eklenti (Polylang/WPML tipi `?lang=` yapısı) + Enfold teması. Bluehost'ta barındırılıyor.

## KURALLAR (ÖNEMLİ)
- **Avukatlık reklam yasağı (TBB Meslek Kuralları):** tüm meta description ve title'lar BİLGİLENDİRİCİ ve TARAFSIZ olacak. YASAK: "en iyi / lider / uzman kadro / başarı garantisi / hemen ara / bize ulaşın / ücretsiz danışma", övgü, üstünlük iddiası, çağrı-eylem (CTA), abartı sıfat. Serbest: konuyu nesnel özetlemek.
- Değer/istatistik **uydurma**. Meta metni sayfanın gerçek içeriğinden.
- Her metin **sayfanın dilinde** (RU sayfa → Rusça, ZH → Çince, EN/TR → kendi dili).
- Meta description hedef uzunluk: **110–160 karakter**. Title: 50–60.
- Otomatik publish/merge yok; değişiklikleri reviewable bırak.

---

## DURUM: NE YAPILDI, NE KALDI

**Zaten yapıldı (canlı sitede, REST API ile, doğrulandı):**
- 4 blog gönderisinin meta description'ı güncellendi (nötr, reklamsız): 422, 4740, 4835, 4882.
- 1 bozuk dış link giderildi: post 3412'de (kriminal sicil rehberi) 404 veren `istanbulbarosu.org.tr/AttorneySearch.aspx` linki kaldırıldı, "Attorney Search" metni korundu.

**BU DOSYADAKİ İŞLER (senin/Cursor'un yapacağı):**
1. **14 sayfanın** meta description'ını gir (aşağıdaki tablo) — Yoast alanı `pages`'te REST'e kapalı olduğu için otomasyon yapılamadı.
2. (Opsiyonel) mu-plugin ile bu kapıyı aç.
3. En büyük SEO sorunu: `?lang=` zorla-redirect ayarını düzelt (aşağıda).
4. Bozuk görseller + kalan meta/title/H1 (Ahrefs UI'dan liste).

---

## GÖREV 1 — 14 SAYFA META DESCRIPTION (öncelik: yüksek, kolay)

Her sayfa için: **wp-admin → Sayfalar → [sayfayı aç] → aşağı kaydır → Yoast SEO kutusu → "Meta açıklaması" alanı → aşağıdaki metni yapıştır → Güncelle.**
(Sayfayı ID veya URL ile bul. Dil sekmesi varsa doğru dile dikkat et.)

| Sayfa ID | URL | Yeni Meta Description |
|----------|-----|------------------------|
| 43 | `https://cindemirlaw.com/our-videos/` | Cindemir Law Office'in Türk hukuku ve yabancılara yönelik hukuki konular hakkında hazırladığı video içeriklerinin derlendiği sayfa. |
| 2 | `https://cindemirlaw.com/onas/` | Cindemir Law Office — независимая юридическая фирма в Стамбуле, работающая с 2004 года в сфере турецкого и международного права. |
| 105 | `https://cindemirlaw.com/stati/` | Статьи о турецком праве: гражданское, коммерческое, миграционное и уголовное право Турции для иностранных граждан и компаний. |
| 3884 | `https://cindemirlaw.com/who-is-hafiz-huseyin-husnu-efendi/` | Hafız Hüseyin Hüsnü Efendi'nin biyografisi: 1847'de Batum'da doğan bu ismin hayatı, ilmî kişiliği ve tarihsel arka planı ele alınır. |
| 16 | `https://cindemirlaw.com/about-us/` | Cindemir Law Office, 2004'ten bu yana İstanbul'da faaliyet gösteren, Türk ve uluslararası hukuk alanında çalışan bağımsız bir hukuk bürosudur. |
| 2427 | `https://cindemirlaw.com/komanda/` | Команда Cindemir Law Office: адвокаты и консультанты, работающие в области турецкого и международного права в Стамбуле. |
| 392 | `https://cindemirlaw.com/support/` | Cindemir Law Office'in müvekkillerle iletişimi ve Türkiye'deki hukuki süreçlerde yabancılara sağladığı destek hakkında bilgi. |
| 51 | `https://cindemirlaw.com/news-events/` | Cindemir Law Office'ten haberler ve etkinlikler: yabancı birey ve şirketleri ilgilendiren Türk hukukundaki gelişmelere dair güncellemeler. |
| 19 | `https://cindemirlaw.com/team/` | Cindemir Law Office ekibi: İstanbul'da Türk ve uluslararası hukuk alanında çalışan avukatlar ve danışmanlar hakkında bilgi. |
| 103 | `https://cindemirlaw.com/pod/` | О порядке общения адвоката с подзащитным в Турции: обмен информацией, права и обязанности сторон в уголовном процессе. |
| 17 | `https://cindemirlaw.com/articles/` | Türk hukukuna dair makaleler: yabancı birey ve şirketleri ilgilendiren medeni, ticari, göç ve ceza hukuku konuları ele alınır. |
| 390 | `https://cindemirlaw.com/privacy-policy/` | Cindemir Law Office'in gizlilik politikası: web sitesi ziyaretçilerine ait kişisel verilerin nasıl toplandığı, kullanıldığı ve korunduğu açıklanır. |
| 56 | `https://cindemirlaw.com/nashiyurist/` | Юридические услуги в Турции: корпоративное, миграционное, семейное и уголовное право для иностранных клиентов в Стамбуле. |
| 3874 | `https://cindemirlaw.com/family-heritage/` | Cindemir Law Office'in tarihçesi: Osmanlı mahkemelerinden günümüze uzanan hukuki geçmişi İstanbul üzerinden anlatılır. |

> Not: Bu metinlerin hepsi 110–160 karakter, reklamsız ve sayfanın dilinde hazırlandı. Değiştirmek istersen aynı kurallara uy.

---

## GÖREV 2 (Opsiyonel ama önerilen) — mu-plugin ile REST'i aç

Eğer bu tür meta güncellemelerini kod/otomasyonla yapmak istersen, aşağıdaki dosyayı
`public_html/wp-content/mu-plugins/cindemir-expose-yoast-meta.php` olarak kaydet
(Bluehost cPanel → File Manager veya FTP). `mu-plugins` klasörü yoksa oluştur — otomatik aktif olur, etkinleştirme gerekmez.

Bu, `page` post-type'ı için Yoast meta alanlarını (meta description, SEO title) WordPress REST API'ye açar. Sadece mevcut alanları expose eder, veri değiştirmez.

```php
<?php
/**
 * Plugin Name: Cindemir – Expose Yoast Meta to REST (pages)
 * Description: Registers Yoast SEO meta fields (meta description & SEO title) for the "page" post type in the REST API so they can be read/updated via the WordPress REST API. Safe, read/write of existing Yoast fields only.
 * Author: Cindemir Law
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', function () {
    $fields = array(
        '_yoast_wpseo_metadesc',
        '_yoast_wpseo_title',
        '_yoast_wpseo_focuskw',
    );

    // Expose for BOTH pages and posts (posts already work, harmless to repeat).
    foreach ( array( 'page', 'post' ) as $ptype ) {
        foreach ( $fields as $key ) {
            register_post_meta( $ptype, $key, array(
                'type'          => 'string',
                'single'        => true,
                'show_in_rest'  => true,
                'auth_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
            ) );
        }
    }
} );
```

---

## GÖREV 3 — EN BÜYÜK SEO SORUNU: `?lang=` zorla-redirect (öncelik: kritik)

Ahrefs'teki en yüksek etkili 3 sorun tek kök nedene bağlı:
- **3XX redirect** (65 sayfa) — neredeyse hepsi `sayfa/` → `sayfa/?lang=ru` (veya `?lang=zh-hans`) self-redirect
- **Page has links to redirect** (299 sayfa) — iç linkler bu redirect URL'lerine gidiyor
- **Sitemap'te 3XX redirect** (63) — sitemap redirect URL'leri listeliyor

**Düzeltme (wp-admin, dil eklentisi ayarı):**
- Polylang: *Languages → Settings → URL modifications* → "Hide URL language information for default language" AÇIK olsun; varsayılan dil için `?lang=` parametresini kaldır.
- WPML: default dil URL formatını "parametre yok" yap.
- Sonra Yoast → sitemap'i yeniden üret; sitemap girdileri dilsiz kanonik URL'leri göstermeli.
Bu tek ayar 3 sorunu birden büyük ölçüde kapatır.

---

## GÖREV 4 — KALAN TEKNİK SORUNLAR

**Bozuk görseller (5, 404):** `/russian/wp-content/.../white-*.jpg` ve `/chinese/wp-content/.../white-*.jpg` — eski multisite yollarından kalma. RU/ZH sürüm sayfalarında (hakan, gökhan-cindemir profilleri) görünüyor. Bu görselleri medya kütüphanesindeki geçerli sürümle değiştir veya kaldır.

**barobirlik.org.tr → 403:** bot engeli, tarayıcıda açılıyor = muhtemelen false positive, DOKUNMA.

**Meta description too short (25) + too long kalan + Title (22 long / 4 short) + H1 (missing/multiple):** Bu sayfa listeleri Ahrefs API'de bu crawl için boş döndü. Listeleri Ahrefs UI'dan çek: Site Audit → Cindemirlaw → Issues → ilgili sorun → "View affected pages" → export. Aynı reklamsız kurallarla düzelt.

---

## BİTİNCE
1. Şu sayfaları tarayıcıda kontrol et: bir dilsiz makale URL'i (artık `?lang=`e 301 atmamalı), 14 güncellenen sayfa, post 3412, sitemap_index.xml.
2. Ahrefs'te **yeniden crawl** tetikle (Site Audit → Cindemirlaw → Run crawl) ve sonuçları karşılaştır.

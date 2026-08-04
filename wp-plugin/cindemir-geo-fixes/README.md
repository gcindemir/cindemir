# Cindemir GEO Fixes (WordPress plugin)

**Yedek:** `backups/2026-08-04-pre-fix/` (canlı HTML + JSON-LD anlık görüntü)

## Ne düzeltir (önemli / mantıklı olanlar)

| Sorun | Düzeltme |
|--------|----------|
| Çift `Organization` (SASWP boş `sameAs`) | SASWP JSON-LD kapatılır |
| Meta Tag Manager Organization | `wp_head` içinde Organization-only blok temizlenir |
| Elementor’a gömülü eski LegalService | `the_content` / Elementor içeriğinden temizlenir |
| Dil sinyali karışık | TR→TR, RU→RU, EN→global, ZH→Çin schema basılır |
| Zayıf OG/meta description (anasayfa) | Dil bazlı güçlü açıklama |
| Polylang bayraklarında boş `alt` | Dil adı ile doldurulur |
| Eksik niyet sayfaları | Aktivasyonda 4 landing + FAQ schema |

**Bilerek dokunulmayanlar:** Audit’teki “20k broken link / Unknown host” (crawl false positive), YouTube `vi/ID` JS şablonu, site geneli title uzunluğu (içerik editörü işi).

## Kurulum

1. `wp-plugin/cindemir-geo-fixes` klasörünü zip’leyin  
2. WP Admin → Eklentiler → Yükle → etkinleştir  
3. veya: `wp plugin install cindemir-geo-fixes.zip --activate`  
4. Anasayfa + RU/EN/ZH front’u Rich Results Test ile kontrol edin  
5. Landing’ler: `/rusca-bilen-avukat/`, `/ru/...`, `/en/international-lawyer-turkey/`, `/zh/zhongwen-lushi-tuerqi/`

## Geri alma

Eklentiyi deaktif edin. Elementor’daki eski schema widget’ları yedekteki HTML’den geri yapıştırılabilir (`backups/...`).

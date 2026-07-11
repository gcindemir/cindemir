# Cindemirlaw SEO görevleri — durum (2026-07-11)

## Tamamlanan

- [x] **İlk yedekleme** — `backups/cindemirlaw.com-20260711-214047/` (491 dosya, 308 HTML, ~276 MB)
- [x] Mevcut düzeltme paketi repoda: `fixes/mu-plugins/cindemir-seo-fixes.php` (v1.5.5), Redirection CSV, `.htaccess` snippet

## Bekleyen — talimat dosyası gerekli

`cindemirlaw-cursor-gorev.md` bu ortama **ulaşmadı** (Letaido Downloads 401; workspace’te dosya yok).

Lütfen dosyayı sohbete tekrar ekleyin (`@cindemirlaw-cursor-gorev.md`) veya içeriği yapıştırın. Dosya olmadan aşağıdaki görevlerdeki **hazır metinler ve tam ayar** uygulanamaz:

| Görev | Ne gerekiyor |
|-------|----------------|
| **1** | 14 sayfa meta description tablosu (ID + URL + metin) |
| **2** | Mu-plugin kurulumu (FTP/WP erişimi veya `CINDEMIR_FTP_*` env) |
| **3** | `?lang=` zorla-redirect tek ayarı (Redirection / mu-plugin v1.6) |
| **4** | Bozuk görseller + Ahrefs title/H1 listeleri |

## Canlı site notu

- WP/FTP kimlik bilgisi bu ortamda yok → değişiklikler `fixes/INSTALL.md` üzerinden manuel yükleme veya FTP env ile `fixes/deploy-ftp.sh`
- Mevcut mu-plugin canlıda (önceki oturum): H1, redirect flatten, `/press/` rewrite, title trim

## Sonraki adım

Talimat dosyası gelince: meta’ları Yoast’a gir / WP-CLI script, mu-plugin v1.6, redirect kuralı, görsel düzeltmeleri sırayla uygula → Ahrefs yeniden crawl.

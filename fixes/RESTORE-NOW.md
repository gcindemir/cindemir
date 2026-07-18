# Site 500 — hemen aç

Sebep: `wp-content/mu-plugins/cindemir-contact-fixes.php` içinde PHP sözdizimi hatası (`\$marker`).

## Yol A — en hızlı (30 sn)

Bluehost → **File Manager** → `public_html/wp-content/mu-plugins/`

1. `cindemir-contact-fixes.php` seç
2. **Rename** → `cindemir-contact-fixes.php.off`
3. Siteyi yenile → açılmalı

## Yol B — düzgün fix (önerilen)

1. İndir: https://github.com/gcindemir/cindemir/releases/download/services-blank-fix-20260715/heal-services-blank.php
2. File Manager → `public_html` → **Upload**
3. Tarayıcıda aç: https://cindemirlaw.com/heal-services-blank.php?key=seo-pack-2026
4. `OK contact …` / `DONE` görünmeli

## Yol C — FTP/SSH

Güncel şifreyle şunu yükle / overwrite et:

`fixes/mu-plugins/cindemir-contact-fixes.php` (v1.3.13, ~97 KB)

Şifreyi agent’e `/tmp/pw_cindemirlaw_com.txt` olarak yazarsan ben çekerim.

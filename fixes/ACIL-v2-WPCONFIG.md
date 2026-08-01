# ACİL v2 — Site tamamen çöktü

Ana sayfa da **critical error** veriyor. Heal dosyası muhtemelen **yanlış klasöre** yüklendi.

---

## Yöntem A — wp-config (en garanti)

1. Bluehost → **File Manager** → `public_html`
2. **`wp-config.php`** dosyasına sağ tık → **Edit**
3. Şu satırı bul:
   ```
   /* That's all, stop editing! Happy publishing. */
   ```
4. **Hemen ÜSTÜNE** şunu yapıştır:

```php
define( 'WPMU_PLUGIN_DIR', __DIR__ . '/wp-content/mu-plugins-empty' );
define( 'WPMU_PLUGIN_URL', 'https://cindemirlaw.com/wp-content/mu-plugins-empty' );
```

5. Kaydet
6. File Manager → `public_html/wp-content/` → **+ Folder** → ad: **`mu-plugins-empty`**
7. Aç: **https://cindemirlaw.com/wp-admin/**

Panel açılmalı.

---

## Yöntem B — klasör adı

`public_html/wp-content/` içinde **`mu-plugins`** → sağ tık → Rename → **`mu-plugins-OFF`**

---

## Panel açılınca

1. `wp-config.php` içindeki 2 satırı **sil** (Yöntem A yaptıysan)
2. `mu-plugins-OFF` → tekrar **`mu-plugins`** yap
3. İçindeki **tüm** `cindemir-*.php` dosyalarını sil
4. Zip indir, extract et, yükle:

**https://github.com/gcindemir/cindemir/raw/cursor/cindemirlaw-seo-tasks-d204/fixes/deploy-package/mu-plugins.zip**

`cindemir-seo-fixes.php` **~56 KB** olmalı.

5. **bitti** yaz.

---

## Yöntem C — _fix klasörü (alternatif)

1. File Manager → `public_html` → **+ Folder** → `_fix`
2. İndir: https://github.com/gcindemir/cindemir/raw/cursor/cindemirlaw-seo-tasks-d204/fixes/deploy-package/_fix/heal.php
3. `_fix` klasörüne yükle
4. Aç: **https://cindemirlaw.com/_fix/heal.php?key=seo-pack-2026**

Ekranda düz yazı görmelisin (`Cindemir heal v2`).

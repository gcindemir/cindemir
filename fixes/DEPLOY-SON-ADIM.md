# Son adım — mu-plugins’i aktifleştir

wp-admin açıldı. Şimdi **wp-config** içindeki geçici satırları kaldırıp yüklenen eklentileri devreye al.

---

## 1) wp-config düzenle

File Manager veya WP File Manager → `public_html/wp-config.php` → **Edit**

**Şu 2 satırı sil:**

```php
define( 'WPMU_PLUGIN_DIR', __DIR__ . '/wp-content/mu-plugins-empty' );
define( 'WPMU_PLUGIN_URL', 'https://cindemirlaw.com/wp-content/mu-plugins-empty' );
```

**Save**

---

## 2) Upgrade linkini aç

https://cindemirlaw.com/?cindemir_upgrade=seo-pack-2026

---

## 3) Cache temizle

wp-admin → **WP Rocket** → **Clear cache**

---

## 4) Kontrol

Ana sayfa kaynağında: `cindemir-seo-fixes 1.8.2`

---

## Critical error olursa

wp-config satırlarını **geri ekle** (panel tekrar açılır), sonra:

`mu-plugins` içinde `cindemir-seo-fixes.php` boyutu **~56 KB** mı kontrol et (2 KB ise bozuk).

Zip: https://github.com/gcindemir/cindemir/raw/cursor/cindemirlaw-seo-tasks-d204/fixes/deploy-package/mu-plugins.zip

**bitti** yaz.

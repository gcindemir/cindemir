# ACİL — bypass silince critical error

mu-plugins içinde **bozuk dosya** var. Önce paneli geri aç, sonra temiz yükleme.

---

## HEMEN — wp-config satırlarını GERİ EKLE

`public_html/wp-config.php` → Edit → şunları **tekrar ekle** (`/* That's all` satırının üstüne):

```php
define( 'WPMU_PLUGIN_DIR', __DIR__ . '/wp-content/mu-plugins-empty' );
define( 'WPMU_PLUGIN_URL', 'https://cindemirlaw.com/wp-content/mu-plugins-empty' );
```

Kaydet → wp-admin tekrar açılmalı.

**Geri ekledim** yaz.

---

## Sonra (panel açıkken) — mu-plugins temizle

File Manager → `public_html/wp-content/mu-plugins`

**SİL** (hepsini):
- `cindemir-seo-fixes.php`
- `cindemir-contact-fixes.php`
- `cindemir-expose-yoast-meta.php`
- `cindemir-purge-cache.php`
- `cindemir-force-upgrade.php`
- `heal-cindemir.php`
- Tüm `.bak` dosyaları

---

## Tek dosya yükle (güvenli sürüm)

İndir (~44 KB):

https://github.com/gcindemir/cindemir/raw/36dbed1/fixes/mu-plugins/cindemir-seo-fixes.php

`mu-plugins` içine yükle. Boyut **44533 byte** civarı olmalı.

---

## Test

1. wp-config’teki **2 satırı tekrar sil**
2. Site + wp-admin açılıyor mu?

Açılıyorsa **bitti** yaz — kalan dosyaları ve v1.8.2’yi ben tamamlarım.

Hâlâ hata varsa: wp-config satırlarını geri ekle, **bitti** yaz.

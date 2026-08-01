# ACİL — wp-admin critical error düzeltme

Site ön yüzü açılıyor ama **wp-admin çökük**. Mu-plugins dosyalarından biri bozuk.

**Terminal yok.** Sadece Bluehost File Manager + 1 link.

---

## 3 adım (5 dakika)

### 1) Bu dosyayı indir

**https://github.com/gcindemir/cindemir/raw/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/heal-cindemir.php**

(~2 KB — `heal-cindemir.php`)

### 2) Bluehost File Manager’a yükle

1. https://www.bluehost.com → giriş (Cloudflare kutusunu işaretle)
2. **Hosting** → **cindemirlaw.com** → **File Manager**
3. **`public_html`** klasörüne gir (mu-plugins DEĞİL — ana klasör)
4. **Upload** → `heal-cindemir.php` yükle

### 3) Bu linki tarayıcıda aç

**https://cindemirlaw.com/heal-cindemir.php?key=seo-pack-2026**

Ekranda şunları görmelisin:
```
disabled: cindemir-seo-fixes.php
OK cindemir-seo-fixes.php (xxxxx bytes)
...
DONE. Test: https://cindemirlaw.com/wp-admin/
```

Sonra **wp-admin**’i aç — critical error gitmeli.

---

## Hâlâ hata varsa

File Manager → `public_html/wp-content/mu-plugins/`:

| Dosya | Ne yap |
|-------|--------|
| `cindemir-seo-fixes.php` | Sil veya `.off` yap |
| `cindemir-contact-fixes.php` | Sil veya `.off` yap |
| `cindemir-seo-fixes.php.bak-broken` | **Yeniden adlandır** → `cindemir-seo-fixes.php` (45 KB olmalı) |

Diğer `cindemir-*.php` dosyalarını geçici olarak `.off` uzantılı yap.

Sonra heal linkini tekrar aç.

---

## Kontrol

- wp-admin açılıyor mu?
- Ana sayfa kaynakta `cindemir-seo-fixes 1.8` görünüyor mu?

Bitti deyince devam ederim.

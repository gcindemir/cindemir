# Kolay deploy (terminal YOK)

Terminal kullanmadan **Bluehost File Manager** ile 2 dakikada yükleme.

---

## Yöntem A — Tek küçük dosya + tarayıcı linki (en kolay)

### 1) Dosyayı indir
Bu linke tıkla, bilgisayarına inen dosyayı kaydet:

**https://github.com/gcindemir/cindemir/raw/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/cindemir-force-upgrade.php**

(Dosya adı: `cindemir-force-upgrade.php` — yaklaşık 2 KB)

### 2) Bluehost File Manager’a yükle
1. https://www.bluehost.com → **Giriş yap**
2. **Hosting** → **cindemirlaw.com** → **File Manager**
3. Klasör: `public_html` → `wp-content` → `mu-plugins`
4. Üstte **Upload** → indirdiğin `cindemir-force-upgrade.php` dosyasını seç → yükle

### 3) Tarayıcıda bu linki aç (tek tık)
**https://cindemirlaw.com/?cindemir_upgrade=seo-pack-2026**

Sayfa normal açılır; arka planda tüm eklentiler GitHub’dan güncellenir.

### 4) Bana yaz: **bitti**

---

## Yöntem B — Zip ile toplu yükleme

### 1) Zip indir
**https://github.com/gcindemir/cindemir/raw/cursor/cindemirlaw-seo-tasks-d204/fixes/deploy-package/mu-plugins.zip**

### 2) File Manager
Aynı klasör: `public_html/wp-content/mu-plugins`

- **Upload** → `mu-plugins.zip` yükle
- Zip dosyasına sağ tık → **Extract** / **Unarchive**

### 3) Kontrol
`cindemir-seo-fixes.php` dosyası **~56 KB** olmalı (2 KB ise bozuk — sil, zip’i tekrar extract et).

Bluehost’ta **WP-CLI** veya cache varsa: Hosting panelinden **Clear cache** / **Purge cache**.

### 4) Bana yaz: **bitti**

---

## Sorun giderme

| Sorun | Çözüm |
|-------|--------|
| `mu-plugins` klasörü yok | File Manager’da **+ Folder** → `mu-plugins` |
| Site hâlâ 1.7.2 | Cache temizle; 5 dk bekle |
| Upload gri / hata | Dosyayı zip içinden çıkarıp tek tek yükle (Yöntem A) |

---

**Not:** Agent terminal/SSH/FTP ile sunucuya bağlanamıyor (Cloudflare + şifre). File Manager senin tarafında en güvenilir yol.

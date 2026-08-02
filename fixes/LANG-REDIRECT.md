# Görev 3 — `?lang=` zorla-redirect düzeltmesi

Ahrefs'teki en yüksek etkili 3 sorun tek kök nedene bağlı:

| Ahrefs sorunu | Etki |
|---------------|------|
| 3XX redirect (65 sayfa) | `sayfa/` → `sayfa/?lang=ru` (veya `?lang=zh-hans`) self-redirect |
| Page has links to redirect (299) | İç linkler redirect URL'lerine gidiyor |
| Sitemap'te 3XX redirect (63) | Sitemap redirect URL'leri listeliyor |

## Teşhis (canlı, 2026-07-11)

`page-sitemap.xml` hâlâ `?lang=ru` / `?lang=zh-hans` içeren URL'ler listeliyor:

```
https://cindemirlaw.com/about-us/?lang=ru
https://cindemirlaw.com/articles/?lang=ru
https://cindemirlaw.com/team/?lang=ru
...
```

Varsayılan dil (İngilizce) sayfaları (`/about-us/`, `/articles/`) doğrudan 200 dönüyor; sorun çoğunlukla çeviri URL formatı ve iç linklerde.

## Düzeltme (wp-admin — manuel, ~5 dk)

### Polylang kullanılıyorsa

1. **Languages → Settings → URL modifications**
2. **"Hide URL language information for default language"** → **AÇIK**
3. Varsayılan dil (English) için `?lang=en` parametresi kaldırılmalı
4. Kaydet

### WPML kullanılıyorsa

1. **WPML → Languages → Language URL format**
2. Varsayılan dil için **"Different languages in directories"** veya **"parameter yok"** formatını seç
3. Default language URL'lerinde `?lang=` olmamalı

### Sonra

1. **Yoast SEO → General → Features** → sitemap cache temizle (veya "Save" ile yeniden üret)
2. **Ayarlar → Kalıcı bağlantılar → Kaydet** (rewrite flush)
3. Host/Cloudflare cache purge

## Doğrulama

```bash
UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'

# Varsayılan dil makale — 301 olmamalı
curl -sI -A "$UA" --max-redirs 0 https://cindemirlaw.com/how-to-divorce-in-turkey/ | head -3

# Sitemap — ?lang= içermemeli (veya sadece gerçek çeviriler)
curl -s -A "$UA" https://cindemirlaw.com/page-sitemap.xml | grep -c '?lang='
```

## Not

`fixes/mu-plugins/cindemir-seo-fixes.php` iç linklerdeki redirect hedeflerini HTML çıktısında rewrite eder, ancak **kök nedeni** (dil eklentisi URL ayarı) wp-admin'den düzeltilmelidir. Aksi halde sitemap ve crawl sorunları sürer.

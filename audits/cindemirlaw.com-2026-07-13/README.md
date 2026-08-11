# Ahrefs Audit — cindemirlaw.com (2026-07-13)

Kaynak: kullanıcı CSV export'ları (`audits/cindemirlaw.com-2026-07-13/`)

## Kritik bulgu (canlı kontrol)

**Mu-plugins 0 byte** — başka agent işlemi dosyaları bozmuş:

| Dosya | Boyut (canlı) |
|-------|----------------|
| `cindemir-seo-fixes.php` | **0 B** |
| `cindemir-expose-yoast-meta.php` | **0 B** |
| `cindemir-contact-fixes.php` | **0 B** |
| `cindemir-seo-fixes.php.bak-broken` | 45K (yedek) |

Bu yüzden Ahrefs'teki birçok hata **tekrar görünüyor**. Önce SSH ile deploy şart.

```bash
export CINDEMIR_SSH_KEY=~/.ssh/cindemirlaw_deploy
./fixes/deploy-ssh.sh
cd ~/public_html && wp cache flush && wp rocket clean --confirm 2>/dev/null || true
```

---

## Hata özeti (Error)

| Sorun | Adet | Çözüm |
|-------|------|-------|
| Page has broken image | 3924 | Çoğu **harici** (barobirlik 403, istanbulbarosu 301) — mu-plugin ile düzeltilemez |
| Image broken | 6 | 4× `chinese/russian/wp-content` → **seo-fixes v1.7.2** |
| 3XX redirect in sitemap | 63 | Sitemap'te canonical URL → **v1.7.2 sitemap filter** |
| Hreflang to non-canonical | 28 | WPML/Polylang ayarı + `fixes/LANG-REDIRECT.md` |
| 404 page | 3 | `contacts-2`, legacy image href → **v1.7.2 redirect** |
| Orphan page | 3 | İç link ekleme (manuel) |

## Uyarı özeti (Warning — indexable)

| Sorun | Adet | Not |
|-------|------|-----|
| Links to redirect | ~1001 | `?lang=` redirect zinciri; plugin + WPML |
| Meta description too short/long | ~46 | Çoğu blog; 14 sayfa zaten OK |
| Title too long/short | ~26 | Ahrefs export'tan sayfa listesi |
| H1 missing | 3 | `cindemir-law`, `link9`, `cindemir-law-3` |

## v1.7.2 değişiklikleri (repo)

1. `contacts-2` → `contacts/?lang=zh-hans` redirect
2. Sitemap: redirect hedef URL kullan (3XX in sitemap düşer)
3. `og:image` meta content rewrite (bozuk görsel meta)

## Düzeltilemeyen (harici)

- `d.barobirlik.org.tr` — 403 (hotlink koruması)
- `www.barobirlik.org.tr` — 403
- `idsb.tmgrup.com.tr` — 403

Bu görselleri wp-admin'den kaldırın veya yerel kopya yükleyin.

## Sonraki adımlar

1. **SSH deploy** (zorunlu)
2. WP Rocket cache temizle
3. Ahrefs re-crawl (24–48 saat)
4. Polylang `hide_default` / URL format ayarı
5. 0-byte dosya oluşturan agent'ı durdur — deploy sonrası `ls -la` kontrolü

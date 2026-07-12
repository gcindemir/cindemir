# Deploy: cindemirlaw.com SEO fixes

WordPress admin / FTP access is required. This repo has no live server credentials, so these files must be uploaded manually.

Full task brief: [`docs/cindemirlaw-cursor-gorev.md`](../docs/cindemirlaw-cursor-gorev.md)

## What this fixes

| Issue | Fix |
|-------|-----|
| EN URLs 301 → wrong RU pages | Cancel bad Redirection rules + mu-plugin guard |
| `/link9/` → `/press/` → external (2 hops) | Single 301 to `cindemir.av.tr` |
| Menu/footer hrefs to `/press/`, `/author/admin/` | v1.5 HTML rewrite + menu custom links |
| `www` → apex chain | `.htaccess` one-hop rule |
| Missing H1 (heritage, RU pages, etc.) | Inject H1 (content + full-page buffer) |
| Multiple H1 (home, long articles) | Keep first H1, demote rest to H2 |
| Orphan `/our-videos/`, `/appointment/` | Footer internal links |
| Socket footer: plain email, no social/baro badges | `cindemir-footer-fixes.php` (mailto, social icons, baro verify, TBB logos) |
| `/antimanual-assistant/`, `/embed-list/` | `noindex` via `wp_robots` + Yoast filter (v1.5.4) |
| Empty image `alt=""` | Fill from filename map (content + buffer) |
| Broken `/russian/` `/chinese/` image paths (404) | v1.5.6 `$url_replace` in mu-plugin |
| 14 pages meta description (reklamsız) | REST script or WP-CLI (see below) |
| `?lang=` redirect / sitemap | wp-admin dil eklentisi ayarı — [`LANG-REDIRECT.md`](LANG-REDIRECT.md) |

## Quick deploy order (2026-07-11 görevleri)

### 1. Upload mu-plugins (4 dosya)

| File | Target |
|------|--------|
| `fixes/mu-plugins/cindemir-seo-fixes.php` | `wp-content/mu-plugins/` (v1.5.7) |
| `fixes/mu-plugins/cindemir-expose-yoast-meta.php` | `wp-content/mu-plugins/` (REST için) |
| `fixes/mu-plugins/cindemir-contact-fixes.php` | `wp-content/mu-plugins/` (REST meta trigger) |
| `fixes/mu-plugins/cindemir-footer-fixes.php` | `wp-content/mu-plugins/` (socket footer: mail, social, baro) |

**Otomatik (önerilen):** Cursor Cloud Environment secrets → `./fixes/scripts/auto-deploy.sh`

### 2. Meta descriptions — 14 sayfa (Görev 1)

**Seçenek A — REST API (önerilen, mu-plugin yüklendikten sonra):**

```bash
export WP_USER='your-username'
export WP_APP_PASSWORD='xxxx xxxx xxxx xxxx'
./fixes/scripts/update-page-meta-descriptions.sh --dry-run   # önizleme
./fixes/scripts/update-page-meta-descriptions.sh             # uygula
```

**Seçenek B — WP-CLI (SSH):**

```bash
wp eval-file fixes/scripts/update-page-meta-descriptions-wpcli.php
```

**Seçenek C — Manuel:** `fixes/meta-descriptions/pages-14.json` veya `docs/cindemirlaw-cursor-gorev.md` tablosundan wp-admin → Yoast → Meta açıklaması.

### 3. `?lang=` redirect (Görev 3 — kritik)

[`fixes/LANG-REDIRECT.md`](LANG-REDIRECT.md) — Polylang/WPML ayarı + sitemap doğrulama.

### 4. Bozuk görseller (Görev 4)

[`fixes/BROKEN-IMAGES.md`](BROKEN-IMAGES.md) — mu-plugin v1.5.6 + Enfold'da kalıcı düzeltme.

### 5. Redirection plugin (mevcut)

1. WP Admin → **Tools → Redirection**
2. Delete rules for `/how-to-lift-entry-ban-to-turkey/` and `/exemptions-on-the-legislation-of-the-documents-in-turkey/`
3. Import `fixes/redirection/import-flatten.csv`

### 6. Optional: `.htaccess`

Prepend `fixes/htaccess/cindemir-redirect-snippets.conf` above `# BEGIN WordPress`.

### 7. Verify

```bash
UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'

# Meta description güncellendi mi (örnek)
curl -s -A "$UA" https://cindemirlaw.com/about-us/ | grep -oP '(?<=meta name="description" content=")[^"]*'

# EN post redirect olmamalı
curl -sI -A "$UA" --max-redirs 0 https://cindemirlaw.com/how-to-lift-entry-ban-to-turkey/ | head -3

# Bozuk görsel rewrite (mu-plugin deploy sonrası kaynak HTML'de)
curl -s -A "$UA" 'https://cindemirlaw.com/hakan/?lang=zh-hans' | grep -c 'chinese/wp-content'  # 0 olmalı
```

### 8. Clear caches

Purge WP / Cloudflare / host cache. Ahrefs'te yeniden crawl tetikle.

## Manual Enfold polish (optional)

On **Home**: keep “About Our Law Office” as H1; change Welcome / Our Articles / Our Services to H2.

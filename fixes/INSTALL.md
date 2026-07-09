# Deploy: cindemirlaw.com SEO fixes

WordPress admin / FTP access is required. This repo has no live server credentials, so these files must be uploaded manually.

## What this fixes

| Issue | Fix |
|-------|-----|
| EN URLs 301 → wrong RU pages | Cancel bad Redirection rules + mu-plugin guard |
| `/link9/` → `/press/` → external (2 hops) | Single 301 to `cindemir.av.tr` |
| Menu/footer hrefs to `/press/`, `/author/admin/` | v1.4 HTML rewrite + menu custom links |
| `www` → apex chain | `.htaccess` one-hop rule |
| Missing H1 (heritage, RU pages, etc.) | Inject H1 (content + full-page buffer) |
| Multiple H1 (home, long articles) | Keep first H1, demote rest to H2 |
| Orphan `/our-videos/`, `/appointment/` | Footer internal links |
| `/antimanual-assistant/`, `/embed-list/` | `noindex` |
| Empty image `alt=""` | Fill from filename map (content + buffer) |

## Steps (≈ 10 minutes)

### 1. Upload mu-plugin
1. In hosting File Manager / FTP open `wp-content/`
2. Create folder `mu-plugins` if missing
3. Upload `fixes/mu-plugins/cindemir-seo-fixes.php` into `wp-content/mu-plugins/`
4. No “Activate” needed — must-use plugins load automatically

### 2. Fix Redirection plugin rules
1. WP Admin → **Tools → Redirection**
2. Search and **delete** rules whose source is:
   - `/how-to-lift-entry-ban-to-turkey/`
   - `/exemptions-on-the-legislation-of-the-documents-in-turkey/`
3. Import `fixes/redirection/import-flatten.csv` (or add those 3 rules manually)
4. See `fixes/redirection/DELETE-AND-REPLACE.md`

### 3. Optional: `.htaccess`
Prepend `fixes/htaccess/cindemir-redirect-snippets.conf` above the `# BEGIN WordPress` block.

### 4. Verify
```bash
# Must stay on EN post (200), NOT jump to RU divorce/compensation
curl -sI -A 'Mozilla/5.0' -L --max-redirs 0 \
  https://cindemirlaw.com/how-to-lift-entry-ban-to-turkey/ | head

curl -sI -A 'Mozilla/5.0' -L --max-redirs 0 \
  https://cindemirlaw.com/exemptions-on-the-legislation-of-the-documents-in-turkey/ | head

# Single hop to av.tr
curl -sI -A 'Mozilla/5.0' https://cindemirlaw.com/link9/ | grep -i location

# H1 present
curl -s -A 'Mozilla/5.0' https://cindemirlaw.com/family-heritage/ | grep -c '<h1'
```

### 5. Clear caches
Purge WP cache / Cloudflare / host cache after deploy.

## Manual Enfold polish (optional, after mu-plugin)

In Enfold page builder on **Home**:
- Keep “About Our Law Office” as H1
- Change Welcome / Our Articles / Our Services special headings to **H2**

That matches what the mu-plugin does in HTML output, but editing the builder keeps the backend clean.

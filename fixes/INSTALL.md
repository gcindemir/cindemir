# Deploy: cindemirlaw.com SEO fixes

WordPress admin / FTP access is required. This repo has no live server credentials, so these files must be uploaded manually.

## What this fixes

| Issue | Fix |
|-------|-----|
| EN URLs 301 → wrong RU pages | Cancel bad Redirection rules + mu-plugin guard |
| `/link9/` → `/press/` → external (2 hops) | Single 301 to `cindemir.av.tr` |
| `www` → apex chain | `.htaccess` one-hop rule |
| Missing H1 (heritage, RU pages, etc.) | Inject H1 from page title map |
| Multiple H1 (home, long articles) | Keep first H1, demote rest to H2 |
| Orphan `/our-videos/`, `/appointment/` | Footer internal links |
| `/antimanual-assistant/`, `/embed-list/` | `noindex` |
| Empty image `alt=""` | Fill from filename map |
| Contact form stuck on "Sending…" | `cindemir-contact-fixes.php` fetch fallback + SEO buffer skip on `ajax=true` |
| WhatsApp (Joinchat) button missing | Debloat exclusions + fixed WhatsApp fallback link |

## Steps (≈ 10 minutes)

### 1. Upload mu-plugins

**Option A — automated (if FTP creds are in env):**
```bash
export CINDEMIR_FTP_USER='your-bluehost-cpanel-user'
export CINDEMIR_FTP_PASS='your-ftp-password'
bash fixes/deploy-ftp.sh
```

**Option B — manual:**
1. In hosting File Manager / FTP open `wp-content/`
2. Create folder `mu-plugins` if missing
3. Upload **both** into `wp-content/mu-plugins/`:
   - `fixes/mu-plugins/cindemir-seo-fixes.php` (update to v1.5.6)
   - `fixes/mu-plugins/cindemir-contact-fixes.php`
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

### 5. Verify contact form & WhatsApp
```bash
# Form AJAX fragment (should contain success text, not timeout)
curl -s -X POST -A 'Mozilla/5.0' https://cindemirlaw.com/contacts/ \
  -d 'ajax=true&avia_1_1=Test&avia_2_1=test@example.com&avia_3_1=1234567890&avia_4_1=Hello&avia_generated_form1=1' \
  | grep -o 'Your message has been sent'

# Joinchat tracking endpoint (was 404; mu-plugin returns 200)
curl -sI -A 'Mozilla/5.0' https://cindemirlaw.com/wp-json/joinchat/v1/track-click | head -1

# Main JS bundle must be 200 (stale cache referenced deleted hashes before)
curl -sI -A 'Mozilla/5.0' https://cindemirlaw.com/wp-content/cache/debloat/js/bcab09791fd07f1767ffc26019f5e896.js | head -1
```

### 6. Clear caches
Purge WP cache / Cloudflare / host cache after deploy.

### 7. Create Chinese Contacts page (WPML)
After uploading `cindemir-contact-fixes.php` **v1.2.0+**, run once:

```bash
curl -s "https://cindemirlaw.com/wp-json/cindemir/v1/setup-zh-contacts?key=wpml-setup-zh-2026"
```

Expected: `"status":"created"` and `/contacts/?lang=zh-hans` returns 200.

Alternative: upload and open `fixes/scripts/cindemir-create-zh-contacts.php` in site root, then visit:
`https://cindemirlaw.com/cindemir-create-zh-contacts.php?key=wpml-setup-zh-2026`
Delete the script after success.

## Manual Enfold polish (optional, after mu-plugin)

In Enfold page builder on **Home**:
- Keep “About Our Law Office” as H1
- Change Welcome / Our Articles / Our Services special headings to **H2**

That matches what the mu-plugin does in HTML output, but editing the builder keeps the backend clean.

# cindemirlaw.com crawl audit — 2026-07-09

Source: Ahrefs/Site Audit style CSV exports (uploaded).

## Snapshot

| Metric | Value |
|--------|------:|
| Internal URLs crawled | 452 |
| HTTP 200 | 378 |
| HTTP 301 | 74 |
| HTML 200 pages | 303 |
| Indexable (all URLs) | 298 |
| Languages (approx.) | EN ~232 · RU ~59 · ZH ~12 |

No 404/5xx in this crawl export. Non-200s are all **301 redirects**.

## Priority findings

### 1. Orphan pages (no internal links)
- https://cindemirlaw.com/our-videos/
- https://cindemirlaw.com/appointment/
- https://cindemirlaw.com/antimanual-assistant/

**Action:** Link from nav/footer if intentional; otherwise noindex or remove.

### 2. Missing H1 (17 pages)
Includes key brand/heritage pages and RU slug variants:
- `/who-is-hafiz-huseyin-husnu-efendi/`, `/family-heritage/`, `/cindemir-law/`, `/news-events/`
- RU paths: `/onas/`, `/stati/`, `/komanda/`, `/kontak/`, `/pod/`, `/nashiyurist/`, `/cindemir-law-3/`
- Utility: `/antimanual-assistant/`, `/embed-list/`
- Tag archives (also noindex): `/tag/injury|damage|turkish|prescription/`

**Action:** Add a single clear H1 per page (Enfold/WPML templates).

### 3. Multiple H1s (11 pages)
Homepage has **4 H1s** (`About Our Law Office`, `Welcome`, `Our Articles`, `Our Services`). Long articles (CISG EN/RU) use H1 for every section heading.

**Action:** One H1 per page; demote section titles to H2+.

### 4. Redirect chains (5)
| From | Via | Final |
|------|-----|-------|
| `/exemptions-on-the-legislation-of-the-documents-in-turkey/` | RU slug | RU slug `?lang=ru` |
| `http://www.cindemirlaw.com/` | `https://www…` | `https://cindemirlaw.com/` |
| `/link9/` | `/press/` | external `cindemir.av.tr` |
| long hash slug | `/fde1068e3/` | long hash `?lang=ru` |
| `/how-to-lift-entry-ban-to-turkey/` | RU slug | RU slug `?lang=ru` |

**Action:** Collapse to single 301 → final URL. Fix wrong-language redirects (EN slug → RU page).

### 5. Title length
- **109** titles > 60 chars (SERP truncation risk)
- **24** titles < 30 chars

### 6. Meta description length
- **38** > 160 chars · **12** < 70 chars  
- Duplicate description groups: **3**

### 7. Performance
- `/articles/` loads in **~5.8s** with **~17k content words** (very heavy archive)
- Several pages 800–1300ms

### 8. Images without alt
**72** empty alt attributes across **14** unique images (logo/banner variants most common).

### 9. Uncrawled outbound links
1071 links skipped: mostly external (`Restricted domain` 838) or `Blocked by robots.txt` (233) — expected for LinkedIn/Google etc., not site bugs.

## Files in this folder

| File | Contents |
|------|----------|
| `…_internal-urls_….csv` | All internal URLs |
| `…_internal-html-200-url_….csv` | HTML 200 pages + SEO fields |
| `…_orphan-pages_….csv` | Orphans |
| `…_redirect-chains_….csv` | Multi-hop redirects |
| `…_uncrawled-links_….csv` | Skipped links |
| `…_alt-texts_….csv` | Image alt audit |

## Suggested next steps
1. Fix orphan + missing H1 + homepage multi-H1
2. Flatten redirect chains / wrong-language 301s
3. Trim `/articles/` archive weight
4. Add alt text on logo/banner images
5. Then decide: redesign, `.co` redirect, or WP cleanup

## Fixes prepared (2026-07-09)

Deployable package in [`fixes/`](../../fixes/INSTALL.md):

- `fixes/mu-plugins/cindemir-seo-fixes.php` — H1, orphans, alts, redirect guards
- `fixes/redirection/` — rules to delete/import in Redirection plugin
- `fixes/htaccess/` — www→apex + press/link9 one-hop rules

**Not yet live:** WP/FTP credentials were not available in this environment. Upload per `fixes/INSTALL.md`.

## Additional crawl exports (links / images) — analyzed 2026-07-09

| Report | Count | Priority |
|--------|------:|----------|
| Links → 4xx | 5 | High (1 real 404 on cindemir.av.tr) |
| Mixed content (http images) | 2 | High |
| Links → redirect URLs | 999 (75 unique targets) | Medium |
| Images without alt | 72 | Medium (partially fixed live) |
| External links | 1412 | Low (mostly social / Google) |
| Blocked by robots.txt | 233 | Info (LinkedIn etc.) |

### Fix now → **done live (2026-07-09 evening)**
1. **404:** post 472 content updated: `howtodivorce.html` → `https://cindemir.av.tr/en/how-to-divorce-in-turkey/`.
2. **Mixed content:** posts 2256 & 2247 image URLs upgraded to `https://cindemir.av.tr/...`.
3. **Internal links to redirects (top volume):**
   - Menu items 35 / 2673 / 2678 (`Press`) converted to custom links → `https://cindemir.av.tr/en/we-are-in-news/`
   - mu-plugin **v1.4.0** rewrites remaining `/press/`, `/link9/`, `/author/admin/` hrefs in HTML output + `author_link` filter
   - `/link9/` and `/press/` still 301 one-hop to the same final URL (safe for bookmarks)
4. **External 403:** `barobirlik.org.tr` left as-is (bot-blocked; not a site bug)

### Live verification (nocache)
| Check | Result |
|-------|--------|
| `/press/` hrefs on sample pages | **0** (rewritten to av.tr news) |
| `/author/admin/` hrefs | **0** (rewritten to `/`) |
| `/family-heritage/` H1 | present (`cindemir-seo-h1`) |
| divorce 404 / mixed `http://` | gone |
| `/how-to-lift-entry-ban-to-turkey/` | **200** EN |
| `/link9/`, `/press/` | **301** → av.tr news |

### Remaining (lower Ahrefs impact)
- ~400 links still point at RU hash/`fde…` or Cyrillic slugs that 301 to `?lang=ru` (WPML canonicalization). Prefer linking canonical RU URLs with `?lang=ru` in menus/widgets when editing content.
- Homepage / long CISG articles: keep one H1 (plugin demotes extras in `the_content`; Enfold builder home already one H1).
- Title/meta length polish in Yoast (109 long titles).
- Optional: disable author archives in Yoast so `/author/admin/` 301 can be removed entirely.

### Note on cindemir.av.tr
Outbound only. No deploy was made to av.tr. Broken `howtodivorce.html` fixed on the **linking** side (cindemirlaw.com).


## Completion pass (2026-07-09 night) — v1.5.1

mu-plugin now:
- Rewrites **all** Ahrefs "links to redirect" internal targets to their final URLs (incl. WPML `?lang=ru|zh-hans`, `/link2-4`, short `fde…` aliases)
- Disables author archives (`/author/admin/` → home)
- Trims `<title>` tags longer than 60 chars in HTML output
- Injects missing H1s from title when theme omits them
- Rewrites known external redirect URLs (mersis, turkodeme, istanbul barosu www, mixed-content image)

Still outside this site's control:
- `cindemir.av.tr/howtodivorce.html` remains 404 on av.tr (no longer linked from cindemirlaw.com)
- External 403s (barobirlik) / social robots blocks

Also v1.5.1: generic `fde…` / Cyrillic path → `?lang=ru` href+redirect resolver.


## Google Search Console findings (2026-07-09)

Property: `sc-domain:cindemirlaw.com`

### Indexing
| Status | Count |
|--------|------:|
| Indexed | 95 |
| Not indexed | 410 |

**Why not indexed**
| Reason | Pages |
|--------|------:|
| Crawled – currently not indexed | 194 |
| Discovered – currently not indexed | 186 |
| Not found (404) | 13 |
| Page with redirect | 9 |
| Excluded by noindex | 6 |
| Redirect error | 2 |

### Other GSC
- HTTPS: OK (0 issues)
- Manual actions / Security: OK
- Core Web Vitals: insufficient data (desktop + mobile)
- Sitemaps: apex OK (~303 URLs); **failed** entry `http://www.cindemirlaw.com/sitemap_index.xml` (remove in GSC)
- Performance (3 mo): 142 clicks · 32.1K impressions · 0.4% CTR · avg pos 15.5

### Live bugs found while probing GSC candidates
- `/russian/` and `/chinese/` → **HTTP 500** (old physical dirs / broken WP). Fixed in mu-plugin **v1.5.3** → `/?lang=ru` and `/?lang=zh-hans`
- `/zh/`, `/zh-hans/` → 404 → same ZH home redirect in v1.5.3
- Sitemap still lists `/press/?lang=ru|zh-hans` (those 301 to av.tr news)

### Follow-up applied
- mu-plugin **v1.5.3**: exclude `/press/` (and other redirect paths) from Yoast XML sitemap entries.
- GSC actions: remove failed `http://www…` sitemap if UI allows; request indexing for key URLs; start validation on 404/redirect-error groups.

## Live re-check (2026-07-10)

Verified against production after mu-plugin deploy:

| Ahrefs CSV issue | Live status |
|------------------|-------------|
| Missing H1 (13 indexable) | Fixed — pages now emit H1 |
| Long titles (108) | Mitigated — output titles trimmed ≤60 |
| Orphans our-videos / appointment | Linked from footer nav |
| Orphan antimanual-assistant | Intentionally unlinked + noindex (v1.5.4 clears Yoast index conflict) |
| 4xx howtodivorce.html | Fixed — points to `cindemir.av.tr/en/how-to-divorce-in-turkey/` |
| External 403 (barobirlik, tmgrup) | Not site bugs |
| Redirect chains link9/press/EN→RU | Flattened / cancelled |
| www http→https→apex (2 hops) | Still needs `.htaccess` one-hop (snippet in `fixes/htaccess/`) |
| Empty alts (sample pages) | 0 empty on home/articles/cindemir |

Fresh Ahrefs Site Audit crawl still needed after domain verification / login.


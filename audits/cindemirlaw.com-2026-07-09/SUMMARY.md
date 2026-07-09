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

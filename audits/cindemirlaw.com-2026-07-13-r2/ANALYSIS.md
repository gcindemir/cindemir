# Ahrefs audit — cindemirlaw.com (13 Jul 2026, round 2)

**Crawl context:** Pre-v1.8.1 deploy. Live site still reports `cindemir-seo-fixes 1.7.2` and `d.barobirlik.org.tr` in HTML.

## Error summary

| Issue | Count | v1.8.1 fix |
|-------|------:|------------|
| Page has broken image | ~305 pages | Local TBB badge + HTML rewrite (`d.barobirlik` → `/uploads/cindemir/tbb_amblem_60.png`) |
| Image broken (asset URLs) | 6 | Legacy `chinese/wp-content` paths + barobirlik + `idsb.tmgrup` strip |
| 3XX redirect in sitemap | 63 | `filter_sitemap_entry` emits `?lang=ru` destination for Cyrillic slugs |
| Hreflang → non-canonical | 4 pages / 28 links | Strip duplicate EN alternates; `contacts-2` → `contacts` |
| Canonical points to 4XX | 1 | `contacts/?lang=zh-hans` canonical fixed (`contacts-2` → `contacts`) |
| 404 pages | 3 | Legacy images + `contacts-2/?lang=zh-hans` (WPML slug) |
| Orphan indexable pages | 3 | Footer orphan nav (`our-videos`, `appointment`; `antimanual-assistant` is noindex) |
| Page links to broken page | 2 | Resolved when barobirlik + contacts-2 fixed |

## Root causes

1. **Barobirlik hotlink 403** — AhrefsBot blocked by `d.barobirlik.org.tr`; appears on every footer (~305 inlinks each).
2. **WPML contacts slug** — Chinese translation canonical still `contacts-2` (404) while page URL is `contacts/?lang=zh-hans`.
3. **Sitemap stale URLs** — Yoast lists bare Cyrillic slugs that 301 to `?lang=ru` versions.
4. **Duplicate hreflang EN** — WPML emits both bare and `?lang=en` alternates; non-canonical flagged.

## Deploy required

v1.8.1 is in repo but **not live**. Run in cPanel Terminal:

```bash
curl -fsSL https://raw.githubusercontent.com/gcindemir/cindemir/cursor/cindemirlaw-seo-tasks-d204/fixes/scripts/server-deploy-from-github.sh | bash
curl -s "https://cindemirlaw.com/wp-json/cindemir/v1/fix-ahrefs?key=seo-pack-2026"
bash fixes/scripts/verify-live.sh   # expect marker 1.8.1, no d.barobirlik in HTML
```

Alternative bootstrap (single file):

```bash
curl -fsSL https://raw.githubusercontent.com/gcindemir/cindemir/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/cindemir-force-upgrade.php \
  -o ~/public_html/wp-content/mu-plugins/cindemir-force-upgrade.php
curl -s "https://cindemirlaw.com/?cindemir_upgrade=seo-pack-2026" >/dev/null
```

Or pull via existing REST after contact-fixes ≥1.2.1:

```bash
curl -s "https://cindemirlaw.com/wp-json/cindemir/v1/apply-seo-meta?key=seo-pack-2026&pull=1"
```

## Post-deploy expectations

- `cindemir-seo-fixes 1.8.1` in HTML source
- No `d.barobirlik.org.tr` in homepage HTML
- `contacts/?lang=zh-hans` canonical → `contacts/?lang=zh-hans` (not contacts-2)
- Post sitemap entries use final `?lang=ru` URLs
- Re-crawl Ahrefs after 24–48h cache expiry

## Notices / warnings (lower priority)

- External 3XX/4XX, meta length, single inlink, redirect chains — mostly informational or post-fix residuals.
- `press/` in sitemap redirects externally — excluded in v1.8.1 via `exclude_press_from_sitemap`.

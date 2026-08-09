# Rich results scan — 2026-08-09

Live plugin at scan time: **cindemir-seo-fixes 1.9.91 → 1.9.92**

## Scope
- Sitemap: 222 URLs (`post-sitemap.xml` + `page-sitemap.xml`)
- Sampled EN pages/posts + RU/ZH via `hreflang`
- Checked JSON-LD types: BreadcrumbList, Article, Organization, WebSite, WebPage, LegalService/LocalBusiness, Person, FAQPage

## Breadcrumbs (GSC: `itemListElement` missing)
| URL class | Result |
|-----------|--------|
| `/?lang=ru`, `/?lang=zh-hans`, `/` | No BreadcrumbList (intentional) — **OK** |
| Inner EN/RU/ZH pages | Single BreadcrumbList with ≥2 ListItems — **OK** |
| Orphan `WebPage.breadcrumb` | Fixed in 1.9.91 |

GSC examples from the report are covered. User should keep **Validate fix** running in Search Console.

## Other rich-result findings
| Type | Status | Notes |
|------|--------|-------|
| **Article** | Fixed in **1.9.92** | Many posts had `"image": null` while `og:image` existed → backfill ImageObject from featured/Yoast/og:image |
| Organization / LegalService | Present sitewide | NAP fields present |
| FAQPage | Not used | No FAQ rich result markup found |
| Person | Present on articles | Author node present |
| Heavy entity graph on articles | Known / deferred | Article + Org + LegalService + Person together; left as-is (not a hard GSC error) |

## Follow-ups (optional)
1. Add real featured images per post where og:image is a generic stock photo.
2. Soft-trim LegalService from pure article templates if Google later flags “spammy structured data”.
3. Sitemap does not list `?lang=` URLs; discovery relies on hreflang (usually fine).

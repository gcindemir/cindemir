# CLS fix — cindemirlaw.com (from Layout Shift Culprits report)

**Score:** 0.239 (needs improvement; poor ≥ 0.25)  
**Dominant node:** `div.entry-content` (0.239) — usually *aggregates* header/font/image shifts into the content paint.

## Root causes (verified live)

| Culprit | Problem |
|---------|---------|
| Header logo `<img>` | `loading="lazy"` + SVG placeholder; attrs `300×100` but file is **300×300**; CSS forces ~44px display |
| `span.logo a::after` | Injects “Cindemir Law Office” after first paint → menu/header reflow |
| Web fonts | Enfold “google webfont font replacement” / Cormorant + Open Sans swap |
| `contain-intrinsic-size: 3000×1500` | WP core auto-sizes guess → huge reserved box then collapse |

## Fixes shipped

1. **Plugin module** `includes/class-cls-fix.php` — critical CSS + HTML buffer (eager logo, correct 300×300)  
2. **Paste-ready CSS** `assets/cls-fix-enfold.css` — Enfold Quick CSS (works without plugin activate)

## Manual WP steps (cindemirlaw.com)

1. **WP Rocket / LazyLoad** → exclude logo: `logoicon` or `#header .logo img`  
2. **Enfold → Import/Export → Custom CSS Code** → paste `cls-fix-enfold.css`  
3. Prefer **HTML brand text** next to logo instead of `::after` (remove `content:` rule once done)  
4. Enfold fonts: use **system stack** or host WOFF2 locally with `font-display: optional`  
5. Re-test article URL in PageSpeed Insights (same URL as report)

## Also apply on cindemir.av.tr

Same logo/lazy pattern may exist; activate `cindemir-geo-fixes` (includes CLS module) after backup.

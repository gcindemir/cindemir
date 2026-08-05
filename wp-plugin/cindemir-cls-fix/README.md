# Cindemir CLS Fix (live on cindemirlaw.com)

Installed & active on production (v1.4.5).

## What it does
- CLS: fixed header/logo slot + `font-display:optional` for icon/web fonts
- LCP: WebP hero/logo preloads and HTML URL swaps
- Debloat CSS proxy (`/?cindemir_debloat_css=…`): rewrites JPEG backgrounds → WebP and forces `font-display:optional` so late async CSS cannot cause font-swap CLS
- WP Rocket: hooks `rocket_buffer` so cached HTML stays optimized

## Related live settings (not in this plugin)
- Debloat: **Inline Optimized CSS** disabled (HTML ~130KB instead of ~680KB)
- WP Rocket: Optimize CSS Delivery disabled
- Imagify: WebP display enabled

Do not remove without re-measuring PageSpeed.

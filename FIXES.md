# Fixes applied (after backup)

## Backup
`backups/2026-08-04-pre-fix/` — live HTML + parsed JSON-LD for TR/EN/RU/ZH homes, team, contact.

## Cannot write live WP from this agent
REST API returns 401 for create/edit. Fixes ship as installable plugin:

**`wp-plugin/cindemir-geo-fixes/`**

Install in WordPress admin (zip upload) or WP-CLI to apply on production.

## What the plugin fixes
1. Duplicate/conflicting Organization + LegalService JSON-LD  
2. Locale market signals (TR/RU/EN/ZH)  
3. Weak front-page meta/OG descriptions  
4. Empty alt on language flags / content images (safe cases)  
5. Creates intent landing pages + FAQ schemas  

## Not fixed (on purpose)
- Semrush “Unknown host” / inflated broken-link counts  
- Mass title-length cleanup across 1600+ pages  
- YouTube lazyload JS `ID` placeholder (runtime template, not a real img)

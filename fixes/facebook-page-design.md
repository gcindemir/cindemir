# Facebook Page Design — Cindemir Law Office

Inspired by **cindemirlaw.com** (Enfold teal brand).  
**Page only:** `100066585793269` — do not edit personal profiles.

## Brand tokens (from live site)

| Token | Hex | Use |
|---|---|---|
| Primary | `#336666` | Footer / main teal |
| Secondary | `#286060` | Darker teal |
| Accent | `#8bba34` | Lime accent line |
| Text | `#6e6d6e` | Body |
| Surface | `#ffffff` / `#f8f8f8` | Cards / bg |

**Fonts on site:** Cormorant Garamond (headings) + Open Sans (body).  
**FB cover type:** serif wordmark (matches site authority).

## Visual assets

| Asset | File / URL |
|---|---|
| **Cover** | `fixes/assets/fb-cover-cindemir.png` — Istanbul skyline + “CINDEMIR LAW OFFICE · Istanbul · Turkish & International Law” (no founding-year claim) |
| **Profile** | Site logo: `https://cindemirlaw.com/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg` |
| **Hero reference** | `https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg` |

## Page copy (aligned with site)

**Intro / Bio**
```
Cindemir Law Office — Istanbul. Turkish & international counsel for foreign clients: company formation, deportation, divorce, debt recovery, criminal record. EN / RU.
https://cindemirlaw.com
```

**About (long)**
```
Cindemir Law Office is an independent Istanbul firm advising Turkish and international clients. English site: https://cindemirlaw.com

Key guides:
• Company formation — /opening-a-company-in-turkey-for-foreigners/
• Deportation — /deportation-law-in-turkey/
• Uncontested divorce — /consensual-divorce-in-turkey-uncontested-divorce/
• Debt recovery — /debt-recovery-in-turkey/
• Criminal record — /getting-criminal-record-in-turkey/

Languages: Turkish, English, Russian.
Address: Ritim İstanbul 44/18, Maltepe / Istanbul
WhatsApp: +90 532 568 06 47
```

**CTA:** WhatsApp → `+90 532 568 06 47` (or Contact → `https://cindemirlaw.com/contacts/`)

## Content cadence (calendar)

- **5 English posts / day** from `cindemirlaw.com` articles  
- Times (Istanbul): **10:00 · 12:30 · 15:00 · 17:30 · 20:00**
- Format: 2–4 sentence summary + single canonical URL (no `?lang=`)  
- Full calendar: `fixes/facebook-content-calendar.json` (~41 days / ~205 posts after day 1)
- **Schedule all queued:** `python3 fixes/scripts/fb-schedule-posts.py`
- Rebuild from sitemap: `python3 fixes/scripts/fb-list-articles.py && python3 fixes/scripts/fb-build-calendar.py`

## Post template

```
{1–2 sentence hook for foreign clients}

{1 sentence on process / risk / next step}

Full guide:
{canonical URL}
```

Optional footer (every 5th post): `Istanbul counsel · English & Russian · cindemirlaw.com`

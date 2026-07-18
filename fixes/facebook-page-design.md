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
| **Cover** | `fixes/assets/fb-cover-cindemir.png` — Istanbul skyline + “CINDEMIR LAW OFFICE · Since 1892” |
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

## Content cadence

- **5 English posts / day** from `cindemirlaw.com` articles  
- Format: 2–4 sentence summary + single canonical URL (no `?lang=`)  
- Calendar: `fixes/facebook-content-calendar.json`  
- Publish script: `fixes/scripts/fb-publish-day.py --day N`

## Post template

```
{1–2 sentence hook for foreign clients}

{1 sentence on process / risk / next step}

Full guide:
{canonical URL}
```

Optional footer (every 5th post): `Istanbul counsel · English & Russian · cindemirlaw.com`

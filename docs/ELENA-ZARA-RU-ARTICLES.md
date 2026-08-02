# Elena Zara author byline + RU article SEO (1.9.67)

## Goal
- Attribute Russian articles to Av. Elena Zara in a bar-safe way
- Fix RU SEO author meta/schema (`admin` → factual author name) and title truncation

## Scope
- Applies when `is_singular('post')` and front language is `ru`
- ~56 Russian posts via `/wp-json/wp/v2/posts?lang=ru`

## Author byline (RU) — identity only
> Ав. Елена Зара — адвокат Стамбульской коллегии адвокатов · [Команда бюро](/team/?lang=ru)

**Not included** (reklam yasağı / solicitation risk): photo, email/mailto, education pitch, “consultancy since …” language, SERP author promotion.

## SEO fixes
1. `meta name="author"` → `Av. Elena Zara` (factual attribution)
2. Schema.org `Person` name → `Av. Elena Zara` only (no image / jobTitle / worksFor enrichment)
3. Title shortening keeps more of the Cyrillic title
4. Short meta descriptions padded with **neutral** Turkish-law topic text (no author/firm pitch)

## Deploy
```
curl -sS -X POST 'https://cindemirlaw.com/wp-json/cindemir/v1/pull-plugins' \
  -H 'Content-Type: application/json' \
  -d '{"key":"seo-pack-2026"}'
```

## Verify
- Sample RU post has `cindemir-elena-bio__byline` and `Ав. Елена Зара`
- No `mailto:elena.zara`, no portrait image in the byline block
- No “консультирует” / education marketing sentence
- `<meta name="author" content="Av. Elena Zara" />`
- Schema Person name is Elena Zara without promotional Person fields
- Version marker `1.9.67`

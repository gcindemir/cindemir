# Elena Zara bio + RU article SEO (1.9.66)

## Goal
- Add Av. Elena Zara short biography to Russian single articles
- Fix RU SEO author attribution and overly truncated titles/descriptions

## Scope
- Applies when `is_singular('post')` and front language is `ru` (WPML/URL/cookie)
- ~56 Russian posts via `/wp-json/wp/v2/posts?lang=ru`

## Bio (RU)
> Ав. Елена Зара — адвокат Стамбульской коллегии адвокатов. Окончила Современную гуманитарную академию (международное право) и юридический факультет Стамбульского университета Айдын. Владеет русским, турецким и английским языками. С 2014 года консультирует по правовым вопросам Турции, России и стран СНГ.

## SEO fixes
1. `meta name="author"` → Elena Zara
2. Schema.org `Person` nodes (author) → Elena Zara + `/team/` URL
3. Title shortening keeps more of the article title (separator + brand)
4. Meta description padded with Elena context when too short

## Deploy
```
curl -sS 'https://cindemirlaw.com/wp-json/cindemir/v1/self-upgrade?key=seo-pack-2026&source=github'
# or
curl -sS 'https://cindemirlaw.com/wp-json/cindemir/v1/pull-plugins?key=seo-pack-2026&source=github'
curl -sS 'https://cindemirlaw.com/wp-json/cindemir/v1/purge-cache?key=seo-pack-2026'
```

## Verify
- Sample RU article HTML contains `cindemir-author-bio` and `Ав. Елена Зара`
- `<meta name="author" content="Elena Zara" />`
- Schema Person name is Elena Zara
- Title tag is not cut mid-word before `| Cindemir`
- Version marker `1.9.66` / `ELENA_ZARA_RU_BIO_20260718`

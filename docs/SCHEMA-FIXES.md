# Schema fixes (1.9.70)

## Fixed
- RU Article author Person → Av. Elena Zara, `/team/?lang=ru`, no Gravatar
- Organization logo on publisher/org nodes
- Homepage `WebSite` when Yoast omits it
- **All WP pages** (about, contact, articles, team, services, press, …) get `WebPage` + Organization when missing
- SiteNavigationElement `@id` aligned to real `url`; press nav no longer points at cindemir.av.tr

## Bar-safe
No jobTitle / worksFor / attorney portrait enrichment in Person schema.

# Schema fixes (1.9.71)

## Fixed
- RU Article author → Av. Elena Zara, `/team/?lang=ru`, no Gravatar
- Organization logo
- Homepage `WebSite`
- **All WP pages** get `WebPage` + Organization (about, contact, articles, team, services, press, …)
- SiteNavigationElement `@id` cleaned; press nav uses local `/press/`
- Page detection uses **body class** only (Enfold CSS contains `.single-post` globally)

## Bar-safe
No jobTitle / worksFor / attorney portrait in Person schema.

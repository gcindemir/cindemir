# Schema fixes (1.9.68)

## Problems fixed
- RU Article `author` Person pointed at homepage + WP admin Gravatar
- `admin` author leftovers on non-RU content → firm Organization
- Homepage Organization missing `logo`
- Team/services pages had only `SiteNavigationElement` (no WebPage/Organization)

## Approach (bar-safe)
- RU author: name + `/team/` URL only (no jobTitle/worksFor/portrait)
- Organization: name, url, logo
- Front: add `WebSite` when missing
- Team/services: add `WebPage` linked to Organization

## Verify
```
# RU article author.url should be /team/?lang=ru and no gravatar
curl -sS 'https://cindemirlaw.com/pravovoy-status-dao-v-turtsii/?lang=ru' | rg -o '"author":\{[^}]+\}'
```

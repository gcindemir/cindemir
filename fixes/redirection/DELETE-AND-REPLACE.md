# Redirection plugin — rules to DELETE (Tools → Redirection → Import/export or search)

These source URLs currently 301 to the WRONG language/topic. Delete them so the real EN posts (IDs 2377, 2356) can load.

| Source | Current (wrong) target | Correct behaviour |
|--------|------------------------|-------------------|
| `/how-to-lift-entry-ban-to-turkey/` | RU “компенсации…” (car accident compensation) | Serve EN post ID 2377 |
| `/exemptions-on-the-legislation-of-the-documents-in-turkey/` | RU “как развестись…” (divorce) | Serve EN post ID 2356 |

Also flatten these to a **single** 301 (delete intermediate hops):

| Source | Final target |
|--------|----------------|
| `/link9/` | `https://cindemir.av.tr/en/we-are-in-news/` |
| `/press/` | `https://cindemir.av.tr/en/we-are-in-news/` |
| `/fde1068e3/` | canonical long RU divorce URL with `?lang=ru` |
| long hash slug (see audit CSV) | same canonical RU divorce URL |

Host-level (preferred over plugin):

| Source | Final |
|--------|-------|
| `http://www.cindemirlaw.com/*` | `https://cindemirlaw.com/$1` |
| `https://www.cindemirlaw.com/*` | `https://cindemirlaw.com/$1` |

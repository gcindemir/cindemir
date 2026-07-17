# cindemirlaw.com — öncelik yürütme (1–4)

Tarih: 16 Jul 2026. Kaynak: GSC backlink + Ahrefs + site audit.

## 1) Hub → P0 internal (plugin v2.2.0)

Canlı (deploy sonrası doğrula):

| Hub / kaynak | P0 hedefler |
|---|---|
| `/services/`, `/about-us/`, `/nashiyurist/`, `/onas/` | 5 P0 guide listesi |
| Related posts (divorce/company/debt/criminal/deportation cluster) | ilgili P0 |

P0:
- `/opening-a-company-in-turkey-for-foreigners/`
- `/consensual-divorce-in-turkey-uncontested-divorce/`
- `/deportation-law-in-turkey/`
- `/debt-recovery-in-turkey/`
- `/getting-criminal-record-in-turkey/`

Marker: `<!-- cindemir-p0-hub-links -->` + `<!-- cindemir-index-hygiene 2.2.0 -->`

## 2) Lang query temizliği

- Language switcher zaten path-aware (v2).
- v2.2: paired cornerstone’da `?lang=ru` → dedicated RU (`/onas/`, `/stati/`, …).
- Homepage `/?lang=ru` ve `/?lang=zh-hans` tek dil sürümü olarak kalır (ayrı RU home yok).
- Amaç: GSC’deki “her şey `/?lang=ru|zh-hans`’a akıyor” örüntüsünü kırmak.

## 3) Directory retarget + 10 editorial outreach

### Directory — website alanını P0’a çevir

| Platform | Website / services URL | Bio kısa |
|---|---|---|
| HG.org | P0 #1 company veya about-us | Istanbul counsel; foreigners, deportation, divorce, debt |
| ProvenExpert | P0 #1 | aynı |
| GoodFirms | P0 #1 | aynı |
| Lawzana | P0 #3 deportation veya #1 | aynı |
| avukatistan / avukatno | about-us + 1 P0 | TR listing |
| BCG Search | about-us | mevcut profili güncelle |
| LinkedIn Featured | P0 #1, #2, #3 | 3 featured link |

**Website (kopyala):**  
`https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/`

**Bio (EN):**  
Cindemir Law Office (Istanbul) advises foreign individuals and companies on Turkish corporate, immigration, family, and debt-recovery matters. English and Russian speaking counsel.

### 10 editorial outreach (hazır)

Detaylı metinler: `fixes/outreach-10-editorial.md`

1. Expatista / living-in-Turkey resource — company formation  
2. Invest in Turkey / FDI blog — company for foreigners  
3. Divorce abroad guide site — uncontested divorce  
4. Immigration/expat forum resource page — deportation  
5. Trade credit / B2B collection blog — debt recovery  
6. Police clearance / apostille guide — criminal record  
7. Guest post: “Doing business in Turkey 2026”  
8. Guest post: “Foreign divorce recognition & uncontested divorce in Turkey”  
9. Journalist quote: airport detention / deportation  
10. Journalist quote: foreign company directors / nominee risk  

## 4) Cluster sadeleştirme

### Canlı 301 (plugin)

| From | To |
|---|---|
| `/obtaining-a-criminal-record-certificate-in-turkey-a-step-by-step-guide/` | `/getting-criminal-record-in-turkey/` |
| `/debt-collection-service/` | `/debt-recovery-in-turkey/` |
| `/methods-of-debt-collection-in-the-light-of-turkish-law/` | `/debt-recovery-in-turkey/` |
| `/establishing-a-commercial-enterprise-in-turkey/` | `/opening-a-company-in-turkey-for-foreigners/` |

### Redirect etme — destek olarak tut + internal P0 link

- `/how-to-divorce-in-turkey/`, `/consequences-of-a-divorce-decision-in-turkey/`, custody → P0 divorce  
- `/can-russian-establish-a-company-in-turkey/`, free-trade, branch → P0 company  
- `/how-to-lift-entry-ban-to-turkey/`, airport-detention, family-unity deportation → P0 deportation  
- Country-specific judgment enforcement pages → keep (unique), hub = `/enforcement-of-a-foreign-decision-in-turkey/`

### Sonraki (manuel içerik)

- Country tenfiz sayfalarına ortak intro + hub link  
- Crypto cluster: 1 hub + 5 destek, kalanını merge  
- `.av.tr` EN ayna ↔ `.com` canonical politikası

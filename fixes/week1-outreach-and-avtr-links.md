# Hafta 1 playbook — outreach metinleri + cindemir.av.tr link yerleşimi

Hedef: P0 deep link’ler. Homepage’e yeni link yok.

P0 hedefler:
1. `https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/`
2. `https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/`
3. `https://cindemirlaw.com/deportation-law-in-turkey/`
4. `https://cindemirlaw.com/debt-recovery-in-turkey/`
5. `https://cindemirlaw.com/getting-criminal-record-in-turkey/`

---

## 1) cindemir.av.tr — link yerleşim planı

Not: `.av.tr` üzerinde birçok EN aynası var (`/en/deportation-law-in-turkey/`, `/en/debt-recovery-in-turkey/`, `/en/consensual-divorce…` vb.) ama **cindemirlaw.com’a contextual link yok**. GSC’deki 3 link büyük ihtimalle schema/anasayfa. Footer yetmez — ilgili makale gövdesine 1 cümle + link.

### A. Hemen eklenecek contextual linkler (öncelik sırası)

| Kaynak sayfa (cindemir.av.tr) | Hedef (cindemirlaw.com) | Anchor | Yerleşim |
|---|---|---|---|
| `/anonim-sirket-kurmak-turk-ticaret-kanunu-kapsaminda-sirket-kurma-ve-esas-sozlesme-duzenleme-rehberi-2/` | P0 #1 company for foreigners | `yabancılar için Türkiye’de şirket kurulumu (EN rehber)` | Sonuç / “ilgili okuma” paragrafı |
| `/yabanci-sirket-muduru-calisma-izni-sorunu-ve-cozumu/` | P0 #1 | `company formation in Turkey for foreigners` | İlgili bölüm sonu |
| `/en/company-establishment-for-russian-nationals-in-turkey/` | P0 #1 + isteğe P1 `/can-russian-establish-a-company-in-turkey/` | `English guide for foreigners` | Intro veya CTA |
| `/sinirdisi-kararinin-iptali/` | P0 #3 deportation | `deportation law in Turkey` | Sonuç paragrafı |
| `/en/deportation-law-in-turkey/` | P0 #3 | `Cindemir Law — deportation in Turkey` | “International clients” kutusu / son paragraf (**aynı konunun EN kanoniği .com**) |
| `/en/consensual-divorce-in-turkey-uncontested-divorce/` | P0 #2 | `uncontested divorce in Turkey` | Son paragraf → .com kanonik |
| `/en/how-to-divorce-in-turkey/` | P0 #2 | `consensual divorce guide` | İlgili okuma |
| `/en/debt-recovery-in-turkey/` | P0 #4 | `debt recovery in Turkey` | Son paragraf → .com |
| `/en/who-can-collect-debt-in-turkey/` | P0 #4 | `debt collection Turkey` | CTA |
| `/en/to-obtain-criminal-record-certificate-in-turkey/` | P0 #5 | `getting a criminal record in Turkey` | Son paragraf → .com |
| `/en/criminal-record-certificate-in-turkey-how-to-obtain-and-legalize-a-turkish-police-clearance-certificate/` | P0 #5 | `criminal record certificate Turkey` | Intro cross-link |

### B. Hub sayfalar (tek seferlik)

| Sayfa | Ne eklenir |
|---|---|
| `/hizmetlerimiz/` | “Uluslararası / İngilizce bilgilendirme:” altında 5 P0 link (liste, dofollow) |
| `/en/services/` | Aynı 5 P0 (EN etiketler) |
| `/hakkimizda/` | 1 cümle + `https://cindemirlaw.com/about-us/` (branded) — homepage değil |
| `/en/about/` | `https://cindemirlaw.com/about-us/` |

### C. Yapıştır-hazır paragraf şablonları

**TR (şirket → P0 #1):**
> Yabancı yatırımcılar için süreç özeti (İngilizce): [yabancılar için Türkiye’de şirket kurulumu](https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/).

**TR (sınır dışı → P0 #3):**
> English overview for international clients: [deportation law in Turkey](https://cindemirlaw.com/deportation-law-in-turkey/).

**EN (.av.tr ayna → .com kanonik):**
> For the full English guide on our international site, see [deportation law in Turkey](https://cindemirlaw.com/deportation-law-in-turkey/).

**EN (boşanma):**
> Related guide: [uncontested / consensual divorce in Turkey](https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/).

**EN (alacak):**
> International clients: [debt recovery in Turkey](https://cindemirlaw.com/debt-recovery-in-turkey/).

**EN (adli sicil):**
> Step-by-step English guide: [getting a criminal record certificate in Turkey](https://cindemirlaw.com/getting-criminal-record-in-turkey/).

### D. Teknik kurallar
- `rel="nofollow"` **kullanma** (kendi siteniz).
- Yeni linkleri **gövdeye** koy; sidebar widget / sitewide footer’a 5 URL yağdırma.
- `.av.tr` EN ayna ile `.com` aynı metinse uzun vadede: `.com` = EN canonical, `.av.tr` EN’de “see international site” + gerekirse `link rel=canonical` düşünülür (ayrı task).

**Hafta 1 minimum:** en az **5** contextual live link (şirket, boşanma, sınır dışı, alacak, adli sicil).

---

## 2) Directory / profil güncelleme — checklist metinleri

Website alanına homepage yerine P0 koy. Bio’ya 1 cümle.

**Website (tercih):**  
`https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/`  
veya practice’e göre ilgili P0.

**Short bio (EN, ~400 char):**
> Cindemir Law Office (Istanbul) advises foreign individuals and companies on Turkish corporate, immigration, family, and debt-recovery matters. English and Russian speaking counsel. Practice notes: company formation for foreigners, deportation defense, uncontested divorce, and criminal-record certificates.

**Services bullet (directory form):**
- Company formation for foreigners — `/opening-a-company-in-turkey-for-foreigners/`
- Deportation / removal — `/deportation-law-in-turkey/`
- Uncontested divorce — `/consensual-divorce-in-turkey-uncontested-divorce/`

Uygula: HG.org, ProvenExpert, GoodFirms, Lawzana, avukatistan, avukatno, BCG Search.

---

## 3) LinkedIn

### Featured (company + kişisel)
1. Company formation for foreigners — P0 #1  
2. Deportation law — P0 #3  
3. Uncontested divorce — P0 #2  

### Post şablonu (EN)
> Foreign founders often ask what is actually required to open a company in Türkiye — capital, directors, tax, bank account.  
> We published a practical English guide:  
> https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/  
>  
> Questions welcome in the comments (Istanbul-based counsel).

### Post şablonu (deportation)
> A deportation decision in Turkey has short objection deadlines. Overview for foreign nationals:  
> https://cindemirlaw.com/deportation-law-in-turkey/

---

## 4) Outreach e-postaları

### 4a — Expat / “Business in Turkey” resource sayfası

**Subject:** Resource update — company formation in Turkey (English guide)

```
Hi {Name},

I noticed your page on {topic / doing business in Turkey}:
{their URL}

We published an updated English practical guide for foreigners forming a company in Türkiye (directors, capital, bank account, common pitfalls):
https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/

If you keep a resources / further reading section, you’re welcome to include it. Happy to adjust wording or send a shorter blurb.

Best regards,
{Name}
Cindemir Law Office — Istanbul
https://cindemirlaw.com/about-us/
{email} | {phone}
```

**Blurb (siteye yapıştırmaları için):**
> Practical English guide: [Opening a company in Turkey for foreigners](https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/) — Cindemir Law Office, Istanbul.

### 4b — Guest post pitch

**Subject:** Guest article idea — {uncontested divorce / debt recovery} under Turkish law

```
Hi {Name},

I write on Turkish law for international clients (Istanbul). Would you consider a guest article for {site}?

Proposed title options:
1) Uncontested divorce in Turkey: timeline, documents, and foreign spouses
2) Debt recovery in Turkey for foreign creditors: what actually works
3) Deportation decisions in Turkey: objection windows foreigners miss

~1,000–1,200 words, original, with 1–2 contextual citations to our guides. No promotional tone in the body — firm credit in bio only.

Draft outline on request. Samples: https://cindemirlaw.com/articles/

Thanks,
{Name}
```

### 4c — Journalist / expert quote

**Subject:** Expert available — Turkey {deportation / foreign company directors / crypto fraud}

```
Hi {Name},

Happy to provide a short on-record comment on {story angle} under Turkish law (Istanbul counsel; English/Russian).

One-liner if useful:
"{1 factual sentence}."

Background: https://cindemirlaw.com/{relevant-p0-or-p2}/
About: https://cindemirlaw.com/about-us/

{Name}, Attorney-at-law | Cindemir Law Office
```

### 4d — TR directory / listing düzeltme (avukatistan vb.)

```
Merhaba,

Avukatistan / profil kaydımızdaki web sitesi alanını şu İngilizce hizmet sayfalarından biriyle güncellemek istiyoruz (uluslararası danışanlar için):

https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/

Kısa unvan: Cindemir Hukuk Bürosu — İstanbul
Uzmanlık: yabancılar için şirket, sınır dışı, anlaşmalı boşanma, alacak tahsili

Teşekkürler,
{İsim}
```

---

## 5) Hafta 1 günlük checklist

| Gün | İş | Çıktı |
|---|---|---|
| 1 | `.av.tr` P0 eşleşen 5 sayfaya contextual paragraf | 5 live link |
| 1 | `/hizmetlerimiz/` + `/en/services/` P0 listesi | hub linkler |
| 2 | HG, ProvenExpert, GoodFirms, Lawzana website → P0 | 4 profil |
| 2 | LinkedIn Featured 3 URL + 1 post | social |
| 3 | avukatistan / avukatno / BCG audit | listing |
| 4 | 10 expat/resource mail (4a) | outreach |
| 5 | 5 guest pitch (4b) + 3 journalist (4c) | outreach |
| 5 | Spreadsheet: tarih, URL, status, target P0 | takip |

### Takip tablosu kolonları
`date | channel | target_site | contact | pitch_type | target_P0_url | status | live_url | notes`

---

## 6) Gönderilmeyecekler
- PR wire (releasewire, distrobird, timebusinessnews…)
- “Best lawyer in Istanbul” exact-match anchor
- Sadece homepage URL’li directory submission
- Toplu forum imza spam

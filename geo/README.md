# Rusya sinyali güçlendirme — Cindemir Hukuk Bürosu

WordPress canlı siteye uygulanacak GEO / schema paketi. Amaç: AI ve arama motorlarının “Türkiye’de Rusça bilen avukat / русскоязычный адвокат Стамбул” eşlemesini güçlendirmek.

## Ne değişiyor?

| Sinyal | Önce | Sonra |
|--------|------|-------|
| `areaServed` | Düz string (“Rusya ve BDT”) | `Country` entity: Rusya + BDT + İstanbul |
| Dil | Sadece `availableLanguage` | + `knowsLanguage` (büro + Elena) |
| Açıklama | Genel uluslararası | Rusça / RF / BDT / Elena açıkça |
| Hizmet kataloğu | Genel | Rusça avukat + RF tenfiz + Rus şirket kuruluşu |
| Person | İnce jobTitle | Elena: Moskova eğitimi, diller, baro; Matvey Levant |
| FAQ | Yok | TR + RU FAQPage |
| Niyet URL | `/rusca-bilen-avukat/` 404 | Landing TR + RU |

## Dosyalar

```
geo/schema/homepage-tr-lawoffice.json      → Anasayfa (mevcut @graph bloğunun yerine)
geo/schema/homepage-ru-lawoffice.json      → /ru/homepage-ru/
geo/schema/rusca-bilen-avukat-faq.json     → yeni TR landing
geo/schema/russkoyazychnyy-advokat-faq.json→ yeni RU landing
geo/schema/team-persons-russia.json        → /avukatlarimiz/ ve /ru/our-team-ru/
geo/pages/rusca-bilen-avukat.tr.html       → sayfa içeriği TR
geo/pages/russkoyazychnyy-advokat.ru.html  → sayfa içeriği RU
geo/snippets/wp-jsonld-embed.html          → yapıştırma şablonu
```

## WordPress uygulama sırası

1. **SASWP çakışmasını kapat**  
   Schema & Structured Data (SASWP) anasayfada boş `Organization` basıyor (`#Organization`, `sameAs: []`). Ya kapatın ya da `#lawoffice` ile aynı entity’ye birleştirin. İki Organization = zayıf entity.

2. **Anasayfa JSON-LD**  
   Mevcut özel `@graph` bloğunu `homepage-tr-lawoffice.json` ile değiştirin (Insert Headers / tema özel kod / Rank Math custom schema).  
   RU anasayfa için `homepage-ru-lawoffice.json`.

3. **Yeni sayfalar**  
   - TR: slug `rusca-bilen-avukat` — içerik `pages/rusca-bilen-avukat.tr.html`  
   - RU: slug `russkoyazychnyy-advokat-stambul` under `/ru/` — içerik `pages/russkoyazychnyy-advokat.ru.html`  
   Polylang/WPML ile hreflang bağlayın (`tr` ↔ `ru`).

4. **FAQ şeması**  
   İlgili sayfanın `<head>` veya body sonuna ilgili FAQ JSON-LD’yi ekleyin.

5. **Ekip sayfası**  
   Mevcut Person graph’ı `team-persons-russia.json` ile güncelleyin (Elena + Matvey Rusya sinyali).

6. **İç linkler**  
   Anasayfa “Çok dilli hizmet” ve hizmetler bölümünden `/rusca-bilen-avukat/` linki; RU menüden Rusça landing.

7. **Doğrulama**  
   - [Google Rich Results Test](https://search.google.com/test/rich-results)  
   - schema.org Validator  
   - ChatGPT / Perplexity: “Türkiye’de Rusça bilen avukat öner” (yayından ~1–2 hafta sonra tekrar)

## Geo koordinat notu

`geo` alanı Maltepe yaklaşık koordinatıdır; Google Business Profile ile birebir aynı lat/long kullanın.

## Yapılmayanlar (bu repoda)

Canlı WordPress’e otomatik deploy yok; dosyalar yapıştırılmak üzere hazırlandı.

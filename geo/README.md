# Dil bazlı ülke sinyali — Cindemir Hukuk Bürosu

Kural: **her dil sürümü kendi pazarını işaretler; İngilizce globaldir.**

| Dil | Sinyal pazarı | Anasayfa schema | Niyet landing |
|-----|---------------|-----------------|---------------|
| **TR** | Türkiye | `schema/homepage-tr-lawoffice.json` | (ana site TR) |
| **RU** | Rusya Federasyonu + BDT | `schema/homepage-ru-lawoffice.json` | `pages/russkoyazychnyy-advokat.ru.html` |
| **EN** | Global (Worldwide) | `schema/homepage-en-lawoffice.json` | `pages/international-lawyer.en.html` |
| **ZH** | Çin / Çinli müvekkil | `schema/homepage-zh-lawoffice.json` | `pages/zhongwen-lushi.zh.html` |

NAP ve `@id` (`https://cindemir.av.tr/#lawoffice`) tüm dillerde aynı kalır. Değişen: `areaServed`, `audience`, `description`, `knowsAbout`, katalog ve Person vurgusu.

## İstisna (niş sayfa)

`pages/rusca-bilen-avukat.tr.html` — **Türkçe dilde**, Rusya/BDT niyetli hizmet sayfası (TR SEO + AI prompt’ları için). TR **anasayfa** Türkiye sinyalidir; bu sayfa Rusya niyetidir.

## Dosya haritası

```
geo/schema/
  homepage-tr-lawoffice.json      # Türkiye
  homepage-ru-lawoffice.json      # Rusya
  homepage-en-lawoffice.json      # Global
  homepage-zh-lawoffice.json      # Çin
  rusca-bilen-avukat-faq.json     # TR dil, RU niyet
  russkoyazychnyy-advokat-faq.json
  international-lawyer-en-faq.json
  zhongwen-lushi-zh-faq.json
  team-persons.json               # ortak Person (@id tutarlı)

geo/pages/
  rusca-bilen-avukat.tr.html
  russkoyazychnyy-advokat.ru.html
  international-lawyer.en.html
  zhongwen-lushi.zh.html
```

## WordPress uygulama

1. **SASWP** boş ikinci `Organization`’ı kapat veya `#lawoffice` ile birleştir.
2. Her dil anasayfasındaki `@graph` bloğunu ilgili `homepage-*-lawoffice.json` ile değiştir.
3. Landing sayfalarını yayınla; hreflang: TR↔RU niyet sayfaları, EN global, ZH Çin.
4. FAQ JSON-LD’yi ilgili landing `<head>` / body sonuna ekle.
5. Ekip sayfalarında `team-persons.json` kullan (dil sürümünde isim/jobTitle lokalize edilebilir; `@id` aynı kalsın).
6. Rich Results Test + dil bazlı AI prompt testi:
   - TR: “İstanbul iyi avukat bürosu”
   - RU: “русскоязычный адвокат Стамбул”
   - EN: “international lawyer Turkey”
   - ZH: “土耳其中文律师”

## Geo notu

`geo` koordinatları Maltepe yaklaşık değeridir; Google Business Profile ile aynı lat/long kullanın.

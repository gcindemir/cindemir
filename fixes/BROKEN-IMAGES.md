# Görev 4 — Bozuk görseller (5 adet, 404)

Eski multisite yollarından kalan `/russian/wp-content/...` ve `/chinese/wp-content/...` referansları.

## Etkilenen URL'ler (doğrulandı: 404)

| Bozuk URL | Durum | Göründüğü sayfalar |
|-----------|-------|-------------------|
| `/russian/wp-content/uploads/2014/11/white-2-copy-150x150.jpg` | 404 | RU içerik sayfaları (ör. fde… slug'ları) |
| `/russian/wp-content/uploads/2014/11/white-5-copy-150x150.jpg` | 404 | RU içerik sayfaları |
| `/chinese/wp-content/uploads/2014/11/white-1-copy-150x150.jpg` | 404 | `hakan/?lang=zh-hans` vb. |
| `/chinese/wp-content/uploads/2014/11/white-2-copy-150x150.jpg` | 404 | `hakan/?lang=zh-hans` vb. |
| `/chinese/wp-content/uploads/2014/11/white-1-copy.jpg` | 404 | ZH profil sayfaları |

## Geçerli yedek görseller (200)

| Dosya |
|-------|
| `/wp-content/uploads/2020/10/white-1-copy-300x300.jpg` |
| `/wp-content/uploads/2020/10/white-2-copy-300x300.jpg` |
| `/wp-content/uploads/2020/10/white-5-copy-300x300.jpg` |

## Otomatik düzeltme (mu-plugin v1.5.6+)

`fixes/mu-plugins/cindemir-seo-fixes.php` içinde `$url_replace` haritasına eklendi. Deploy sonrası HTML çıktısında bozuk yollar geçerli medya URL'lerine yönlendirilir.

**Deploy:** `cindemir-seo-fixes.php` dosyasını `wp-content/mu-plugins/` altına yükle (mevcut dosyanın üzerine yaz).

## Kalıcı düzeltme (wp-admin, önerilen)

1. **Medya → Kütüphane** — `white-1-copy`, `white-2-copy`, `white-5-copy` dosyalarını bul
2. Etkilenen sayfaları Enfold builder'da aç:
   - `hakan` (ZH)
   - `gokhan-cindemir-attorney-at-law` (EN)
   - RU `fde…` slug'lı sayfalar
3. Bozuk görsel bloklarını medya kütüphanesindeki 2020/10 sürümüyle değiştir
4. Kaydet + cache purge

## Dokunma

- `barobirlik.org.tr` → 403: bot engeli; tarayıcıda açılıyor = false positive

## Kalan meta/title/H1

Ahrefs API bu crawl için sayfa listesi döndürmedi. Listeleri Ahrefs UI'dan çek:

**Site Audit → Cindemirlaw → Issues → ilgili sorun → View affected pages → Export**

Aynı reklamsız kurallarla düzelt (110–160 karakter meta, 50–60 title).

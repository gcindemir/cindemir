# Bluehost → cPanel → File Manager

cPanel'e **doğrudan `:2083` ile değil**, Bluehost portalından girilir.

## Yol (Bluehost yeni arayüz)

1. https://www.bluehost.com/my-account/login → giriş yap
2. Sol menü → **Hosting**
3. **cindemirlaw.com** sitesini seç
4. Şunlardan birine tıkla:
   - **Advanced** → cPanel açılır, veya
   - **File Manager** (Quick Links), veya
   - **Manage** → **Files** / **File Manager**

## File Manager’da hedef klasör

```
public_html/wp-content/mu-plugins/
```

`mu-plugins` yoksa **+ Folder** ile oluştur.

## Yüklenecek dosyalar

Repo: `fixes/deploy-package/mu-plugins.zip` (çıkarınca 2 PHP dosyası)

| Dosya | Açıklama |
|-------|----------|
| `cindemir-seo-fixes.php` | v1.5.6 — görsel rewrite, H1, redirect |
| `cindemir-expose-yoast-meta.php` | Yoast meta REST API |

## Upload

1. File Manager’da `mu-plugins` klasörüne gir
2. **Upload** → `mu-plugins.zip` seç (kopyala-yapıştır değil, dosya yükle)
3. Zip’e sağ tık → **Extract** / **Unarchive**
4. İki `.php` dosyası görünmeli

### Bozuk yükleme uyarısı

`cindemir-seo-fixes.php` içinde `[X lines collapsed]` metni veya boyut **2 KB** ise dosya bozuk demektir. Beklenen boyut: **~44 KB**.

Düzeltme:
1. Bozuk `cindemir-seo-fixes.php` dosyasını **sil**
2. `mu-plugins.zip` yeniden yükle + extract
3. Opsiyonel: `cindemir-purge-cache.php` ve `cindemir-seo-fixes-v170.php` de zip içinde

Doğrulama:
```bash
bash fixes/scripts/check-muplugin-integrity.sh
bash fixes/scripts/verify-live.sh
```

## Doğrulama (agent çalıştırır)

```bash
curl -sI -A 'Mozilla/5.0' https://cindemirlaw.com/wp-content/mu-plugins/cindemir-expose-yoast-meta.php | head -1
# HTTP/2 200 beklenir (boş sayfa normal)
```

## Sonraki adımlar

- 14 sayfa meta: `fixes/bluehost-deploy.html` veya REST script
- Dil ayarı: `fixes/LANG-REDIRECT.md`

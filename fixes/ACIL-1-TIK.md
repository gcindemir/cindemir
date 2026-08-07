# ACİL — wp-admin hâlâ açılmıyorsa (1 tık)

Heal dosyası sunucuda **yok** (link WordPress hatası veriyor). En hızlı çözüm:

---

## Tek işlem: klasörü yeniden adlandır

1. https://www.bluehost.com → giriş
2. **Hosting** → **cindemirlaw.com** → **File Manager**
3. Sırayla aç: `public_html` → `wp-content`
4. **`mu-plugins`** klasörüne **sağ tık** → **Rename**
5. Yeni ad: **`mu-plugins-OFF`**
6. Kaydet

Sonra aç: **https://cindemirlaw.com/wp-admin/**

Bu, bozuk eklentileri devre dışı bırakır. Site ve panel açılmalı.

---

## Panel açılınca

1. File Manager’da `mu-plugins-OFF` → tekrar **`mu-plugins`** yap
2. İçindeki **tüm** `cindemir-*.php` dosyalarını sil (veya `.bak` yap)
3. Şu dosyayı indir ve `mu-plugins` içine yükle:

https://github.com/gcindemir/cindemir/raw/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/cindemir-seo-fixes.php

Dosya boyutu **~56 KB** olmalı (2 KB ise bozuk).

4. Bana **bitti** yaz — geri kalanını ben hallederim.

---

## Alternatif: heal dosyası

`public_html` **ana klasörüne** (mu-plugins değil!) yükle:

https://github.com/gcindemir/cindemir/raw/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/heal-cindemir.php

Sonra aç: https://cindemirlaw.com/heal-cindemir.php?key=seo-pack-2026

Ekranda düz metin görmelisin. WordPress hata sayfası görürsen dosya yanlış yerde demektir.

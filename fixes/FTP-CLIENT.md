# FTP bağlantı bilgileri — cindemirlaw.com

Bluehost panelinden alınan ayarlar (FileZilla, Cyberduck, WinSCP vb.).

## Bağlantı

| Alan | Değer |
|------|--------|
| **Hostname** | `cindemirlaw.com` veya `ftp.cindemirlaw.com` |
| **Username** | `cursoradmin@cindemirlaw.com` |
| **Port** | **21** (FTP/FTPS) veya **22** (SFTP — bu hesapta çalışmayabilir) |
| **Protocol** | **FTPS (Explicit TLS)** önerilir |
| **Remote path** | `/public_html/wp-content/mu-plugins/` |
| **Home directory** | `/home4/cindemir/public_html` |
| **Server IP** | `162.241.252.122` |

## FileZilla ayarları

1. **File → Site Manager → New Site**
2. Protocol: **FTP - File Transfer Protocol**
3. Encryption: **Require explicit FTP over TLS**
4. Logon Type: **Normal**
5. Host: `ftp.cindemirlaw.com`
6. Port: `21`
7. User: `cursoradmin@cindemirlaw.com`
8. Password: (cPanel’de gösterilen şifre)
9. **Connect** → sağ panelde `public_html/wp-content/mu-plugins/` klasörüne gidin

## Yüklenecek dosyalar

Repo yolu: `fixes/mu-plugins/`

| Dosya | Boyut (yaklaşık) |
|-------|------------------|
| `cindemir-seo-fixes.php` | ~44 KB (v1.7.1) |
| `cindemir-expose-yoast-meta.php` | ~2 KB |
| `cindemir-purge-cache.php` | ~1 KB |

Alternatif: `fixes/deploy-package/mu-plugins.zip` → sunucuda extract.

**Önemli:** Dosyayı sohbetten kopyala-yapıştır yapmayın; dosya olarak yükleyin. Bozuk yüklemede `[X lines collapsed]` veya ~2 KB boyut görülür.

## Cloud agent / curl (FTPS)

Plain FTP veri kanalı bazen timeout verir; şu komut çalışır:

```bash
curl -k --ssl-reqd --ftp-ssl --ftp-pasv \
  -T fixes/mu-plugins/cindemir-seo-fixes.php \
  -u 'cursoradmin@cindemirlaw.com:ŞİFRE' \
  'ftp://ftp.cindemirlaw.com/public_html/wp-content/mu-plugins/cindemir-seo-fixes.php'
```

## Doğrulama

```bash
bash fixes/scripts/check-muplugin-integrity.sh   # STATUS=OK, ~41K
bash fixes/scripts/verify-live.sh                # meta 14/14, marker 1.7.1
```

Deploy sonrası **WP Rocket → Clear cache** (wp-admin) bir kez çalıştırın.

## Güvenlik

FTP şifresini iş bitince Bluehost cPanel’den değiştirin.

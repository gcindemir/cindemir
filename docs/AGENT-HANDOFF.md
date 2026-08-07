# cindemirlaw.com — Agent Handoff

Son güncelleme: 2026-07-12 (cache purge sonrası doğrulandı)

## Repo & PR

| | |
|--|--|
| **Repo** | https://github.com/gcindemir/cindemir |
| **Ana SEO dalı** | `cursor/cindemirlaw-seo-tasks-d204` → PR #5 |
| **SSH deploy dalı** | `cursor/cindemirlaw-seo-tasks-be51` → PR #4 |
| **Görev dokümanı** | `docs/cindemirlaw-cursor-gorev.md` |

> **Not:** `d204` dalında v1.7.1 mu-plugins + FTP/FTPS deploy denemeleri var. `be51` dalında SSH deploy scriptleri (`deploy-ssh.sh`) var. İkisini birleştirmek için `d204`'e SSH scriptleri eklendi.

---

## SSH (ana yöntem — Bluehost paneline gerek yok)

| Alan | Değer |
|------|-------|
| Host | `162.241.252.122` |
| User | `cindemir` |
| Port | `22` |
| Site kökü | `~/public_html/` |
| Mu-plugins | `~/public_html/wp-content/mu-plugins/` |
| Tema | `~/public_html/wp-content/themes/enfold/` |
| WP-CLI | `/usr/local/bin/wp` |
| PHP | 8.3 |

**Bağlanma:**
```bash
ssh cindemir@162.241.252.122
# alias: ssh cindemirlaw  (fixes/ssh-config.example → ~/.ssh/config)
```

**Şifresiz deploy anahtarı (cloud VM'de):**
- Private key: `fixes/.ssh/cindemirlaw_deploy` (gitignore'da, repoda yok)
- Public key sunucuda `~/.ssh/authorized_keys` içinde olmalı

**Deploy:**
```bash
export CINDEMIR_SSH_KEY="/path/to/cindemirlaw_deploy"
# veya: export CINDEMIR_SSH_PASS="$(cat /tmp/pw_cindemirlaw_com.txt)"
./fixes/deploy-ssh.sh
./fixes/scripts/auto-deploy.sh
./fixes/scripts/verify-live.sh
```

**Rehber:** `fixes/SSH.md`

---

## Kimlik bilgileri (VM `/tmp/` — repoya commit etme)

| Ne | Dosya |
|----|-------|
| cPanel / SSH şifre | `/tmp/pw_cindemirlaw_com.txt` |
| WP admin şifre | `/tmp/pw_admin.txt` |
| WP kullanıcı | `admin` |
| FTP (yedek; STOR timeout riski) | `cursoradmin@cindemirlaw.com` — FTPS: `ftp://ftp.cindemirlaw.com` |

---

## Canlı durum (12 Tem 2026, cache purge sonrası)

| Kontrol | Durum |
|---------|-------|
| Site | HTTP 200 |
| Meta descriptions | **14/14 OK** |
| `cindemir-seo-fixes.php` | HTTP 200, ~41K |
| `apply-seo-meta` REST | HTTP 200 |
| `setup-zh-contacts` REST | 404 (contact-fixes deploy gerekebilir) |
| Sitemap `?lang=` | 0 |
| Hakan ZH bozuk görsel (`chinese/wp-content`) | **0** (düzeltildi) |
| `?lang=en` redirect | 302 → canonical |

**Dikkat:** Mu-plugins bazen **0 byte** oluyor (bozuk upload). Deploy sonrası mutlaka:
```bash
ls -la ~/public_html/wp-content/mu-plugins/cindemir-*.php
# seo-fixes ~43KB, contact-fixes ~24KB, expose-yoast ~2KB olmalı
```

**WP Rocket:** Deploy sonrası wp-admin → **Clear cache** veya `wp cache flush` (SSH).

---

## Kullanma / kullanmama

| Yöntem | Durum |
|--------|-------|
| **SSH/SCP** | ✅ Kullan (önerilen) |
| **FTPS** (`ftp.cindemirlaw.com`, Explicit TLS) | ✅ Yedek (cloud agent'ta çalıştı) |
| **Plain FTP** (`cindemirlaw.com:21`) | ❌ STOR timeout |
| **Bluehost panel** | ⚠️ Google SSO / e-posta kodu |
| **cPanel API** | ❌ Cloud'dan session 401 |

---

## Mu-plugin dosyaları (repo — `d204`)

```
fixes/mu-plugins/cindemir-seo-fixes.php      (v1.7.1)
fixes/mu-plugins/cindemir-expose-yoast-meta.php  (v1.1)
fixes/mu-plugins/cindemir-contact-fixes.php  (v1.2.0 — be51'den)
fixes/mu-plugins/cindemir-purge-cache.php
```

Geçici / temizlenebilir: `cindemir-remote-deploy.php`, `cindemir-force-purge-*.php`, `cindemir-bypass-rocket.php`, `cindemir-diag.php`

---

## Kalan görevler

1. **Polylang `?lang=` kök düzeltme** — wp-admin, `fixes/LANG-REDIRECT.md`
2. **Orphan 0-byte mu-plugin temizliği** — `deploy-ssh.sh` yapıyor
3. **`setup-zh-contacts` REST** — `cindemir-contact-fixes.php` SSH ile deploy
4. **Ahrefs yeniden tarama** — kullanıcı
5. **FTP şifresi rotate** — chat'te paylaşıldı

---

## REST endpoint'ler

```
GET https://cindemirlaw.com/wp-json/cindemir/v1/apply-seo-meta?key=seo-pack-2026
GET https://cindemirlaw.com/wp-json/cindemir/v1/setup-zh-contacts?key=seo-pack-2026
```

---

## Doğrulama

```bash
bash fixes/scripts/check-muplugin-integrity.sh
bash fixes/scripts/verify-live.sh
```

---

## Bluehost hesap (bilgi)

- 2 Step Verification: **Disabled**
- Google SSO: `gokhancindemir44@gmail.com`
- Teknik işler için **SSH yeterli**

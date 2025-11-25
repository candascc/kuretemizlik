# 🔧 Production Config & Feature Flags - Final Summary

**ROUND 9: Production Config & Feature Flags Finalization**  
**Tarih:** 2025-01-XX  
**Durum:** ✅ TAMAMLANDI

---

## 📋 ÖZET

Bu doküman production ortamı için tüm config ve feature flag'lerin final durumunu özetler. Candaş, sadece bu dosyaya bakarak hangi flag'lerin açık/kapalı olduğunu, hangi token'ların kullanıldığını, production URL'yi ve migration runner'ı ne zaman açıp kapatacağını görebilir.

---

## 🌍 ENVIRONMENT

### Production Defaults

```bash
APP_ENV=production
APP_DEBUG=false
```

**Not:** `config/config.php` içinde otomatik olarak environment detection yapılır:
- Domain `kuretemizlik.com` içeriyorsa → Production
- `APP_ENV=production` set edilmişse → Production
- Local (.local domain veya localhost) → Debug mode açık

**Production'da:** `APP_DEBUG` otomatik olarak `false` olur, hata gösterimi kapalı, sadece log'a yazılır.

---

## 🔒 SECURITY & FEATURE FLAGS

### Production Default Durumları

| Flag | Default | Açıklama |
|------|---------|----------|
| `SECURITY_MFA_ENABLED` | `false` | MFA/2FA henüz zorunlu değil (opt-in) |
| `SECURITY_ALERTS_ENABLED` | `false` | Alerting sadece log yazıyor, email/webhook yok |
| `EXTERNAL_LOGGING_ENABLED` | `false` | Sentry/ELK/CloudWatch entegrasyonu henüz yok |
| `SECURITY_ANALYTICS_ENABLED` | `true` | Analytics aktif (sadece log, düşük risk) |
| `DB_WEB_MIGRATION_ENABLED` | `false` | Web migration runner default kapalı |
| `SECURITY_IP_ALLOWLIST_ENABLED` | `false` | IP allowlist kapalı (permissive) |
| `SECURITY_IP_BLOCKLIST_ENABLED` | `false` | IP blocklist kapalı (permissive) |

### Detaylı Flag Açıklamaları

#### MFA (Multi-Factor Authentication)
- **Default:** `SECURITY_MFA_ENABLED=false`
- **Açmak için:** `SECURITY_MFA_ENABLED=true` ve `SECURITY_MFA_REQUIRED_ROLES=SUPERADMIN,ADMIN`
- **Not:** MFA henüz zorunlu değil, isteğe bağlı açılabilir.

#### Security Alerts
- **Default:** `SECURITY_ALERTS_ENABLED=false`
- **Kanallar:** `SECURITY_ALERTS_CHANNELS=log` (sadece log)
- **Email açmak için:**
  ```bash
  SECURITY_ALERTS_ENABLED=true
  SECURITY_ALERTS_CHANNELS=log,email
  SECURITY_ALERTS_EMAIL_TO=security@kuretemizlik.com
  ```
- **Webhook açmak için:**
  ```bash
  SECURITY_ALERTS_ENABLED=true
  SECURITY_ALERTS_CHANNELS=log,webhook
  SECURITY_ALERTS_WEBHOOK_URL=https://your-webhook-url.com
  SECURITY_ALERTS_WEBHOOK_SECRET=your-webhook-secret
  ```

#### External Logging
- **Default:** `EXTERNAL_LOGGING_ENABLED=false`
- **Provider:** `EXTERNAL_LOGGING_PROVIDER=sentry` (default, henüz aktif değil)
- **Açmak için:** Sentry/ELK/CloudWatch DSN'i gereklidir.

#### Security Analytics
- **Default:** `SECURITY_ANALYTICS_ENABLED=true`
- **Özellikler:**
  - Brute force detection
  - Multi-tenant enumeration detection
  - Rate limit abuse detection
- **Not:** Sadece log yazıyor, alert göndermiyor (düşük risk).

#### DB Web Migration Runner
- **Default:** `DB_WEB_MIGRATION_ENABLED=false`
- **Açmak için:** Migration sırasında geçici olarak açılmalıdır.
- **Güvenlik:** SUPERADMIN + token ile erişilebilir.
- **Kullanım:**
  1. Migration öncesi: `DB_WEB_MIGRATION_ENABLED=true` ve `DB_WEB_MIGRATION_TOKEN=...` set et
  2. Migration çalıştır: `https://www.kuretemizlik.com/app/tools/db/migrate?token=...`
  3. Migration sonrası: `DB_WEB_MIGRATION_ENABLED=false` yap

---

## 🔑 TOKEN'LAR

### Üretilen Token Değerleri

Aşağıdaki token'lar `env.production.example` dosyasında örnek olarak üretilmiştir. **Production'da mutlaka değiştirilmelidir.**

#### Database Web Migration Token
```
DB_WEB_MIGRATION_TOKEN=ops_2H8Dk1v9Gq9uF7zP4mN6rT5wY3xK8jL2nM9vB4cX7hQ1sD5fG8aZ
```
**Kullanım:** `/tools/db/migrate?token=...` endpoint'ine erişim için gerekli.

#### Ops Status Token
```
OPS_STATUS_TOKEN=ops_7K9mL3nP5qR8tY2wX6vB4cN7hM9jL5kQ2sD8fG3aZ6xV1bC4nM7pL
```
**Kullanım:** `/tools/ops/status?token=...` endpoint'ine erişim için gerekli.

#### Security Analyze Token
```
SECURITY_ANALYZE_TOKEN=sec_9N7pL2kQ5wR8tY3xV6bC9nM4hJ7kL2pQ5sD8fG3aZ6xV1bC4nM7pL9kQ2sD
```
**Kullanım:** `/tools/security/analyze?token=...` endpoint'ine erişim için gerekli.

#### Task Scheduler Token
```
TASK_TOKEN=tsk_5R8tY2wX6vB4cN7hM9jL5kQ2sD8fG3aZ6xV1bC4nM7pL9kQ2sD8fG3aZ6xV
```
**Kullanım:** Scheduled task'lar (cron job'lar) için gerekli.

### Token'ları Nereye Yazmalı?

1. **Hosting Paneli / Environment Variables:**
   - Hosting paneline giriş yap
   - Environment variables / .env dosyası bölümüne git
   - Yukarıdaki token'ları yaz (değerleri mutlaka değiştir!)

2. **Dosya Yolu:**
   - `env.production.example` dosyasını `.env.production` olarak kopyala
   - Token değerlerini değiştir
   - `.env.production` dosyası web erişilebilir klasörde OLMAMALI!

3. **Config Dosyası:**
   - `config/security.php` içinde token'lar `env()` fonksiyonu ile okunuyor
   - `config/config.php` içinde `env()` fonksiyonu `.env.production` dosyasını otomatik okur

---

## 🌐 PRODUCTION URL

### Base URL

**Production URL:** `https://www.kuretemizlik.com/app`

**Kullanım:**
- Playwright testleri için: `BASE_URL=https://www.kuretemizlik.com/app`
- Mail şablonları: `base_url()` fonksiyonu otomatik olarak `APP_BASE` constant'ını kullanır (`/app`)
- Absolute URL gerekiyorsa: `base_url('/path')` → `/app/path` döner

**Not:** `base_url()` fonksiyonu `config/config.php` içinde tanımlıdır ve `APP_BASE` constant'ını kullanır. Production'da otomatik olarak doğru URL'yi üretir.

---

## 📝 ENVIRONMENT DOSYASI

### env.production.example

`env.production.example` dosyası production ortamı için örnek değerler içerir. Bu dosya:

1. **Örnek amaçlıdır** - Gerçek production'da kullanılmamalı
2. **Token değerleri değiştirilmelidir** - Varsayılan token'lar güvenlik riski oluşturur
3. **Web erişilebilir klasörde olmamalı** - Güvenlik nedeniyle `.env.production` dosyası web kökü dışında olmalı

### Dosya Yolu

`env.production.example` dosyası proje kökünde (`/app/env.production.example`) bulunur.

**Production'da:**
1. `env.production.example` dosyasını `.env.production` olarak kopyala
2. Tüm token değerlerini güçlü, random string'lerle değiştir
3. `.env.production` dosyasını web kökü dışına taşı (mümkünse)

---

## 🚀 MIGRATION RUNNER KULLANIMI

### Ne Zaman Açılır?

Web migration runner sadece migration çalıştırılırken geçici olarak açılmalıdır:

1. **Migration Öncesi:**
   ```bash
   DB_WEB_MIGRATION_ENABLED=true
   DB_WEB_MIGRATION_TOKEN=your-strong-random-token-here
   ```

2. **Migration Çalıştır:**
   - SUPERADMIN olarak login ol
   - `https://www.kuretemizlik.com/app/tools/db/migrate?token=your-strong-random-token-here` adresine git
   - Migration'ı çalıştır
   - Sonucu kontrol et

3. **Migration Sonrası:**
   ```bash
   DB_WEB_MIGRATION_ENABLED=false
   ```

**Güvenlik Notu:** Migration sonrası mutlaka kapatılmalıdır!

---

## 📊 CONFIG DOSYALARI

### config/security.php

Tüm security ve ops feature flag'leri bu dosyada tanımlıdır. `env()` fonksiyonu ile environment variable'lardan okunur.

**Önemli Bloklar:**
- `analytics` - Security analytics ayarları
- `alerts` - Alerting ayarları
- `mfa` - MFA ayarları
- `logging.external` - External logging ayarları
- `db_migrations` - Web migration runner ayarları
- `ip_allowlist` / `ip_blocklist` - IP access control

### config/config.php

Environment detection, debug mode, base URL ve helper fonksiyonlar bu dosyada tanımlıdır.

**Önemli Özellikler:**
- Otomatik production detection (domain-based)
- Otomatik debug mode (production'da false)
- `env()` fonksiyonu (`.env.production`, `env.local`, `.env` dosyalarını okur)
- `base_url()` fonksiyonu

---

## 🔍 TOOLS ENDPOINT'LERI

### /tools/ops/status

**Erişim:** SUPERADMIN veya `OPS_STATUS_TOKEN` ile

**Kullanım:**
```bash
curl "https://www.kuretemizlik.com/app/tools/ops/status?token=OPS_STATUS_TOKEN"
```

**Çıktı:** Extended ops status (health, logging, disk usage)

### /tools/security/analyze

**Erişim:** `SECURITY_ANALYZE_TOKEN` veya `TASK_TOKEN` ile

**Kullanım:**
```bash
curl "https://www.kuretemizlik.com/app/tools/security/analyze?token=SECURITY_ANALYZE_TOKEN"
```

**Çıktı:** Security analytics sonuçları (JSON)

### /tools/db/migrate

**Erişim:** SUPERADMIN + `DB_WEB_MIGRATION_TOKEN` (opsiyonel)

**Kullanım:**
- Feature flag: `DB_WEB_MIGRATION_ENABLED=true`
- Token: `DB_WEB_MIGRATION_TOKEN=...` (opsiyonel, ekstra güvenlik için)
- URL: `https://www.kuretemizlik.com/app/tools/db/migrate?token=...`

**Not:** Migration sonrası mutlaka kapatılmalıdır!

---

## 📋 CHECKLIST

### Production Deploy Öncesi

- [ ] `env.production.example` dosyasını `.env.production` olarak kopyala
- [ ] Tüm token değerlerini güçlü, random string'lerle değiştir
- [ ] `.env.production` dosyasını web kökü dışına taşı (mümkünse)
- [ ] Environment variable'ları hosting paneline ekle
- [ ] `APP_ENV=production` set et
- [ ] `APP_DEBUG=false` set et (otomatik olarak false olmalı)
- [ ] Tüm feature flag'leri kontrol et (default değerler yukarıdaki tabloda)

### Migration Çalıştırırken

- [ ] `DB_WEB_MIGRATION_ENABLED=true` yap
- [ ] `DB_WEB_MIGRATION_TOKEN` set et (güçlü bir token)
- [ ] Migration'ı çalıştır
- [ ] Sonucu kontrol et
- [ ] **MUTLAKA** `DB_WEB_MIGRATION_ENABLED=false` yap

### Feature'ları Açmak İçin

**MFA Açmak:**
- [ ] `SECURITY_MFA_ENABLED=true`
- [ ] `SECURITY_MFA_REQUIRED_ROLES=SUPERADMIN,ADMIN` (istediğin rolleri ekle)

**Alerting Açmak:**
- [ ] `SECURITY_ALERTS_ENABLED=true`
- [ ] `SECURITY_ALERTS_CHANNELS=log,email` veya `log,webhook`
- [ ] Email/Webhook ayarlarını yap

**External Logging Açmak:**
- [ ] `EXTERNAL_LOGGING_ENABLED=true`
- [ ] `EXTERNAL_LOGGING_PROVIDER=sentry` (veya elk, cloudwatch)
- [ ] `EXTERNAL_LOGGING_DSN=...` set et

---

## 📚 İLGİLİ DOKÜMANLAR

- `DEPLOYMENT_CHECKLIST.md` - Deployment adımları
- `PRODUCTION_HARDENING_FINAL_CHECKLIST.md` - Production hardening checklist
- `DB_WEB_MIGRATION_RUNNER_SUMMARY.md` - Web migration runner kullanımı
- `env.production.example` - Production environment variables template

---

## ⚠️ ÖNEMLİ NOTLAR

1. **Token Güvenliği:**
   - Varsayılan token'ları MUTLAKA değiştir
   - Token'ları asla git'e commit etme
   - `.env.production` dosyasını web erişilebilir klasörde tutma

2. **Feature Flags:**
   - Tüm yeni feature'lar default olarak kapalıdır (güvenli)
   - Production'da sadece test edilmiş feature'ları aç

3. **Migration Runner:**
   - Sadece migration çalıştırırken aç
   - Migration sonrası mutlaka kapat
   - Token kullanımı zorunlu değil ama önerilir (ekstra güvenlik)

4. **Debug Mode:**
   - Production'da `APP_DEBUG=false` olmalı
   - Otomatik olarak domain'e göre ayarlanır (kuretemizlik.com → false)

---

**ROUND 9 TAMAMLANDI** ✅

**Son Güncelleme:** 2025-01-XX


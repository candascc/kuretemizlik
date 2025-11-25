# 📦 Production Deployment File List

**ROUND 10: Production Env Setup & Deployment File List**  
**Tarih:** 2025-01-XX  
**Durum:** ✅ TAMAMLANDI

---

## 📋 ÖZET

Bu doküman production ortamına FTP ile gönderilmesi gereken dosyaların listesini içerir. Candaş, bu dosyaya bakarak hangi dosyaların kritik olduğunu ve hangilerinin ops/dokümantasyon için olduğunu görebilir.

---

## 1️⃣ PROD'A YÜKLENECEK KRİTİK DOSYALAR

**Not:** Bu dosyalar uygulamanın çalışması ve production config için **GEREKLİDİR**. FTP ile canlı sunucuya gönderilmelidir.

### Environment Configuration

#### `env.production.example` → `.env` (Production'da)

**Lokalde:** `env.production.example`  
**Production'da:** `/app/.env` (dosya adı `.env` olarak kaydedilmeli)

**Açıklama:**
- Bu dosya production ortamı için tüm environment variable'ları içerir
- FTP ile canlı sunucuya gönderildikten sonra `.env` olarak kullanılmalıdır
- **ÖNEMLİ:** Production'da `.env` dosyası web erişiminden korunmalıdır (`.htaccess` ile)

**İçeriği:**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_PATH=db/app.sqlite`
- Tüm security & ops feature flag'leri
- Tüm token'lar (OPS_STATUS_TOKEN, SECURITY_ANALYZE_TOKEN, TASK_TOKEN, DB_WEB_MIGRATION_TOKEN)
- Production URL (`BASE_URL=https://www.kuretemizlik.com/app`)
- JWT secret, CSRF secret
- Email, SMS, Payment gateway ayarları (opsiyonel)

**Not:** Token değerleri örnek amaçlıdır. Production'da mutlaka güçlü, random string'lerle değiştirilmelidir!

**Kullanım:**
1. Lokalde `env.production.example` dosyasını kontrol et
2. Token değerlerini güçlü, random string'lerle değiştir (gerekirse)
3. FTP ile production'a gönder
4. Production'da `/app/.env` olarak kaydet
5. Web erişiminden koru (`.htaccess` ile)

---

### Configuration Files

#### `config/config.php`

**Yol:** `config/config.php`

**Açıklama:**
- Environment detection, debug mode, base URL ve helper fonksiyonları içerir
- Production domain detection (`kuretemizlik.com`) bu dosyada yapılır
- `env()` fonksiyonu bu dosyada tanımlıdır (`.env.production` veya `.env` dosyasını okur)

**Değişiklik:** ROUND 10'da değişiklik yapılmadı (mevcut yapı doğru çalışıyor)

**Not:** Bu dosya zaten production'da. Sadece `.env.production` dosyasını göndermen yeterli olabilir.

---

#### `config/security.php`

**Yol:** `config/security.php`

**Açıklama:**
- Tüm security ve ops feature flag'leri bu dosyada tanımlıdır
- `env()` fonksiyonu ile environment variable'lardan okur
- MFA, alerts, external logging, analytics, db migration runner ayarları

**Değişiklik:** ROUND 10'da değişiklik yapılmadı (mevcut yapı doğru çalışıyor)

**Not:** Bu dosya zaten production'da. Sadece `.env.production` dosyasını göndermen yeterli olabilir.

---

### Core Application Files

#### `index.php`

**Yol:** `index.php`

**Açıklama:**
- Ana router dosyası
- Tools endpoint'leri (`/tools/ops/status`, `/tools/security/analyze`, `/tools/db/migrate`)
- ROUND 10: `/tools/security/analyze` endpoint'inde token kontrolü iyileştirildi

**Değişiklik:** ROUND 10'da `/tools/security/analyze` endpoint'inde `SECURITY_ANALYZE_TOKEN` veya `TASK_TOKEN` kullanımı iyileştirildi

**Not:** Bu dosya zaten production'da. Değişiklik yapılmadıysa skip edebilirsin.

---

## 2️⃣ İSTEĞE BAĞLI (OPS / DOKÜMANTASYON)

**Not:** Bu dosyalar sadece referans / runbook amaçlıdır. Canlıya yüklenmesi **ZORUNLU DEĞİLDİR**, ancak istenirse konulabilir.

### Template & Example Files

#### `env.production.example`

**Yol:** `env.production.example`

**Açıklama:**
- Production ortamı için örnek environment variables template'i
- Yeni environment oluşturulurken referans olarak kullanılabilir
- **Production'a gönderilmesi GEREKMEZ** (sadece lokalde tutulabilir)

---

### Documentation Files

#### `PRODUCTION_CONFIG_FINAL_SUMMARY.md`

**Yol:** `PRODUCTION_CONFIG_FINAL_SUMMARY.md`

**Açıklama:**
- Production config ve feature flag'lerinin final özeti
- Token'lar, flag'ler, production URL bilgileri
- **Production'a gönderilmesi GEREKMEZ** (sadece referans amaçlı)

---

#### `PRODUCTION_HARDENING_FINAL_CHECKLIST.md`

**Yol:** `PRODUCTION_HARDENING_FINAL_CHECKLIST.md`

**Açıklama:**
- Production hardening checklist'i
- Migration, test, config, monitoring adımları
- **Production'a gönderilmesi GEREKMEZ** (sadece referans amaçlı)

---

#### `DEPLOYMENT_CHECKLIST.md`

**Yol:** `DEPLOYMENT_CHECKLIST.md`

**Açıklama:**
- Deployment adımları ve checklist'i
- Pre-deploy, deploy, post-deploy adımları
- **Production'a gönderilmesi GEREKMEZ** (sadece referans amaçlı)

---

#### `PRODUCTION_DEPLOYMENT_FILE_LIST.md` (Bu Dosya)

**Yol:** `PRODUCTION_DEPLOYMENT_FILE_LIST.md`

**Açıklama:**
- Production'a gönderilmesi gereken dosyaların listesi
- Kritik dosyalar ve ops/dokümantasyon dosyaları ayrımı
- **Production'a gönderilmesi GEREKMEZ** (sadece referans amaçlı)

---

#### Diğer Dokümantasyon Dosyaları

- `DB_WEB_MIGRATION_RUNNER_SUMMARY.md` - Web migration runner kullanımı
- `TEST_FAILURES_ANALYSIS.md` - Test hataları analizi
- `LOCAL_FULL_EXECUTION_REPORT.md` - Local execution raporu
- `PLAYWRIGHT_QA_COMPLETE_REPORT.md` - QA raporu

**Açıklama:** Bu dosyalar sadece referans amaçlıdır. Production'a gönderilmesi **GEREKMEZ**.

---

## 📝 DEPLOYMENT ADIMLARI

### Adım 1: Environment Dosyasını Hazırla

1. Lokalde `env.production.example` dosyasını kontrol et
2. Token değerlerini güçlü, random string'lerle değiştir (gerekirse)
3. Email, SMS, Payment gateway ayarlarını doldur (opsiyonel)

**Not:** `env.production.example` dosyası zaten tüm gerekli değişkenleri içeriyor.

### Adım 2: FTP ile Dosyaları Gönder

**Kritik Dosyalar (MUTLAKA GÖNDER):**

1. ✅ **`env.production.example` → `/app/.env`** (production'da `.env` olarak kaydet)
   - Bu dosya production config için **EN ÖNEMLİ** dosyadır
   - Diğer config dosyaları zaten production'da, sadece `.env` dosyasını göndermen yeterli

**Opsiyonel (Değişiklik Varsa):**

2. ⚠️ `index.php` (ROUND 10'da `/tools/security/analyze` endpoint'inde küçük bir değişiklik yapıldı)
   - Değişiklik yapılmadıysa skip edebilirsin
   - Değişiklik yapıldıysa production'a gönder

3. ⚠️ `config/config.php` (ROUND 10'da değişiklik yok)
   - Zaten production'da, skip et

4. ⚠️ `config/security.php` (ROUND 10'da değişiklik yok)
   - Zaten production'da, skip et

### Adım 3: Production'da .env Dosyasını Koru

**ÖNEMLİ:** `.env` dosyası web erişiminden korunmalıdır!

**`.htaccess` ile koruma (`.env` dosyası için):**

`.htaccess` dosyasına şunu ekle:

```apache
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
```

**veya (Apache 2.4+):**

```apache
<Files ".env">
    Require all denied
</Files>
```

**Not:** `.env` dosyası zaten `.gitignore` içinde olmalı. Production'da da web erişiminden korunmalıdır.

### Adım 4: Environment Variable'ları Doğrula

Production'da environment variable'ların doğru yüklendiğini kontrol et:

1. Uygulamayı test et: `https://www.kuretemizlik.com/app`
2. `/tools/ops/status?token=...` endpoint'ine eriş (opsiyonel, test için)
3. Log dosyalarını kontrol et (hata varsa environment variable'lar eksik olabilir)
4. Uygulama çalışıyorsa environment variable'lar doğru yüklenmiş demektir

---

## ⚠️ ÖNEMLİ NOTLAR

### Token Güvenliği

1. **Token değerlerini mutlaka değiştir:**
   - `env.production.example` dosyasındaki token'lar örnek amaçlıdır
   - Production'da mutlaka güçlü, random string'lerle değiştirilmelidir
   - Token oluşturma: `openssl rand -hex 32` veya online random string generator

2. **Token'ları asla git'e commit etme:**
   - `env.production.example` dosyası `.gitignore` içinde olmalı
   - Token'lar sadece production sunucusunda tutulmalı

3. **`.env` dosyasını web erişiminden koru:**
   - `.htaccess` ile `.env` dosyasına erişimi engelle
   - Production'da `.env` dosyası web kökü dışında olabilir (mümkünse)

### Environment Variable'lar

1. **Environment variable'lar doğru mu?**
   - `APP_ENV=production` olmalı
   - `APP_DEBUG=false` olmalı (production'da)
   - `BASE_URL=https://www.kuretemizlik.com/app` olmalı

2. **Config dosyaları tutarlı mı?**
   - `config/security.php` içindeki `env()` çağrıları `.env` dosyasındaki değerlerle tutarlı olmalı
   - `config/config.php` içindeki environment detection doğru çalışmalı

### Feature Flags

1. **Production default'ları:**
   - `SECURITY_MFA_ENABLED=false` (opt-in)
   - `SECURITY_ALERTS_ENABLED=false` (sadece log)
   - `EXTERNAL_LOGGING_ENABLED=false` (henüz yok)
   - `SECURITY_ANALYTICS_ENABLED=true` (sadece log, düşük risk)
   - `DB_WEB_MIGRATION_ENABLED=false` (sadece migration sırasında aç)

2. **Feature'ları açmak:**
   - `.env` dosyasında ilgili flag'i `true` yap
   - Uygulamayı yeniden başlat (gerekirse)
   - Test et

---

## 🔍 DOĞRULAMA KONTROL LİSTESİ

Deployment sonrası kontrol edilecekler:

- [ ] `env.production.example` dosyası lokalde hazır mı?
- [ ] Token değerleri production için değiştirildi mi? (güvenlik için)
- [ ] `.env` dosyası production'da mevcut ve doğru konumda mı? (`/app/.env`)
- [ ] `.env` dosyası web erişiminden korunmuş mu? (`.htaccess` ile)
- [ ] Environment variable'lar doğru yüklenmiş mi? (`APP_ENV=production`, `APP_DEBUG=false`)
- [ ] Base URL doğru mu? (`BASE_URL=https://www.kuretemizlik.com/app`)
- [ ] Uygulama çalışıyor mu? (`https://www.kuretemizlik.com/app`)
- [ ] Config dosyaları tutarlı mı? (`config/security.php`, `config/config.php`)
- [ ] Feature flag'ler doğru mu? (production default'ları)

---

## 📊 DOSYA ÖZET TABLOSU

| Dosya | Yol (Lokalde) | Yol (Production) | Kritik | Açıklama |
|-------|---------------|------------------|--------|----------|
| `env.production.example` | `env.production.example` | `/app/.env` | ✅ | Environment variables (MUTLAKA) |
| `index.php` | `index.php` | `/app/index.php` | ⚠️ | Router (değişiklik varsa gönder) |
| `config/config.php` | `config/config.php` | `/app/config/config.php` | ⚠️ | Core config (zaten var, skip et) |
| `config/security.php` | `config/security.php` | `/app/config/security.php` | ⚠️ | Security config (zaten var, skip et) |
| `env.production.example` | `env.production.example` | - | ❌ | Template (opsiyonel, lokalde tut) |
| `PRODUCTION_CONFIG_FINAL_SUMMARY.md` | `PRODUCTION_CONFIG_FINAL_SUMMARY.md` | - | ❌ | Dokümantasyon (opsiyonel) |
| `PRODUCTION_HARDENING_FINAL_CHECKLIST.md` | `PRODUCTION_HARDENING_FINAL_CHECKLIST.md` | - | ❌ | Dokümantasyon (opsiyonel) |
| `DEPLOYMENT_CHECKLIST.md` | `DEPLOYMENT_CHECKLIST.md` | - | ❌ | Dokümantasyon (opsiyonel) |
| `PRODUCTION_DEPLOYMENT_FILE_LIST.md` | `PRODUCTION_DEPLOYMENT_FILE_LIST.md` | - | ❌ | Dokümantasyon (opsiyonel) |

**Açıklama:**
- ✅ **Kritik:** Production için mutlaka gönderilmeli
- ⚠️ **Opsiyonel:** Değişiklik varsa gönder, yoksa skip et
- ❌ **Dokümantasyon:** Production'a gönderilmesi gerekmez (sadece referans)

---

## 🎯 SONUÇ

**Kritik Dosyalar (MUTLAKA GÖNDER):**

1. ✅ **`env.production.example` → `/app/.env`** (production'da `.env` olarak kaydet)
   - Bu dosya production config için **EN ÖNEMLİ** dosyadır
   - Diğer config dosyaları zaten production'da, sadece `.env` dosyasını göndermen yeterli

**Opsiyonel Dosyalar (Değişiklik Varsa):**

2. ⚠️ `index.php` (ROUND 10'da `/tools/security/analyze` endpoint'inde küçük bir değişiklik yapıldı)
   - Değişiklik yapılmadıysa skip edebilirsin

**Ops/Dokümantasyon (OPSİYONEL):**

3. ❌ `env.production.example` (sadece lokalde tut)
4. ❌ Tüm `.md` dokümantasyon dosyaları (sadece lokalde tut)

---

## 📝 ENV DOSYASI İÇERİĞİ ÖZETİ

`env.production.example` dosyası şu değişkenleri içerir:

### Environment
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_BASE=/app`
- `APP_CHARSET=UTF-8`

### Database
- `DB_PATH=db/app.sqlite`

### Security & Feature Flags
- `SECURITY_MFA_ENABLED=false`
- `SECURITY_ALERTS_ENABLED=false`
- `EXTERNAL_LOGGING_ENABLED=false`
- `SECURITY_ANALYTICS_ENABLED=true`
- `DB_WEB_MIGRATION_ENABLED=false`

### Tokens
- `OPS_STATUS_TOKEN=ops_7K9mL3nP5qR8tY2wX6vB4cN7hM9jL5kQ2sD8fG3aZ6xV1bC4nM7pL`
- `SECURITY_ANALYZE_TOKEN=sec_9N7pL2kQ5wR8tY3xV6bC9nM4hJ7kL2pQ5sD8fG3aZ6xV1bC4nM7pL9kQ2sD`
- `TASK_TOKEN=tsk_5R8tY2wX6vB4cN7hM9jL5kQ2sD8fG3aZ6xV1bC4nM7pL9kQ2sD8fG3aZ6xV`
- `DB_WEB_MIGRATION_TOKEN=ops_2H8Dk1v9Gq9uF7zP4mN6rT5wY3xK8jL2nM9vB4cX7hQ1sD5fG8aZ`

### Security
- `CSRF_SECRET=prod_7xK9mP2qR5sT8vW1yZ4bC6nM9pL3kQ6sD9fG2aZ5xV8bC1nM4pL7kQ0sD3fG6aZ9xV`
- `JWT_SECRET=prod_jwt_9aB2cD4eF6gH8iJ0kL2mN4pQ6rS8tU0vW2xY4zA6bC8dE0fG2hI4jK6lM8nP0qR`

### Production URL
- `BASE_URL=https://www.kuretemizlik.com/app`
- `APP_URL=https://www.kuretemizlik.com/app`

**Detaylar:** `env.production.example` dosyasına bak.

---

**ROUND 10 TAMAMLANDI** ✅

**Son Güncelleme:** 2025-01-XX

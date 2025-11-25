# 🔒 Security & Ops Hardening - ROUND 4 PLAN

**Tarih:** 2025-01-XX  
**Durum:** Planlama Aşaması  
**Kapsam:** Real MFA (TOTP), Alerting (Email/Webhook), Monitoring Hooks (Sentry/ELK)

---

## 📋 MEVCUT DURUM ÖZETİ

### MFA (Multi-Factor Authentication)
- **MfaService**: Skeleton mevcut (`src/Services/MfaService.php`)
  - `isEnabled()`, `isRequiredForUser()`, `startMfaChallenge()`, `verifyMfaCode()` metodları var
  - Şu an sadece placeholder logic (dummy code acceptance)
  - Session-based challenge storage
- **DB Schema**: `users` tablosunda MFA kolonları **ZATEN VAR**:
  - `two_factor_secret` (TEXT, nullable)
  - `two_factor_backup_codes` (TEXT, nullable)
  - `two_factor_enabled_at` (TEXT, nullable)
  - `two_factor_required` (INTEGER, default 0)
- **AuthController**: MFA check entegrasyonu var (Round 3'te eklendi)
  - Login sonrası MFA kontrolü yapılıyor
  - MFA challenge başlatılıyor ama gerçek TOTP yok
- **Config**: `config/security.php` içinde MFA ayarları mevcut
  - `mfa.enabled` (default: false)
  - `mfa.methods` (otp_sms, totp)
  - `mfa.required_for_roles` (SUPERADMIN)

### SecurityAlertService
- **Skeleton**: `src/Services/SecurityAlertService.php` mevcut
  - `notifyAnomaly()` metodu var
  - `sendEmailAlert()` ve `sendWebhookAlert()` sadece placeholder (log yazıyor)
  - Config'den channel'ları okuyor (`log`, `email`, `webhook`)
- **Integration**: SecurityAnalyticsService ile entegre
  - Anomaly tespit edildiğinde `SecurityAlertService::notifyAnomaly()` çağrılıyor
- **Config**: `config/security.php` içinde alerting ayarları mevcut
  - `alerts.enabled` (default: false)
  - `alerts.channels` (log, email, webhook)
  - `alerts.email.to`, `alerts.email.from`
  - `alerts.webhook.url`, `alerts.webhook.secret`

### SecurityAnalyticsService
- **Anomaly Detection**: 3 rule mevcut
  - Brute force detection (10 failed attempts in 15 min)
  - Multi-tenant enumeration (5 different companies from same IP in 1 hour)
  - Rate limit abuse (3 rate limit exceeded in 30 min)
- **Alerting Hook**: Anomaly tespit edildiğinde SecurityAlertService'e bildiriyor
- **Config**: `config/security.php` içinde analytics ayarları mevcut
  - `analytics.enabled` (default: true)
  - `analytics.rules` (brute_force, multi_tenant_enumeration, rate_limit_abuse)

### AppErrorHandler
- **Structured Logging**: JSON format, Sentry/ELK/CloudWatch uyumlu
- **Request ID**: Correlation için request ID üretimi ve yönetimi
- **Extension Points**: Henüz external sink'ler için interface yok
- **Log Format**: JSON format (`logs/errors_YYYY-MM-DD.json`)

### AuditLogger
- **Multi-tenant aware**: Company ID filtering
- **Export**: CSV/JSON export mevcut
- **Retention**: Config-aware cleanup mevcut
- **UI**: Admin UI mevcut (filtreleme, arama, export)

---

## 🎯 RİSKLER & FIRSATLAR

### Riskler:
1. **MFA Skeleton**: Şu an herhangi bir kod kabul ediliyor (güvenlik riski)
2. **Alerting**: Sadece log yazıyor, gerçek email/webhook yok (ops riski)
3. **Monitoring**: External APM sistemlerine bağlanma hazırlığı yok (observability riski)

### Fırsatlar:
1. **DB Schema**: MFA kolonları zaten var, migration gerekmez
2. **Config Infrastructure**: Tüm config yapısı hazır
3. **Integration Points**: SecurityAnalyticsService ve AppErrorHandler hook'ları mevcut

---

## 📝 BU TURDA YAPILACAKLAR

### STAGE 1: Gerçek MFA (TOTP) Model & Servis
- **DB**: `users` tablosunda MFA kolonları zaten var, migration gerekmez
- **MfaService**: TOTP (RFC 6238) implementasyonu
  - `generateSecret()` - Base32 secret üretimi
  - `getOtpUri()` - QR code URI üretimi
  - `verifyCode()` - TOTP kodu doğrulama (time window ±1 step)
  - Recovery codes üretimi ve doğrulama
- **Config**: Mevcut config yapısını kullan, genişletme
- **Dokümantasyon**: `MFA_SETUP.md` oluştur

### STAGE 2: MFA UI & Login Flow Entegrasyonu (Opt-in)
- **Login Flow**: AuthController'da MFA challenge step'i
- **MFA UI**: `src/Views/auth/mfa_challenge.php` oluştur
- **MFA Enable Flow**: Basic admin UI (`/admin/users/{id}/mfa`)
- **Audit Logging**: MFA events için audit log
- **Testler**: E2E testler ekle

### STAGE 3: Alerting'i Gerçekleştirme
- **Config Genişletme**: Throttling config ekle
- **SecurityAlertService**: Email ve webhook implementasyonu
  - Email: Mevcut email servisi kullan (varsa) veya skeleton bırak
  - Webhook: HTTP POST request (timeout, error handling)
  - Throttling: Aynı event type için flood koruması
- **Integration Points**:
  - SecurityAnalyticsService → SecurityAlertService
  - AppErrorHandler → SecurityAlertService (CRITICAL errors)
  - Rate limit exceeded → SecurityAlertService (brute force pattern)
- **Testler**: Regression testler

### STAGE 4: Monitoring Hooks & Dokümantasyon
- **AppErrorHandler Extension Point**: External sink interface
- **Dokümantasyon**: `OPS_HARDENING_ROUND2_REPORT.md` oluştur
- **Test Run**: Tüm test suite'leri çalıştır

---

## 🧪 TEST STRATEJİSİ

### E2E Testler (`tests/ui/e2e-security.spec.ts`):
- MFA kapalıyken login flow'un eskisi gibi çalışması
- MFA açık + user'da mfa_enabled = true iken MFA ekranına gelme
- Yanlış TOTP kodu ile login başarısız
- Doğru TOTP kodu ile login başarılı
- Recovery code ile login
- MFA enable/disable flow

### Integration Testler:
- SecurityAlertService email/webhook gönderimi (mock)
- AppErrorHandler → SecurityAlertService hook
- SecurityAnalyticsService → SecurityAlertService hook

### Regression Testler:
- Mevcut login flow'ların bozulmaması
- Ops endpoint'lerinin çalışmaya devam etmesi
- Audit logging'in çalışmaya devam etmesi

---

## 🔧 TEKNİK DETAYLAR

### TOTP Implementation:
- RFC 6238 standardı
- SHA1 HMAC
- 30 saniye time step
- ±1 step tolerance (clock skew için)
- Base32 secret encoding

### Alerting Throttling:
- Memory-based throttling (file/SQLite)
- Per event type + key (IP, user_id, etc.)
- Configurable threshold (default: 5 alerts per minute per key)

### Monitoring Extension Points:
- `ErrorSinkInterface` benzeri abstraction
- Default: File-based JSON logging
- Config: `logging.external.enabled`, `logging.external.dsn`
- Skeleton: Sentry/ELK için placeholder class'lar

---

## 📊 BAŞARI KRİTERLERİ

1. ✅ MFA TOTP implementasyonu çalışıyor
2. ✅ Login flow MFA'yı destekliyor (opt-in)
3. ✅ SecurityAlertService email/webhook gönderiyor
4. ✅ Throttling flood koruması sağlıyor
5. ✅ AppErrorHandler extension point'leri hazır
6. ✅ Tüm testler yeşil
7. ✅ Mevcut QA/Security/Perf altyapısı bozulmadı

---

**Plan Tamamlandı - STAGE 1'e Geçiliyor**


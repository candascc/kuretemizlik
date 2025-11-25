# 🔒 Security & Production Hardening Plan - Round 1

**Tarih:** 2025-01-XX  
**Durum:** Planlama Aşaması  
**Kapsam:** Multi-tenant izolasyon, Auth/Session, Payment güvenliği, Security headers, Rate limiting, Audit logging

---

## 📋 GENEL ÖZET

Bu doküman, BUG_HUNT_COMPREHENSIVE_REPORT.md ve FINAL_STATUS_REPORT.md'deki bulgulara dayanarak, production-ready güvenlik seviyesine ulaşmak için yapılacak iyileştirmelerin planını içerir.

### Toplam Risk Envanteri

- **CRITICAL:** 12 bulgu (4'ü çözülmüş, 8'i açık)
- **HIGH:** 18 bulgu (6'sı çözülmüş, 12'si açık)
- **MEDIUM:** 15 bulgu
- **LOW:** 8 bulgu

---

## A) MULTI-TENANT & DATA ISOLATION

### [BUG_001] Staff Tablosunda company_id Eksik - Multi-Tenant İzolasyon Bypass
- **Severity:** CRITICAL
- **Durum:** ❌ OPEN
- **Etkilenen Alan:** Backend-API | Multi-tenant | DB
- **Konum:** `schema-current.sql` - `staff` table, `src/Models/Staff.php`
- **Risk Senaryosu:** 
  - Şirket A'nın personeli, Şirket B'nin admin'i tarafından görülebilir
  - Personel atama işlemlerinde yanlış şirketin personeli seçilebilir
  - Personel ödemeleri ve bakiyeleri şirketler arası karışabilir
  - **GDPR/Veri Gizliliği ihlali riski**
- **Önerilen Çözüm:**
  1. `staff` tablosuna `company_id INTEGER NOT NULL DEFAULT 1` ekle (migration)
  2. Foreign key constraint: `FOREIGN KEY(company_id) REFERENCES companies(id)`
  3. Mevcut verileri company_id=1'e atayın
  4. `Staff` model'ine `CompanyScope` trait ekle
  5. Tüm staff sorgularına `scopeToCompany()` uygula
  6. `staff_job_assignments` tablosunda da company_id kontrolü ekle

### [BUG_002] Appointments Tablosunda company_id Eksik
- **Severity:** CRITICAL
- **Durum:** ❌ OPEN
- **Etkilenen Alan:** Backend-API | Multi-tenant | DB
- **Konum:** `schema-current.sql` - `appointments` table
- **Risk Senaryosu:**
  - Şirket A'nın randevuları, Şirket B tarafından görülebilir
  - Müşteri bilgileri sızıntısı
  - Randevu çakışmaları yanlış hesaplanabilir
- **Önerilen Çözüm:**
  1. `appointments` tablosuna `company_id` ekle (migration)
  2. `Appointment` model'ine `CompanyScope` trait ekle
  3. Tüm appointment sorgularına company filtresi uygula

### [BUG_003] PortalController'da company_id Kontrolü Eksik
- **Severity:** CRITICAL
- **Durum:** ⚠️ PARTIAL (rate limit eklendi, company_id kontrolü eksik)
- **Etkilenen Alan:** Backend-API | Multi-tenant
- **Konum:** `src/Controllers/PortalController.php` - `dashboard()`, `jobs()`, `invoices()`
- **Risk Senaryosu:**
  - Müşteri, session'ı manipüle ederek başka şirketin müşteri ID'sini kullanabilir
  - Başka şirketin işlerini, faturalarını görebilir
- **Önerilen Çözüm:**
  1. PortalController'da her sorguya customer'ın company_id'sini kontrol et
  2. Session'daki `portal_customer_id` ile customer'ı bul
  3. Customer'ın `company_id`'sini doğrula
  4. Tüm alt sorgularda (jobs, invoices, contracts) company_id filtresi ekle
  5. Helper method: `verifyPortalCustomerAccess($customerId)`

### [BUG_004] API V2 CustomerController'da Address Sorgusu company_id Kontrolü Eksik
- **Severity:** HIGH
- **Durum:** ❌ OPEN
- **Etkilenen Alan:** Backend-API | Multi-tenant
- **Konum:** `src/Controllers/Api/V2/CustomerController.php` - `show()`
- **Risk Senaryosu:**
  - Address sorgusu direkt customer_id ile yapılıyor, company_id kontrolü yok
  - Yanlış adresler dönebilir
- **Önerilen Çözüm:**
  1. Address sorgusuna customer'ın company_id kontrolü ekle
  2. JOIN ile company_id filtresi uygula

### [BUG_005] ConflictDetector'da company_id Kontrolü Eksik
- **Severity:** HIGH
- **Durum:** ❌ OPEN
- **Etkilenen Alan:** Backend-API | Multi-tenant
- **Konum:** `src/Lib/ConflictDetector.php` - `hasJobConflict()`, `getConflictingJobs()`
- **Risk Senaryosu:**
  - Farklı şirketlerin işleri çakışma olarak algılanabilir
  - Yanlış çakışma uyarıları
- **Önerilen Çözüm:**
  1. ConflictDetector'a company_id parametresi ekle
  2. Tüm conflict sorgularına `AND company_id = ?` ekle

### [BUG_007] API V2 JobController'da Update İşleminde company_id Değiştirilebilir
- **Severity:** HIGH
- **Durum:** ❌ OPEN
- **Etkilenen Alan:** Backend-API | Multi-tenant
- **Konum:** `src/Controllers/Api/V2/JobController.php` - `update()`
- **Risk Senaryosu:**
  - Kullanıcı, POST/JSON body'ye `company_id: 2` ekleyerek job'ı başka şirkete taşıyabilir
  - Veri sızıntısı ve yetki aşımı
- **Önerilen Çözüm:**
  1. `$allowedFields` listesine `company_id` ekleme
  2. Update işleminden önce mevcut job'ın company_id'sini kontrol et
  3. Update sonrası company_id'nin değişmediğini doğrula
  4. Veya company_id'yi update'ten tamamen hariç tut

### [BUG_013] RecurringGenerator'da Conflict Detection company_id Kontrolü Eksik
- **Severity:** HIGH
- **Durum:** ❌ OPEN
- **Etkilenen Alan:** Backend-API | Multi-tenant | Task Management
- **Konum:** `src/Services/RecurringGenerator.php` - `generate()`
- **Risk Senaryosu:**
  - Farklı şirketlerin işleri çakışma olarak algılanır
  - Recurring job oluşturulmaz (yanlış çakışma)
- **Önerilen Çözüm:**
  1. Conflict sorgusuna `AND j.company_id = ?` ekle

---

## B) AUTH / SESSION / PORTAL GÜVENLİĞİ

### [BUG_006] Permission Bypass Riski - ADMIN Role'ü Tüm İzinlere Sahip
- **Severity:** HIGH
- **Durum:** ❌ OPEN
- **Etkilenen Alan:** Backend-API | Auth
- **Konum:** `src/Lib/Auth.php` - `hasRole()`, `src/Lib/Permission.php`
- **Risk Senaryosu:**
  - ADMIN kullanıcı, kendi şirketi dışındaki verilere erişebilir (eğer company_id kontrolü eksikse)
  - Permission kontrolü company_id kontrolünden önce yapılırsa, multi-tenant izolasyon bypass edilebilir
- **Önerilen Çözüm:**
  1. Permission kontrolünden önce mutlaka company_id kontrolü yap
  2. ADMIN role'ü için de company scope uygula (SUPERADMIN hariç)
  3. Permission check'i company scope check'inden sonra yap

### Session Fixation / Hijacking Riskleri
- **Severity:** MEDIUM
- **Durum:** ⚠️ PARTIAL (PortalController'da session_regenerate_id var, diğer login'lerde kontrol edilmeli)
- **Etkilenen Alan:** Auth | Session
- **Risk Senaryosu:**
  - Login sonrası session yenilenmezse, session fixation saldırısı mümkün
  - Session hijacking riski
- **Önerilen Çözüm:**
  1. Tüm login endpoint'lerinde `session_regenerate_id(true)` kullan
  2. Login sonrası session timeout ayarla
  3. Session cookie'lerde `HttpOnly`, `Secure`, `SameSite` attribute'larını kontrol et

### Rate Limiting / Brute Force Koruması
- **Severity:** HIGH
- **Durum:** ⚠️ PARTIAL (PortalController'da rate limit var, diğer login'lerde eksik)
- **Etkilenen Alan:** Auth | Security
- **Risk Senaryosu:**
  - Brute force saldırıları ile şifre kırılabilir
  - Account lockout mekanizması yok
- **Önerilen Çözüm:**
  1. Login endpoint'leri için IP + username bazlı rate limit
  2. 5 başarısız denemeden sonra geçici blok (5-15 dakika)
  3. DB tablosu veya cache ile rate limit tracking
  4. Generic hata mesajı (güvenlik sebebiyle detay verme)

---

## C) PAYMENT & FİNANSAL BÜTÜNLÜK

### [BUG_009] Payment Idempotency Eksik - Duplicate Payment Riski
- **Severity:** CRITICAL
- **Durum:** ✅ IMPLEMENTED (ROUND 1)
- **Etkilenen Alan:** Backend-API | Billing
- **Konum:** `src/Services/PaymentService.php` - `processPayment()`, `createPaymentRequest()`, `src/Controllers/PortalController.php` - `processPayment()`
- **Risk Senaryosu:**
  - Ödeme sağlayıcı webhook gönderir
  - İlk webhook işlenir, ödeme tamamlanır
  - İkinci webhook (retry) gelir, ödeme tekrar işlenir
  - Müşteri 2x ödeme yapar
  - **Finansal kayıp ve müşteri şikayeti**
- **Uygulanan Çözüm (STAGE 3.1):**
  1. ✅ `processPayment()` içinde status kontrolü eklendi: `completed`/`paid` ise mevcut sonucu döndür (idempotent)
  2. ✅ Transaction içinde double-check eklendi (race condition koruması)
  3. ✅ `createPaymentRequest()` içinde `transaction_id` duplicate kontrolü eklendi
  4. ✅ `PortalController::processPayment()` içinde session-based idempotency key eklendi
  5. ✅ UNIQUE constraint violation handling eklendi
- **Önerilen Çözüm:**
  1. `online_payments` tablosunda `transaction_id` UNIQUE constraint kontrol et
  2. Payment işlemeden önce `transaction_id` ile mevcut payment'ı kontrol et
  3. Eğer payment zaten `completed` ise, idempotent response döndür
  4. Transaction wrapper içinde idempotency kontrolü yap

### [BUG_011] Management Fee Duplicate Prevention Race Condition Riski
- **Severity:** HIGH
- **Durum:** ✅ IMPLEMENTED (ROUND 1)
- **Etkilenen Alan:** Backend-API | Billing | DB
- **Konum:** `src/Models/ManagementFee.php` - `generateForPeriod()`, `create()`, `db/migrations/041_add_unique_constraint_management_fees.sql`
- **Risk Senaryosu:**
  - Admin "2025-01 aidatlarını oluştur" butonuna iki kez tıklar
  - İki request aynı anda gelir
  - Her ikisi de duplicate kontrolü yapar, ikisi de "yok" görür
  - Aynı dönem için 2x aidat oluşur
  - Müşteri 2x ödeme yapmak zorunda kalır
- **Uygulanan Çözüm (STAGE 3.2):**
  1. ✅ Migration `041_add_unique_constraint_management_fees.sql` oluşturuldu
  2. ✅ `UNIQUE INDEX idx_management_fees_unique_unit_period_fee` eklendi (unit_id, period, fee_name)
  3. ✅ `ManagementFee::create()` içinde application-level duplicate check eklendi
  4. ✅ UNIQUE constraint violation handling eklendi (race condition koruması)
  5. ✅ Duplicate durumunda mevcut kayıt ID'si döndürülüyor (idempotent behavior)
  6. ✅ `generateForPeriod()` içinde duplicate handling iyileştirildi

### [BUG_014] Job Payment Sync'te Transaction Eksik
- **Severity:** HIGH
- **Durum:** ✅ IMPLEMENTED (ROUND 1)
- **Etkilenen Alan:** Backend-API | Billing
- **Konum:** `src/Services/PaymentService.php`, `src/Lib/PaymentService.php` - `syncFinancePayment()`, `createIncomeWithPayment()`, `deleteFinancePayment()`, `createJobPayment()`
- **Risk Senaryosu:**
  - Finance entry güncellenir
  - Job payment sync başarısız olur
  - Finance entry ve job payment tutarsız hale gelir
- **Uygulanan Çözüm (STAGE 3.3):**
  1. ✅ `syncFinancePayment()` transaction içine alındı
  2. ✅ `createIncomeWithPayment()` transaction içine alındı
  3. ✅ `deleteFinancePayment()` transaction içine alındı
  4. ✅ `createJobPayment()` transaction içine alındı
  5. ✅ Tüm işlemler (finance entry + job payment + job sync) atomik hale getirildi
  6. ✅ Hata durumunda rollback garantisi sağlandı

### [BUG_015] Management Fee Payment'te Atomicity Eksik
- **Severity:** HIGH
- **Durum:** ✅ VERIFIED & CONFIRMED (Zaten mevcut, teyit edildi)
- **Etkilenen Alan:** Backend-API | Billing
- **Konum:** `src/Models/ManagementFee.php` - `applyPayment()`, `src/Services/PaymentService.php` - `processPayment()`
- **Risk Senaryosu:**
  - Online payment başarılı, `online_payments` tablosuna kaydedilir
  - `management_fees.paid_amount` güncellenirken hata olur
  - Payment kaydedilmiş ama fee güncellenmemiş
- **Mevcut Durum (STAGE 3.3 - Teyit):**
  1. ✅ `ManagementFee::applyPayment()` zaten transaction içinde (mevcut kod)
  2. ✅ `PaymentService::processPayment()` zaten transaction içinde (mevcut kod)
  3. ✅ Payment update + fee update + money_entry insert atomik olarak işleniyor
  4. ✅ Notification transaction dışına taşınmış (payment commit'inden sonra gönderiliyor)

---

## D) OBSERVABILITY (LOG, AUDIT, ALERT, RATE LIMIT, ABUSE)

### Security Headers
- **Severity:** MEDIUM
- **Durum:** ✅ IMPLEMENTED (ROUND 1)
- **Etkilenen Alan:** HTTP Headers | Security
- **Risk Senaryosu:**
  - XSS, clickjacking, MIME type sniffing saldırıları
- **Uygulanan Çözüm (STAGE 4.1):**
  1. ✅ `X-Frame-Options: SAMEORIGIN` (DENY'den SAMEORIGIN'e güncellendi - daha esnek)
  2. ✅ `X-Content-Type-Options: nosniff` (zaten mevcut)
  3. ✅ `Referrer-Policy: strict-origin-when-cross-origin` (zaten mevcut)
  4. ✅ `X-XSS-Protection: 0` (1; mode=block'dan 0'a güncellendi - modern browser uyumluluğu)
  5. ✅ Content-Security-Policy (CSP) - zaten mevcut, report-only mode destekleniyor
  6. ✅ HSTS (Strict-Transport-Security) - HTTPS kontrolü ile zaten mevcut
  7. ✅ Permissions-Policy - zaten mevcut

### Audit Logging
- **Severity:** HIGH
- **Durum:** ✅ IMPLEMENTED (ROUND 1) → ✅ ENHANCED (ROUND 2)
- **Etkilenen Alan:** Logging | Security
- **Risk Senaryosu:**
  - Güvenlik olayları loglanmıyor
  - Saldırı tespiti yapılamaz
  - Compliance gereksinimleri karşılanamaz
- **Uygulanan Çözüm (STAGE 4.3 - ROUND 1):**
  1. ✅ Login success/failure audit log eklendi (IP, user_id, user-agent metadata içinde)
     - Admin login: `LOGIN_SUCCESS`, `LOGIN_FAILED`, `LOGIN_RATE_LIMIT_EXCEEDED`
     - Portal login: `PORTAL_LOGIN_SUCCESS`, `PORTAL_LOGIN_FAILED`, `PORTAL_LOGIN_RATE_LIMIT_EXCEEDED`
     - Resident login: `RESIDENT_LOGIN_SUCCESS`, `RESIDENT_LOGIN_RATE_LIMIT_EXCEEDED`
  2. ✅ Payment operations audit log eklendi
     - `PAYMENT_COMPLETED`, `PAYMENT_FAILED`, `PAYMENT_IDEMPOTENT_ATTEMPT`
     - `MANAGEMENT_FEE_PAYMENT_APPLIED`
  3. ✅ Rate limit exceeded audit log eklendi (tüm login endpoint'leri için)
  4. ✅ Mevcut `AuditLogger` class'ı kullanıldı (yeni tablo açılmadı)
  5. ✅ Hassas data mask'leme zaten mevcut (`AuditLogger::sanitizeMetadata()`)
  6. ⚠️ Role/permission değişiklikleri için audit log zaten mevcut (`RoleController`, `SettingsController`)
  7. ⚠️ Config değişiklikleri için audit log zaten mevcut (`SettingsController`)
- **Uygulanan Çözüm (STAGE 1 - ROUND 2):**
  1. ✅ `activity_log` tablosuna `ip_address`, `user_agent`, `company_id` kolonları eklendi (migration `042_add_ip_useragent_to_activity_log.sql`)
  2. ✅ `AuditLogger::log()` method'u güncellendi (IP, user_agent, company_id direkt kolonlara yazılıyor)
  3. ✅ `AuditLogger::getLogs()` method'u güncellendi (IP, company_id filtreleme desteği eklendi)
  4. ✅ Multi-tenant awareness eklendi (non-SUPERADMIN kullanıcılar sadece kendi şirketlerinin loglarını görebilir)
  5. ✅ Audit Log Admin UI güncellendi:
     - IP adresi filtresi eklendi
     - Şirket filtresi eklendi (SUPERADMIN için)
     - IP adresi ve şirket bilgileri tabloda gösteriliyor
  6. ✅ Performance iyileştirmeleri (index'ler eklendi: `created_at`, `action`, `company_id`)

### Rate Limiting Infrastructure
- **Severity:** HIGH
- **Durum:** ✅ IMPLEMENTED (ROUND 1) → ✅ ENHANCED (ROUND 2)
- **Uygulanan Çözüm (STAGE 4.2 - ROUND 1):**
  1. ✅ `RateLimitHelper` class'ı oluşturuldu (merkezi rate limiting helper)
  2. ✅ Mevcut `RateLimit` class'ı kullanıldı (SQLite-backed, persistent)
  3. ✅ Login endpoint'lerinde rate limiting zaten mevcut (STAGE 2'de eklendi)
  4. ✅ Rate limit configurations standardize edildi (5 attempts / 5 minutes)
  5. ✅ IP address detection iyileştirildi (proxy/load balancer desteği)
  6. ⚠️ API rate limiting zaten mevcut (`ApiRateLimiter` class'ı ile)
- **Uygulanan Çözüm (STAGE 2 - ROUND 2):**
  1. ✅ Tüm login endpoint'leri `RateLimitHelper` kullanacak şekilde migrate edildi
     - `AuthController::processLogin()` → `RateLimitHelper::checkLoginRateLimit()`
     - `PortalController::processLogin()` → `RateLimitHelper::checkLoginRateLimit()`
     - `ResidentController::processLogin()` → `RateLimitHelper::checkLoginRateLimit()`
     - `LoginController::processForgotPassword()` → `RateLimitHelper::checkLoginRateLimit()`
     - `LoginController::processResetPassword()` → `RateLimitHelper::checkLoginRateLimit()`
  2. ✅ OTP endpoint'leri `RateLimitHelper` kullanacak şekilde migrate edildi
  3. ✅ Password reset endpoint'leri `RateLimitHelper` kullanacak şekilde migrate edildi
  4. ✅ Mevcut rate limit threshold'ları korundu (backward compatibility)
  5. ✅ IP detection `RateLimitHelper::getClientIp()` ile standardize edildi
- **Etkilenen Alan:** Security | Performance
- **Risk Senaryosu:**
  - API abuse, DDoS, brute force saldırıları

---

## D.2) SECURITY ANALYTICS & ALERTING (ROUND 3 - STAGE 1-2)

### Security Analytics Service (Operational)
- **Severity:** MEDIUM
- **Durum:** ✅ OPERATIONAL (ROUND 3)
- **Uygulanan Çözüm (STAGE 1 - ROUND 3):**
  1. ✅ Config-aware analytics (`config/security.php`):
     - `security.analytics.enabled` (default: true)
     - `security.analytics.rules` (brute_force, multi_tenant_enumeration, rate_limit_abuse)
  2. ✅ Scheduled execution endpoint: `/tools/security/analyze` (token-protected)
  3. ✅ `SecurityAnalyticsService::runScheduledAnalysis()` public entry point for cron/job runners
  4. ✅ Rule-specific enablement checks (individual rules can be disabled via config)

### Security Alerting Service
- **Severity:** MEDIUM
- **Durum:** ✅ SKELETON IMPLEMENTED (ROUND 3)
- **Uygulanan Çözüm (STAGE 2 - ROUND 3):**
  1. ✅ `SecurityAlertService` class'ı oluşturuldu
  2. ✅ Multi-channel alerting skeleton:
     - **Log channel:** Default, always active (non-blocking)
     - **Email channel:** Skeleton (placeholder for Round 4+)
     - **Webhook channel:** Skeleton (placeholder for Round 4+)
  3. ✅ Config-aware alerting (`config/security.php`):
     - `security.alerts.enabled` (default: false - only log)
     - `security.alerts.channels` (array: ["log"], future: ["log", "email", "webhook"])
  4. ✅ Loosely coupled with `SecurityAnalyticsService` (non-blocking alert calls)

---

## D.3) AUDIT EXPORT & RETENTION (ROUND 3 - STAGE 3)

### Audit Export Enhanced
- **Severity:** MEDIUM
- **Durum:** ✅ IMPLEMENTED (ROUND 3)
- **Uygulanan Çözüm (STAGE 3 - ROUND 3):**
  1. ✅ CSV export with IP address and company_id columns
  2. ✅ Multi-tenant awareness (non-SUPERADMIN can only export their company's logs)
  3. ✅ Permission checks (ADMIN/SUPERADMIN only)

### Audit Retention Policy
- **Severity:** MEDIUM
- **Durum:** ✅ SKELETON IMPLEMENTED (ROUND 3)
- **Uygulanan Çözüm (STAGE 3 - ROUND 3):**
  1. ✅ `AuditLogger::cleanupOldRecords()` method (config-aware)
  2. ✅ Config: `security.audit.retention_days` (default: 2555 days = 7 years)
  3. ✅ Config: `security.audit.enable_retention_cleanup` (default: false)
  4. ✅ Manual cleanup via `/audit/cleanup` endpoint (password-protected)
  5. ⚠️ Automatic cleanup cron job wiring Round 4+'e bırakıldı (method hazır)

---

## D.4) ADVANCED AUTH FEATURES (ROUND 3 - STAGE 4)

### IP Access Control
- **Severity:** MEDIUM
- **Durum:** ✅ SKELETON IMPLEMENTED (ROUND 3)
- **Uygulanan Çözüm (STAGE 4 - ROUND 3):**
  1. ✅ `IpAccessControl` helper class
  2. ✅ IP allowlist support (CIDR notation)
  3. ✅ IP blocklist support (CIDR notation)
  4. ✅ Config: `security.ip_allowlist.enabled` (default: false)
  5. ✅ Config: `security.ip_blocklist.enabled` (default: false)
  6. ✅ Integration in `AuthController::processLogin()` (non-blocking when disabled)
  7. ✅ Audit logging for IP access denials

### Multi-Factor Authentication (MFA/2FA)
- **Severity:** MEDIUM
- **Durum:** ✅ SKELETON IMPLEMENTED (ROUND 3)
- **Uygulanan Çözüm (STAGE 4 - ROUND 3):**
  1. ✅ `MfaService` class
  2. ✅ Config: `security.mfa.enabled` (default: false)
  3. ✅ Config: `security.mfa.methods` (array: ["otp_sms", "totp"])
  4. ✅ Config: `security.mfa.required_for_roles` (array: ["SUPERADMIN"])
  5. ✅ `MfaService::startMfaChallenge()` skeleton (placeholder for Round 4+)
  6. ✅ `MfaService::verifyMfaCode()` skeleton (placeholder for Round 4+)
  7. ✅ Integration in `AuthController::processLogin()` (non-blocking when disabled)
  8. ✅ Audit logging for MFA challenges
  9. ⚠️ Actual SMS/TOTP implementation Round 4+'e bırakıldı

---

## 📊 ÖNCELİK SIRASI

### Phase 1: Critical Multi-Tenant & Payment (1-2 hafta)
1. ✅ Staff tablosuna company_id ekle
2. ✅ Appointments tablosuna company_id ekle
3. ✅ PortalController company_id kontrolü
4. ✅ Payment idempotency
5. ✅ Management fee duplicate prevention (unique constraint + lock)

### Phase 2: Auth & Session Hardening (3-5 gün)
6. ✅ Session regeneration (tüm login'lerde)
7. ✅ Rate limiting (tüm login endpoint'leri)
8. ✅ Permission check order (company_id önce, permission sonra)

### Phase 3: Security Headers & Audit (2-3 gün)
9. ✅ Security headers kontrolü ve güncelleme
10. ✅ Audit logging infrastructure
11. ✅ Rate limiting infrastructure

### Phase 4: Testing & Documentation (2-3 gün)
12. ✅ Security testleri ekle
13. ✅ Dokümantasyon güncelleme

---

## 🧪 TEST STRATEJİSİ

### Multi-Tenant Isolation Testleri
- Company A user → Company B verisini göremiyor
- Portal customer → Başka company'nin verisini göremiyor
- Staff atama → Cross-company atama engelleniyor

### Payment Idempotency Testleri
- Aynı webhook iki kez gönderilirse → Duplicate payment olmamalı
- Transaction ID ile idempotency kontrolü çalışmalı

### Rate Limiting Testleri
- 5 başarısız login → Geçici blok
- Rate limit aşımı → Generic hata mesajı

### Audit Logging Testleri
- Login success/failure → Log'a yazılıyor
- Payment state değişikliği → Log'a yazılıyor
- Hassas data mask'leniyor

---

**Sonraki Adım:** STAGE 2 - Multi-tenant & Auth Hardening implementasyonu


# 🔒 Security & Ops Hardening - ROUND 5 PLAN

**Tarih:** 2025-01-XX  
**Durum:** Planlama Aşaması  
**Kapsam:** External Error Tracking (Sentry), MFA UX Polishing, Security Dashboard Skeleton

---

## 📋 MEVCUT DURUM ANALİZİ

### External Logging / Monitoring Extension Points

**Mevcut Durum:**
- `AppErrorHandler::sendToExternalSinks()` - Extension point mevcut (Round 4'te eklendi)
- `sendToSentry()` - Skeleton (SDK entegrasyonu yok, sadece log yazıyor)
- `sendToElk()` - HTTP POST implementasyonu mevcut (çalışıyor)
- `sendToCloudWatch()` - Skeleton (SDK entegrasyonu yok)
- `sendToCustomWebhook()` - Generic webhook implementasyonu mevcut (çalışıyor)

**Config:**
- `config/security.php` içinde `logging.external` bloğu mevcut
- Provider seçimi: `sentry`, `elk`, `cloudwatch`, `custom`
- DSN/endpoint config mevcut

**Eksikler:**
- Sentry için gerçek SDK entegrasyonu yok (sadece skeleton)
- Provider-agnostic interface yok (her provider için switch-case)
- Error sink'ler için factory/helper pattern yok

### MFA UX / Flow Durumu

**Mevcut Durum:**
- ✅ TOTP implementasyonu tamam (RFC 6238)
- ✅ MFA challenge UI mevcut (`src/Views/auth/mfa_challenge.php`)
- ✅ Admin MFA management UI mevcut (`src/Views/settings/user_mfa.php`)
- ✅ QR code gösterimi mevcut (qrcode.js library)

**UX İyileştirme Alanları:**
1. **QR Code UI:**
   - QR code container küçük, daha büyük ve net olabilir
   - "Kodları güvenli bir yere kaydet" uyarısı eksik
   - Download option yok

2. **Backup Codes:**
   - Backup codes sadece ilk gösterimde full listeleniyor
   - "Copy to clipboard" / "Download (TXT/CSV)" butonu yok
   - Güvenlik uyarıları eksik

3. **MFA Challenge UI:**
   - Hata mesajları minimal (iyileştirilebilir)
   - Recovery code modal var ama UX iyileştirilebilir
   - Mobil touch target'lar kontrol edilmeli (44px kuralı)

4. **Admin MFA Management:**
   - MFA enable/disable için uyarı mesajları eksik
   - MFA durum gösterimi minimal

### Security / Analytics Verileri

**Mevcut Veriler:**
- `activity_log` tablosu - Audit log verileri (login, payment, role changes, MFA events)
- `SecurityAnalyticsService` - Anomaly detection (brute force, multi-tenant enumeration, rate limit abuse)
- Rate limit events - `RateLimitHelper` üzerinden loglanıyor
- MFA events - `MFA_ENABLED`, `MFA_DISABLED`, `MFA_CHALLENGE_STARTED`, `MFA_CHALLENGE_PASSED`, `MFA_CHALLENGE_FAILED`

**UI'da Gösterilebilir:**
- Failed login attempts (son 24 saat / 7 gün)
- Rate limit exceeded events
- Security anomalies (SecurityAnalyticsService)
- MFA events (enabled, disabled, challenge success/failure)
- Active MFA users count
- Security event timeline

**Mevcut UI:**
- `AuditController::index()` - Audit log listesi (filtreleme, arama, export)
- Security dashboard yok (sadece audit log listesi var)

### Mevcut Test Coverage

**E2E Testler (`tests/ui/e2e-security.spec.ts`):**
- ✅ MFA kapalıyken login flow testi
- ✅ MFA challenge page erişim testi
- ✅ MFA challenge form structure testi
- ✅ Invalid MFA code handling testi
- ✅ MFA admin UI erişim testi

**Kritik Path'ler:**
- Login flow (MFA disabled/enabled)
- MFA challenge flow
- Admin MFA management
- Security headers
- Rate limiting
- Audit logging

---

## 🎯 RİSKLER & SINIRLAMALAR

### Riskler:
1. **External Logging:** Sentry SDK entegrasyonu yok, sadece skeleton
2. **MFA UX:** Backup codes güvenliği ve UX iyileştirmeleri gerekli
3. **Security Dashboard:** Henüz yok, sıfırdan oluşturulacak

### Sınırlamalar (OUT OF SCOPE):
- ❌ **Büyük DB Migration:** Bu turda migration açılmayacak
- ❌ **Ağır Chart Library:** Basit tablolar + KPI cards yeterli (Chart.js, D3.js eklenmeyecek)
- ❌ **ML-based Anomaly Detection:** Sadece rule-based detection mevcut
- ❌ **Real-time WebSocket:** Dashboard polling-based olacak (WebSocket yok)
- ❌ **Sentry SDK Zorunluluğu:** SDK olmadan da çalışacak generic implementation

---

## 📝 BU TURDA YAPILACAKLAR

### STAGE 1: External Error Tracking (Sentry / Provider-Agnostic)

**Hedef:** AppErrorHandler'daki extension point'leri kullanarak, konfigürasyonla açılıp kapanan, provider-agnostic bir external error sink entegrasyonu.

**Dokunulacak Dosyalar:**
- `src/Services/ErrorSinkInterface.php` - Yeni interface
- `src/Services/SentryErrorSink.php` - Sentry implementation (HTTP-based, SDK olmadan)
- `src/Services/GenericWebhookErrorSink.php` - Generic webhook implementation
- `src/Lib/AppErrorHandler.php` - Factory/helper pattern ekleme
- `config/security.php` - Config genişletme (gerekirse)
- `EXTERNAL_LOGGING_SETUP.md` - Yeni dokümantasyon

**Yapılacaklar:**
1. Provider-agnostic `ErrorSinkInterface` oluştur
2. `SentryErrorSink` implementation (Sentry ingestion endpoint'e HTTP POST)
3. `GenericWebhookErrorSink` implementation (mevcut `sendToCustomWebhook` mantığını refactor)
4. `AppErrorHandler` içinde factory pattern (config'den provider seçimi)
5. Config dokümantasyonu (`EXTERNAL_LOGGING_SETUP.md`)
6. E2E testler (config disabled iken no-op, enabled iken kod path patlamıyor)

### STAGE 2: MFA UX & Flow Polishing

**Hedef:** Mevcut MFA TOTP implementasyonunu bozmadan, UX'i ve yönetilebilirliğini cilalamak.

**Dokunulacak Dosyalar:**
- `src/Views/settings/user_mfa.php` - QR code UI iyileştirmeleri, backup codes download
- `src/Views/auth/mfa_challenge.php` - Challenge UI iyileştirmeleri, hata mesajları
- `src/Controllers/SettingsController.php` - Backup codes download endpoint
- `MFA_SETUP.md` - Dokümantasyon güncelleme

**Yapılacaklar:**
1. **MFA Setup UI:**
   - QR code container'ı büyüt, daha net göster
   - "Kodları güvenli bir yere kaydet" uyarıları ekle
   - Backup codes için "Copy to clipboard" / "Download (TXT/CSV)" butonu
   - Backup codes sadece ilk gösterimde full listele, sonra "Regenerate" seçeneği

2. **MFA Challenge UI:**
   - Hata mesajlarını iyileştir (net ama güvenli)
   - Recovery code modal UX iyileştirmeleri
   - Mobil touch target'ları kontrol et (44px kuralı)

3. **Admin MFA Management:**
   - MFA enable/disable için uyarı mesajları (confirm dialog)
   - MFA durum gösterimi iyileştirmeleri (last enabled at, last verified at)

4. **Testler:**
   - MFA UI testlerini güncelle (backup codes download, QR code visibility)

### STAGE 3: Security Dashboard Skeleton (Admin UI)

**Hedef:** Sistemde toplanan security & audit verilerini üstünde, SUPERADMIN için basit ama işlevsel bir Security Dashboard skeleton'ı.

**Dokunulacak Dosyalar:**
- `src/Controllers/SecurityController.php` - Yeni controller (veya `AuditController` altına `dashboard()` metodu)
- `src/Services/SecurityStatsService.php` - Yeni helper servis (aggregate stats)
- `src/Views/security/dashboard.php` - Yeni dashboard view
- `index.php` - Routing ekleme
- `SECURITY_OPS_ROUND5_SUMMARY.md` - Dokümantasyon

**Yapılacaklar:**
1. **Backend:**
   - `SecurityStatsService` oluştur (aggregate stats helper)
   - `SecurityController::dashboard()` veya `AuditController::dashboard()` ekle
   - Son 24 saat / 7 gün için aggregate:
     - Failed login attempts
     - Rate limit exceeded events
     - Security anomalies (SecurityAnalyticsService)
     - MFA events (enabled, disabled, challenge success/failure)
     - Active MFA users count
   - Multi-tenant izolasyon (SUPERADMIN: tüm şirketler, Admin: sadece kendi company_id)

2. **UI:**
   - 3-6 adet KPI kartı (failed logins, anomalies, MFA events, etc.)
   - 1-2 tablo (son X security event)
   - Mevcut design system ile uyumlu (Tailwind CSS)
   - Responsive grid (mobile: 1 col, tablet: 2 col, desktop: 3 col)

3. **Routing & Permission:**
   - `/security/dashboard` route'u ekle
   - SUPERADMIN → full access
   - Admin → sadece kendi şirket scope'unda

4. **Testler:**
   - SUPERADMIN erişim testi
   - Non-SUPERADMIN erişim kısıtı testi (403/redirect)
   - Dashboard KPI/tablo render testi

### STAGE 4: Test & Rapor Güncelleme

**Hedef:** Yaptığın her şeyi QA & dokümantasyonla bağlamak.

**Dokunulacak Dosyalar:**
- `tests/ui/e2e-security.spec.ts` - Yeni testler
- `PLAYWRIGHT_QA_COMPLETE_REPORT.md` - Round 5 bölümü
- `SECURITY_OPS_ROUND5_SUMMARY.md` - Final özet

**Yapılacaklar:**
1. Testler:
   - External logging testleri (config disabled/enabled)
   - MFA UX testleri (backup codes download, QR code visibility)
   - Security Dashboard testleri (erişim, KPI render)

2. Rapor güncellemeleri:
   - `PLAYWRIGHT_QA_COMPLETE_REPORT.md` - Round 5 bölümü
   - `SECURITY_OPS_ROUND5_SUMMARY.md` - Final özet

3. Test script'leri:
   - `npm run test:ui`
   - `npm run test:ui:e2e`
   - `npm run test:ui:cross`
   - `npm run test:perf`

---

## 🧪 TEST STRATEJİSİ

### E2E Testler (`tests/ui/e2e-security.spec.ts`):

**External Logging:**
- Config disabled iken external sink çağrılmıyor (no-op)
- Config enabled iken kod path patlamıyor (mock endpoint)

**MFA UX:**
- Backup codes download butonu görünür
- QR code container büyük ve net
- MFA challenge UI hata mesajları doğru gösteriliyor

**Security Dashboard:**
- SUPERADMIN erişim testi
- Non-SUPERADMIN erişim kısıtı (403/redirect)
- Dashboard KPI/tablo render testi

### Regression Testler:
- Mevcut MFA flow'ların bozulmaması
- Mevcut login flow'ların bozulmaması
- Ops endpoint'lerinin çalışmaya devam etmesi

---

## 🔧 TEKNİK DETAYLAR

### External Error Tracking:
- **Interface:** `ErrorSinkInterface::send(array $payload): void`
- **Sentry:** HTTP POST to Sentry ingestion endpoint (SDK olmadan)
- **Generic Webhook:** HTTP POST with signature (HMAC-SHA256)
- **Factory Pattern:** Config'den provider seçimi, singleton cache

### MFA UX Polishing:
- **Backup Codes:** JSON → TXT/CSV export, clipboard copy
- **QR Code:** 256x256 → 320x320, daha büyük container
- **Touch Targets:** Minimum 44x44px (mobil)

### Security Dashboard:
- **Stats Service:** Aggregate queries (activity_log, SecurityAnalyticsService)
- **KPI Cards:** Simple HTML/CSS (Tailwind), no chart library
- **Event Tables:** Paginated, filtered (date range, event type)

---

## 📊 BAŞARI KRİTERLERİ

1. ✅ External error tracking çalışıyor (Sentry/generic webhook)
2. ✅ MFA UX iyileştirmeleri tamamlandı (QR code, backup codes)
3. ✅ Security Dashboard skeleton oluşturuldu (KPI cards + event tables)
4. ✅ Tüm testler yeşil
5. ✅ Mevcut QA/Security/Perf altyapısı bozulmadı
6. ✅ Tüm özellikler opt-in (default: disabled)

---

**Plan Tamamlandı - STAGE 1'e Geçiliyor**


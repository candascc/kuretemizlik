# ROUND 31 – STAGE 0: CONTEXT & ARKA PLAN (READ-ONLY)

**Tarih:** 2025-11-22  
**Round:** ROUND 31

---

## OKUNAN DOSYALAR VE ÖZETLER

### 1. PROD CRAWL & RAPORLAR

**PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json:**
- 71 sayfa crawl edilmiş
- `/app/jobs/new` → HTTP 500
- `/app/recurring/new` → Status 200 ama console'da "Server returned HTML instead of JSON"
- `/app/reports` → HTTP 403
- `/appointments` ve `/appointments/new` → HTTP 404

**PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.md:**
- Toplam 9 console error, 7 network error
- NETWORK_500: 2 adet
- NETWORK_403: 2 adet
- NETWORK_404: 10 adet

**PRODUCTION_ROUND30_ROOT_CAUSE_HARDENING_REPORT.md:**
- `/health` endpoint JSON-only guarantee yapıldı
- 404 page console error whitelist eklendi
- Output buffering ve enhanced exception handling uygulandı

### 2. TEST & QA

**tests/ui/prod-smoke.spec.ts:**
- Production smoke testleri
- `/health`, `/login`, `/jobs/new`, 404 page testleri mevcut
- Console error handling whitelist'leri var

**PLAYWRIGHT_QA_COMPLETE_REPORT.md:**
- ROUND 30 bölümü eklendi
- Test infrastructure mevcut

### 3. BACKLOG & KOD

**KUREAPP_BACKLOG.md:**
- JOB-01: /jobs/new 500 FIX → DONE (ROUND 29)
- REC-01: /recurring/new services JSON FIX → DONE (ROUND 29)
- TEST-01, TEST-02: ROUND 30'da eklendi

**index.php:**
- `/health` endpoint: ROUND 30'da JSON-only guarantee yapıldı
- Root route (`/`) ve `/app` route'ları mevcut
- Login flow mevcut

**Controller Dosyaları:**
- `JobController.php` - `/jobs/new` için `create()` metodu (ROUND 29'da fix edildi)
- `ApiController.php` - `/api/services` için `services()` metodu (ROUND 29'da fix edildi)
- `RecurringJobController.php` - `/recurring/new` için `create()` metodu
- `ReportController.php` - `/reports` için controller (muhtemelen)
- `AppointmentController.php` - `/appointments` için controller (muhtemelen)

---

## ENDPOINT → DOSYA MAPPING

| Endpoint | Controller | View | Durum |
|----------|------------|------|-------|
| `/` (root) | `DashboardController::today()` | `dashboard/today.php` | ✅ Çalışıyor |
| `/app` | `DashboardController::today()` veya redirect | `dashboard/today.php` | ⚠️ İlk açılışta 500 |
| `/health` | Closure in `index.php` | N/A (JSON) | ✅ ROUND 30'da fix edildi |
| `/jobs/new` | `JobController::create()` | `jobs/form.php` | ⚠️ PROD'da 500 |
| `/recurring/new` | `RecurringJobController::create()` | `recurring/form.php` | ⚠️ JSON error |
| `/api/services` | `ApiController::services()` | N/A (JSON) | ⚠️ HTML döndürüyor |
| `/reports` | `ReportController::index()` | `reports/index.php` | ⚠️ 403 |
| `/reports/*` | `ReportController::*()` | `reports/*.php` | ✅ Çalışıyor |
| `/privacy-policy` | N/A | N/A | ❌ 404 |
| `/terms-of-use` | N/A | N/A | ❌ 404 |
| `/status` | N/A | N/A | ❌ 404 |
| `/appointments` | N/A | N/A | ❌ 404 |
| `/appointments/new` | N/A | N/A | ❌ 404 |

---

## DAHA ÖNCE ÇÖZÜLEN HATALAR

1. **ROUND 30:**
   - `/health` endpoint JSON-only guarantee
   - 404 page console error whitelist

2. **ROUND 29:**
   - `/jobs/new` 500 fix (ama PROD'da hala 500 görünüyor - deploy farkı olabilir)
   - `/recurring/new` services JSON fix (ama PROD'da hala HTML döndürüyor)

---

## ROUND 31 KAPSAMINDA ELE ALINACAK HATALAR

1. **HOME-01:** `/app` first-load 500 after login
2. **JOB-01:** `/app/jobs/new` PROD'da HTTP 500 (ROUND 29 fix'i PROD'a deploy edilmemiş olabilir)
3. **REC-01:** `/app/recurring/new` "Server returned HTML instead of JSON"
4. **REP-01:** `/app/reports` 403 Forbidden
5. **LEGAL-01/02/03:** `/app/privacy-policy`, `/app/terms-of-use`, `/app/status` 404
6. **APPT-01/02:** `/appointments`, `/appointments/new` 404

---

**STAGE 0 TAMAMLANDI** ✅


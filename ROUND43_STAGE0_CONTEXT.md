# ROUND 43 – STAGE 0: KISA CONTEXT

**Tarih:** 2025-11-23  
**Round:** ROUND 43

---

## ENDPOINT BEKLENEN DAVRANIŞ TABLOSU

| Endpoint | ROUND 42 Sonrası Beklenen Davranış | Deployment Sonrası PROD'da Doğrulanacak Metrikler |
|----------|-----------------------------------|---------------------------------------------------|
| **`/app/jobs/new`** | Admin için 200 + form, hiçbir cihazda 500 olmaması | Status: 200, Content-Type: text/html, Console Error: 0, Network Error: 0 |
| **`/app/reports`** | Admin için 200 veya mantıklı redirect (403 değil) | Status: 200 veya 302, Content-Type: text/html, Console Error: 0, Network Error: 0 |
| **`/app/recurring/new`** | 200 + form, console'da "Server returned HTML instead of JSON" olmaması | Status: 200, Content-Type: text/html, Console Error: 0 (özellikle JSON parse error yok) |
| **`/app/api/services`** | JSON-only, Content-Type: application/json, HTML/500 yok | Status: 200 veya 401, Content-Type: application/json, JSON Parse: OK, Console Error: 0 |

---

## ROUND 42 KOD DEĞİŞİKLİKLERİ

1. **`app/src/Controllers/ReportController.php`**
   - `ReportController::financial()` metodunda:
     - `Auth::requireGroup()` → `Auth::hasGroup()` + redirect
     - `Auth::requireCapability()` → `Auth::hasCapability()` + redirect
     - Exception handling eklendi
     - ADMIN/SUPERADMIN için bypass eklendi

2. **`app/src/Controllers/ApiController.php`**
   - `ApiController::services()` metodunda:
     - `headers_sent()` kontrolü eklendi
     - Output buffering güçlendirildi
     - JSON-only guarantee güçlendirildi

---

**STAGE 0 TAMAMLANDI** ✅


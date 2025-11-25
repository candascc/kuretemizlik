# ROUND 44 – STAGE 0: KISA CONTEXT & SON PROD FOTOĞRAFI

**Tarih:** 2025-11-23  
**Round:** ROUND 44

---

## ENDPOINT SON PROD DAVRANIŞI TABLOSU

| Endpoint | Status (Smoke/Crawl) | Content-Type | Console Error Var mı? | Daha Önce Hangi Round'da Ne Fix Uygulanmış? | Hala Açık Problem |
|----------|---------------------|--------------|----------------------|-------------------------------------------|------------------|
| **`/app/jobs/new`** | Smoke: PASS (tablet/desktop), Crawl: 500 | - | Crawl: 1 | ROUND 34: Exception handling, redirect, output buffer. ROUND 42: Ek değişiklik yapılmadı. | Admin crawl'de hala 500 |
| **`/app/reports`** | Crawl: 403 | - | Crawl: 1 | ROUND 34: `View::forbidden()` yerine redirect. ROUND 42: `ReportController::financial()` düzeltildi. | Admin crawl'de hala 403 |
| **`/app/recurring/new`** | Crawl: 200 | - | Crawl: 1 ("Server returned HTML instead of JSON") | ROUND 34: `/api/services` auth middleware'den muaf. ROUND 42: `ApiController::services()` güçlendirildi. | Console'da HTML/JSON hatası var |
| **`/app/api/services`** | - | - | Crawl: 1 (recurring/new'den) | ROUND 34: Output buffering, JSON-only guarantee. ROUND 42: `headers_sent()` kontrolü, output buffering güçlendirildi. | Hala HTML döndürüyor |

---

## ANALİZ

### Ortak Pattern
- `/app/health` JSON-only, marker'lı → index.php ve deploy pipeline çalışıyor
- `/app/jobs/new`: Smoke PASS ama crawl FAIL → 500
- `/app/reports`: 403 → middleware/auth karmaşası
- `/app/recurring/new`: 200 ama console error → `/app/api/services` HTML döndürüyor

### Kök Sebep Hipotezi
- İki ayrı auth/error paradigması bir arada:
  - Eski: `Auth::require*()` → exception → global HTML 500/403 template
  - Yeni: controller içinde `has*` + redirect / JSON-only
- Bu karışım 500/403/HTML leak üretiyor

---

**STAGE 0 TAMAMLANDI** ✅


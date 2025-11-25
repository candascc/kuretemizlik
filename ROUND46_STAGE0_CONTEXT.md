# ROUND 46 – STAGE 0: CONTEXT

**Tarih:** 2025-11-23  
**Round:** ROUND 46 – REPORTS DASHBOARD (INDEX VIEW) + REP-01 SON KAPANIŞ

---

## MEVCUT DURUM

### Çözülen Endpoint'ler
- ✅ `/app/health` → JSON-only
- ✅ `/app/jobs/new` → 200
- ✅ `/app/recurring/new` + `/app/api/services` → 200, JSON-only
- ✅ `/app/reports/financial`, `/app/reports/jobs`, `/app/reports/customers`, `/app/reports/services` → 200

### Açık Issue
- ⚠️ `/app/reports` root endpoint'i → ROUND 45'te kod değişikliği yapıldı ama hala redirect yapıyor, gerçek dashboard view yok

---

## `ensureReportsAccess()` HELPER ÖZETİ

**Konum:** `app/src/Controllers/ReportController.php`

**Kullanım:**
- `index()` → Kullanıyor (ROUND 45)
- `financial()` → Kullanıyor (ROUND 45)
- `jobs()` → Kullanıyor (ROUND 45)
- `customers()` → Kullanıyor (ROUND 45)
- `services()` → Kullanıyor (ROUND 45)

**Davranış:**
- `Auth::check()` kontrolü yapıyor
- ADMIN/SUPERADMIN için bypass
- Diğer roller için `hasGroup('nav.reports.core')` kontrolü
- Yetkisiz ise redirect (403 yok)

---

## HEDEF

**ROUND 46'da:**
- `/app/reports` → 200 status ile gerçek bir "Raporlar Dashboard" view
- Auth kontrolü `ensureReportsAccess()` üzerinden (zaten var)
- Dashboard'da KPI'lar, son işler, top müşteriler, alt raporlara linkler
- Tasarım `financial.php` ile uyumlu

---

**STAGE 0 TAMAMLANDI** ✅


# ROUND 37 – STAGE 0: MARKER ÖZETİ

**Tarih:** 2025-11-22  
**Round:** ROUND 37

---

## MARKER ÖZETİ TABLOSU

| Marker String | Endpoint / View | Dosya | Tip |
|---------------|-----------------|-------|-----|
| **`KUREAPP_R36_MARKER_JOBS_VIEW_V1`** | `/app/jobs/new` → HTML view | `src/Views/jobs/form-new.php` (satır 4) | HTML Comment |
| **`KUREAPP_R36_MARKER_REPORTS_VIEW_V1`** | `/app/reports` → redirect to `/reports/financial` → HTML view | `src/Views/reports/financial.php` (satır 4) | HTML Comment |
| **`KUREAPP_R36_MARKER_HEALTH_JSON_V1`** | `/app/health` → JSON response | `index.php` `/health` handler (satır 739, 748, 765, 786) | JSON Field |

---

## BEKLENEN DAVRANIŞ

### 1. `/app/jobs/new`
- **Beklenen:** HTML source'da `<!-- KUREAPP_R36_MARKER_JOBS_VIEW_V1 -->` comment'i görünmeli
- **Status:** 200 (veya redirect, 500 olmamalı)

### 2. `/app/reports`
- **Beklenen:** `/reports/financial` redirect sonrası HTML source'da `<!-- KUREAPP_R36_MARKER_REPORTS_VIEW_V1 -->` comment'i görünmeli
- **Status:** 200 (redirect sonrası), 403 olmamalı

### 3. `/app/health`
- **Beklenen:** 
  - HTTP 200
  - `Content-Type: application/json; charset=utf-8`
  - JSON body'de `"marker": "KUREAPP_R36_MARKER_HEALTH_JSON_V1"` alanı olmalı
- **Status:** 200, HTML değil JSON döndürmeli

---

**STAGE 0 TAMAMLANDI** ✅


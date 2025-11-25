# ROUND 36 – STAGE 1: MARKER TASARIMI (PLAN)

**Tarih:** 2025-11-22  
**Round:** ROUND 36

---

## MARKER STRING'LERİ

### 1. Jobs New View Marker
**Marker:** `KUREAPP_R36_MARKER_JOBS_VIEW_V1`

**Kullanım Yeri:**
- `/app/jobs/new` sayfasının HTML çıktısına eklenecek
- HTML comment olarak: `<!-- KUREAPP_R36_MARKER_JOBS_VIEW_V1 -->`
- Sadece `/app/jobs/new` sayfasında görünmeli, başka hiçbir view'da olmamalı

**Plan:**
- `src/Controllers/JobController.php` → `create()` action'ı
- Bu action'ın render ettiği view dosyası: `src/Views/jobs/new.php` (veya benzeri)
- View dosyasının başına (form container'ın başına veya body'nin başına) marker comment eklenecek

---

### 2. Reports View Marker
**Marker:** `KUREAPP_R36_MARKER_REPORTS_VIEW_V1`

**Kullanım Yeri:**
- `/app/reports` endpoint'i default olarak `/reports/financial` sayfasına redirect ediyorsa:
  - O sayfanın HTML çıktısına eklenecek
- Eğer `/app/reports` direkt bir view render ediyorsa:
  - O view'ın HTML çıktısına eklenecek
- HTML comment olarak: `<!-- KUREAPP_R36_MARKER_REPORTS_VIEW_V1 -->`
- Sadece reports ana sayfasında görünmeli

**Plan:**
- `src/Controllers/ReportController.php` → `index()` action'ı
- Bu action'ın render ettiği view dosyası veya redirect target'ı tespit edilecek
- İlgili view dosyasının başına marker comment eklenecek

---

### 3. Health JSON Marker
**Marker:** `KUREAPP_R36_MARKER_HEALTH_JSON_V1`

**Kullanım Yeri:**
- `/app/health` endpoint'inin JSON çıktısına eklenecek
- JSON objesi içinde `marker` alanı olarak: `"marker": "KUREAPP_R36_MARKER_HEALTH_JSON_V1"`
- Her health çağrısında bu marker alanı olmalı
- Content-Type: `application/json; charset=utf-8` header'ı içerikten önce set edilmeli

**Plan:**
- `index.php` içinde `/health` endpoint handler'ı
- JSON çıktısını oluşturduğu noktada `marker` alanı eklenecek
- Error durumunda da marker alanı olmalı

---

## MARKER ÖZELLİKLERİ

1. **Benzersizlik:** Her marker string'i başka yerlerde kullanılmayacak
2. **Aranabilirlik:** Test ve crawl raporlarında "text search" ile bulunabilir olacak
3. **Versiyonlama:** `_V1` suffix'i ile versiyonlama yapıldı (ileride `_V2` gibi güncellemeler yapılabilir)
4. **Açıklayıcı:** Marker string'i kendisi ne olduğunu açıklıyor (JOBS_VIEW, REPORTS_VIEW, HEALTH_JSON)

---

## MEVCUT BUILD_TAG YAPISI

- `KUREAPP_BUILD_TAG` zaten kullanılıyorsa, onu aynen koruyacağız
- Marker sadece ek bilgi, build tag'in yerine geçmeyecek
- Health endpoint'inde hem `build` hem de `marker` alanı olacak

---

**STAGE 1 TAMAMLANDI** ✅


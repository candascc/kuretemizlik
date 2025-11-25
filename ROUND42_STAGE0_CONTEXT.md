# ROUND 42 – STAGE 0: CONTEXT & BUG FOTOĞRAFI

**Tarih:** 2025-11-23  
**Round:** ROUND 42

---

## ENDPOINT ÖZET TABLOSU

| Endpoint | Son Prod Durumu | Daha Önce Yapılan Fix | Hala Açık Problem |
|----------|-----------------|----------------------|-------------------|
| **`/app/jobs/new`** | 500 (admin crawl'de) | ROUND 34: Exception handling, redirect, output buffer temizleme | 500 hala oluşuyor, ROUND 34 fix'leri deploy edilmemiş |
| **`/app/reports`** | 403 (admin crawl'de) | ROUND 34: `View::forbidden()` yerine redirect | 403 hala oluşuyor, ROUND 34 fix'leri deploy edilmemiş |
| **`/app/recurring/new`** | 200 ama console'da "Server returned HTML instead of JSON" | ROUND 34: `/api/services` auth middleware'den muaf, JSON-only guarantee | Console error hala görünüyor, ROUND 34 fix'leri deploy edilmemiş |
| **`/app/api/services`** | Bazı senaryolarda HTML veya 500 | ROUND 34: Output buffering, JSON-only guarantee, `Throwable` catch | HTML/500 hala dönebiliyor, ROUND 34 fix'leri deploy edilmemiş |

---

## DETAYLI BİLGİLER

### `/app/jobs/new`
- **ROUND 34 Fix'leri:**
  - Output buffer temizleme
  - Exception durumunda redirect
  - `Auth::requireCapability()` yerine `Auth::hasCapability()` + redirect
  - View tarafında defensive variable initialization
- **Hala Açık Problem:**
  - 500 error page döndürüyor (admin crawl'de)
  - ROUND 34 fix'leri production'a deploy edilmemiş

### `/app/reports`
- **ROUND 34 Fix'leri:**
  - `View::forbidden()` yerine redirect
  - `Auth::requireGroup()` yerine `Auth::hasGroup()` + redirect
- **Hala Açık Problem:**
  - 403 Forbidden döndürüyor (admin crawl'de)
  - ROUND 34 fix'leri production'a deploy edilmemiş

### `/app/recurring/new` + `/app/api/services`
- **ROUND 34 Fix'leri:**
  - `/api/services` auth middleware'den muaf
  - Output buffering, JSON-only guarantee
  - `Throwable` catch
- **Hala Açık Problem:**
  - Console'da "Server returned HTML instead of JSON" hatası görünüyor
  - ROUND 34 fix'leri production'a deploy edilmemiş

---

**STAGE 0 TAMAMLANDI** ✅


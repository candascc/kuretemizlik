# ROUND 32 – STAGE 2: ROUND 31 BEKLENTİLERİ VS GERÇEK PROD FARK ANALİZİ

**Tarih:** 2025-11-22  
**Round:** ROUND 32

---

## FARK ANALİZİ TABLOSU

| ID | URL/Akış | Kategori | Kısa Açıklama | Round 31 Durumu |
|----|----------|----------|---------------|-----------------|
| **JOB-01** | `/app/jobs/new` | **RESURFACED** | HTTP 500 dönüyor | ROUND 31'de DONE |
| **REP-01** | `/app/reports` | **RESURFACED** | HTTP 403 dönüyor (admin için) | ROUND 31'de DONE |
| **REC-01** | `/app/recurring/new` | **RESURFACED** | Console error: "Server returned HTML instead of JSON" | ROUND 31'de DONE |
| **TEST-01** | `/health` | **RESURFACED** | Content-Type `text/html` dönüyor, `application/json` bekleniyor | ROUND 30'da DONE |
| **URL-01** | `ointments`, `ointments/new` | **RESURFACED** | URL normalization sorunu, 404 dönüyor | ROUND 27'de düzeltilmişti |

---

## DETAYLI ANALİZ

### JOB-01: `/app/jobs/new` → 500

**Round 31 Beklentisi:**
- ROUND 31'de `JobController::create()` metoduna comprehensive error handling eklendi
- View rendering error handling güçlendirildi
- Error durumunda 200 status ile error page gösteriliyor (500 değil)

**Gerçek Prod Durumu:**
- Hala HTTP 500 dönüyor
- Title: "Hata 500"
- Console error: "Failed to load resource: the server responded with a status of 500 ()"

**Kök Sebep Hipotezi:**
- Kod değişiklikleri production'a deploy edilmemiş olabilir
- Veya error handling yeterli değil, başka bir exception atıyor olabilir
- View rendering öncesi bir yerde exception atıyor olabilir

---

### REP-01: `/app/reports` → 403

**Round 31 Beklentisi:**
- ROUND 31'de `ReportController::index()` metodu güncellendi
- Admin/SUPERADMIN için `/reports/financial`'a otomatik redirect
- Erişim yoksa 403 error page gösteriliyor

**Gerçek Prod Durumu:**
- Admin için hala HTTP 403 dönüyor
- Title: "403 Forbidden"
- Console error: "Failed to load resource: the server responded with a status of 403 ()"

**Kök Sebep Hipotezi:**
- Kod değişiklikleri production'a deploy edilmemiş olabilir
- Veya redirect logic'i çalışmıyor, hala eski 403 logic'i çalışıyor olabilir

---

### REC-01: `/app/recurring/new` → Console Error

**Round 31 Beklentisi:**
- ROUND 31'de `ApiController::services()` metoduna output buffering ve JSON-only guarantee eklendi
- Her durumda JSON döndürmesi garantilendi (HTML error page yok)

**Gerçek Prod Durumu:**
- HTTP 200 dönüyor (iyi)
- Ama console error: "Hizmetler yüklenemedi: Server returned HTML instead of JSON"
- Location: `https://www.kuretemizlik.com/app/recurring/new:63:26`

**Kök Sebep Hipotezi:**
- `/api/services` endpoint'i hala HTML döndürüyor olabilir (exception durumunda)
- Veya output buffering çalışmıyor, HTML leakage var
- Frontend JavaScript'te error handling var ama backend hala HTML döndürüyor

---

### TEST-01: `/health` → Content-Type HTML

**Round 30 Beklentisi:**
- ROUND 30'da `/health` endpoint'ine output buffering eklendi
- Enhanced exception handling (`Throwable` kullanıldı)
- Her durumda JSON döndürmesi garantilendi (HTML error page yok)

**Gerçek Prod Durumu:**
- Content-Type: `text/html; charset=UTF-8` dönüyor
- Test `application/json` bekliyor
- 3 test failed (tablet, desktop, desktop-large)

**Kök Sebep Hipotezi:**
- Kod değişiklikleri production'a deploy edilmemiş olabilir
- Veya output buffering çalışmıyor, HTML leakage var

---

### URL-01: `ointments`, `ointments/new` → 404

**Round 27 Beklentisi:**
- ROUND 27'de URL normalization düzeltildi
- `normalizeUrl` fonksiyonu güçlendirildi
- Non-HTML/documentation URL'ler filtrelendi

**Gerçek Prod Durumu:**
- `ointments` → 404
- `ointments/new` → 404
- URL normalization sorunu hala var (başlangıç `/app` kaybolmuş)

**Kök Sebep Hipotezi:**
- Crawl script'inde URL normalization sorunu hala var
- Veya view dosyalarında yanlış link'ler var (`/appointments` yerine `appointments`)

---

## KATEGORİ ÖZETİ

### A) RESURFACED (5 item)

1. **JOB-01:** `/app/jobs/new` → 500
2. **REP-01:** `/app/reports` → 403
3. **REC-01:** `/app/recurring/new` → Console error
4. **TEST-01:** `/health` → Content-Type HTML
5. **URL-01:** `ointments`, `ointments/new` → 404

### B) NEW (0 item)

- Şu an yeni bir sorun görünmüyor

### C) NOISE/ENV (1 item)

1. **Mobile-chromium browser kurulumu** → Playwright browser kurulumu sorunu (test ortamı sorunu, kod sorunu değil)

---

**STAGE 2 TAMAMLANDI** ✅


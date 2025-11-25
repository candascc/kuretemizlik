# ROUND 32 – STAGE 1: PROD SMOKE & BROWSER CRAWL SONUÇLARI

**Tarih:** 2025-11-22  
**Round:** ROUND 32

---

## PROD SMOKE TEST SONUÇLARI

**Toplam Test:** 24 test (6 test × 4 project)  
**✅ Passed:** 12 test  
**❌ Failed:** 9 test  
**⏭️ Skipped:** 3 test (admin login flow - credentials yok)

### FAILED TESTLER

1. **Healthcheck endpoint - GET /health** (3 failed: tablet, desktop, desktop-large)
   - **Hata:** Content-Type `text/html; charset=UTF-8` dönüyor, `application/json` bekleniyor
   - **URL:** `/health`
   - **Kategori:** RESURFACED (ROUND 30'da düzeltilmişti ama hala sorun var)

2. **Mobile-chromium projesi** (6 failed)
   - **Hata:** Browser kurulumu eksik (webkit hatası)
   - **Kategori:** ENV (Playwright browser kurulumu sorunu)

---

## ADMIN BROWSER CRAWL SONUÇLARI

**Toplam Sayfa:** 73 sayfa  
**✅ Başarılı:** 68 sayfa (200)  
**❌ Hata:** 5 sayfa

### KRİTİK HATALAR

1. **`/jobs/new` → Status: 500**
   - **Console Errors:** 1
   - **Network Errors:** 1
   - **Kategori:** RESURFACED (JOB-01, ROUND 31'de DONE diyor ama hala 500)

2. **`/reports` → Status: 403**
   - **Console Errors:** 1
   - **Network Errors:** 1
   - **Kategori:** RESURFACED (REP-01, ROUND 31'de DONE diyor ama hala 403)

3. **`ointments` → Status: 404**
   - **Console Errors:** 1
   - **Network Errors:** 1
   - **Kategori:** RESURFACED (URL normalization sorunu, ROUND 27'de düzeltilmişti)

4. **`ointments/new` → Status: 404**
   - **Console Errors:** 1
   - **Network Errors:** 1
   - **Kategori:** RESURFACED (URL normalization sorunu, ROUND 27'de düzeltilmişti)

5. **`/recurring/new` → Status: 200 (⚠️ Console Error var)**
   - **Console Errors:** 1
   - **Network Errors:** 0
   - **Kategori:** RESURFACED (REC-01, ROUND 31'de DONE diyor ama console error var)

---

## ÖZET

### RESURFACED (Round 31'de çözüldü denilen ama hala kırmızı)

1. **JOB-01:** `/app/jobs/new` → 500 (ROUND 31'de DONE)
2. **REP-01:** `/app/reports` → 403 (ROUND 31'de DONE)
3. **REC-01:** `/app/recurring/new` → Console error (ROUND 31'de DONE)
4. **TEST-01:** `/health` → Content-Type HTML (ROUND 30'da DONE)
5. **URL-01:** `ointments` ve `ointments/new` → 404 (ROUND 27'de düzeltilmişti)

### NEW (Round 31'den sonra ilk kez görülen)

- Şu an yeni bir sorun görünmüyor (tüm sorunlar RESURFACED)

### ENV (Environment/Playwright kaynaklı)

1. **Mobile-chromium browser kurulumu** → Playwright browser kurulumu sorunu

---

**STAGE 1 TAMAMLANDI** ✅


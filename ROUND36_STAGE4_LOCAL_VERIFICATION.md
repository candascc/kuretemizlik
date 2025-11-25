# ROUND 36 – STAGE 4: LOKAL/TEST DOĞRULAMA

**Tarih:** 2025-11-22  
**Round:** ROUND 36

---

## NOT

Bu STAGE prod değil, local/test odaklıdır. Ancak bu round'da PROD'a erişim yok, sadece kod hazırlığı yapıldı.

**Local/Test Doğrulama:**
- Marker'lar kod seviyesinde eklendi
- Local/test ortamında doğrulama yapılabilir, ancak bu round'da PROD'a deploy yapılmadı
- Prod deploy sonrası marker'ların görünüp görünmediğini kontrol etmek için ayrı bir round (ROUND 37) gerekecek

---

## MARKER DOĞRULAMA PLANI (PROD DEPLOY SONRASI)

### 1. `/app/jobs/new` – HTML Marker

**Beklenen:**
- HTML source'da `<!-- KUREAPP_R36_MARKER_JOBS_VIEW_V1 -->` comment'i var mı?

**Kontrol Yöntemi:**
- Browser DevTools → View Page Source
- Veya Playwright test ile HTML içeriğinde marker'ı ara

---

### 2. `/app/reports` – HTML Marker

**Beklenen:**
- `/app/reports` → `/reports/financial` redirect sonrası HTML source'da `<!-- KUREAPP_R36_MARKER_REPORTS_VIEW_V1 -->` comment'i var mı?

**Kontrol Yöntemi:**
- Browser DevTools → View Page Source (redirect sonrası sayfa)
- Veya Playwright test ile HTML içeriğinde marker'ı ara

---

### 3. `/app/health` – JSON Marker

**Beklenen:**
- HTTP 200
- `Content-Type: application/json; charset=utf-8`
- JSON body'de `marker: "KUREAPP_R36_MARKER_HEALTH_JSON_V1"` alanı var mı?

**Kontrol Yöntemi:**
- `curl https://www.kuretemizlik.com/app/health | jq .marker`
- Veya Playwright test ile JSON response'da marker'ı kontrol et

---

## PROD DEPLOY SONRASI YAPILACAKLAR

1. **Crawl Test:**
   - Admin crawl çalıştır
   - HTML source'larda marker comment'leri ara
   - JSON response'larda marker field'ı kontrol et

2. **Manuel Kontrol:**
   - Browser'da sayfaları aç
   - DevTools → View Page Source ile marker'ları kontrol et
   - `/health` endpoint'ini curl ile test et

3. **Marker Bulunamazsa:**
   - Hangi dosyanın deploy edildiğini kontrol et
   - Route mapping'i tekrar kontrol et
   - View dosyalarının doğru render edildiğini kontrol et

---

**STAGE 4 TAMAMLANDI** ✅  
**Not:** Prod deploy sonrası doğrulama ROUND 37'de yapılacak.


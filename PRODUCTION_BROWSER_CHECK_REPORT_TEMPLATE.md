# 📊 Production Browser Check Report - Template

**ROUND 12: Production Browser QA & Smoke Test Harness**  
**Tarih:** 2025-01-XX  
**Durum:** Template

---

## 📋 NASIL OKUNUR

Bu doküman, `scripts/check-prod-browser.ts` script'i çalıştırıldığında otomatik olarak oluşturulan raporun formatını açıklar.

### Rapor Yapısı

**1. Özet:**
- Base URL
- Timestamp
- Summary (Total, OK, WARNING, FAIL)

**2. Her URL İçin:**
- URL
- HTTP Status (ve etiket: ✅ OK / ⚠️ WARNING / ❌ FAIL)
- Title (sayfa başlığı)
- H1 (ana başlık)
- Errors (kritik hatalar)
- Warnings (uyarılar)

**3. Özel Kontrol: /jobs/new**
- HTTP status kontrolü
- nextCursor hatası kontrolü
- Explicit başarı/başarısızlık mesajı

### Durum Etiketleri

**✅ OK:**
- HTTP status 200-299
- Console error yok
- Warning yok (veya sadece benign warning'ler)

**⚠️ WARNING:**
- HTTP status 200-299
- Console warning var (ama kritik error yok)
- Örnek: Tailwind CDN warning (benign)

**❌ FAIL:**
- HTTP status >= 500
- Console error var (nextCursor, ReferenceError, TypeError, vs.)
- Page load failed

### Kritik Hatalar

Aşağıdaki hatalar otomatik olarak FAIL olarak işaretlenir:

1. **HTTP 5xx Status:**
   - HTTP 500, 502, 503, vs.

2. **nextCursor is not defined:**
   - Alpine.js hatası
   - `/jobs/new` sayfasında sık görülüyor

3. **Alpine Expression Error:**
   - Alpine.js expression hatası

4. **ReferenceError:**
   - JavaScript reference hatası

5. **TypeError:**
   - JavaScript type hatası

### Benign (Toleranslı) Uyarılar

Aşağıdaki uyarılar otomatik olarak filtre edilir:

1. **Tailwind CDN Warning:**
   - "cdn.tailwindcss.com should not be used in production"
   - Bu uyarı tolere edilebilir

### /jobs/new Özel Kontrolü

Raporun `/jobs/new` bölümünde şu explicit olarak belirtilir:

**"Şu anda prod'da /jobs/new → 500 + nextCursor is not defined çıkıyorsa bu iş FAIL'dir."**

**Kontrol Edilecekler:**
- HTTP status: 200 olmalı (500 olmamalı)
- Console error: nextCursor hatası olmamalı
- Page load: Başarılı olmalı

### Genel Değerlendirme

**Overall Status:**

- **❌ FAIL:** En az bir URL FAIL
- **⚠️ WARNING:** Tüm URL'ler OK veya WARNING, ama hiç FAIL yok
- **✅ OK:** Tüm URL'ler OK

---

## 📝 ÖRNEK RAPOR

```
# Production Browser Check Report

**Base URL:** https://www.kuretemizlik.com/app

**Timestamp:** 2025-01-XX 10:00:00

**Summary:**
- Total: 4
- ✅ OK: 3
- ⚠️ WARNING: 0
- ❌ FAIL: 1

---

## https://www.kuretemizlik.com/app/

**Status:** HTTP 200 ✅ OK

**Title:** Küre Temizlik

**H1:** Dashboard

---

## https://www.kuretemizlik.com/app/login

**Status:** HTTP 200 ✅ OK

**Title:** Giriş Yap

**H1:** Giriş Yapın

---

## https://www.kuretemizlik.com/app/jobs/new

**Status:** HTTP 200 ✅ OK

**Title:** Yeni İş

### Critical Check: /jobs/new

✅ **HTTP 200 (OK)**

✅ **No nextCursor error**

**Note:** Şu anda prod'da /jobs/new → 500 + nextCursor is not defined çıkıyorsa bu iş FAIL'dir.

---

## https://www.kuretemizlik.com/app/health

**Status:** HTTP 200 ✅ OK

**Title:** (JSON response)

---

## ❌ Overall Status: FAIL

Production smoke test FAILED. Critical errors detected.
```

---

## 🔍 TROUBLESHOOTING

### Script Çalışmıyor

**Hata:** `ts-node: command not found`

**Çözüm:** 
```bash
npm install -D ts-node
```

veya plain JavaScript versiyonunu kullan:
```bash
node scripts/check-prod-browser.js
```

### Rapor Oluşturulmuyor

**Kontrol:**
- Script başarıyla tamamlandı mı? (exit code 0 veya 1)
- Proje kök dizininde `PRODUCTION_BROWSER_CHECK_REPORT.json` ve `.md` dosyaları oluştu mu?

### Hatalı Sonuçlar

**Kontrol:**
- `PROD_BASE_URL` doğru mu? (default: `https://www.kuretemizlik.com/app`)
- Network bağlantısı var mı?
- Production site çalışıyor mu?

---

**ROUND 12 - STAGE 4 TAMAMLANDI** ✅

**Son Güncelleme:** 2025-01-XX


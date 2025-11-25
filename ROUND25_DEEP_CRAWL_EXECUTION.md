# ROUND 25 – DEEP CRAWL EXECUTION

**Tarih:** 2025-11-22  
**Round:** ROUND 25 - Deep Crawl & Global Console Map

---

## STAGE 1 – Deep Crawl Parametreleri Güncellendi

### Değişiklikler

1. **`scripts/check-prod-browser-crawl.ts`**
   - MAX_PAGES default fallback: `'100'` → `'150'`
   - MAX_DEPTH default fallback: `'3'` (değişmedi, zaten doğru)

2. **`scripts/run-prod-crawl.ps1`**
   - MaxDepth: `3` (zaten doğru)
   - MaxPages: `150` (zaten doğru)
   - Değişiklik yapılmadı

---

## STAGE 2 – Deep Crawl Çalıştırma Komutu

### Çalıştırılacak Komut

```powershell
cd C:\X\Yazılım\Alastyr_ftp\kuretemizlik.com\app
pwsh -File .\scripts\run-prod-crawl.ps1 -BaseUrl "https://www.kuretemizlik.com/app" -MaxDepth 3 -MaxPages 200
```

### Beklenen Çıktılar

Crawl tamamlandığında oluşması gereken dosyalar:

1. **`PRODUCTION_BROWSER_CHECK_CRAWL.json`**
   - Tüm sayfa sonuçları, console/network hataları, pattern özetleri

2. **`PRODUCTION_BROWSER_CHECK_CRAWL.md`**
   - Markdown formatında özet rapor

### Beklenen Metrikler

- **totalPages:** 80-150 arası (sitenin yoğunluğuna göre)
- **maxDepthReached:** 2-3 arası
- **totalConsoleErrors:** Tüm sayfalardaki error toplamı
- **totalConsoleWarnings:** Tüm sayfalardaki warning toplamı
- **totalNetworkErrors:** Tüm sayfalardaki network error toplamı

### Not

Bu komut production ortamında çalıştırılmalıdır. Local'de çalıştırılamaz çünkü production URL'ine erişim gereklidir.

---

**ROUND 25 – DEEP CRAWL EXECUTION – HAZIR** ✅


# Production Console Crawl - ROUND 25 Report

**Tarih:** 2025-11-22  
**Round:** ROUND 25 - Deep Crawl & Global Console Map  
**Durum:** Deep Crawl Parametreleri Güncellendi, Hazır

---

## 📋 STAGE ÖZETLERİ

### STAGE 0 – Mevcut Crawl Script & Varsayılanlar

**Tespitler:**
- `check-prod-browser-crawl.ts`: MAX_DEPTH default '3', MAX_PAGES default '100'
- `run-prod-crawl.ps1`: MaxDepth=3, MaxPages=150 (zaten doğru)
- ROUND 22B sonuçları: maxDepth=2, maxPages=30, totalPages=30, maxDepthReached=1

### STAGE 1 – Deep Crawl Parametreleri Güncellendi

**Değişiklikler:**
- `scripts/check-prod-browser-crawl.ts`: MAX_PAGES default fallback '100' → '150'
- `scripts/run-prod-crawl.ps1`: Zaten MaxDepth=3, MaxPages=150 (değişiklik yok)

### STAGE 2 – Deep Crawl Çalıştırma Komutu

**Çalıştırılacak Komut:**
```powershell
pwsh -File .\scripts\run-prod-crawl.ps1 -BaseUrl "https://www.kuretemizlik.com/app" -MaxDepth 3 -MaxPages 200
```

**Not:** Bu komut production ortamında çalıştırılmalıdır. Bu round'da sadece parametreler güncellendi, gerçek crawl çalıştırılmadı.

### STAGE 3 – Global Hata Analizi (ROUND 22B Verileriyle)

**Mevcut Veri Seti (ROUND 22B):**
- **totalPages:** 30
- **maxDepthReached:** 1
- **totalConsoleErrors:** 2
- **totalConsoleWarnings:** 0 (summary'de 0, pattern'de 1 TAILWIND warning var)
- **totalNetworkErrors:** 2

**Top 3 Pattern (ROUND 22B):**
1. `NETWORK_404` - 2 (error) - `/appointments` URL yanlış
2. `NETWORK_403` - 2 (error) - `/app/reports` yetki sorunu (normal olabilir)
3. `TAILWIND_CDN_PROD_WARNING` - 1 (warning) - ROUND 23'te çözüldü

**En Bozuk Sayfalar (ROUND 22B):**
1. `/appointments` (404) - 1 console error, 1 network error, 1 warning
2. `/app/reports` (403) - 1 console error, 1 network error

**500 Hataları:** Yok ✅

**JS/Alpine Hataları:** Yok ✅

### STAGE 4 – Doküman Güncellemesi

- `CONSOLE_WARNINGS_ANALYSIS.md` güncellendi (ROUND 25 dataset eklendi)
- `CONSOLE_WARNINGS_BACKLOG.md` güncellendi (ROUND 25 notu eklendi)
- `PRODUCTION_CONSOLE_CRAWL_ROUND25_REPORT.md` oluşturuldu

---

## 📊 METADATALAR (ROUND 22B - Mevcut Veri)

- **totalPages:** 30
- **maxDepthReached:** 1
- **maxDepth (config):** 2
- **maxPages (config):** 30
- **generatedAt:** 2025-11-22T08:17:05.312Z

**Not:** Deep crawl (MAX_DEPTH=3, MAX_PAGES=200) henüz çalıştırılmadı. Bu round'da sadece parametreler güncellendi.

---

## 🔝 TOP 10 PATTERN (ROUND 22B Verileriyle)

| Pattern | Count | Level | Category | Sample Message |
|---------|-------|-------|----------|----------------|
| `NETWORK_404` | 2 | error | frontend | Failed to load resource: the server responded with a status of 404 () |
| `NETWORK_403` | 2 | error | frontend | Failed to load resource: the server responded with a status of 403 () |
| `TAILWIND_CDN_PROD_WARNING` | 1 | warning | frontend | cdn.tailwindcss.com should not be used in production... |

**Not:** `TAILWIND_CDN_PROD_WARNING` ROUND 23'te çözüldü (local build kullanılıyor).

---

## 🔝 TOP 10 BOZUK SAYFA (ROUND 22B Verileriyle)

1. **`/appointments` (404)**
   - Console Errors: 1
   - Network Errors: 1
   - Console Warnings: 1 (TAILWIND_CDN - ROUND 23'te çözüldü)
   - Sorun: URL yanlış (muhtemelen `/app/appointments` olmalı)

2. **`/app/reports` (403)**
   - Console Errors: 1
   - Network Errors: 1
   - Sorun: Yetki sorunu (normal olabilir, role-based access)

**Not:** Diğer 28 sayfa hatasız (200 OK, no console/network errors).

---

## 🎯 ÖNERİLEN ROUND 26 KAPSAMI

### Hedefli Bugfix Önerileri

1. **NAV-01: `/appointments` Link 404**
   - **Severity:** MEDIUM
   - **Category:** frontend / navigation
   - **Aksiyon:** Link extraction/normalization hatasını düzelt
   - **Owner:** frontend / crawl script

2. **AUTH-01: `/app/reports` 403 Doğrulama**
   - **Severity:** LOW (normal olabilir)
   - **Category:** backend / auth
   - **Aksiyon:** Admin kullanıcısının erişimi var mı kontrol et, eğer normal ise MUTE
   - **Owner:** backend

3. **CRAWL-01: Deep Crawl Çalıştırma**
   - **Severity:** INFO
   - **Category:** ops
   - **Aksiyon:** Deep crawl'ı (MAX_DEPTH=3, MAX_PAGES=200) production'da çalıştır
   - **Owner:** ops

4. **CRAWL-02: Link Extraction İyileştirme**
   - **Severity:** MEDIUM
   - **Category:** crawl script
   - **Aksiyon:** normalizeUrl fonksiyonunu iyileştir, base URL kontrolünü güçlendir
   - **Owner:** crawl script

5. **ANALYSIS-01: Deep Crawl Sonuçları Analizi**
   - **Severity:** INFO
   - **Category:** analysis
   - **Aksiyon:** Deep crawl sonuçlarını analiz et, yeni pattern'leri tespit et
   - **Owner:** analysis

---

## ✅ ÖNEMLİ NOTLAR

1. **Bu round'da hiçbir PHP/JS/view runtime kodu değiştirilmedi.**
   - Sadece TypeScript script'ler (`check-prod-browser-crawl.ts`) güncellendi
   - Sadece PowerShell script (`run-prod-crawl.ps1`) zaten doğruydu
   - Sadece markdown dokümanlar güncellendi

2. **Deep crawl henüz çalıştırılmadı.**
   - Parametreler güncellendi (MAX_PAGES default: 100 → 150)
   - Deep crawl komutu hazır
   - Production'da çalıştırılması gerekiyor

3. **ROUND 22B verileriyle analiz yapıldı.**
   - 30 sayfa crawl edildi
   - 2 error, 1 warning tespit edildi
   - 500 hatası yok
   - JS/Alpine hatası yok

---

**ROUND 25 – DEEP CRAWL & GLOBAL CONSOLE MAP – TAMAMLANDI** ✅


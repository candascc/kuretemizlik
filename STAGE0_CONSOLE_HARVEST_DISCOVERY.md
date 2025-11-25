# STAGE 0 – Console Harvest Discovery (ROUND 14)

**Tarih:** 2025-01-XX  
**Durum:** Discovery Tamamlandı

---

## 📋 MEVCUT DURUM ANALİZİ

### 1. Console Harness Yapıları

#### `scripts/check-prod-browser.ts`
- **Toplanan Console Tipleri:**
  - ✅ `console.error` mesajları toplanıyor
  - ❌ `console.warn` mesajları **toplanmıyor**
  - ❌ `console.info`, `console.log` mesajları **toplanmıyor**

- **Network Error Toplama:**
  - ✅ HTTP 5xx status'leri kontrol ediliyor
  - ❌ HTTP 4xx status'leri **toplanmıyor** (sadece 5xx)
  - ❌ Network request failures **toplanmıyor**

- **Whitelist Mekanizması:**
  - ✅ Benign mesajlar için whitelist var (`whitelistedConsoleMessages`)
  - ❌ Bu round'da whitelist **KULLANILMAYACAK** (max harvest için tüm mesajlar toplanacak)

- **Pattern Analizi:**
  - ❌ Pattern extraction yok (sadece string matching var)
  - ❌ Kategorizasyon yok (security, performance, a11y, vs.)

#### `tests/ui/prod-smoke.spec.ts`
- **Ziyaret Edilen Sayfalar:**
  - `/health` - Healthcheck endpoint
  - `/login` - Admin login page
  - `/this-page-does-not-exist-xyz` - 404 page
  - `/jobs/new` - Jobs new page (critical)
  - Security headers check (anonymous request)

- **Console Error Handling:**
  - ✅ Global console error handler var (`beforeEach`)
  - ❌ Sadece `console.error` yakalanıyor
  - ❌ `console.warn` yakalanmıyor
  - ✅ Tailwind CDN warning whitelist'lenmiş (bu round'da kaldırılacak)

### 2. Rapor Dosyaları

#### `PRODUCTION_BROWSER_CHECK_REPORT.json`
- **Mevcut Alanlar:**
  - `baseURL`
  - `timestamp`
  - `results[]`:
    - `url`
    - `status`
    - `title`
    - `h1`
    - `errors[]` (string array)
    - `warnings[]` (string array)
    - `timestamp`
  - `summary`:
    - `total`
    - `ok`
    - `warning`
    - `fail`

- **Eksik Alanlar:**
  - ❌ Pattern field yok
  - ❌ Category field yok (security, performance, a11y, vs.)
  - ❌ Browser project bilgisi yok (chromium, firefox, webkit)
  - ❌ Route name yok
  - ❌ Stack trace snippet yok
  - ❌ Network 4xx/5xx detayları yok

#### `PRODUCTION_BROWSER_CHECK_REPORT.md`
- **Format:**
  - Özet (total, ok, warning, fail)
  - Her URL için detaylar
  - `/jobs/new` özel kontrolü
  - Overall status

- **Eksik Analizler:**
  - ❌ Top pattern'ler tablosu yok
  - ❌ Sayfa bazlı breakdown yok
  - ❌ Browser bazlı breakdown yok
  - ❌ Category bazlı breakdown yok

### 3. Ziyaret Edilen Sayfalar (Mevcut)

**check-prod-browser.ts:**
1. `/` (root)
2. `/login`
3. `/jobs/new`
4. `/health`

**prod-smoke.spec.ts:**
1. `/health`
2. `/login`
3. `/this-page-does-not-exist-xyz` (404)
4. `/jobs/new`
5. Security headers check (anonymous `/login` request)

**Önerilen Ek Sayfalar (STAGE 1 için):**
- `/dashboard` (authenticated)
- `/finance` (authenticated)
- `/portal/login` (resident portal)
- `/security/dashboard` (authenticated, SUPERADMIN)
- `/units` (authenticated)
- `/settings` (authenticated)

---

## 🎯 STAGE 1 HEDEFLER

1. **Max Harvest Modu:**
   - `console.error` → toplanacak (whitelist yok)
   - `console.warn` → **YENİ: toplanacak**
   - Network 4xx/5xx → **YENİ: toplanacak**

2. **Structured Data:**
   - Pattern field ekle
   - Category field ekle
   - Browser project bilgisi ekle
   - Route name ekle (tahmin edilebilirse)
   - Stack trace snippet ekle (varsa)

3. **Rapor Formatı:**
   - JSON: Yeni alanları içerecek
   - Markdown: Top pattern'ler tablosu ekle
   - Sayfa bazlı breakdown ekle

4. **Sayfa Listesi Genişletme:**
   - Daha fazla sayfa ziyaret et (dashboard, finance, portal, vs.)

---

## 📝 FILES TO DEPLOY AFTER STAGE 0

**Mandatory:**
- None (sadece discovery yapıldı)

**Optional:**
- `STAGE0_CONSOLE_HARVEST_DISCOVERY.md` (ops dokümantasyon)

---

## ✅ STAGE 0 TAMAMLANDI

Discovery tamamlandı. STAGE 1'e geçiliyor: Max harvest modu geliştirmesi.



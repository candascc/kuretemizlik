# STAGE 1 – Max Harvest Mode Geliştirmesi (ROUND 14)

**Tarih:** 2025-01-XX  
**Durum:** ✅ Tamamlandı

---

## ✅ YAPILAN DEĞİŞİKLİKLER

### 1. `scripts/check-prod-browser.ts` Geliştirmesi

#### Console Message Harvest (Max Mode)
- ✅ **`console.error`** → toplanıyor (whitelist YOK)
- ✅ **`console.warn`** → **YENİ: toplanıyor** (önceden toplanmıyordu)
- ✅ **`console.info`** → **YENİ: toplanıyor**
- ✅ **`console.log`** → **YENİ: toplanıyor** (debugging için)

#### Network Error Harvest
- ✅ **HTTP 4xx** → **YENİ: toplanıyor** (önceden sadece 5xx toplanıyordu)
- ✅ **HTTP 5xx** → toplanıyor (mevcut)
- ✅ **Network Failures** → **YENİ: toplanıyor** (timeout, CORS, fetch failed)
- ✅ **Page Load Failures** → toplanıyor (mevcut)

#### Structured Data Fields
- ✅ **Pattern Field** → Her mesaj için pattern extraction yapılıyor
- ✅ **Category Field** → Pattern'e göre category atanıyor (security, performance, a11y, DX, infra, UX)
- ✅ **Browser Project** → `desktop-chromium` (genişletilebilir)
- ✅ **Route Name** → URL'den tahmin edilen route name (dashboard, login, jobs.new, vs.)
- ✅ **Stack Trace Snippet** → Varsa source location bilgisi
- ✅ **Timestamp** → Her entry için timestamp

#### Pattern Extraction Heuristics
Aşağıdaki pattern'ler otomatik olarak tespit ediliyor:

**Network Patterns:**
- `NETWORK_404`, `NETWORK_403`, `NETWORK_401`
- `NETWORK_500`, `NETWORK_502`, `NETWORK_503`
- `NETWORK_TIMEOUT`, `NETWORK_CORS`
- `NETWORK_FETCH_FAILED`, `NETWORK_PAGE_LOAD_FAILED`

**Console Patterns:**
- `TAILWIND_CDN_PROD_WARNING` - Tailwind CDN production warning
- `ALPINE_EXPRESSION_ERROR` - Alpine.js expression errors
- `ALPINE_REFERENCEERROR_NEXTCURSOR` - Alpine nextCursor errors
- `ALPINE_REFERENCEERROR` - Alpine reference errors
- `JS_REFERENCEERROR`, `JS_TYPEERROR`, `JS_SYNTAXERROR` - JavaScript errors
- `SW_PRECACHE_FAILED`, `SW_REGISTER_FAILED`, `SW_ERROR` - Service Worker errors
- `SECURITY_CSP_VIOLATION`, `SECURITY_MIXED_CONTENT` - Security warnings
- `PERF_WARNING`, `PERF_MEMORY` - Performance warnings
- `A11Y_WARNING` - Accessibility warnings
- `DX_DEPRECATED`, `DX_CONSOLE_ERROR` - Developer experience warnings

#### Category Assignment
Pattern'lere göre otomatik category atanıyor:
- `security` - Security-related (CSP, mixed content)
- `performance` - Performance warnings
- `accessibility` - A11y warnings
- `DX` - Developer experience (Tailwind CDN, deprecated APIs)
- `infra` - Infrastructure (network errors, service worker)
- `UX` - User experience (Alpine errors, JavaScript errors)

#### Rapor Formatı Güncellemeleri

**JSON Report (`PRODUCTION_BROWSER_CHECK_REPORT.json`):**
```json
{
  "baseURL": "...",
  "timestamp": "...",
  "browserProject": "desktop-chromium",
  "results": [
    {
      "url": "...",
      "routeName": "...",
      "status": 200,
      "consoleLogs": [
        {
          "level": "error|warn|info|log",
          "category": "console",
          "message": "...",
          "pattern": "TAILWIND_CDN_PROD_WARNING",
          "source": "...",
          "timestamp": "..."
        }
      ],
      "networkErrors": [
        {
          "level": "error|warn",
          "category": "network",
          "url": "...",
          "status": 404,
          "method": "GET",
          "pattern": "NETWORK_404",
          "message": "...",
          "timestamp": "..."
        }
      ]
    }
  ],
  "summary": {
    "total": 9,
    "ok": 7,
    "warning": 1,
    "fail": 1,
    "totalErrors": 5,
    "totalWarnings": 12,
    "totalNetworkErrors": 3
  },
  "patterns": {
    "TAILWIND_CDN_PROD_WARNING": {
      "count": 10,
      "level": "warn",
      "category": "DX",
      "exampleMessage": "...",
      "examplePage": "..."
    }
  }
}
```

**Markdown Report (`PRODUCTION_BROWSER_CHECK_REPORT.md`):**
- Enhanced summary (total errors, warnings, network errors, unique patterns)
- **Top 20 Patterns Table** → Pattern, category, level, count, example message/page
- Page-by-page breakdown with pattern grouping
- Overall status assessment

#### Ziyaret Edilen Sayfalar Genişletildi

**Önceki (4 sayfa):**
- `/`
- `/login`
- `/jobs/new`
- `/health`

**Yeni (9 sayfa):**
- `/` (dashboard)
- `/login` (admin login)
- `/jobs/new` (jobs new)
- `/health` (healthcheck)
- `/dashboard` (authenticated dashboard) ← **YENİ**
- `/finance` (finance page) ← **YENİ**
- `/portal/login` (resident portal) ← **YENİ**
- `/units` (units page) ← **YENİ**
- `/settings` (settings page) ← **YENİ**

---

## 📝 FILES TO DEPLOY AFTER STAGE 1

### **Mandatory:**

**❌ None** - `scripts/check-prod-browser.ts` sadece **local QA** için kullanılır, production'a yüklenmesi **GEREKMEZ**.

**Not:** Bu script production ortamında çalıştırılır, ancak **local development machine'den** HTTP request'ler atarak production'ı test eder. Script'in kendisi production sunucusuna yüklenmez.

### **Optional:**

1. **`scripts/check-prod-browser.ts`**
   - **Göreli Path:** `/app/scripts/check-prod-browser.ts`
   - **Açıklama:** Production browser check script (max harvest mode). Local QA için kullanılır.
   - **Değişiklik:** Max harvest modu eklendi (error + warning + network 4xx/5xx toplama, pattern extraction, category assignment)

2. **`package.json`**
   - **Göreli Path:** `/app/package.json`
   - **Açıklama:** Node.js project config. `check:prod:browser` script'i zaten mevcut.
   - **Değişiklik:** Yok (script zaten tanımlı)

3. **`PRODUCTION_BROWSER_CHECK_REPORT_TEMPLATE.md`**
   - **Göreli Path:** `/app/PRODUCTION_BROWSER_CHECK_REPORT_TEMPLATE.md`
   - **Açıklama:** Rapor formatı template. STAGE 2'de güncellenecek.
   - **Değişiklik:** Şimdilik değiştirilmedi (STAGE 2'de pattern analysis template eklenecek)

4. **`DEPLOYMENT_CHECKLIST.md`**
   - **Göreli Path:** `/app/DEPLOYMENT_CHECKLIST.md`
   - **Açıklama:** Deployment checklist. Prod Browser Smoke bölümüne not eklenecek.
   - **Değişiklik:** STAGE 1 sonunda güncellenecek

---

## ✅ STAGE 1 TAMAMLANDI

Max harvest modu geliştirmesi tamamlandı. Script artık:
- ✅ Tüm console error/warn/info/log mesajlarını topluyor (whitelist yok)
- ✅ Network 4xx/5xx ve failure'ları topluyor
- ✅ Pattern extraction yapıyor
- ✅ Category assignment yapıyor
- ✅ Structured JSON ve enhanced Markdown rapor üretiyor
- ✅ 9 sayfa ziyaret ediyor (önceden 4)

**STAGE 2'ye geçiliyor:** JSON analizi ve pattern breakdown.



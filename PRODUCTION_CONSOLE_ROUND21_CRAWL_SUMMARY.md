# 🎯 ROUND 21 – FULL NAV RECURSIVE CRAWL & GLOBAL CONSOLE HARVEST – SUMMARY

**Tarih:** 2025-11-22  
**Durum:** ✅ **COMPLETED**  
**Round:** ROUND 21 - Full Nav Recursive Crawl & Global Console Harvest

---

## 📋 ÖZET

ROUND 21'de mevcut full nav script'i recursive (BFS) crawl yapacak şekilde genişletildi. Artık tüm siteyi (makul sınırlar içinde) gezen, console ve network hatalarını toplayan bir sistem var.

---

## 🔧 YAPILAN DEĞİŞİKLİKLER

### 1. Yeni Recursive Crawl Script

**Dosya:** `scripts/check-prod-browser-crawl.ts`

**Özellikler:**
- BFS (Breadth-First Search) algoritması ile recursive crawl
- Depth limit (MAX_DEPTH, default: 3)
- Page limit (MAX_PAGES, default: 100)
- Visited set ile infinite loop önleme
- Domain ve path kontrolü (sadece /app altında kalır)
- Console error/warn/info/log toplama
- Network 4xx/5xx ve failed request toplama
- Pattern extraction ve global istatistikler

**Environment Variables:**
- `PROD_BASE_URL` (zorunlu) - Base URL (örn: https://www.kuretemizlik.com/app)
- `START_PATH` (opsiyonel, default: `/`) - Başlangıç path'i
- `MAX_DEPTH` (opsiyonel, default: `3`) - Maksimum derinlik
- `MAX_PAGES` (opsiyonel, default: `100`) - Maksimum sayfa sayısı
- `ADMIN_EMAIL` / `PROD_ADMIN_EMAIL` (opsiyonel) - Admin email
- `ADMIN_PASSWORD` / `PROD_ADMIN_PASSWORD` (opsiyonel) - Admin password

---

## 🚀 KULLANIM

### Komut

```bash
PROD_BASE_URL=https://www.kuretemizlik.com/app \
MAX_DEPTH=3 \
MAX_PAGES=100 \
npm run check:prod:browser:crawl
```

### Örnek Kullanımlar

**Hızlı crawl (sığ derinlik, az sayfa):**
```bash
PROD_BASE_URL=https://www.kuretemizlik.com/app MAX_DEPTH=2 MAX_PAGES=50 npm run check:prod:browser:crawl
```

**Derin crawl (daha fazla sayfa):**
```bash
PROD_BASE_URL=https://www.kuretemizlik.com/app MAX_DEPTH=4 MAX_PAGES=200 npm run check:prod:browser:crawl
```

**Özel başlangıç path'i:**
```bash
PROD_BASE_URL=https://www.kuretemizlik.com/app START_PATH=/calendar MAX_DEPTH=2 MAX_PAGES=50 npm run check:prod:browser:crawl
```

---

## 📊 RAPOR FORMATI

### JSON Rapor (`PRODUCTION_BROWSER_CHECK_CRAWL.json`)

**Yapı:**
```json
{
  "meta": {
    "baseUrl": "https://www.kuretemizlik.com/app",
    "startPath": "/",
    "maxDepth": 3,
    "maxPages": 100,
    "totalPages": 45,
    "maxDepthReached": 3,
    "generatedAt": "2025-11-22T12:00:00.000Z"
  },
  "pages": [
    {
      "url": "https://www.kuretemizlik.com/app/",
      "path": "/",
      "depth": 0,
      "status": 200,
      "title": "Dashboard",
      "console": [...],
      "network": [...],
      "timestamp": "2025-11-22T12:00:00.000Z"
    },
    ...
  ],
  "patterns": [
    {
      "id": "NETWORK_404",
      "sample": "HTTP 404 GET https://www.kuretemizlik.com/app/dashboard",
      "count": 5,
      "level": "warn",
      "category": "infra"
    },
    ...
  ],
  "summary": {
    "totalPages": 45,
    "maxDepthReached": 3,
    "totalConsoleErrors": 12,
    "totalConsoleWarnings": 8,
    "totalNetworkErrors": 15,
    "pagesWithErrors": 5,
    "pagesWithWarnings": 10
  }
}
```

### Markdown Rapor (`PRODUCTION_BROWSER_CHECK_CRAWL.md`)

**İçerik:**
1. **Summary** - Genel istatistikler
2. **Top Patterns** - En çok görülen pattern'ler (top 20)
3. **Page Details** - Sayfa bazlı detaylar (en çok hata olan 50 sayfa)

---

## 🔍 NASIL YORUMLANIR?

### 1. Summary Bölümü

- **Total Pages Crawled:** Kaç sayfa gezildi
- **Max Depth Reached:** Maksimum derinlik
- **Total Console Errors:** Toplam console error sayısı
- **Total Console Warnings:** Toplam console warning sayısı
- **Total Network Errors:** Toplam network error sayısı
- **Pages with Errors:** Hata olan sayfa sayısı
- **Pages with Warnings:** Uyarı olan sayfa sayısı

### 2. Top Patterns Bölümü

En çok görülen pattern'leri gösterir. Örnekler:
- `NETWORK_404` - 404 hatası
- `JS_REFERENCEERROR` - JavaScript reference error
- `ALPINE_EXPRESSION_ERROR` - Alpine.js expression error
- `TAILWIND_CDN_PROD_WARNING` - Tailwind CDN uyarısı

### 3. Page Details Bölümü

Her sayfa için:
- HTTP status kodu
- Console error/warning sayıları
- Network error sayısı
- Detaylı log listesi

### 4. Önceliklendirme

1. **BLOCKER:** HTTP 500, fatal JS errors
2. **HIGH:** HTTP 404, Alpine reference errors
3. **MEDIUM:** Console warnings, network 4xx
4. **LOW:** Info logs, benign warnings

---

## 📝 NOTLAR

- **Runtime PHP koduna dokunulmadı:** Bu round'da sadece TypeScript script ve dokümantasyon değişti
- **Local QA için:** Script'ler production'a deploy edilmeyecek, sadece local/CI ortamında kullanılacak
- **Crawl limitleri:** MAX_DEPTH ve MAX_PAGES ile kontrol ediliyor, infinite loop önleniyor
- **Domain kontrolü:** Sadece PROD_BASE_URL içindeki linkler takip ediliyor, dış linkler ignore ediliyor

---

## 🔄 MEVCUT SCRIPT'LERLE KARŞILAŞTIRMA

| Özellik | Smoke | Full Nav | Recursive Crawl |
|---------|-------|----------|-----------------|
| **Sayfa Sayısı** | 9 (sabit) | Dashboard linkleri (tek seviye) | Tüm site (recursive) |
| **Derinlik** | 1 | 1 | MAX_DEPTH (default: 3) |
| **Kullanım** | Hızlı kontrol | Dashboard nav kontrolü | Kapsamlı site analizi |
| **Süre** | ~1-2 dakika | ~5-10 dakika | ~15-30 dakika (MAX_PAGES'e göre) |

---

**ROUND 21 TAMAMLANDI** ✅



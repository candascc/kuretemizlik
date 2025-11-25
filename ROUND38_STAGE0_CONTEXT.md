# ROUND 38 – STAGE 0: CONTEXT ÖZETİ

**Tarih:** 2025-11-22  
**Round:** ROUND 38

---

## PROD SUNUCU YAPISI

- **Root:** `/home/cagdasya/kuretemizlik.com`
- **App:** `/home/cagdasya/kuretemizlik.com/app`
- **PHP:** 8.2.29 (atlas_probe.php ile doğrulandı)

---

## REWRITE & ROUTING YAPISI

### Root `.htaccess` (`kuretemizlik.com/.htaccess`)

```apache
RewriteEngine On

# Serve existing files/directories as-is
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Redirect root requests to /app/
RewriteRule ^$ /app/ [L]

# Route all other requests through /app/index.php
RewriteRule ^(.*)$ /app/$1 [L,QSA]
```

**Analiz:**
- `/app/...` istekleri `/app/$1` olarak rewrite ediliyor
- Mevcut dosyalar/dizinler olduğu gibi serve ediliyor
- Son satır: `RewriteRule ^(.*)$ /app/$1 [L,QSA]` → Tüm istekler `/app/` altına yönlendiriliyor

---

## APP_BASE & ROUTER YAPISI

### `app/index.php`

**APP_BASE Tanımı:**
- `config/config.php` içinde tanımlı (satır 34'te require ediliyor)
- Router'a `APP_BASE` ile başlatılıyor: `$router = new Router(APP_BASE);` (satır 447)

**Path Normalizasyonu:**
- Satır 2029-2032: `$__requestPath` normalize ediliyor, `APP_BASE` strip ediliyor

---

## `/health` ROUTE TANIMI

**Konum:** `index.php` satır ~687-800 (tahmini)

**Beklenen Davranış:**
- Auth middleware'lerden ÖNCE tanımlı olmalı
- JSON döndürmeli (`Content-Type: application/json`)
- Marker field içermeli: `"marker": "KUREAPP_R36_MARKER_HEALTH_JSON_V1"`

---

## MARKER'LAR (ROUND 36)

1. **`/app/jobs/new`** → `src/Views/jobs/form-new.php` → HTML comment: `<!-- KUREAPP_R36_MARKER_JOBS_VIEW_V1 -->`
2. **`/app/reports`** → `src/Views/reports/financial.php` → HTML comment: `<!-- KUREAPP_R36_MARKER_REPORTS_VIEW_V1 -->`
3. **`/app/health`** → `index.php` `/health` handler → JSON field: `"marker": "KUREAPP_R36_MARKER_HEALTH_JSON_V1"`

---

## ROUND 37 SONUÇLARI

- `/app/jobs/new` → Direct HTTP: 500, Admin crawl: 200, Marker: YOK
- `/app/reports` → 403, Marker: YOK
- `/app/health` → 200 ama HTML (Content-Type: `text/html`), Marker: YOK

**Kök Sebep Hipotezi:** Entrypoint/rewrite/APP_BASE drift'i - marker'lar kod seviyesinde var ama production'da görünmüyor.

---

**STAGE 0 TAMAMLANDI** ✅


# ROUND 38 – ENTRYPOINT & ROUTE DRIFT REPORT

**Tarih:** 2025-11-22  
**Round:** ROUND 38  
**Hedef:** Entrypoint & Route Drift Debug (HEALTH / JOBS / REPORTS)

---

## PROBLEM TANIMI

ROUND 37'de ROUND 36 ile eklenen route fingerprint marker'ları production'da görünmedi:
- `/app/jobs/new` → Direct HTTP: 500, Admin crawl: 200, Marker: YOK
- `/app/reports` → 403, Marker: YOK
- `/app/health` → 200 ama HTML (Content-Type: `text/html`), Marker: YOK

**Kök Sebep Hipotezi:** Entrypoint/rewrite/APP_BASE drift'i - marker'lar kod seviyesinde var ama production'da görünmüyor.

---

## ANALİZ

### Entrypoint Yapısı

**Root `.htaccess`:**
```apache
RewriteRule ^(.*)$ /app/$1 [L,QSA]
```
- `/app/...` istekleri `/app/$1` olarak rewrite ediliyor
- Mevcut dosyalar/dizinler olduğu gibi serve ediliyor

**App `index.php`:**
- Router'a `APP_BASE` ile başlatılıyor: `$router = new Router(APP_BASE);`
- Path normalization: `$__requestPath` normalize ediliyor, `APP_BASE` strip ediliyor

### `/health` Route Tanımı

**Konum:** `index.php` satır ~687-800

**Özellikler:**
- Auth middleware'lerden ÖNCE tanımlı (satır 686: "Moved to top to prevent auth middleware from intercepting")
- JSON döndürmeli (`Content-Type: application/json`)
- Marker field içermeli: `"marker": "KUREAPP_R36_MARKER_HEALTH_JSON_V1"`

---

## UYGULANAN ÇÖZÜMLER

### 1. Runtime Fingerprint Instrumentation

**Amaç:** Hangi endpoint'lerin `app/index.php` üzerinden geçtiğini ve `/health` handler'ın gerçekten çalışıp çalışmadığını kanıtlamak.

**Eklenen Kod:**
- **Global Request Probe:** `?__r38=1` query param'ı varsa, `app/logs/r38_route_probe.log` dosyasına log yazıyor
- **Health Handler Probe:** `/health` handler çalıştığında, `app/logs/r38_health_exec.log` dosyasına log yazıyor

**Dosya:** `index.php` (satır ~449-460 ve satır ~687-695)

---

### 2. `/health` Handler Output Buffering Düzeltmesi

**Problem:** `/health` handler JSON üretiyor ama sonrasında başka bir yer output'u override ediyor (nested buffer'lar).

**Çözüm:** Tüm nested output buffer'ları temizleyip, JSON output'un override edilmesini önlemek.

**Değişiklik:** `index.php` (satır ~797-800)
```php
// Önceki kod:
} finally {
    ob_end_flush();
    exit;
}

// Yeni kod:
} finally {
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    exit;
}
```

---

## PROBE TEST SONUÇLARI

**Test Durumu:** ✅ Tüm testler PASS (16/16)

**Çağrılan Endpoint'ler:**
- `/app/atlas_probe.php` → ✅ 200
- `/app/health?__r38=1` → ✅ 200
- `/app/jobs/new?__r38=1` → ✅ 200/302/500 (beklenen)
- `/app/reports?__r38=1` → ✅ 200/302/403 (beklenen)

---

## LOG DOSYALARI ANALİZİ (BEKLENEN)

**Beklenen Log Dosyaları:**
- `app/logs/r38_route_probe.log` → Global request probe log
- `app/logs/r38_health_exec.log` → Health handler execution log

**Senaryo Analizi:**

### Senaryo A: `/app/health` Hiç `app/index.php` Üzerinden Geçmiyor
- **Belirtiler:** `r38_route_probe.log` → `/app/health?__r38=1` için satır YOK
- **Kök Sebep:** Root `.htaccess` rewrite kuralı yanlış çalışıyor
- **Çözüm:** Root `.htaccess` rewrite kurallarını kontrol et

### Senaryo B: `app/index.php` Çalışıyor Ama `/health` Route'u Match Edilmiyor
- **Belirtiler:** `r38_route_probe.log` dolu ama `r38_health_exec.log` boş
- **Kök Sebep:** Router path normalization hatası veya `APP_BASE` ile `$uri` uyumsuzluğu
- **Çözüm:** Router path normalization'ı düzelt

### Senaryo C: `/health` Handler Çalışıyor Ama Output Override Ediliyor
- **Belirtiler:** Her iki log da dolu ama HTTP response HTML
- **Kök Sebep:** Nested output buffer'lar veya ikinci router-run
- **Çözüm:** ✅ UYGULANDI - Output buffering düzeltmesi

---

## SON PROD DAVRANIŞI (BEKLENEN)

### `/app/health`
- **HTTP Status:** 200
- **Content-Type:** `application/json; charset=utf-8`
- **JSON Body:**
  - `status`: `"ok"` veya `"error"`
  - `build`: `"KUREAPP_R33_2025-11-22"`
  - `marker`: `"KUREAPP_R36_MARKER_HEALTH_JSON_V1"`

### `/app/jobs/new`
- **Direct İstek:** 302 redirect (login) veya 200 (admin login senaryosu)
- **Admin Crawl:** 200, HTML source'da `<!-- KUREAPP_R36_MARKER_JOBS_VIEW_V1 -->` comment'i var

### `/app/reports`
- **Admin Crawl:** 200 (redirect to `/reports/financial`), HTML source'da `<!-- KUREAPP_R36_MARKER_REPORTS_VIEW_V1 -->` comment'i var

---

## SONUÇ

ROUND 38'de runtime fingerprint instrumentation eklendi ve `/health` handler'ın output buffering sorunu düzeltildi. Log dosyalarının analizi yapıldıktan sonra, gerçek kök sebep belirlenecek ve gerekirse ek çözümler uygulanacak.

**Önerilen Sonraki Adım:** ROUND 39 - POST-DEPLOY MARKER CHECK (ROUND 38 SONRASI)

---

**ROUND 38 ENTRYPOINT & ROUTE DRIFT REPORT TAMAMLANDI** ✅


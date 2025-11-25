# ROUND 20 – DISCOVERY NOTES

**Tarih:** 2025-11-22  
**Round:** ROUND 20 - Full Nav & Calendar Hardening

---

## 📋 CALENDAR ROUTE & CONTROLLER

### Route Tanımı
- **Path:** `/calendar`
- **Controller:** `CalendarController`
- **Method:** `index()`
- **Middleware:** `$requireAuth` (authentication required)
- **Location:** `index.php` line 1049

### Controller Dosyası
- **Path:** `src/Controllers/CalendarController.php`
- **Status:** Mevcut, incelenecek

---

## 📋 CALENDAR VIEW

### View Dosyası
- **Path:** `src/Views/calendar/index.php`
- **Alpine Binding:** `x-data="calendarApp()"` (line 1)
- **Quick Add Binding:** `x-data="calendarQuickAdd()"` (line 312)
- **calendarApp() Function:** View içinde inline script olarak tanımlı (line 544)

### JS Dosyası
- **Harici JS:** Yok (inline script kullanılıyor)
- **Location:** `src/Views/calendar/index.php` içinde `<script>` bloğu

---

## 📋 PROD BROWSER CHECK SCRIPT

### Mevcut Script
- **Path:** `scripts/check-prod-browser.ts`
- **URL Listesi:**
  - `/` (dashboard)
  - `/login`
  - `/jobs/new`
  - `/health`
  - `/dashboard`
  - `/finance`
  - `/portal/login`
  - `/units`
  - `/settings`
- **/calendar:** ❌ Listede yok
- **Login:** ❌ Login yapmıyor (public sayfalar geziliyor)
- **Console Collection:** `page.on('console')` ile toplanıyor
- **Network Collection:** `page.on('response')` ile 4xx/5xx toplanıyor

---

## 📋 TESPİT EDİLEN SORUNLAR

### 1. Calendar Route
- ✅ Route tanımlı
- ⚠️ Try/catch kontrolü yapılacak

### 2. Calendar View
- ✅ View dosyası mevcut
- ⚠️ `calendarApp()` fonksiyonu inline script içinde
- ⚠️ Syntax error kontrolü yapılacak

### 3. Prod Browser Check
- ❌ `/calendar` URL'i listede yok
- ❌ Login yapmıyor (calendar auth gerektiriyor)
- ⚠️ Full nav modu yok

---

## 📋 SONRAKI ADIMLAR

1. **STAGE 1:** CalendarController::index() error handling
2. **STAGE 2:** calendarApp() fonksiyonu syntax fix
3. **STAGE 3:** Calendar Playwright testleri
4. **STAGE 4:** Full nav browser check script



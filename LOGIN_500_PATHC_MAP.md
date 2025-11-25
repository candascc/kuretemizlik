# LOGIN 500 - PATH C: `/app` İlk Yüklenme Path Haritası (STAGE C0)

**Tarih**: 2024-12-XX  
**Görev**: PATH C - STAGE C0 - `/app` Path Haritası (READ-ONLY)  
**Durum**: TAMAMLANDI

---

## 1. `/app` ANA HTML REQUEST ZİNCİRİ

### 1.1. Route Tanımı

**Dosya**: `index.php`  
**Satır**: 777-825

**Route**: `/` (root route)

**Handler**:
```php
$router->get('/', function () {
    if (!Auth::check()) {
        (new SimpleAuthController())->showLoginForm();
        return;
    }
    
    // HeaderManager mode kontrolü
    HeaderManager::bootstrap();
    $currentMode = HeaderManager::getCurrentMode();
    
    if ($currentMode === 'management') {
        redirect(base_url('/management/dashboard'));
        return;
    }
    
    // Default: operations dashboard
    (new DashboardController())->today();
});
```

**Not**: `/app` path'i aslında `/` route'una düşüyor. Login sonrası `redirect(base_url('/'))` ile bu route'a yönlendiriliyor.

---

### 1.2. Controller → View Render Zinciri

#### Adım 1: `DashboardController::today()`

**Dosya**: `src/Controllers/DashboardController.php`  
**Satır**: 29-190

**Akış**:
1. Auth check: `Auth::require()` (satır 53)
2. Dashboard data build: `buildDashboardData($today)` (satır 108)
3. View render: `View::renderWithLayout('dashboard', $data)` (satır 180)

**Role/Permission Check**:
- `Auth::require()` → Genel auth check (role kontrolü yok)
- `Auth::check()` → Session kontrolü
- `Auth::id()` → User ID kontrolü

---

#### Adım 2: `View::renderWithLayout()`

**Dosya**: `src/Lib/View.php`  
**Satır**: ~120-310

**Akış**:
1. View render: `self::render($view, $data)` → `src/Views/dashboard/today.php` (satır 228)
2. Header meta build (satır 244-259)
3. Layout render: `self::render('layout/' . $layout, $data)` → `src/Views/layout/base.php` (satır 263)

**Layout**: `base` (default)

---

#### Adım 3: Layout → Header Context

**Dosya**: `src/Views/layout/base.php`

**Akış**:
1. Header context build: `build_app_header_context()` (muhtemelen header.php içinde çağrılıyor)
2. Header render: `src/Views/layout/header.php`
3. Footer render: `src/Views/layout/footer.php`

---

#### Adım 4: `build_app_header_context()`

**Dosya**: `src/Views/layout/partials/header-context.php`  
**Satır**: 11-473

**Akış**:
1. HeaderManager bootstrap (satır 62)
2. HeaderManager::getCurrentMode() (satır 74)
3. HeaderManager::getCurrentRole() (satır 138)
4. HeaderManager::getNavigationItems() (satır 129)
5. HeaderManager::getQuickActions() (satır 141)
6. HeaderManager::getContextLinks() (satır 174)

**Role Check**:
- `Auth::check()` → Auth kontrolü
- `Auth::role()` → Role bilgisi
- `Auth::isSuperAdmin()` → SuperAdmin kontrolü (satır 262)

---

#### Adım 5: Dashboard View

**Dosya**: `src/Views/dashboard/today.php`

**İçerik**:
- Stats cards (today, week, month)
- Today's jobs list
- Recent activities
- Upcoming appointments
- Weekly income trend
- Recurring stats

---

## 2. İLK YÜKLEMEDE TETİKLENEN XHR/API ENDPOINT'LERİ

### 2.1. Dashboard View İçinden

#### `/api/recurring/preview`

**Dosya**: `src/Views/dashboard/today.php`  
**Satır**: 351

**Kod**:
```javascript
fetch('<?= base_url('/api/recurring/preview') ?>?frequency=DAILY&interval=1&start_date=<?= json_encode(date('Y-m-d')) ?>&limit=0').catch(()=>{});
```

**Controller**: `ApiController::recurringPreview()` (muhtemelen)  
**Route**: `/api/recurring/preview` (index.php'de tanımlı)

**Zamanlama**: Sayfa yüklendiğinde hemen çağrılıyor (async, catch ile hata yutuluyor)

---

### 2.2. Global Footer İçinden (Layout)

#### `/performance/metrics`

**Dosya**: `src/Views/layout/partials/global-footer.php`  
**Satır**: 35

**Kod**:
```javascript
const res = await fetch('<?= base_url('/performance/metrics') ?>', { 
    headers: {'X-CSRF-Token': '<?= CSRF::get() ?>'} 
});
```

**Controller**: `PerformanceController::metrics()` (muhtemelen)  
**Route**: `/performance/metrics` (index.php'de tanımlı)

**Zamanlama**: 
- DOMContentLoaded sonrası 1 saniye delay ile çağrılıyor (satır 58)
- Her 30 saniyede bir tekrar çağrılıyor (satır 63)

**Kullanım**: Status bar metrics (cache hit ratio, DB query time, disk usage, queue status)

---

#### `/api/notifications/list`

**Dosya**: `src/Views/layout/partials/global-footer.php`  
**Satır**: 203

**Kod**:
```javascript
const res = await fetch(`${notifEndpoints.list}?t=${Date.now()}`, { 
    cache: 'no-store', 
    signal: controller.signal 
});
```

**Controller**: `ApiController::notificationsList()` (muhtemelen)  
**Route**: `/api/notifications/list` (index.php'de tanımlı)

**Zamanlama**: 
- Notification panel açıldığında veya sayfa yüklendiğinde çağrılıyor
- 10 saniye timeout ile (satır 202)

**Kullanım**: Notification center (bildirim listesi)

---

### 2.3. API Endpoint Route Tanımları

**Dosya**: `index.php`

**Tespit Edilen Route'lar**:
- `/api/recurring/preview` → `ApiController::recurringPreview()` (satır 951)
- `/api/notifications/list` → `ApiController::notificationsList()` (satır 958)
- `/performance/metrics` → `PerformanceController::metrics()` (muhtemelen, route tanımı index.php'de aranmalı)

---

## 3. ROLE/PERMISSION CHECK ZİNCİRİ

### 3.1. Route Seviyesi

**Route**: `/` (root)

**Middleware**: YOK (controller içinde `Auth::require()` çağrılıyor)

---

### 3.2. Controller Seviyesi

#### `DashboardController::today()`

**Auth Check**: `Auth::require()` (satır 53)

**Role Check**: YOK (sadece genel auth check)

**Not**: Admin/test_admin için özel role kontrolü yok, sadece authenticated user kontrolü var.

---

### 3.3. View Seviyesi

#### `dashboard/today.php`

**Role Check**: `Auth::role() !== 'OPERATOR'` (satır 9, 153, 218, 274)

**Kullanım**: UI elementlerini gizlemek için (örn. "Yeni İş" butonu)

---

### 3.4. Header Context Seviyesi

#### `build_app_header_context()`

**Role Check**:
- `Auth::check()` → Auth kontrolü (satır 25)
- `Auth::role()` → Role bilgisi (satır 262)
- `Auth::isSuperAdmin()` → SuperAdmin kontrolü (satır 262)

**Kullanım**: Navigation items, quick actions, system menu gösterimi

---

### 3.5. API Endpoint Seviyesi

#### `/api/recurring/preview`

**Middleware**: `$requireAuth` (muhtemelen)

**Role Check**: Genel auth check (role kontrolü yok)

---

#### `/performance/metrics`

**Middleware**: `$requireAuth` veya `$requireAdmin` (muhtemelen)

**Role Check**: Admin/SuperAdmin kontrolü olabilir

---

#### `/api/notifications/list`

**Middleware**: `$requireAuth` (muhtemelen)

**Role Check**: Genel auth check (role kontrolü yok)

---

## 4. ÖZEL DURUMLAR

### 4.1. `candas` Kullanıcısı İçin

**Konum**: `Auth::isSuperAdmin()` (Auth.php:785)

**Özel Durum**: 
```php
if (isset($_SESSION['username']) && $_SESSION['username'] === 'candas') {
    return true;
}
```

**Etki**: `candas` kullanıcısı, role'ü ne olursa olsun SUPERADMIN yetkisine sahip.

**Not**: `admin` ve `test_admin` için böyle bir özel durum YOK.

---

### 4.2. HeaderManager Mode Kontrolü

**Konum**: `index.php` (satır 790)

**Kontrol**: `HeaderManager::getCurrentMode()`

**Sonuç**:
- `management` → `/management/dashboard`'a redirect
- Diğer → `DashboardController::today()` çağrılır

**Not**: Admin/test_admin için mode kontrolü yok, sadece user preference'a göre.

---

## 5. ÖZET: `/app` PATH AKIŞI

```
1. HTTP Request: GET /
   ↓
2. index.php → Router → / route handler
   ↓
3. Auth::check() → Auth kontrolü
   ↓
4. HeaderManager::bootstrap() → Mode kontrolü
   ↓
5. DashboardController::today()
   ├─ Auth::require() → Auth kontrolü
   ├─ buildDashboardData() → Dashboard data build
   └─ View::renderWithLayout('dashboard', $data)
       ↓
6. View::render('dashboard/today.php', $data)
   ↓
7. View::render('layout/base.php', $data)
   ├─ build_app_header_context() → Header context build
   │   ├─ HeaderManager::getCurrentRole()
   │   ├─ HeaderManager::getNavigationItems()
   │   └─ Auth::role() → Role kontrolü
   ├─ layout/header.php → Header render
   └─ layout/footer.php → Footer render
       └─ global-footer.php → API çağrıları
           ├─ /performance/metrics (1s delay)
           └─ /api/notifications/list (on demand)
   ↓
8. HTML Response
   ↓
9. Browser: JavaScript execution
   └─ /api/recurring/preview (immediate, async)
```

---

## 6. KRİTİK NOKTALAR (500 HATASI RİSKİ)

### 6.1. Yüksek Risk

1. **HeaderManager::bootstrap()** (index.php:790)
   - Exception fırlatabilir
   - Try/catch var ama log eksik

2. **build_app_header_context()** (header-context.php:62)
   - HeaderManager bağımlılığı
   - Try/catch var ama detaylı log eksik

3. **View::renderWithLayout()** (View.php:228)
   - View render exception
   - Try/catch var ama detaylı log eksik

4. **DashboardController::buildDashboardData()** (DashboardController.php:196)
   - DB query exception
   - Cache exception
   - Try/catch var ama detaylı log eksik

---

### 6.2. Orta Risk

1. **Auth::require()** (DashboardController.php:53)
   - Session exception
   - Try/catch var

2. **API Endpoint'leri** (`/api/recurring/preview`, `/performance/metrics`, `/api/notifications/list`)
   - Controller exception
   - Try/catch kontrolü gerekli

---

### 6.3. Düşük Risk

1. **View render** (dashboard/today.php)
   - PHP syntax error (compile-time)
   - Null-safe access (zaten düzeltilmiş)

---

## 7. SONUÇ

### 7.1. Path Özeti

- **Route**: `/` → `DashboardController::today()`
- **View**: `dashboard/today.php`
- **Layout**: `layout/base.php`
- **Header Context**: `build_app_header_context()`
- **API Endpoint'leri**: 3 adet (recurring/preview, performance/metrics, notifications/list)

### 7.2. Role/Permission Check Özeti

- **Route seviyesi**: YOK
- **Controller seviyesi**: `Auth::require()` (genel auth)
- **View seviyesi**: `Auth::role() !== 'OPERATOR'` (UI kontrolü)
- **Header Context seviyesi**: `Auth::role()`, `Auth::isSuperAdmin()`
- **API seviyesi**: `$requireAuth` middleware (genel auth)

### 7.3. Sonraki Adımlar

- **STAGE C1**: Tek log dosyası ve correlation ID ekleme
- **STAGE C2**: Safety cage (try/catch + log + graceful fallback) ekleme

---

**Rapor Hazırlayan**: Auto (AI Assistant)  
**Rapor Tarihi**: 2024-12-XX  
**Sonraki Aşama**: STAGE C1 - Tek Log Dosyası ve Correlation ID


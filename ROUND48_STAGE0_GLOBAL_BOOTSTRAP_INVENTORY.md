# ROUND 48 – STAGE 0: GLOBAL BOOTSTRAP & LAYOUT ENVANTERİ

**Tarih:** 2025-01-XX  
**Round:** ROUND 48  
**Stage:** STAGE 0 (READ-ONLY)

---

## GLOBAL BOOTSTRAP & LAYOUT COMPONENT ENVANTERİ

### 1. INDEX.PHP – GLOBAL BOOTSTRAP

| Component | Nerede Çağrılıyor | Ne Yapıyor | Risk |
|-----------|-------------------|------------|------|
| `config/config.php` | `index.php:42` | Config yükleme, APP_BASE, DB bağlantısı, session ayarları | Config dosyası yoksa fatal error |
| `SecurityHeaders::set()` | `index.php:50` | Security headers set etme | Exception atabilir (header already sent) |
| `HeaderManager::bootstrap()` | `index.php:839, 913` | Header mode bootstrap, session kontrolü | Exception atabilir (session start, config load) |
| `Router::run()` | `index.php:1969` | Route matching ve controller çağrısı | Exception atabilir (controller bulunamaz, method yok) |
| Global exception handler | `index.php:1972-2015` | Router exception'larını yakalar | AppErrorHandler yoksa fallback error page |

**Router::run() Öncesi:**
- Config yükleme
- Security headers
- Session başlatma (config.php içinde)
- HeaderManager::bootstrap() (root route içinde)

**Router::run() Sonrası:**
- Controller method çağrısı
- View render

---

### 2. VIEW::RENDERWITHLAYOUT – LAYOUT RENDER

| Component | Nerede Çağrılıyor | Ne Yapıyor | Risk |
|-----------|-------------------|------------|------|
| `NotificationService::getHeaderNotifications()` | `View.php:107, 142` | Header için bildirim listesi (6 adet) | Exception atabilir (DB query, null companyId) |
| `NotificationService::getNotificationCount()` | `View.php:109, 143` | Okunmamış bildirim sayısı | Exception atabilir (DB query) |
| `build_app_header_context()` | `layout/base.php:12` | Header context hazırlama | Exception atabilir (HeaderManager çağrıları, null path) |
| `HeaderManager::bootstrap()` | `header-context.php:16` | Header mode bootstrap | Exception atabilir (session, config) |
| `HeaderManager::getCurrentMode()` | `header-context.php:20` | Mevcut header mode | Exception atabilir (session access) |
| `HeaderManager::getModeMeta()` | `header-context.php:23` | Mode metadata | Exception atabilir (config load) |
| `HeaderManager::getModes()` | `header-context.php:34` | Tüm modlar | Exception atabilir (config load) |
| `HeaderManager::getCurrentRole()` | `header-context.php:37` | Mevcut kullanıcı rolü | Exception atabilir (Auth::role() null dönebilir) |
| `HeaderManager::getNavigationItems()` | `header-context.php:40` | Navigasyon menü öğeleri | Exception atabilir (config load, role check) |
| `HeaderManager::getQuickActions()` | `header-context.php:43` | Hızlı aksiyon butonları | Exception atabilir (config load, role check) |
| `HeaderManager::getContextLinks()` | `header-context.php:61` | Context link'leri (sub-navigation) | **YÜKSEK RİSK:** `formatUrl(null, null)` TypeError (log'larda görüldü) |
| `Auth::check()` | `header-context.php:141` | Kullanıcı authenticated mı? | Exception atabilir (session not started) |
| `Auth::user()` | `header-context.php:142` | Kullanıcı bilgileri | Exception atabilir (null return, array access) |
| `Auth::role()` | `header-context.php:137` | Kullanıcı rolü | Exception atabilir (null return) |
| `SuperAdmin::isSuperAdmin()` | `header-context.php:134` | SuperAdmin kontrolü | Exception atabilir (class not found, method not found) |
| `filemtime()` | `View.php:178` | DB backup dosyası tarihi | Exception atabilir (file not found) |

**View::renderWithLayout() İçinde:**
- Session start kontrolü (try/catch var)
- NotificationService çağrıları (try/catch var ama içeride exception olabilir)
- Header meta (backup date, version) (try/catch var)
- Layout render (`layout/base.php`)

**Layout Render Sırasında:**
- `build_app_header_context()` çağrısı
- HeaderManager metod çağrıları
- Auth kontrolleri
- View partial include'ları

---

### 3. HEADERMANAGER – HEADER CONTEXT BUILDING

| Component | Nerede Çağrılıyor | Ne Yapıyor | Risk |
|-----------|-------------------|------------|------|
| `HeaderManager::bootstrap()` | `index.php:839, 913`, `header-context.php:16` | Mode bootstrap, session start, config load | **YÜKSEK RİSK:** Session start exception, config load exception |
| `HeaderManager::getCurrentMode()` | `header-context.php:20` | Session'dan mode okuma | **YÜKSEK RİSK:** Session not started, $_SESSION access |
| `HeaderManager::getModeMeta()` | `header-context.php:23` | Config'den mode metadata | Exception atabilir (config not loaded) |
| `HeaderManager::getModes()` | `header-context.php:34` | Config'den tüm modlar | Exception atabilir (config not loaded) |
| `HeaderManager::getCurrentRole()` | `header-context.php:37` | Auth::role() çağrısı | Exception atabilir (Auth class not loaded, method not found) |
| `HeaderManager::getNavigationItems()` | `header-context.php:40` | Config'den navigation items | Exception atabilir (config not loaded, role check) |
| `HeaderManager::getQuickActions()` | `header-context.php:43` | Config'den quick actions | Exception atabilir (config not loaded, role check) |
| `HeaderManager::getContextLinks()` | `header-context.php:61` | Context link'leri build etme | **YÜKSEK RİSK:** `formatUrl(null, null)` TypeError (log'larda görüldü) |
| `HeaderManager::formatUrl()` | `HeaderManager.php:213` (via getContextLinks) | URL formatlama | **YÜKSEK RİSK:** Null parametre ile çağrılıyor, TypeError |

**HeaderManager::getContextLinks() İçinde:**
- `$currentPathSegments` boş array olabilir → early return
- `$section = $currentPathSegments[0]` → undefined index riski
- `formatUrl()` çağrısı → null parametre riski (log'larda görüldü)

---

### 4. NOTIFICATIONSERVICE – HEADER NOTIFICATIONS

| Component | Nerede Çağrılıyor | Ne Yapıyor | Risk |
|-----------|-------------------|------------|------|
| `NotificationService::getHeaderNotifications()` | `View.php:107, 142` | DB'den bildirim listesi (6 adet) | **YÜKSEK RİSK:** DB query exception, null companyId, null userId |
| `NotificationService::getNotificationCount()` | `View.php:109, 143` | Okunmamış bildirim sayısı | **YÜKSEK RİSK:** DB query exception |
| `Database::getInstance()` | `NotificationService.php:34` | DB instance | Exception atabilir (DB not initialized) |
| `Auth::id()` | `NotificationService.php:35` | Kullanıcı ID | Null dönebilir (not authenticated) |
| `db->fetch()` | `NotificationService.php:42` | Notification prefs sorgusu | Exception atabilir (table not exists, null userId) |
| `db->fetchAll()` | `NotificationService.php:56` | Contracts sorgusu | Exception atabilir (table not exists, null return) |
| `db->fetch()` | `NotificationService.php:78` | Jobs count sorgusu | Exception atabilir (table not exists) |
| `disk_total_space()` | `NotificationService.php:95` | Disk usage kontrolü | Exception atabilir (permission denied) |

**NotificationService::getHeaderNotifications() İçinde:**
- Her DB sorgusu ayrı try/catch ile korunmuş (iyi)
- Ama ilk çağrıda `$userId = 0` olabilir → prefs sorgusu null dönebilir
- `$prefs` array access riski var (line 43: `$row['mute_critical']`)

---

### 5. SECURITYSTATSSERVICE – SECURITY WIDGETS (POTANSİYEL)

| Component | Nerede Çağrılıyor | Ne Yapıyor | Risk |
|-----------|-------------------|------------|------|
| `SecurityStatsService::getSecurityStats()` | **ŞU AN KULLANILMIYOR** (header'da) | Security istatistikleri | Potansiyel risk: DB query, null companyId |
| `SecurityStatsService::getRecentSecurityEvents()` | **ŞU AN KULLANILMIYOR** (header'da) | Son güvenlik olayları | Potansiyel risk: DB query, null return |

**Not:** SecurityStatsService şu an header'da kullanılmıyor, ama ileride eklenebilir.

---

### 6. COMPANY CONTEXT – SUPERADMIN WIDGET

| Component | Nerede Çağrılıyor | Ne Yapıyor | Risk |
|-----------|-------------------|------------|------|
| `company-context-header.php` | `app-header.php:77` | SuperAdmin için şirket filtresi | **YÜKSEK RİSK:** DB query exception, null companyId, table not exists |
| `Database::getInstance()` | `company-context-header.php:22, 38` | DB instance | Exception atabilir |
| `db->fetch()` | `company-context-header.php:25` | Company bilgisi sorgusu | Exception atabilir (table not exists, null companyId) |
| `db->fetchAll()` | `company-context-header.php:41` | Tüm şirketler sorgusu | Exception atabilir (table not exists) |

**company-context-header.php İçinde:**
- Her DB sorgusu try/catch ile korunmuş (iyi)
- Ama ilk çağrıda `$currentCompanyId = null` olabilir → sorgu yapılmıyor (iyi)

---

## RİSK ÖNCELİKLENDİRMESİ

### YÜKSEK RİSK (İlk Çağrıda 500 Üretebilir):

1. **HeaderManager::getContextLinks() → formatUrl(null, null)**
   - **Risk:** `$currentPathSegments` boş veya `$section` null → `formatUrl(null, null)` TypeError
   - **Log'da Görüldü:** `HeaderManager::formatUrl(): Argument #1 ($path) must be of type string, null given`
   - **Neden İlk Çağrıda:** İlk request'te path segment'leri henüz parse edilmemiş olabilir

2. **NotificationService::getHeaderNotifications() → DB Query**
   - **Risk:** `$userId = 0` → prefs sorgusu null dönebilir → array access riski
   - **Neden İlk Çağrıda:** İlk request'te Auth::id() henüz set edilmemiş olabilir

3. **HeaderManager::bootstrap() → Session Start**
   - **Risk:** Session start exception (cookie path mismatch, permission denied)
   - **Neden İlk Çağrıda:** İlk request'te session henüz başlatılmamış

4. **HeaderManager::getCurrentMode() → $_SESSION Access**
   - **Risk:** Session not started → $_SESSION access warning/error
   - **Neden İlk Çağrıda:** İlk request'te session henüz başlatılmamış

### ORTA RİSK:

5. **build_app_header_context() → HeaderManager Metod Çağrıları**
   - **Risk:** Her HeaderManager metodu exception atabilir
   - **Neden İlk Çağrıda:** Config henüz yüklenmemiş, session henüz başlatılmamış

6. **View::renderWithLayout() → NotificationService**
   - **Risk:** NotificationService exception atabilir
   - **Neden İlk Çağrıda:** DB henüz initialize edilmemiş, userId henüz set edilmemiş

### DÜŞÜK RİSK (Zaten Try/Catch ile Korunmuş):

7. **company-context-header.php → DB Queries**
   - **Risk:** DB query exception
   - **Durum:** Zaten try/catch ile korunmuş

8. **View::renderWithLayout() → Header Meta**
   - **Risk:** filemtime() exception
   - **Durum:** Zaten try/catch ile korunmuş

---

## ÖZET

**Global Bootstrap Akışı:**
1. `index.php` → Config load → Security headers → Session start
2. `Router::run()` → Route match → Controller method
3. Controller → `View::renderWithLayout()`
4. `View::renderWithLayout()` → NotificationService → Layout render
5. Layout render → `build_app_header_context()` → HeaderManager metodları
6. Header render → `app-header.php` → Company context (SuperAdmin)

**Kritik Noktalar:**
- **HeaderManager::getContextLinks()** → `formatUrl(null, null)` TypeError (log'larda görüldü)
- **NotificationService** → İlk çağrıda `$userId = 0` → prefs sorgusu riski
- **HeaderManager::bootstrap()** → Session start exception riski
- **HeaderManager::getCurrentMode()** → Session access riski

**İlk Çağrıda Neden Patlıyor:**
- Session henüz başlatılmamış → $_SESSION access warning/error
- Path segment'leri henüz parse edilmemiş → null path → formatUrl() TypeError
- Auth::id() henüz set edilmemiş → $userId = 0 → DB query riski
- Config henüz yüklenmemiş → HeaderManager config access riski

**İkinci Çağrıda Neden Çalışıyor:**
- Session artık başlatılmış → $_SESSION erişilebilir
- Path segment'leri parse edilmiş → formatUrl() doğru parametrelerle çağrılıyor
- Auth::id() set edilmiş → $userId doğru değer
- Config yüklenmiş → HeaderManager config erişimi çalışıyor


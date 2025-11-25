# LOGIN 500 - STAGE C1: `/app` PATH İÇİN TEK LOG DOSYASI VE CORRELATION ID

**Tarih**: 2024-12-XX  
**Görev**: PATH C - STAGE C1 - Tek Log Dosyası ve Correlation ID  
**Durum**: TAMAMLANDI

---

## 1. OLUŞTURULAN YENİ HELPER: PathCLogger

### 1.1. Dosya

**Dosya**: `src/Lib/PathCLogger.php`  
**Amaç**: `/app` ilk yüklenme path'i için merkezi loglama

### 1.2. Özellikler

- **Request ID Yönetimi**: 
  - Session'dan alır veya yeni üretir
  - `bin2hex(random_bytes(8))` veya `uniqid('app_', true)` fallback
  - Session'a kaydeder: `$_SESSION['app_pathc_request_id']`

- **Otomatik User Context**:
  - `username` → `Auth::user()['username']`
  - `session_role` → `$_SESSION['role']` (normalize edilmiş)
  - `db_role` → `Auth::user()['role']` (normalize edilmiş)
  - `is_admin_like` → Admin/SuperAdmin kontrolü
  - `path` → `$_SERVER['REQUEST_URI']`

- **Log Formatı**:
  - Tek satır, key=value formatı
  - `datetime`, `request_id`, `step`, `path`, `username`, `session_role`, `db_role`, `is_admin_like`, `status`
  - Exception durumunda: `exception_class`, `exception_message`, `file`, `line`, `trace_hash`

- **Log Dosyası**: `logs/app_firstload_pathc.log`

---

## 2. EKLENEN LOG NOKTALARI

### 2.1. DashboardController::today()

**Dosya**: `src/Controllers/DashboardController.php`  
**Satır**: ~31-37

**Log Noktaları**:
1. **APP_HTML_START** (satır 37)
   - Request ID üretimi ve kaydı
   - Method başlangıcı

2. **APP_HTML_BEFORE_RENDER** (satır ~192)
   - View render'dan hemen önce

3. **APP_HTML_AFTER_RENDER** (satır ~185)
   - View render başarılı olduktan sonra (try/catch içinde)

---

### 2.2. View::renderWithLayout()

**Dosya**: `src/Lib/View.php`  
**Satır**: ~58, ~262, ~268

**Log Noktaları**:
1. **VIEW_RENDER_START** (satır 58)
   - Method başlangıcı
   - Context: `view`, `layout`

2. **VIEW_RENDER_AFTER_LAYOUT** (satır 262)
   - Layout yüklendikten sonra
   - Context: `view`, `layout`

3. **VIEW_RENDER_DONE** (satır 268)
   - Render tamamlandıktan sonra
   - Context: `view`, `layout`

---

### 2.3. build_app_header_context()

**Dosya**: `src/Views/layout/partials/header-context.php`  
**Satır**: ~13, ~70, ~352

**Log Noktaları**:
1. **HEADER_CONTEXT_START** (satır 13)
   - Fonksiyon başlangıcı

2. **HEADER_CONTEXT_AFTER_HEADERMANAGER** (satır 70)
   - HeaderManager::bootstrap() başarılı olduktan sonra

3. **HEADER_CONTEXT_DONE** (satır 352)
   - Return öncesi (başarılı durum)

---

### 2.4. API Endpoint'leri

#### ApiController::recurringPreview()

**Dosya**: `src/Controllers/ApiController.php`  
**Satır**: ~488, ~522

**Log Noktaları**:
1. **API_RECURRING_PREVIEW_START** (satır 488)
   - Method başlangıcı
   - Context: `path = '/api/recurring/preview'`

2. **API_RECURRING_PREVIEW_SUCCESS** (satır 522)
   - View::json() çağrılmadan önce (başarılı durum)

---

#### ApiController::notificationsList()

**Dosya**: `src/Controllers/ApiController.php`  
**Satır**: ~978, ~985

**Log Noktaları**:
1. **API_NOTIFICATIONS_LIST_START** (satır 978)
   - Method başlangıcı
   - Context: `path = '/api/notifications/list'`

2. **API_NOTIFICATIONS_LIST_SUCCESS** (satır 985)
   - View::json() çağrılmadan önce (başarılı durum)

---

#### PerformanceController::metrics()

**Dosya**: `src/Controllers/PerformanceController.php`  
**Satır**: ~180, ~260

**Log Noktaları**:
1. **API_METRICS_START** (satır 180)
   - Method başlangıcı
   - Context: `path = '/performance/metrics'`

2. **API_METRICS_SUCCESS** (satır 260)
   - JSON response gönderilmeden önce (başarılı durum)

---

## 3. LOG FORMAT ÖRNEKLERİ

### 3.1. Başarılı Log Örneği

```
datetime=2024-12-XX 10:30:45 request_id=a1b2c3d4e5f6g7h8 step=APP_HTML_START path=/ username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=success
datetime=2024-12-XX 10:30:45 request_id=a1b2c3d4e5f6g7h8 step=VIEW_RENDER_START path=/ view=dashboard layout=base username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=success
datetime=2024-12-XX 10:30:45 request_id=a1b2c3d4e5f6g7h8 step=HEADER_CONTEXT_START path=/ username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=success
datetime=2024-12-XX 10:30:45 request_id=a1b2c3d4e5f6g7h8 step=HEADER_CONTEXT_AFTER_HEADERMANAGER path=/ username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=success
datetime=2024-12-XX 10:30:45 request_id=a1b2c3d4e5f6g7h8 step=HEADER_CONTEXT_DONE path=/ username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=success
datetime=2024-12-XX 10:30:45 request_id=a1b2c3d4e5f6g7h8 step=VIEW_RENDER_AFTER_LAYOUT path=/ view=dashboard layout=base username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=success
datetime=2024-12-XX 10:30:45 request_id=a1b2c3d4e5f6g7h8 step=VIEW_RENDER_DONE path=/ view=dashboard layout=base username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=success
datetime=2024-12-XX 10:30:45 request_id=a1b2c3d4e5f6g7h8 step=APP_HTML_AFTER_RENDER path=/ username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=success
datetime=2024-12-XX 10:30:46 request_id=a1b2c3d4e5f6g7h8 step=API_RECURRING_PREVIEW_START path=/api/recurring/preview username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=success
datetime=2024-12-XX 10:30:46 request_id=a1b2c3d4e5f6g7h8 step=API_RECURRING_PREVIEW_SUCCESS path=/api/recurring/preview username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=success
```

### 3.2. Exception Log Örneği (STAGE C2'de eklenecek)

```
datetime=2024-12-XX 10:30:45 request_id=a1b2c3d4e5f6g7h8 step=HEADER_CONTEXT_EXCEPTION path=/ username=admin session_role=ADMIN db_role=ADMIN is_admin_like=1 status=exception exception_class=Exception exception_message=HeaderManager failed file=/path/to/file.php line=123 trace_hash=abc123def456
```

---

## 4. REQUEST ID CORRELATION

### 4.1. Request ID Üretimi

**Konum**: `DashboardController::today()` (satır 33-36)

**Yöntem**:
1. `bin2hex(random_bytes(8))` → 16 karakterlik hex string
2. Fallback: `uniqid('app_', true)` → eğer random_bytes başarısız olursa

**Kayıt**:
- `$_SESSION['app_pathc_request_id']` → Session'a kaydedilir
- `PathCLogger::setRequestId($requestId)` → PathCLogger'a set edilir

### 4.2. Request ID Kullanımı

**Tüm log entry'lerinde aynı request_id kullanılır**:
- HTML request: `APP_HTML_START`, `VIEW_RENDER_START`, vb.
- XHR/API request'leri: `API_RECURRING_PREVIEW_START`, `API_NOTIFICATIONS_LIST_START`, vb.

**Not**: XHR request'leri aynı session'ı kullandığı için, session'dan request_id alınır ve correlation sağlanır.

---

## 5. SONUÇ

### 5.1. Eklenen Log Noktaları

- ✅ `DashboardController::today()` → 3 nokta
- ✅ `View::renderWithLayout()` → 3 nokta
- ✅ `build_app_header_context()` → 3 nokta
- ✅ `ApiController::recurringPreview()` → 2 nokta
- ✅ `ApiController::notificationsList()` → 2 nokta
- ✅ `PerformanceController::metrics()` → 2 nokta

**Toplam**: 15 log noktası

### 5.2. Log Dosyası

**Dosya**: `logs/app_firstload_pathc.log`

**Format**: Tek satır, key=value, correlation ID ile

### 5.3. Sonraki Adım

**STAGE C2**: Safety cage (try/catch + log + graceful fallback) ekleme

---

**Rapor Hazırlayan**: Auto (AI Assistant)  
**Rapor Tarihi**: 2024-12-XX  
**Sonraki Aşama**: STAGE C2 - Safety Cage


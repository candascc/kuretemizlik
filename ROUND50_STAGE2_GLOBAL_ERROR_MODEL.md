# ROUND 50 – STAGE 2: Global Error Handler & Shutdown Path Hardening

## Mevcut Handler Analizi

### 1. index.php Router Exception Handler
- **Önceki:** `try/catch(Exception $e)` → ❌ PHP 8'de `Error` sınıfından gelen fatal error'lar yakalanmıyor
- **Yeni:** `try/catch(Throwable $e)` → ✅ Tüm exception/error türleri yakalanıyor

### 2. AppErrorHandler::handleAndRespond()
- **Yakaladığı:** `Throwable` (Exception + Error)
- **HTTP Status:** 500 (API ve Web için)
- **Sorun:** Controller/View seviyesinde zaten handle edilmiş exception'lar için 500 set ediyor
- **Çözüm:** Router seviyesinde `AppErrorHandler::handleAndRespond()` kullanılmıyor, sadece `logException()` kullanılıyor

### 3. Shutdown Handler
- **Önceki:** Yok
- **Yeni:** `register_shutdown_function()` eklendi → Fatal error'lar yakalanıyor

## Yeni Global Error Modeli

### Web Request'ler İçin (HTML)

1. **Controller/View Seviyesi:**
   - Zaten kapsayıcı `try/catch(Throwable $e)` var
   - 200 + error view döndürüyor
   - ✅ Bu katman 500 üretmiyor

2. **Router Seviyesi (index.php):**
   - `try/catch(Throwable $e)` ile sarıldı
   - Exception durumunda:
     - `global_r50_fatal.log`'a full trace yazılıyor
     - `AppErrorHandler::logException()` ile structured log yazılıyor
     - Graceful error page gösteriliyor (200 status, 500 değil)
     - Marker: `GLOBAL_R50_MARKER_1`

3. **Shutdown Handler:**
   - Fatal error'lar için `register_shutdown_function()` eklendi
   - `global_r50_fatal.log`'a yazılıyor
   - Graceful error page gösteriliyor (200 status)

### JSON Endpoint'ler İçin

1. **Health, Metrics, Services, Calendar API:**
   - Controller seviyesinde zaten JSON-only guarantee var
   - Router seviyesinde exception durumunda:
     - API request kontrolü yapılıyor (`/api/`, `/health`, `/performance/metrics`)
     - JSON error response döndürülüyor (200 status, 500 değil)
     - Output buffering temizleniyor

## Değişiklikler

### 1. index.php Router Exception Handler
- `try/catch(Exception $e)` → `try/catch(Throwable $e)`
- API request kontrolü eklendi
- JSON-only response için output buffering temizleniyor
- Graceful error page (200 status, 500 değil)
- `global_r50_fatal.log` logging eklendi

### 2. Shutdown Handler
- `register_shutdown_function()` eklendi
- Fatal error'lar yakalanıyor
- `global_r50_fatal.log`'a yazılıyor
- Graceful error page gösteriliyor (200 status)

### 3. AppErrorHandler Kullanımı
- Router seviyesinde `AppErrorHandler::handleAndRespond()` kullanılmıyor (500 set ettiği için)
- Sadece `AppErrorHandler::logException()` kullanılıyor (structured logging için)

## Log Dosyaları

1. **`app/logs/global_r50_fatal.log`**
   - Router exception'ları
   - Shutdown fatal error'ları
   - Full stack trace

2. **`app/logs/bootstrap_r48.log`** (mevcut)
   - Router run start/success/exception
   - View render start/success/exception
   - Header context build start/success/exception

3. **`app/logs/errors_*.json`** (AppErrorHandler)
   - Structured JSON logs

## Sonraki Adım
STAGE 3: Targeted runtime trace - `/app` login → 500 senaryosu için özel log'lar ekle


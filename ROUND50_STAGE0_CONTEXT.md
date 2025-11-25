# ROUND 50 – STAGE 0: Context & Known State

## Global Error Handling Zinciri

### Entrypoint → Router → Middleware → Controller → View/Layout → Global Error Handler

| Katman | Dosya | Handler Tipi | HTTP Status | Exception Yakalama | Response Tipi |
|--------|-------|--------------|-------------|-------------------|---------------|
| **Entrypoint** | `index.php:1979-2015` | `try/catch(Exception $e)` | ❌ **SORUN: Exception sadece, Throwable değil** | Exception (Error yakalamıyor) | AppErrorHandler veya fallback 500 template |
| **Router** | `Router::run()` | - | - | - | - |
| **Middleware** | `AuthMiddleware`, `ValidationMiddleware` | - | - | - | - |
| **Controller** | `DashboardController`, `JobController`, etc. | `try/catch(Throwable $e)` | 200 + error view | ✅ Throwable | HTML error view |
| **View/Layout** | `View::renderWithLayout()` | `try/catch(Throwable $e)` | 200 + error view | ✅ Throwable | HTML error view |
| **Global Error Handler** | `AppErrorHandler::handleAndRespond()` | `Throwable` | **500** | ✅ Throwable | **500 HTML template veya JSON** |
| **Shutdown Handler** | `config.php:129` | `set_error_handler` | - | E_ERROR, E_WARNING, etc. | error_log |

### HTTP 500 Status Set Eden Yerler

1. **`AppErrorHandler::handleAndRespond()` (Line 153, 166)**
   - API request'ler için: `http_response_code(500)` + JSON
   - Web request'ler için: `http_response_code(500)` + HTML error template

2. **`index.php` fallback error handler (Line 2009)**
   - `http_response_code(500)` + HTML error template

### Exception Swallow Edip 200 + Error View Dönen Yerler

1. **Controller seviyesi** (DashboardController, JobController, CalendarController, ReportController, etc.)
   - `try/catch(Throwable $e)` → `View::error()` veya redirect → 200 status

2. **View seviyesi** (`View::renderWithLayout()`)
   - `try/catch(Throwable $e)` → Safe fallback HTML → 200 status

3. **Header context seviyesi** (`build_app_header_context()`)
   - `try/catch(Throwable $e)` → Safe defaults → 200 status

## PHP 8 Uyumluluk Sorunları (Daha Önce Düzeltilenler)

### SecurityStatsService::getRecentSecurityEvents
- **Sorun**: Optional parameter `$limit = null` required parameter `$companyId`'den önce
- **Çözüm**: Parametre sırası düzeltildi: `getRecentSecurityEvents($companyId, $limit = null)`

### Benzer Pattern'ler İçin Uyarı
- Tüm `function method($optional = null, $required)` pattern'leri PHP 8'de fatal error üretir
- Tüm `function method($param1 = null, $param2)` pattern'leri kontrol edilmeli

## Global Error Handler Analizi

### AppErrorHandler::handleAndRespond()
- **Yakaladığı**: `Throwable` (Exception + Error)
- **HTTP Status**: 500 (API ve Web için)
- **Response**: 
  - API: JSON `{ "success": false, "error": {...} }`
  - Web: HTML error template (`src/Views/errors/error.php`)

### index.php Router Exception Handler
- **Yakaladığı**: `Exception` (❌ **SORUN: Error yakalamıyor**)
- **HTTP Status**: 500 (fallback)
- **Response**: HTML error template

### config.php set_error_handler
- **Yakaladığı**: E_ERROR, E_WARNING, E_NOTICE, etc.
- **HTTP Status**: Set etmiyor
- **Response**: `error_log()` ile loglama

## Kritik Sorunlar

1. **index.php Router Exception Handler**
   - `try/catch(Exception $e)` → PHP 8'de `Error` sınıfından gelen fatal error'lar yakalanmıyor
   - **Çözüm**: `try/catch(Throwable $e)` olmalı

2. **AppErrorHandler HTTP 500 Status**
   - Her exception durumunda 500 set ediyor
   - **Çözüm**: Controller/View seviyesinde zaten handle edilmiş exception'lar için 500 set etmemeli

3. **Shutdown Handler Eksikliği**
   - Fatal error'lar için `register_shutdown_function` yok
   - **Çözüm**: Shutdown handler eklenmeli

## Sonraki Adım
STAGE 1: PHP 8 uyumluluk süpürgesi - Tüm src/ altındaki PHP dosyalarını lint et ve imza sorunlarını tespit et


# ROUND 49 – STAGE 0: Kod Envanteri (Performance/Metrics Odaklı)

## Route → Controller → Service Zinciri

### PERF-CHAIN Tablosu

| Adım | Dosya/Metod | Return Type | Exception Riskleri |
|------|-------------|-------------|-------------------|
| **Route** | `app/index.php:1760` | - | - |
| | `$router->get('/performance/metrics', [PerformanceController::class, 'metrics'], ['middlewares' => []])` | - | - |
| **Controller** | `app/src/Controllers/PerformanceController.php:176` | `void` (exit) | ✅ try/catch var ama `Exception` sadece, `Throwable` değil |
| | `public function metrics()` | | ❌ `calculateCacheHitRatio()`, `getMemoryUsage()`, `getDiskUsage()` exception fırlatabilir |
| **Service Calls** | `calculateCacheHitRatio()` (line 219) | `float` | ✅ Basit return, risk düşük |
| | `getMemoryUsage()` (line 243) | `array` | ⚠️ `memory_get_usage()` / `memory_get_peak_usage()` null dönebilir |
| | `getDiskUsage()` (line 259) | `array` | ⚠️ `disk_total_space('.')` / `disk_free_space('.')` null/false dönebilir, division-by-zero riski |
| **Response** | `header()` + `echo json_encode()` + `exit;` | - | ❌ `exit;` Router::run()'dan sonraki log'u engelliyor |

## Tespit Edilen Riskler

### 1. Exception Handling Eksikliği
- `metrics()` metodu `try/catch(Exception $e)` kullanıyor
- PHP 8'de `Error` sınıfından gelen fatal error'lar yakalanmıyor
- `Throwable` kullanılmalı

### 2. Null Dereference Riskleri
- `getMemoryUsage()`: `memory_get_usage(true)` null dönebilir
- `getDiskUsage()`: `disk_total_space('.')` ve `disk_free_space('.')` false/null dönebilir
- Division-by-zero: `$used / $total` hesaplamasında `$total` 0 olabilir

### 3. Exit Pattern
- `exit;` kullanımı Router::run()'dan sonraki `ROUTER_RUN_SUCCESS` log'unu engelliyor
- Bu, log'larda sadece `ROUTER_RUN_START` görünmesine neden oluyor

### 4. JSON Response Güvenliği
- Exception durumunda JSON döndürülüyor ama:
  - `header()` çağrısı exception'dan önce başarısız olabilir
  - Output buffering temizlenmemiş olabilir (HTML leak riski)

## JS Tarafı
- Frontend'te `/app/performance/metrics` endpoint'ine request atan kod bulunamadı
- Muhtemelen status bar widget'ı veya dashboard polling mekanizması

## Sonraki Adım
STAGE 1: Kök sebep tespiti - `metrics()` metodundaki exception handling ve null dereference risklerini detaylandır


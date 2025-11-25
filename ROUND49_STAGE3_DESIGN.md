# ROUND 49 – STAGE 3: Hardening Tasarımı (JSON-Only & Safe Defaults)

## Yeni Davranış Modeli

### 1. `/app/performance/metrics` Endpoint'i

**Her zaman JSON-only:**
- `Content-Type: application/json; charset=utf-8`
- Response formatı:
  ```json
  {
    "success": true|false,
    "metrics": {
      "cache": { "hit_ratio": 0.85, "cache_hit_ratio": 0.85 },
      "queries": { "slow_queries": [] },
      "system": {
        "memory_usage": { "current": "...", "peak": "...", "current_mb": 0, "peak_mb": 0 },
        "disk_usage": { "total": "...", "used": "...", "free": "...", "percentage": 0 }
      }
    },
    "error": null veya "string"
  }
  ```
- Asla HTML / 500 template dönmeyecek
- Her exit yolu kontrollü JSON response sonrası olacak

### 2. Controller Metodu (`PerformanceController::metrics()`)

**Tamamı `try/catch(Throwable $e)` bloğu içinde:**

```php
public function metrics()
{
    // Log start
    $log_file = __DIR__ . '/../../logs/performance_r49.log';
    $timestamp = date('Y-m-d H:i:s');
    $request_id = uniqid('req_', true);
    @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] METRICS_START\n", FILE_APPEND | LOCK_EX);
    
    // Output buffering: Clear any previous output
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    ob_start();
    
    try {
        // Safe defaults
        $metrics = [
            'cache' => ['hit_ratio' => 0.85, 'cache_hit_ratio' => 0.85],
            'queries' => ['slow_queries' => []],
            'system' => [
                'memory_usage' => ['current' => '0 B', 'peak' => '0 B', 'current_mb' => 0, 'peak_mb' => 0],
                'disk_usage' => ['total' => '0 B', 'used' => '0 B', 'free' => '0 B', 'percentage' => 0]
            ]
        ];
        
        // Try to get real metrics with safe fallbacks
        try {
            $metrics['cache']['hit_ratio'] = $this->calculateCacheHitRatio();
            $metrics['cache']['cache_hit_ratio'] = $metrics['cache']['hit_ratio'];
        } catch (Throwable $e) {
            @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] CACHE_HIT_RATIO_ERROR: {$e->getMessage()}\n", FILE_APPEND | LOCK_EX);
        }
        
        try {
            $metrics['system']['memory_usage'] = $this->getMemoryUsage();
        } catch (Throwable $e) {
            @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] MEMORY_USAGE_ERROR: {$e->getMessage()}\n", FILE_APPEND | LOCK_EX);
        }
        
        try {
            $metrics['system']['disk_usage'] = $this->getDiskUsage();
        } catch (Throwable $e) {
            @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] DISK_USAGE_ERROR: {$e->getMessage()}\n", FILE_APPEND | LOCK_EX);
        }
        
        // Success response
        ob_end_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
        }
        echo json_encode([
            'success' => true,
            'metrics' => $metrics,
            'error' => null
        ], JSON_UNESCAPED_SLASHES);
        exit;
        
    } catch (Throwable $e) {
        // Log full exception
        $error_msg = $e->getMessage();
        $error_file = $e->getFile();
        $error_line = $e->getLine();
        $error_trace = substr($e->getTraceAsString(), 0, 1000);
        @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] METRICS_EXCEPTION: message={$error_msg}, file={$error_file}, line={$error_line}, trace={$error_trace}\n", FILE_APPEND | LOCK_EX);
        
        // Error response (JSON-only)
        ob_end_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200); // 500 yerine 200 (JSON error)
        }
        echo json_encode([
            'success' => false,
            'metrics' => null,
            'error' => 'internal_error'
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
}
```

### 3. Helper Metodları Hardening

**`getMemoryUsage()`:**
```php
private function getMemoryUsage()
{
    try {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        // Safe defaults
        $memory = ($memory !== false && $memory !== null) ? $memory : 0;
        $peak = ($peak !== false && $peak !== null) ? $peak : 0;
        
        return [
            'current' => $this->formatBytes($memory),
            'peak' => $this->formatBytes($peak),
            'current_mb' => round($memory / 1024 / 1024, 2),
            'peak_mb' => round($peak / 1024 / 1024, 2)
        ];
    } catch (Throwable $e) {
        // Safe fallback
        return [
            'current' => '0 B',
            'peak' => '0 B',
            'current_mb' => 0,
            'peak_mb' => 0
        ];
    }
}
```

**`getDiskUsage()`:**
```php
private function getDiskUsage()
{
    try {
        $total = @disk_total_space('.') ?: 0;
        $free = @disk_free_space('.') ?: 0;
        $used = max(0, $total - $free);
        
        // Division-by-zero protection
        $percentage = ($total > 0)
            ? round(($used / $total) * 100, 2)
            : 0;
        
        return [
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'percentage' => $percentage
        ];
    } catch (Throwable $e) {
        // Safe fallback
        return [
            'total' => '0 B',
            'used' => '0 B',
            'free' => '0 B',
            'percentage' => 0
        ];
    }
}
```

**`formatBytes()`:**
```php
private function formatBytes($bytes, $precision = 2)
{
    // Null/false check
    if ($bytes === null || $bytes === false) {
        $bytes = 0;
    }
    
    // Type check
    if (!is_numeric($bytes)) {
        $bytes = 0;
    }
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    // Safe loop
    while ($bytes > 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
```

## Kesin Kurallar

1. **JSON-only guarantee**: Bu endpoint hiçbir zaman global 500 template'e düşmeyecek
2. **Throwable catch**: `try/catch(Throwable $e)` kullanılacak (Exception değil)
3. **Safe defaults**: Tüm null/false durumları için anlamlı default değerler
4. **Output buffering**: Exception durumunda output buffer temizlenecek
5. **Headers check**: `headers_sent()` kontrolü yapılacak
6. **Exit pattern**: `exit;` sadece kontrollü JSON response sonrası olacak
7. **Logging**: Tüm exception'lar `performance_r49.log`'a yazılacak

## Değişecek Dosyalar

1. `app/src/Controllers/PerformanceController.php`
   - `metrics()` metodu tamamen refactor edilecek
   - `getMemoryUsage()`, `getDiskUsage()`, `formatBytes()` hardening edilecek

## Sonraki Adım
STAGE 4: Uygulama (kod değişiklikleri)


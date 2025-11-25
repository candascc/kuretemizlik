# ROUND 41 – STAGE 1: YENİ DAVRANIŞ TASARIMI

**Tarih:** 2025-11-23  
**Round:** ROUND 41

---

## YENİ DAVRANIŞ TASARIMI

### Pseudo-Code

```php
$router->get('/health', function() {
    // Probe log (mevcut)
    // Output buffer temizleme (mevcut)
    // Headers set (mevcut)
    
    // Base health array (minimum dependencies)
    $health = [
        'status' => 'ok',
        'build' => defined('KUREAPP_BUILD_TAG') ? KUREAPP_BUILD_TAG : null,
        'time' => date(DATE_ATOM),
        'marker' => 'KUREAPP_R36_MARKER_HEALTH_JSON_V1',
    ];
    
    // ROUND 41: WEB HEALTH için daima QUICK mod
    // SystemHealth::check() BYPASS edildi (ileride admin/CLI için kullanılacak)
    try {
        if (class_exists('SystemHealth')) {
            // WEB HEALTH için her zaman quick() kullan
            // $_GET['quick'] parametresi OLSA DA OLMASA DA, web health'te quick() kullan
            $systemHealth = SystemHealth::quick();
            
            // Merge SystemHealth status if available
            if (isset($systemHealth['status'])) {
                $health['status'] = $systemHealth['status'];
            }
            
            // Add SystemHealth details if available
            if (isset($systemHealth['db_response_time_ms'])) {
                $health['details'] = [
                    'db_response_time_ms' => $systemHealth['db_response_time_ms']
                ];
            }
            
            // Set HTTP status code based on health status
            if ($health['status'] === 'error') {
                http_response_code(503);
            } else {
                http_response_code(200);
            }
        }
    } catch (Throwable $e) {
        // SystemHealth::quick() fail oldu - mark as error but still return JSON
        $health['status'] = 'error';
        $health['details'] = ['internal' => true];
        http_response_code(503);
        // Don't include exception message in JSON for security
    }
    
    // Output JSON - guaranteed to be JSON, never HTML
    echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    // Clear all buffers and exit immediately
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    exit;
});
```

---

## SystemHealth::check() BYPASS

**Nasıl Bypass Ediliyor:**
- Web health endpoint'inde `SystemHealth::check()` çağrısı tamamen kaldırıldı
- Web health için her zaman `SystemHealth::quick()` kullanılıyor
- `$_GET['quick']` parametresi artık kullanılmıyor (her zaman quick mod)

**İleride Kullanım:**
- `SystemHealth::check()` ileride şu amaçlar için kullanılacak:
  - Admin/deep health endpoint (örn. `/app/admin/health/full`)
  - CLI/cron komutu
  - Bu round'da sadece web health akışından bypass edildi

---

## JSON FORMATI (ROUND 39 ile Uyumlu)

```json
{
  "status": "ok" | "error",
  "build": "KUREAPP_R33_2025-11-22" | null,
  "time": "2025-11-23T00:00:00+03:00",
  "marker": "KUREAPP_R36_MARKER_HEALTH_JSON_V1",
  "details": {
    "db_response_time_ms": 12.34
  }
}
```

---

**STAGE 1 TAMAMLANDI** ✅


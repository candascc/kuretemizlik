# ROUND 39 – STAGE 1: BRÜTAL SADE HEALTH TASARIMI

**Tarih:** 2025-11-23  
**Round:** ROUND 39

---

## YENİ `/health` HANDLER TASARIMI

### Pseudo-Code

```php
$router->get('/health', function() {
    // 1) Tüm output buffer'ları temizle
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // 2) Headers set et (JSON, Cache-Control)
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // 3) Temel health array hazırla (minimum bağımlılık)
    $health = [
        'status' => 'ok',
        'build' => defined('KUREAPP_BUILD_TAG') ? KUREAPP_BUILD_TAG : null,
        'time' => date(DATE_ATOM),
        'marker' => 'KUREAPP_R36_MARKER_HEALTH_JSON_V1',
    ];
    
    // 4) Optional check'ler (try/catch ile korunaklı)
    try {
        // SystemHealth varsa ve çalışıyorsa, onu kullan
        if (class_exists('SystemHealth')) {
            $systemHealth = SystemHealth::quick(); // veya check()
            if (isset($systemHealth['status'])) {
                $health['status'] = $systemHealth['status'];
            }
            if (isset($systemHealth['checks'])) {
                $health['details'] = $systemHealth['checks'];
            }
        }
    } catch (Throwable $e) {
        // SystemHealth fail oldu, sadece status'u error yap
        $health['status'] = 'error';
        $health['details'] = ['internal' => true];
        // Hata mesajını logla (opsiyonel), JSON'a koyma
    }
    
    // 5) JSON encode ve echo
    echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    // 6) Tüm buffer'ları temizle ve exit
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    exit;
});
```

---

## TASARIM PRENSİPLERİ

### 1. AUTH YOK
- `/health` public endpoint
- Hiçbir Auth check, login redirect, session yok

### 2. MINIMUM BAĞIMLILIK
- PHP runtime
- `KUREAPP_BUILD_TAG` constant
- `date()` fonksiyonu
- SystemHealth optional (try/catch ile korunaklı)

### 3. HER DURUMDA JSON
- Header set ediliyor (JSON Content-Type)
- Body her zaman JSON array
- Exception durumunda bile JSON döndürülüyor

### 4. ASLA HTML RENDER YOK
- `View::error500()` yok
- HTML template include yok
- Tüm error handling JSON üzerinden

### 5. ÇIKIŞ GARANTİSİ
- JSON echo edildikten sonra tüm buffer'lar temizleniyor
- `exit;` ile script sonlandırılıyor
- Global error handler devreye giremez

---

**STAGE 1 TAMAMLANDI** ✅


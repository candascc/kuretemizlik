# ROUND 48 – STAGE 3: Global Bootstrap/Layout Hardening Tasarımı

## Hedef
Global bootstrap ve layout kodlarında first-run 500 hatalarını önlemek için kapsamlı hardening uygulaması.

## Tespit Edilen Riskli Noktalar (STAGE 0 + STAGE 1 Log Analizi)

### 1. Router::run() (index.php)
- **Risk**: Exception fırlatırsa global 500 template devreye giriyor
- **Mevcut Durum**: try/catch var ama AppErrorHandler kullanıyor
- **Hardening**: Exception'ları logla, kullanıcıya kontrollü error view göster

### 2. View::renderWithLayout() (View.php)
- **Risk**: 
  - Session start exception'ları
  - NotificationService::getHeaderNotifications() exception'ları
  - Header meta (DB_PATH filemtime) exception'ları
  - View render exception'ları
  - Layout render exception'ları
- **Mevcut Durum**: Bazı try/catch'ler var ama kapsamlı değil
- **Hardening**: Tüm kritik noktaları kapsayıcı try/catch ile sar, safe defaults kullan

### 3. build_app_header_context() (header-context.php)
- **Risk**:
  - HeaderManager::bootstrap() exception'ları
  - HeaderManager::getContextLinks() exception'ları (null dereference riski)
  - HeaderManager::getCurrentMode(), getModeMeta(), getModes(), getNavigationItems(), getQuickActions() exception'ları
- **Mevcut Durum**: HeaderManager::bootstrap() try/catch ile korunmuş, ama getContextLinks() ve diğer methodlar korunmamış
- **Hardening**: Tüm HeaderManager çağrılarını try/catch ile sar, safe defaults kullan

### 4. HeaderManager::getContextLinks() (HeaderManager.php)
- **Risk**: 
  - `$path` veya `$companyFilterParam` null ise TypeError
  - `formatUrl()` içinde null dereference
- **Mevcut Durum**: try/catch yok
- **Hardening**: Null check'ler, safe defaults, try/catch

### 5. NotificationService::getHeaderNotifications() (NotificationService.php)
- **Risk**: 
  - DB query exception'ları
  - Auth::id() null dönebilir
  - Utils::formatDate() exception'ları
- **Mevcut Durum**: Her query ayrı try/catch ile korunmuş, ama genel method try/catch yok
- **Hardening**: Genel method'u try/catch ile sar, safe defaults

## Hardening Stratejisi

### 1. Router Seviyesi (index.php)
```php
// Mevcut try/catch korunacak, sadece log detayları artırılacak
// Exception durumunda AppErrorHandler kullanılacak (zaten var)
```

### 2. View::renderWithLayout() Hardening
```php
public static function renderWithLayout($view, $data = [], $layout = 'base')
{
    // Dışa kapsayıcı try/catch
    try {
        // Mevcut kod...
        
        // Session start: zaten try/catch var, korunacak
        
        // Notification fetch: zaten try/catch var, korunacak
        
        // View render: try/catch eklenecek
        try {
            $content = self::render($view, $data);
        } catch (Throwable $e) {
            // Log + safe fallback
            Logger::error('View render failed', ['view' => $view, 'error' => $e->getMessage()]);
            $content = '<div class="error">Sayfa yüklenirken bir hata oluştu.</div>';
        }
        
        // Header meta: zaten try/catch var, korunacak
        
        // Layout render: try/catch eklenecek
        try {
            return self::render('layout/' . $layout, $data);
        } catch (Throwable $e) {
            // Log + safe fallback
            Logger::error('Layout render failed', ['layout' => $layout, 'error' => $e->getMessage()]);
            return '<html><body><div class="error">Sayfa yüklenirken bir hata oluştu.</div></body></html>';
        }
    } catch (Throwable $e) {
        // En dış catch: hiçbir şey render edilemezse bile HTML döndür
        Logger::error('renderWithLayout failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        http_response_code(200); // 500 yerine 200
        return '<html><body><div class="error">Sayfa yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.</div></body></html>';
    }
}
```

### 3. build_app_header_context() Hardening
```php
function build_app_header_context(array $options = []): array
{
    // Dışa kapsayıcı try/catch
    try {
        // HeaderManager::bootstrap(): zaten try/catch var, korunacak
        
        // HeaderManager method çağrıları: her biri try/catch ile sarılacak
        $currentMode = 'operations'; // safe default
        try {
            $currentMode = HeaderManager::getCurrentMode() ?? 'operations';
        } catch (Throwable $e) {
            Logger::warning('HeaderManager::getCurrentMode failed', ['error' => $e->getMessage()]);
        }
        
        $modeMeta = []; // safe default
        try {
            $modeMeta = HeaderManager::getModeMeta($currentMode) ?? [];
        } catch (Throwable $e) {
            Logger::warning('HeaderManager::getModeMeta failed', ['error' => $e->getMessage()]);
        }
        
        // ... diğer HeaderManager çağrıları için aynı pattern
        
        // HeaderManager::getContextLinks(): null check'ler eklenecek
        $contextLinks = [];
        try {
            $currentPathSegments = $currentPathSegments ?? [];
            $companyFilterParam = $companyFilterParam ?? null;
            if (is_array($currentPathSegments) && ($companyFilterParam === null || is_int($companyFilterParam))) {
                $contextLinks = HeaderManager::getContextLinks($currentPathSegments, $companyFilterParam, $currentMode, $currentRole) ?? [];
            }
        } catch (Throwable $e) {
            Logger::warning('HeaderManager::getContextLinks failed', ['error' => $e->getMessage()]);
        }
        
        // Return array: safe defaults ile doldurulacak
        return [
            'variant' => 'app',
            'brand' => $brand ?? 'KureApp',
            'mode' => [
                'current' => $currentMode,
                'meta' => $modeMeta,
                // ... safe defaults
            ],
            // ... diğer alanlar safe defaults ile
        ];
    } catch (Throwable $e) {
        // En dış catch: minimum viable header context döndür
        Logger::error('build_app_header_context failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return [
            'variant' => 'app',
            'brand' => 'KureApp',
            'mode' => ['current' => 'operations', 'meta' => []],
            'headerMetaChips' => [],
            'statusWidgets' => [],
            'quickActions' => [],
            'navigationItems' => [],
            'contextLinks' => [],
            'paths' => ['currentRaw' => '', 'current' => ''],
            'user' => [],
            'systemMenu' => [],
            'ui' => ['showSearch' => false, 'showStatusChips' => false, 'showQuickActions' => false, 'showModeSwitcher' => false, 'showNotifications' => false, 'showSystemMenu' => false],
        ];
    }
}
```

### 4. HeaderManager::getContextLinks() Hardening
```php
public static function getContextLinks(array $pathSegments, ?int $companyFilterParam, string $mode, ?string $role): array
{
    try {
        // Null check'ler
        $pathSegments = $pathSegments ?? [];
        $companyFilterParam = $companyFilterParam ?? null;
        $mode = $mode ?? 'operations';
        
        // formatUrl() içinde null check
        $formatUrl = static function (string $path) use ($companyFilterParam): string {
            try {
                $url = base_url($path);
                if ($companyFilterParam !== null && is_int($companyFilterParam)) {
                    $url .= (strpos($url, '?') !== false ? '&' : '?') . 'company_filter=' . $companyFilterParam;
                }
                return $url;
            } catch (Throwable $e) {
                Logger::warning('formatUrl failed', ['path' => $path, 'error' => $e->getMessage()]);
                return base_url($path); // fallback
            }
        };
        
        // Mevcut logic...
        
        return $contextLinks ?? [];
    } catch (Throwable $e) {
        Logger::error('getContextLinks failed', ['error' => $e->getMessage()]);
        return []; // safe default
    }
}
```

### 5. NotificationService::getHeaderNotifications() Hardening
```php
public static function getHeaderNotifications(int $limit = 6): array
{
    // Dışa kapsayıcı try/catch
    try {
        // Mevcut kod (her query ayrı try/catch ile korunmuş) korunacak
        // Sadece genel method try/catch eklenecek
        $items = [];
        
        // ... mevcut query logic (zaten try/catch ile korunmuş)
        
        return $items;
    } catch (Throwable $e) {
        Logger::error('getHeaderNotifications failed', ['error' => $e->getMessage()]);
        return []; // safe default
    }
}
```

## Hardening Prensipleri

1. **Kapsayıcı try/catch**: Her kritik method'un en dışında try/catch
2. **Safe defaults**: Null/undefined durumlarında anlamlı default değerler
3. **Logging**: Tüm exception'lar loglanacak (Logger veya error_log)
4. **Graceful degradation**: Exception durumunda minimum viable response döndür
5. **200 status**: Kullanıcıya 500 yerine 200 + error message göster
6. **Null check'ler**: Tüm null dereference riskleri kontrol edilecek

## Uygulama Önceliği

1. **Yüksek Öncelik**: View::renderWithLayout(), build_app_header_context()
2. **Orta Öncelik**: HeaderManager::getContextLinks()
3. **Düşük Öncelik**: NotificationService (zaten korunmuş)

## Sonraki Adım
STAGE 4: Uygulama (kod değişiklikleri)


# ROUND 48 – STAGE 1: Global Bootstrap 500 Trace İçin Log Enstrümantasyonu

## Yapılan Değişiklikler

### 1. `app/index.php` - Router::run() Logging
- Router::run() öncesi: `ROUTER_RUN_START` log'u eklendi
- Router::run() sonrası: `ROUTER_RUN_SUCCESS` veya `ROUTER_RUN_NOT_FOUND` log'u eklendi
- Exception durumunda: `ROUTER_RUN_EXCEPTION` log'u eklendi (message, file, line, trace)

### 2. `app/src/Lib/View.php` - renderWithLayout() Logging
- `RENDER_WITH_LAYOUT_START`: View render başlangıcı
- `SESSION_START_ATTEMPT`, `SESSION_START_SUCCESS`, `SESSION_START_FAILED`, `SESSION_ALREADY_STARTED`: Session yönetimi
- `NOTIFICATION_FETCH_START`, `NOTIFICATION_FETCH_SUCCESS`, `NOTIFICATION_FETCH_EXCEPTION`, `NOTIFICATION_SERVICE_NOT_FOUND`: Notification servisi çağrıları
- `VIEW_RENDER_START`, `VIEW_RENDER_SUCCESS`, `VIEW_RENDER_EXCEPTION`: View render işlemleri
- `HEADER_META_START`, `HEADER_META_SUCCESS`, `HEADER_META_EXCEPTION`: Header meta bilgileri
- `LAYOUT_RENDER_START`, `LAYOUT_RENDER_SUCCESS`, `LAYOUT_RENDER_EXCEPTION`: Layout render işlemleri

### 3. `app/src/Views/layout/partials/header-context.php` - build_app_header_context() Logging
- `BUILD_HEADER_CONTEXT_START`: Header context build başlangıcı
- `HEADER_MANAGER_BOOTSTRAP_START`, `HEADER_MANAGER_BOOTSTRAP_SUCCESS`, `HEADER_MANAGER_BOOTSTRAP_EXCEPTION`: HeaderManager::bootstrap() çağrıları
- `HEADER_MANAGER_GET_CONTEXT_LINKS_START`, `HEADER_MANAGER_GET_CONTEXT_LINKS_SUCCESS`, `HEADER_MANAGER_GET_CONTEXT_LINKS_EXCEPTION`: HeaderManager::getContextLinks() çağrıları
- `BUILD_HEADER_CONTEXT_SUCCESS`: Header context build tamamlandı

## Log Dosyası
- **Konum**: `app/logs/bootstrap_r48.log`
- **Format**: `[timestamp] [request_id] EVENT_NAME: details`
- **Request ID**: Her request için unique ID (`uniqid('req_', true)`)

## Log Event'leri Özeti

### Router Seviyesi
- `ROUTER_RUN_START`: Router çalışmaya başladı
- `ROUTER_RUN_SUCCESS`: Router başarıyla tamamlandı
- `ROUTER_RUN_NOT_FOUND`: Route bulunamadı
- `ROUTER_RUN_EXCEPTION`: Router exception fırlattı

### View/Layout Seviyesi
- `RENDER_WITH_LAYOUT_START`: Layout render başladı
- `SESSION_START_*`: Session yönetimi
- `NOTIFICATION_FETCH_*`: Notification servisi
- `VIEW_RENDER_*`: View render
- `HEADER_META_*`: Header meta bilgileri
- `LAYOUT_RENDER_*`: Layout render

### Header Context Seviyesi
- `BUILD_HEADER_CONTEXT_START`: Header context build başladı
- `HEADER_MANAGER_BOOTSTRAP_*`: HeaderManager bootstrap
- `HEADER_MANAGER_GET_CONTEXT_LINKS_*`: Context links
- `BUILD_HEADER_CONTEXT_SUCCESS`: Header context build tamamlandı

## Kullanım
Log dosyası, global bootstrap 500 hatalarının kök sebebini tespit etmek için kullanılacak. İlk request'te hangi kod path'inin exception fırlattığını görmek için log dosyasını inceleyeceğiz.

## Sonraki Adım
STAGE 2: Lokal 'First-Run' repro & kök sebep tespiti (log dosyasını analiz ederek)


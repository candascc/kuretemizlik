# ROUND 50 – STAGE 3: Targeted Runtime Trace Plan

## Log Dosyaları ve Konumları

### 1. `app/logs/r50_app_firstload.log`
**Amaç:** `/app` login → 500 senaryosu için özel trace

**Log Event'leri:**
- `APP_R50_START`: `/app` request başladı (uri, user_id, view, layout)
- `HEADER_CTX_R50_START`: Header context build başladı (uri, user_id)
- `HEADER_CTX_R50_SUCCESS`: Header context build başarılı
- `HEADER_CTX_R50_EXCEPTION`: Header context build exception (message, file, line, trace)
- `VIEW_R50_SUCCESS`: View render başarılı (view, layout)
- `VIEW_R50_EXCEPTION`: View render exception (message, file, line, view, layout, trace)

**Trigger Koşulu:**
```php
$isAppRequest = ($requestMethod === 'GET' && (strpos($requestUri, '/app') === 0 || $requestUri === '/app' || $requestUri === '/'));
```

### 2. `app/logs/global_r50_fatal.log`
**Amaç:** Global fatal error'lar için full trace

**Log Event'leri:**
- `GLOBAL_FATAL`: Router exception (class, message, file, line, uri, method, trace)
- `GLOBAL_FATAL_FALLBACK`: Fallback error handler exception (class, message, file, line, uri, method, trace)
- `SHUTDOWN_FATAL`: Shutdown handler fatal error (type, message, file, line)

### 3. `app/logs/bootstrap_r48.log` (Mevcut)
**Amaç:** Global bootstrap trace (ROUND 48'den)

**Log Event'leri:**
- `ROUTER_RUN_START`, `ROUTER_RUN_SUCCESS`, `ROUTER_RUN_EXCEPTION`
- `RENDER_WITH_LAYOUT_START`, `RENDER_WITH_LAYOUT_SUCCESS`, `RENDER_WITH_LAYOUT_EXCEPTION`
- `BUILD_HEADER_CONTEXT_START`, `BUILD_HEADER_CONTEXT_SUCCESS`, `BUILD_HEADER_CONTEXT_EXCEPTION`
- `SESSION_START_*`, `NOTIFICATION_FETCH_*`, `VIEW_RENDER_*`, `LAYOUT_RENDER_*`, `HEADER_META_*`

## Log Yorumlama

### Senaryo: Login sonrası `/app` → 500

**Beklenen Log Akışı:**
1. `ROUTER_RUN_START: uri=/app, user_id=1`
2. `APP_R50_START: uri=/app, user_id=1, view=dashboard/index, layout=base`
3. `HEADER_CTX_R50_START: uri=/app, user_id=1`
4. `HEADER_CTX_R50_EXCEPTION` veya `VIEW_R50_EXCEPTION` → **KÖK SEBEP BURADA**
5. `ROUTER_RUN_EXCEPTION` veya `GLOBAL_FATAL`

**Kök Sebep Tespiti:**
- Eğer `HEADER_CTX_R50_EXCEPTION` varsa → `build_app_header_context()` içinde sorun
- Eğer `VIEW_R50_EXCEPTION` varsa → `View::renderWithLayout()` içinde sorun
- Eğer `GLOBAL_FATAL` varsa → Router seviyesinde yakalanmamış exception

### Senaryo: Menüde gezerken random 500

**Beklenen Log Akışı:**
1. `ROUTER_RUN_START: uri=/app/calendar, user_id=1`
2. `APP_R50_START: uri=/app/calendar, user_id=1, view=calendar/index, layout=base`
3. `HEADER_CTX_R50_START: uri=/app/calendar, user_id=1`
4. Exception log'u → **KÖK SEBEP**

## Log Formatı

```
[timestamp] [request_id] EVENT_NAME: key1=value1, key2=value2
TRACE:
...stack trace...
---
```

## Sonraki Adım
STAGE 4: Lokal repro + gerçek fatal çıkarılması - Log dosyalarını analiz ederek gerçek kök sebebi tespit et


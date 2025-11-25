# ROUND 47 – STAGE 3: KALICI ÇÖZÜM TASARIMI

**Tarih:** 2025-11-23  
**Round:** ROUND 47

---

## HEDEF MODEL

### 1) Calendar HTML Endpoint (`/app/calendar`)

**Auth Modeli:**
- `Auth::check()` + `Auth::hasCapability('calendar.view')` (veya sistemde calendar için doğru capability neyse)
- Kesinlikle `Auth::require*` kullanılmayacak

**Error Handling:**
- Dışta **kapsayıcı try/catch(Throwable)** → hiçbir exception global 500 template'e gitmeyecek
- İçeride:
  - Tüm service/repository çağrılarında safe default:
    - Boş sonuç = `[]`
    - Null = anlamlı fallback (ör: "hiç event yok")
  - Hiçbir yerde "undefined index/offset" patlaması yaşanmayacak; array key'leri `?? null` ile okunacak

**Output:**
- Her durumda ya:
  - 200 + calendar view (boş/dolu)
  - veya 200 + sade error view / redirect (`/app/`)
  - ama **asla 500 error page** yok

---

### 2) Calendar API Endpoint'leri (örn. `/app/api/calendar/events`)

**JSON-only Guarantee:**
- `Content-Type: application/json; charset=utf-8`
- `echo json_encode([...]); exit;`

**Auth Modeli:**
- `Auth::check()` yoksa → 401 JSON `{ "success": false, "error": "unauthorized" }`
- `hasCapability('calendar.view')` false ise → 403 JSON

**Error Handling:**
- Tüm logic tek büyük `try/catch(Throwable $e)` içinde
- Catch'te:
  - Log → `calendar_api_r47.log`'a yaz
  - Response → 500 JSON: `{ "success": false, "error": "internal_error" }`
- Asla HTML veya 500 template yok

---

### 3) "First-load Initialization" Sorunu

**Eğer root cause "ilk request'te bir şey oluşturuluyor, patlıyor" ise:**
- Bu initialization logic'i:
  - Ya migrate/CLI tarafa taşınmalı,
  - Ya da CalendarController/Service içindeki try/catch ile güvenli hale getirilmeli (exception yutulmasın, loglansın ama kullanıcıya 500 gösterilmesin)

---

## UYGULAMA PLANI

### Controller Methodları Değişecek:
1. `CalendarController::index()` - Kapsayıcı try/catch + safe defaults
2. `ApiController::calendar()` - JSON-only guarantee + try/catch

### Service/Repository Methodlarına Safe Defaults Eklenecek:
- `Job::getByDateRange()` → null check, boş array döndür
- `Customer::all()` → null check, boş array döndür
- `Service::getActive()` → null check, boş array döndür

### Log Dosyaları:
- `app/logs/calendar_r47.log` - Controller seviyesi hatalar
- `app/logs/calendar_api_r47.log` - API seviyesi hatalar

---

**STAGE 3 TAMAMLANDI** ✅


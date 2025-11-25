# ROUND 47 – STAGE 1: KOD ENVANTERİ (CALENDAR ODAKLI)

**Tarih:** 2025-11-23  
**Round:** ROUND 47

---

## ROUTE TANIMLARI

**`app/index.php`:**
- `/calendar` → `CalendarController::index()` (ROUND 20'de try/catch eklendi ama yeterli değil)
- `/api/calendar` → `ApiController::calendar()`
- `/api/calendar/{id}` → `ApiController::calendar()`
- `/calendar/feed.ics` → `CalendarFeedController::feed()`
- `/calendar/sync` → `CalendarController::sync()`
- `/calendar/create` → `CalendarController::create()` (POST)
- `/calendar/update/{id}` → `CalendarController::update()` (POST)
- `/calendar/delete/{id}` → `CalendarController::delete()` (POST)
- `/calendar/status/{id}` → `CalendarController::updateStatus()` (POST)

---

## CALENDAR CONTROLLER METOD ENVANTERİ

| Method | Route | Auth Modeli | Error Handling | Potansiyel Risk |
|--------|-------|-------------|----------------|-----------------|
| `index()` | `/calendar` | ❓ | ⚠️ Route seviyesinde try/catch var ama controller içinde yok | First-load 500 riski |
| `sync()` | `/calendar/sync` | `$requireAuth` | ❓ | ❓ |
| `create()` | `/calendar/create` (POST) | `$requireAuth` | ❓ | ❓ |
| `update()` | `/calendar/update/{id}` (POST) | `$requireAuth` | ❓ | ❓ |
| `delete()` | `/calendar/delete/{id}` (POST) | `$requireAuth` | ❓ | ❓ |
| `updateStatus()` | `/calendar/status/{id}` (POST) | `$requireAuth` | ❓ | ❓ |

---

## API CONTROLLER - CALENDAR METODLARI

| Method | Route | Auth Modeli | Error Handling | Potansiyel Risk |
|--------|-------|-------------|----------------|-----------------|
| `calendar()` | `/api/calendar` | `$requireAuth` | ❓ | JSON-only guarantee? |

---

**STAGE 1 TAMAMLANDI** ✅


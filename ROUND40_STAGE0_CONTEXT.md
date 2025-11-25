# ROUND 40 – STAGE 0: CONTEXT ÖZETİ

**Tarih:** 2025-11-23  
**Round:** ROUND 40

---

## `/health` BEKLENEN DAVRANIŞ (ROUND 39 SONRASI)

### 1. HTTP Status
- **200** (normal durum)
- **503** (SystemHealth fail olduysa)

### 2. Content-Type
- **`application/json; charset=utf-8`** (her zaman)

### 3. JSON Alanları
- **`status`**: `"ok"` | `"error"` | `"degraded"` | `"healthy"`
- **`build`**: `"KUREAPP_R33_2025-11-22"` | `null`
- **`time`**: ISO 8601 timestamp (örn: `"2025-11-23T00:00:00+03:00"`)
- **`marker`**: `"KUREAPP_R36_MARKER_HEALTH_JSON_V1"`** (zorunlu)
- **`details`**: Optional SystemHealth checks (varsa)

---

## ASLA OLMAMALI

- ❌ HTML 500 error page
- ❌ Login sayfası
- ❌ Content-Type: `text/html`
- ❌ HTML tags (`<!DOCTYPE`, `<html`, `<body`)

---

**STAGE 0 TAMAMLANDI** ✅


# Production Browser Check Report (Max Harvest Mode)

**ROUND 14: Production Console Harvest & Cleanup Prep**

**Base URL:** https://www.kuretemizlik.com/app

**Browser Project:** desktop-chromium

**Timestamp:** 2025-11-22T03:55:40.916Z

## Summary

- **Total Pages:** 9
- ✅ **OK:** 4
- ⚠️ **WARNING:** 0
- ❌ **FAIL:** 5

- **Total Errors:** 7
- **Total Warnings:** 5
- **Network Errors (4xx/5xx):** 7

- **Unique Patterns:** 2

---

## Top 20 Patterns

| Pattern | Category | Level | Count | Example Message | Example Page |
|---------|----------|-------|-------|-----------------|--------------|
| `UNKNOWN` | unknown | error | 7 | Failed to load resource: the server responded w... | / |
| `NETWORK_404` | infra | warn | 5 | HTTP 404 GET https://www.kuretemizlik.com/app/d... | / |

---

## Page-by-Page Breakdown

### dashboard

**URL:** `https://www.kuretemizlik.com/app/`

**Status:** HTTP 200 ❌ FAIL

**Title:** Giriş Yap

**Issues:** 3 (2 errors, 1 warnings)

#### Console Messages (1)

- **UNKNOWN** (1): Failed to load resource: the server responded with a status of 404 ()

#### Network Errors (2)

- **UNKNOWN** (1): Network failure: net::ERR_ABORTED (https://www.kuretemizlik.com/app/performance/metrics)
- **NETWORK_404** (1): HTTP 404 GET https://www.kuretemizlik.com/app/dashboard

---

### login

**URL:** `https://www.kuretemizlik.com/app/login`

**Status:** HTTP 200 ❌ FAIL

**Title:** Giriş Yap

**Issues:** 3 (2 errors, 1 warnings)

#### Console Messages (1)

- **UNKNOWN** (1): Failed to load resource: the server responded with a status of 404 ()

#### Network Errors (2)

- **UNKNOWN** (1): Network failure: net::ERR_ABORTED (https://www.kuretemizlik.com/app/performance/metrics)
- **NETWORK_404** (1): HTTP 404 GET https://www.kuretemizlik.com/app/dashboard

---

### jobs.new

**URL:** `https://www.kuretemizlik.com/app/jobs/new`

**Status:** HTTP 200 ❌ FAIL

**Title:** Giriş Yap

**Issues:** 2 (1 errors, 1 warnings)

#### Console Messages (1)

- **UNKNOWN** (1): Failed to load resource: the server responded with a status of 404 ()

#### Network Errors (1)

- **NETWORK_404** (1): HTTP 404 GET https://www.kuretemizlik.com/app/dashboard

---

### health

**URL:** `https://www.kuretemizlik.com/app/health`

**Status:** HTTP 200 ❌ FAIL

**Title:** Giriş Yap

**Issues:** 2 (1 errors, 1 warnings)

#### Console Messages (1)

- **UNKNOWN** (1): Failed to load resource: the server responded with a status of 404 ()

#### Network Errors (1)

- **NETWORK_404** (1): HTTP 404 GET https://www.kuretemizlik.com/app/dashboard

---

### dashboard

**URL:** `https://www.kuretemizlik.com/app/dashboard`

**Status:** HTTP 404 ❌ FAIL

**Title:** 404 - Sayfa Bulunamadı

**Issues:** 2 (1 errors, 1 warnings)

#### Console Messages (1)

- **UNKNOWN** (1): Failed to load resource: the server responded with a status of 404 ()

#### Network Errors (1)

- **NETWORK_404** (1): HTTP 404 GET https://www.kuretemizlik.com/app/dashboard

---

### finance

**URL:** `https://www.kuretemizlik.com/app/finance`

**Status:** HTTP 200 ✅ OK

**Title:** Giriş Yap

**Issues:** 0 (0 errors, 0 warnings)

---

### portal.login

**URL:** `https://www.kuretemizlik.com/app/portal/login`

**Status:** HTTP 200 ✅ OK

**Title:** Müşteri Portalı

**H1:** Telefon numaranızla giriş yapın

**Issues:** 0 (0 errors, 0 warnings)

---

### units

**URL:** `https://www.kuretemizlik.com/app/units`

**Status:** HTTP 200 ✅ OK

**Title:** Giriş Yap

**Issues:** 0 (0 errors, 0 warnings)

---

### settings

**URL:** `https://www.kuretemizlik.com/app/settings`

**Status:** HTTP 200 ✅ OK

**Title:** Giriş Yap

**Issues:** 0 (0 errors, 0 warnings)

---

## ❌ Overall Status: FAIL

Production smoke test FAILED. Critical errors detected.

---

**Note (ROUND 14):** Bu rapor max harvest modunda oluşturulmuştur. Hiçbir console warning susturulmamıştır. Tüm error ve warning'ler toplanmış ve pattern bazında kategorize edilmiştir.

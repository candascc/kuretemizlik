# KUREAPP – TEST & TARAYICI ALTYAPISI KEŞFİ
## STAGE 4 – ÇALIŞTIRILABİLİR TARAYICI/TEST PAKETİ ÖNER

**Tarih:** 2025-11-22  
**Round:** ROUND 31 - Global Test & Tarayıcı Altyapısı Keşfi  
**Durum:** ✅ STAGE 4 TAMAMLANDI

---

## 🎯 STANDARD HEALTH CHECK PAKETİ

### LOCAL HEALTH CHECK

#### Minimum Gating Testleri (Pre-Commit / Pre-Deploy)

**Komut:**
```bash
npm run test:ui:gating:local
```

**Ne Yapar:**
- Desktop + Mobile Chromium'da çalıştırır
- Auth testleri (`auth.spec.ts`)
- E2E flows testleri (`e2e-flows.spec.ts`)
- E2E finance testleri (`e2e-finance.spec.ts`)
- E2E multi-tenant testleri (`e2e-multitenant.spec.ts`)
- E2E security testleri (`e2e-security.spec.ts`)

**Süre:** ~5-10 dakika (test sayısına bağlı)

**Risk:** Düşük (local environment, production'a etki yok)

**Kullanım Senaryosu:**
- Pre-commit hook (opsiyonel)
- Pre-deploy validation
- Günlük development akışında critical path testleri

---

#### Full Local Test Suite

**Komut:**
```bash
npm run test:ui
```

**Ne Yapar:**
- Tüm Playwright testlerini çalıştırır (17 spec dosyası)
- Mobile, Tablet, Desktop viewport'larda çalışır
- Visual regression, a11y, performance testleri dahil

**Süre:** ~15-30 dakika (test sayısına ve viewport sayısına bağlı)

**Risk:** Düşük (local environment)

**Kullanım Senaryosu:**
- Haftalık full regression test
- Major feature release öncesi
- CI/CD pipeline'da (şu an yok)

---

#### Visual Regression Testleri

**Komut:**
```bash
npm run test:ui:visual
```

**Ne Yapar:**
- Visual regression testlerini çalıştırır
- Screenshot comparison yapar
- Snapshot'ları güncellemek için: `npm run test:ui:update-snapshots`

**Süre:** ~3-5 dakika

**Risk:** Düşük (local environment)

**Kullanım Senaryosu:**
- UI değişikliklerinden sonra
- CSS/styling refactor'larından sonra
- Pre-release validation

---

#### Accessibility Testleri

**Komut:**
```bash
npm run test:ui:a11y
```

**Ne Yapar:**
- WCAG 2.1 AA compliance testleri
- Form labels, ARIA attributes, color contrast, keyboard navigation kontrolü

**Süre:** ~2-3 dakika

**Risk:** Düşük (local environment)

**Kullanım Senaryosu:**
- Accessibility iyileştirmelerinden sonra
- Pre-release validation
- Compliance audit öncesi

---

#### Performance Testleri (Lighthouse)

**Komut:**
```bash
npm run test:perf:lighthouse:local
```

**Ne Yapar:**
- Lighthouse CI'yi local mode'da çalıştırır
- 4 URL'i tarar (login, dashboard, units, management-fees)
- Performance, Accessibility, Best Practices, SEO skorlarını kontrol eder
- Core Web Vitals metriklerini ölçer

**Süre:** ~5-10 dakika (3 run × 4 URL = 12 Lighthouse run)

**Risk:** Düşük (local environment)

**Kullanım Senaryosu:**
- Performance iyileştirmelerinden sonra
- Pre-release validation
- Haftalık performance monitoring

**Not:** Local server çalışıyor olmalı (`http://localhost/app`)

---

### PRODUCTION HEALTH CHECK

#### Production Smoke Testleri

**Komut:**
```bash
PROD_BASE_URL=https://www.kuretemizlik.com/app npm run test:prod:smoke
```

**Ne Yapar:**
- Production environment'ı HTTP-only tarar (read-only)
- `/health` endpoint kontrolü (JSON-only guarantee)
- `/login` sayfası kontrolü
- `/jobs/new` sayfası kontrolü (500 hatası olmamalı)
- 404 page kontrolü
- Security headers kontrolü
- Admin login flow (opsiyonel, credentials gerekli)

**Süre:** ~2-3 dakika

**Risk:** ⚠️ **ORTA** (production environment'a HTTP request yapıyor, ama read-only)

**Kullanım Senaryosu:**
- Pre-deployment validation
- Post-deployment smoke test
- Günlük production health check (scheduled run önerilir)
- Critical bug fix sonrası validation

**Önemli Notlar:**
- Read-only, production'a yazma yapmıyor
- Console error whitelist'leri var (ROUND 30)
- Admin login flow için `PROD_ADMIN_EMAIL` ve `PROD_ADMIN_PASSWORD` env var'ları gerekli

---

#### Production Browser Crawl (Admin Role)

**Komut:**
```bash
PROD_BASE_URL=https://www.kuretemizlik.com/app \
CRAWL_ROLE_KEY=admin \
MAX_DEPTH=3 \
MAX_PAGES=150 \
npm run check:prod:browser:crawl
```

**Ne Yapar:**
- Production'a login yapar (admin role)
- BFS (Breadth-First Search) ile recursive crawl yapar
- Console error/warning collection
- Network 4xx/5xx error collection
- Pattern extraction ve global statistics
- JSON + Markdown rapor üretir

**Süre:** ~10-20 dakika (max pages ve depth'e bağlı)

**Risk:** ⚠️ **ORTA-YÜKSEK** (production environment'a login yapıyor, crawl yapıyor)

**Kullanım Senaryosu:**
- Haftalık production health check (scheduled run önerilir)
- Major release öncesi comprehensive check
- Production issue investigation
- Post-deployment validation

**Önemli Notlar:**
- Admin credentials gerekli (`CRAWL_ADMIN_USERNAME`, `CRAWL_ADMIN_PASSWORD` env var'ları)
- Production'a yazma yapmıyor (read-only crawl)
- Raporlar: `PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json` ve `.md`

---

#### Production Browser Crawl (Multi-Role)

**Komut:**
```bash
PROD_BASE_URL=https://www.kuretemizlik.com/app \
CRAWL_ROLES=admin,ops,mgmt \
MAX_DEPTH=2 \
MAX_PAGES=100 \
npm run check:prod:browser:crawl:roles
```

**Ne Yapar:**
- Multiple roles için sequential crawl yapar (admin, ops, mgmt)
- Her role için ayrı rapor üretir
- Role-specific seed paths kullanır

**Süre:** ~30-60 dakika (3 role × crawl süresi)

**Risk:** ⚠️ **ORTA-YÜKSEK** (production environment'a multiple role login yapıyor)

**Kullanım Senaryosu:**
- Aylık comprehensive production health check
- Multi-role testing validation
- Security audit öncesi

**Önemli Notlar:**
- Her role için credentials gerekli (`CRAWL_ADMIN_USERNAME`, `CRAWL_OPS_USERNAME`, `CRAWL_MGMT_USERNAME`, vb.)
- Raporlar: `PRODUCTION_BROWSER_CHECK_CRAWL_<ROLE>.json` ve `.md` (her role için)

---

#### Production Basic Browser Check (Max Harvest)

**Komut:**
```bash
PROD_BASE_URL=https://www.kuretemizlik.com/app npm run check:prod:browser
```

**Ne Yapar:**
- 9 sabit URL'i tarar (dashboard, login, jobs/new, health, finance, portal/login, units, settings)
- Console error/warning/info/log collection (no whitelist)
- Network 4xx/5xx error collection
- Pattern extraction ve category assignment

**Süre:** ~3-5 dakika

**Risk:** ⚠️ **ORTA** (production environment'a HTTP request yapıyor, ama read-only)

**Kullanım Senaryosu:**
- Hızlı production health check
- Critical endpoint validation
- Pre-deployment quick check

**Önemli Notlar:**
- Login yapmıyor (anonymous check)
- Raporlar: `PRODUCTION_BROWSER_CHECK_REPORT.json` ve `.md`

---

## 📋 ÖNERİLEN HEALTH CHECK ROUTINE

### Günlük (Development)

```bash
# Minimum gating testleri (pre-commit)
npm run test:ui:gating:local
```

**Süre:** ~5-10 dakika  
**Risk:** Düşük  
**Kullanım:** Pre-commit hook veya günlük development akışında

---

### Haftalık (Local Full Regression)

```bash
# Full local test suite
npm run test:ui

# Visual regression
npm run test:ui:visual

# Accessibility
npm run test:ui:a11y

# Performance (Lighthouse)
npm run test:perf:lighthouse:local
```

**Süre:** ~30-45 dakika (toplam)  
**Risk:** Düşük  
**Kullanım:** Haftalık full regression test, major feature release öncesi

---

### Pre-Deployment (Production Smoke)

```bash
# Production smoke testleri
PROD_BASE_URL=https://www.kuretemizlik.com/app npm run test:prod:smoke
```

**Süre:** ~2-3 dakika  
**Risk:** Orta (read-only)  
**Kullanım:** Pre-deployment validation, post-deployment smoke test

---

### Haftalık (Production Health Check)

```bash
# Production browser crawl (admin role)
PROD_BASE_URL=https://www.kuretemizlik.com/app \
CRAWL_ROLE_KEY=admin \
MAX_DEPTH=3 \
MAX_PAGES=150 \
npm run check:prod:browser:crawl
```

**Süre:** ~10-20 dakika  
**Risk:** Orta-Yüksek (production login)  
**Kullanım:** Haftalık production health check (scheduled run önerilir)

---

### Aylık (Comprehensive Production Check)

```bash
# Production browser crawl (multi-role)
PROD_BASE_URL=https://www.kuretemizlik.com/app \
CRAWL_ROLES=admin,ops,mgmt \
MAX_DEPTH=2 \
MAX_PAGES=100 \
npm run check:prod:browser:crawl:roles
```

**Süre:** ~30-60 dakika  
**Risk:** Orta-Yüksek (production multiple role login)  
**Kullanım:** Aylık comprehensive production health check, security audit öncesi

---

## ⚠️ RİSK ANALİZİ

### Düşük Risk (Local Environment)

- ✅ `test:ui:*` - Tüm local Playwright testleri
- ✅ `test:perf:lighthouse:local` - Local Lighthouse CI
- ✅ `test:ui:visual` - Visual regression testleri
- ✅ `test:ui:a11y` - Accessibility testleri

**Açıklama:** Local environment'da çalışıyor, production'a etki yok.

---

### Orta Risk (Production Read-Only)

- ⚠️ `test:prod:smoke` - Production smoke testleri (HTTP-only, read-only)
- ⚠️ `check:prod:browser` - Production basic browser check (HTTP-only, read-only)

**Açıklama:** Production environment'a HTTP request yapıyor, ama read-only. Production'a yazma yapmıyor.

**Önlemler:**
- Read-only check'ler (no POST/PUT/DELETE)
- Console error whitelist'leri (ROUND 30)
- Timeout'lar (30s)

---

### Orta-Yüksek Risk (Production Login)

- ⚠️ `check:prod:browser:crawl` - Production recursive crawl (login yapıyor)
- ⚠️ `check:prod:browser:crawl:roles` - Production multi-role crawl (login yapıyor)

**Açıklama:** Production environment'a login yapıyor, crawl yapıyor. Production'a yazma yapmıyor ama session oluşturuyor.

**Önlemler:**
- Read-only crawl (no POST/PUT/DELETE)
- Session timeout handling
- Rate limiting (max pages, max depth)
- Credentials environment variable'ları (secret management)

---

## 📊 SÜRE TAHMİNLERİ

| Komut | Süre (Dakika) | Risk | Kullanım Sıklığı |
|-------|---------------|------|------------------|
| `test:ui:gating:local` | 5-10 | Düşük | Günlük |
| `test:ui` | 15-30 | Düşük | Haftalık |
| `test:ui:visual` | 3-5 | Düşük | Haftalık |
| `test:ui:a11y` | 2-3 | Düşük | Haftalık |
| `test:perf:lighthouse:local` | 5-10 | Düşük | Haftalık |
| `test:prod:smoke` | 2-3 | Orta | Pre-deployment |
| `check:prod:browser` | 3-5 | Orta | Haftalık |
| `check:prod:browser:crawl` (admin) | 10-20 | Orta-Yüksek | Haftalık |
| `check:prod:browser:crawl:roles` (multi) | 30-60 | Orta-Yüksek | Aylık |

---

## 🎯 ÖNERİLEN CI/CD ENTEGRASYONU

### GitHub Actions Workflow Örnekleri

#### 1. UI Tests Workflow (Pre-Commit / PR)

```yaml
name: UI Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
      - run: npm install
      - run: npx playwright install --with-deps chromium
      - run: npm run test:ui:gating:local
      - uses: actions/upload-artifact@v3
        if: always()
        with:
          name: playwright-report
          path: tests/ui/reports/
```

**Süre:** ~10-15 dakika (CI'da)  
**Risk:** Düşük  
**Kullanım:** Her push/PR'da otomatik çalışır

---

#### 2. Production Smoke Tests Workflow (Scheduled)

```yaml
name: Production Smoke Tests

on:
  schedule:
    - cron: '0 9 * * *'  # Her gün 09:00 UTC
  workflow_dispatch:

jobs:
  smoke:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
      - run: npm install
      - run: npx playwright install --with-deps chromium
      - run: |
          PROD_BASE_URL=https://www.kuretemizlik.com/app \
          npm run test:prod:smoke
      - uses: actions/upload-artifact@v3
        if: always()
        with:
          name: smoke-test-report
          path: tests/ui/reports/
```

**Süre:** ~3-5 dakika  
**Risk:** Orta (read-only)  
**Kullanım:** Günlük scheduled run

---

#### 3. Production Browser Crawl Workflow (Scheduled)

```yaml
name: Production Browser Crawl

on:
  schedule:
    - cron: '0 10 * * 1'  # Her Pazartesi 10:00 UTC
  workflow_dispatch:

jobs:
  crawl:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
      - run: npm install
      - run: npx playwright install --with-deps chromium
      - run: |
          PROD_BASE_URL=https://www.kuretemizlik.com/app \
          CRAWL_ROLE_KEY=admin \
          CRAWL_ADMIN_USERNAME=${{ secrets.CRAWL_ADMIN_USERNAME }} \
          CRAWL_ADMIN_PASSWORD=${{ secrets.CRAWL_ADMIN_PASSWORD }} \
          MAX_DEPTH=3 \
          MAX_PAGES=150 \
          npm run check:prod:browser:crawl
      - uses: actions/upload-artifact@v3
        if: always()
        with:
          name: crawl-report
          path: PRODUCTION_BROWSER_CHECK_CRAWL_*.json
          path: PRODUCTION_BROWSER_CHECK_CRAWL_*.md
```

**Süre:** ~15-25 dakika  
**Risk:** Orta-Yüksek (production login)  
**Kullanım:** Haftalık scheduled run

---

## 📝 ÖZET

### Minimum Health Check (Günlük)

```bash
npm run test:ui:gating:local
```

### Standard Health Check (Haftalık)

```bash
# Local
npm run test:ui
npm run test:ui:visual
npm run test:ui:a11y
npm run test:perf:lighthouse:local

# Production
PROD_BASE_URL=https://www.kuretemizlik.com/app npm run test:prod:smoke
PROD_BASE_URL=https://www.kuretemizlik.com/app npm run check:prod:browser:crawl
```

### Comprehensive Health Check (Aylık)

```bash
# Multi-role production crawl
PROD_BASE_URL=https://www.kuretemizlik.com/app \
CRAWL_ROLES=admin,ops,mgmt \
npm run check:prod:browser:crawl:roles
```

---

**STAGE 4 TAMAMLANDI** ✅

**Sonraki Adım:** STAGE 5 - Global Rapor: "KUREAPP TEST & TARAYICI ATLASI"


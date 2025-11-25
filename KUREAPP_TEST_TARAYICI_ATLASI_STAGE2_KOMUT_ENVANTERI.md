# KUREAPP – TEST & TARAYICI ALTYAPISI KEŞFİ
## STAGE 2 – KOMUT & PIPELINE ENVANTERİ

**Tarih:** 2025-11-22  
**Round:** ROUND 31 - Global Test & Tarayıcı Altyapısı Keşfi  
**Durum:** ✅ STAGE 2 TAMAMLANDI

---

## 📋 NPM SCRIPTS ENVANTERİ

### TEST SCRIPTS

| Script Adı | Ne Yaptığı | Test/Tarama Ailesi | Environment | Tetiklenme Şekli |
|------------|------------|-------------------|-------------|------------------|
| `test` | Placeholder (error mesajı) | - | - | Manual |
| `test:ui` | Tüm Playwright testlerini çalıştırır | Playwright Test Suite | Local (default: `http://kuretemizlik.local/app`) | Manual / CI (yok) |
| `test:ui:headed` | Playwright testlerini headed mode'da çalıştırır (browser görünür) | Playwright Test Suite | Local | Manual |
| `test:ui:mobile` | Sadece mobile-chromium project testlerini çalıştırır | Playwright Test Suite | Local | Manual |
| `test:ui:desktop` | Sadece desktop-chromium project testlerini çalıştırır | Playwright Test Suite | Local | Manual |
| `test:ui:report` | Playwright HTML raporunu gösterir | Playwright Test Suite | Local | Manual |
| `test:ui:visual` | Visual regression testlerini çalıştırır (`visual-regression.spec.ts`) | Playwright Test Suite (Visual) | Local | Manual |
| `test:ui:a11y` | Accessibility testlerini çalıştırır (`accessibility.spec.ts`) | Playwright Test Suite (A11y) | Local | Manual |
| `test:ui:update-snapshots` | Visual regression snapshot'larını günceller | Playwright Test Suite (Visual) | Local | Manual |
| `test:ui:e2e` | Tüm E2E testlerini çalıştırır (flows, finance, multitenant, security) | Playwright Test Suite (E2E) | Local | Manual |
| `test:ui:e2e:flows` | Sadece E2E user flow testlerini çalıştırır (`e2e-flows.spec.ts`) | Playwright Test Suite (E2E) | Local | Manual |
| `test:ui:e2e:finance` | Sadece E2E finance testlerini çalıştırır (`e2e-finance.spec.ts`) | Playwright Test Suite (E2E) | Local | Manual |
| `test:ui:e2e:multitenant` | Sadece E2E multi-tenant testlerini çalıştırır (`e2e-multitenant.spec.ts`) | Playwright Test Suite (E2E) | Local | Manual |
| `test:perf` | Performance testlerini çalıştırır (`performance.spec.ts`) | Playwright Test Suite (Performance) | Local | Manual |
| `test:perf:lighthouse:local` | Lighthouse CI'yi local mode'da çalıştırır (`lhci autorun`) | Lighthouse/Perf | Local (`http://localhost/app`) | Manual |
| `test:perf:lighthouse:ci` | Lighthouse CI'yi CI mode'da çalıştırır (filesystem upload) | Lighthouse/Perf | Local/CI | Manual / CI (yok) |
| `test:ui:cross` | Cross-browser testlerini çalıştırır (Chromium, Firefox, WebKit) | Playwright Test Suite (Cross-Browser) | Local (opt-in: `ENABLE_CROSS_BROWSER=1`) | Manual |
| `test:ui:smoke:cross` | Smoke testlerini cross-browser'da çalıştırır (auth, dashboard) | Playwright Test Suite (Cross-Browser) | Local (opt-in: `ENABLE_CROSS_BROWSER=1`) | Manual |
| `test:ui:gating:local` | Gating testlerini çalıştırır (auth, e2e-flows, e2e-finance, e2e-multitenant, e2e-security) | Playwright Test Suite (Gating) | Local (desktop + mobile) | Manual / Pre-commit (yok) |
| `test:prod:smoke` | Production smoke testlerini çalıştırır (`prod-smoke.spec.ts`) | Playwright Test Suite (Prod Smoke) | Production (`PROD_BASE_URL` env var) | Manual |

### CHECK / CRAWL SCRIPTS

| Script Adı | Ne Yaptığı | Test/Tarama Ailesi | Environment | Tetiklenme Şekli |
|------------|------------|-------------------|-------------|------------------|
| `check:prod:browser` | Production browser check (max harvest mode) - 9 sabit URL tarar | Browser Crawl | Production (`PROD_BASE_URL` env var) | Manual |
| `check:prod:browser:full` | Production full nav mode - navigation link'lerinden otomatik URL çıkarır | Browser Crawl | Production (`PROD_BASE_URL` env var) | Manual |
| `check:prod:browser:crawl` | Production recursive crawl (BFS) - role-aware, seed paths | Browser Crawl | Production (`PROD_BASE_URL`, `CRAWL_ROLE_KEY` env vars) | Manual |
| `check:prod:browser:crawl:roles` | Multi-role crawl orchestrator - multiple roles sequential crawl | Browser Crawl | Production (`PROD_BASE_URL`, `CRAWL_ROLES` env vars) | Manual |

### BUILD SCRIPTS

| Script Adı | Ne Yaptığı | Test/Tarama Ailesi | Environment | Tetiklenme Şekli |
|------------|------------|-------------------|-------------|------------------|
| `build:css:tailwind` | Tailwind CSS build (input → output, minify) | Build Pipeline | Local | Manual / CI (yok) |

---

## 🔍 SCRIPT ANALİZİ

### Test Script'leri Kategorisi

**Toplam:** 20 test script'i

**Dağılım:**
- **Playwright UI Tests:** 15 script
  - General: 5 (test:ui, test:ui:headed, test:ui:mobile, test:ui:desktop, test:ui:report)
  - Visual: 2 (test:ui:visual, test:ui:update-snapshots)
  - A11y: 1 (test:ui:a11y)
  - E2E: 4 (test:ui:e2e, test:ui:e2e:flows, test:ui:e2e:finance, test:ui:e2e:multitenant)
  - Performance: 1 (test:perf)
  - Cross-Browser: 2 (test:ui:cross, test:ui:smoke:cross)
  - Gating: 1 (test:ui:gating:local)
  - Prod Smoke: 1 (test:prod:smoke)
- **Lighthouse/Perf:** 2 script (test:perf:lighthouse:local, test:perf:lighthouse:ci)
- **Browser Crawl:** 4 script (check:prod:browser, check:prod:browser:full, check:prod:browser:crawl, check:prod:browser:crawl:roles)

### Eksik Script'ler

**PHP Test Suite:**
- ❌ `test:php` - PHPUnit testlerini çalıştıran script yok
- ❌ `test:php:unit` - PHPUnit unit testlerini çalıştıran script yok
- ❌ `test:php:functional` - PHPUnit functional testlerini çalıştıran script yok
- ❌ `test:php:integration` - PHPUnit integration testlerini çalıştıran script yok
- ❌ `test:php:security` - PHPUnit security testlerini çalıştıran script yok
- ❌ `test:php:performance` - PHPUnit performance testlerini çalıştıran script yok

**Lint/Format:**
- ❌ `lint` - Code linting script'i yok
- ❌ `lint:fix` - Code linting fix script'i yok
- ❌ `format` - Code formatting script'i yok

**Audit/Security:**
- ❌ `audit` - npm audit script'i yok
- ❌ `audit:fix` - npm audit fix script'i yok

---

## 🔄 CI/CD WORKFLOW ENVANTERİ

### GitHub Actions Workflows

**Durum:** ❌ **MEVCUT DEĞİL**

**Eksik Workflow'lar:**
1. **UI Tests Workflow:**
   - Event: `push`, `pull_request`
   - Jobs:
     - Install dependencies
     - Install Playwright browsers
     - Run `test:ui:gating:local`
     - Upload test reports
   - Status: ❌ YOK

2. **Production Smoke Tests Workflow:**
   - Event: `schedule` (cron: daily), `workflow_dispatch`
   - Jobs:
     - Run `test:prod:smoke`
     - Upload test reports
   - Status: ❌ YOK

3. **Lighthouse CI Workflow:**
   - Event: `schedule` (cron: weekly), `workflow_dispatch`
   - Jobs:
     - Start local server
     - Run `test:perf:lighthouse:ci`
     - Upload Lighthouse reports
   - Status: ❌ YOK

4. **Browser Crawl Workflow:**
   - Event: `schedule` (cron: weekly), `workflow_dispatch`
   - Jobs:
     - Run `check:prod:browser:crawl:roles` (admin role)
     - Upload crawl reports
   - Status: ❌ YOK

5. **PHP Tests Workflow:**
   - Event: `push`, `pull_request`
   - Jobs:
     - Setup PHP
     - Install Composer dependencies
     - Run PHPUnit tests
     - Upload test reports
   - Status: ❌ YOK

---

## 📊 SCRIPT KULLANIM ANALİZİ

### En Çok Kullanılan Script'ler (Tahmin)

1. **`test:ui`** - Günlük development akışında en çok kullanılan
2. **`test:ui:gating:local`** - Pre-commit veya pre-deploy'da kullanılması gereken (ama CI yok)
3. **`check:prod:browser:crawl`** - Production tarama için (manual)
4. **`test:prod:smoke`** - Production smoke test (manual)

### Hiç Kullanılmayan Script'ler (Potansiyel)

1. **`test:ui:cross`** - Cross-browser testler (opt-in gerektiriyor, muhtemelen nadiren kullanılıyor)
2. **`test:ui:smoke:cross`** - Cross-browser smoke testler (opt-in gerektiriyor)
3. **`test:perf:lighthouse:ci`** - Lighthouse CI (CI workflow yok, muhtemelen hiç kullanılmıyor)
4. **`check:prod:browser:full`** - Full nav mode (recursive crawl daha kapsamlı, muhtemelen tercih edilmiyor)

---

## 🔍 ENVIRONMENT VARIABLE ENVANTERİ

### Playwright Test Environment Variables

| Variable | Açıklama | Default | Kullanım |
|----------|----------|---------|----------|
| `BASE_URL` | Test edilecek uygulamanın base URL'i | `http://kuretemizlik.local/app` | Tüm Playwright testleri |
| `ENABLE_CROSS_BROWSER` | Cross-browser testleri aktifleştir (Firefox/WebKit) | - | Cross-browser testleri |
| `TEST_ADMIN_EMAIL` | Admin email (test data) | - | Auth testleri |
| `TEST_ADMIN_PASSWORD` | Admin password (test data) | - | Auth testleri |
| `TEST_RESIDENT_PHONE` | Resident phone (test data) | - | Resident testleri |
| `TEST_COMPANY_A_EMAIL` | Company A email (multi-tenant) | - | Multi-tenant testleri |
| `TEST_COMPANY_A_PASSWORD` | Company A password (multi-tenant) | - | Multi-tenant testleri |
| `TEST_COMPANY_B_EMAIL` | Company B email (multi-tenant) | - | Multi-tenant testleri |
| `TEST_COMPANY_B_PASSWORD` | Company B password (multi-tenant) | - | Multi-tenant testleri |

### Production Browser Check Environment Variables

| Variable | Açıklama | Default | Kullanım |
|----------|----------|---------|----------|
| `PROD_BASE_URL` | Production base URL | `https://www.kuretemizlik.com/app` | Tüm browser crawl script'leri |
| `PROD_ADMIN_EMAIL` | Admin email (production) | - | Browser crawl login |
| `PROD_ADMIN_PASSWORD` | Admin password (production) | - | Browser crawl login |
| `CRAWL_ROLE_KEY` | Crawl için kullanılacak role (admin, ops, mgmt) | `admin` | `check-prod-browser-crawl.ts` |
| `CRAWL_ROLES` | Multi-role crawl için role listesi (virgülle ayrılmış) | `admin` | `check-prod-browser-crawl-roles.ts` |
| `START_PATH` | Crawl başlangıç path'i | Role-specific | `check-prod-browser-crawl.ts` |
| `MAX_DEPTH` | Crawl max depth | `3` | `check-prod-browser-crawl.ts` |
| `MAX_PAGES` | Crawl max pages | `150` | `check-prod-browser-crawl.ts` |
| `CRAWL_ADMIN_USERNAME` | Admin username (crawl) | `admin` (fallback) | `check-prod-browser-crawl.ts` |
| `CRAWL_ADMIN_PASSWORD` | Admin password (crawl) | `12dream21` (fallback, LOCAL QA ONLY) | `check-prod-browser-crawl.ts` |
| `CRAWL_OPS_USERNAME` | Ops username (crawl) | - | `check-prod-browser-crawl.ts` |
| `CRAWL_OPS_PASSWORD` | Ops password (crawl) | - | `check-prod-browser-crawl.ts` |
| `CRAWL_MGMT_USERNAME` | Mgmt username (crawl) | - | `check-prod-browser-crawl.ts` |
| `CRAWL_MGMT_PASSWORD` | Mgmt password (crawl) | - | `check-prod-browser-crawl.ts` |

### Lighthouse CI Environment Variables

| Variable | Açıklama | Default | Kullanım |
|----------|----------|---------|----------|
| - | Lighthouse CI config `lighthouserc.json` içinde hardcoded | `http://localhost/app` | Lighthouse CI |

---

## 📊 ÖZET

### Script İstatistikleri

- **Toplam npm Script:** 25
  - Test: 20
  - Check/Crawl: 4
  - Build: 1
- **CI/CD Workflow:** 0
- **Environment Variables:** 15+ (Playwright + Browser Crawl)

### Eksiklikler

1. **CI/CD Workflow:** Hiçbir workflow yok
2. **PHP Test Script'leri:** npm script'lerinde PHPUnit testleri yok
3. **Lint/Format Script'leri:** Code quality script'leri yok
4. **Audit Script'leri:** Security audit script'leri yok
5. **Scheduled Runs:** Hiçbir scheduled run yok

---

**STAGE 2 TAMAMLANDI** ✅

**Sonraki Adım:** STAGE 3 - "Unutulan / Dormant" Yapıları Tespit Et


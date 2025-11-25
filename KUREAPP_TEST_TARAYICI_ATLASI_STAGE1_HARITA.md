# KUREAPP – TEST & TARAYICI ALTYAPISI KEŞFİ
## STAGE 1 – TEST & TARAYICI EKOSİSTEMİ HARİTALANDIRMA

**Tarih:** 2025-11-22  
**Round:** ROUND 31 - Global Test & Tarayıcı Altyapısı Keşfi  
**Durum:** ✅ STAGE 1 TAMAMLANDI

---

## 📊 TEST & TARAYICI EKOSİSTEM HARİTASI

### 1️⃣ PLAYWRIGHT TEST SUITE

**Açıklama:** Kapsamlı UI/UX test suite'i. Playwright kullanarak browser-based testler.

#### Functional Testler
- **Dosyalar:**
  - `tests/ui/auth.spec.ts` - Authentication flow (login, logout, form validation)
  - `tests/ui/dashboard.spec.ts` - Dashboard & KPI kartları responsive testleri
  - `tests/ui/units.spec.ts` - Units list/detail sayfa testleri
  - `tests/ui/finance.spec.ts` - Finance form testleri
  - `tests/ui/layout.spec.ts` - Navbar & Footer layout testleri
  - `tests/ui/calendar.spec.ts` - Calendar component testleri
  - `tests/ui/management.spec.ts` - Management module testleri
  - `tests/ui/login-recurring.spec.ts` - Login recurring job testleri

- **Hedef Environment:** Local (default: `http://kuretemizlik.local/app`)
- **Kullanım Şekli:** npm script (`test:ui`, `test:ui:headed`, `test:ui:mobile`, `test:ui:desktop`)
- **Coverage:** Authentication, dashboard, units, finance, layout, calendar, management

#### Visual Regression Testler
- **Dosyalar:**
  - `tests/ui/visual-regression.spec.ts` - Screenshot comparison testleri
  - `tests/ui/visual-regression.spec.ts-snapshots/` - Snapshot dosyaları

- **Hedef Environment:** Local
- **Kullanım Şekli:** npm script (`test:ui:visual`, `test:ui:update-snapshots`)
- **Coverage:** Dashboard KPI cards, footer, navbar, button states, card components, form inputs

#### Accessibility (a11y) Testler
- **Dosyalar:**
  - `tests/ui/accessibility.spec.ts` - WCAG 2.1 AA compliance testleri

- **Hedef Environment:** Local
- **Kullanım Şekli:** npm script (`test:ui:a11y`)
- **Coverage:** Form labels, ARIA attributes, color contrast, keyboard navigation, focus indicators
- **Tool:** `@axe-core/playwright` (devDependency)

#### E2E User Flow Testleri
- **Dosyalar:**
  - `tests/ui/e2e-flows.spec.ts` - Manager/staff flow testleri
  - `tests/ui/e2e-finance.spec.ts` - Finance flow testleri
  - `tests/ui/e2e-multitenant.spec.ts` - Multi-tenant isolation testleri
  - `tests/ui/e2e-security.spec.ts` - Security flow testleri

- **Hedef Environment:** Local
- **Kullanım Şekli:** npm script (`test:ui:e2e`, `test:ui:e2e:flows`, `test:ui:e2e:finance`, `test:ui:e2e:multitenant`)
- **Coverage:** Manager flow (create building → unit → job), staff flow, finance flow, multi-tenant isolation

#### Performance / Core Web Vitals Testleri
- **Dosyalar:**
  - `tests/ui/performance.spec.ts` - Core Web Vitals testleri

- **Hedef Environment:** Local
- **Kullanım Şekli:** npm script (`test:perf`)
- **Coverage:** Performance metrics, Core Web Vitals

#### Cross-Browser Testler
- **Dosyalar:** Tüm spec dosyaları (Playwright config'de cross-browser projects tanımlı)

- **Hedef Environment:** Local (opt-in: `ENABLE_CROSS_BROWSER=1`)
- **Kullanım Şekli:** npm script (`test:ui:cross`, `test:ui:smoke:cross`)
- **Coverage:** Chromium (default), Firefox (opt-in), WebKit/Safari (opt-in)
- **Config:** `playwright.config.ts` - projects: `desktop-firefox`, `desktop-webkit` (conditional)

#### Prod Smoke Testleri
- **Dosyalar:**
  - `tests/ui/prod-smoke.spec.ts` - Production smoke testleri (read-only, HTTP only)

- **Hedef Environment:** Production (`PROD_BASE_URL` env var)
- **Kullanım Şekli:** npm script (`test:prod:smoke`)
- **Coverage:** `/health`, `/login`, `/jobs/new`, 404 page, security headers, admin login flow (optional)
- **Özellikler:** Read-only, no SSH/DB access, console error whitelist (ROUND 30)

#### Edge Cases
- **Dosyalar:**
  - `tests/ui/edge-cases.spec.ts` - Edge case senaryoları

- **Hedef Environment:** Local
- **Kullanım Şekli:** npm script (`test:ui`)
- **Coverage:** Empty states, long text handling, very small viewport (320px), large viewport (1920px), breakpoint geçişleri, Turkish long words

#### Helper Fonksiyonlar
- **Dosyalar:**
  - `tests/ui/helpers/auth.ts` - Login/logout helper fonksiyonları
  - `tests/ui/helpers/viewport.ts` - Viewport resize ve layout helper'ları
  - `tests/ui/helpers/data.ts` - Test data creation/cleanup helper'ları

- **Kullanım:** Tüm test spec'lerinde import edilerek kullanılıyor

#### Config & Setup
- **Dosyalar:**
  - `playwright.config.ts` - Playwright yapılandırması
    - Projects: mobile-chromium, tablet-chromium, desktop-chromium, desktop-large-chromium, desktop-firefox (opt-in), desktop-webkit (opt-in)
    - Base URL: `process.env.BASE_URL || 'http://kuretemizlik.local/app'`
    - Reporters: list, html, json
    - Timeout: 30s
    - Retries: CI'da 2, local'de 0
    - Workers: CI'da 1, local'de undefined (parallel)

- **Dokümantasyon:**
  - `tests/ui/README.md` - UI test suite dokümantasyonu

---

### 2️⃣ BROWSER CRAWL & PROD TARAMA YAPILARI

**Açıklama:** Production environment'ı tarayan, console/network error'ları toplayan, pattern extraction yapan script'ler.

#### Basic Browser Check (Max Harvest Mode)
- **Dosyalar:**
  - `scripts/check-prod-browser.ts` - Production browser check (max harvest mode)

- **Hedef Environment:** Production (`PROD_BASE_URL` env var)
- **Kullanım Şekli:** npm script (`check:prod:browser`)
- **Coverage:** 9 sabit URL (dashboard, login, jobs/new, health, finance, portal/login, units, settings)
- **Özellikler:**
  - Console error/warn/info/log collection (no whitelist)
  - Network 4xx/5xx error collection
  - Pattern extraction (NETWORK_404, ALPINE_EXPRESSION_ERROR, vb.)
  - Category assignment (security, performance, a11y, DX, infra)
  - JSON + Markdown rapor üretimi

- **Raporlar:**
  - `PRODUCTION_BROWSER_CHECK_REPORT.json`
  - `PRODUCTION_BROWSER_CHECK_REPORT.md`

#### Full Nav Browser Check
- **Dosyalar:**
  - `scripts/check-prod-browser-full.ts` - Production full nav mode

- **Hedef Environment:** Production (`PROD_BASE_URL` env var)
- **Kullanım Şekli:** npm script (`check:prod:browser:full`)
- **Coverage:** Navigation link'lerinden otomatik çıkarılan URL'ler + common routes
- **Özellikler:**
  - Login yapıyor (admin credentials)
  - Navigation link'lerini extract ediyor
  - Her URL'i ayrı page context'te tarıyor
  - Console + network error collection

- **Raporlar:**
  - `PRODUCTION_BROWSER_CHECK_FULL_NAV.json`
  - `PRODUCTION_BROWSER_CHECK_FULL_NAV.md`

#### Recursive Crawl (BFS)
- **Dosyalar:**
  - `scripts/check-prod-browser-crawl.ts` - Production recursive crawl (BFS)

- **Hedef Environment:** Production (`PROD_BASE_URL` env var)
- **Kullanım Şekli:** npm script (`check:prod:browser:crawl`)
- **Coverage:** BFS ile recursive crawl (max depth, max pages limit)
- **Özellikler:**
  - Role-aware login (admin, ops, mgmt) - ROUND 28
  - BFS (Breadth-First Search) crawl
  - Seed paths (role-specific)
  - Auto re-login if session expired
  - Console/network error collection
  - Pattern extraction ve global statistics
  - JSON + Markdown rapor üretimi (role-specific)

- **Raporlar:**
  - `PRODUCTION_BROWSER_CHECK_CRAWL_<ROLE>.json` (örn: `PRODUCTION_BROWSER_CHECK_CRAWL_ADMIN.json`)
  - `PRODUCTION_BROWSER_CHECK_CRAWL_<ROLE>.md`

- **Config:**
  - `CRAWL_ROLE_KEY` env var (default: 'admin')
  - `PROD_BASE_URL` env var
  - `START_PATH` env var (default: role-specific)
  - `MAX_DEPTH` env var (default: 3)
  - `MAX_PAGES` env var (default: 150)
  - Role credentials: `CRAWL_ADMIN_USERNAME`, `CRAWL_ADMIN_PASSWORD`, vb.

#### Multi-Role Crawl Orchestrator
- **Dosyalar:**
  - `scripts/check-prod-browser-crawl-roles.ts` - Multi-role crawl orchestrator

- **Hedef Environment:** Production (`PROD_BASE_URL` env var)
- **Kullanım Şekli:** npm script (`check:prod:browser:crawl:roles`)
- **Coverage:** Multiple roles (admin, ops, mgmt) sequential crawl
- **Özellikler:**
  - `CRAWL_ROLES` env var ile role listesi (örn: "admin,ops,mgmt")
  - Her role için ayrı crawl çalıştırıyor
  - Her role için ayrı rapor üretiyor

#### PowerShell Wrapper
- **Dosyalar:**
  - `scripts/run-prod-crawl.ps1` - PowerShell wrapper for recursive crawl

- **Hedef Environment:** Production (local QA only)
- **Kullanım Şekli:** PowerShell script (`.\\scripts\\run-prod-crawl.ps1`)
- **Coverage:** Recursive crawl with PowerShell parameter support
- **Özellikler:**
  - Parameters: `-BaseUrl`, `-StartPath`, `-MaxDepth`, `-MaxPages`, `-Roles`
  - Environment variable'ları set ediyor
  - `npm run check:prod:browser:crawl:roles` çağırıyor

---

### 3️⃣ PERF / LIGHTHOUSE / METRİKLER

**Açıklama:** Lighthouse CI entegrasyonu ve performance metrikleri.

#### Lighthouse CI
- **Dosyalar:**
  - `lighthouserc.json` - Lighthouse CI yapılandırması

- **Hedef Environment:** Local (default: `http://localhost/app`)
- **Kullanım Şekli:** npm script (`test:perf:lighthouse:local`, `test:perf:lighthouse:ci`)
- **Coverage:** 4 URL (login, dashboard, units, management-fees)
- **Özellikler:**
  - Desktop preset
  - 3 run (numberOfRuns: 3)
  - Assertions:
    - Performance: minScore 0.70 (error)
    - Accessibility: minScore 0.90 (error)
    - Best Practices: minScore 0.80 (error)
    - SEO: minScore 0.70 (warn)
    - Core Web Vitals: FCP, LCP, CLS, TBT, TTI
    - Resource size warnings
  - Upload target: filesystem (`./lhci-report`)

- **Tool:** `@lhci/cli` (devDependency)

#### Lighthouse CLI (Manual)
- **Dosyalar:**
  - `scripts/run_lighthouse.ps1` - PowerShell script for Lighthouse CLI

- **Hedef Environment:** Local (default: `https://kuretemizlik.local/app`)
- **Kullanım Şekli:** PowerShell script (`.\\scripts\\run_lighthouse.ps1`)
- **Coverage:** Single URL (parameter ile)
- **Özellikler:**
  - Parameters: `-Url`, `-Preset` (desktop/mobile), `-OutputPrefix`
  - JSON + HTML output
  - Timestamp-based output file names

#### Lighthouse Rapor Analizi
- **Dosyalar:**
  - `scripts/analyze_lighthouse.js` - Lighthouse rapor analiz script'i

- **Hedef Environment:** Local (post-run analysis)
- **Kullanım Şekli:** Node.js script (`node scripts/analyze_lighthouse.js <report.json> [limit]`)
- **Coverage:** Lighthouse JSON raporu analizi
- **Özellikler:**
  - Scores gösterimi
  - Top issues listesi (score < 1)
  - Limit parametresi (default: 20)

#### Raporlar
- **Dosyalar:**
  - `LIGHTHOUSE_PERFORMANCE_REPORT.md` - Lighthouse performance raporu
  - `LIGHTHOUSE_PERFORMANCE_REPORT_V2.md` - Lighthouse performance raporu v2

---

### 4️⃣ CI/CD TEST JOB'LARI

**Açıklama:** CI/CD pipeline'ları ve otomatik test çalıştırma.

#### GitHub Actions Workflows
- **Dosyalar:**
  - `.github/workflows/` - ⚠️ **MEVCUT DEĞİL**

- **Durum:** ❌ CI/CD workflow'ları kurulmamış
- **Eksiklikler:**
  - Playwright test'lerin otomatik çalıştırılması yok
  - Production smoke test'lerin scheduled run'ı yok
  - Lighthouse CI'nin scheduled run'ı yok
  - Browser crawl'lerin scheduled run'ı yok

---

### 5️⃣ SECURITY / INFRA TARAMALARI

**Açıklama:** Security ve infrastructure tarama yapıları.

#### Dependency Vulnerability Scanning
- **Dosyalar:**
  - `SECURITY_DEPENDENCY_RISKS.md` - npm dependency vulnerability analizi

- **Hedef Environment:** Local
- **Kullanım Şekli:** Manual (`npm audit`)
- **Coverage:** npm dependencies (13 vulnerability: 5 low, 8 high - backlog'da)
- **Durum:** ⚠️ PENDING (KUREAPP_BACKLOG.md - S-01)

#### MFA Rollout
- **Dosyalar:**
  - `MFA_SETUP.md` - MFA rollout dokümantasyonu

- **Hedef Environment:** Production
- **Kullanım Şekli:** Manual (production rollout)
- **Coverage:** MFA skeleton hazır, production rollout bekliyor
- **Durum:** ⚠️ PENDING (KUREAPP_BACKLOG.md - S-02)

#### External Logging (Sentry/ELK)
- **Dosyalar:**
  - `EXTERNAL_LOGGING_SETUP.md` - Sentry/ELK entegrasyonu dokümantasyonu

- **Hedef Environment:** Production
- **Kullanım Şekli:** Manual (production setup)
- **Coverage:** External logging skeleton hazır, production setup bekliyor
- **Durum:** ⚠️ PENDING (KUREAPP_BACKLOG.md - S-03)

#### Security Ops Reports
- **Dosyalar:**
  - `SECURITY_OPS_ROUND5_SUMMARY.md` - Security ops round 5 özeti
  - `SECURITY_HARDENING_PLAN.md` - Security hardening planı

---

### 6️⃣ DİĞER "UNUTULMUŞ" YAPILAR

**Açıklama:** PHP test suite ve diğer test yapıları.

#### PHP Functional Tests
- **Dosyalar:**
  - `tests/functional/ApiFeatureTest.php`
  - `tests/functional/AuthSessionTest.php`
  - `tests/functional/HeaderSecurityTest.php`
  - `tests/functional/JobCustomerFinanceFlowTest.php`
  - `tests/functional/ManagementResidentsTest.php`
  - `tests/functional/PaymentTransactionTest.php`
  - `tests/functional/RbacAccessTest.php`
  - `tests/functional/ResidentPaymentTest.php`
  - `tests/functional/ResidentProfileTest.php`
  - `tests/functional/run_all.php` - Test runner

- **Hedef Environment:** Local (PHPUnit)
- **Kullanım Şekli:** PHP script (`php tests/functional/run_all.php`) veya PHPUnit (`vendor/bin/phpunit tests/functional/`)
- **Coverage:** API features, auth sessions, RBAC, payment transactions, resident flows
- **Durum:** ⚠️ npm script'lerinde referans yok

#### PHP Unit Tests
- **Dosyalar:**
  - `tests/unit/ContractTemplateSelectionTest.php`
  - `tests/unit/ControllerHelperTest.php`
  - `tests/unit/InputSanitizerTest.php`
  - `tests/unit/JobContractFlowTest.php`
  - `tests/unit/PortalLoginControllerTest.php`
  - `tests/unit/ResidentAuthValidationTest.php`
  - `tests/unit/ResidentContactVerificationServiceTest.php`
  - `tests/unit/ResidentNotificationPreferenceServiceTest.php`
  - `tests/unit/ResidentOtpServiceFlowTest.php`
  - `tests/unit/ResidentPortalMetricsCacheTest.php`
  - `tests/unit/ResidentPortalMetricsTest.php`
  - `tests/unit/ResidentUserLookupTest.php`
  - `tests/unit/ResponseFormatterTest.php`
  - `tests/unit/UtilsSanitizeTest.php`

- **Hedef Environment:** Local (PHPUnit)
- **Kullanım Şekli:** PHPUnit (`vendor/bin/phpunit tests/unit/`)
- **Coverage:** Controllers, services, repositories, utilities
- **Durum:** ⚠️ npm script'lerinde referans yok

#### PHP Integration Tests
- **Dosyalar:**
  - `tests/integration/ControllerIntegrationTest.php`

- **Hedef Environment:** Local (PHPUnit)
- **Kullanım Şekli:** PHPUnit (`vendor/bin/phpunit tests/integration/`)
- **Coverage:** Controller integration
- **Durum:** ⚠️ npm script'lerinde referans yok

#### PHP Security Tests
- **Dosyalar:**
  - `tests/security/CsrfProtectionTest.php`
  - `tests/security/SqlInjectionTest.php`
  - `tests/security/XssPreventionTest.php`
  - `tests/security/run_all.php` - Test runner

- **Hedef Environment:** Local (PHPUnit)
- **Kullanım Şekli:** PHPUnit (`vendor/bin/phpunit tests/security/`) veya PHP script (`php tests/security/run_all.php`)
- **Coverage:** CSRF, SQL Injection, XSS prevention
- **Durum:** ⚠️ npm script'lerinde referans yok

#### PHP Performance Tests
- **Dosyalar:**
  - `tests/performance/PerformanceTest.php`
  - `tests/performance/baseline_measurement.php`

- **Hedef Environment:** Local (PHPUnit)
- **Kullanım Şekli:** PHPUnit (`vendor/bin/phpunit tests/performance/`)
- **Coverage:** Performance benchmarks
- **Durum:** ⚠️ npm script'lerinde referans yok

#### PHP Test Runners
- **Dosyalar:**
  - `tests/run_all_tests.php` - Tüm PHP test'lerini çalıştıran runner

- **Hedef Environment:** Local
- **Kullanım Şekli:** PHP script (`php tests/run_all_tests.php`)
- **Coverage:** Functional, unit, integration, security, performance testleri
- **Durum:** ⚠️ npm script'lerinde referans yok

#### Test Data Endpoints
- **Dosyalar:**
  - `tests/seed.php` - Test data seed endpoint
  - `tests/cleanup.php` - Test data cleanup endpoint

- **Hedef Environment:** Local (test environment)
- **Kullanım Şekli:** HTTP endpoint (test data management)
- **Coverage:** Test data creation/cleanup

---

## 📊 ÖZET TABLO

| Aile | Dosya Sayısı | Environment | Kullanım Şekli | CI/CD Entegrasyonu |
|------|--------------|-------------|----------------|-------------------|
| **Playwright Test Suite** | 17 spec + 3 helper | Local (default), Prod (smoke) | npm scripts | ❌ YOK |
| **Browser Crawl Scripts** | 5 script | Production | npm scripts + PowerShell | ❌ YOK |
| **Lighthouse/Perf** | 3 script + 1 config | Local | npm scripts + PowerShell | ❌ YOK |
| **CI/CD Workflows** | 0 | - | - | ❌ YOK |
| **Security/Infra** | 4 doc | Production | Manual | ❌ YOK |
| **PHP Test Suite** | 30+ test file | Local | PHPUnit (manual) | ❌ YOK |

---

## 🔍 ÖNEMLİ BULGULAR

### ✅ GÜÇLÜ YÖNLER

1. **Kapsamlı Playwright Test Suite:** 17 spec dosyası, tüm major akışlar kapsanmış
2. **Gelişmiş Browser Crawl:** Role-aware, recursive BFS crawl, pattern extraction
3. **Lighthouse CI Entegrasyonu:** Assertion-based scoring, Core Web Vitals tracking
4. **Kapsamlı Dokümantasyon:** 50+ rapor dosyası, tüm round'lar dokümante edilmiş

### ⚠️ EKSİKLİKLER

1. **CI/CD Workflow Yok:** Hiçbir test otomatik çalışmıyor
2. **PHP Test Suite Entegrasyonu Yok:** PHPUnit testleri npm script'lerinde yok
3. **Scheduled Runs Yok:** Production smoke test'ler, Lighthouse CI, browser crawl'ler scheduled değil
4. **Cross-Browser Opt-In:** Firefox/WebKit testleri sadece `ENABLE_CROSS_BROWSER=1` ile çalışıyor

---

**STAGE 1 TAMAMLANDI** ✅

**Sonraki Adım:** STAGE 2 - Komut & Pipeline Envanteri


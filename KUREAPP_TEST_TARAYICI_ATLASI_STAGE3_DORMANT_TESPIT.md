# KUREAPP – TEST & TARAYICI ALTYAPISI KEŞFİ
## STAGE 3 – "UNUTULAN / DORMANT" YAPILARI TESPİT ET

**Tarih:** 2025-11-22  
**Round:** ROUND 31 - Global Test & Tarayıcı Altyapısı Keşfi  
**Durum:** ✅ STAGE 3 TAMAMLANDI

---

## 🔍 DORMANT YAPILAR ANALİZİ

### 1️⃣ TEST/SPEC DOSYALARI - NPM SCRIPT'LERİNDE KULLANILMAYANLAR

#### Playwright Test Spec Dosyaları

| Dosya | npm Script'te Var mı? | CI Workflow'da Var mı? | Durum |
|-------|----------------------|------------------------|-------|
| `tests/ui/auth.spec.ts` | ✅ `test:ui:gating:local` | ❌ | **AKTİF** |
| `tests/ui/dashboard.spec.ts` | ✅ `test:ui:smoke:cross` | ❌ | **AKTİF** |
| `tests/ui/units.spec.ts` | ✅ `test:ui` | ❌ | **AKTİF** |
| `tests/ui/finance.spec.ts` | ✅ `test:ui` | ❌ | **AKTİF** |
| `tests/ui/layout.spec.ts` | ✅ `test:ui` | ❌ | **AKTİF** |
| `tests/ui/edge-cases.spec.ts` | ✅ `test:ui` | ❌ | **AKTİF** |
| `tests/ui/visual-regression.spec.ts` | ✅ `test:ui:visual` | ❌ | **AKTİF** |
| `tests/ui/accessibility.spec.ts` | ✅ `test:ui:a11y` | ❌ | **AKTİF** |
| `tests/ui/e2e-flows.spec.ts` | ✅ `test:ui:e2e:flows`, `test:ui:gating:local` | ❌ | **AKTİF** |
| `tests/ui/e2e-finance.spec.ts` | ✅ `test:ui:e2e:finance`, `test:ui:gating:local` | ❌ | **AKTİF** |
| `tests/ui/e2e-multitenant.spec.ts` | ✅ `test:ui:e2e:multitenant`, `test:ui:gating:local` | ❌ | **AKTİF** |
| `tests/ui/e2e-security.spec.ts` | ✅ `test:ui:e2e`, `test:ui:gating:local` | ❌ | **AKTİF** |
| `tests/ui/prod-smoke.spec.ts` | ✅ `test:prod:smoke` | ❌ | **AKTİF** |
| `tests/ui/performance.spec.ts` | ✅ `test:perf` | ❌ | **AKTİF** |
| `tests/ui/calendar.spec.ts` | ✅ `test:ui` | ❌ | **AKTİF** |
| `tests/ui/management.spec.ts` | ✅ `test:ui` | ❌ | **AKTİF** |
| `tests/ui/login-recurring.spec.ts` | ✅ `test:ui` | ❌ | **AKTİF** |

**Sonuç:** Tüm Playwright test spec dosyaları npm script'lerinde kullanılıyor. **DORMANT YOK.**

---

### 2️⃣ SCRIPT'LER - NPM SCRIPT'LERİNDE KULLANILMAYANLAR

#### Browser Crawl Script'leri

| Script | npm Script'te Var mı? | CI Workflow'da Var mı? | Durum |
|--------|----------------------|------------------------|-------|
| `scripts/check-prod-browser.ts` | ✅ `check:prod:browser` | ❌ | **AKTİF** |
| `scripts/check-prod-browser-full.ts` | ✅ `check:prod:browser:full` | ❌ | **AKTİF** |
| `scripts/check-prod-browser-crawl.ts` | ✅ `check:prod:browser:crawl` | ❌ | **AKTİF** |
| `scripts/check-prod-browser-crawl-roles.ts` | ✅ `check:prod:browser:crawl:roles` | ❌ | **AKTİF** |
| `scripts/run-prod-crawl.ps1` | ❌ (PowerShell script, npm'de yok) | ❌ | **DORMANT** (PowerShell-only) |

**Sonuç:** `run-prod-crawl.ps1` PowerShell-only script, npm script'lerinde referans yok. **DORMANT.**

#### Lighthouse/Perf Script'leri

| Script | npm Script'te Var mı? | CI Workflow'da Var mı? | Durum |
|--------|----------------------|------------------------|-------|
| `scripts/run_lighthouse.ps1` | ❌ (PowerShell script, npm'de yok) | ❌ | **DORMANT** (PowerShell-only) |
| `scripts/analyze_lighthouse.js` | ❌ (Node.js script, npm'de yok) | ❌ | **DORMANT** (Manual-only) |

**Sonuç:** Lighthouse script'leri npm script'lerinde referans yok. **DORMANT.**

---

### 3️⃣ PHP TEST SUITE - TAMAMEN DORMANT

#### PHP Test Dosyaları

| Test Kategorisi | Dosya Sayısı | npm Script'te Var mı? | CI Workflow'da Var mı? | Durum |
|-----------------|--------------|----------------------|------------------------|-------|
| **Functional Tests** | 9 dosya | ❌ | ❌ | **DORMANT** |
| **Unit Tests** | 14 dosya | ❌ | ❌ | **DORMANT** |
| **Integration Tests** | 1 dosya | ❌ | ❌ | **DORMANT** |
| **Security Tests** | 3 dosya | ❌ | ❌ | **DORMANT** |
| **Performance Tests** | 1 dosya | ❌ | ❌ | **DORMANT** |
| **Test Runners** | 2 dosya (`run_all_tests.php`, `tests/functional/run_all.php`, `tests/security/run_all.php`) | ❌ | ❌ | **DORMANT** |

**Sonuç:** Tüm PHP test suite'i npm script'lerinde ve CI workflow'larında yok. **TAMAMEN DORMANT.**

**Dosyalar:**
- `tests/functional/*.php` (9 dosya)
- `tests/unit/*.php` (14 dosya)
- `tests/integration/*.php` (1 dosya)
- `tests/security/*.php` (3 dosya)
- `tests/performance/*.php` (1 dosya)
- `tests/run_all_tests.php`
- `tests/functional/run_all.php`
- `tests/security/run_all.php`

---

### 4️⃣ CONFIG DOSYALARI - KULLANILMAYANLAR

| Config Dosyası | npm Script'te Kullanılıyor mu? | CI Workflow'da Kullanılıyor mu? | Durum |
|----------------|--------------------------------|--------------------------------|-------|
| `playwright.config.ts` | ✅ Tüm Playwright test script'leri | ❌ | **AKTİF** |
| `lighthouserc.json` | ✅ `test:perf:lighthouse:local`, `test:perf:lighthouse:ci` | ❌ | **AKTİF** |
| `phpunit.xml.dist` (root'ta) | ❌ | ❌ | **DORMANT** (PHP test suite dormant) |

**Sonuç:** `phpunit.xml.dist` config dosyası kullanılmıyor (PHP test suite dormant). **DORMANT.**

---

### 5️⃣ BACKLOG/DOKÜMANTASYON - REFERANS EDİLMEYENLER

#### Rapor Dosyaları

**Durum:** Rapor dosyaları genellikle geçmiş round'ların çıktıları. Aktif kullanım yok, sadece dokümantasyon amaçlı.

**Dormant Rapor Dosyaları (50+ dosya):**
- `PLAYWRIGHT_QA_COMPLETE_REPORT.md`
- `PRODUCTION_ROUND31_DEFECTS_HARDENING_REPORT.md`
- `PRODUCTION_ROUND30_ROOT_CAUSE_HARDENING_REPORT.md`
- `PRODUCTION_CONSOLE_CRAWL_ROUND27_REPORT.md`
- `PRODUCTION_CONSOLE_CRAWL_ROUND25_REPORT.md`
- ... (ve diğer 45+ rapor dosyası)

**Sonuç:** Rapor dosyaları dokümantasyon amaçlı, aktif kullanım yok. **DORMANT (ama dokümantasyon için gerekli).**

---

### 6️⃣ BROWSER CRAWL - ROLE KULLANIMI

#### Role-Aware Crawl Kullanımı

| Role | Config Var mı? | npm Script'te Kullanılıyor mu? | CI Workflow'da Kullanılıyor mu? | Durum |
|------|----------------|--------------------------------|--------------------------------|-------|
| **admin** | ✅ `ROLE_CONFIGS.admin` | ✅ `check:prod:browser:crawl` (default) | ❌ | **AKTİF** |
| **ops** | ✅ `ROLE_CONFIGS.ops` | ⚠️ `check:prod:browser:crawl:roles` (CRAWL_ROLES env var ile) | ❌ | **DORMANT** (config var ama kullanılmıyor) |
| **mgmt** | ✅ `ROLE_CONFIGS.mgmt` | ⚠️ `check:prod:browser:crawl:roles` (CRAWL_ROLES env var ile) | ❌ | **DORMANT** (config var ama kullanılmıyor) |

**Sonuç:** `ops` ve `mgmt` rolleri için config var ama aktif kullanım yok. **DORMANT.**

**Neden Dormant:**
- `check:prod:browser:crawl` default olarak `admin` role kullanıyor
- `check:prod:browser:crawl:roles` multi-role crawl yapıyor ama `CRAWL_ROLES` env var ile manuel tetiklenmesi gerekiyor
- CI workflow yok, scheduled run yok
- Dokümantasyonda `ops` ve `mgmt` rolleri için örnek kullanım yok

---

### 7️⃣ CROSS-BROWSER TESTLER - OPT-IN DORMANT

#### Cross-Browser Test Kullanımı

| Browser | Config Var mı? | npm Script'te Var mı? | CI Workflow'da Var mı? | Durum |
|---------|----------------|----------------------|------------------------|-------|
| **Chromium** | ✅ `desktop-chromium`, `mobile-chromium` | ✅ Tüm test script'leri (default) | ❌ | **AKTİF** |
| **Firefox** | ✅ `desktop-firefox` (conditional, `ENABLE_CROSS_BROWSER=1`) | ✅ `test:ui:cross`, `test:ui:smoke:cross` | ❌ | **DORMANT** (opt-in gerektiriyor) |
| **WebKit** | ✅ `desktop-webkit` (conditional, `ENABLE_CROSS_BROWSER=1`) | ✅ `test:ui:cross`, `test:ui:smoke:cross` | ❌ | **DORMANT** (opt-in gerektiriyor) |

**Sonuç:** Firefox ve WebKit testleri opt-in gerektiriyor, muhtemelen nadiren kullanılıyor. **DORMANT.**

**Neden Dormant:**
- `ENABLE_CROSS_BROWSER=1` env var gerektiriyor
- Default olarak sadece Chromium çalışıyor
- CI workflow yok, scheduled run yok
- Dokümantasyonda cross-browser test kullanımı yok

---

## 📊 DORMANT YAPILAR ÖZET TABLOSU

| Kategori | Dormant Yapı | Neden Dormant | Öneri |
|----------|--------------|---------------|-------|
| **Script** | `scripts/run-prod-crawl.ps1` | PowerShell-only, npm script'lerinde referans yok | npm script'e ekle veya sil |
| **Script** | `scripts/run_lighthouse.ps1` | PowerShell-only, npm script'lerinde referans yok | npm script'e ekle veya sil |
| **Script** | `scripts/analyze_lighthouse.js` | Manual-only, npm script'lerinde referans yok | npm script'e ekle (`test:perf:lighthouse:analyze`) |
| **Test Suite** | PHP Test Suite (30+ dosya) | npm script'lerinde referans yok, CI workflow yok | npm script'leri ekle (`test:php`, `test:php:unit`, vb.) |
| **Config** | `phpunit.xml.dist` | PHP test suite dormant | PHP test suite aktifleştirilince kullanılacak |
| **Role** | `ops` role crawl | Config var ama kullanılmıyor | CI workflow'a ekle veya dokümantasyon ekle |
| **Role** | `mgmt` role crawl | Config var ama kullanılmıyor | CI workflow'a ekle veya dokümantasyon ekle |
| **Browser** | Firefox testleri | Opt-in gerektiriyor (`ENABLE_CROSS_BROWSER=1`) | CI workflow'a ekle veya default yap |
| **Browser** | WebKit testleri | Opt-in gerektiriyor (`ENABLE_CROSS_BROWSER=1`) | CI workflow'a ekle veya default yap |

---

## 🎯 AKTİF vs DORMANT ÖZET

### ✅ AKTİF YAPILAR

1. **Playwright Test Suite:** Tüm spec dosyaları npm script'lerinde kullanılıyor
2. **Browser Crawl Script'leri (TypeScript):** Tüm script'ler npm script'lerinde kullanılıyor
3. **Lighthouse CI:** npm script'lerinde kullanılıyor (`test:perf:lighthouse:local`, `test:perf:lighthouse:ci`)
4. **Admin Role Crawl:** Default olarak kullanılıyor

### ⚠️ DORMANT YAPILAR

1. **PHP Test Suite:** Tamamen dormant (30+ dosya)
2. **PowerShell Script'leri:** `run-prod-crawl.ps1`, `run_lighthouse.ps1` (npm script'lerinde yok)
3. **Lighthouse Analiz Script'i:** `analyze_lighthouse.js` (npm script'lerinde yok)
4. **Ops/Mgmt Role Crawl:** Config var ama kullanılmıyor
5. **Cross-Browser Testler (Firefox/WebKit):** Opt-in gerektiriyor, muhtemelen nadiren kullanılıyor
6. **CI/CD Workflows:** Hiçbir workflow yok (tüm test'ler manual)

---

## 💡 ÖNERİLER

### Silinmeli mi, Yeniden Canlandırılmalı mı?

| Dormant Yapı | Öneri | Gerekçe |
|--------------|-------|---------|
| `scripts/run-prod-crawl.ps1` | **Yeniden Canlandır** | PowerShell kullanıcıları için faydalı, npm script'e ekle |
| `scripts/run_lighthouse.ps1` | **Yeniden Canlandır** | Lighthouse CLI için faydalı, npm script'e ekle |
| `scripts/analyze_lighthouse.js` | **Yeniden Canlandır** | Lighthouse rapor analizi için faydalı, npm script'e ekle |
| **PHP Test Suite** | **Yeniden Canlandır** | Backend test coverage için kritik, npm script'leri ekle |
| `ops` role crawl | **Yeniden Canlandır** | Multi-role testing için önemli, CI workflow'a ekle |
| `mgmt` role crawl | **Yeniden Canlandır** | Multi-role testing için önemli, CI workflow'a ekle |
| Firefox/WebKit testleri | **Yeniden Canlandır** | Cross-browser compatibility için önemli, CI workflow'a ekle |
| **CI/CD Workflows** | **YENİDEN CANLANDIR (YENİ)** | Otomatik test çalıştırma için kritik, GitHub Actions workflow'ları ekle |

---

**STAGE 3 TAMAMLANDI** ✅

**Sonraki Adım:** STAGE 4 - Çalıştırılabilir Tarayıcı/Test Paketi Öner


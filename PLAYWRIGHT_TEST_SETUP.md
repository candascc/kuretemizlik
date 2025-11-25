# 🎭 Playwright UI Test Setup - Küre Temizlik

## 📋 Özet

Bu doküman, Küre Temizlik uygulaması için kurulan Playwright tabanlı UI test altyapısını açıklar.

## ✅ Oluşturulan Dosyalar

### 1. Yapılandırma
- **`playwright.config.ts`** - Playwright ana yapılandırma dosyası
  - Base URL: `http://localhost/app` (env ile override edilebilir)
  - 4 viewport projesi: mobile, tablet, desktop, desktop-large
  - HTML, JSON ve list reporter'lar

### 2. Test Dosyaları
- **`tests/ui/auth.spec.ts`** - Authentication flow testleri
- **`tests/ui/dashboard.spec.ts`** - Dashboard & KPI kartları testleri
- **`tests/ui/units.spec.ts`** - Units list/detail sayfa testleri
- **`tests/ui/finance.spec.ts`** - Finance form testleri
- **`tests/ui/layout.spec.ts`** - Navbar & Footer testleri
- **`tests/ui/edge-cases.spec.ts`** - Edge case senaryoları

### 3. Helper Fonksiyonlar
- **`tests/ui/helpers/auth.ts`** - Login/logout helper'ları
- **`tests/ui/helpers/viewport.ts`** - Viewport ve layout helper'ları

### 4. Dokümantasyon
- **`tests/ui/README.md`** - Test suite dokümantasyonu
- **`PLAYWRIGHT_TEST_SETUP.md`** - Bu dosya

## 🚀 Kurulum Adımları

### 1. Bağımlılıkları Yükle

```bash
cd /path/to/app
npm install
```

Bu komut `package.json`'a eklenen şu bağımlılıkları yükler:
- `@playwright/test` - Playwright test framework
- `@types/node` - TypeScript type definitions
- `typescript` - TypeScript compiler

### 2. Playwright Browser'ları Yükle

```bash
npx playwright install chromium
```

Opsiyonel (cross-browser test için):
```bash
npx playwright install --with-deps
```

### 3. Environment Variables Ayarla

`.env` dosyasına veya test ortamına ekle:

```bash
BASE_URL=http://localhost/app
TEST_ADMIN_EMAIL=admin@test.com
TEST_ADMIN_PASSWORD=admin123
TEST_RESIDENT_PHONE=5551234567
```

## 🏃 Test Çalıştırma

### Temel Komutlar

```bash
# Tüm testleri çalıştır
npm run test:ui

# Headed mode (browser görünür - debug için)
npm run test:ui:headed

# Sadece mobile testleri
npm run test:ui:mobile

# Sadece desktop testleri
npm run test:ui:desktop

# HTML raporu görüntüle
npm run test:ui:report
```

### Gelişmiş Kullanım

```bash
# Belirli bir test dosyası
npx playwright test dashboard.spec.ts

# Belirli bir test case
npx playwright test dashboard.spec.ts -g "should display single column"

# Debug mode (step-by-step)
npx playwright test --debug

# UI mode (interactive)
npx playwright test --ui
```

## 📊 Test Kapsamı Detayları

### Authentication Tests (`auth.spec.ts`)
- ✅ Login form layout (mobile & desktop)
- ✅ Form validation (required fields)
- ✅ Error handling (invalid credentials)
- ✅ Touch targets (44px minimum)
- ✅ Font-size kontrolü (mobile min 14px)
- ✅ Resident login (phone-based)

### Dashboard Tests (`dashboard.spec.ts`)
- ✅ KPI kartları grid responsive:
  - Mobile: 1 kolon (`grid-cols-1`)
  - Tablet: 2 kolon (`sm:grid-cols-2`)
  - Desktop: 4 kolon (`lg:grid-cols-4`)
- ✅ Fluid typography kontrolü (h1, body)
- ✅ Line-height kontrolü (1.6 standardı)
- ✅ Card spacing (p-4 mobile, p-6 desktop)
- ✅ Touch targets (44px minimum)
- ✅ Horizontal scroll kontrolü (tüm viewport'larda)
- ✅ Container max-width kontrolü

### Units Tests (`units.spec.ts`)
- ✅ Liste sayfası:
  - Mobile: table-to-cards dönüşümü
  - Desktop: normal table görünümü
- ✅ Detail sayfa:
  - Layout ve spacing
  - Text truncation
  - Grid layout standardizasyonu (sm:/lg: breakpoints)

### Finance Tests (`finance.spec.ts`)
- ✅ Form layout responsive
- ✅ Input field styling (border-radius, focus states)
- ✅ Validation feedback
- ✅ Submit button states (loading/disabled)
- ✅ Font-size kontrolü (mobile min 14px)

### Layout Tests (`layout.spec.ts`)
- ✅ Navbar:
  - Mobile menu toggle
  - Body scroll lock
  - Touch targets
- ✅ Footer:
  - Accordion behavior (mobile)
  - Grid layout (desktop)
  - Link spacing ve font-size
  - Touch targets
- ✅ Global:
  - Smooth scroll
  - Transitions

### Edge Cases Tests (`edge-cases.spec.ts`)
- ✅ Empty state displays (icon + message + CTA)
- ✅ Long text handling (word-break, truncation)
- ✅ Very small viewport (320px)
- ✅ Large viewport (1920px)
- ✅ Breakpoint geçişleri (639px, 640px, 1023px, 1024px)
- ✅ Turkish long words

## 🔧 Helper Fonksiyonlar

### Auth Helpers

```typescript
// Admin login
await loginAsAdmin(page, 'admin@test.com', 'password');

// Resident login (phone-based)
await loginAsResident(page, '5551234567');

// Logout
await logout(page);
```

### Viewport Helpers

```typescript
// Viewport resize
await resizeToMobile(page);    // 390x844
await resizeToTablet(page);    // 768x1024
await resizeToDesktop(page);   // 1280x720

// Layout checks
const hasScroll = await hasHorizontalScroll(page);
const columnCount = await getGridColumnCount(page, '.grid');
```

## 📈 Test İstatistikleri

**Toplam Test Dosyası:** 6  
**Toplam Test Case:** ~40+  
**Viewport Coverage:** 4 (mobile, tablet, desktop, large)  
**Browser:** Chromium (opsiyonel: WebKit, Firefox)

## 🎯 Test Senaryoları Özeti

| Senaryo | Dosya | Viewport | Assertion |
|---------|-------|----------|-----------|
| Login form layout | `auth.spec.ts` | Mobile, Desktop | No horizontal scroll, touch targets |
| KPI grid responsive | `dashboard.spec.ts` | All | Column count (1/2/4) |
| Table-to-cards | `units.spec.ts` | Mobile | Cards visible, table hidden |
| Footer accordion | `layout.spec.ts` | Mobile | Accordion opens/closes |
| Empty state | `edge-cases.spec.ts` | All | Icon + message + CTA |
| Long text | `edge-cases.spec.ts` | Mobile | No layout break, word-break |

## 🐛 Debugging

### Test Debug

```bash
# Debug mode
npx playwright test --debug

# Specific test
npx playwright test dashboard.spec.ts --debug

# UI mode (interactive)
npx playwright test --ui
```

### Screenshot & Video

Test başarısız olduğunda otomatik olarak:
- Screenshot alınır (`test-results/`)
- Video kaydedilir (`test-results/`)
- Trace dosyası oluşturulur (`test-results/`)

### Console Logs

```typescript
// Test içinde console.log
await page.evaluate(() => console.log('Debug info'));

// Network requests
page.on('request', request => console.log(request.url()));
```

## 🔮 Gelecek İyileştirmeler

### Kısa Vadeli
1. **Visual Regression Testing**
   - Percy veya Loki entegrasyonu
   - Component-level screenshot karşılaştırması

2. **Accessibility Testing**
   - axe-core entegrasyonu
   - WCAG 2.1 AA compliance kontrolü

3. **Performance Testing**
   - Lighthouse CI entegrasyonu
   - Core Web Vitals metrikleri

### Orta Vadeli
4. **Cross-Browser Testing**
   - WebKit (Safari) testleri
   - Firefox testleri

5. **E2E User Flows**
   - Tam kullanıcı akışları (create job → assign → complete)
   - Multi-step form testleri

6. **API + UI Integration**
   - Backend API mock'ları
   - Test data setup/teardown

## 📚 Referanslar

- **Design System:** `DESIGN_SYSTEM.md`
- **Responsive Audit:** `RESPONSIVE_UI_UX_AUDIT_REPORT.md`
- **Refactor Report:** `RESPONSIVE_REFACTOR_COMPLETE_REPORT.md`
- **Playwright Docs:** https://playwright.dev

## ⚠️ Önemli Notlar

1. **Base URL**: Testler `http://localhost/app` üzerinde çalışır. Production'da `BASE_URL` env variable'ı ile override edin.

2. **Login Credentials**: Test login için gerçek kullanıcı bilgileri gerekiyor. Test ortamında test kullanıcıları oluşturun.

3. **Test Data**: Bazı testler mevcut veriye bağımlı (örn: units list). Test ortamında seed data olmalı.

4. **CI/CD**: GitHub Actions veya benzeri CI/CD pipeline'ında testleri çalıştırmak için `playwright.config.ts` içindeki `webServer` ayarını aktif edin.

---

**Kurulum Tarihi:** 2025-01-XX  
**Test Framework:** Playwright 1.40+  
**Language:** TypeScript


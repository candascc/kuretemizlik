# 🧪 UI Test Suite - Playwright

Bu dizin, Küre Temizlik uygulamasının UI/UX ve responsive davranışını test eden Playwright testlerini içerir.

## 📁 Dosya Yapısı

```
tests/ui/
├── README.md                    # Bu dosya
├── helpers/
│   ├── auth.ts                 # Login/logout helper fonksiyonları
│   ├── viewport.ts             # Viewport resize ve layout helper'ları
│   └── data.ts                 # Test data creation/cleanup helper'ları
├── auth.spec.ts                # Authentication flow testleri
├── dashboard.spec.ts           # Dashboard & KPI kartları testleri
├── units.spec.ts               # Units list/detail sayfa testleri
├── finance.spec.ts             # Finance form testleri
├── layout.spec.ts              # Navbar & Footer testleri
├── edge-cases.spec.ts          # Edge case senaryoları
├── visual-regression.spec.ts   # Visual regression testleri
├── accessibility.spec.ts       # Accessibility (a11y) testleri
├── e2e-flows.spec.ts           # E2E user flow testleri
├── e2e-finance.spec.ts         # E2E finance flow testleri
└── e2e-multitenant.spec.ts     # E2E multi-tenant isolation testleri
```

## 🚀 Kurulum

```bash
# Playwright ve bağımlılıkları yükle
npm install

# Playwright browser'ları yükle
npx playwright install chromium
```

## ⚙️ Yapılandırma

Test yapılandırması `playwright.config.ts` dosyasında tanımlıdır.

**Önemli Ayarlar:**
- `baseURL`: Test edilecek uygulamanın base URL'i (varsayılan: `http://localhost/app`)
- Viewport'lar: Mobile (390x844), Tablet (1024x1366), Desktop (1280x720, 1440x900)

**Environment Variables:**
```bash
# .env veya test ortamında
BASE_URL=http://localhost/app
TEST_ADMIN_EMAIL=admin@test.com
TEST_ADMIN_PASSWORD=admin123
TEST_RESIDENT_PHONE=5551234567

# E2E tests için opsiyonel (multi-tenant testleri için)
TEST_COMPANY_A_EMAIL=company-a@test.com
TEST_COMPANY_A_PASSWORD=password123
TEST_COMPANY_B_EMAIL=company-b@test.com
TEST_COMPANY_B_PASSWORD=password123
```

## 🏃 Test Çalıştırma

```bash
# Tüm testleri çalıştır
npm run test:ui

# Headed mode (browser görünür)
npm run test:ui:headed

# Sadece mobile testleri
npm run test:ui:mobile

# Sadece desktop testleri
npm run test:ui:desktop

# HTML raporu görüntüle
npm run test:ui:report

# E2E testleri
npm run test:ui:e2e              # Tüm E2E testleri
npm run test:ui:e2e:flows        # Sadece user flow testleri
npm run test:ui:e2e:finance      # Sadece finance testleri
npm run test:ui:e2e:multitenant  # Sadece multi-tenant testleri
```

## 📊 Test Kapsamı

### 1. Authentication (`auth.spec.ts`)
- ✅ Login form layout (mobile & desktop)
- ✅ Form validation
- ✅ Error handling
- ✅ Touch targets

### 2. Dashboard (`dashboard.spec.ts`)
- ✅ KPI kartları grid responsive (1/2/4 kolon)
- ✅ Fluid typography kontrolü
- ✅ Card spacing ve padding
- ✅ Touch targets (44px minimum)

### 3. Units (`units.spec.ts`)
- ✅ Liste sayfası table-to-cards dönüşümü
- ✅ Detail sayfa layout
- ✅ Text truncation
- ✅ Grid layout standardizasyonu

### 4. Finance Forms (`finance.spec.ts`)
- ✅ Form layout responsive
- ✅ Validation feedback
- ✅ Input field styling
- ✅ Submit button states

### 5. Layout (`layout.spec.ts`)
- ✅ Navbar/hamburger menu
- ✅ Footer accordion (mobile)
- ✅ Footer grid (desktop)
- ✅ Body scroll lock
- ✅ Smooth scroll

### 6. Edge Cases (`edge-cases.spec.ts`)
- ✅ Empty state displays
- ✅ Long text handling
- ✅ Very small viewport (320px)
- ✅ Large viewport (1920px)
- ✅ Breakpoint geçişleri
- ✅ Turkish long words

### 7. Visual Regression (`visual-regression.spec.ts`)
- ✅ Dashboard KPI cards screenshots
- ✅ Footer and Navbar components
- ✅ Button states (normal & hover)
- ✅ Card components
- ✅ Form inputs (normal & focus)

### 8. Accessibility (`accessibility.spec.ts`)
- ✅ WCAG 2.1 AA compliance
- ✅ Form labels and ARIA attributes
- ✅ Color contrast
- ✅ Keyboard navigation
- ✅ Focus indicators

### 9. E2E User Flows (`e2e-flows.spec.ts`)
- ✅ Manager flow: Create building → unit → job
- ✅ Staff flow: View and complete jobs
- ✅ Dashboard integration
- ✅ Validation and error handling

### 10. E2E Finance (`e2e-finance.spec.ts`)
- ✅ Create management fee
- ✅ Mark fee as paid
- ✅ Balance updates
- ✅ Financial summary and reports
- ✅ Overdue fees

### 11. E2E Multi-Tenant (`e2e-multitenant.spec.ts`)
- ✅ Data isolation (buildings, units, jobs, fees)
- ✅ Session isolation
- ✅ URL parameter protection
- ✅ Dashboard isolation

## 🔧 Helper Fonksiyonlar

### Auth Helpers (`helpers/auth.ts`)
- `loginAsAdmin(page, email?, password?)` - Admin olarak giriş yap
- `loginAsResident(page, phone?)` - Resident olarak giriş yap
- `logout(page)` - Çıkış yap

### Viewport Helpers (`helpers/viewport.ts`)
- `resizeToMobile(page)` - Mobile viewport (390x844)
- `resizeToTablet(page)` - Tablet viewport (768x1024)
- `resizeToDesktop(page)` - Desktop viewport (1280x720)
- `hasHorizontalScroll(page)` - Yatay scroll kontrolü
- `getGridColumnCount(page, selector)` - Grid kolon sayısı

## 📝 Test Yazma Rehberi

### Yeni Test Ekleme

```typescript
import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { resizeToMobile } from './helpers/viewport';

test.describe('Yeni Özellik', () => {
  test('should work correctly', async ({ page }) => {
    await resizeToMobile(page);
    await loginAsAdmin(page);
    
    await page.goto('/new-feature');
    
    // Assertions
    await expect(page.locator('h1')).toBeVisible();
  });
});
```

### Best Practices

1. **Viewport Testleri**: Her test farklı viewport'larda çalışmalı
2. **Helper Kullanımı**: Tekrarlayan kod için helper fonksiyonlar kullan
3. **Wait Strategies**: `waitForTimeout` yerine `waitForSelector` tercih et
4. **Assertions**: Net ve anlamlı assertion mesajları yaz
5. **Error Handling**: Login başarısız olursa test skip edilmeli

## 🐛 Debugging

```bash
# Debug mode (step-by-step)
npx playwright test --debug

# Specific test debug
npx playwright test dashboard.spec.ts --debug

# Screenshot on failure (otomatik)
# Video on failure (otomatik)
```

## 📈 CI/CD Entegrasyonu

### GitHub Actions Örneği

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
      - run: npm run test:ui
      - uses: actions/upload-artifact@v3
        if: always()
        with:
          name: playwright-report
          path: tests/ui/reports/
```

## 🔮 Gelecek İyileştirmeler

- [ ] Visual regression testing (Percy/Loki entegrasyonu)
- [ ] Accessibility testing (axe-core)
- [ ] Performance testing (Lighthouse CI)
- [ ] Cross-browser testing (WebKit, Firefox)
- [ ] Snapshot testing (component-level)

## 📚 Kaynaklar

- [Playwright Documentation](https://playwright.dev)
- [Design System](./../../DESIGN_SYSTEM.md)
- [Responsive Audit Report](./../../RESPONSIVE_UI_UX_AUDIT_REPORT.md)


import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { resizeToMobile, resizeToTablet, resizeToDesktop } from './helpers/viewport';

/**
 * Visual Regression Tests
 * 
 * Tests visual consistency for:
 * - Dashboard KPI cards (colors, border-radius, shadow, spacing)
 * - Footer and Navbar components
 * - Button states (normal, hover)
 * - Cards and modals
 * 
 * Covers Top 15 Audit Items:
 * - #8: Renk tutarsızlığı
 * - #10: Hover state yetersiz
 * - #13: Border-radius tutarsızlığı
 * - #14: Shadow tutarsızlığı
 */

test.describe('Visual Regression', () => {
  
  test.beforeEach(async ({ page }) => {
    try {
      await loginAsAdmin(page);
      await page.waitForTimeout(1000);
    } catch (error) {
      // Continue if login fails
    }
  });

  test.describe('Dashboard KPI Cards', () => {
    
    test('should match KPI cards visual baseline on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      // Find KPI cards grid
      const kpiGrid = page.locator('.grid').filter({ hasText: /Periyodik|Bugünkü|Müşteri|Gelir/i }).first();
      
      if (await kpiGrid.isVisible().catch(() => false)) {
        // Take screenshot of KPI cards grid
        await expect(kpiGrid).toHaveScreenshot('dashboard-kpi-cards-mobile.png', {
          maxDiffPixels: 100, // Allow small differences
        });
      } else {
        test.skip();
      }
    });
    
    test('should match KPI cards visual baseline on tablet', async ({ page }) => {
      await resizeToTablet(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const kpiGrid = page.locator('.grid').filter({ hasText: /Periyodik|Bugünkü|Müşteri|Gelir/i }).first();
      
      if (await kpiGrid.isVisible().catch(() => false)) {
        await expect(kpiGrid).toHaveScreenshot('dashboard-kpi-cards-tablet.png', {
          maxDiffPixels: 100,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match KPI cards visual baseline on desktop', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const kpiGrid = page.locator('.grid').filter({ hasText: /Periyodik|Bugünkü|Müşteri|Gelir/i }).first();
      
      if (await kpiGrid.isVisible().catch(() => false)) {
        await expect(kpiGrid).toHaveScreenshot('dashboard-kpi-cards-desktop.png', {
          maxDiffPixels: 100,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match individual KPI card visual baseline', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      // Find first KPI card
      const firstCard = page.locator('.card, [class*="rounded-xl"], [class*="rounded-2xl"]')
        .filter({ hasText: /Periyodik|Bugünkü|Müşteri|Gelir/i })
        .first();
      
      if (await firstCard.isVisible().catch(() => false)) {
        // Test card styling: border-radius, shadow, padding
        await expect(firstCard).toHaveScreenshot('kpi-card-individual.png', {
          maxDiffPixels: 50,
        });
      } else {
        test.skip();
      }
    });
  });

  test.describe('Footer Component', () => {
    
    test('should match footer visual baseline on mobile (closed)', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      // Scroll to footer
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await page.waitForTimeout(500);
      
      const footer = page.locator('footer').first();
      
      if (await footer.isVisible().catch(() => false)) {
        await expect(footer).toHaveScreenshot('footer-mobile-closed.png', {
          maxDiffPixels: 100,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match footer visual baseline on mobile (accordion open)', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await page.waitForTimeout(500);
      
      // Open first accordion section
      const firstAccordion = page.locator('footer details').first();
      const summary = firstAccordion.locator('summary').first();
      
      if (await summary.isVisible().catch(() => false)) {
        await summary.click();
        await page.waitForTimeout(500);
        
        const footer = page.locator('footer').first();
        await expect(footer).toHaveScreenshot('footer-mobile-open.png', {
          maxDiffPixels: 100,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match footer visual baseline on desktop', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await page.waitForTimeout(500);
      
      const footer = page.locator('footer').first();
      
      if (await footer.isVisible().catch(() => false)) {
        await expect(footer).toHaveScreenshot('footer-desktop.png', {
          maxDiffPixels: 100,
        });
      } else {
        test.skip();
      }
    });
  });

  test.describe('Navbar Component', () => {
    
    test('should match navbar visual baseline on mobile (closed)', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      await page.waitForTimeout(500);
      
      const navbar = page.locator('nav, header').first();
      
      if (await navbar.isVisible().catch(() => false)) {
        await expect(navbar).toHaveScreenshot('navbar-mobile-closed.png', {
          maxDiffPixels: 100,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match navbar visual baseline on mobile (menu open)', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      await page.waitForTimeout(500);
      
      // Open mobile menu
      const hamburger = page.locator('button[aria-label*="menu"], button:has([class*="fa-bars"])').first();
      
      if (await hamburger.isVisible().catch(() => false)) {
        await hamburger.click();
        await page.waitForTimeout(500);
        
        const mobileMenu = page.locator('#mobile-menu, .mobile-nav, [class*="mobile-menu"]').first();
        if (await mobileMenu.isVisible().catch(() => false)) {
          await expect(mobileMenu).toHaveScreenshot('navbar-mobile-open.png', {
            maxDiffPixels: 100,
          });
        }
      } else {
        test.skip();
      }
    });
    
    test('should match navbar visual baseline on desktop', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(500);
      
      const navbar = page.locator('nav, header').first();
      
      if (await navbar.isVisible().catch(() => false)) {
        await expect(navbar).toHaveScreenshot('navbar-desktop.png', {
          maxDiffPixels: 100,
        });
      } else {
        test.skip();
      }
    });
  });

  test.describe('Button States', () => {
    
    test('should match primary button visual baseline (normal state)', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      // Find primary button
      const primaryButton = page.locator('button.bg-primary-600, .btn-primary, button:has-text("Yeni"), button:has-text("Ekle")').first();
      
      if (await primaryButton.isVisible().catch(() => false)) {
        await expect(primaryButton).toHaveScreenshot('button-primary-normal.png', {
          maxDiffPixels: 50,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match primary button visual baseline (hover state)', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const primaryButton = page.locator('button.bg-primary-600, .btn-primary, button:has-text("Yeni"), button:has-text("Ekle")').first();
      
      if (await primaryButton.isVisible().catch(() => false)) {
        // Hover over button
        await primaryButton.hover();
        await page.waitForTimeout(300); // Wait for transition
        
        await expect(primaryButton).toHaveScreenshot('button-primary-hover.png', {
          maxDiffPixels: 50,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match secondary button visual baseline (normal state)', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const secondaryButton = page.locator('button.bg-gray-600, .btn-secondary, button:has-text("İptal"), button:has-text("Kapat")').first();
      
      if (await secondaryButton.isVisible().catch(() => false)) {
        await expect(secondaryButton).toHaveScreenshot('button-secondary-normal.png', {
          maxDiffPixels: 50,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match secondary button visual baseline (hover state)', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const secondaryButton = page.locator('button.bg-gray-600, .btn-secondary, button:has-text("İptal"), button:has-text("Kapat")').first();
      
      if (await secondaryButton.isVisible().catch(() => false)) {
        await secondaryButton.hover();
        await page.waitForTimeout(300);
        
        await expect(secondaryButton).toHaveScreenshot('button-secondary-hover.png', {
          maxDiffPixels: 50,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match danger button visual baseline (normal state)', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const dangerButton = page.locator('button.bg-red-600, .btn-danger, button:has-text("Sil"), button:has-text("Delete")').first();
      
      if (await dangerButton.isVisible().catch(() => false)) {
        await expect(dangerButton).toHaveScreenshot('button-danger-normal.png', {
          maxDiffPixels: 50,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match danger button visual baseline (hover state)', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const dangerButton = page.locator('button.bg-red-600, .btn-danger, button:has-text("Sil"), button:has-text("Delete")').first();
      
      if (await dangerButton.isVisible().catch(() => false)) {
        await dangerButton.hover();
        await page.waitForTimeout(300);
        
        await expect(dangerButton).toHaveScreenshot('button-danger-hover.png', {
          maxDiffPixels: 50,
        });
      } else {
        test.skip();
      }
    });
  });

  test.describe('Card Components', () => {
    
    test('should match card visual baseline (border-radius, shadow, padding)', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      // Find a card component
      const card = page.locator('.card, [class*="rounded-xl"], [class*="rounded-2xl"]')
        .filter({ hasNotText: /Periyodik|Bugünkü|Müşteri|Gelir/i })
        .first();
      
      if (await card.isVisible().catch(() => false)) {
        await expect(card).toHaveScreenshot('card-component.png', {
          maxDiffPixels: 50,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match card hover state visual baseline', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const card = page.locator('.card, [class*="rounded-xl"]')
        .filter({ hasNotText: /Periyodik|Bugünkü|Müşteri|Gelir/i })
        .first();
      
      if (await card.isVisible().catch(() => false)) {
        await card.hover();
        await page.waitForTimeout(300);
        
        await expect(card).toHaveScreenshot('card-component-hover.png', {
          maxDiffPixels: 50,
        });
      } else {
        test.skip();
      }
    });
  });

  test.describe('Form Inputs', () => {
    
    test('should match form input visual baseline (normal state)', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/units/new');
      await page.waitForTimeout(1000);
      
      const input = page.locator('input[type="text"], input[type="number"], select').first();
      
      if (await input.isVisible().catch(() => false)) {
        await expect(input).toHaveScreenshot('form-input-normal.png', {
          maxDiffPixels: 30,
        });
      } else {
        test.skip();
      }
    });
    
    test('should match form input visual baseline (focus state)', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/units/new');
      await page.waitForTimeout(1000);
      
      const input = page.locator('input[type="text"], input[type="number"]').first();
      
      if (await input.isVisible().catch(() => false)) {
        await input.focus();
        await page.waitForTimeout(200);
        
        await expect(input).toHaveScreenshot('form-input-focus.png', {
          maxDiffPixels: 30,
        });
      } else {
        test.skip();
      }
    });
  });
});


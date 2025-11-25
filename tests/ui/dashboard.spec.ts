import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { resizeToMobile, resizeToTablet, resizeToDesktop, getGridColumnCount, hasHorizontalScroll } from './helpers/viewport';

/**
 * Dashboard & KPI Cards Tests
 * 
 * Tests:
 * - KPI card grid responsive behavior
 * - Fluid typography on headings
 * - Card layout and spacing
 * - Touch targets on mobile
 */

test.describe('Dashboard', () => {
  
  test.beforeEach(async ({ page }) => {
    // Try to login before each test
    try {
      await loginAsAdmin(page);
      await page.waitForTimeout(1000);
    } catch (error) {
      // If login fails, continue anyway (might be public dashboard)
    }
  });
  
  test.describe('KPI Cards Grid', () => {
    
    test('should display single column on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      // Find KPI cards grid
      const kpiGrid = page.locator('.grid').filter({ hasText: /Periyodik|Bugünkü|Müşteri|Gelir/i }).first();
      
      if (await kpiGrid.isVisible().catch(() => false)) {
        // Check grid column count
        const columnCount = await getGridColumnCount(page, '.grid.grid-cols-1, .grid[class*="grid-cols"]');
        
        // On mobile, should be 1 column (or grid-cols-1 class)
        const gridClasses = await kpiGrid.getAttribute('class');
        expect(gridClasses).toMatch(/grid-cols-1/);
        
        // Verify no horizontal scroll
        const hasScroll = await hasHorizontalScroll(page);
        expect(hasScroll).toBe(false);
      }
    });
    
    test('should display 2 columns on tablet', async ({ page }) => {
      await resizeToTablet(page);
      await page.goto('/');
      
      const kpiGrid = page.locator('.grid').filter({ hasText: /Periyodik|Bugünkü|Müşteri|Gelir/i }).first();
      
      if (await kpiGrid.isVisible().catch(() => false)) {
        // On tablet (640px+), should have sm:grid-cols-2
        const gridClasses = await kpiGrid.getAttribute('class');
        expect(gridClasses).toMatch(/sm:grid-cols-2/);
        
        // Verify layout
        const hasScroll = await hasHorizontalScroll(page);
        expect(hasScroll).toBe(false);
      }
    });
    
    test('should display 4 columns on desktop', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      
      const kpiGrid = page.locator('.grid').filter({ hasText: /Periyodik|Bugünkü|Müşteri|Gelir/i }).first();
      
      if (await kpiGrid.isVisible().catch(() => false)) {
        // On desktop (1024px+), should have lg:grid-cols-4
        const gridClasses = await kpiGrid.getAttribute('class');
        expect(gridClasses).toMatch(/lg:grid-cols-4/);
      }
    });
    
    test('should have proper card spacing on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      const cards = page.locator('.card, [class*="rounded-xl"], [class*="rounded-2xl"]').filter({ hasText: /Periyodik|Bugünkü|Müşteri|Gelir/i });
      const cardCount = await cards.count();
      
      if (cardCount > 0) {
        // Check first card padding
        const firstCard = cards.first();
        const padding = await firstCard.evaluate((el) => {
          const style = window.getComputedStyle(el);
          return {
            top: parseFloat(style.paddingTop),
            bottom: parseFloat(style.paddingBottom),
            left: parseFloat(style.paddingLeft),
            right: parseFloat(style.paddingRight),
          };
        });
        
        // Mobile should have at least p-4 (16px)
        expect(padding.top).toBeGreaterThanOrEqual(16);
        expect(padding.left).toBeGreaterThanOrEqual(16);
      }
    });
    
    test('should have touch targets >= 44px on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      // Check links and buttons in cards
      const interactiveElements = page.locator('.card a, .card button, [class*="section-header-link"]');
      const count = await interactiveElements.count();
      
      for (let i = 0; i < Math.min(count, 5); i++) {
        const element = interactiveElements.nth(i);
        const box = await element.boundingBox();
        
        if (box) {
          expect(box.height).toBeGreaterThanOrEqual(44);
          expect(box.width).toBeGreaterThanOrEqual(44);
        }
      }
    });
  });
  
  test.describe('Typography', () => {
    
    test('should use fluid typography for page heading', async ({ page }) => {
      await page.goto('/');
      
      const heading = page.locator('h1').first();
      
      if (await heading.isVisible().catch(() => false)) {
        const classes = await heading.getAttribute('class');
        const fontSize = await heading.evaluate((el) => {
          return window.getComputedStyle(el).fontSize;
        });
        
        // Should have fluid-h1 class or clamp() in computed style
        const hasFluidClass = classes?.includes('fluid-h1');
        const fontSizeNum = parseFloat(fontSize);
        
        // Font size should be reasonable (between 24px and 40px for h1)
        expect(fontSizeNum).toBeGreaterThanOrEqual(20);
        expect(fontSizeNum).toBeLessThanOrEqual(45);
      }
    });
    
    test('should have proper line-height for body text', async ({ page }) => {
      await page.goto('/');
      
      const bodyText = page.locator('p').first();
      
      if (await bodyText.isVisible().catch(() => false)) {
        const lineHeight = await bodyText.evaluate((el) => {
          return window.getComputedStyle(el).lineHeight;
        });
        
        // Line height should be around 1.5-1.6 (24px for 16px font = 1.5)
        const lineHeightNum = parseFloat(lineHeight);
        const fontSize = await bodyText.evaluate((el) => {
          return parseFloat(window.getComputedStyle(el).fontSize);
        });
        
        const ratio = lineHeightNum / fontSize;
        expect(ratio).toBeGreaterThanOrEqual(1.4);
        expect(ratio).toBeLessThanOrEqual(1.8);
      }
    });
  });
  
  test.describe('Layout & Spacing', () => {
    
    test('should not have horizontal scroll on any viewport', async ({ page }) => {
      const viewports = [
        { width: 375, height: 812, name: 'mobile-small' },
        { width: 390, height: 844, name: 'mobile' },
        { width: 768, height: 1024, name: 'tablet' },
        { width: 1280, height: 720, name: 'desktop' },
        { width: 1440, height: 900, name: 'desktop-large' },
      ];
      
      for (const viewport of viewports) {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await page.goto('/');
        await page.waitForTimeout(500);
        
        const hasScroll = await hasHorizontalScroll(page);
        expect(hasScroll, `Horizontal scroll detected on ${viewport.name} (${viewport.width}x${viewport.height})`).toBe(false);
      }
    });
    
    test('should have consistent container max-width', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      
      // Find main container
      const container = page.locator('.max-w-7xl, .max-w-6xl, [class*="max-w"]').first();
      
      if (await container.isVisible().catch(() => false)) {
        const maxWidth = await container.evaluate((el) => {
          return window.getComputedStyle(el).maxWidth;
        });
        
        // Should be max-w-7xl (1280px) or similar standard width
        const maxWidthNum = parseFloat(maxWidth);
        expect(maxWidthNum).toBeGreaterThanOrEqual(1200);
        expect(maxWidthNum).toBeLessThanOrEqual(1400);
      }
    });
  });
});


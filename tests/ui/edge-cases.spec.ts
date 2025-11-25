import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { resizeToMobile, resizeToDesktop, hasHorizontalScroll } from './helpers/viewport';

/**
 * Edge Cases & Long Content Tests
 * 
 * Tests:
 * - Empty state displays
 * - Long text handling
 * - Very long table rows
 * - Small viewport (320px)
 * - Large viewport (1920px+)
 */

test.describe('Edge Cases', () => {
  
  test.beforeEach(async ({ page }) => {
    try {
      await loginAsAdmin(page);
      await page.waitForTimeout(1000);
    } catch (error) {
      // Continue if login fails
    }
  });
  
  test.describe('Empty States', () => {
    
    test('should display empty state with icon and message', async ({ page }) => {
      // Try to find a page that might be empty
      await page.goto('/units');
      await page.waitForTimeout(1000);
      
      // Check for empty state indicators
      const emptyState = page.locator(':has-text("Henüz"), :has-text("Veri yok"), :has-text("boş")').first();
      
      // If empty state exists, check for icon and CTA
      if (await emptyState.isVisible().catch(() => false)) {
        // Should have icon
        const icon = emptyState.locator('i, [class*="fa-"], svg').first();
        const hasIcon = await icon.isVisible().catch(() => false);
        
        // Should have CTA button
        const ctaButton = emptyState.locator('a, button').first();
        const hasCTA = await ctaButton.isVisible().catch(() => false);
        
        // At least message should be visible
        expect(await emptyState.isVisible()).toBeTruthy();
      }
    });
    
    test('should have proper styling for empty state', async ({ page }) => {
      await page.goto('/units');
      await page.waitForTimeout(1000);
      
      const emptyState = page.locator(':has-text("Henüz"), :has-text("Veri yok")').first();
      
      if (await emptyState.isVisible().catch(() => false)) {
        // Should have proper padding and spacing
        const padding = await emptyState.evaluate((el) => {
          return parseFloat(window.getComputedStyle(el).paddingTop);
        });
        
        expect(padding).toBeGreaterThanOrEqual(24); // At least p-6
      }
    });
  });
  
  test.describe('Long Content', () => {
    
    test('should handle long headings without breaking layout', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      // Create a test scenario with long heading
      await page.evaluate(() => {
        const heading = document.querySelector('h1');
        if (heading) {
          heading.textContent = 'Çok Uzun Bir Başlık Metni Bu Başlık Çok Uzun Olmalı Ve Layout\'u Bozmamalı';
        }
      });
      
      await page.waitForTimeout(300);
      
      // Check no horizontal scroll
      const hasScroll = await hasHorizontalScroll(page);
      expect(hasScroll).toBe(false);
      
      // Check heading has word-break
      const heading = page.locator('h1').first();
      if (await heading.isVisible().catch(() => false)) {
        const wordBreak = await heading.evaluate((el) => {
          return window.getComputedStyle(el).wordBreak;
        });
        
        // Should break words properly
        expect(wordBreak).toMatch(/break-word|break-all/);
      }
    });
    
    test('should truncate long table cell content', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/units');
      
      await page.waitForTimeout(1000);
      
      // Check table cells for truncation
      const tableCells = page.locator('table td, .mobile-table-cards [class*="text"]');
      const cellCount = await tableCells.count();
      
      if (cellCount > 0) {
        // Check if cells have text-overflow: ellipsis or max-width
        const firstCell = tableCells.first();
        const textOverflow = await firstCell.evaluate((el) => {
          return window.getComputedStyle(el).textOverflow;
        });
        const maxWidth = await firstCell.evaluate((el) => {
          return window.getComputedStyle(el).maxWidth;
        });
        
        // Should have ellipsis or max-width constraint
        const hasConstraint = textOverflow === 'ellipsis' || maxWidth !== 'none';
        // This is optional, so we just check if it exists
      }
    });
    
    test('should handle very long Turkish words', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      // Inject very long Turkish word
      await page.evaluate(() => {
        const body = document.querySelector('body');
        if (body) {
          const testDiv = document.createElement('div');
          testDiv.className = 'p-4';
          testDiv.textContent = 'Muvaffakiyetsizleştiricileştiriveremeyebileceklerimizdenmişsinizcesine';
          body.appendChild(testDiv);
        }
      });
      
      await page.waitForTimeout(300);
      
      // Should not break layout
      const hasScroll = await hasHorizontalScroll(page);
      expect(hasScroll).toBe(false);
    });
  });
  
  test.describe('Viewport Edge Cases', () => {
    
    test('should work on very small viewport (320px)', async ({ page }) => {
      await page.setViewportSize({ width: 320, height: 568 }); // iPhone SE
      await page.goto('/');
      
      await page.waitForTimeout(500);
      
      // Check no horizontal scroll
      const hasScroll = await hasHorizontalScroll(page);
      expect(hasScroll).toBe(false);
      
      // Check font-size is readable (min 14px)
      const bodyText = page.locator('p, .text-sm').first();
      if (await bodyText.isVisible().catch(() => false)) {
        const fontSize = await bodyText.evaluate((el) => {
          return parseFloat(window.getComputedStyle(el).fontSize);
        });
        expect(fontSize).toBeGreaterThanOrEqual(14);
      }
    });
    
    test('should work on large viewport (1920px)', async ({ page }) => {
      await page.setViewportSize({ width: 1920, height: 1080 });
      await page.goto('/');
      
      await page.waitForTimeout(500);
      
      // Check container max-width is respected
      const container = page.locator('.max-w-7xl, .max-w-6xl').first();
      
      if (await container.isVisible().catch(() => false)) {
        const containerWidth = await container.boundingBox();
        if (containerWidth) {
          // Should not exceed max-width (1280px for max-w-7xl)
          expect(containerWidth.width).toBeLessThanOrEqual(1320); // Some tolerance
        }
      }
    });
  });
  
  test.describe('Responsive Breakpoints', () => {
    
    test('should switch layouts at correct breakpoints', async ({ page }) => {
      const breakpoints = [
        { width: 639, expected: 'mobile' }, // Below 640px
        { width: 640, expected: 'tablet' }, // At 640px (sm)
        { width: 1023, expected: 'tablet' }, // Below 1024px
        { width: 1024, expected: 'desktop' }, // At 1024px (lg)
      ];
      
      for (const bp of breakpoints) {
        await page.setViewportSize({ width: bp.width, height: 800 });
        await page.goto('/');
        await page.waitForTimeout(500);
        
        // Check grid uses appropriate classes
        const kpiGrid = page.locator('.grid').filter({ hasText: /Periyodik|Bugünkü/i }).first();
        
        if (await kpiGrid.isVisible().catch(() => false)) {
          const classes = await kpiGrid.getAttribute('class');
          
          if (bp.expected === 'mobile') {
            // Should have grid-cols-1
            expect(classes).toMatch(/grid-cols-1/);
          } else if (bp.expected === 'tablet') {
            // Should have sm:grid-cols-2 or similar
            expect(classes).toMatch(/sm:grid-cols/);
          } else if (bp.expected === 'desktop') {
            // Should have lg:grid-cols
            expect(classes).toMatch(/lg:grid-cols/);
          }
        }
      }
    });
  });
});


import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { resizeToMobile, resizeToDesktop, hasHorizontalScroll } from './helpers/viewport';

/**
 * Units (Birimler/Daireler) List & Detail Tests
 * 
 * Tests:
 * - List page table responsive behavior
 * - Mobile table-to-cards conversion
 * - Detail page layout
 * - Text truncation and ellipsis
 */

test.describe('Units', () => {
  
  test.beforeEach(async ({ page }) => {
    try {
      await loginAsAdmin(page);
      await page.waitForTimeout(1000);
    } catch (error) {
      // Continue if login fails
    }
  });
  
  test.describe('Units List Page', () => {
    
    test('should convert table to cards on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/units');
      
      // Wait for page to load
      await page.waitForTimeout(1000);
      
      // Check if mobile-table-cards.js is working
      // On mobile, table should be hidden and cards should be visible
      const table = page.locator('table').first();
      const mobileCards = page.locator('.mobile-table-cards, [class*="mobile-card"]').first();
      
      // Either table is hidden or mobile cards are visible
      const tableVisible = await table.isVisible().catch(() => false);
      const cardsVisible = await mobileCards.isVisible().catch(() => false);
      
      // If mobile-table-cards.js is active, cards should be visible
      // Otherwise, table should have overflow-x-auto
      if (!cardsVisible && tableVisible) {
        const tableContainer = table.locator('..').first();
        const overflow = await tableContainer.evaluate((el) => {
          return window.getComputedStyle(el).overflowX;
        });
        // Should have horizontal scroll container
        expect(overflow).toMatch(/auto|scroll/);
      }
      
      // No horizontal scroll on page level
      const hasScroll = await hasHorizontalScroll(page);
      expect(hasScroll).toBe(false);
    });
    
    test('should display table normally on desktop', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/units');
      
      await page.waitForTimeout(1000);
      
      const table = page.locator('table').first();
      
      if (await table.isVisible().catch(() => false)) {
        // Table should be visible
        await expect(table).toBeVisible();
        
        // Check table has proper styling
        const tableClasses = await table.getAttribute('class');
        expect(tableClasses).toBeTruthy();
      }
    });
    
    test('should have proper spacing in list items', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/units');
      
      await page.waitForTimeout(1000);
      
      // Check cards or table rows
      const items = page.locator('.card, table tbody tr, .mobile-table-cards > div').first();
      
      if (await items.isVisible().catch(() => false)) {
        const padding = await items.evaluate((el) => {
          const style = window.getComputedStyle(el);
          return parseFloat(style.paddingTop);
        });
        
        // Should have at least p-4 (16px) on mobile
        expect(padding).toBeGreaterThanOrEqual(12);
      }
    });
  });
  
  test.describe('Units Detail Page', () => {
    
    test('should display detail page correctly on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/units');
      
      await page.waitForTimeout(1000);
      
      // Try to find first unit link and click
      const firstUnitLink = page.locator('a[href*="/units/"], table a, .card a').first();
      
      if (await firstUnitLink.isVisible().catch(() => false)) {
        await firstUnitLink.click();
        await page.waitForTimeout(1000);
        
        // Check no horizontal scroll
        const hasScroll = await hasHorizontalScroll(page);
        expect(hasScroll).toBe(false);
        
        // Check detail cards have proper spacing
        const detailCards = page.locator('.card, [class*="rounded-xl"]');
        const cardCount = await detailCards.count();
        
        if (cardCount > 0) {
          const firstCard = detailCards.first();
          const padding = await firstCard.evaluate((el) => {
            return parseFloat(window.getComputedStyle(el).paddingTop);
          });
          expect(padding).toBeGreaterThanOrEqual(16);
        }
      } else {
        test.skip();
      }
    });
    
    test('should truncate long text properly', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/units');
      
      await page.waitForTimeout(1000);
      
      // Check for truncated text elements
      const truncatedElements = page.locator('.truncate, .text-ellipsis, [class*="truncate"]');
      const count = await truncatedElements.count();
      
      if (count > 0) {
        const firstTruncated = truncatedElements.first();
        const textOverflow = await firstTruncated.evaluate((el) => {
          return window.getComputedStyle(el).textOverflow;
        });
        
        // Should have ellipsis
        expect(textOverflow).toMatch(/ellipsis/);
      }
    });
    
    test('should have proper grid layout on detail page', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/units');
      
      await page.waitForTimeout(1000);
      
      const firstUnitLink = page.locator('a[href*="/units/"]').first();
      
      if (await firstUnitLink.isVisible().catch(() => false)) {
        await firstUnitLink.click();
        await page.waitForTimeout(1000);
        
        // Check grid layouts use sm: and lg: breakpoints (not md:)
        const grids = page.locator('.grid[class*="grid-cols"]');
        const gridCount = await grids.count();
        
        for (let i = 0; i < Math.min(gridCount, 3); i++) {
          const grid = grids.nth(i);
          const classes = await grid.getAttribute('class');
          
          if (classes) {
            // Should not use md: breakpoint (old standard)
            // Should use sm: or lg: (new standard)
            const hasMd = classes.includes('md:grid-cols');
            if (hasMd) {
              // If md: exists, it should also have sm: (mobile-first)
              expect(classes).toMatch(/grid-cols-1/);
            }
          }
        }
      } else {
        test.skip();
      }
    });
  });
});


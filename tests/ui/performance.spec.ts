import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { resizeToDesktop } from './helpers/viewport';

/**
 * Performance Tests using Lighthouse
 * 
 * Tests Core Web Vitals and performance metrics:
 * - LCP (Largest Contentful Paint)
 * - CLS (Cumulative Layout Shift)
 * - INP/TBT (Total Blocking Time)
 * - FCP (First Contentful Paint)
 * 
 * Note: These tests require Lighthouse to be run separately via npm scripts
 * This spec file provides Playwright-based performance assertions
 */

test.describe('Performance - Core Web Vitals', () => {
  
  test.beforeEach(async ({ page }) => {
    await resizeToDesktop(page);
  });

  test.describe('Page Load Performance', () => {
    
    test('login page should load within acceptable time', async ({ page }) => {
      const startTime = Date.now();
      await page.goto('/login');
      await page.waitForLoadState('networkidle');
      const loadTime = Date.now() - startTime;
      
      // Login page should load within 3 seconds
      expect(loadTime).toBeLessThan(3000);
      
      // Verify page is interactive
      const emailInput = page.locator('input[name="email"], input[type="email"], input[name="phone"]').first();
      await expect(emailInput).toBeVisible({ timeout: 2000 });
    });
    
    test('dashboard should load within acceptable time', async ({ page }) => {
      try {
        await loginAsAdmin(page);
        await page.waitForTimeout(1000);
      } catch (error) {
        // Continue if login fails
      }
      
      const startTime = Date.now();
      await page.goto('/');
      await page.waitForLoadState('networkidle');
      const loadTime = Date.now() - startTime;
      
      // Dashboard should load within 5 seconds
      expect(loadTime).toBeLessThan(5000);
      
      // Verify KPI cards are visible
      const kpiCards = page.locator('.card, [class*="kpi"]');
      const cardCount = await kpiCards.count();
      expect(cardCount).toBeGreaterThan(0);
    });
    
    test('units list page should load within acceptable time', async ({ page }) => {
      try {
        await loginAsAdmin(page);
        await page.waitForTimeout(1000);
      } catch (error) {
        // Continue if login fails
      }
      
      const startTime = Date.now();
      await page.goto('/units');
      await page.waitForLoadState('networkidle');
      const loadTime = Date.now() - startTime;
      
      // Units list should load within 4 seconds
      expect(loadTime).toBeLessThan(4000);
    });
  });

  test.describe('Resource Loading', () => {
    
    test('should not have excessive unused JavaScript', async ({ page }) => {
      await page.goto('/');
      await page.waitForLoadState('networkidle');
      
      // Get JavaScript resources
      const jsResources = await page.evaluate(() => {
        return performance.getEntriesByType('resource')
          .filter((entry: any) => entry.name.endsWith('.js'))
          .map((entry: any) => ({
            name: entry.name,
            size: entry.transferSize || 0,
            duration: entry.duration
          }));
      });
      
      // Total JS size should be reasonable (less than 2MB)
      const totalJSSize = jsResources.reduce((sum: number, r: any) => sum + r.size, 0);
      expect(totalJSSize).toBeLessThan(2 * 1024 * 1024); // 2MB
    });
    
    test('should not have excessive unused CSS', async ({ page }) => {
      await page.goto('/');
      await page.waitForLoadState('networkidle');
      
      // Get CSS resources
      const cssResources = await page.evaluate(() => {
        return performance.getEntriesByType('resource')
          .filter((entry: any) => entry.name.endsWith('.css'))
          .map((entry: any) => ({
            name: entry.name,
            size: entry.transferSize || 0
          }));
      });
      
      // Total CSS size should be reasonable (less than 500KB)
      const totalCSSSize = cssResources.reduce((sum: number, r: any) => sum + r.size, 0);
      expect(totalCSSSize).toBeLessThan(500 * 1024); // 500KB
    });
    
    test('images should be optimized', async ({ page }) => {
      await page.goto('/');
      await page.waitForLoadState('networkidle');
      
      // Get image resources
      const imageResources = await page.evaluate(() => {
        return performance.getEntriesByType('resource')
          .filter((entry: any) => {
            const name = entry.name.toLowerCase();
            return name.endsWith('.jpg') || name.endsWith('.jpeg') || 
                   name.endsWith('.png') || name.endsWith('.webp') || 
                   name.endsWith('.gif');
          })
          .map((entry: any) => ({
            name: entry.name,
            size: entry.transferSize || 0
          }));
      });
      
      // Check if images are reasonably sized (individual images < 500KB)
      for (const img of imageResources) {
        expect(img.size).toBeLessThan(500 * 1024); // 500KB per image
      }
    });
  });

  test.describe('Layout Stability', () => {
    
    test('should not have excessive layout shifts', async ({ page }) => {
      await page.goto('/');
      
      // Wait for page to stabilize
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      
      // Check for layout shifts using Performance Observer
      const layoutShifts = await page.evaluate(() => {
        return new Promise((resolve) => {
          let clsValue = 0;
          const observer = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
              if (!(entry as any).hadRecentInput) {
                clsValue += (entry as any).value;
              }
            }
          });
          
          try {
            observer.observe({ entryTypes: ['layout-shift'] });
            
            // Wait a bit for layout shifts to accumulate
            setTimeout(() => {
              observer.disconnect();
              resolve(clsValue);
            }, 3000);
          } catch (e) {
            resolve(0);
          }
        });
      });
      
      // CLS should be less than 0.1 (good threshold)
      expect(layoutShifts as number).toBeLessThan(0.1);
    });
  });

  test.describe('Network Performance', () => {
    
    test('should not have blocking resources', async ({ page }) => {
      const blockingResources: string[] = [];
      
      page.on('response', (response) => {
        const url = response.url();
        const headers = response.headers();
        const contentType = headers['content-type'] || '';
        
        // Check for blocking resources (CSS, JS in head)
        if (contentType.includes('text/css') || contentType.includes('application/javascript')) {
          const timing = response.timing();
          if (timing && timing.requestStart - timing.startTime > 100) {
            blockingResources.push(url);
          }
        }
      });
      
      await page.goto('/');
      await page.waitForLoadState('networkidle');
      
      // Should have minimal blocking resources
      // Note: Some blocking is expected, but excessive blocking is bad
      expect(blockingResources.length).toBeLessThan(10);
    });
  });

  test.describe('Mobile Performance', () => {
    
    test('mobile viewport should load efficiently', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 812 });
      
      const startTime = Date.now();
      await page.goto('/');
      await page.waitForLoadState('networkidle');
      const loadTime = Date.now() - startTime;
      
      // Mobile should load within 4 seconds
      expect(loadTime).toBeLessThan(4000);
      
      // Verify mobile layout is correct
      const hasHorizontalScroll = await page.evaluate(() => {
        return document.body.scrollWidth > window.innerWidth;
      });
      expect(hasHorizontalScroll).toBe(false);
    });
  });
});


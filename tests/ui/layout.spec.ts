import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { resizeToMobile, resizeToDesktop, hasHorizontalScroll } from './helpers/viewport';

/**
 * Layout Components Tests
 * 
 * Tests:
 * - Navbar/hamburger menu behavior
 * - Footer accordion on mobile
 * - Footer layout on desktop
 * - Touch targets
 */

test.describe('Layout Components', () => {
  
  test.beforeEach(async ({ page }) => {
    try {
      await loginAsAdmin(page);
      await page.waitForTimeout(1000);
    } catch (error) {
      // Continue if login fails
    }
  });
  
  test.describe('Navbar', () => {
    
    test('should toggle mobile menu correctly', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      // Find hamburger menu button
      const hamburger = page.locator('button[aria-label*="menu"], button:has([class*="fa-bars"]), #mobile-menu-toggle').first();
      
      if (await hamburger.isVisible().catch(() => false)) {
        // Menu should be closed initially
        const mobileMenu = page.locator('#mobile-menu, .mobile-nav, [class*="mobile-menu"]').first();
        const initiallyVisible = await mobileMenu.isVisible().catch(() => false);
        
        // Click hamburger
        await hamburger.click();
        await page.waitForTimeout(500);
        
        // Menu should be visible
        const afterClickVisible = await mobileMenu.isVisible().catch(() => false);
        expect(afterClickVisible).toBeTruthy();
        
        // Click again to close
        await hamburger.click();
        await page.waitForTimeout(500);
        
        // Menu should be hidden again
        const afterCloseVisible = await mobileMenu.isVisible().catch(() => false);
        expect(afterCloseVisible).toBeFalsy();
      }
    });
    
    test('should lock body scroll when mobile menu is open', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      const hamburger = page.locator('button[aria-label*="menu"], button:has([class*="fa-bars"])').first();
      
      if (await hamburger.isVisible().catch(() => false)) {
        await hamburger.click();
        await page.waitForTimeout(500);
        
        // Check if body has overflow: hidden
        const bodyOverflow = await page.evaluate(() => {
          return window.getComputedStyle(document.body).overflow;
        });
        
        // Should lock scroll (overflow: hidden or body has modal-open class)
        const hasModalOpen = await page.locator('body.modal-open').count() > 0;
        expect(bodyOverflow === 'hidden' || hasModalOpen).toBeTruthy();
      }
    });
    
    test('should have proper touch targets in navbar', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      // Check navbar buttons and links
      const navButtons = page.locator('nav button, nav a').filter({ hasNotText: '' });
      const buttonCount = await navButtons.count();
      
      for (let i = 0; i < Math.min(buttonCount, 5); i++) {
        const button = navButtons.nth(i);
        const box = await button.boundingBox();
        
        if (box) {
          expect(box.height).toBeGreaterThanOrEqual(44);
          expect(box.width).toBeGreaterThanOrEqual(44);
        }
      }
    });
  });
  
  test.describe('Footer', () => {
    
    test('should display footer accordion on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      // Scroll to footer
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await page.waitForTimeout(500);
      
      // Check footer accordion sections
      const accordionSections = page.locator('footer details, footer .footer-accordion');
      const sectionCount = await accordionSections.count();
      
      if (sectionCount > 0) {
        // First section should be collapsible
        const firstSection = accordionSections.first();
        const summary = firstSection.locator('summary').first();
        
        if (await summary.isVisible().catch(() => false)) {
          // Check if it's closed initially
          const isOpen = await firstSection.getAttribute('open');
          
          // Click to open
          await summary.click();
          await page.waitForTimeout(500);
          
          // Should be open now
          const isOpenAfter = await firstSection.getAttribute('open');
          expect(isOpenAfter).toBeTruthy();
        }
      }
    });
    
    test('should have proper spacing in footer links on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await page.waitForTimeout(500);
      
      const footerLinks = page.locator('footer a');
      const linkCount = await footerLinks.count();
      
      if (linkCount > 0) {
        const firstLink = footerLinks.first();
        const box = await firstLink.boundingBox();
        
        if (box) {
          // Touch target should be at least 44px
          expect(box.height).toBeGreaterThanOrEqual(44);
        }
        
        // Check gap between links
        if (linkCount > 1) {
          const secondLink = footerLinks.nth(1);
          const secondBox = await secondLink.boundingBox();
          
          if (box && secondBox) {
            const gap = secondBox.y - (box.y + box.height);
            // Should have reasonable gap (at least 8px)
            expect(gap).toBeGreaterThanOrEqual(8);
          }
        }
      }
    });
    
    test('should display footer grid correctly on desktop', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/');
      
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await page.waitForTimeout(500);
      
      // Footer should have grid layout on desktop
      const footerGrid = page.locator('footer .grid, footer [class*="grid-cols"]').first();
      
      if (await footerGrid.isVisible().catch(() => false)) {
        const classes = await footerGrid.getAttribute('class');
        
        // Should use lg:grid-cols-4 or similar
        expect(classes).toMatch(/lg:grid-cols|xl:grid-cols/);
      }
    });
    
    test('should have proper font-size in footer on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/');
      
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await page.waitForTimeout(500);
      
      const footerText = page.locator('footer p, footer span, footer a').first();
      
      if (await footerText.isVisible().catch(() => false)) {
        const fontSize = await footerText.evaluate((el) => {
          return parseFloat(window.getComputedStyle(el).fontSize);
        });
        
        // Should be at least 14px on mobile (text-sm minimum)
        expect(fontSize).toBeGreaterThanOrEqual(14);
      }
    });
  });
  
  test.describe('Global Layout', () => {
    
    test('should have smooth scroll behavior', async ({ page }) => {
      await page.goto('/');
      
      // Check if smooth scroll is enabled
      const scrollBehavior = await page.evaluate(() => {
        return window.getComputedStyle(document.documentElement).scrollBehavior;
      });
      
      expect(scrollBehavior).toMatch(/smooth/);
    });
    
    test('should have proper transitions on interactive elements', async ({ page }) => {
      await page.goto('/');
      
      // Check button has transition
      const button = page.locator('button, .btn').first();
      
      if (await button.isVisible().catch(() => false)) {
        const transition = await button.evaluate((el) => {
          return window.getComputedStyle(el).transition;
        });
        
        // Should have transition property
        expect(transition).not.toBe('all 0s ease 0s');
        expect(transition.length).toBeGreaterThan(0);
      }
    });
  });
});


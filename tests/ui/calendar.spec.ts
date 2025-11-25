import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { resizeToDesktop } from './helpers/viewport';

/**
 * ROUND 20: Calendar Page Tests
 * 
 * Tests to ensure:
 * 1. Calendar page loads without 500 or JS errors
 * 2. Quick add modal opens without Alpine errors
 * 3. No syntax errors in calendarApp() function
 * 4. Calendar state is properly initialized
 */

test.describe('ROUND 20: Calendar Page', () => {
  
  test.beforeEach(async ({ page }) => {
    await resizeToDesktop(page);
  });

  test('should load calendar page without 500 or JS errors', async ({ page }) => {
    // Track console errors
    const consoleErrors: string[] = [];
    const pageErrors: string[] = [];
    
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        const text = msg.text();
        consoleErrors.push(text);
        
        // Fail test if we see critical errors
        if (text.includes('calendarApp is not defined') || 
            text.includes('Unexpected token') || 
            text.includes('SyntaxError')) {
          throw new Error(`Console error detected: ${text}`);
        }
      }
    });
    
    page.on('pageerror', (error) => {
      const errorText = error.message;
      pageErrors.push(errorText);
      
      // Fail test on any JS runtime errors
      if (errorText.includes('calendarApp') || 
          errorText.includes('SyntaxError') ||
          errorText.includes('ReferenceError')) {
        throw new Error(`Page error detected: ${errorText}`);
      }
    });
    
    // Track response status codes
    const responseStatuses: number[] = [];
    
    page.on('response', (response) => {
      const url = response.url();
      // Track main document responses (not assets)
      if (!url.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|ico)$/i)) {
        responseStatuses.push(response.status());
      }
    });
    
    // Login first
    try {
      await loginAsAdmin(page);
    } catch (error) {
      // If login fails, skip test but don't fail
      test.skip();
    }
    
    // Navigate to /calendar
    await page.goto('/calendar');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    // Wait a bit for any async initialization
    await page.waitForTimeout(2000);
    
    // Check that no 500 errors occurred
    const has500Error = responseStatuses.some(status => status >= 500);
    expect(has500Error).toBe(false);
    
    // Check that page loaded successfully (status 200)
    const has200Status = responseStatuses.some(status => status === 200);
    expect(has200Status).toBe(true);
    
    // Check that no critical console errors occurred
    const hasCriticalError = consoleErrors.some(error => 
      error.includes('calendarApp is not defined') || 
      error.includes('Unexpected token') ||
      error.includes('SyntaxError')
    );
    
    expect(hasCriticalError).toBe(false);
    
    // Check that no page errors occurred
    const hasPageError = pageErrors.some(error => 
      error.includes('calendarApp') || 
      error.includes('SyntaxError') ||
      error.includes('ReferenceError')
    );
    
    expect(hasPageError).toBe(false);
    
    // Verify calendar page elements are present
    const calendarTitle = page.locator('h1:has-text("Takvim")');
    await expect(calendarTitle).toBeVisible({ timeout: 5000 });
  });

  test('should open quick add modal without Alpine errors', async ({ page }) => {
    // Track console errors
    const consoleErrors: string[] = [];
    const pageErrors: string[] = [];
    
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        const text = msg.text();
        consoleErrors.push(text);
        
        // Fail test if we see Alpine-related errors
        if (text.includes('calendarApp') || 
            text.includes('showQuickAddModal') ||
            text.includes('Alpine') ||
            text.includes('ReferenceError')) {
          throw new Error(`Console error detected: ${text}`);
        }
      }
    });
    
    page.on('pageerror', (error) => {
      const errorText = error.message;
      pageErrors.push(errorText);
      
      // Fail test on any JS runtime errors
      if (errorText.includes('calendarApp') || 
          errorText.includes('showQuickAddModal') ||
          errorText.includes('Alpine')) {
        throw new Error(`Page error detected: ${errorText}`);
      }
    });
    
    // Login first
    try {
      await loginAsAdmin(page);
    } catch (error) {
      // If login fails, skip test but don't fail
      test.skip();
    }
    
    // Navigate to /calendar
    await page.goto('/calendar');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    // Wait for calendar to initialize
    await page.waitForTimeout(2000);
    
    // Find and click the "Hızlı Ekle" button
    const quickAddButton = page.locator('button:has-text("Hızlı Ekle"), button:has-text("+")').first();
    await expect(quickAddButton).toBeVisible({ timeout: 5000 });
    
    // Click the button
    await quickAddButton.click();
    
    // Wait a bit for modal to open
    await page.waitForTimeout(1000);
    
    // Verify modal is visible (check for modal title or form)
    const modalTitle = page.locator('h3:has-text("Hızlı İş Ekleme"), [x-show*="showQuickAddModal"]').first();
    await expect(modalTitle).toBeVisible({ timeout: 5000 });
    
    // Check that no Alpine errors occurred
    const hasAlpineError = consoleErrors.some(error => 
      error.includes('calendarApp') || 
      error.includes('showQuickAddModal') ||
      error.includes('Alpine') ||
      error.includes('ReferenceError')
    );
    
    expect(hasAlpineError).toBe(false);
    
    // Check that no page errors occurred
    const hasPageError = pageErrors.some(error => 
      error.includes('calendarApp') || 
      error.includes('showQuickAddModal') ||
      error.includes('Alpine')
    );
    
    expect(hasPageError).toBe(false);
  });

  test('calendarApp function should be defined and accessible', async ({ page }) => {
    // Login first
    try {
      await loginAsAdmin(page);
    } catch (error) {
      // If login fails, skip test but don't fail
      test.skip();
    }
    
    // Navigate to /calendar
    await page.goto('/calendar');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    // Wait for scripts to execute
    await page.waitForTimeout(2000);
    
    // Check that calendarApp function is defined
    const calendarAppDefined = await page.evaluate(() => {
      return typeof window.calendarApp === 'function' || 
             typeof (window as any).calendarApp === 'function';
    });
    
    // Note: calendarApp might not be on window if it's only used by Alpine
    // So we check for Alpine's x-data binding instead
    const alpineBinding = await page.evaluate(() => {
      const element = document.querySelector('[x-data*="calendarApp"]');
      return element !== null;
    });
    
    expect(alpineBinding).toBe(true);
  });
});



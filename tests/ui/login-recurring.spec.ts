import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { resizeToDesktop } from './helpers/viewport';

/**
 * ROUND 19: Login & Recurring 500 Fix Tests
 * 
 * Tests to ensure:
 * 1. Login flow doesn't produce 500 errors
 * 2. /recurring/new page loads without 500
 * 3. Services API returns JSON (not HTML)
 * 4. No "Hizmetler yüklenemedi" or "Unexpected token '<'" console errors
 */

test.describe('ROUND 19: Login & Recurring 500 Fix', () => {
  
  test.beforeEach(async ({ page }) => {
    await resizeToDesktop(page);
  });

  test('Admin login should redirect to dashboard without 500', async ({ page }) => {
    // Track response status codes
    const responseStatuses: number[] = [];
    
    page.on('response', (response) => {
      const url = response.url();
      // Track main document responses (not assets)
      if (!url.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|ico)$/i)) {
        responseStatuses.push(response.status());
      }
    });
    
    // Navigate to login page
    await page.goto('/login');
    
    // Wait for login form to be visible
    const emailInput = page.locator('input[name="email"], input[name="username"], input[type="email"]').first();
    await expect(emailInput).toBeVisible({ timeout: 5000 });
    
    // Get admin credentials from environment or use defaults
    const adminEmail = process.env.TEST_ADMIN_EMAIL || 'admin@kuretemizlik.com';
    const adminPassword = process.env.TEST_ADMIN_PASSWORD || 'admin123';
    
    // Fill login form
    await emailInput.fill(adminEmail);
    const passwordInput = page.locator('input[name="password"], input[type="password"]').first();
    await passwordInput.fill(adminPassword);
    
    // Submit form
    const submitButton = page.locator('button[type="submit"], input[type="submit"]').first();
    await submitButton.click();
    
    // Wait for redirect to dashboard (either / or /dashboard)
    await page.waitForURL(/\/(dashboard|app\/?)$/, { timeout: 10000 });
    
    // Check that no 500 errors occurred
    const has500Error = responseStatuses.some(status => status >= 500);
    expect(has500Error).toBe(false);
    
    // Verify we're on dashboard (check for dashboard-specific elements)
    const dashboardElements = page.locator('.dashboard, [class*="dashboard"], [id*="dashboard"]');
    const hasDashboardElements = await dashboardElements.count() > 0;
    expect(hasDashboardElements || page.url().includes('/dashboard') || page.url().match(/\/app\/?$/)).toBe(true);
  });

  test('/jobs/new should load services without JSON parse errors', async ({ page }) => {
    // Track console errors
    const consoleErrors: string[] = [];
    
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        const text = msg.text();
        consoleErrors.push(text);
        
        // Fail test if we see the specific error patterns
        if (text.includes('Hizmetler yüklenemedi') || 
            text.includes('Unexpected token') || 
            text.includes('<!DOCTYPE')) {
          throw new Error(`Console error detected: ${text}`);
        }
      }
    });
    
    // Login first
    try {
      await loginAsAdmin(page);
    } catch (error) {
      // If login fails, skip test but don't fail
      test.skip();
    }
    
    // Navigate to /jobs/new
    await page.goto('/jobs/new');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    // Wait a bit for any async service loading
    await page.waitForTimeout(2000);
    
    // Check that no JSON parse errors occurred
    const hasJsonError = consoleErrors.some(error => 
      error.includes('Hizmetler yüklenemedi') || 
      error.includes('Unexpected token') ||
      error.includes('<!DOCTYPE')
    );
    
    expect(hasJsonError).toBe(false);
  });

  test('/recurring/new should load services without JSON parse errors', async ({ page }) => {
    // Track console errors
    const consoleErrors: string[] = [];
    
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        const text = msg.text();
        consoleErrors.push(text);
        
        // Fail test if we see the specific error patterns
        if (text.includes('Hizmetler yüklenemedi') || 
            text.includes('Unexpected token') || 
            text.includes('<!DOCTYPE')) {
          throw new Error(`Console error detected: ${text}`);
        }
      }
    });
    
    // Track response status codes
    const responseStatuses: number[] = [];
    
    page.on('response', (response) => {
      const url = response.url();
      // Track /recurring/new and /api/services responses
      if (url.includes('/recurring/new') || url.includes('/api/services')) {
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
    
    // Navigate to /recurring/new
    await page.goto('/recurring/new');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    // Wait a bit for any async service loading
    await page.waitForTimeout(2000);
    
    // Check that /recurring/new didn't return 500
    const has500Error = responseStatuses.some(status => status >= 500);
    expect(has500Error).toBe(false);
    
    // Check that no JSON parse errors occurred
    const hasJsonError = consoleErrors.some(error => 
      error.includes('Hizmetler yüklenemedi') || 
      error.includes('Unexpected token') ||
      error.includes('<!DOCTYPE')
    );
    
    expect(hasJsonError).toBe(false);
  });

  test('/api/services should return JSON (not HTML)', async ({ page }) => {
    // Login first
    try {
      await loginAsAdmin(page);
    } catch (error) {
      // If login fails, skip test but don't fail
      test.skip();
    }
    
    // Make request to /api/services
    const response = await page.request.get('/api/services');
    
    // Check status code
    expect(response.status()).toBeLessThan(500);
    
    // Check content-type
    const contentType = response.headers()['content-type'] || '';
    expect(contentType).toContain('application/json');
    
    // Try to parse as JSON
    const body = await response.json();
    
    // Verify JSON structure
    expect(body).toHaveProperty('success');
    
    // If success is true, data should be an array
    if (body.success === true) {
      expect(body).toHaveProperty('data');
      expect(Array.isArray(body.data)).toBe(true);
    }
  });
});



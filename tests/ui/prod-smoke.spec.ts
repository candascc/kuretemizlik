import { test, expect } from '@playwright/test';

/**
 * Production Smoke Tests (Read-Only, HTTP Only)
 * 
 * ROUND 12: Production Browser QA & Smoke Test Harness
 * 
 * These tests run against production environment via HTTP requests only.
 * No SSH/terminal access, no file system access, no DB access.
 * 
 * Environment Variables:
 * - PROD_BASE_URL: Production base URL (default: https://www.kuretemizlik.com/app)
 * - PROD_ADMIN_EMAIL: Admin email for login tests (optional)
 * - PROD_ADMIN_PASSWORD: Admin password for login tests (optional)
 * 
 * Usage:
 *   PROD_BASE_URL=https://www.kuretemizlik.com/app npm run test:prod:smoke
 */

const baseURL = process.env.PROD_BASE_URL || 'https://www.kuretemizlik.com/app';

test.describe('Production Smoke Tests (Read-Only)', () => {
  test.beforeEach(async ({ page }) => {
    // Global console error handler
    // Whitelist benign warnings, fail on critical errors
    // ROUND 30: Improved error handling - ignore browser's automatic 404 errors
    page.on('console', msg => {
      if (msg.type() === 'error') {
        const text = msg.text();
        
        // Whitelist Tailwind CDN warning
        if (text.includes('cdn.tailwindcss.com should not be used in production')) {
          return;
        }
        
        // ROUND 30: Whitelist browser's automatic 404 error messages (normal behavior)
        // These are not real JS errors, just browser reporting HTTP 404
        if (text.includes('Failed to load resource: the server responded with a status of 404')) {
          return;
        }
        
        // Critical: nextCursor error is a FAIL
        if (text.includes('nextCursor is not defined')) {
          throw new Error('Alpine nextCursor error on prod: ' + text);
        }
        
        // Other console.error's are also FAIL (real JS runtime errors)
        throw new Error('Console error on prod page: ' + text);
      }
    });
  });

  test('Healthcheck endpoint - GET /health', async ({ page }) => {
    const response = await page.goto(`${baseURL}/health`);
    
    expect(response?.status()).toBe(200);
    
    const contentType = response?.headers()['content-type'] || '';
    expect(contentType).toContain('application/json');
    
    const body = await response?.json();
    expect(body).toHaveProperty('status');
    expect(body.status).toMatch(/ok|healthy|up/i);
    
    // Check for database check in response
    const bodyStr = JSON.stringify(body);
    expect(bodyStr).toMatch(/database|db/i);
  });

  test('Login page - GET /login (Admin Login UI)', async ({ page }) => {
    const response = await page.goto(`${baseURL}/login`);
    
    expect(response?.status()).toBe(200);
    
    // Check for email/username input
    const emailInput = page.locator('input[type="email"], input[name="email"], input[name="username"], input[placeholder*="email" i], input[placeholder*="kullanıcı" i]');
    await expect(emailInput.first()).toBeVisible();
    
    // Check for password input
    const passwordInput = page.locator('input[type="password"], input[name="password"]');
    await expect(passwordInput.first()).toBeVisible();
    
    // Check for login button
    const loginButton = page.locator('button[type="submit"], button:has-text("Giriş"), button:has-text("Login"), input[type="submit"]');
    await expect(loginButton.first()).toBeVisible();
    
    // Check for HTML lang attribute
    const htmlLang = await page.locator('html').getAttribute('lang');
    expect(htmlLang).toBe('tr');
    
    // Page should load without fatal JS errors (handled by beforeEach console handler)
    await page.waitForLoadState('networkidle');
  });

  test('404 page - GET /this-page-does-not-exist-xyz', async ({ page }) => {
    const response = await page.goto(`${baseURL}/this-page-does-not-exist-xyz`);
    
    expect(response?.status()).toBe(404);
    
    // Check for custom 404 design (our error view)
    const pageContent = await page.content();
    expect(pageContent).toMatch(/404|bulunamadı|not found/i);
    
    // Check for HTML lang attribute
    const htmlLang = await page.locator('html').getAttribute('lang');
    expect(htmlLang).toBe('tr');
    
    // Page should load without fatal JS errors (handled by beforeEach console handler)
    await page.waitForLoadState('networkidle');
  });

  test('Jobs New page - GET /jobs/new (Critical: Should not be 500)', async ({ page }) => {
    const response = await page.goto(`${baseURL}/jobs/new`);
    
    // Critical: Should be 200, not 500
    expect(response?.status()).toBe(200);
    
    // Check for HTML lang attribute
    const htmlLang = await page.locator('html').getAttribute('lang');
    expect(htmlLang).toBe('tr');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    
    // Critical: nextCursor error should not exist (handled by beforeEach console handler)
    // If nextCursor error exists, the beforeEach handler will throw and test will fail
    
    // Additional check: Verify no ReferenceError or TypeError in console
    // (handled by beforeEach console handler)
  });

  test('Calendar page - GET /calendar (ROUND 47: First-load 500 fix)', async ({ page }) => {
    // ROUND 47: Test calendar first-load scenario
    // This test verifies that /calendar returns 200 on first load, not 500
    // Note: Calendar requires authentication, so we expect redirect to login or 200 with login form
    const response = await page.goto(`${baseURL}/calendar`, { waitUntil: 'networkidle' });
    
    // Critical: Should be 200 or 302 (redirect to login), NOT 500
    // First-load 500 was the issue - this should never happen
    const status = response?.status() ?? 0;
    expect([200, 302]).toContain(status);
    
    // If redirected to login, that's acceptable (auth required)
    if (status === 302) {
      const location = response?.headers()['location'] || '';
      expect(location).toMatch(/login/i);
      return; // Test passes - redirect is expected for unauthenticated users
    }
    
    // If 200, check for HTML lang attribute
    const htmlLang = await page.locator('html').getAttribute('lang');
    expect(htmlLang).toBe('tr');
    
    // Critical: No console errors (handled by beforeEach console handler)
    // If there are JS errors, the beforeEach handler will throw and test will fail
    
    // Additional check: Page should not be a 500 error page
    // If it's a login page, that's fine (auth required)
    const pageContent = await page.content();
    const isLoginPage = pageContent.includes('login') || pageContent.includes('Giriş');
    const isErrorPage = pageContent.match(/500|internal server error/i);
    
    // If it's not a login page and it's an error page, that's a fail
    if (!isLoginPage && isErrorPage) {
      throw new Error('Calendar page returned 500 error page instead of calendar or login');
    }
  });

  test('Security headers - Basic check (anonymous page)', async ({ request }) => {
    // Use request API to check headers without full page load
    const response = await request.get(`${baseURL}/login`);
    
    expect(response.status()).toBe(200);
    
    const headers = response.headers();
    
    // Check for X-Frame-Options
    expect(headers).toHaveProperty('x-frame-options');
    const xFrameOptions = headers['x-frame-options']?.toLowerCase();
    expect(['deny', 'sameorigin', 'allow-from']).toContain(xFrameOptions);
    
    // Check for X-Content-Type-Options
    expect(headers).toHaveProperty('x-content-type-options');
    expect(headers['x-content-type-options']?.toLowerCase()).toBe('nosniff');
    
    // Check for Referrer-Policy
    expect(headers).toHaveProperty('referrer-policy');
    const referrerPolicy = headers['referrer-policy']?.toLowerCase();
    expect(referrerPolicy).toBeTruthy();
  });

  test('Admin login flow (if credentials provided)', async ({ page }) => {
    // Skip if credentials not provided
    const adminEmail = process.env.PROD_ADMIN_EMAIL;
    const adminPassword = process.env.PROD_ADMIN_PASSWORD;
    
    if (!adminEmail || !adminPassword) {
      test.skip(true, 'PROD_ADMIN_EMAIL and PROD_ADMIN_PASSWORD not set');
    }
    
    // Navigate to login page
    await page.goto(`${baseURL}/login`);
    
    // Fill login form
    const emailInput = page.locator('input[type="email"], input[name="email"], input[name="username"]').first();
    const passwordInput = page.locator('input[type="password"], input[name="password"]').first();
    const loginButton = page.locator('button[type="submit"], input[type="submit"]').first();
    
    await emailInput.fill(adminEmail);
    await passwordInput.fill(adminPassword);
    await loginButton.click();
    
    // Wait for redirect (should redirect to dashboard)
    await page.waitForURL(/\/(dashboard|home)/, { timeout: 10000 });
    
    // Verify we're logged in (check for logout button or user menu)
    const logoutButton = page.locator('a[href*="logout"], button:has-text("Çıkış"), button:has-text("Logout")');
    await expect(logoutButton.first()).toBeVisible({ timeout: 5000 });
    
    // Page should load without fatal JS errors (handled by beforeEach console handler)
    await page.waitForLoadState('networkidle');
  });
});


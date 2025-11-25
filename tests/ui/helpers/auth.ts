import { Page } from '@playwright/test';

/**
 * Authentication Helper Functions
 * 
 * Provides reusable login/logout functions for tests
 */

/**
 * Login as admin user
 * @param page Playwright page instance
 * @param email Admin email (default from env or test data)
 * @param password Admin password (default from env or test data)
 */
export async function loginAsAdmin(
  page: Page,
  username: string = process.env.TEST_ADMIN_EMAIL || process.env.TEST_ADMIN_USERNAME || 'candas',
  password: string = process.env.TEST_ADMIN_PASSWORD || '12dream21'
): Promise<void> {
  await page.goto('/login');
  
  // Wait for login form to be visible - check for username, email, or phone input
  await page.waitForSelector('input[name="username"], input[name="email"], input[type="email"], input[name="phone"], input[type="tel"]', { timeout: 10000 });
  
  // Try username input first (most common in this app)
  const usernameInput = page.locator('input[name="username"]').first();
  if (await usernameInput.isVisible().catch(() => false)) {
    await usernameInput.fill(username);
    await page.locator('input[name="password"], input[type="password"]').first().fill(password);
    await page.locator('button[type="submit"], button:has-text("Giriş"), button:has-text("Login")').first().click();
  } else {
    // Try email input
    const emailInput = page.locator('input[name="email"], input[type="email"]').first();
    if (await emailInput.isVisible().catch(() => false)) {
      await emailInput.fill(username);
      await page.locator('input[name="password"], input[type="password"]').first().fill(password);
      await page.locator('button[type="submit"], button:has-text("Giriş"), button:has-text("Login")').first().click();
    } else {
      // Phone-based login (resident portal)
      const phoneInput = page.locator('input[name="phone"], input[type="tel"]').first();
      if (await phoneInput.isVisible().catch(() => false)) {
        await phoneInput.fill(username);
        await page.locator('button[type="submit"], button:has-text("Giriş")').first().click();
      }
    }
  }
  
  // Wait for redirect to dashboard or home
  await page.waitForURL(/\/(dashboard|home|resident\/dashboard|\/app\/?$)/, { timeout: 15000 }).catch(() => {
    // If no redirect, check if we're still on login page (login failed)
    const currentUrl = page.url();
    if (currentUrl.includes('/login')) {
      throw new Error('Login failed - still on login page');
    }
    return page.waitForTimeout(1000);
  });
}

/**
 * Login as resident user (phone-based)
 * @param page Playwright page instance
 * @param phone Phone number
 */
export async function loginAsResident(
  page: Page,
  phone: string = process.env.TEST_RESIDENT_PHONE || '5551234567'
): Promise<void> {
  await page.goto('/resident/login');
  
  await page.waitForSelector('input[name="phone"], input[type="tel"]', { timeout: 5000 });
  await page.locator('input[name="phone"], input[type="tel"]').first().fill(phone);
  await page.locator('button[type="submit"], button:has-text("Giriş")').first().click();
  
  // Wait for OTP or password step
  await page.waitForTimeout(2000);
}

/**
 * Logout from current session
 * @param page Playwright page instance
 */
export async function logout(page: Page): Promise<void> {
  // Try to find logout button/link
  const logoutButton = page.locator('a:has-text("Çıkış"), button:has-text("Logout"), a[href*="logout"]').first();
  
  if (await logoutButton.isVisible().catch(() => false)) {
    await logoutButton.click();
    await page.waitForURL(/\/(login|resident\/login)/, { timeout: 5000 });
  } else {
    // Clear cookies and storage as fallback
    await page.context().clearCookies();
    await page.evaluate(() => {
      localStorage.clear();
      sessionStorage.clear();
    });
  }
}


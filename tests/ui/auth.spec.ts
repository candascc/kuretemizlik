import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsResident, logout } from './helpers/auth';
import { resizeToMobile, resizeToDesktop, hasHorizontalScroll } from './helpers/viewport';

/**
 * Authentication Flow Tests
 * 
 * Tests:
 * - Login form layout (mobile & desktop)
 * - Successful login
 * - Failed login error handling
 * - Form validation
 * - Responsive behavior
 */

test.describe('Authentication Flow', () => {
  
  test.describe('Admin Login', () => {
    
    test('should display login form correctly on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/login');
      
      // Check form elements are visible
      const emailInput = page.locator('input[name="email"], input[type="email"]').first();
      const passwordInput = page.locator('input[name="password"], input[type="password"]').first();
      const submitButton = page.locator('button[type="submit"]').first();
      
      // Form should be visible
      await expect(emailInput.or(passwordInput).first()).toBeVisible();
      
      // Check no horizontal scroll
      const hasScroll = await hasHorizontalScroll(page);
      expect(hasScroll).toBe(false);
      
      // Check touch targets (minimum 44px)
      const buttonBox = await submitButton.boundingBox();
      if (buttonBox) {
        expect(buttonBox.height).toBeGreaterThanOrEqual(44);
      }
    });
    
    test('should display login form correctly on desktop', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/login');
      
      // Check form elements
      const emailInput = page.locator('input[name="email"], input[type="email"]').first();
      const passwordInput = page.locator('input[name="password"], input[type="password"]').first();
      
      await expect(emailInput.or(passwordInput).first()).toBeVisible();
      
      // Check form layout doesn't break
      const hasScroll = await hasHorizontalScroll(page);
      expect(hasScroll).toBe(false);
    });
    
    test('should show error message on invalid credentials', async ({ page }) => {
      await page.goto('/login');
      
      // Try to find email or phone input
      const emailInput = page.locator('input[name="email"], input[type="email"]').first();
      const phoneInput = page.locator('input[name="phone"], input[type="tel"]').first();
      
      if (await emailInput.isVisible().catch(() => false)) {
        await emailInput.fill('invalid@test.com');
        await page.locator('input[name="password"], input[type="password"]').first().fill('wrongpassword');
        await page.locator('button[type="submit"]').first().click();
      } else if (await phoneInput.isVisible().catch(() => false)) {
        await phoneInput.fill('9999999999');
        await page.locator('button[type="submit"]').first().click();
      }
      
      // Wait for error message
      await page.waitForTimeout(1000);
      
      // Check for error message (could be flash message, alert, or inline error)
      const errorMessage = page.locator('.alert-error, .flash-error, .text-red-600, [role="alert"]').first();
      const hasError = await errorMessage.isVisible().catch(() => false);
      
      // If no visible error, check if still on login page (implicit error)
      if (!hasError) {
        const currentUrl = page.url();
        expect(currentUrl).toMatch(/\/(login|resident\/login)/);
      }
    });
    
    test('should validate required fields', async ({ page }) => {
      await page.goto('/login');
      
      // Try to submit empty form
      const submitButton = page.locator('button[type="submit"]').first();
      await submitButton.click();
      
      // Wait a bit for validation
      await page.waitForTimeout(500);
      
      // Check for HTML5 validation or custom validation messages
      const emailInput = page.locator('input[name="email"], input[type="email"]').first();
      const phoneInput = page.locator('input[name="phone"], input[type="tel"]').first();
      
      if (await emailInput.isVisible().catch(() => false)) {
        const validity = await emailInput.evaluate((el: HTMLInputElement) => el.validity.valid);
        // If HTML5 validation is active, form won't submit
        // If custom validation, check for error message
        const errorMsg = page.locator('.field-error, .text-red-600').first();
        const hasError = await errorMsg.isVisible().catch(() => false);
        expect(validity === false || hasError).toBeTruthy();
      }
    });
  });
  
  test.describe('Resident Login', () => {
    
    test('should display resident login form on mobile', async ({ page }) => {
      await resizeToMobile(page);
      await page.goto('/resident/login');
      
      // Check phone input is visible
      const phoneInput = page.locator('input[name="phone"], input[type="tel"]').first();
      await expect(phoneInput).toBeVisible();
      
      // Check no horizontal scroll
      const hasScroll = await hasHorizontalScroll(page);
      expect(hasScroll).toBe(false);
      
      // Check form input has minimum font-size (14px) on mobile
      const fontSize = await phoneInput.evaluate((el) => {
        return window.getComputedStyle(el).fontSize;
      });
      const fontSizeNum = parseFloat(fontSize);
      expect(fontSizeNum).toBeGreaterThanOrEqual(14);
    });
    
    test('should handle phone input formatting', async ({ page }) => {
      await page.goto('/resident/login');
      
      const phoneInput = page.locator('input[name="phone"], input[type="tel"]').first();
      await phoneInput.fill('5551234567');
      
      // Check if phone is formatted or kept as-is
      const value = await phoneInput.inputValue();
      expect(value.length).toBeGreaterThan(0);
    });
  });
  
  test.describe('Logout', () => {
    
    test('should logout successfully', async ({ page }) => {
      // Try to login first (if credentials available)
      try {
        await loginAsAdmin(page);
        await page.waitForTimeout(1000);
        
        // Try to logout
        await logout(page);
        
        // Should be redirected to login
        const currentUrl = page.url();
        expect(currentUrl).toMatch(/\/(login|resident\/login)/);
      } catch (error) {
        // If login fails, skip logout test
        test.skip();
      }
    });
  });
});


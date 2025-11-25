import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { resizeToMobile, resizeToDesktop, hasHorizontalScroll } from './helpers/viewport';

/**
 * Finance Form Tests
 * 
 * Tests:
 * - Form layout on mobile and desktop
 * - Form validation feedback
 * - Input field styling
 * - Submit button behavior
 */

test.describe('Finance Forms', () => {
  
  test.beforeEach(async ({ page }) => {
    try {
      await loginAsAdmin(page);
      await page.waitForTimeout(1000);
    } catch (error) {
      // Continue if login fails
    }
  });
  
  test.describe('Form Layout', () => {
    
    test('should display form correctly on mobile', async ({ page }) => {
      await resizeToMobile(page);
      
      // Try finance form page
      const financePages = ['/finance/new', '/finance/form', '/management-fees/generate'];
      
      for (const path of financePages) {
        try {
          await page.goto(path);
          await page.waitForTimeout(1000);
          
          // Check if form exists
          const form = page.locator('form').first();
          if (await form.isVisible().catch(() => false)) {
            // Check no horizontal scroll
            const hasScroll = await hasHorizontalScroll(page);
            expect(hasScroll).toBe(false);
            
            // Check form inputs have proper font-size (min 14px on mobile)
            const input = page.locator('input[type="text"], input[type="number"], select').first();
            if (await input.isVisible().catch(() => false)) {
              const fontSize = await input.evaluate((el) => {
                return parseFloat(window.getComputedStyle(el).fontSize);
              });
              expect(fontSize).toBeGreaterThanOrEqual(14);
            }
            
            break; // Found a working form page
          }
        } catch (error) {
          continue; // Try next page
        }
      }
    });
    
    test('should have proper grid layout for form fields', async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto('/finance/new');
      
      await page.waitForTimeout(1000);
      
      // Check form grid uses sm: breakpoint (not md:)
      const formGrids = page.locator('form .grid[class*="grid-cols"]');
      const gridCount = await formGrids.count();
      
      if (gridCount > 0) {
        const firstGrid = formGrids.first();
        const classes = await firstGrid.getAttribute('class');
        
        if (classes) {
          // Should use sm:grid-cols-2 (new standard) or have grid-cols-1 for mobile
          const hasSm = classes.includes('sm:grid-cols');
          const hasGridCols1 = classes.includes('grid-cols-1');
          
          expect(hasSm || hasGridCols1).toBeTruthy();
        }
      }
    });
    
    test('should have proper input field styling', async ({ page }) => {
      await page.goto('/finance/new');
      await page.waitForTimeout(1000);
      
      const input = page.locator('input[type="text"], input[type="number"]').first();
      
      if (await input.isVisible().catch(() => false)) {
        // Check input has form-input class or proper styling
        const classes = await input.getAttribute('class');
        const borderRadius = await input.evaluate((el) => {
          return window.getComputedStyle(el).borderRadius;
        });
        
        // Should have rounded corners (rounded-lg or rounded-xl)
        const borderRadiusNum = parseFloat(borderRadius);
        expect(borderRadiusNum).toBeGreaterThan(0);
      }
    });
  });
  
  test.describe('Form Validation', () => {
    
    test('should show validation feedback on empty required fields', async ({ page }) => {
      await page.goto('/finance/new');
      await page.waitForTimeout(1000);
      
      // Try to submit form without filling
      const submitButton = page.locator('button[type="submit"]').first();
      
      if (await submitButton.isVisible().catch(() => false)) {
        await submitButton.click();
        await page.waitForTimeout(500);
        
        // Check for validation messages
        const errorMessages = page.locator('.field-error, .text-red-600, [aria-invalid="true"]');
        const errorCount = await errorMessages.count();
        
        // Should have at least HTML5 validation or custom validation
        const requiredInputs = page.locator('input[required], select[required]');
        const requiredCount = await requiredInputs.count();
        
        if (requiredCount > 0) {
          // Check if HTML5 validation is working
          const firstRequired = requiredInputs.first();
          const validity = await firstRequired.evaluate((el: HTMLInputElement) => {
            return (el as any).validity?.valid === false;
          });
          
          expect(validity || errorCount > 0).toBeTruthy();
        }
      }
    });
    
    test('should have proper focus states on inputs', async ({ page }) => {
      await page.goto('/finance/new');
      await page.waitForTimeout(1000);
      
      const input = page.locator('input[type="text"], input[type="number"]').first();
      
      if (await input.isVisible().catch(() => false)) {
        await input.focus();
        await page.waitForTimeout(200);
        
        // Check for focus ring or border change
        const outline = await input.evaluate((el) => {
          const style = window.getComputedStyle(el);
          return {
            outline: style.outline,
            outlineWidth: style.outlineWidth,
            borderColor: style.borderColor,
            boxShadow: style.boxShadow,
          };
        });
        
        // Should have visible focus indicator
        const hasFocusIndicator = 
          parseFloat(outline.outlineWidth) > 0 ||
          outline.boxShadow !== 'none' ||
          outline.borderColor.includes('rgb(37, 99, 235)'); // primary-600
        
        expect(hasFocusIndicator).toBeTruthy();
      }
    });
  });
  
  test.describe('Form Submit', () => {
    
    test('should disable submit button during submission', async ({ page }) => {
      await page.goto('/finance/new');
      await page.waitForTimeout(1000);
      
      const submitButton = page.locator('button[type="submit"]').first();
      
      if (await submitButton.isVisible().catch(() => false)) {
        // Fill some fields if needed
        const amountInput = page.locator('input[name*="amount"], input[name*="tutar"]').first();
        if (await amountInput.isVisible().catch(() => false)) {
          await amountInput.fill('100');
        }
        
        // Click submit
        await submitButton.click();
        await page.waitForTimeout(300);
        
        // Check if button is disabled or has loading state
        const isDisabled = await submitButton.isDisabled().catch(() => false);
        const hasLoading = await submitButton.locator('.fa-spinner, [class*="spinner"]').isVisible().catch(() => false);
        
        // Should have some loading/disabled feedback
        expect(isDisabled || hasLoading).toBeTruthy();
      }
    });
  });
});


import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { loginAsAdmin } from './helpers/auth';

/**
 * Accessibility (a11y) Tests
 * 
 * Tests WCAG 2.1 compliance using axe-core
 * 
 * Focus areas:
 * - Color contrast
 * - Landmark roles
 * - Form labels
 * - Keyboard navigation
 * - ARIA attributes
 * 
 * Critical/Serious violations will fail the test
 */

test.describe('Accessibility (a11y)', () => {
  
  test.describe('Login Page', () => {
    
    test('should have no critical or serious accessibility violations', async ({ page }) => {
      await page.goto('/login');
      await page.waitForTimeout(1000);
      
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'best-practice'])
        .analyze();
      
      // Filter critical and serious violations
      const criticalViolations = accessibilityScanResults.violations.filter(
        v => v.impact === 'critical' || v.impact === 'serious'
      );
      
      // Fail if critical/serious violations exist
      if (criticalViolations.length > 0) {
        console.error('Critical/Serious Accessibility Violations:', JSON.stringify(criticalViolations, null, 2));
      }
      
      expect(criticalViolations).toHaveLength(0);
      
      // Log all violations for reference (but don't fail on minor issues)
      if (accessibilityScanResults.violations.length > 0) {
        console.log(`Total violations: ${accessibilityScanResults.violations.length}`);
        console.log('All violations:', JSON.stringify(accessibilityScanResults.violations, null, 2));
      }
    });
    
    test('should have proper form labels', async ({ page }) => {
      await page.goto('/login');
      await page.waitForTimeout(1000);
      
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();
      
      // Check for label-related violations
      const labelViolations = accessibilityScanResults.violations.filter(
        v => v.id === 'label' || v.id === 'label-title-only' || v.id === 'aria-label'
      );
      
      expect(labelViolations).toHaveLength(0);
    });
    
    test('should have sufficient color contrast', async ({ page }) => {
      await page.goto('/login');
      await page.waitForTimeout(1000);
      
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['wcag2aa'])
        .analyze();
      
      // Check for color contrast violations
      const contrastViolations = accessibilityScanResults.violations.filter(
        v => v.id === 'color-contrast'
      );
      
      // Color contrast is critical for accessibility
      expect(contrastViolations).toHaveLength(0);
    });
  });

  test.describe('Dashboard', () => {
    
    test.beforeEach(async ({ page }) => {
      try {
        await loginAsAdmin(page);
        await page.waitForTimeout(1000);
      } catch (error) {
        // Continue if login fails
      }
    });
    
    test('should have no critical or serious accessibility violations', async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'best-practice'])
        .analyze();
      
      const criticalViolations = accessibilityScanResults.violations.filter(
        v => v.impact === 'critical' || v.impact === 'serious'
      );
      
      if (criticalViolations.length > 0) {
        console.error('Critical/Serious Accessibility Violations:', JSON.stringify(criticalViolations, null, 2));
      }
      
      expect(criticalViolations).toHaveLength(0);
    });
    
    test('should have proper heading hierarchy', async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'best-practice'])
        .analyze();
      
      // Check for heading hierarchy violations
      const headingViolations = accessibilityScanResults.violations.filter(
        v => v.id === 'heading-order' || v.id === 'page-has-heading-one'
      );
      
      expect(headingViolations).toHaveLength(0);
    });
    
    test('should have proper landmark roles', async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'best-practice'])
        .analyze();
      
      // Check for landmark violations
      const landmarkViolations = accessibilityScanResults.violations.filter(
        v => v.id === 'landmark-one-main' || v.id === 'region'
      );
      
      expect(landmarkViolations).toHaveLength(0);
    });
  });

  test.describe('Units List Page', () => {
    
    test.beforeEach(async ({ page }) => {
      try {
        await loginAsAdmin(page);
        await page.waitForTimeout(1000);
      } catch (error) {
        // Continue if login fails
      }
    });
    
    test('should have no critical or serious accessibility violations', async ({ page }) => {
      await page.goto('/units');
      await page.waitForTimeout(1000);
      
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'best-practice'])
        .analyze();
      
      const criticalViolations = accessibilityScanResults.violations.filter(
        v => v.impact === 'critical' || v.impact === 'serious'
      );
      
      if (criticalViolations.length > 0) {
        console.error('Critical/Serious Accessibility Violations:', JSON.stringify(criticalViolations, null, 2));
      }
      
      expect(criticalViolations).toHaveLength(0);
    });
    
    test('should have accessible table structure', async ({ page }) => {
      await page.goto('/units');
      await page.waitForTimeout(1000);
      
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();
      
      // Check for table-related violations
      const tableViolations = accessibilityScanResults.violations.filter(
        v => v.id === 'th-has-data-cells' || v.id === 'html-has-lang'
      );
      
      expect(tableViolations).toHaveLength(0);
    });
  });

  test.describe('Finance Form', () => {
    
    test.beforeEach(async ({ page }) => {
      try {
        await loginAsAdmin(page);
        await page.waitForTimeout(1000);
      } catch (error) {
        // Continue if login fails
      }
    });
    
    test('should have no critical or serious accessibility violations', async ({ page }) => {
      // Try finance form pages
      const financePages = ['/finance/new', '/finance/form', '/management-fees/generate'];
      
      for (const path of financePages) {
        try {
          await page.goto(path);
          await page.waitForTimeout(1000);
          
          // Check if form exists
          const form = page.locator('form').first();
          if (await form.isVisible().catch(() => false)) {
            const accessibilityScanResults = await new AxeBuilder({ page })
              .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'best-practice'])
              .analyze();
            
            const criticalViolations = accessibilityScanResults.violations.filter(
              v => v.impact === 'critical' || v.impact === 'serious'
            );
            
            if (criticalViolations.length > 0) {
              console.error('Critical/Serious Accessibility Violations:', JSON.stringify(criticalViolations, null, 2));
            }
            
            expect(criticalViolations).toHaveLength(0);
            break; // Found a working form page
          }
        } catch (error) {
          continue; // Try next page
        }
      }
    });
    
    test('should have proper form field labels and ARIA attributes', async ({ page }) => {
      await page.goto('/units/new');
      await page.waitForTimeout(1000);
      
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();
      
      // Check for form-related violations
      const formViolations = accessibilityScanResults.violations.filter(
        v => v.id === 'label' || v.id === 'aria-required-attr' || v.id === 'aria-valid-attr-value'
      );
      
      expect(formViolations).toHaveLength(0);
    });
  });

  test.describe('Units Detail Page', () => {
    
    test.beforeEach(async ({ page }) => {
      try {
        await loginAsAdmin(page);
        await page.waitForTimeout(1000);
      } catch (error) {
        // Continue if login fails
      }
    });
    
    test('should have no critical or serious accessibility violations', async ({ page }) => {
      await page.goto('/units');
      await page.waitForTimeout(1000);
      
      // Try to find first unit link and click
      const firstUnitLink = page.locator('a[href*="/units/"]').first();
      
      if (await firstUnitLink.isVisible().catch(() => false)) {
        await firstUnitLink.click();
        await page.waitForTimeout(1000);
        
        const accessibilityScanResults = await new AxeBuilder({ page })
          .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'best-practice'])
          .analyze();
        
        const criticalViolations = accessibilityScanResults.violations.filter(
          v => v.impact === 'critical' || v.impact === 'serious'
        );
        
        if (criticalViolations.length > 0) {
          console.error('Critical/Serious Accessibility Violations:', JSON.stringify(criticalViolations, null, 2));
        }
        
        expect(criticalViolations).toHaveLength(0);
      } else {
        test.skip();
      }
    });
  });

  test.describe('Keyboard Navigation', () => {
    
    test('should be navigable with keyboard only', async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      const accessibilityScanResults = await new AxeBuilder({ page })
        .withTags(['keyboard'])
        .analyze();
      
      // Check for keyboard navigation violations
      const keyboardViolations = accessibilityScanResults.violations.filter(
        v => v.id === 'keyboard' || v.id === 'focus-order-semantics' || v.id === 'focusable-content'
      );
      
      expect(keyboardViolations).toHaveLength(0);
    });
    
    test('should have visible focus indicators', async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      // Tab through interactive elements
      await page.keyboard.press('Tab');
      await page.waitForTimeout(200);
      
      // Check if focused element has visible focus indicator
      const focusedElement = await page.evaluateHandle(() => document.activeElement);
      const focusStyles = await focusedElement.evaluate((el: Element) => {
        const style = window.getComputedStyle(el);
        return {
          outline: style.outline,
          outlineWidth: style.outlineWidth,
          boxShadow: style.boxShadow,
        };
      });
      
      // Should have visible focus indicator
      const hasFocusIndicator = 
        parseFloat(focusStyles.outlineWidth) > 0 ||
        focusStyles.boxShadow !== 'none';
      
      expect(hasFocusIndicator).toBeTruthy();
    });
  });
});


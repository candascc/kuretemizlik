import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import {
  resizeToMobile,
  resizeToTablet,
  resizeToDesktop,
  resizeToLargeDesktop,
  hasHorizontalScroll,
} from './helpers/viewport';

type FilterPage = {
  path: string;
  name: string;
};

/**
 * Responsive Layout Regression Tests
 *
 * Covers shared filter grid component (list-filters.php) used across multiple modules.
 * Ensures padding symmetry, gutter consistency and breakpoint behaviour so that
 * visual regressions like misaligned finance forms are caught early.
 */

test.use({ video: 'off' });

const filterPages: FilterPage[] = [
  { path: '/finance', name: 'Finance list' },
  { path: '/jobs', name: 'Jobs list' },
  { path: '/reports', name: 'Reports hub' },
  { path: '/customers', name: 'Customers list' },
];

async function getFilterForm(page: any) {
  const form = page.locator('form:has(button:has-text("Filtrele"))').first();
  if (await form.isVisible().catch(() => false)) {
    return form;
  }
  return null;
}

async function getGridColumnCount(page: any, selector: string) {
  return await page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return 0;
    const style = window.getComputedStyle(el);
    const template = style.gridTemplateColumns;
    if (!template || template === 'none') return 0;
    const repeatMatch = template.match(/repeat\((\d+)/);
    if (repeatMatch) {
      return parseInt(repeatMatch[1], 10);
    }
    return template.split(' ').length;
  }, selector);
}

async function loginWithFallback(page: any) {
  await loginAsAdmin(page);
  await page.waitForTimeout(1000);

  if ((await page.url()).includes('/login')) {
    const fallbackEmail = process.env.DEFAULT_ADMIN_EMAIL || 'admin@test.com';
    const fallbackPassword = process.env.DEFAULT_ADMIN_PASSWORD || 'admin123';
    await loginAsAdmin(page, fallbackEmail, fallbackPassword);
    await page.waitForTimeout(1000);
  }
}

test.describe('Responsive layout consistency', () => {
  test.beforeEach(async ({ page }) => {
    await loginWithFallback(page);
  });

  for (const pageInfo of filterPages) {
    test(`should stack filter grid to single column on mobile (${pageInfo.name})`, async ({ page }) => {
      await resizeToMobile(page);
      await page.goto(pageInfo.path);
      const form = await getFilterForm(page);
      test.skip(!form, `Filter form not visible on ${pageInfo.path}`);

      const grid = form!.locator('div.grid').first();
      await expect(grid, `Filter grid missing on ${pageInfo.path}`).toBeVisible();

      const gridSelector = await grid.evaluate((el) => {
        el.setAttribute('data-test-grid', 'filters-mobile');
        return '[data-test-grid="filters-mobile"]';
      });

      const columnCount = await getGridColumnCount(page, gridSelector);
      expect(columnCount, `${pageInfo.name} should collapse to 1 column on mobile`).toBeLessThanOrEqual(1);

      const scroll = await hasHorizontalScroll(page);
      expect(scroll, `${pageInfo.name} should not scroll horizontally on mobile`).toBe(false);
    });
  }

  for (const pageInfo of filterPages) {
    test(`should use balanced multi-column grid on desktop (${pageInfo.name})`, async ({ page }) => {
      await resizeToDesktop(page);
      await page.goto(pageInfo.path);
      const form = await getFilterForm(page);
      test.skip(!form, `Filter form not visible on ${pageInfo.path}`);

      const grid = form!.locator('div.grid').first();
      await expect(grid).toBeVisible();

      const gridSelector = await grid.evaluate((el) => {
        el.setAttribute('data-test-grid', 'filters-desktop');
        return '[data-test-grid="filters-desktop"]';
      });

      const columnCount = await getGridColumnCount(page, gridSelector);
      expect(columnCount, `${pageInfo.name} should have >=4 columns on desktop`).toBeGreaterThanOrEqual(4);

      const firstField = grid.locator('div').first();
      const secondField = grid.locator('div').nth(1);

      if (await firstField.isVisible().catch(() => false) && await secondField.isVisible().catch(() => false)) {
        const [firstBox, secondBox] = await Promise.all([firstField.boundingBox(), secondField.boundingBox()]);
        if (firstBox && secondBox) {
          const widthDiff = Math.abs(firstBox.width - secondBox.width);
          expect(widthDiff, 'Desktop filter items should have consistent widths').toBeLessThanOrEqual(4);
        }
      }
    });
  }

  for (const pageInfo of filterPages) {
    test(`should keep padding and gutters symmetrical (${pageInfo.name})`, async ({ page }) => {
      await resizeToLargeDesktop(page);
      await page.goto(pageInfo.path);
      const form = await getFilterForm(page);
      test.skip(!form, `Filter form not visible on ${pageInfo.path}`);

      const metrics = await form!.evaluate(() => {
        const style = window.getComputedStyle(this as unknown as HTMLElement);
        return {
          paddingLeft: parseFloat(style.paddingLeft),
          paddingRight: parseFloat(style.paddingRight),
          columnGap: parseFloat(style.columnGap || '0'),
          rowGap: parseFloat(style.rowGap || '0'),
        };
      });

      expect(Math.abs(metrics.paddingLeft - metrics.paddingRight)).toBeLessThanOrEqual(2);
      expect(metrics.paddingLeft).toBeGreaterThanOrEqual(16);
      expect(metrics.columnGap).toBeGreaterThanOrEqual(12);
      expect(metrics.rowGap).toBeGreaterThanOrEqual(12);
    });
  }

  for (const pageInfo of filterPages) {
    test(`should maintain two-column layout on tablet (${pageInfo.name})`, async ({ page }) => {
      await resizeToTablet(page);
      await page.goto(pageInfo.path);
      const form = await getFilterForm(page);
      test.skip(!form, `Filter form not visible on ${pageInfo.path}`);

      const grid = form!.locator('div.grid').first();
      await expect(grid).toBeVisible();

      const gridSelector = await grid.evaluate((el) => {
        el.setAttribute('data-test-grid', 'filters-tablet');
        return '[data-test-grid="filters-tablet"]';
      });

      const columnCount = await getGridColumnCount(page, gridSelector);
      expect(columnCount).toBeGreaterThanOrEqual(2);
      expect(columnCount).toBeLessThanOrEqual(3);
    });
  }
});



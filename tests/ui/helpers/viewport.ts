import { Page } from '@playwright/test';

/**
 * Viewport Helper Functions
 * 
 * Provides reusable viewport resize functions for responsive testing
 */

/**
 * Resize to mobile viewport (iPhone 12)
 * @param page Playwright page instance
 */
export async function resizeToMobile(page: Page): Promise<void> {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.waitForTimeout(300); // Wait for layout recalculation
}

/**
 * Resize to tablet viewport (iPad)
 * @param page Playwright page instance
 */
export async function resizeToTablet(page: Page): Promise<void> {
  await page.setViewportSize({ width: 768, height: 1024 });
  await page.waitForTimeout(300);
}

/**
 * Resize to desktop viewport
 * @param page Playwright page instance
 */
export async function resizeToDesktop(page: Page): Promise<void> {
  await page.setViewportSize({ width: 1280, height: 720 });
  await page.waitForTimeout(300);
}

/**
 * Resize to large desktop viewport
 * @param page Playwright page instance
 */
export async function resizeToLargeDesktop(page: Page): Promise<void> {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.waitForTimeout(300);
}

/**
 * Check if horizontal scroll exists
 * @param page Playwright page instance
 * @returns true if horizontal scroll exists
 */
export async function hasHorizontalScroll(page: Page): Promise<boolean> {
  return await page.evaluate(() => {
    return document.body.scrollWidth > window.innerWidth;
  });
}

/**
 * Get computed grid column count
 * @param page Playwright page instance
 * @param selector CSS selector for grid element
 * @returns Number of columns
 */
export async function getGridColumnCount(page: Page, selector: string): Promise<number> {
  return await page.evaluate((sel) => {
    const element = document.querySelector(sel);
    if (!element) return 0;
    const style = window.getComputedStyle(element);
    const gridTemplateColumns = style.gridTemplateColumns;
    if (!gridTemplateColumns || gridTemplateColumns === 'none') return 0;
    return gridTemplateColumns.split(' ').length;
  }, selector);
}

/**
 * Check if element is visible in viewport
 * @param page Playwright page instance
 * @param selector CSS selector
 * @returns true if element is visible
 */
export async function isElementVisible(page: Page, selector: string): Promise<boolean> {
  const element = page.locator(selector).first();
  return await element.isVisible().catch(() => false);
}


/**
 * Management Module UI Tests
 * 
 * ROUND 28: Management module (apartman yönetimi) UI / JS / Alpine hatalarına karşı testler
 * 
 * Tests the management module pages for console errors, JS errors, and basic UI functionality.
 */

import { test, expect } from '@playwright/test';

const BASE_URL = process.env.BASE_URL || 'https://www.kuretemizlik.com/app';

/**
 * Login as admin user
 */
async function loginAsAdmin(page: any) {
  const username = process.env.CRAWL_ADMIN_USERNAME || 
                   process.env.ADMIN_USERNAME || 
                   'admin';
  const password = process.env.CRAWL_ADMIN_PASSWORD || 
                   process.env.ADMIN_PASSWORD || 
                   '12dream21'; // ONLY FOR LOCAL QA – DO NOT USE IN SERVER CONFIG

  const loginBaseUrl = BASE_URL.replace(/\/app\/?$/, '');
  await page.goto(`${loginBaseUrl}/login`, { waitUntil: 'networkidle' });
  
  await page.fill('#username, input[name="username"]', username);
  await page.fill('#password, input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  
  // Wait a bit for redirect
  await page.waitForTimeout(2000);
}

test.describe('Management module (admin role)', () => {
  test('management dashboard loads without console errors', async ({ page }) => {
    const consoleErrors: string[] = [];
    
    page.on('console', (msg: any) => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
    
    page.on('pageerror', (error: Error) => {
      consoleErrors.push(error.message);
    });

    await loginAsAdmin(page);
    await page.goto(`${BASE_URL}/management/dashboard?header_mode=management`, { 
      waitUntil: 'networkidle',
      timeout: 30000 
    });

    expect(page.url()).toContain('/management/dashboard');
    expect(consoleErrors).toEqual([]);
  });

  test('residents list loads without JS errors', async ({ page }) => {
    const consoleErrors: string[] = [];
    
    page.on('console', (msg: any) => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
    
    page.on('pageerror', (error: Error) => {
      consoleErrors.push(error.message);
    });

    await loginAsAdmin(page);
    await page.goto(`${BASE_URL}/management/residents`, { 
      waitUntil: 'networkidle',
      timeout: 30000 
    });

    // Basit bir UI doğrulaması: tablo veya filtre barı gibi
    // Selector'lar sayfaya göre ayarlanabilir
    const hasTable = await page.locator('table, [data-testid="residents-table"], .residents-table, [class*="table"]').count();
    const hasContent = await page.locator('body').textContent();
    
    // En azından sayfa yüklendiğini doğrula
    expect(hasContent).toBeTruthy();
    expect(consoleErrors).toEqual([]);
  });

  test('management module pages return 200 status', async ({ page }) => {
    await loginAsAdmin(page);
    
    const managementPages = [
      '/management/dashboard?header_mode=management',
      '/management/residents',
    ];
    
    for (const path of managementPages) {
      const response = await page.goto(`${BASE_URL}${path}`, { 
        waitUntil: 'networkidle',
        timeout: 30000 
      });
      
      expect(response?.status()).toBe(200);
    }
  });
});


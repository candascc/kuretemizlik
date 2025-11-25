import { test, expect } from '@playwright/test';

const PROD_BASE_URL = process.env.PROD_BASE_URL || 'https://www.kuretemizlik.com/app';

test.describe('Production Marker Check (ROUND 37)', () => {
  test('Jobs New - HTML Marker Check', async ({ page }) => {
    // Navigate to jobs/new
    await page.goto(`${PROD_BASE_URL}/jobs/new`, { waitUntil: 'networkidle' });
    
    // Get HTML content
    const html = await page.content();
    
    // Check for marker comment
    expect(html).toContain('KUREAPP_R36_MARKER_JOBS_VIEW_V1');
    
    // Check status (should be 200, not 500)
    const response = await page.goto(`${PROD_BASE_URL}/jobs/new`, { waitUntil: 'networkidle' });
    expect(response?.status()).toBe(200);
  });

  test('Reports - HTML Marker Check (after redirect)', async ({ page }) => {
    // Navigate to reports (will redirect to /reports/financial)
    await page.goto(`${PROD_BASE_URL}/reports`, { waitUntil: 'networkidle' });
    
    // Wait for redirect to complete
    await page.waitForLoadState('networkidle');
    
    // Get HTML content (after redirect)
    const html = await page.content();
    
    // Check for marker comment
    expect(html).toContain('KUREAPP_R36_MARKER_REPORTS_VIEW_V1');
    
    // Check status (should be 200, not 403)
    const response = await page.goto(`${PROD_BASE_URL}/reports`, { waitUntil: 'networkidle' });
    expect(response?.status()).toBe(200);
  });

  test('Health - JSON Marker Check', async ({ request }) => {
    // Make direct HTTP request to /health
    const response = await request.get(`${PROD_BASE_URL}/health`);
    
    // Check status
    expect(response.status()).toBe(200);
    
    // Check Content-Type
    const contentType = response.headers()['content-type'] || '';
    expect(contentType).toContain('application/json');
    
    // Parse JSON
    const json = await response.json();
    
    // Check for marker field
    expect(json).toHaveProperty('marker');
    expect(json.marker).toBe('KUREAPP_R36_MARKER_HEALTH_JSON_V1');
  });
});


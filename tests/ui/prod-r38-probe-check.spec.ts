import { test, expect } from '@playwright/test';

const PROD_BASE_URL = process.env.PROD_BASE_URL || 'https://www.kuretemizlik.com/app';

test.describe('ROUND 38 - Runtime Probe Check', () => {
  test('Atlas Probe - Verify PHP Environment', async ({ request }) => {
    const response = await request.get(`${PROD_BASE_URL}/atlas_probe.php`);
    expect(response.status()).toBe(200);
    const text = await response.text();
    expect(text).toContain('KUREAPP_ATLAS_PROBE_R37');
    expect(text).toContain('PHP:');
  });

  test('Health Endpoint - Trigger Probe Log', async ({ request }) => {
    // Call /health with __r38=1 to trigger probe logging
    const response = await request.get(`${PROD_BASE_URL}/health?__r38=1`);
    expect(response.status()).toBe(200);
    // Note: We can't directly read log files from Playwright, but we can verify the endpoint responded
    // The log files will be checked separately via FTP or server access
  });

  test('Jobs New - Trigger Probe Log (unauthenticated)', async ({ request }) => {
    // Call /jobs/new with __r38=1 to trigger probe logging
    const response = await request.get(`${PROD_BASE_URL}/jobs/new?__r38=1`);
    // May return 500, 302, or 200 depending on auth state - that's OK for probe
    // We just want to trigger the log
    expect([200, 302, 500]).toContain(response.status());
  });

  test('Reports - Trigger Probe Log (unauthenticated)', async ({ request }) => {
    // Call /reports with __r38=1 to trigger probe logging
    const response = await request.get(`${PROD_BASE_URL}/reports?__r38=1`);
    // May return 403, 302, or 200 depending on auth state - that's OK for probe
    // We just want to trigger the log
    expect([200, 302, 403]).toContain(response.status());
  });
});


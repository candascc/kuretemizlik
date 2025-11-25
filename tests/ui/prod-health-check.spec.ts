import { test, expect } from '@playwright/test';

const PROD_BASE_URL = process.env.PROD_BASE_URL || 'https://www.kuretemizlik.com/app';

test.describe('ROUND 39 - Health Endpoint JSON-Only Check', () => {
  test('Health Endpoint - JSON Only, No HTML', async ({ request }) => {
    const response = await request.get(`${PROD_BASE_URL}/health`);
    
    // Check status
    expect(response.status()).toBe(200);
    
    // Check Content-Type - MUST be application/json
    const contentType = response.headers()['content-type'] || '';
    expect(contentType).toContain('application/json');
    
    // Parse JSON - should not throw
    const json = await response.json();
    
    // Check required fields
    expect(json).toHaveProperty('status');
    expect(json).toHaveProperty('build');
    expect(json).toHaveProperty('time');
    expect(json).toHaveProperty('marker');
    
    // Check marker value
    expect(json.marker).toBe('KUREAPP_R36_MARKER_HEALTH_JSON_V1');
    
    // Check status is valid
    expect(['ok', 'error', 'degraded', 'healthy']).toContain(json.status);
    
    // Verify response is NOT HTML (should not contain HTML tags)
    const text = await response.text();
    expect(text).not.toContain('<!DOCTYPE');
    expect(text).not.toContain('<html');
    expect(text).not.toContain('<body');
  });

  test('Health Endpoint - Quick Check', async ({ request }) => {
    const response = await request.get(`${PROD_BASE_URL}/health?quick=1`);
    
    // Check status
    expect([200, 503]).toContain(response.status());
    
    // Check Content-Type - MUST be application/json
    const contentType = response.headers()['content-type'] || '';
    expect(contentType).toContain('application/json');
    
    // Parse JSON - should not throw
    const json = await response.json();
    
    // Check required fields
    expect(json).toHaveProperty('status');
    expect(json).toHaveProperty('marker');
    expect(json.marker).toBe('KUREAPP_R36_MARKER_HEALTH_JSON_V1');
  });
});


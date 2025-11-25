import { Page } from '@playwright/test';

/**
 * Test Data Helper Functions
 * 
 * Provides reusable functions for creating and cleaning up test data
 */

/**
 * Generate unique test identifier
 */
export function generateTestId(): string {
  return `test-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Create a building via UI
 * @param page Playwright page instance
 * @param name Building name (optional, defaults to test ID)
 * @returns Building name or null if creation failed
 */
export async function createBuildingViaUI(
  page: Page,
  name?: string
): Promise<string | null> {
  const buildingName = name || `Test Building ${generateTestId()}`;
  
  try {
    // Navigate to buildings page
    await page.goto('/buildings');
    await page.waitForTimeout(1000);
    
    // Click "New Building" button
    const newButton = page.locator('a:has-text("Yeni Bina"), a:has-text("Ekle"), a[href*="/buildings/new"]').first();
    if (await newButton.isVisible().catch(() => false)) {
      await newButton.click();
      await page.waitForTimeout(1000);
      
      // Fill building form
      const nameInput = page.locator('input[name="name"], input[placeholder*="Bina"]').first();
      if (await nameInput.isVisible().catch(() => false)) {
        await nameInput.fill(buildingName);
        
        // Fill other optional fields if they exist
        const addressInput = page.locator('input[name="address"], textarea[name="address"]').first();
        if (await addressInput.isVisible().catch(() => false)) {
          await addressInput.fill('Test Address');
        }
        
        // Submit form
        const submitButton = page.locator('button[type="submit"], button:has-text("Kaydet"), button:has-text("Oluştur")').first();
        await submitButton.click();
        await page.waitForTimeout(2000);
        
        // Check if creation was successful (redirect or success message)
        const currentUrl = page.url();
        if (currentUrl.includes('/buildings') && !currentUrl.includes('/new')) {
          return buildingName;
        }
      }
    }
  } catch (error) {
    console.error('Failed to create building:', error);
  }
  
  return null;
}

/**
 * Create a unit via UI
 * @param page Playwright page instance
 * @param buildingId Building ID (optional)
 * @param unitNumber Unit number (optional)
 * @returns Unit number or null if creation failed
 */
export async function createUnitViaUI(
  page: Page,
  buildingId?: number,
  unitNumber?: string
): Promise<string | null> {
  const unitNum = unitNumber || `Unit-${generateTestId().substr(0, 8)}`;
  
  try {
    // Navigate to units page
    const unitsUrl = buildingId ? `/units/new?building_id=${buildingId}` : '/units/new';
    await page.goto(unitsUrl);
    await page.waitForTimeout(1000);
    
    // Fill unit form
    const numberInput = page.locator('input[name="unit_number"], input[name="number"], input[placeholder*="Daire"]').first();
    if (await numberInput.isVisible().catch(() => false)) {
      await numberInput.fill(unitNum);
      
      // Select building if dropdown exists and buildingId not in URL
      if (!buildingId) {
        const buildingSelect = page.locator('select[name="building_id"]').first();
        if (await buildingSelect.isVisible().catch(() => false)) {
          const options = await buildingSelect.locator('option').all();
          if (options.length > 1) {
            // Select first non-empty option
            await buildingSelect.selectOption({ index: 1 });
          }
        }
      }
      
      // Submit form
      const submitButton = page.locator('button[type="submit"], button:has-text("Kaydet"), button:has-text("Oluştur")').first();
      await submitButton.click();
      await page.waitForTimeout(2000);
      
      // Check if creation was successful
      const currentUrl = page.url();
      if (currentUrl.includes('/units') && !currentUrl.includes('/new')) {
        return unitNum;
      }
    }
  } catch (error) {
    console.error('Failed to create unit:', error);
  }
  
  return null;
}

/**
 * Create a management fee via UI
 * @param page Playwright page instance
 * @param unitId Unit ID (optional)
 * @param amount Amount (optional, defaults to 100)
 * @returns true if creation was successful
 */
export async function createManagementFeeViaUI(
  page: Page,
  unitId?: number,
  amount: number = 100
): Promise<boolean> {
  try {
    // Navigate to management fees page
    const feesUrl = unitId ? `/management-fees/generate?unit_id=${unitId}` : '/management-fees/generate';
    await page.goto(feesUrl);
    await page.waitForTimeout(1000);
    
    // Fill fee form
    const amountInput = page.locator('input[name="amount"], input[type="number"]').first();
    if (await amountInput.isVisible().catch(() => false)) {
      await amountInput.fill(amount.toString());
      
      // Select unit if dropdown exists
      if (!unitId) {
        const unitSelect = page.locator('select[name="unit_id"]').first();
        if (await unitSelect.isVisible().catch(() => false)) {
          const options = await unitSelect.locator('option').all();
          if (options.length > 1) {
            await unitSelect.selectOption({ index: 1 });
          }
        }
      }
      
      // Fill description if exists
      const descInput = page.locator('textarea[name="description"], input[name="description"]').first();
      if (await descInput.isVisible().catch(() => false)) {
        await descInput.fill('Test Management Fee');
      }
      
      // Submit form
      const submitButton = page.locator('button[type="submit"], button:has-text("Oluştur"), button:has-text("Kaydet")').first();
      await submitButton.click();
      await page.waitForTimeout(2000);
      
      // Check if creation was successful
      const currentUrl = page.url();
      return currentUrl.includes('/management-fees') && !currentUrl.includes('/generate');
    }
  } catch (error) {
    console.error('Failed to create management fee:', error);
  }
  
  return false;
}

/**
 * Create a job/task via UI
 * @param page Playwright page instance
 * @param unitId Unit ID (optional)
 * @param title Job title (optional)
 * @returns true if creation was successful
 */
export async function createJobViaUI(
  page: Page,
  unitId?: number,
  title?: string
): Promise<boolean> {
  const jobTitle = title || `Test Job ${generateTestId().substr(0, 8)}`;
  
  try {
    // Navigate to jobs page
    const jobsUrl = unitId ? `/jobs/new?unit_id=${unitId}` : '/jobs/new';
    await page.goto(jobsUrl);
    await page.waitForTimeout(1000);
    
    // Fill job form
    const titleInput = page.locator('input[name="title"], input[name="name"], input[placeholder*="Görev"]').first();
    if (await titleInput.isVisible().catch(() => false)) {
      await titleInput.fill(jobTitle);
      
      // Select unit if dropdown exists
      if (!unitId) {
        const unitSelect = page.locator('select[name="unit_id"]').first();
        if (await unitSelect.isVisible().catch(() => false)) {
          const options = await unitSelect.locator('option').all();
          if (options.length > 1) {
            await unitSelect.selectOption({ index: 1 });
          }
        }
      }
      
      // Fill description if exists
      const descInput = page.locator('textarea[name="description"]').first();
      if (await descInput.isVisible().catch(() => false)) {
        await descInput.fill('Test job description');
      }
      
      // Submit form
      const submitButton = page.locator('button[type="submit"], button:has-text("Oluştur"), button:has-text("Kaydet")').first();
      await submitButton.click();
      await page.waitForTimeout(2000);
      
      // Check if creation was successful
      const currentUrl = page.url();
      return currentUrl.includes('/jobs') && !currentUrl.includes('/new');
    }
  } catch (error) {
    console.error('Failed to create job:', error);
  }
  
  return false;
}

/**
 * Cleanup test data (delete created items)
 * Note: This is a placeholder - actual cleanup depends on UI delete functionality
 * @param page Playwright page instance
 * @param itemType Type of item to delete ('building', 'unit', 'fee', 'job')
 * @param itemId Item ID or identifier
 */
export async function cleanupTestData(
  page: Page,
  itemType: 'building' | 'unit' | 'fee' | 'job',
  itemId: string | number
): Promise<void> {
  // This is a placeholder - actual implementation depends on delete UI
  // For now, we rely on test environment periodic cleanup
  console.log(`Cleanup placeholder: ${itemType} with ID ${itemId}`);
}

/**
 * Wait for element to appear and be stable
 * @param page Playwright page instance
 * @param selector CSS selector
 * @param timeout Timeout in milliseconds
 */
export async function waitForStableElement(
  page: Page,
  selector: string,
  timeout: number = 5000
): Promise<void> {
  await page.waitForSelector(selector, { state: 'visible', timeout });
  await page.waitForTimeout(500); // Additional wait for stability
}

/**
 * Seed basic test data via API (if available)
 * 
 * This is a placeholder for future API-based test data seeding.
 * Currently, test data is created via UI, but this function can be
 * extended to use API endpoints for faster test setup.
 * 
 * @param page Playwright page instance
 * @param dataType Type of data to seed ('building', 'unit', 'job', 'fee')
 * @param options Optional parameters for data creation
 * @returns Created data ID or null if not implemented
 */
export async function seedBasicTestDataViaAPI(
  page: Page,
  dataType: 'building' | 'unit' | 'job' | 'fee',
  options?: Record<string, any>
): Promise<string | number | null> {
  const baseURL = process.env.BASE_URL || 'http://localhost/app';
  const testEndpoint = `${baseURL}/tests/seed`;
  
  try {
    // Check if test seeding endpoint exists
    const response = await page.request.get(testEndpoint, {
      headers: { 'Accept': 'application/json' }
    });
    
    if (response.ok()) {
      // Endpoint exists - try to seed data
      const seedResponse = await page.request.post(testEndpoint, {
        data: {
          type: dataType,
          ...options
        },
        headers: { 'Content-Type': 'application/json' }
      });
      
      if (seedResponse.ok()) {
        const result = await seedResponse.json();
        return result.id || null;
      }
    }
  } catch (error) {
    // Endpoint doesn't exist or not available - fallback to UI-based creation
    console.log(`API seeding not available for ${dataType}, falling back to UI-based creation`);
  }
  
  // Fallback: Return null to indicate UI-based creation should be used
  return null;
}

/**
 * Cleanup test data via API (if available)
 * 
 * This is a placeholder for future API-based test data cleanup.
 * 
 * @param page Playwright page instance
 * @param dataType Type of data to cleanup
 * @param dataId ID of the data to cleanup
 */
export async function cleanupTestDataViaAPI(
  page: Page,
  dataType: 'building' | 'unit' | 'job' | 'fee',
  dataId: string | number
): Promise<boolean> {
  const baseURL = process.env.BASE_URL || 'http://localhost/app';
  const cleanupEndpoint = `${baseURL}/tests/cleanup`;
  
  try {
    const response = await page.request.post(cleanupEndpoint, {
      data: {
        type: dataType,
        id: dataId
      },
      headers: { 'Content-Type': 'application/json' }
    });
    
    return response.ok();
  } catch (error) {
    // Endpoint doesn't exist - cleanup will be handled by test environment reset
    console.log(`API cleanup not available for ${dataType} with ID ${dataId}`);
    return false;
  }
}


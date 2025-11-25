import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright Configuration for Küre Temizlik UI Tests
 * 
 * Test Coverage:
 * - Authentication flows
 * - Dashboard & KPI cards responsive behavior
 * - Units list/detail pages
 * - Finance forms
 * - Layout components (navbar, footer)
 * - Edge cases (empty states, long content)
 */

export default defineConfig({
  testDir: './tests/ui',
  
  /* Maximum time one test can run for */
  timeout: 30 * 1000,
  
  /* Run tests in files in parallel */
  fullyParallel: true,
  
  /* Fail the build on CI if you accidentally left test.only in the source code */
  forbidOnly: !!process.env.CI,
  
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  
  /* Opt out of parallel tests on CI */
  workers: process.env.CI ? 1 : undefined,
  
  /* Reporter to use */
  reporter: [
    ['list'],
    ['html', { outputFolder: 'tests/ui/reports' }],
    ['json', { outputFile: 'tests/ui/results.json' }]
  ],
  
  /* Shared settings for all projects */
  use: {
    /* Base URL to use in actions like `await page.goto('/')` */
    /* ROUND 8: Local environment için default baseURL güncellendi */
    baseURL: process.env.BASE_URL || 'http://kuretemizlik.local/app',
    
    /* Collect trace when retrying the failed test */
    trace: 'on-first-retry',
    
    /* Screenshot on failure */
    screenshot: {
      mode: 'only-on-failure',
      fullPage: false, // Component-level screenshots for visual regression
    },
    
    /* Video on failure */
    video: 'retain-on-failure',
  },
  
  /* Visual regression snapshot directory */
  expect: {
    /* Maximum number of pixels that can differ */
    toHaveScreenshot: {
      maxDiffPixels: 100,
      threshold: 0.2,
    },
  },

  /* Configure projects for major browsers and viewports */
  /* ROUND 8: Cross-browser testler (Firefox/WebKit) environment variable ile opt-in hale getirildi */
  projects: [
    // Mobile - iPhone 12
    {
      name: 'mobile-chromium',
      use: {
        ...devices['iPhone 12'],
        viewport: { width: 390, height: 844 },
      },
    },

    // Tablet - iPad
    {
      name: 'tablet-chromium',
      use: {
        ...devices['iPad Pro'],
        viewport: { width: 1024, height: 1366 },
      },
    },

    // Desktop - Standard
    {
      name: 'desktop-chromium',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1280, height: 720 },
      },
    },

    // Desktop - Large
    {
      name: 'desktop-large-chromium',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1440, height: 900 },
      },
    },

    // Desktop - Firefox (Opt-in: ENABLE_CROSS_BROWSER=1)
    ...(process.env.ENABLE_CROSS_BROWSER === '1' ? [{
      name: 'desktop-firefox',
      use: {
        ...devices['Desktop Firefox'],
        viewport: { width: 1280, height: 720 },
      },
    }] : []),

    // Desktop - WebKit (Safari) (Opt-in: ENABLE_CROSS_BROWSER=1)
    ...(process.env.ENABLE_CROSS_BROWSER === '1' ? [{
      name: 'desktop-webkit',
      use: {
        ...devices['Desktop Safari'],
        viewport: { width: 1280, height: 720 },
      },
    }] : []),
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'php -S localhost:8000',
  //   url: 'http://localhost:8000',
  //   reuseExistingServer: !process.env.CI,
  // },
});


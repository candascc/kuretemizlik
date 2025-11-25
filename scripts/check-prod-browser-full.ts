/**
 * Production Browser Check Script - Full Nav Mode
 * 
 * ROUND 20: Full Navigation Coverage
 * 
 * This script logs into production, navigates through all main menu items,
 * and collects console + network errors from each page.
 * 
 * Usage:
 *   PROD_BASE_URL=https://www.kuretemizlik.com/app npm run check:prod:browser:full
 */

import { chromium, Browser, Page } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';

const baseURL = process.env.PROD_BASE_URL || 'https://www.kuretemizlik.com/app';
const adminEmail = process.env.PROD_ADMIN_EMAIL || 'admin@kuretemizlik.com';
const adminPassword = process.env.PROD_ADMIN_PASSWORD || 'admin123';

interface LogEntry {
  route: string;
  level: 'error' | 'warn' | 'info' | 'log';
  category: 'frontend' | 'backend' | 'infra';
  message: string;
  stack?: string;
  timestamp: string;
  url: string;
}

interface NavResult {
  url: string;
  routeName: string;
  status: number;
  title?: string;
  logs: LogEntry[];
  timestamp: string;
}

interface FullNavReport {
  baseURL: string;
  timestamp: string;
  results: NavResult[];
  summary: {
    total: number;
    ok: number;
    warning: number;
    fail: number;
    totalErrors: number;
    totalWarnings: number;
  };
}

/**
 * Extract navigation links from the page
 */
async function extractNavLinks(page: Page, baseURL: string): Promise<string[]> {
  const links: string[] = [];
  
  try {
    // Try multiple selectors for navigation links
    const selectors = [
      'nav a[href^="/app"]',
      '.sidebar a[href^="/app"]',
      '[data-test="nav-link"]',
      '.navbar a[href^="/app"]',
      'aside a[href^="/app"]',
      'header a[href^="/app"]'
    ];
    
    for (const selector of selectors) {
      const elements = await page.locator(selector).all();
      for (const element of elements) {
        const href = await element.getAttribute('href');
        if (href && href.startsWith('/app')) {
          // Normalize URL
          let normalized = href;
          if (!normalized.startsWith('http')) {
            normalized = baseURL + normalized.replace(/^\/app/, '');
          }
          if (!links.includes(normalized)) {
            links.push(normalized);
          }
        }
      }
    }
    
    // Also check for common routes
    const commonRoutes = [
      '/calendar',
      '/jobs',
      '/jobs/new',
      '/recurring',
      '/recurring/new',
      '/customers',
      '/finance',
      '/units',
      '/settings',
      '/dashboard'
    ];
    
    for (const route of commonRoutes) {
      const url = baseURL + route;
      if (!links.includes(url)) {
        links.push(url);
      }
    }
  } catch (error) {
    console.error('Error extracting nav links:', error);
  }
  
  return links;
}

/**
 * Login to the application
 */
async function login(page: Page, email: string, password: string): Promise<boolean> {
  try {
    await page.goto(`${baseURL}/login`);
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    // Find and fill login form
    const emailInput = page.locator('input[name="email"], input[name="username"], input[type="email"]').first();
    const passwordInput = page.locator('input[name="password"], input[type="password"]').first();
    const submitButton = page.locator('button[type="submit"], input[type="submit"]').first();
    
    if (await emailInput.count() === 0 || await passwordInput.count() === 0) {
      console.error('Login form not found');
      return false;
    }
    
    await emailInput.fill(email);
    await passwordInput.fill(password);
    await submitButton.click();
    
    // Wait for redirect (either to dashboard or /)
    await page.waitForURL(/\/(dashboard|app\/?)$/, { timeout: 10000 });
    
    return true;
  } catch (error) {
    console.error('Login failed:', error);
    return false;
  }
}

/**
 * Check a single URL and collect logs
 */
async function checkUrl(page: Page, url: string, routeName: string): Promise<NavResult> {
  const logs: LogEntry[] = [];
  
  // Set up console and error listeners
  page.on('console', (msg) => {
    const level = msg.type() as 'error' | 'warn' | 'info' | 'log';
    if (level === 'error' || level === 'warn') {
      logs.push({
        route: routeName,
        level,
        category: 'frontend',
        message: msg.text(),
        timestamp: new Date().toISOString(),
        url
      });
    }
  });
  
  page.on('pageerror', (error) => {
    logs.push({
      route: routeName,
      level: 'error',
      category: 'frontend',
      message: error.message,
      stack: error.stack,
      timestamp: new Date().toISOString(),
      url
    });
  });
  
  page.on('response', (response) => {
    const status = response.status();
    if (status >= 400) {
      logs.push({
        route: routeName,
        level: status >= 500 ? 'error' : 'warn',
        category: status >= 500 ? 'backend' : 'infra',
        message: `HTTP ${status} ${response.statusText()}`,
        timestamp: new Date().toISOString(),
        url: response.url()
      });
    }
  });
  
  try {
    const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
    const status = response?.status() || 0;
    const title = await page.title();
    
    // Wait a bit for any async initialization
    await page.waitForTimeout(2000);
    
    return {
      url,
      routeName,
      status,
      title,
      logs,
      timestamp: new Date().toISOString()
    };
  } catch (error) {
    logs.push({
      route: routeName,
      level: 'error',
      category: 'backend',
      message: `Navigation failed: ${error instanceof Error ? error.message : String(error)}`,
      timestamp: new Date().toISOString(),
      url
    });
    
    return {
      url,
      routeName,
      status: 0,
      logs,
      timestamp: new Date().toISOString()
    };
  }
}

/**
 * Main execution
 */
async function main() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  
  console.log(`\n🔍 ROUND 20: Full Nav Browser Check`);
  console.log(`Base URL: ${baseURL}\n`);
  
  // Login
  console.log('📝 Logging in...');
  const loginSuccess = await login(page, adminEmail, adminPassword);
  
  if (!loginSuccess) {
    console.error('❌ Login failed. Exiting.');
    await browser.close();
    process.exit(1);
  }
  
  console.log('✅ Login successful\n');
  
  // Extract navigation links
  console.log('🔗 Extracting navigation links...');
  const navLinks = await extractNavLinks(page, baseURL);
  console.log(`Found ${navLinks.length} navigation links\n`);
  
  const results: NavResult[] = [];
  
  // Check each URL
  for (let i = 0; i < navLinks.length; i++) {
    const url = navLinks[i];
    const routeName = url.replace(baseURL, '').replace(/^\//, '') || 'root';
    
    console.log(`[${i + 1}/${navLinks.length}] Checking: ${routeName}`);
    
    // Create a new page context for each URL to avoid state pollution
    const newPage = await context.newPage();
    const result = await checkUrl(newPage, url, routeName);
    results.push(result);
    await newPage.close();
    
    const errorCount = result.logs.filter(l => l.level === 'error').length;
    const warnCount = result.logs.filter(l => l.level === 'warn').length;
    
    if (result.status >= 500) {
      console.log(`  ❌ Status: ${result.status} (${errorCount} errors, ${warnCount} warnings)`);
    } else if (result.status >= 400 || errorCount > 0) {
      console.log(`  ⚠️  Status: ${result.status} (${errorCount} errors, ${warnCount} warnings)`);
    } else {
      console.log(`  ✅ Status: ${result.status} (${errorCount} errors, ${warnCount} warnings)`);
    }
  }
  
  await browser.close();
  
  // Generate summary
  const summary = {
    total: results.length,
    ok: results.filter(r => r.status === 200 && r.logs.filter(l => l.level === 'error').length === 0).length,
    warning: results.filter(r => r.status >= 400 && r.status < 500 || r.logs.filter(l => l.level === 'warn').length > 0).length,
    fail: results.filter(r => r.status >= 500 || r.logs.filter(l => l.level === 'error').length > 0).length,
    totalErrors: results.reduce((sum, r) => sum + r.logs.filter(l => l.level === 'error').length, 0),
    totalWarnings: results.reduce((sum, r) => sum + r.logs.filter(l => l.level === 'warn').length, 0)
  };
  
  const report: FullNavReport = {
    baseURL,
    timestamp: new Date().toISOString(),
    results,
    summary
  };
  
  // Write JSON report
  const jsonPath = path.join(process.cwd(), 'PRODUCTION_BROWSER_CHECK_FULL_NAV.json');
  fs.writeFileSync(jsonPath, JSON.stringify(report, null, 2));
  console.log(`\n📄 JSON report written: ${jsonPath}`);
  
  // Write Markdown report
  const mdPath = path.join(process.cwd(), 'PRODUCTION_BROWSER_CHECK_FULL_NAV.md');
  let md = `# Production Browser Check - Full Nav Report\n\n`;
  md += `**Date:** ${new Date().toISOString()}\n`;
  md += `**Base URL:** ${baseURL}\n\n`;
  md += `## Summary\n\n`;
  md += `- **Total Routes:** ${summary.total}\n`;
  md += `- **OK:** ${summary.ok}\n`;
  md += `- **Warnings:** ${summary.warning}\n`;
  md += `- **Failed:** ${summary.fail}\n`;
  md += `- **Total Errors:** ${summary.totalErrors}\n`;
  md += `- **Total Warnings:** ${summary.totalWarnings}\n\n`;
  md += `## Results\n\n`;
  
  for (const result of results) {
    const errorCount = result.logs.filter(l => l.level === 'error').length;
    const warnCount = result.logs.filter(l => l.level === 'warn').length;
    const statusIcon = result.status >= 500 ? '❌' : result.status >= 400 || errorCount > 0 ? '⚠️' : '✅';
    
    md += `### ${statusIcon} ${result.routeName}\n\n`;
    md += `- **URL:** ${result.url}\n`;
    md += `- **Status:** ${result.status}\n`;
    md += `- **Title:** ${result.title || 'N/A'}\n`;
    md += `- **Errors:** ${errorCount}\n`;
    md += `- **Warnings:** ${warnCount}\n\n`;
    
    if (result.logs.length > 0) {
      md += `#### Logs\n\n`;
      for (const log of result.logs) {
        md += `- **${log.level.toUpperCase()}** [${log.category}]: ${log.message}\n`;
        if (log.stack) {
          md += `  \`\`\`\n  ${log.stack.split('\n').slice(0, 3).join('\n  ')}\n  \`\`\`\n`;
        }
      }
      md += `\n`;
    }
  }
  
  fs.writeFileSync(mdPath, md);
  console.log(`📄 Markdown report written: ${mdPath}`);
  
  console.log(`\n✅ Full nav check completed!`);
  console.log(`Summary: ${summary.ok} OK, ${summary.warning} warnings, ${summary.fail} failed`);
  
  // Exit with appropriate code
  if (summary.fail > 0) {
    process.exit(1);
  } else if (summary.warning > 0) {
    process.exit(0); // Warnings are non-fatal
  } else {
    process.exit(0);
  }
}

main().catch(error => {
  console.error('Fatal error:', error);
  process.exit(1);
});



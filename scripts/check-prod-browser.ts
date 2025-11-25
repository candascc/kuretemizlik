/**
 * Production Browser Check Script (Max Harvest Mode)
 * 
 * ROUND 14: Production Console Harvest & Cleanup Prep
 * 
 * This script checks production environment via HTTP requests only.
 * No SSH/terminal access, no file system access, no DB access.
 * 
 * ROUND 14 Changes:
 * - Collects ALL console.error, console.warn, console.info messages (no whitelist)
 * - Collects network 4xx/5xx errors
 * - Structured pattern extraction
 * - Category assignment (security, performance, a11y, DX, infra)
 * 
 * Usage:
 *   PROD_BASE_URL=https://www.kuretemizlik.com/app npm run check:prod:browser
 */

import { chromium, Browser, Page } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';

const baseURL = process.env.PROD_BASE_URL || 'https://www.kuretemizlik.com/app';
const browserProject = 'desktop-chromium'; // Default: Chromium (can be extended for multi-browser)

interface ConsoleLogEntry {
  level: 'error' | 'warn' | 'info' | 'log' | 'debug';
  category: 'console' | 'network';
  message: string;
  pattern?: string;
  stackSnippet?: string;
  source?: string;
  timestamp: string;
}

interface NetworkErrorEntry {
  level: 'error' | 'warn';
  category: 'network';
  url: string;
  status: number;
  method: string;
  pattern?: string;
  message: string;
  timestamp: string;
}

interface CheckResult {
  url: string;
  routeName?: string; // Estimated route name (e.g., 'dashboard', 'login', 'jobs.new')
  status: number;
  title?: string;
  h1?: string;
  consoleLogs: ConsoleLogEntry[];
  networkErrors: NetworkErrorEntry[];
  timestamp: string;
  browserProject: string;
}

interface ReportData {
  baseURL: string;
  timestamp: string;
  browserProject: string;
  results: CheckResult[];
  summary: {
    total: number;
    ok: number;
    warning: number;
    fail: number;
    totalErrors: number;
    totalWarnings: number;
    totalNetworkErrors: number;
  };
  patterns: {
    [pattern: string]: {
      count: number;
      level: 'error' | 'warn';
      category: string;
      exampleMessage: string;
      examplePage: string;
    };
  };
}

const urlsToCheck = [
  { url: `${baseURL}/`, routeName: 'dashboard' },
  { url: `${baseURL}/login`, routeName: 'login' },
  { url: `${baseURL}/jobs/new`, routeName: 'jobs.new' },
  { url: `${baseURL}/health`, routeName: 'health' },
  { url: `${baseURL}/dashboard`, routeName: 'dashboard' },
  { url: `${baseURL}/finance`, routeName: 'finance' },
  { url: `${baseURL}/portal/login`, routeName: 'portal.login' },
  { url: `${baseURL}/units`, routeName: 'units' },
  { url: `${baseURL}/settings`, routeName: 'settings' },
];

/**
 * Extract pattern from console/network message
 * ROUND 14: Simple heuristic-based pattern extraction
 */
function extractPattern(message: string, category: 'console' | 'network'): string {
  const msg = message.toLowerCase();
  
  // Network patterns
  if (category === 'network') {
    if (msg.includes('404')) return 'NETWORK_404';
    if (msg.includes('403')) return 'NETWORK_403';
    if (msg.includes('401')) return 'NETWORK_401';
    if (msg.includes('500')) return 'NETWORK_500';
    if (msg.includes('502')) return 'NETWORK_502';
    if (msg.includes('503')) return 'NETWORK_503';
    if (msg.includes('timeout')) return 'NETWORK_TIMEOUT';
    if (msg.includes('cors')) return 'NETWORK_CORS';
    if (msg.includes('failed') && msg.includes('fetch')) return 'NETWORK_FETCH_FAILED';
  }
  
  // Console patterns
  if (category === 'console') {
    // Tailwind CDN warning
    if (msg.includes('tailwindcss.com') && msg.includes('production')) return 'TAILWIND_CDN_PROD_WARNING';
    
    // Alpine.js errors
    if (msg.includes('alpine') && msg.includes('expression error')) return 'ALPINE_EXPRESSION_ERROR';
    if (msg.includes('alpine') && msg.includes('nextcursor')) return 'ALPINE_REFERENCEERROR_NEXTCURSOR';
    if (msg.includes('alpine') && msg.includes('not defined')) return 'ALPINE_REFERENCEERROR';
    
    // JavaScript errors
    if (msg.includes('referenceerror') && msg.includes('not defined')) return 'JS_REFERENCEERROR';
    if (msg.includes('typeerror')) return 'JS_TYPEERROR';
    if (msg.includes('syntaxerror')) return 'JS_SYNTAXERROR';
    
    // Service Worker
    if (msg.includes('service worker') || msg.includes('sw')) {
      if (msg.includes('precache') || msg.includes('pre-cache')) return 'SW_PRECACHE_FAILED';
      if (msg.includes('register')) return 'SW_REGISTER_FAILED';
      return 'SW_ERROR';
    }
    
    // Security
    if (msg.includes('csp') || msg.includes('content security policy')) return 'SECURITY_CSP_VIOLATION';
    if (msg.includes('mixed content')) return 'SECURITY_MIXED_CONTENT';
    
    // Performance
    if (msg.includes('slow') || msg.includes('performance')) return 'PERF_WARNING';
    if (msg.includes('memory')) return 'PERF_MEMORY';
    
    // Accessibility
    if (msg.includes('a11y') || msg.includes('accessibility')) return 'A11Y_WARNING';
    
    // Generic patterns
    if (msg.includes('deprecated')) return 'DX_DEPRECATED';
    if (msg.includes('console.error')) return 'DX_CONSOLE_ERROR';
  }
  
  return 'UNKNOWN';
}

/**
 * Assign category to pattern
 */
function getCategory(pattern: string): string {
  if (pattern.startsWith('NETWORK_')) return 'infra';
  if (pattern.startsWith('TAILWIND_')) return 'DX';
  if (pattern.startsWith('ALPINE_') || pattern.startsWith('JS_')) return 'UX';
  if (pattern.startsWith('SW_')) return 'infra';
  if (pattern.startsWith('SECURITY_')) return 'security';
  if (pattern.startsWith('PERF_')) return 'performance';
  if (pattern.startsWith('A11Y_')) return 'accessibility';
  if (pattern.startsWith('DX_')) return 'DX';
  return 'unknown';
}

async function checkURL(page: Page, urlEntry: { url: string; routeName?: string }): Promise<CheckResult> {
  const result: CheckResult = {
    url: urlEntry.url,
    routeName: urlEntry.routeName,
    status: 0,
    consoleLogs: [],
    networkErrors: [],
    timestamp: new Date().toISOString(),
    browserProject,
  };

  // ROUND 14: Collect ALL console messages (no whitelist)
  page.on('console', msg => {
    const type = msg.type();
    const text = msg.text();
    
    // Collect error, warn, info, log (all levels)
    if (['error', 'warn', 'info', 'log', 'debug'].includes(type)) {
      const level = type as 'error' | 'warn' | 'info' | 'log' | 'debug';
      const pattern = extractPattern(text, 'console');
      
      const entry: ConsoleLogEntry = {
        level,
        category: 'console',
        message: text.trim(),
        pattern,
        timestamp: new Date().toISOString(),
      };
      
      // Try to get stack trace
      try {
        const location = msg.location();
        if (location) {
          entry.source = `${location.url}:${location.lineNumber}:${location.columnNumber}`;
        }
      } catch (e) {
        // Ignore
      }
      
      result.consoleLogs.push(entry);
    }
  });

  // ROUND 14: Collect network 4xx/5xx errors
  page.on('response', response => {
    const status = response.status();
    if (status >= 400) {
      const request = response.request();
      const url = response.url();
      const method = request.method();
      
      const pattern = extractPattern(`HTTP ${status} ${method} ${url}`, 'network');
      
      const entry: NetworkErrorEntry = {
        level: status >= 500 ? 'error' : 'warn',
        category: 'network',
        url,
        status,
        method,
        pattern,
        message: `HTTP ${status} ${method} ${url}`,
        timestamp: new Date().toISOString(),
      };
      
      result.networkErrors.push(entry);
    }
  });

  // ROUND 14: Collect network failures (timeout, CORS, etc.)
  page.on('requestfailed', request => {
    const url = request.url();
    const failure = request.failure();
    
    if (failure) {
      const pattern = extractPattern(failure.errorText, 'network');
      
      const entry: NetworkErrorEntry = {
        level: 'error',
        category: 'network',
        url,
        status: 0,
        method: request.method(),
        pattern,
        message: `Network failure: ${failure.errorText} (${url})`,
        timestamp: new Date().toISOString(),
      };
      
      result.networkErrors.push(entry);
    }
  });

  try {
    const response = await page.goto(urlEntry.url, { waitUntil: 'networkidle', timeout: 30000 });
    result.status = response?.status() || 0;

    // Get page title
    try {
      result.title = await page.title();
    } catch (e) {
      // Ignore title errors
    }

    // Get H1 text
    try {
      const h1 = page.locator('h1').first();
      if (await h1.count() > 0) {
        result.h1 = await h1.textContent() || undefined;
      }
    } catch (e) {
      // Ignore H1 errors
    }

  } catch (error: any) {
    // Page load failed - add as network error
    const entry: NetworkErrorEntry = {
      level: 'error',
      category: 'network',
      url: urlEntry.url,
      status: 0,
      method: 'GET',
      pattern: 'NETWORK_PAGE_LOAD_FAILED',
      message: `Page load failed: ${error.message}`,
      timestamp: new Date().toISOString(),
    };
    result.networkErrors.push(entry);
    result.status = 0;
  }

  return result;
}

/**
 * Generate Markdown report with pattern analysis
 * ROUND 14: Enhanced report with pattern breakdown and top patterns
 */
function generateReport(data: ReportData): string {
  let md = `# Production Browser Check Report (Max Harvest Mode)\n\n`;
  md += `**ROUND 14: Production Console Harvest & Cleanup Prep**\n\n`;
  md += `**Base URL:** ${data.baseURL}\n\n`;
  md += `**Browser Project:** ${data.browserProject}\n\n`;
  md += `**Timestamp:** ${data.timestamp}\n\n`;
  
  md += `## Summary\n\n`;
  md += `- **Total Pages:** ${data.summary.total}\n`;
  md += `- ✅ **OK:** ${data.summary.ok}\n`;
  md += `- ⚠️ **WARNING:** ${data.summary.warning}\n`;
  md += `- ❌ **FAIL:** ${data.summary.fail}\n\n`;
  md += `- **Total Errors:** ${data.summary.totalErrors}\n`;
  md += `- **Total Warnings:** ${data.summary.totalWarnings}\n`;
  md += `- **Network Errors (4xx/5xx):** ${data.summary.totalNetworkErrors}\n\n`;
  md += `- **Unique Patterns:** ${Object.keys(data.patterns).length}\n\n`;
  md += `---\n\n`;

  // Top 20 Patterns Table
  md += `## Top 20 Patterns\n\n`;
  md += `| Pattern | Category | Level | Count | Example Message | Example Page |\n`;
  md += `|---------|----------|-------|-------|-----------------|--------------|\n`;
  
  const sortedPatterns = Object.entries(data.patterns)
    .sort((a, b) => b[1].count - a[1].count)
    .slice(0, 20);
  
  for (const [pattern, info] of sortedPatterns) {
    const exampleMsg = info.exampleMessage.length > 50 
      ? info.exampleMessage.substring(0, 47) + '...' 
      : info.exampleMessage;
    const examplePage = info.examplePage.replace(data.baseURL, '').substring(0, 30);
    md += `| \`${pattern}\` | ${info.category} | ${info.level} | ${info.count} | ${exampleMsg} | ${examplePage} |\n`;
  }
  
  md += `\n---\n\n`;

  // Page-by-page breakdown
  md += `## Page-by-Page Breakdown\n\n`;
  
  for (const result of data.results) {
    const totalIssues = result.consoleLogs.length + result.networkErrors.length;
    const errors = result.consoleLogs.filter(l => l.level === 'error').length + 
                   result.networkErrors.filter(n => n.level === 'error').length;
    const warnings = result.consoleLogs.filter(l => l.level === 'warn').length + 
                     result.networkErrors.filter(n => n.level === 'warn').length;
    
    const statusLabel = result.status >= 500 
      ? '❌ FAIL' 
      : errors > 0 
        ? '❌ FAIL' 
        : warnings > 0 
          ? '⚠️ WARNING' 
          : '✅ OK';

    md += `### ${result.routeName || result.url}\n\n`;
    md += `**URL:** \`${result.url}\`\n\n`;
    md += `**Status:** HTTP ${result.status} ${statusLabel}\n\n`;
    
    if (result.title) {
      md += `**Title:** ${result.title}\n\n`;
    }
    
    if (result.h1) {
      md += `**H1:** ${result.h1}\n\n`;
    }
    
    md += `**Issues:** ${totalIssues} (${errors} errors, ${warnings} warnings)\n\n`;

    if (result.consoleLogs.length > 0) {
      md += `#### Console Messages (${result.consoleLogs.length})\n\n`;
      
      // Group by pattern
      const byPattern: { [key: string]: ConsoleLogEntry[] } = {};
      for (const log of result.consoleLogs) {
        const pattern = log.pattern || 'UNKNOWN';
        if (!byPattern[pattern]) byPattern[pattern] = [];
        byPattern[pattern].push(log);
      }
      
      for (const [pattern, logs] of Object.entries(byPattern)) {
        md += `- **${pattern}** (${logs.length}): ${logs[0].message.substring(0, 100)}\n`;
      }
      md += `\n`;
    }

    if (result.networkErrors.length > 0) {
      md += `#### Network Errors (${result.networkErrors.length})\n\n`;
      
      // Group by pattern
      const byPattern: { [key: string]: NetworkErrorEntry[] } = {};
      for (const err of result.networkErrors) {
        const pattern = err.pattern || 'UNKNOWN';
        if (!byPattern[pattern]) byPattern[pattern] = [];
        byPattern[pattern].push(err);
      }
      
      for (const [pattern, errors] of Object.entries(byPattern)) {
        md += `- **${pattern}** (${errors.length}): ${errors[0].message.substring(0, 100)}\n`;
      }
      md += `\n`;
    }

    md += `---\n\n`;
  }

  // Overall assessment
  if (data.summary.fail > 0) {
    md += `## ❌ Overall Status: FAIL\n\n`;
    md += `Production smoke test FAILED. Critical errors detected.\n\n`;
  } else if (data.summary.warning > 0) {
    md += `## ⚠️ Overall Status: WARNING\n\n`;
    md += `Production smoke test passed with warnings.\n\n`;
  } else {
    md += `## ✅ Overall Status: OK\n\n`;
    md += `Production smoke test PASSED.\n\n`;
  }

  md += `---\n\n`;
  md += `**Note (ROUND 14):** Bu rapor max harvest modunda oluşturulmuştur. Hiçbir console warning susturulmamıştır. `;
  md += `Tüm error ve warning'ler toplanmış ve pattern bazında kategorize edilmiştir.\n`;

  return md;
}

async function main() {
  console.log(`Starting production browser check (MAX HARVEST MODE - ROUND 14)...`);
  console.log(`Base URL: ${baseURL}`);
  console.log(`Browser Project: ${browserProject}`);
  console.log(`URLs to check: ${urlsToCheck.length}\n`);

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  const results: CheckResult[] = [];

  for (const urlEntry of urlsToCheck) {
    console.log(`Checking: ${urlEntry.url} (route: ${urlEntry.routeName || 'unknown'})`);
    const result = await checkURL(page, urlEntry);
    results.push(result);
    
    const totalIssues = result.consoleLogs.length + result.networkErrors.length;
    const errors = result.consoleLogs.filter(l => l.level === 'error').length + 
                   result.networkErrors.filter(n => n.level === 'error').length;
    const warnings = result.consoleLogs.filter(l => l.level === 'warn').length + 
                     result.networkErrors.filter(n => n.level === 'warn').length;
    
    console.log(`  Status: ${result.status}, Console Logs: ${result.consoleLogs.length}, Network Errors: ${result.networkErrors.length} (${errors} errors, ${warnings} warnings)\n`);
  }

  await browser.close();

  // ROUND 14: Calculate summary with pattern aggregation
  const totalErrors = results.reduce((sum, r) => 
    sum + r.consoleLogs.filter(l => l.level === 'error').length + 
         r.networkErrors.filter(n => n.level === 'error').length, 0);
  const totalWarnings = results.reduce((sum, r) => 
    sum + r.consoleLogs.filter(l => l.level === 'warn').length + 
         r.networkErrors.filter(n => n.level === 'warn').length, 0);
  const totalNetworkErrors = results.reduce((sum, r) => 
    sum + r.networkErrors.length, 0);
  
  const summary = {
    total: results.length,
    ok: results.filter(r => r.status === 200 && 
                            r.consoleLogs.filter(l => l.level === 'error').length === 0 && 
                            r.networkErrors.filter(n => n.level === 'error').length === 0).length,
    warning: results.filter(r => {
      const hasErrors = r.consoleLogs.filter(l => l.level === 'error').length > 0 || 
                        r.networkErrors.filter(n => n.level === 'error').length > 0;
      const hasWarnings = r.consoleLogs.filter(l => l.level === 'warn').length > 0 || 
                          r.networkErrors.filter(n => n.level === 'warn').length > 0;
      return !hasErrors && hasWarnings;
    }).length,
    fail: results.filter(r => r.status >= 500 || 
                              r.consoleLogs.filter(l => l.level === 'error').length > 0 || 
                              r.networkErrors.filter(n => n.level === 'error').length > 0).length,
    totalErrors,
    totalWarnings,
    totalNetworkErrors,
  };

  // ROUND 14: Aggregate patterns
  const patterns: { [key: string]: {
    count: number;
    level: 'error' | 'warn';
    category: string;
    exampleMessage: string;
    examplePage: string;
  } } = {};
  
  for (const result of results) {
    for (const log of result.consoleLogs) {
      const pattern = log.pattern || 'UNKNOWN';
      if (!patterns[pattern]) {
        patterns[pattern] = {
          count: 0,
          level: log.level === 'error' ? 'error' : 'warn',
          category: getCategory(pattern),
          exampleMessage: log.message,
          examplePage: result.url,
        };
      }
      patterns[pattern].count++;
      // Update level to error if any entry is error
      if (log.level === 'error') {
        patterns[pattern].level = 'error';
      }
    }
    
    for (const err of result.networkErrors) {
      const pattern = err.pattern || 'UNKNOWN';
      if (!patterns[pattern]) {
        patterns[pattern] = {
          count: 0,
          level: err.level,
          category: getCategory(pattern),
          exampleMessage: err.message,
          examplePage: result.url,
        };
      }
      patterns[pattern].count++;
      if (err.level === 'error') {
        patterns[pattern].level = 'error';
      }
    }
  }

  const reportData: ReportData = {
    baseURL,
    timestamp: new Date().toISOString(),
    browserProject,
    results,
    summary,
    patterns,
  };

  // Write JSON report
  const jsonPath = path.join(process.cwd(), 'PRODUCTION_BROWSER_CHECK_REPORT.json');
  fs.writeFileSync(jsonPath, JSON.stringify(reportData, null, 2));
  console.log(`✅ JSON report written: ${jsonPath}`);

  // Write Markdown report
  const mdReport = generateReport(reportData);
  const mdPath = path.join(process.cwd(), 'PRODUCTION_BROWSER_CHECK_REPORT.md');
  fs.writeFileSync(mdPath, mdReport);
  console.log(`✅ Markdown report written: ${mdPath}`);

  console.log(`\n📊 Summary:`);
  console.log(`  - Total Pages: ${summary.total}`);
  console.log(`  - ✅ OK: ${summary.ok}`);
  console.log(`  - ⚠️ WARNING: ${summary.warning}`);
  console.log(`  - ❌ FAIL: ${summary.fail}`);
  console.log(`  - Total Errors: ${summary.totalErrors}`);
  console.log(`  - Total Warnings: ${summary.totalWarnings}`);
  console.log(`  - Network Errors: ${summary.totalNetworkErrors}`);
  console.log(`  - Unique Patterns: ${Object.keys(patterns).length}`);

  // Exit code based on result
  if (summary.fail > 0) {
    console.error(`\n❌ FAIL: ${summary.fail} URL(s) failed`);
    process.exit(1);
  } else {
    console.log(`\n✅ PASS: All URLs passed (with warnings)`);
    process.exit(0);
  }
}

main().catch(error => {
  console.error('Error running browser check:', error);
  process.exit(1);
});


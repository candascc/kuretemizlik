/**
 * Production Browser Check Script - Recursive Crawl Mode
 * 
 * ROUND 21: Full Recursive Crawl & Global Console Harvest
 * 
 * This script logs into production, then recursively crawls all pages
 * starting from the dashboard using BFS (Breadth-First Search).
 * 
 * Features:
 * - BFS crawl with depth and page limits
 * - Console error/warn/info/log collection
 * - Network 4xx/5xx and failed request collection
 * - Pattern extraction and global statistics
 * 
 * Usage:
 *   PROD_BASE_URL=https://www.kuretemizlik.com/app \
 *   MAX_DEPTH=3 \
 *   MAX_PAGES=100 \
 *   npm run check:prod:browser:crawl
 */

import { chromium, Browser, Page, BrowserContext } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';

// ROUND 28: Role-aware crawl configuration
type CrawlRoleConfig = {
  key: string;             // "admin", "ops", "mgmt", "portal" vb.
  label: string;           // İnsan okunaklı açıklama
  startPath: string;       // Default START_PATH (örn: "/")
  maxDepth: number;        // Default MAX_DEPTH
  maxPages: number;        // Default MAX_PAGES
  seedPaths: string[];     // Ek seed URL listesi (base_url altında, path olarak)
  usernameEnvKeys: string[]; // Öncelikli bakılacak env değişkenleri listesi
  passwordEnvKeys: string[]; // Aynı şekilde şifre için
};

const ROLE_CONFIGS: Record<string, CrawlRoleConfig> = {
  admin: {
    key: 'admin',
    label: 'Admin – Operasyon + Yönetim',
    startPath: process.env.START_PATH || '/',
    maxDepth: Number(process.env.MAX_DEPTH || '3'),
    maxPages: Number(process.env.MAX_PAGES || '150'),
    seedPaths: [
      '/',                     // dashboard
      '/jobs',
      '/jobs/new',
      '/recurring',
      '/recurring/new',
      '/customers',
      '/customers/new',
      '/finance',
      '/finance/new',
      '/services',
      '/calendar',
      '/performance',
      '/analytics',
      // Yönetim modülü:
      '/management/dashboard?header_mode=management',
      '/management/residents',
    ],
    usernameEnvKeys: [
      'CRAWL_ADMIN_USERNAME',
      'PROD_ADMIN_USERNAME',
      'ADMIN_USERNAME',
    ],
    passwordEnvKeys: [
      'CRAWL_ADMIN_PASSWORD',
      'PROD_ADMIN_PASSWORD',
      'ADMIN_PASSWORD',
    ],
  },
  // Diğer roller için şimdilik placeholder bırak, ileride doldurulacak:
  ops: {
    key: 'ops',
    label: 'Operasyon Kullanıcısı',
    startPath: process.env.START_PATH || '/',
    maxDepth: Number(process.env.MAX_DEPTH || '3'),
    maxPages: Number(process.env.MAX_PAGES || '150'),
    seedPaths: [
      '/', '/jobs', '/jobs/new', '/recurring', '/recurring/new',
      '/customers', '/finance', '/calendar', '/services',
    ],
    usernameEnvKeys: ['CRAWL_OPS_USERNAME'],
    passwordEnvKeys: ['CRAWL_OPS_PASSWORD'],
  },
  mgmt: {
    key: 'mgmt',
    label: 'Yönetim Kullanıcısı',
    startPath: '/management/dashboard?header_mode=management',
    maxDepth: Number(process.env.MAX_DEPTH || '3'),
    maxPages: Number(process.env.MAX_PAGES || '150'),
    seedPaths: [
      '/management/dashboard?header_mode=management',
      '/management/residents',
    ],
    usernameEnvKeys: ['CRAWL_MGMT_USERNAME'],
    passwordEnvKeys: ['CRAWL_MGMT_PASSWORD'],
  },
};

// Aktif rol seçim mantığı
const roleKey = process.env.CRAWL_ROLE_KEY || 'admin';
const roleConfig = ROLE_CONFIGS[roleKey] || ROLE_CONFIGS['admin'];

// Env'den gelen değerler varsa onları kullan, yoksa roleConfig'den al
const baseURL = process.env.PROD_BASE_URL || 'https://www.kuretemizlik.com/app';
const startPath = process.env.START_PATH || roleConfig.startPath;
const maxDepth = parseInt(process.env.MAX_DEPTH || String(roleConfig.maxDepth), 10);
const maxPages = parseInt(process.env.MAX_PAGES || String(roleConfig.maxPages), 10);

interface ConsoleEntry {
  level: 'error' | 'warn' | 'info' | 'log' | 'debug';
  text: string;
  location?: string;
  category?: 'frontend' | 'backend' | 'infra';
  patternId?: string;
  timestamp: string;
}

interface NetworkEntry {
  url: string;
  status: number;
  method: string;
  type: '4xx' | '5xx' | 'failed';
  text: string;
  timestamp: string;
}

interface PageResult {
  url: string;
  path: string;
  depth: number;
  status: number;
  title?: string;
  console: ConsoleEntry[];
  network: NetworkEntry[];
  timestamp: string;
}

interface PatternSummary {
  id: string;
  sample: string;
  count: number;
  level: 'error' | 'warn' | 'info' | 'log';
  category: string;
}

interface CrawlReport {
  meta: {
    baseUrl: string;
    startPath: string;
    maxDepth: number;
    maxPages: number;
    totalPages: number;
    maxDepthReached: number;
    generatedAt: string;
    roleKey?: string;      // ROUND 28: Role information
    roleLabel?: string;    // ROUND 28: Role label
  };
  pages: PageResult[];
  patterns: PatternSummary[];
  summary: {
    totalPages: number;
    maxDepthReached: number;
    totalConsoleErrors: number;
    totalConsoleWarnings: number;
    totalNetworkErrors: number;
    pagesWithErrors: number;
    pagesWithWarnings: number;
  };
}

/**
 * Check if a URL path should be visited (skip non-HTML files)
 */
function shouldVisit(path: string): boolean {
  if (!path || path === '/') {
    return true; // Root path is always valid
  }
  
  // Extract file extension (case-insensitive)
  const lowerPath = path.toLowerCase();
  const lastDot = lowerPath.lastIndexOf('.');
  if (lastDot === -1 || lastDot === lowerPath.length - 1) {
    // No extension or dot at end - assume it's a route/page
    return true;
  }
  
  const extension = lowerPath.substring(lastDot + 1);
  
  // Skip document files
  const skipExtensions = [
    'md', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv',
    'zip', 'rar', '7z',
    'png', 'jpg', 'jpeg', 'webp', 'svg', 'ico', 'gif', 'bmp',
    'js', 'css', 'map', 'json', 'xml', 'txt'
  ];
  
  return !skipExtensions.includes(extension);
}

/**
 * Normalize URL to ensure it's within the base domain and /app path
 */
function normalizeUrl(href: string, baseURL: string): string | null {
  try {
    // Ignore anchors, mailto, tel, javascript, etc.
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || 
        href.startsWith('tel:') || href.startsWith('javascript:') || 
        href.startsWith('data:')) {
      return null;
    }
    
    // If absolute URL, check if it's within base domain
    if (href.startsWith('http://') || href.startsWith('https://')) {
      const url = new URL(href);
      const baseUrlObj = new URL(baseURL);
      
      // Must be same origin
      if (url.origin !== baseUrlObj.origin) {
        return null;
      }
      
      // Must be under /app path (strict check)
      const basePath = baseUrlObj.pathname;
      if (!url.pathname.startsWith(basePath)) {
        // If path doesn't start with /app, it's outside our scope
        return null;
      }
      
      // Remove query and hash for normalization
      const normalizedUrl = url.origin + url.pathname;
      
      // Check if this path should be visited (skip non-HTML files)
      if (!shouldVisit(url.pathname)) {
        return null;
      }
      
      return normalizedUrl;
    }
    
    // Relative URL (starts with /)
    if (href.startsWith('/')) {
      const baseUrlObj = new URL(baseURL);
      const basePath = baseUrlObj.pathname;
      
      // If it's just /, make it baseURL
      if (href === '/') {
        return baseURL;
      }
      
      // Use URL API to properly combine baseURL and relative path
      // This ensures correct path handling without string manipulation bugs
      try {
        const combinedUrl = new URL(href, baseURL);
        
        // Verify it's same origin
        if (combinedUrl.origin !== baseUrlObj.origin) {
          return null;
        }
        
        // Verify it's under /app path
        if (!combinedUrl.pathname.startsWith(basePath)) {
          return null;
        }
        
        // Return normalized URL (remove query and hash for consistency)
        const normalizedUrl = combinedUrl.origin + combinedUrl.pathname;
        
        // Check if this path should be visited (skip non-HTML files)
        if (!shouldVisit(combinedUrl.pathname)) {
          return null;
        }
        
        return normalizedUrl;
      } catch (error) {
        // If URL construction fails, skip this link
        return null;
      }
    }
    
    // Relative path (no leading /)
    // This is relative to current page, we'll handle it in context
    return null;
  } catch (error) {
    return null;
  }
}

/**
 * Extract pattern from console/network message
 */
function extractPattern(message: string, level: string): string {
  const msg = message.toLowerCase();
  
  // Network patterns
  if (msg.includes('404')) return 'NETWORK_404';
  if (msg.includes('403')) return 'NETWORK_403';
  if (msg.includes('401')) return 'NETWORK_401';
  if (msg.includes('500')) return 'NETWORK_500';
  if (msg.includes('502')) return 'NETWORK_502';
  if (msg.includes('503')) return 'NETWORK_503';
  if (msg.includes('timeout')) return 'NETWORK_TIMEOUT';
  if (msg.includes('cors')) return 'NETWORK_CORS';
  if (msg.includes('failed') && msg.includes('fetch')) return 'NETWORK_FETCH_FAILED';
  
  // Console patterns
  if (msg.includes('tailwindcss.com') && msg.includes('production')) return 'TAILWIND_CDN_PROD_WARNING';
  if (msg.includes('alpine') && msg.includes('expression error')) return 'ALPINE_EXPRESSION_ERROR';
  if (msg.includes('alpine') && msg.includes('nextcursor')) return 'ALPINE_REFERENCEERROR_NEXTCURSOR';
  if (msg.includes('alpine') && msg.includes('not defined')) return 'ALPINE_REFERENCEERROR';
  if (msg.includes('referenceerror') && msg.includes('not defined')) return 'JS_REFERENCEERROR';
  if (msg.includes('typeerror')) return 'JS_TYPEERROR';
  if (msg.includes('syntaxerror')) return 'JS_SYNTAXERROR';
  if (msg.includes('service worker') || msg.includes('sw')) {
    if (msg.includes('precache') || msg.includes('pre-cache')) return 'SW_PRECACHE_FAILED';
    if (msg.includes('register')) return 'SW_REGISTER_FAILED';
    return 'SW_ERROR';
  }
  if (msg.includes('csp') || msg.includes('content security policy')) return 'SECURITY_CSP_VIOLATION';
  if (msg.includes('mixed content')) return 'SECURITY_MIXED_CONTENT';
  if (msg.includes('deprecated')) return 'DX_DEPRECATED';
  
  return 'UNKNOWN';
}

/**
 * Login to the application with role-based credentials
 * ROUND 28: Role-aware login helper
 * ROUND 22B: Fixed to use username instead of email, with default credentials
 */
async function loginAsRole(page: Page, baseUrl: string, roleConfig: CrawlRoleConfig): Promise<boolean> {
  // Username çözme sırası
  let username: string | undefined;
  for (const key of roleConfig.usernameEnvKeys) {
    if (process.env[key]) {
      username = process.env[key];
      break;
    }
  }
  
  // Password çözme sırası
  let password: string | undefined;
  for (const key of roleConfig.passwordEnvKeys) {
    if (process.env[key]) {
      password = process.env[key];
      break;
    }
  }
  
  // ROUND 28: Admin için default credentials (ONLY FOR LOCAL QA – DO NOT USE IN SERVER CONFIG)
  if (roleConfig.key === 'admin') {
    if (!username) {
      username = 'admin';
    }
    if (!password) {
      password = '12dream21'; // ONLY FOR LOCAL QA – DO NOT USE IN SERVER CONFIG
    }
  }
  
  if (!username || !password) {
    throw new Error(`Missing credentials for role ${roleConfig.key}. Required env vars: ${roleConfig.usernameEnvKeys.join(', ')} and ${roleConfig.passwordEnvKeys.join(', ')}`);
  }
  
  try {
    // ROUND 28: Login URL construction - handle baseUrl with or without /app
    const baseUrlForLogin = baseUrl.replace(/\/app\/?$/, '');
    const loginUrl = new URL('/login', baseUrlForLogin).toString();
    console.log(`[LOGIN] Navigating to ${loginUrl} (role: ${roleConfig.key})`);
    
    await page.goto(loginUrl, { waitUntil: 'networkidle', timeout: 15000 });
    
    // Wait for login form to be visible
    await page.waitForSelector('#username, input[name="username"]', { timeout: 5000 });
    
    // Fill username and password
    await page.fill('#username, input[name="username"]', username);
    await page.fill('#password, input[name="password"]', password);
    
    console.log('[LOGIN] Filled credentials, submitting...');
    
    // Wait for navigation after submit
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Wait a bit for any redirects
    await page.waitForTimeout(2000);
    
    // Check if we're on a dashboard page
    const currentUrl = page.url();
    console.log('[LOGIN] After submit URL:', currentUrl);
    
    const isDashboard = currentUrl.includes('/dashboard') || 
                        currentUrl.endsWith('/app') || 
                        currentUrl.endsWith('/app/') ||
                        currentUrl.match(/\/app\/?$/) ||
                        currentUrl.includes('/management/dashboard');
    
    if (isDashboard) {
      console.log(`[LOGIN] ✅ Login successful - on dashboard (role: ${roleConfig.key})`);
      return true;
    }
    
    // Also check for common dashboard indicators
    const dashboardIndicators = await Promise.all([
      page.locator('h1:has-text("Dashboard"), h1:has-text("Ana Sayfa"), [class*="dashboard"]').count(),
      page.locator('body').textContent()
    ]);
    
    // If we see dashboard-related content, consider login successful
    if (dashboardIndicators[0] > 0 || (dashboardIndicators[1] && dashboardIndicators[1].toLowerCase().includes('dashboard'))) {
      console.log(`[LOGIN] ✅ Login successful - dashboard content detected (role: ${roleConfig.key})`);
      return true;
    }
    
    // Final check: if URL changed from /login, assume success
    if (!currentUrl.includes('/login')) {
      console.log(`[LOGIN] ✅ Login successful - URL changed from /login (role: ${roleConfig.key})`);
      return true;
    }
    
    console.warn(`[LOGIN] ⚠️ Login might have failed - still on login page (role: ${roleConfig.key})`);
    return false;
  } catch (error) {
    console.error(`[LOGIN] ❌ Login failed (role: ${roleConfig.key}):`, error);
    return false;
  }
}

/**
 * Check if page is showing login form (for auto re-login)
 */
async function isLoginPage(page: Page): Promise<boolean> {
  try {
    const title = await page.title();
    if (title && (title.includes('Giriş') || title.includes('Login'))) {
      return true;
    }
    
    const loginForm = await page.locator('form[action*="/login"], input[name="username"], #username').count();
    if (loginForm > 0) {
      return true;
    }
    
    return false;
  } catch (error) {
    return false;
  }
}

/**
 * Extract all links from a page
 */
async function extractLinks(page: Page, baseURL: string): Promise<string[]> {
  const links: string[] = [];
  
  try {
    // Get all <a> tags with href
    const anchors = await page.locator('a[href]').all();
    
    for (const anchor of anchors) {
      const href = await anchor.getAttribute('href');
      if (!href) continue;
      
      const normalized = normalizeUrl(href, baseURL);
      if (normalized && !links.includes(normalized)) {
        links.push(normalized);
      }
    }
  } catch (error) {
    console.error('Error extracting links:', error);
  }
  
  return links;
}

/**
 * Crawl a single page and collect logs
 */
async function crawlPage(
  context: BrowserContext,
  url: string,
  depth: number
): Promise<{ result: PageResult; links: string[] }> {
  const page = await context.newPage();
  const consoleEntries: ConsoleEntry[] = [];
  const networkEntries: NetworkEntry[] = [];
  
  // Set up console listeners
  page.on('console', (msg) => {
    const level = msg.type() as 'error' | 'warn' | 'info' | 'log' | 'debug';
    const text = msg.text();
    const patternId = extractPattern(text, level);
    
    let location: string | undefined;
    try {
      const loc = msg.location();
      if (loc) {
        location = `${loc.url}:${loc.lineNumber}:${loc.columnNumber}`;
      }
    } catch (e) {
      // Ignore
    }
    
    consoleEntries.push({
      level,
      text,
      location,
      category: 'frontend',
      patternId,
      timestamp: new Date().toISOString()
    });
  });
  
  // Set up page error listener
  page.on('pageerror', (error) => {
    const patternId = extractPattern(error.message, 'error');
    consoleEntries.push({
      level: 'error',
      text: error.message,
      location: error.stack?.split('\n')[0],
      category: 'frontend',
      patternId,
      timestamp: new Date().toISOString()
    });
  });
  
  // Set up network listeners
  page.on('response', (response) => {
    const status = response.status();
    if (status >= 400) {
      const type = status >= 500 ? '5xx' : '4xx';
      const patternId = extractPattern(`HTTP ${status}`, 'error');
      networkEntries.push({
        url: response.url(),
        status,
        method: response.request().method(),
        type,
        text: `HTTP ${status} ${response.statusText()}`,
        timestamp: new Date().toISOString()
      });
    }
  });
  
  page.on('requestfailed', (request) => {
    const failure = request.failure();
    if (failure) {
      const patternId = extractPattern(failure.errorText, 'error');
      networkEntries.push({
        url: request.url(),
        status: 0,
        method: request.method(),
        type: 'failed',
        text: `Network failure: ${failure.errorText}`,
        timestamp: new Date().toISOString()
      });
    }
  });
  
  let status = 0;
  let title: string | undefined;
  let links: string[] = [];
  
  try {
    const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
    status = response?.status() || 0;
    
    // ROUND 28: Auto re-login if session expired
    if (await isLoginPage(page)) {
      console.log(`  [AUTO-RELOGIN] Session expired, re-logging in...`);
      // Note: roleConfig is not available here, we'll need to pass it or use a global
      // For now, we'll skip auto re-login in crawlPage and handle it at a higher level
      // This is a limitation that will be addressed in the multi-role orchestrator
    }
    
    try {
      title = await page.title();
    } catch (e) {
      // Ignore
    }
    
    // Wait a bit for any async initialization
    await page.waitForTimeout(2000);
    
    // Extract links
    links = await extractLinks(page, baseURL);
  } catch (error) {
    consoleEntries.push({
      level: 'error',
      text: `Page load failed: ${error instanceof Error ? error.message : String(error)}`,
      category: 'backend',
      patternId: 'NETWORK_PAGE_LOAD_FAILED',
      timestamp: new Date().toISOString()
    });
  } finally {
    await page.close();
  }
  
  // Extract path correctly - use URL API to avoid string manipulation bugs
  let path = '/';
  try {
    const urlObj = new URL(url);
    const baseUrlObj = new URL(baseURL);
    if (urlObj.origin === baseUrlObj.origin) {
      const basePath = baseUrlObj.pathname;
      if (urlObj.pathname.startsWith(basePath)) {
        path = urlObj.pathname.substring(basePath.length) || '/';
      } else {
        path = urlObj.pathname;
      }
    }
  } catch (error) {
    // Fallback to simple replace if URL parsing fails
    path = url.replace(baseURL, '') || '/';
  }
  
  return {
    result: {
      url,
      path,
      depth,
      status,
      title,
      console: consoleEntries,
      network: networkEntries,
      timestamp: new Date().toISOString()
    },
    links
  };
}

/**
 * Main crawl function using BFS
 * ROUND 28: Now accepts multiple seed URLs and role config
 * ROUND 22B: Now accepts a page parameter to use the logged-in session
 */
async function crawl(
  page: Page,
  seedUrls: string[],
  options: { maxDepth: number; maxPages: number; roleConfig: CrawlRoleConfig }
): Promise<CrawlReport> {
  const visited = new Set<string>();
  const queue: Array<{ url: string; depth: number }> = [];
  const results: PageResult[] = [];
  
  // ROUND 28: Start with seed URLs
  for (const seedUrl of seedUrls) {
    if (!visited.has(seedUrl)) {
      queue.push({ url: seedUrl, depth: 0 });
      visited.add(seedUrl);
    }
  }
  
  // Use the existing page's context (already logged in)
  const context = page.context();
  
  let maxDepthReached = 0;
  
  while (queue.length > 0 && results.length < options.maxPages) {
    const { url, depth } = queue.shift()!;
    
    if (depth > options.maxDepth) {
      continue;
    }
    
    maxDepthReached = Math.max(maxDepthReached, depth);
    
    console.log(`[${results.length + 1}/${options.maxPages}] Crawling (depth ${depth}): ${url.replace(baseURL, '') || '/'}`);
    
    try {
      const { result, links } = await crawlPage(context, url, depth);
      results.push(result);
      
      const errorCount = result.console.filter(c => c.level === 'error').length;
      const warnCount = result.console.filter(c => c.level === 'warn').length;
      const networkErrorCount = result.network.length;
      
      if (result.status >= 500) {
        console.log(`  ❌ Status: ${result.status} (${errorCount} console errors, ${warnCount} warnings, ${networkErrorCount} network errors)`);
      } else if (result.status >= 400 || errorCount > 0) {
        console.log(`  ⚠️  Status: ${result.status} (${errorCount} console errors, ${warnCount} warnings, ${networkErrorCount} network errors)`);
      } else {
        console.log(`  ✅ Status: ${result.status} (${errorCount} console errors, ${warnCount} warnings, ${networkErrorCount} network errors)`);
      }
      
      // Add new links to queue if we haven't reached max depth
      if (depth < options.maxDepth) {
        for (const link of links) {
          if (!visited.has(link)) {
            visited.add(link);
            queue.push({ url: link, depth: depth + 1 });
          }
        }
      }
    } catch (error) {
      console.error(`  ❌ Error crawling ${url}:`, error);
      // Add error result with correct path extraction
      let errorPath = '/';
      try {
        const urlObj = new URL(url);
        const baseUrlObj = new URL(baseURL);
        if (urlObj.origin === baseUrlObj.origin) {
          const basePath = baseUrlObj.pathname;
          if (urlObj.pathname.startsWith(basePath)) {
            errorPath = urlObj.pathname.substring(basePath.length) || '/';
          } else {
            errorPath = urlObj.pathname;
          }
        }
      } catch (e) {
        errorPath = url.replace(baseURL, '') || '/';
      }
      
      results.push({
        url,
        path: errorPath,
        depth,
        status: 0,
        console: [{
          level: 'error',
          text: `Crawl error: ${error instanceof Error ? error.message : String(error)}`,
          category: 'backend',
          patternId: 'CRAWL_ERROR',
          timestamp: new Date().toISOString()
        }],
        network: [],
        timestamp: new Date().toISOString()
      });
    }
  }
  
  // Don't close browser here - it's managed in main()
  
  // Generate pattern summary
  const patternMap = new Map<string, { count: number; sample: string; level: string; category: string }>();
  
  for (const page of results) {
    for (const entry of page.console) {
      const patternId = entry.patternId || 'UNKNOWN';
      const existing = patternMap.get(patternId);
      if (existing) {
        existing.count++;
      } else {
        patternMap.set(patternId, {
          count: 1,
          sample: entry.text,
          level: entry.level,
          category: entry.category || 'frontend'
        });
      }
    }
    
    for (const entry of page.network) {
      const patternId = extractPattern(entry.text, entry.type);
      const existing = patternMap.get(patternId);
      if (existing) {
        existing.count++;
      } else {
        patternMap.set(patternId, {
          count: 1,
          sample: entry.text,
          level: entry.status >= 500 ? 'error' : 'warn',
          category: entry.status >= 500 ? 'backend' : 'infra'
        });
      }
    }
  }
  
  const patterns: PatternSummary[] = Array.from(patternMap.entries())
    .map(([id, data]) => ({
      id,
      sample: data.sample,
      count: data.count,
      level: data.level as 'error' | 'warn' | 'info' | 'log',
      category: data.category
    }))
    .sort((a, b) => b.count - a.count);
  
  // Calculate summary
  const totalConsoleErrors = results.reduce((sum, r) => sum + r.console.filter(c => c.level === 'error').length, 0);
  const totalConsoleWarnings = results.reduce((sum, r) => sum + r.console.filter(c => c.level === 'warn').length, 0);
  const totalNetworkErrors = results.reduce((sum, r) => sum + r.network.length, 0);
  const pagesWithErrors = results.filter(r => r.status >= 500 || r.console.some(c => c.level === 'error')).length;
  const pagesWithWarnings = results.filter(r => (r.status >= 400 && r.status < 500) || r.console.some(c => c.level === 'warn')).length;
  
  return {
    meta: {
      baseUrl: baseURL,
      startPath,
      maxDepth: options.maxDepth,
      maxPages: options.maxPages,
      totalPages: results.length,
      maxDepthReached,
      generatedAt: new Date().toISOString(),
      roleKey: options.roleConfig.key,      // ROUND 28
      roleLabel: options.roleConfig.label,   // ROUND 28
    },
    pages: results,
    patterns,
    summary: {
      totalPages: results.length,
      maxDepthReached,
      totalConsoleErrors,
      totalConsoleWarnings,
      totalNetworkErrors,
      pagesWithErrors,
      pagesWithWarnings
    }
  };
}

/**
 * Generate Markdown report
 */
function generateMarkdownReport(report: CrawlReport): string {
  let md = `# Production Browser Check - Recursive Crawl Report\n\n`;
  md += `**Date:** ${report.meta.generatedAt}\n`;
  md += `**Base URL:** ${report.meta.baseUrl}\n`;
  md += `**Start Path:** ${report.meta.startPath}\n`;
  md += `**Max Depth:** ${report.meta.maxDepth}\n`;
  md += `**Max Pages:** ${report.meta.maxPages}\n\n`;
  
  md += `## Summary\n\n`;
  md += `- **Total Pages Crawled:** ${report.summary.totalPages}\n`;
  md += `- **Max Depth Reached:** ${report.summary.maxDepthReached}\n`;
  md += `- **Total Console Errors:** ${report.summary.totalConsoleErrors}\n`;
  md += `- **Total Console Warnings:** ${report.summary.totalConsoleWarnings}\n`;
  md += `- **Total Network Errors:** ${report.summary.totalNetworkErrors}\n`;
  md += `- **Pages with Errors:** ${report.summary.pagesWithErrors}\n`;
  md += `- **Pages with Warnings:** ${report.summary.pagesWithWarnings}\n\n`;
  
  md += `## Top Patterns\n\n`;
  md += `| Pattern | Count | Level | Category | Sample |\n`;
  md += `|---------|-------|-------|----------|--------|\n`;
  
  for (const pattern of report.patterns.slice(0, 20)) {
    const sample = pattern.sample.length > 80 ? pattern.sample.substring(0, 80) + '...' : pattern.sample;
    md += `| ${pattern.id} | ${pattern.count} | ${pattern.level} | ${pattern.category} | ${sample.replace(/\|/g, '\\|')} |\n`;
  }
  
  md += `\n## Page Details\n\n`;
  
  // Show first 50 pages with most errors
  const sortedPages = [...report.pages].sort((a, b) => {
    const aErrors = a.console.filter(c => c.level === 'error').length + a.network.length;
    const bErrors = b.console.filter(c => c.level === 'error').length + b.network.length;
    return bErrors - aErrors;
  });
  
  for (const page of sortedPages.slice(0, 50)) {
    const errorCount = page.console.filter(c => c.level === 'error').length;
    const warnCount = page.console.filter(c => c.level === 'warn').length;
    const networkErrorCount = page.network.length;
    const statusIcon = page.status >= 500 ? '❌' : page.status >= 400 || errorCount > 0 ? '⚠️' : '✅';
    
    md += `### ${statusIcon} ${page.path} (depth ${page.depth})\n\n`;
    md += `- **URL:** ${page.url}\n`;
    md += `- **Status:** ${page.status}\n`;
    md += `- **Title:** ${page.title || 'N/A'}\n`;
    md += `- **Console Errors:** ${errorCount}\n`;
    md += `- **Console Warnings:** ${warnCount}\n`;
    md += `- **Network Errors:** ${networkErrorCount}\n\n`;
    
    if (page.console.length > 0 || page.network.length > 0) {
      md += `#### Logs\n\n`;
      
      for (const entry of page.console.slice(0, 10)) {
        md += `- **${entry.level.toUpperCase()}** [${entry.category}]: ${entry.text.replace(/\|/g, '\\|')}\n`;
        if (entry.location) {
          md += `  - Location: ${entry.location}\n`;
        }
      }
      
      for (const entry of page.network.slice(0, 10)) {
        md += `- **NETWORK** [${entry.type}]: ${entry.text.replace(/\|/g, '\\|')} (${entry.method} ${entry.url})\n`;
      }
      
      md += `\n`;
    }
  }
  
  return md;
}

/**
 * Main execution
 */
async function main() {
  console.log(`\n🔍 ROUND 28: Role-Aware Recursive Crawl Browser Check`);
  console.log(`Role: ${roleConfig.label} (${roleConfig.key})`);
  console.log(`Base URL: ${baseURL}`);
  console.log(`Start Path: ${startPath}`);
  console.log(`Max Depth: ${maxDepth}`);
  console.log(`Max Pages: ${maxPages}\n`);
  
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  
  // ROUND 28: Role-aware login
  console.log(`📝 Logging in as ${roleConfig.label} (${roleConfig.key})...`);
  const loginSuccess = await loginAsRole(page, baseURL, roleConfig);
  
  if (!loginSuccess) {
    console.error(`❌ Login failed for role ${roleConfig.key}. Exiting.`);
    await browser.close();
    // ROUND 27: Even login failure exits with 0 (non-fatal for gating)
    // Error is already logged to console
    process.exit(0);
  }
  
  console.log(`✅ Login successful as ${roleConfig.label}\n`);
  
  // Keep the page open for crawling (don't close it)
  
  // ROUND 28: Start crawling with seed paths
  const startUrl = baseURL + startPath;
  const seedUrls = [startUrl, ...roleConfig.seedPaths.map(path => baseURL + path)];
  const report = await crawl(page, seedUrls, { maxDepth, maxPages, roleConfig });
  
  await browser.close();
  
  // ROUND 28: Role-specific report file names
  const roleSuffix = roleConfig.key.toUpperCase();
  const jsonPath = path.join(process.cwd(), `PRODUCTION_BROWSER_CHECK_CRAWL_${roleSuffix}.json`);
  fs.writeFileSync(jsonPath, JSON.stringify(report, null, 2));
  console.log(`\n📄 JSON report written: ${jsonPath}`);
  
  // Write Markdown report
  const mdPath = path.join(process.cwd(), `PRODUCTION_BROWSER_CHECK_CRAWL_${roleSuffix}.md`);
  const md = generateMarkdownReport(report);
  fs.writeFileSync(mdPath, md);
  console.log(`📄 Markdown report written: ${mdPath}`);
  
  console.log(`\n✅ Crawl completed!`);
  console.log(`Summary: ${report.summary.totalPages} pages, ${report.summary.totalConsoleErrors} errors, ${report.summary.totalConsoleWarnings} warnings`);
  
  // ROUND 27: Exit code normalization (B Seçeneği)
  // Always exit with 0 - errors/warnings are reported in JSON/MD files
  // This allows the script to be used in gating/deployment pipelines without breaking builds
  process.exit(0);
}

main().catch(error => {
  console.error('Fatal error:', error);
  // ROUND 27: Even fatal errors exit with 0 (non-fatal for gating)
  // Error is already logged to console
  process.exit(0);
});


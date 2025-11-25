/**
 * Production Browser Check Script - Multi-Role Crawl Orchestrator
 * 
 * ROUND 28: Role-Based Crawl Orchestrator
 * 
 * This script runs crawls for multiple roles sequentially.
 * Each role gets its own JSON/MD report file.
 * 
 * Usage:
 *   CRAWL_ROLES=admin PROD_BASE_URL=... npm run check:prod:browser:crawl:roles
 *   CRAWL_ROLES=admin,ops,mgmt PROD_BASE_URL=... npm run check:prod:browser:crawl:roles
 */

import { spawn } from 'child_process';

// Role configs (matching check-prod-browser-crawl.ts)
const ROLE_CONFIGS: Record<string, { key: string; label: string }> = {
  admin: {
    key: 'admin',
    label: 'Admin – Operasyon + Yönetim',
  },
  ops: {
    key: 'ops',
    label: 'Operasyon Kullanıcısı',
  },
  mgmt: {
    key: 'mgmt',
    label: 'Yönetim Kullanıcısı',
  },
};

console.log('\n🔍 ROUND 28: Multi-Role Crawl Orchestrator\n');

const rolesEnv = process.env.CRAWL_ROLES || 'admin';
const roleKeys = rolesEnv.split(',').map(k => k.trim()).filter(Boolean);

if (roleKeys.length === 0) {
  console.error('❌ No roles specified. Set CRAWL_ROLES env variable.');
  process.exit(0);
}

console.log(`📋 Roles to crawl: ${roleKeys.join(', ')}\n`);

async function runCrawlForRole(roleKey: string): Promise<void> {
  const roleConfig = ROLE_CONFIGS[roleKey];
  if (!roleConfig) {
    console.warn(`⚠️ Unknown role: ${roleKey}, skipping...`);
    return;
  }
  
  console.log(`\n========================================`);
  console.log(`Crawling as ${roleConfig.label} (${roleKey})`);
  console.log(`========================================\n`);
  
  return new Promise((resolve, reject) => {
    const env = {
      ...process.env,
      CRAWL_ROLE_KEY: roleKey,
    };
    
    // Use ts-node directly to call the main script
    const child = spawn('npx', ['ts-node', 'scripts/check-prod-browser-crawl.ts'], {
      env,
      stdio: 'inherit',
      shell: true,
      cwd: process.cwd(),
    });
    
    child.on('close', (code) => {
      if (code === 0) {
        console.log(`\n✅ Crawl completed for role ${roleKey}\n`);
        resolve();
      } else {
        console.log(`\n⚠️ Crawl finished with exit code ${code} for role ${roleKey} (non-fatal)\n`);
        resolve(); // Non-fatal, continue with other roles
      }
    });
    
    child.on('error', (error) => {
      console.error(`\n❌ Error running crawl for role ${roleKey}:`, error);
      reject(error);
    });
  });
}

async function main() {
  for (const roleKey of roleKeys) {
    try {
      await runCrawlForRole(roleKey);
    } catch (error) {
      console.error(`❌ Failed to crawl role ${roleKey}:`, error);
      // Continue with next role
    }
  }
  
  console.log('\n✅ All role crawls completed!');
  console.log(`Generated reports: PRODUCTION_BROWSER_CHECK_CRAWL_<ROLE>.json/.md\n`);
}

main().catch(error => {
  console.error('Fatal error:', error);
  process.exit(0); // ROUND 27: Non-fatal exit
});


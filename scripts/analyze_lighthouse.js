#!/usr/bin/env node
const fs = require('fs');
const path = require('path');

function usage() {
  console.log('Usage: node scripts/analyze_lighthouse.js <report.json> [limit]');
  process.exit(1);
}

const [,, reportPathArg, limitArg] = process.argv;
if (!reportPathArg) usage();

const reportPath = path.resolve(reportPathArg);
if (!fs.existsSync(reportPath)) {
  console.error('Report not found:', reportPath);
  process.exit(1);
}

const limit = Number.isInteger(Number(limitArg)) ? Number(limitArg) : 20;
const report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));

console.log('=== Lighthouse Scores ===');
for (const [key, category] of Object.entries(report.categories)) {
  const score = category.score !== undefined ? (category.score * 100).toFixed(0) : 'N/A';
  console.log(`${key.padEnd(15)} ${score}`);
}

const audits = Object.values(report.audits || {})
  .filter(audit => audit.score !== null && audit.score < 1)
  .sort((a, b) => (a.score ?? 0) - (b.score ?? 0));

console.log('\n=== Top Issues ===');
audits.slice(0, limit).forEach(audit => {
  const score = audit.score !== null ? (audit.score * 100).toFixed(0) : 'N/A';
  console.log(`${score.padStart(3)} | ${audit.id} | ${audit.title}`);
});

if (audits.length > limit) {
  console.log(`...and ${audits.length - limit} more`);
}

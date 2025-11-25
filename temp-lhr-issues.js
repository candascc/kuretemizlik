const report = require('./lighthouse-report-secure.report.json');
const audits = Object.values(report.audits).filter(a => a.score !== null && a.score < 1);
audits.sort((a,b) => (a.score ?? 0) - (b.score ?? 0));
for (const a of audits.slice(0,20)) {
  console.log(a.id, a.score, '-', a.title);
}

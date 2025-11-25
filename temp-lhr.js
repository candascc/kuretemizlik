const r=require('./lighthouse-report-secure.report.json'); for (const key of Object.keys(r.categories)) { console.log(key, r.categories[key].score); }

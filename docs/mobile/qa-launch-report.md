# Mobile Launch QA Report – 2025-11-09

## 1. Automated Regression
| Suite | Command | Result |
| --- | --- | --- |
| Functional Core | `php tests/functional/run_all.php` | ✅ PASS (4 sub-suites, 100%) |
| Header PHPUnit | `php vendor/bin/phpunit tests/HeaderManagerTest.php` | ✅ PASS (7 tests) |

Key coverage:
- Payment transaction atomicity, session fixation, header sanitization
- Management residents portal filters, pagination, alert handling

## 2. PWA Verification
- Manifest served (`/manifest.json`) with real icons & shortcuts
- Service worker v2 precaches `/offline`, bundles, logo
- Offline simulation (DevTools) displays branded fallback and auto-reloads on reconnect
- PWA install prompt available via Chrome “Install app”

## 3. Responsive Spot Checks
- Viewports 375 / 414 / 768 / 1024 / 1440 px on Dashboard & Residents
  - KPI cards resize with `fluid-kpi`; mobile stacks render correctly
  - Hero sections maintain CTA visibility, badges wrap gracefully
- Dark mode verified on macOS Safari (desktop) & Chrome devtools device toolbar

## 4. Manual Smoke
- `/management/dashboard`, `/management/residents`, `/portal/login` – layout + navigation ok
- PWA offline flow: disconnect → `/management/dashboard` returns `/offline`
- Service worker update: `chrome://inspect/#service-workers` to confirm single active registration

## 5. Remaining Risks
- Mock data fixtures not yet integrated; marketing screenshots should wait for consistent mock feed
- Background sync functions are placeholders; no offline queue yet
- Lighthouse PWA report pending rerun after final asset bundle

## 6. Launch Checklist (Next)
- [ ] Capture mobile/desktop screenshots for marketing kit after mock data injection
- [ ] Run Lighthouse mobile profile (`scripts/run_lighthouse.ps1`) and archive JSON
- [ ] Document install flow (GIF/video) for campaign support


# PWA Packaging Checklist – Küre Temizlik (2025-11-08)

## 1. Manifest (`public/manifest.json`)
- ✅ Uses real PNG icon (`assets/img/logokureapp.png`) for 192/512 sizes
- `display: standalone`, `theme_color` & `background_color` align with brand tokens
- Shortcuts configured for hızlı erişim (`/jobs/new`, `/customers`, `/`)
- Share target endpoint placeholder (`/share`) reserved for future enhancement
- Manifest served via `<link rel=\"manifest\" href=\"/manifest.json\">`

## 2. Service Worker (`public/service-worker.js`)
- Versioned cache name `kure-temizlik-v2`
- Precaches: root, `/offline`, `app.bundle.(css|js)`, `custom.css`, manifest, logo
- Cache-first strategy with network fallback; navigation requests fall back to offline page
- Skip-waiting message hook exposed (future upgrade prompt)
- Push notification + background sync scaffolding retained

## 3. Offline Fallback (`/offline`)
- New route renders `src/Views/offline.php`
- Responsive card with recovery checklist, retry button, destektelefon link
- Auto reload when `window` fires `online`
- Page cached during SW install

## 4. Icons & Branding
- Favicon & apple-touch icon now point to `logokureapp.png` via `Utils::asset()`
- Theme color meta already set in `layout/base.php`
- Consider exporting square PNG derivatives (192/512) if marketing delivers higher-res source

## 5. Registration & Scope
- `global-footer.php` registers SW on window load (scope `/`)
- Existing Tailwind bundle/JS delivered via `assets/dist` enabling offline caching
- PWA ready for Lighthouse audit (`View -> Developer Tools -> Lighthouse -> PWA`)

## 6. Next Enhancements (Optional)
- Add update toast when SW `updatefound` fires (`postMessage({type:'SKIP_WAITING'})`)
- Provide dedicated screenshot assets and update manifest once available
- Implement IndexedDB queues for background sync handlers (`syncJobs`, `syncPayments`)
- Expose `add to home screen` prompt flow in onboarding docs

## 7. Demo Steps
1. Build assets (`php scripts/build_assets.php`) and ensure bundles exist
2. Visit app in Chrome/Edge → DevTools → Application → Manifest → `Add to homescreen`
3. Enable offline (DevTools Network tab) and navigate — offline page should display, then auto-refresh when back online
4. Run Lighthouse (mobile) to verify PWA score & cache coverage

## 8. Lighthouse – 2025-11-09 (mock=1, mobile)
- **Scores**: Performance 100, Accessibility 100, Best Practices 100, SEO 100 (`reports/lighthouse/management-mobile-20251109-1528.report.html`)
- **Blocking issues**: none; all category scores hit 100.
- **Follow-ups flagged by Lighthouse (non-blocking insights)**:
  - `redirects`, `unused-javascript`, `render-blocking-insight`, `network-dependency-tree-insight` all reported with score 0 (advisory) — review once marketing bundle finalized.
  - `total-byte-weight` (score 50) and `unminified-css` (score 50) remain opportunities if we decide to slim demo bundles further.
- **Run command**:
  ```powershell
  pwsh -Command "$env:TMP='C:\X\Yazılım\Alastyr_ftp\kuretemizlik.com\app\tmp'; $env:TEMP=$env:TMP; lighthouse 'https://kuretemizlik.local/app/management/dashboard?mock=1' --preset=desktop --form-factor=mobile --screenEmulation.mobile --throttling-method=devtools --chrome-flags='--headless --ignore-certificate-errors --disable-dev-shm-usage --no-sandbox' --output=json --output=html --output-path='reports/lighthouse/management-mobile-20251109-1528' --quiet"
  ```


# Build Progress Log

## 2025-11-25 11:43:27
- Investigated crawl `Login failed` errors; inspected new crawl logs under `C:\Windows\Temp` and confirmed background PHP emitted `session_id(): Session ID cannot be changed after headers have already been sent`.
- Updated `InternalCrawlService::login()` background session handling to avoid mutating the session ID after response flush, disable cookies/cache headers, and keep isolated session data purely in an in-memory/temp-path context. This prevents background warnings that corrupted the JSON crawl result stream.

## 2025-11-25 11:47:47
- Simulated the background login path via CLI to ensure the new isolation logic boots correctly, addressed follow-up issues (invalid session ID characters, CLI session header guards), and re-ran the check to verify the login helper completes without fatal errors.

## 2025-11-25 12:14:59
- Added `scripts/manual_crawl_start.php` plus an `index.php` opt-out guard so we can bootstrap the full router without executing HTTP responses, mirroring the sysadmin crawl launcher from the CLI.
- Started a manual ADMIN crawl via the new script (testId `crawl_88_1764061999_754fb7c3`); login succeeded and the progress JSON is available under `%TEMP%/crawl_progress`, though 404-heavy link extraction surfaced numerous downstream issues that now need triage.

## 2025-11-25 13:17:55
- Hardened `SecurityStatsService` count helpers to always use `fetch/fetchAll`, preventing the `PDOStatement as array` fatal that previously broke `/app/security/dashboard`.
- Guarded `admin/emails/queue` view data access so missing `retry_count` keys no longer trip the global view fallback, eliminating the “Sayfa yüklenirken bir hata oluştu” false positives during crawl.
- Added `DocsController` + `docs/static_doc` view and registered routes for `MANUAL_TEST_CHECKLIST.md`, `UX_IMPLEMENTATION_GUIDE.md`, and `DEPLOYMENT_CHECKLIST.md`, turning the old dead links into proper authenticated documentation pages.
- Normalized crawl link extraction further (href sanitization + footer CTA fix) so the crawler no longer chases `/jobs/create` (non-existent GET) and other malformed anchors; reran `php scripts/manual_crawl_start.php ADMIN` and confirmed **200/200** success with zero errors (progress file `crawl_88_1764065828_7361d910`).


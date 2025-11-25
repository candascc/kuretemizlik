# Build Error Log

## 2025-11-25 10:59 (from IIS crawl run)
- **Symptom:** `session_id(): Session ID cannot be changed after headers have already been sent` during background crawl login, causing the crawl result to short-circuit with `Login failed`.
- **Impact:** JSON progress files contained `error: "Login failed"` for every role and the internal crawler never left the login phase.
- **Resolution:** Reworked the isolated-session bootstrapping in `InternalCrawlService::login()` to skip `session_id()` changes once headers are flushed, clear in-memory session data, force cookie-less session storage, and log when fallback mode is used.

## 2025-11-25 13:05 (manual crawl)
- **Symptom:** Crawl output flagged `/app/security/dashboard`, `/app/admin/emails/queue`, and static `.md` documentation links with `Error pattern detected: Sayfa yüklenirken bir hata oluştu`.
- **Impact:** Security dashboard crashed with `Cannot use object of type PDOStatement as array`, the email queue view triggered the global error fallback because `retry_count` was missing on some rows, and the footer linked to three non-existent `.md` routes that always returned 404.
- **Resolution:** Updated `SecurityStatsService` to rely on `fetch/fetchAll` helpers, added null-safe retry counters in `admin/emails/queue.php`, created `DocsController` + `docs/static_doc` view with authenticated routes for `MANUAL_TEST_CHECKLIST.md`, `UX_IMPLEMENTATION_GUIDE.md`, and `DEPLOYMENT_CHECKLIST.md`, and re-ran the admin crawl to verify a clean 200/200 pass.



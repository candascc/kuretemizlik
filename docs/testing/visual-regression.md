<!-- Visual regression strategy for resident portal -->

# Resident Portal Visual Regression Strategy

## Snapshot-Based DOM Diffs (HTML)

- Use existing functional suite to emit deterministic HTML snapshots:
  ```bash
  VISUAL_SNAPSHOT_DIR=tests/visual/snapshots php tests/functional/run_all.php
  ```
- `ManagementResidentsTest` saves:
  - `management-residents-page-2.html` – filtered/paginated state
  - `management-residents-empty.html` – empty result state
- Store generated files in git (baseline) or compare via `diff`/`git diff` for regressions.

## Workflow

1. Run suite with `VISUAL_SNAPSHOT_DIR` pointing to a clean directory.
2. Commit/update baseline snapshots after intentional UI changes.
3. CI step: run suite again, compare snapshots (`git diff --exit-code tests/visual/snapshots`).

## Optional Screenshot Layer

- For pixel-level diffs integrate Playwright or Puppeteer:
  - Launch `/management/residents` in headless Chrome.
  - Capture states mirroring HTML snapshots.
  - Compare via `pixelmatch` or `resemblejs` (threshold ≤ 0.1).
- Store artifacts under `tests/visual/screenshots/`.

## Governance

- Baselines reviewed & approved by design lead.
- Unexpected diffs fail CI; update baselines only after sign-off.
- Document reviewed cases in `docs/header/resident-portal-qa.md`.


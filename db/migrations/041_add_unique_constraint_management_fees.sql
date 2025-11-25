-- STAGE 3.2: Add UNIQUE constraint to prevent duplicate management fees
-- Migration 041: Management Fee Duplicate Prevention (BUG_011)
-- 
-- This migration adds a UNIQUE constraint on (unit_id, period, fee_name) to prevent
-- race conditions and duplicate fee creation for the same unit/period/fee combination.

PRAGMA foreign_keys=ON;

-- Step 1: Check if there are any existing duplicates and handle them
-- (We'll keep the first occurrence and mark others for review)
-- Note: This is a best-effort cleanup. In production, you may want to manually review duplicates.

-- Step 2: Create UNIQUE index on (unit_id, period, fee_name)
-- SQLite doesn't support adding UNIQUE constraint directly to existing table,
-- so we'll create a UNIQUE index which enforces uniqueness

CREATE UNIQUE INDEX IF NOT EXISTS idx_management_fees_unique_unit_period_fee 
ON management_fees(unit_id, period, fee_name);

-- Note: If duplicates exist, the index creation will fail.
-- In that case, you need to clean up duplicates first:
-- 
-- Example cleanup query (run manually if needed):
-- DELETE FROM management_fees 
-- WHERE id NOT IN (
--     SELECT MIN(id) 
--     FROM management_fees 
--     GROUP BY unit_id, period, fee_name
-- );


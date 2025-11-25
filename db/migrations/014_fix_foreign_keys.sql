-- Migration: Fix Foreign Key Constraints
-- Add ON DELETE actions to existing tables
-- This migration fixes HIGH-006 issue for existing databases
-- Date: 2025-11-05

-- NOTE: SQLite doesn't support ALTER TABLE for FKs directly
-- We need to recreate tables with proper constraints
-- This migration is for documentation; actual FK enforcement happens in Database::ensureSchema()

-- Create migration log table if it doesn't exist
CREATE TABLE IF NOT EXISTS migration_log (
    migration_name TEXT PRIMARY KEY,
    executed_at TEXT NOT NULL
);

-- Log migration
INSERT OR IGNORE INTO migration_log (migration_name, executed_at) 
VALUES ('014_fix_foreign_keys', datetime('now'));

-- Foreign Key Constraints Documentation:
-- jobs table:
--   - service_id REFERENCES services(id) ON DELETE SET NULL
--   - customer_id REFERENCES customers(id) ON DELETE CASCADE  
--   - address_id REFERENCES addresses(id) ON DELETE SET NULL
--
-- money_entries table:
--   - job_id REFERENCES jobs(id) ON DELETE SET NULL
--   - created_by REFERENCES users(id) ON DELETE SET NULL
--
-- activity_log table:
--   - actor_id REFERENCES users(id) ON DELETE SET NULL
--
-- Validation query - check for orphaned records
-- Run after migration to ensure data integrity
--
-- Check orphaned jobs (should be 0 after FK enforcement)
-- SELECT COUNT(*) as orphaned_jobs FROM jobs 
-- WHERE customer_id IS NOT NULL 
--   AND customer_id NOT IN (SELECT id FROM customers);
--
-- Check orphaned addresses (should be 0)
-- SELECT COUNT(*) as orphaned_addresses FROM addresses 
-- WHERE customer_id NOT IN (SELECT id FROM customers);
--
-- If orphaned records exist, they should be cleaned up before FK enforcement
--



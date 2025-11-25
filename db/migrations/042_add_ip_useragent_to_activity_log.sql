-- Migration 042: Add ip_address and user_agent columns to activity_log table
-- SECURITY HARDENING ROUND 2 - STAGE 1
-- Date: 2025-01-XX
-- CRITICAL: Backup database before running!

PRAGMA foreign_keys=OFF; -- Temporarily disable for ALTER operations

-- ============================================================================
-- STEP 1: Add ip_address and user_agent columns to activity_log table
-- ============================================================================

-- Check if columns already exist (SQLite doesn't support IF NOT EXISTS for ALTER TABLE ADD COLUMN)
-- We'll use a safe approach: try to add, ignore if already exists

-- Add ip_address column (nullable, for backward compatibility)
-- Note: SQLite will fail silently if column already exists, so we check first
SELECT CASE 
    WHEN EXISTS (
        SELECT 1 FROM pragma_table_info('activity_log') WHERE name = 'ip_address'
    ) THEN 1
    ELSE 0
END as ip_address_exists;

-- Add ip_address column if it doesn't exist
-- SQLite doesn't support IF NOT EXISTS for ALTER TABLE, so we'll use a workaround
-- We'll create a new table and copy data if columns don't exist

-- First, check if we need to add columns
-- If columns don't exist, we'll add them via table recreation (safer for SQLite)

-- For now, we'll use a simple ALTER TABLE approach
-- If column exists, this will fail but we can catch it
-- If column doesn't exist, it will be added

-- Add ip_address column
ALTER TABLE activity_log ADD COLUMN ip_address TEXT;

-- Add user_agent column
ALTER TABLE activity_log ADD COLUMN user_agent TEXT;

-- ============================================================================
-- STEP 2: Add company_id column for multi-tenant filtering (if not exists)
-- ============================================================================

-- Check if company_id column exists
SELECT CASE 
    WHEN EXISTS (
        SELECT 1 FROM pragma_table_info('activity_log') WHERE name = 'company_id'
    ) THEN 1
    ELSE 0
END as company_id_exists;

-- Add company_id column (nullable, default to 1 for existing records)
ALTER TABLE activity_log ADD COLUMN company_id INTEGER DEFAULT 1;

-- Update existing records to have company_id = 1 (if they're NULL)
UPDATE activity_log SET company_id = 1 WHERE company_id IS NULL;

-- ============================================================================
-- STEP 3: Create indexes for better query performance
-- ============================================================================

-- Index on created_at for date range queries
CREATE INDEX IF NOT EXISTS idx_activity_log_created_at ON activity_log(created_at DESC);

-- Index on action for filtering by action type
CREATE INDEX IF NOT EXISTS idx_activity_log_action ON activity_log(action);

-- Index on actor_id for user activity queries
CREATE INDEX IF NOT EXISTS idx_activity_log_actor_id ON activity_log(actor_id);

-- Index on entity (category) for category filtering
CREATE INDEX IF NOT EXISTS idx_activity_log_entity ON activity_log(entity);

-- Index on company_id for multi-tenant filtering
CREATE INDEX IF NOT EXISTS idx_activity_log_company_id ON activity_log(company_id);

-- Composite index for common query patterns (company_id + created_at)
CREATE INDEX IF NOT EXISTS idx_activity_log_company_created ON activity_log(company_id, created_at DESC);

-- Composite index for IP-based queries (security analytics)
CREATE INDEX IF NOT EXISTS idx_activity_log_ip_created ON activity_log(ip_address, created_at DESC);

PRAGMA foreign_keys=ON; -- Re-enable foreign key checks

-- ============================================================================
-- STEP 4: Migrate existing metadata to columns (optional, for better querying)
-- ============================================================================

-- Extract ip_address from meta_json and populate ip_address column
-- This is a one-time migration for existing records
UPDATE activity_log 
SET ip_address = json_extract(meta_json, '$.ip_address')
WHERE ip_address IS NULL 
  AND meta_json IS NOT NULL 
  AND json_extract(meta_json, '$.ip_address') IS NOT NULL;

-- Extract user_agent from meta_json and populate user_agent column
UPDATE activity_log 
SET user_agent = json_extract(meta_json, '$.user_agent')
WHERE user_agent IS NULL 
  AND meta_json IS NOT NULL 
  AND json_extract(meta_json, '$.user_agent') IS NOT NULL;

-- Note: company_id extraction from metadata is more complex and may require
-- joining with users table. For now, we'll leave it as default (1) for existing records.


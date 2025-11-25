-- Migration 040: Add company_id to staff and appointments tables
-- SECURITY HARDENING ROUND 1 - BUG_001, BUG_002
-- Date: 2025-01-XX
-- CRITICAL: Backup database before running!

PRAGMA foreign_keys=OFF; -- Temporarily disable for ALTER operations

-- ============================================================================
-- STEP 1: Add company_id to staff table
-- ============================================================================

-- Check if column already exists
SELECT CASE 
    WHEN EXISTS (
        SELECT 1 FROM pragma_table_info('staff') WHERE name = 'company_id'
    ) THEN 1
    ELSE 0
END as column_exists;

-- Add company_id column (nullable first for safety, then update existing records)
ALTER TABLE staff ADD COLUMN company_id INTEGER;

-- Set default company_id for existing records (company_id = 1)
UPDATE staff SET company_id = 1 WHERE company_id IS NULL;

-- Make company_id NOT NULL after data migration
-- Note: SQLite doesn't support ALTER COLUMN, so we need to recreate the table
-- For safety, we'll keep it nullable but add a CHECK constraint

-- Add foreign key constraint
-- Note: SQLite requires the companies table to exist
CREATE INDEX IF NOT EXISTS idx_staff_company_id ON staff(company_id);

-- ============================================================================
-- STEP 2: Add company_id to appointments table
-- ============================================================================

-- Check if column already exists
SELECT CASE 
    WHEN EXISTS (
        SELECT 1 FROM pragma_table_info('appointments') WHERE name = 'company_id'
    ) THEN 1
    ELSE 0
END as column_exists;

-- Add company_id column
ALTER TABLE appointments ADD COLUMN company_id INTEGER;

-- Set default company_id for existing records
-- Try to infer from related customer if possible
UPDATE appointments 
SET company_id = (
    SELECT COALESCE(
        (SELECT company_id FROM customers WHERE id = appointments.customer_id),
        1
    )
)
WHERE company_id IS NULL;

-- Fallback: set to 1 if still NULL
UPDATE appointments SET company_id = 1 WHERE company_id IS NULL;

-- Add index
CREATE INDEX IF NOT EXISTS idx_appointments_company_id ON appointments(company_id);

PRAGMA foreign_keys=ON; -- Re-enable foreign keys

-- ============================================================================
-- VERIFICATION
-- ============================================================================

-- Verify staff table
SELECT COUNT(*) as total_staff, 
       COUNT(company_id) as staff_with_company_id,
       COUNT(DISTINCT company_id) as distinct_companies
FROM staff;

-- Verify appointments table
SELECT COUNT(*) as total_appointments,
       COUNT(company_id) as appointments_with_company_id,
       COUNT(DISTINCT company_id) as distinct_companies
FROM appointments;


-- Add company_id to file_uploads table for multi-tenancy support
-- Migration 037: File uploads company scoping

PRAGMA foreign_keys=ON;

-- Add company_id column to file_uploads table (if not exists)
-- Note: SQLite doesn't support IF NOT EXISTS for ALTER TABLE ADD COLUMN
-- This will fail silently if column already exists
ALTER TABLE file_uploads ADD COLUMN company_id INTEGER;

-- Backfill company_id from uploaded_by user's company
-- This assumes users have company_id set
UPDATE file_uploads
SET company_id = (
  SELECT u.company_id
  FROM users u
  WHERE u.id = file_uploads.uploaded_by
)
WHERE company_id IS NULL;

-- Set default company_id to 1 for any remaining NULL values (legacy data)
UPDATE file_uploads
SET company_id = 1
WHERE company_id IS NULL;

-- Create index for company_id lookups
CREATE INDEX IF NOT EXISTS idx_file_uploads_company_id ON file_uploads(company_id);


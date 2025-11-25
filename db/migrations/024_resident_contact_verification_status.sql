ALTER TABLE resident_users
    ADD COLUMN phone_verified INTEGER NOT NULL DEFAULT 0;

ALTER TABLE resident_users
    ADD COLUMN phone_verified_at TEXT NULL;

ALTER TABLE resident_users
    ADD COLUMN email_verified_at TEXT NULL;

-- Backfill existing records
UPDATE resident_users
SET
    email_verified_at = CASE
        WHEN email_verified = 1 AND (email_verified_at IS NULL OR email_verified_at = '')
            THEN COALESCE(updated_at, created_at, datetime('now'))
        ELSE email_verified_at
    END,
    phone_verified = CASE
        WHEN phone IS NOT NULL AND phone <> '' THEN 1
        ELSE phone_verified
    END,
    phone_verified_at = CASE
        WHEN phone IS NOT NULL AND phone <> '' AND (phone_verified_at IS NULL OR phone_verified_at = '')
            THEN COALESCE(updated_at, created_at, datetime('now'))
        ELSE phone_verified_at
    END;


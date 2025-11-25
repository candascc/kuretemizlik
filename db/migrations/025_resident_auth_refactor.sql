-- Refactor resident_users for phone-first auth with password lifecycle metadata
BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS resident_users_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unit_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    email TEXT UNIQUE,
    phone TEXT,
    password_hash TEXT,
    password_set_at TEXT,
    last_otp_sent_at TEXT,
    otp_attempts INTEGER NOT NULL DEFAULT 0,
    otp_context TEXT,
    is_owner INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    email_verified INTEGER DEFAULT 0,
    phone_verified INTEGER DEFAULT 0,
    verification_token TEXT,
    email_verified_at TEXT,
    phone_verified_at TEXT,
    last_login_at TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE
);

INSERT INTO resident_users_new (
    id,
    unit_id,
    name,
    email,
    phone,
    password_hash,
    password_set_at,
    last_otp_sent_at,
    otp_attempts,
    otp_context,
    is_owner,
    is_active,
    email_verified,
    phone_verified,
    verification_token,
    email_verified_at,
    phone_verified_at,
    last_login_at,
    created_at,
    updated_at
)
SELECT
    id,
    unit_id,
    name,
    email,
    phone,
    NULLIF(password_hash, ''),
    CASE
        WHEN password_hash IS NOT NULL AND password_hash <> '' THEN COALESCE(updated_at, created_at)
        ELSE NULL
    END AS password_set_at,
    NULL,
    0,
    NULL,
    is_owner,
    is_active,
    email_verified,
    phone_verified,
    verification_token,
    email_verified_at,
    phone_verified_at,
    last_login_at,
    created_at,
    updated_at
FROM resident_users;

DROP TABLE resident_users;

ALTER TABLE resident_users_new RENAME TO resident_users;

CREATE INDEX IF NOT EXISTS idx_resident_users_phone ON resident_users(phone);

COMMIT;


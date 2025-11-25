-- Migration 016: Expand user roles to support management module access
PRAGMA foreign_keys=OFF;

CREATE TABLE users_new (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL CHECK(role IN ('ADMIN','OPERATOR','SITE_MANAGER','FINANCE','SUPPORT','SUPERADMIN')),
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  two_factor_secret TEXT,
  two_factor_backup_codes TEXT,
  two_factor_enabled_at TEXT,
  two_factor_required INTEGER DEFAULT 0,
  email TEXT,
  company_id INTEGER DEFAULT 1
);

INSERT INTO users_new (
  id,
  username,
  password_hash,
  role,
  is_active,
  created_at,
  updated_at,
  two_factor_secret,
  two_factor_backup_codes,
  two_factor_enabled_at,
  two_factor_required,
  email,
  company_id
)
SELECT
  id,
  username,
  password_hash,
  role,
  is_active,
  created_at,
  updated_at,
  two_factor_secret,
  two_factor_backup_codes,
  two_factor_enabled_at,
  two_factor_required,
  email,
  company_id
FROM users;

DROP TABLE users;
ALTER TABLE users_new RENAME TO users;

CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

PRAGMA foreign_keys=ON;

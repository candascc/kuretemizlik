-- Migration: 017_resident_login_tokens

CREATE TABLE IF NOT EXISTS resident_login_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resident_user_id INTEGER NOT NULL,
    token TEXT NOT NULL,
    channel TEXT NOT NULL CHECK(channel IN ('email', 'sms')),
    expires_at TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    max_attempts INTEGER NOT NULL DEFAULT 5,
    meta TEXT,
    consumed_at TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(resident_user_id) REFERENCES resident_users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_resident_login_tokens_user ON resident_login_tokens(resident_user_id);
CREATE INDEX IF NOT EXISTS idx_resident_login_tokens_token ON resident_login_tokens(token);
CREATE INDEX IF NOT EXISTS idx_resident_login_tokens_expires ON resident_login_tokens(expires_at);


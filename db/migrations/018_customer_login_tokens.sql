-- Migration: 018_customer_login_tokens

CREATE TABLE IF NOT EXISTS customer_login_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    token TEXT NOT NULL,
    channel TEXT NOT NULL CHECK(channel IN ('email', 'sms')),
    expires_at TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    max_attempts INTEGER NOT NULL DEFAULT 5,
    meta TEXT,
    consumed_at TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_customer_login_tokens_customer ON customer_login_tokens(customer_id);
CREATE INDEX IF NOT EXISTS idx_customer_login_tokens_token ON customer_login_tokens(token);
CREATE INDEX IF NOT EXISTS idx_customer_login_tokens_expires ON customer_login_tokens(expires_at);


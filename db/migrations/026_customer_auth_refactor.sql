-- Migration 026: Customer portal authentication refactor

ALTER TABLE customers ADD COLUMN password_hash TEXT;
ALTER TABLE customers ADD COLUMN password_set_at TEXT;
ALTER TABLE customers ADD COLUMN last_otp_sent_at TEXT;
ALTER TABLE customers ADD COLUMN otp_attempts INTEGER NOT NULL DEFAULT 0;
ALTER TABLE customers ADD COLUMN otp_context TEXT;


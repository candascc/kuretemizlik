-- Migration: 035_contract_otp_tokens
-- Sözleşme onayı için OTP kodları tablosu
-- NOT: Bu migration, 036_job_contracts.sql'den SONRA çalıştırılmalıdır
-- çünkü job_contracts tablosuna foreign key bağımlılığı vardır.

CREATE TABLE IF NOT EXISTS contract_otp_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_contract_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    token TEXT NOT NULL,
    phone TEXT NOT NULL,
    channel TEXT NOT NULL DEFAULT 'sms' CHECK(channel IN ('sms', 'email')),
    expires_at TEXT NOT NULL,
    sent_at TEXT NOT NULL DEFAULT (datetime('now')),
    verified_at TEXT,
    attempts INTEGER NOT NULL DEFAULT 0,
    max_attempts INTEGER NOT NULL DEFAULT 5,
    ip_address TEXT,
    user_agent TEXT,
    meta TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(job_contract_id) REFERENCES job_contracts(id) ON DELETE CASCADE,
    FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_contract_otp_tokens_job_contract ON contract_otp_tokens(job_contract_id);
CREATE INDEX IF NOT EXISTS idx_contract_otp_tokens_customer ON contract_otp_tokens(customer_id);
CREATE INDEX IF NOT EXISTS idx_contract_otp_tokens_token ON contract_otp_tokens(token);
CREATE INDEX IF NOT EXISTS idx_contract_otp_tokens_expires ON contract_otp_tokens(expires_at);
CREATE INDEX IF NOT EXISTS idx_contract_otp_tokens_verified ON contract_otp_tokens(verified_at);


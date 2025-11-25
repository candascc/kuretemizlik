-- Migration: 036_job_contracts
-- İş bazlı sözleşmeler tablosu

CREATE TABLE IF NOT EXISTS job_contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id INTEGER NOT NULL UNIQUE,
    template_id INTEGER,
    status TEXT NOT NULL CHECK(status IN ('PENDING', 'SENT', 'APPROVED', 'EXPIRED', 'REJECTED')) DEFAULT 'PENDING',
    approval_method TEXT NOT NULL DEFAULT 'SMS_OTP' CHECK(approval_method IN ('SMS_OTP', 'EMAIL_OTP', 'MANUAL')),
    approved_at TEXT,
    approved_phone TEXT,
    approved_ip TEXT,
    approved_user_agent TEXT,
    approved_customer_id INTEGER,
    sms_sent_at TEXT,
    sms_sent_count INTEGER DEFAULT 0,
    last_sms_token_id INTEGER,
    contract_text TEXT,
    contract_pdf_path TEXT,
    contract_hash TEXT,
    metadata TEXT,
    expires_at TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY(template_id) REFERENCES contract_templates(id) ON DELETE SET NULL,
    FOREIGN KEY(approved_customer_id) REFERENCES customers(id) ON DELETE SET NULL
    -- Note: last_sms_token_id FK is not defined here to avoid circular dependency
    -- with contract_otp_tokens table. It's a reference field only.
);

CREATE INDEX IF NOT EXISTS idx_job_contracts_job_id ON job_contracts(job_id);
CREATE INDEX IF NOT EXISTS idx_job_contracts_status ON job_contracts(status);
CREATE INDEX IF NOT EXISTS idx_job_contracts_expires_at ON job_contracts(expires_at);


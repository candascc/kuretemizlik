-- Migration: 039_fix_customer_delete_cascade
-- Fix foreign key constraints for customer deletion
-- Adds CASCADE to jobs.customer_id and email_logs.customer_id

-- SQLite doesn't support ALTER TABLE to modify foreign keys
-- We need to recreate the tables with proper CASCADE constraints

PRAGMA foreign_keys=OFF;

BEGIN TRANSACTION;

-- ===== 1. Fix jobs table =====
-- Create new jobs table with CASCADE
CREATE TABLE jobs_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER,
    customer_id INTEGER NOT NULL,
    company_id INTEGER NOT NULL DEFAULT 1,
    address_id INTEGER,
    start_at TEXT NOT NULL,
    end_at TEXT NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('SCHEDULED','DONE','CANCELLED')) DEFAULT 'SCHEDULED',
    total_amount REAL NOT NULL DEFAULT 0,
    amount_paid REAL NOT NULL DEFAULT 0,
    payment_status TEXT NOT NULL CHECK(payment_status IN ('UNPAID','PARTIAL','PAID')) DEFAULT 'UNPAID',
    assigned_to INTEGER,
    note TEXT,
    income_id INTEGER,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    recurring_job_id INTEGER NULL,
    occurrence_id INTEGER NULL,
    reminder_sent INTEGER DEFAULT 0,
    FOREIGN KEY(service_id) REFERENCES services(id),
    FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY(address_id) REFERENCES addresses(id),
    FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Copy data from old table to new table
-- Ensure company_id is not NULL (default to 1 if NULL)
INSERT INTO jobs_new 
SELECT 
    id, service_id, customer_id, 
    COALESCE(company_id, 1) as company_id,
    address_id, start_at, end_at, status, 
    total_amount, amount_paid, payment_status, 
    assigned_to, note, income_id, created_at, updated_at,
    recurring_job_id, occurrence_id, reminder_sent
FROM jobs;

-- Drop old table
DROP TABLE jobs;

-- Rename new table
ALTER TABLE jobs_new RENAME TO jobs;

-- Recreate indexes
CREATE INDEX IF NOT EXISTS idx_jobs_customer_id ON jobs(customer_id);
CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs(status);
CREATE INDEX IF NOT EXISTS idx_jobs_start_at ON jobs(start_at);
CREATE INDEX IF NOT EXISTS idx_jobs_company_id ON jobs(company_id);
CREATE INDEX IF NOT EXISTS idx_jobs_recurring_job_id ON jobs(recurring_job_id);
CREATE INDEX IF NOT EXISTS idx_jobs_payment_status ON jobs(payment_status);
CREATE INDEX IF NOT EXISTS idx_jobs_created_at ON jobs(created_at);
CREATE INDEX IF NOT EXISTS idx_jobs_status_payment ON jobs(status, payment_status);
CREATE INDEX IF NOT EXISTS idx_jobs_status_start_at ON jobs(status, start_at);
CREATE INDEX IF NOT EXISTS idx_jobs_customer_status ON jobs(customer_id, status);

-- ===== 2. Fix email_logs table =====
-- Create new email_logs table with CASCADE
CREATE TABLE email_logs_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id INTEGER,
    customer_id INTEGER,
    to_email TEXT NOT NULL,
    subject TEXT NOT NULL,
    type TEXT NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('pending', 'sent', 'failed')),
    error_message TEXT,
    sent_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Copy data from old table to new table
INSERT INTO email_logs_new SELECT * FROM email_logs;

-- Drop old table
DROP TABLE email_logs;

-- Rename new table
ALTER TABLE email_logs_new RENAME TO email_logs;

-- Recreate indexes
CREATE INDEX IF NOT EXISTS idx_email_logs_customer_id ON email_logs(customer_id);
CREATE INDEX IF NOT EXISTS idx_email_logs_job_id ON email_logs(job_id);
CREATE INDEX IF NOT EXISTS idx_email_logs_status ON email_logs(status);
CREATE INDEX IF NOT EXISTS idx_email_logs_sent_at ON email_logs(sent_at);

COMMIT;

PRAGMA foreign_keys=ON;


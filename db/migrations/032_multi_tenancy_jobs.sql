PRAGMA foreign_keys=OFF;

CREATE TABLE IF NOT EXISTS companies (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  subdomain TEXT UNIQUE,
  owner_name TEXT,
  owner_email TEXT,
  owner_phone TEXT,
  address TEXT,
  tax_number TEXT,
  is_active INTEGER NOT NULL DEFAULT 1,
  settings_json TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

INSERT OR IGNORE INTO companies (id, name, subdomain, is_active)
VALUES (1, 'Default Company', 'default', 1);

-- Customers
ALTER TABLE customers ADD COLUMN company_id INTEGER;
UPDATE customers SET company_id = 1 WHERE company_id IS NULL;

-- Addresses
ALTER TABLE addresses ADD COLUMN company_id INTEGER;
UPDATE addresses SET company_id = COALESCE((SELECT company_id FROM customers WHERE customers.id = addresses.customer_id), 1);

-- Services
ALTER TABLE services ADD COLUMN company_id INTEGER;
UPDATE services SET company_id = 1 WHERE company_id IS NULL;

-- Jobs
ALTER TABLE jobs ADD COLUMN company_id INTEGER;
UPDATE jobs SET company_id = COALESCE((SELECT company_id FROM customers WHERE customers.id = jobs.customer_id), 1);

-- Recurring jobs
ALTER TABLE recurring_jobs ADD COLUMN company_id INTEGER;
UPDATE recurring_jobs SET company_id = COALESCE((SELECT company_id FROM customers WHERE customers.id = recurring_jobs.customer_id), 1);

-- Money entries
ALTER TABLE money_entries ADD COLUMN company_id INTEGER;
UPDATE money_entries SET company_id = COALESCE((SELECT company_id FROM jobs WHERE jobs.id = money_entries.job_id), 1);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_customers_company_id ON customers(company_id);
CREATE INDEX IF NOT EXISTS idx_addresses_company_id ON addresses(company_id);
CREATE INDEX IF NOT EXISTS idx_services_company_id ON services(company_id);
CREATE INDEX IF NOT EXISTS idx_jobs_company_id ON jobs(company_id);
CREATE INDEX IF NOT EXISTS idx_recurring_jobs_company_id ON recurring_jobs(company_id);
CREATE INDEX IF NOT EXISTS idx_money_entries_company_id ON money_entries(company_id);
CREATE INDEX IF NOT EXISTS idx_users_company_id ON users(company_id);

PRAGMA foreign_keys=ON;

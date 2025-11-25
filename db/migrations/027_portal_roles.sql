ALTER TABLE resident_users
    ADD COLUMN role TEXT NOT NULL DEFAULT 'RESIDENT_TENANT';

ALTER TABLE customers
    ADD COLUMN role TEXT NOT NULL DEFAULT 'CUSTOMER_STANDARD';

CREATE INDEX IF NOT EXISTS idx_resident_users_role ON resident_users(role);
CREATE INDEX IF NOT EXISTS idx_customers_role ON customers(role);


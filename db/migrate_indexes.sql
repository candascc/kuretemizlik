-- Mevcut veritabanına indeks ekleme scripti
-- Bu scripti sadece mevcut veritabanına indeks eklemek için kullanın

-- Jobs tablosu ek indeksleri
CREATE INDEX IF NOT EXISTS idx_jobs_customer_id ON jobs(customer_id);
CREATE INDEX IF NOT EXISTS idx_jobs_service_id ON jobs(service_id);
CREATE INDEX IF NOT EXISTS idx_jobs_status_start_at ON jobs(status, start_at);
CREATE INDEX IF NOT EXISTS idx_jobs_customer_status ON jobs(customer_id, status);

-- Money entries tablosu ek indeksleri
CREATE INDEX IF NOT EXISTS idx_money_entries_created_by ON money_entries(created_by);
CREATE INDEX IF NOT EXISTS idx_money_entries_job_id ON money_entries(job_id);
CREATE INDEX IF NOT EXISTS idx_money_entries_kind_date ON money_entries(kind, date);

-- Customers tablosu indeksleri
CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name);
CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone);

-- Addresses tablosu indeksleri
CREATE INDEX IF NOT EXISTS idx_addresses_customer_id ON addresses(customer_id);

-- Activity log tablosu ek indeksleri
CREATE INDEX IF NOT EXISTS idx_activity_log_actor_id ON activity_log(actor_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_entity ON activity_log(entity);

-- Users tablosu indeksleri
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- Apartman/Site Yönetimi - Resident Portal
-- Migration 010: Resident Users and Portal Features

PRAGMA foreign_keys=ON;

-- Sakin portal kullanıcıları
CREATE TABLE IF NOT EXISTS resident_users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  unit_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  email TEXT UNIQUE NOT NULL,
  phone TEXT,
  password_hash TEXT NOT NULL,
  is_owner INTEGER DEFAULT 1,
  is_active INTEGER DEFAULT 1,
  email_verified INTEGER DEFAULT 0,
  verification_token TEXT,
  last_login_at TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE
);

-- Sakin talepleri (complaint/request system)
CREATE TABLE IF NOT EXISTS resident_requests (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  building_id INTEGER NOT NULL,
  unit_id INTEGER NOT NULL,
  resident_user_id INTEGER,
  request_type TEXT NOT NULL CHECK(request_type IN ('maintenance', 'complaint', 'suggestion', 'question', 'security', 'noise', 'parking', 'other')),
  category TEXT,
  subject TEXT NOT NULL,
  description TEXT NOT NULL,
  priority TEXT DEFAULT 'normal' CHECK(priority IN ('low', 'normal', 'high', 'urgent')),
  status TEXT DEFAULT 'open' CHECK(status IN ('open', 'in_progress', 'resolved', 'closed', 'rejected')),
  assigned_to INTEGER,
  response TEXT,
  resolved_at TEXT,
  resolved_by INTEGER,
  satisfaction_rating INTEGER, -- 1-5
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE,
  FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE,
  FOREIGN KEY(resident_user_id) REFERENCES resident_users(id) ON DELETE SET NULL,
  FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY(resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Online ödeme kayıtları
CREATE TABLE IF NOT EXISTS online_payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  management_fee_id INTEGER NOT NULL,
  resident_user_id INTEGER,
  amount DECIMAL(10,2) NOT NULL,
  payment_method TEXT NOT NULL CHECK(payment_method IN ('card', 'bank_transfer', 'mobile_payment')),
  payment_provider TEXT, -- 'iyzico', 'paytr', 'stripe', etc.
  transaction_id TEXT UNIQUE,
  status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded')),
  payment_data TEXT, -- JSON for provider-specific data
  error_message TEXT,
  processed_at TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(management_fee_id) REFERENCES management_fees(id) ON DELETE CASCADE,
  FOREIGN KEY(resident_user_id) REFERENCES resident_users(id) ON DELETE SET NULL
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_resident_users_unit_id ON resident_users(unit_id);
CREATE INDEX IF NOT EXISTS idx_resident_users_email ON resident_users(email);
CREATE INDEX IF NOT EXISTS idx_resident_users_active ON resident_users(is_active);
CREATE INDEX IF NOT EXISTS idx_resident_requests_building_id ON resident_requests(building_id);
CREATE INDEX IF NOT EXISTS idx_resident_requests_unit_id ON resident_requests(unit_id);
CREATE INDEX IF NOT EXISTS idx_resident_requests_status ON resident_requests(status);
CREATE INDEX IF NOT EXISTS idx_resident_requests_type ON resident_requests(request_type);
CREATE INDEX IF NOT EXISTS idx_resident_requests_priority ON resident_requests(priority);
CREATE INDEX IF NOT EXISTS idx_resident_requests_assigned_to ON resident_requests(assigned_to);
CREATE INDEX IF NOT EXISTS idx_online_payments_fee_id ON online_payments(management_fee_id);
CREATE INDEX IF NOT EXISTS idx_online_payments_status ON online_payments(status);
CREATE INDEX IF NOT EXISTS idx_online_payments_transaction_id ON online_payments(transaction_id);


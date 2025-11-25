-- Randevular ve Sözleşmeler için Migration
-- Mevcut veritabanına yeni tablolar ekler

PRAGMA journal_mode=WAL;
PRAGMA foreign_keys=ON;

-- Randevular tablosu
CREATE TABLE IF NOT EXISTS appointments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER NOT NULL,
  service_id INTEGER,
  title TEXT NOT NULL,
  description TEXT,
  appointment_date TEXT NOT NULL,
  start_time TEXT NOT NULL,
  end_time TEXT,
  status TEXT NOT NULL CHECK(status IN ('SCHEDULED','CONFIRMED','COMPLETED','CANCELLED','NO_SHOW')) DEFAULT 'SCHEDULED',
  priority TEXT CHECK(priority IN ('LOW','MEDIUM','HIGH','URGENT')) DEFAULT 'MEDIUM',
  assigned_to INTEGER,
  notes TEXT,
  reminder_sent INTEGER DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY(service_id) REFERENCES services(id),
  FOREIGN KEY(assigned_to) REFERENCES users(id)
);

-- Sözleşmeler tablosu
CREATE TABLE IF NOT EXISTS contracts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER NOT NULL,
  contract_number TEXT UNIQUE NOT NULL,
  title TEXT NOT NULL,
  description TEXT,
  contract_type TEXT NOT NULL CHECK(contract_type IN ('CLEANING','MAINTENANCE','RECURRING','ONE_TIME')) DEFAULT 'CLEANING',
  start_date TEXT NOT NULL,
  end_date TEXT,
  total_amount REAL,
  payment_terms TEXT,
  status TEXT NOT NULL CHECK(status IN ('DRAFT','ACTIVE','SUSPENDED','COMPLETED','TERMINATED')) DEFAULT 'DRAFT',
  auto_renewal INTEGER DEFAULT 0,
  renewal_period_days INTEGER,
  file_path TEXT,
  notes TEXT,
  created_by INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY(created_by) REFERENCES users(id)
);

-- Sözleşme ödemeleri tablosu
CREATE TABLE IF NOT EXISTS contract_payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  contract_id INTEGER NOT NULL,
  amount REAL NOT NULL,
  payment_date TEXT NOT NULL,
  payment_method TEXT CHECK(payment_method IN ('CASH','BANK_TRANSFER','CREDIT_CARD','CHECK')) DEFAULT 'CASH',
  status TEXT NOT NULL CHECK(status IN ('PENDING','PAID','OVERDUE','CANCELLED')) DEFAULT 'PENDING',
  due_date TEXT,
  notes TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);

-- Sözleşme ekleri tablosu
CREATE TABLE IF NOT EXISTS contract_attachments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  contract_id INTEGER NOT NULL,
  file_name TEXT NOT NULL,
  file_path TEXT NOT NULL,
  file_size INTEGER,
  mime_type TEXT,
  uploaded_by INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
  FOREIGN KEY(uploaded_by) REFERENCES users(id)
);

-- İndeksler
-- Appointments tablosu indeksleri
CREATE INDEX IF NOT EXISTS idx_appointments_customer_id ON appointments(customer_id);
CREATE INDEX IF NOT EXISTS idx_appointments_date ON appointments(appointment_date);
CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments(status);
CREATE INDEX IF NOT EXISTS idx_appointments_assigned_to ON appointments(assigned_to);
CREATE INDEX IF NOT EXISTS idx_appointments_date_status ON appointments(appointment_date, status);
CREATE INDEX IF NOT EXISTS idx_appointments_priority ON appointments(priority);

-- Contracts tablosu indeksleri
CREATE INDEX IF NOT EXISTS idx_contracts_customer_id ON contracts(customer_id);
CREATE INDEX IF NOT EXISTS idx_contracts_status ON contracts(status);
CREATE INDEX IF NOT EXISTS idx_contracts_type ON contracts(contract_type);
CREATE INDEX IF NOT EXISTS idx_contracts_start_date ON contracts(start_date);
CREATE INDEX IF NOT EXISTS idx_contracts_end_date ON contracts(end_date);
CREATE INDEX IF NOT EXISTS idx_contracts_created_by ON contracts(created_by);
CREATE INDEX IF NOT EXISTS idx_contracts_number ON contracts(contract_number);

-- Contract payments tablosu indeksleri
CREATE INDEX IF NOT EXISTS idx_contract_payments_contract_id ON contract_payments(contract_id);
CREATE INDEX IF NOT EXISTS idx_contract_payments_status ON contract_payments(status);
CREATE INDEX IF NOT EXISTS idx_contract_payments_due_date ON contract_payments(due_date);
CREATE INDEX IF NOT EXISTS idx_contract_payments_payment_date ON contract_payments(payment_date);

-- Contract attachments tablosu indeksleri
CREATE INDEX IF NOT EXISTS idx_contract_attachments_contract_id ON contract_attachments(contract_id);
CREATE INDEX IF NOT EXISTS idx_contract_attachments_uploaded_by ON contract_attachments(uploaded_by);

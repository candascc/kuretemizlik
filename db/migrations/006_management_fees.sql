-- Apartman/Site Yönetimi - Management Fees
-- Migration 006: Fee Definitions and Management Fees

PRAGMA foreign_keys=ON;

-- Aidat tanımları (monthly recurring)
CREATE TABLE IF NOT EXISTS management_fee_definitions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  building_id INTEGER NOT NULL,
  name TEXT NOT NULL, -- 'Aidat', 'Doğalgaz', 'Su', 'Elektrik Ortak'
  fee_type TEXT NOT NULL CHECK(fee_type IN ('fixed', 'per_sqm', 'per_person', 'custom')),
  amount DECIMAL(10,2) NOT NULL,
  is_mandatory INTEGER DEFAULT 1,
  description TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE
);

-- Aidat kayıtları
CREATE TABLE IF NOT EXISTS management_fees (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  unit_id INTEGER NOT NULL,
  building_id INTEGER NOT NULL,
  definition_id INTEGER,
  period TEXT NOT NULL, -- '2025-01' (YYYY-MM format)
  fee_name TEXT NOT NULL,
  base_amount DECIMAL(10,2) NOT NULL,
  discount_amount DECIMAL(10,2) DEFAULT 0,
  late_fee DECIMAL(10,2) DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL,
  paid_amount DECIMAL(10,2) DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'partial', 'paid', 'overdue', 'cancelled')),
  due_date TEXT NOT NULL,
  payment_date TEXT,
  payment_method TEXT CHECK(payment_method IN ('cash', 'transfer', 'card', 'check')),
  receipt_number TEXT,
  notes TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE,
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE,
  FOREIGN KEY(definition_id) REFERENCES management_fee_definitions(id) ON DELETE SET NULL
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_fee_definitions_building_id ON management_fee_definitions(building_id);
CREATE INDEX IF NOT EXISTS idx_management_fees_unit_id ON management_fees(unit_id);
CREATE INDEX IF NOT EXISTS idx_management_fees_building_id ON management_fees(building_id);
CREATE INDEX IF NOT EXISTS idx_management_fees_period ON management_fees(period);
CREATE INDEX IF NOT EXISTS idx_management_fees_status ON management_fees(status);
CREATE INDEX IF NOT EXISTS idx_management_fees_due_date ON management_fees(due_date);
CREATE INDEX IF NOT EXISTS idx_management_fees_building_period ON management_fees(building_id, period);
CREATE INDEX IF NOT EXISTS idx_management_fees_unit_period ON management_fees(unit_id, period);


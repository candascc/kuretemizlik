-- Apartman/Site Yönetimi - Building Expenses
-- Migration 007: Expense Tracking

PRAGMA foreign_keys=ON;

-- Bina giderleri
CREATE TABLE IF NOT EXISTS building_expenses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  building_id INTEGER NOT NULL,
  category TEXT NOT NULL, -- 'elektrik', 'su', 'dogalgaz', 'temizlik', 'guvenlik', 'bakim', 'vergi', 'sigorta', 'diger'
  subcategory TEXT,
  amount DECIMAL(10,2) NOT NULL,
  expense_date TEXT NOT NULL,
  invoice_number TEXT,
  vendor_name TEXT,
  vendor_tax_number TEXT,
  payment_method TEXT CHECK(payment_method IN ('cash', 'transfer', 'card', 'check')),
  is_recurring INTEGER DEFAULT 0,
  description TEXT,
  receipt_path TEXT, -- Document storage path
  created_by INTEGER,
  approved_by INTEGER,
  approval_status TEXT DEFAULT 'pending' CHECK(approval_status IN ('pending', 'approved', 'rejected')),
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE,
  FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_building_expenses_building_id ON building_expenses(building_id);
CREATE INDEX IF NOT EXISTS idx_building_expenses_category ON building_expenses(category);
CREATE INDEX IF NOT EXISTS idx_building_expenses_date ON building_expenses(expense_date);
CREATE INDEX IF NOT EXISTS idx_building_expenses_status ON building_expenses(approval_status);
CREATE INDEX IF NOT EXISTS idx_building_expenses_created_by ON building_expenses(created_by);
CREATE INDEX IF NOT EXISTS idx_building_expenses_building_date ON building_expenses(building_id, expense_date);


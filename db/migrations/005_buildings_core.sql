-- Apartman/Site Yönetimi - Core Tables
-- Migration 005: Buildings and Units

PRAGMA foreign_keys=ON;

-- Binalar ana tablo
CREATE TABLE IF NOT EXISTS buildings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  building_type TEXT NOT NULL CHECK(building_type IN ('apartman', 'site', 'plaza', 'rezidans')),
  customer_id INTEGER, -- Link to customers table for cleaning jobs
  address_line TEXT NOT NULL,
  district TEXT,
  city TEXT NOT NULL,
  postal_code TEXT,
  total_floors INTEGER,
  total_units INTEGER NOT NULL,
  construction_year INTEGER,
  manager_name TEXT,
  manager_phone TEXT,
  manager_email TEXT,
  tax_office TEXT,
  tax_number TEXT,
  bank_name TEXT,
  bank_iban TEXT,
  monthly_maintenance_day INTEGER DEFAULT 1, -- 1-28
  status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'terminated')),
  notes TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- Daireler/Unitler
CREATE TABLE IF NOT EXISTS units (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  building_id INTEGER NOT NULL,
  unit_type TEXT NOT NULL CHECK(unit_type IN ('daire', 'dubleks', 'ofis', 'dukkán', 'depo')),
  floor_number INTEGER,
  unit_number TEXT NOT NULL, -- '12A', 'D:5', 'Blok A-12'
  gross_area REAL,
  net_area REAL,
  room_count TEXT, -- '2+1', '3+1', 'Studio'
  owner_type TEXT NOT NULL DEFAULT 'owner' CHECK(owner_type IN ('owner', 'tenant', 'empty', 'company')),
  owner_name TEXT NOT NULL,
  owner_phone TEXT,
  owner_email TEXT,
  owner_id_number TEXT,
  owner_address TEXT,
  tenant_name TEXT,
  tenant_phone TEXT,
  tenant_email TEXT,
  tenant_contract_start TEXT,
  tenant_contract_end TEXT,
  monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  debt_balance DECIMAL(10,2) DEFAULT 0, -- Running debt balance
  parking_count INTEGER DEFAULT 0,
  storage_count INTEGER DEFAULT 0,
  status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'sold')),
  notes TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_buildings_status ON buildings(status);
CREATE INDEX IF NOT EXISTS idx_buildings_customer_id ON buildings(customer_id);
CREATE INDEX IF NOT EXISTS idx_buildings_building_type ON buildings(building_type);
CREATE INDEX IF NOT EXISTS idx_buildings_city ON buildings(city);
CREATE INDEX IF NOT EXISTS idx_units_building_id ON units(building_id);
CREATE INDEX IF NOT EXISTS idx_units_owner_type ON units(owner_type);
CREATE INDEX IF NOT EXISTS idx_units_status ON units(status);
CREATE INDEX IF NOT EXISTS idx_units_owner_name ON units(owner_name);
CREATE INDEX IF NOT EXISTS idx_units_unit_number ON units(unit_number);


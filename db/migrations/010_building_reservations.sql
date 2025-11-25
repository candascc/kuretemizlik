-- Apartman/Site Yönetimi - Reservations and Facility Booking
-- Migration 010: Building Reservations and Facility Management

PRAGMA foreign_keys=ON;

-- Rezervasyon alanları (havuz, salon, otopark, vs.)
CREATE TABLE IF NOT EXISTS building_facilities (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  building_id INTEGER NOT NULL,
  facility_name TEXT NOT NULL,
  facility_type TEXT NOT NULL CHECK(facility_type IN ('pool', 'gym', 'party_hall', 'playground', 'barbecue', 'parking', 'storage', 'other')),
  description TEXT,
  capacity INTEGER,
  hourly_rate DECIMAL(10,2) DEFAULT 0,
  daily_rate DECIMAL(10,2) DEFAULT 0,
  requires_approval INTEGER DEFAULT 1,
  max_advance_days INTEGER DEFAULT 30,
  is_active INTEGER DEFAULT 1,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE
);

-- Rezervasyonlar
CREATE TABLE IF NOT EXISTS building_reservations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  building_id INTEGER NOT NULL,
  facility_id INTEGER NOT NULL,
  unit_id INTEGER,
  resident_name TEXT NOT NULL,
  resident_phone TEXT,
  start_date TEXT NOT NULL,
  end_date TEXT NOT NULL,
  reservation_type TEXT DEFAULT 'hourly' CHECK(reservation_type IN ('hourly', 'daily', 'weekly')),
  total_amount DECIMAL(10,2) DEFAULT 0,
  deposit_amount DECIMAL(10,2) DEFAULT 0,
  status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected', 'cancelled', 'completed')),
  approved_by INTEGER,
  notes TEXT,
  cancelled_reason TEXT,
  created_by INTEGER,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE,
  FOREIGN KEY(facility_id) REFERENCES building_facilities(id) ON DELETE CASCADE,
  FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE SET NULL,
  FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_facilities_building_id ON building_facilities(building_id);
CREATE INDEX IF NOT EXISTS idx_facilities_type ON building_facilities(facility_type);
CREATE INDEX IF NOT EXISTS idx_facilities_active ON building_facilities(is_active);
CREATE INDEX IF NOT EXISTS idx_reservations_building_id ON building_reservations(building_id);
CREATE INDEX IF NOT EXISTS idx_reservations_facility_id ON building_reservations(facility_id);
CREATE INDEX IF NOT EXISTS idx_reservations_unit_id ON building_reservations(unit_id);
CREATE INDEX IF NOT EXISTS idx_reservations_status ON building_reservations(status);
CREATE INDEX IF NOT EXISTS idx_reservations_dates ON building_reservations(start_date, end_date);


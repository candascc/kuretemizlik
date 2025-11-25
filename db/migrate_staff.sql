-- Personel Yönetimi Tabloları
-- SQLite veritabanı şeması

PRAGMA journal_mode=WAL;
PRAGMA foreign_keys=ON;

-- Personel tablosu
CREATE TABLE IF NOT EXISTS staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    surname TEXT NOT NULL,
    phone TEXT,
    email TEXT,
    tc_number TEXT UNIQUE,
    birth_date TEXT,
    address TEXT,
    position TEXT,
    hire_date TEXT NOT NULL,
    salary REAL DEFAULT 0.00,
    hourly_rate REAL DEFAULT 0.00,
    photo TEXT,
    notes TEXT,
    status TEXT NOT NULL CHECK(status IN ('active', 'inactive', 'terminated')) DEFAULT 'active',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Personel devam/devamsızlık tablosu
CREATE TABLE IF NOT EXISTS staff_attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    date TEXT NOT NULL,
    check_in TEXT,
    check_out TEXT,
    break_start TEXT,
    break_end TEXT,
    total_hours REAL DEFAULT 0.00,
    overtime_hours REAL DEFAULT 0.00,
    status TEXT NOT NULL CHECK(status IN ('present', 'absent', 'late', 'half_day', 'sick_leave', 'annual_leave')) DEFAULT 'present',
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    UNIQUE (staff_id, date)
);

-- Personel iş atamaları tablosu
CREATE TABLE IF NOT EXISTS staff_job_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    job_id INTEGER NOT NULL,
    assigned_date TEXT NOT NULL,
    start_time TEXT,
    end_time TEXT,
    hourly_rate REAL,
    total_amount REAL,
    status TEXT NOT NULL CHECK(status IN ('assigned', 'completed', 'cancelled')) DEFAULT 'assigned',
    notes TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Personel maaş ve ödemeler tablosu
CREATE TABLE IF NOT EXISTS staff_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    payment_date TEXT NOT NULL,
    amount REAL NOT NULL,
    payment_type TEXT NOT NULL CHECK(payment_type IN ('salary', 'bonus', 'advance', 'deduction', 'overtime')) DEFAULT 'salary',
    description TEXT,
    reference_number TEXT,
    status TEXT NOT NULL CHECK(status IN ('pending', 'paid', 'cancelled')) DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
);

-- Personel alacak/verecek tablosu
CREATE TABLE IF NOT EXISTS staff_balances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    balance_type TEXT NOT NULL CHECK(balance_type IN ('receivable', 'payable')),
    amount REAL NOT NULL,
    description TEXT,
    due_date TEXT,
    status TEXT NOT NULL CHECK(status IN ('pending', 'paid', 'cancelled')) DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
);

-- İndeksler
CREATE INDEX IF NOT EXISTS idx_staff_status ON staff(status);
CREATE INDEX IF NOT EXISTS idx_staff_hire_date ON staff(hire_date);
CREATE INDEX IF NOT EXISTS idx_attendance_date ON staff_attendance(date);
CREATE INDEX IF NOT EXISTS idx_attendance_staff_date ON staff_attendance(staff_id, date);
CREATE INDEX IF NOT EXISTS idx_assignments_staff ON staff_job_assignments(staff_id);
CREATE INDEX IF NOT EXISTS idx_assignments_job ON staff_job_assignments(job_id);
CREATE INDEX IF NOT EXISTS idx_payments_staff ON staff_payments(staff_id);
CREATE INDEX IF NOT EXISTS idx_payments_date ON staff_payments(payment_date);
CREATE INDEX IF NOT EXISTS idx_balances_staff ON staff_balances(staff_id);

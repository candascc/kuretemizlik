-- Apartman/Site Yönetimi - Documents and Meetings
-- Migration 008: Document Management and Meeting System

PRAGMA foreign_keys=ON;

-- Doküman yönetimi
CREATE TABLE IF NOT EXISTS building_documents (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  building_id INTEGER NOT NULL,
  unit_id INTEGER, -- NULL for building-wide documents
  document_type TEXT NOT NULL CHECK(document_type IN ('contract', 'deed', 'permit', 'invoice', 'receipt', 'insurance', 'meeting_minutes', 'announcement', 'other')),
  title TEXT NOT NULL,
  description TEXT,
  file_path TEXT NOT NULL,
  file_name TEXT NOT NULL,
  file_size INTEGER,
  mime_type TEXT,
  is_public INTEGER DEFAULT 0, -- Residents can see?
  uploaded_by INTEGER NOT NULL,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE,
  FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE,
  FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Toplantı yönetimi
CREATE TABLE IF NOT EXISTS building_meetings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  building_id INTEGER NOT NULL,
  meeting_type TEXT NOT NULL CHECK(meeting_type IN ('regular', 'extraordinary', 'board')),
  title TEXT NOT NULL,
  description TEXT,
  meeting_date TEXT NOT NULL,
  location TEXT,
  agenda TEXT, -- JSON array of agenda items
  attendance_count INTEGER DEFAULT 0,
  quorum_reached INTEGER DEFAULT 0,
  minutes TEXT,
  minutes_document_id INTEGER,
  status TEXT DEFAULT 'scheduled' CHECK(status IN ('scheduled', 'completed', 'cancelled')),
  created_by INTEGER NOT NULL,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE,
  FOREIGN KEY(minutes_document_id) REFERENCES building_documents(id) ON DELETE SET NULL,
  FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Toplantı katılımcıları
CREATE TABLE IF NOT EXISTS meeting_attendees (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  meeting_id INTEGER NOT NULL,
  unit_id INTEGER NOT NULL,
  attendee_name TEXT NOT NULL,
  is_owner INTEGER DEFAULT 1,
  proxy_holder TEXT, -- If represented by proxy
  attended INTEGER DEFAULT 0,
  vote_weight DECIMAL(5,2) DEFAULT 1.0, -- Based on ownership %
  FOREIGN KEY(meeting_id) REFERENCES building_meetings(id) ON DELETE CASCADE,
  FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_documents_building_id ON building_documents(building_id);
CREATE INDEX IF NOT EXISTS idx_documents_unit_id ON building_documents(unit_id);
CREATE INDEX IF NOT EXISTS idx_documents_type ON building_documents(document_type);
CREATE INDEX IF NOT EXISTS idx_documents_uploaded_by ON building_documents(uploaded_by);
CREATE INDEX IF NOT EXISTS idx_meetings_building_id ON building_meetings(building_id);
CREATE INDEX IF NOT EXISTS idx_meetings_date ON building_meetings(meeting_date);
CREATE INDEX IF NOT EXISTS idx_meetings_status ON building_meetings(status);
CREATE INDEX IF NOT EXISTS idx_meeting_attendees_meeting_id ON meeting_attendees(meeting_id);
CREATE INDEX IF NOT EXISTS idx_meeting_attendees_unit_id ON meeting_attendees(unit_id);


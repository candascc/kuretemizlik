-- Apartman/Site Yönetimi - Surveys and Announcements
-- Migration 009: Survey System and Announcements

PRAGMA foreign_keys=ON;

-- Anket sistemi
CREATE TABLE IF NOT EXISTS building_surveys (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  building_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  description TEXT,
  survey_type TEXT NOT NULL CHECK(survey_type IN ('poll', 'vote', 'feedback', 'complaint')),
  start_date TEXT NOT NULL,
  end_date TEXT NOT NULL,
  is_anonymous INTEGER DEFAULT 0,
  allow_multiple INTEGER DEFAULT 0,
  status TEXT DEFAULT 'active' CHECK(status IN ('draft', 'active', 'closed')),
  created_by INTEGER NOT NULL,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE,
  FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Anket soruları
CREATE TABLE IF NOT EXISTS survey_questions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  survey_id INTEGER NOT NULL,
  question_text TEXT NOT NULL,
  question_type TEXT NOT NULL CHECK(question_type IN ('single', 'multiple', 'text', 'rating')),
  options TEXT, -- JSON array for choices
  is_required INTEGER DEFAULT 1,
  display_order INTEGER DEFAULT 0,
  FOREIGN KEY(survey_id) REFERENCES building_surveys(id) ON DELETE CASCADE
);

-- Anket cevapları
CREATE TABLE IF NOT EXISTS survey_responses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  survey_id INTEGER NOT NULL,
  question_id INTEGER NOT NULL,
  unit_id INTEGER,
  respondent_name TEXT,
  response_data TEXT NOT NULL, -- JSON
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(survey_id) REFERENCES building_surveys(id) ON DELETE CASCADE,
  FOREIGN KEY(question_id) REFERENCES survey_questions(id) ON DELETE CASCADE,
  FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE SET NULL
);

-- Duyurular
CREATE TABLE IF NOT EXISTS building_announcements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  building_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  content TEXT NOT NULL,
  announcement_type TEXT NOT NULL CHECK(announcement_type IN ('info', 'warning', 'urgent', 'event', 'maintenance')),
  priority INTEGER DEFAULT 0, -- 0=normal, 1=high, 2=urgent
  is_pinned INTEGER DEFAULT 0,
  publish_date TEXT NOT NULL,
  expire_date TEXT,
  send_email INTEGER DEFAULT 0,
  send_sms INTEGER DEFAULT 0,
  created_by INTEGER NOT NULL,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE,
  FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_surveys_building_id ON building_surveys(building_id);
CREATE INDEX IF NOT EXISTS idx_surveys_status ON building_surveys(status);
CREATE INDEX IF NOT EXISTS idx_surveys_dates ON building_surveys(start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_survey_questions_survey_id ON survey_questions(survey_id);
CREATE INDEX IF NOT EXISTS idx_survey_responses_survey_id ON survey_responses(survey_id);
CREATE INDEX IF NOT EXISTS idx_survey_responses_unit_id ON survey_responses(unit_id);
CREATE INDEX IF NOT EXISTS idx_announcements_building_id ON building_announcements(building_id);
CREATE INDEX IF NOT EXISTS idx_announcements_date ON building_announcements(publish_date);
CREATE INDEX IF NOT EXISTS idx_announcements_type ON building_announcements(announcement_type);
CREATE INDEX IF NOT EXISTS idx_announcements_priority ON building_announcements(priority);

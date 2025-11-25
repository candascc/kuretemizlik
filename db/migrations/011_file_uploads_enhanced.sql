-- Enhanced File Uploads System
-- Migration 011: File uploads table and enhancements

PRAGMA foreign_keys=ON;

-- File uploads table
CREATE TABLE IF NOT EXISTS file_uploads (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  original_name TEXT NOT NULL,
  filename TEXT NOT NULL,
  file_path TEXT NOT NULL,
  file_size INTEGER NOT NULL,
  mime_type TEXT NOT NULL,
  category TEXT NOT NULL DEFAULT 'documents',
  entity_type TEXT, -- 'job', 'contract', 'building_document', etc.
  entity_id INTEGER, -- Related entity ID
  uploaded_by INTEGER NOT NULL,
  thumbnail_path TEXT,
  metadata TEXT, -- JSON metadata
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_file_uploads_category ON file_uploads(category);
CREATE INDEX IF NOT EXISTS idx_file_uploads_entity ON file_uploads(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_file_uploads_uploaded_by ON file_uploads(uploaded_by);
CREATE INDEX IF NOT EXISTS idx_file_uploads_created_at ON file_uploads(created_at);

-- File upload progress tracking
CREATE TABLE IF NOT EXISTS file_upload_progress (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  session_id TEXT NOT NULL,
  filename TEXT NOT NULL,
  total_size INTEGER NOT NULL,
  uploaded_size INTEGER DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'uploading', -- 'uploading', 'processing', 'completed', 'failed'
  error_message TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  completed_at TEXT
);

-- Indexes for progress tracking
CREATE INDEX IF NOT EXISTS idx_upload_progress_session ON file_upload_progress(session_id);
CREATE INDEX IF NOT EXISTS idx_upload_progress_status ON file_upload_progress(status);

-- File access logs
CREATE TABLE IF NOT EXISTS file_access_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  file_id INTEGER NOT NULL,
  user_id INTEGER,
  action TEXT NOT NULL, -- 'view', 'download', 'delete'
  ip_address TEXT,
  user_agent TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(file_id) REFERENCES file_uploads(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Indexes for access logs
CREATE INDEX IF NOT EXISTS idx_file_access_file_id ON file_access_logs(file_id);
CREATE INDEX IF NOT EXISTS idx_file_access_user_id ON file_access_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_file_access_created_at ON file_access_logs(created_at);

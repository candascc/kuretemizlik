-- Notification System
-- Migration 011: Email and SMS Queue System

PRAGMA foreign_keys=ON;

-- Upgrade legacy email_queue structure (from migration 004)
ALTER TABLE email_queue RENAME TO email_queue_legacy;

CREATE TABLE email_queue (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  to_email TEXT NOT NULL,
  subject TEXT NOT NULL,
  message TEXT NOT NULL,
  template TEXT DEFAULT 'default',
  data TEXT,
  status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'sent', 'failed')),
  attempts INTEGER DEFAULT 0,
  max_attempts INTEGER DEFAULT 3,
  scheduled_at TEXT DEFAULT (datetime('now')),
  last_attempt_at TEXT,
  sent_at TEXT,
  error_message TEXT,
  created_at TEXT DEFAULT (datetime('now'))
);

INSERT INTO email_queue (
  id,
  to_email,
  subject,
  message,
  template,
  data,
  status,
  attempts,
  max_attempts,
  scheduled_at,
  last_attempt_at,
  sent_at,
  error_message,
  created_at
)
SELECT
  id,
  to_email,
  subject,
  body AS message,
  COALESCE(type, 'default') AS template,
  NULL AS data,
  CASE WHEN status = 'sending' THEN 'pending' ELSE status END AS status,
  retry_count AS attempts,
  max_retries AS max_attempts,
  created_at AS scheduled_at,
  NULL AS last_attempt_at,
  sent_at,
  error_message,
  created_at
FROM email_queue_legacy;

DROP TABLE email_queue_legacy;

-- SMS queue
CREATE TABLE IF NOT EXISTS sms_queue (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  to_phone TEXT NOT NULL,
  message TEXT NOT NULL,
  data TEXT,
  status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'sent', 'failed')),
  attempts INTEGER DEFAULT 0,
  max_attempts INTEGER DEFAULT 3,
  scheduled_at TEXT DEFAULT (datetime('now')),
  last_attempt_at TEXT,
  sent_at TEXT,
  error_message TEXT,
  created_at TEXT DEFAULT (datetime('now'))
);

-- Notification logs
CREATE TABLE IF NOT EXISTS notification_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  resident_id INTEGER,
  type TEXT NOT NULL CHECK(type IN ('email', 'sms', 'push')),
  subject TEXT,
  template TEXT DEFAULT 'default',
  data TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(resident_id) REFERENCES resident_users(id) ON DELETE SET NULL
);

-- Notification preferences
CREATE TABLE IF NOT EXISTS notification_preferences (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  resident_id INTEGER NOT NULL,
  email_enabled INTEGER DEFAULT 1,
  sms_enabled INTEGER DEFAULT 0,
  push_enabled INTEGER DEFAULT 1,
  fee_reminders INTEGER DEFAULT 1,
  meeting_notifications INTEGER DEFAULT 1,
  announcement_notifications INTEGER DEFAULT 1,
  request_updates INTEGER DEFAULT 1,
  payment_confirmations INTEGER DEFAULT 1,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(resident_id) REFERENCES resident_users(id) ON DELETE CASCADE,
  UNIQUE(resident_id)
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_email_queue_status ON email_queue(status);
CREATE INDEX IF NOT EXISTS idx_email_queue_scheduled ON email_queue(scheduled_at);
CREATE INDEX IF NOT EXISTS idx_email_queue_created ON email_queue(created_at);
CREATE INDEX IF NOT EXISTS idx_sms_queue_status ON sms_queue(status);
CREATE INDEX IF NOT EXISTS idx_sms_queue_scheduled ON sms_queue(scheduled_at);
CREATE INDEX IF NOT EXISTS idx_sms_queue_created ON sms_queue(created_at);
CREATE INDEX IF NOT EXISTS idx_notification_logs_resident ON notification_logs(resident_id);
CREATE INDEX IF NOT EXISTS idx_notification_logs_type ON notification_logs(type);
CREATE INDEX IF NOT EXISTS idx_notification_preferences_resident ON notification_preferences(resident_id);

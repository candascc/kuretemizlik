-- Add reminder tracking to jobs table
ALTER TABLE jobs ADD COLUMN reminder_sent INTEGER DEFAULT 0;

CREATE INDEX IF NOT EXISTS idx_jobs_reminder_sent ON jobs(reminder_sent);


-- Resident profile enhancements: secondary contacts and notification preferences
ALTER TABLE resident_users ADD COLUMN secondary_email TEXT;
ALTER TABLE resident_users ADD COLUMN secondary_phone TEXT;
ALTER TABLE resident_users ADD COLUMN notify_email INTEGER DEFAULT 1;
ALTER TABLE resident_users ADD COLUMN notify_sms INTEGER DEFAULT 0;


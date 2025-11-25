CREATE INDEX IF NOT EXISTS idx_resident_users_active_login
    ON resident_users(is_active, last_login_at);

CREATE INDEX IF NOT EXISTS idx_resident_users_email_verified
    ON resident_users(email_verified);

CREATE INDEX IF NOT EXISTS idx_resident_notification_preferences_category_resident
    ON resident_notification_preferences(category, resident_user_id);


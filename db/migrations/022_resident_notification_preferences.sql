CREATE TABLE IF NOT EXISTS resident_notification_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resident_user_id INTEGER NOT NULL,
    category TEXT NOT NULL,
    notify_email INTEGER NOT NULL DEFAULT 1,
    notify_sms INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE(resident_user_id, category),
    FOREIGN KEY (resident_user_id) REFERENCES resident_users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_resident_notification_preferences_resident
    ON resident_notification_preferences(resident_user_id);

CREATE INDEX IF NOT EXISTS idx_resident_notification_preferences_category
    ON resident_notification_preferences(category);


-- Resident contact verification log
CREATE TABLE IF NOT EXISTS resident_contact_verifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resident_user_id INTEGER NOT NULL,
    verification_type TEXT NOT NULL, -- email | phone
    new_value TEXT NOT NULL,
    code_hash TEXT NOT NULL,
    channel TEXT NOT NULL, -- email | sms
    status TEXT NOT NULL DEFAULT 'pending', -- pending | verified | expired | cancelled | superseded
    attempts INTEGER NOT NULL DEFAULT 0,
    max_attempts INTEGER NOT NULL DEFAULT 5,
    expires_at DATETIME NOT NULL,
    sent_at DATETIME NOT NULL,
    meta TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (resident_user_id) REFERENCES resident_users(id)
);

CREATE INDEX IF NOT EXISTS idx_resident_contact_verifications_resident
    ON resident_contact_verifications(resident_user_id, verification_type, status);

CREATE INDEX IF NOT EXISTS idx_resident_contact_verifications_expires
    ON resident_contact_verifications(status, expires_at);


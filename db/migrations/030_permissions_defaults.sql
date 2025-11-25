PRAGMA foreign_keys = OFF;

CREATE TABLE permissions_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    category TEXT NOT NULL DEFAULT 'general',
    is_system_permission INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

INSERT INTO permissions_new (id, name, description, category, is_system_permission, created_at, updated_at)
SELECT
    id,
    name,
    COALESCE(description, ''),
    COALESCE(category, 'general'),
    0,
    created_at,
    updated_at
FROM permissions;

DROP TABLE permissions;

ALTER TABLE permissions_new RENAME TO permissions;

CREATE INDEX IF NOT EXISTS idx_permissions_category ON permissions(category);

PRAGMA foreign_keys = ON;



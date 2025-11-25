PRAGMA foreign_keys = OFF;

CREATE TABLE IF NOT EXISTS queue_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    queue TEXT NOT NULL DEFAULT 'default',
    payload TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    reserved_at INTEGER,
    available_at INTEGER NOT NULL DEFAULT (strftime('%s','now')),
    created_at INTEGER NOT NULL DEFAULT (strftime('%s','now')),
    failed_at INTEGER,
    error TEXT
);

CREATE INDEX IF NOT EXISTS idx_queue_jobs_queue ON queue_jobs(queue);
CREATE INDEX IF NOT EXISTS idx_queue_jobs_available ON queue_jobs(queue, available_at);
CREATE INDEX IF NOT EXISTS idx_queue_jobs_failed ON queue_jobs(failed_at) WHERE failed_at IS NOT NULL;

PRAGMA foreign_keys = ON;



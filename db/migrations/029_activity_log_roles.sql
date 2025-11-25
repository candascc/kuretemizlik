ALTER TABLE activity_log
    ADD COLUMN actor_role TEXT;

ALTER TABLE activity_log
    ADD COLUMN entity_id INTEGER;

UPDATE activity_log
SET actor_role = (
    SELECT role FROM users WHERE users.id = activity_log.actor_id
)
WHERE actor_role IS NULL;

CREATE INDEX IF NOT EXISTS idx_activity_log_actor_role ON activity_log(actor_role);
CREATE INDEX IF NOT EXISTS idx_activity_log_entity_id ON activity_log(entity_id);


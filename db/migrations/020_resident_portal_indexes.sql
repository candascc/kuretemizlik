-- Indexes to improve resident portal pagination performance
CREATE INDEX IF NOT EXISTS idx_management_fees_unit_status_due
    ON management_fees(unit_id, status, due_date);

CREATE INDEX IF NOT EXISTS idx_resident_requests_unit_status_created
    ON resident_requests(unit_id, status, created_at);

CREATE INDEX IF NOT EXISTS idx_building_announcements_active
    ON building_announcements(building_id, expire_date);

CREATE INDEX IF NOT EXISTS idx_building_meetings_schedule
    ON building_meetings(building_id, meeting_date, status);


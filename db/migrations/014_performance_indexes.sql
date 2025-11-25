-- Performance Indexes
-- Migration 014: Add composite and frequently-used indexes for query optimization

PRAGMA foreign_keys=ON;

-- Jobs table indexes
CREATE INDEX IF NOT EXISTS idx_jobs_status_start_at ON jobs(status, start_at);
CREATE INDEX IF NOT EXISTS idx_jobs_customer_status ON jobs(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_jobs_recurring_id ON jobs(recurring_job_id) WHERE recurring_job_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_jobs_payment_status ON jobs(payment_status) WHERE payment_status != 'PAID';

-- Management fees indexes
CREATE INDEX IF NOT EXISTS idx_management_fees_building_status ON management_fees(building_id, status);
CREATE INDEX IF NOT EXISTS idx_management_fees_unit_period ON management_fees(unit_id, period_month, period_year);
CREATE INDEX IF NOT EXISTS idx_management_fees_due_date ON management_fees(due_date) WHERE status != 'paid';
CREATE INDEX IF NOT EXISTS idx_management_fees_building_due ON management_fees(building_id, due_date, status);

-- Building expenses indexes
CREATE INDEX IF NOT EXISTS idx_building_expenses_building_status ON building_expenses(building_id, approval_status);
CREATE INDEX IF NOT EXISTS idx_building_expenses_date ON building_expenses(expense_date, building_id);
CREATE INDEX IF NOT EXISTS idx_building_expenses_category ON building_expenses(category, building_id);

-- Activity log indexes
CREATE INDEX IF NOT EXISTS idx_activity_log_actor_created ON activity_log(actor_id, created_at);
CREATE INDEX IF NOT EXISTS idx_activity_log_entity_created ON activity_log(entity, created_at);
CREATE INDEX IF NOT EXISTS idx_activity_log_action_created ON activity_log(action, created_at);

-- Composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_units_building_status ON units(building_id, status);
CREATE INDEX IF NOT EXISTS idx_addresses_customer ON addresses(customer_id);

-- Appointments indexes
CREATE INDEX IF NOT EXISTS idx_appointments_date_status ON appointments(appointment_date, status);
CREATE INDEX IF NOT EXISTS idx_appointments_customer_date ON appointments(customer_id, appointment_date);

-- Contracts indexes
CREATE INDEX IF NOT EXISTS idx_contracts_status_dates ON contracts(status, start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_contracts_customer ON contracts(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_contracts_expiring ON contracts(end_date, status) WHERE end_date IS NOT NULL AND status = 'ACTIVE';

-- Building meetings indexes
CREATE INDEX IF NOT EXISTS idx_meetings_building_date ON building_meetings(building_id, meeting_date);
CREATE INDEX IF NOT EXISTS idx_meetings_status_date ON building_meetings(status, meeting_date);

-- Building announcements indexes
CREATE INDEX IF NOT EXISTS idx_announcements_building_date ON building_announcements(building_id, publish_date);

-- Building surveys indexes
CREATE INDEX IF NOT EXISTS idx_surveys_building_status ON building_surveys(building_id, status);

-- Resident requests indexes
CREATE INDEX IF NOT EXISTS idx_resident_requests_building_status ON resident_requests(building_id, status);
CREATE INDEX IF NOT EXISTS idx_resident_requests_unit ON resident_requests(unit_id, status);
CREATE INDEX IF NOT EXISTS idx_resident_requests_created ON resident_requests(created_at, building_id);

-- Resident users indexes
CREATE INDEX IF NOT EXISTS idx_resident_users_unit_active ON resident_users(unit_id, is_active);
CREATE INDEX IF NOT EXISTS idx_resident_users_email ON resident_users(email) WHERE email IS NOT NULL;

-- Slow queries tracking (for performance monitoring)
CREATE INDEX IF NOT EXISTS idx_slow_queries_occurred ON slow_queries(occurred_at);
CREATE INDEX IF NOT EXISTS idx_slow_queries_duration ON slow_queries(duration_ms);

PRAGMA foreign_keys=ON;


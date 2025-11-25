-- Auto-generated install script
-- Source: schema-current.sql
-- Generated: 2025-11-08 14:49:26

PRAGMA journal_mode=WAL;
PRAGMA foreign_keys=ON;

-- TABLE: companies
CREATE TABLE companies ( id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, subdomain TEXT UNIQUE, owner_name TEXT, owner_email TEXT, owner_phone TEXT, address TEXT, tax_number TEXT, is_active INTEGER NOT NULL DEFAULT 1, settings_json TEXT, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')) );

-- TABLE: activity_log
CREATE TABLE activity_log ( id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INTEGER, action TEXT NOT NULL, entity TEXT, meta_json TEXT, created_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY(actor_id) REFERENCES users(id) );

-- TABLE: addresses
CREATE TABLE addresses ( id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER NOT NULL, company_id INTEGER NOT NULL DEFAULT 1, label TEXT, line TEXT NOT NULL, city TEXT, created_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE, FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE );

-- TABLE: appointments
CREATE TABLE appointments ( id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER NOT NULL, service_id INTEGER, title TEXT NOT NULL, description TEXT, appointment_date TEXT NOT NULL, start_time TEXT NOT NULL, end_time TEXT, status TEXT NOT NULL CHECK(status IN ('SCHEDULED','CONFIRMED','COMPLETED','CANCELLED','NO_SHOW')) DEFAULT 'SCHEDULED', priority TEXT CHECK(priority IN ('LOW','MEDIUM','HIGH','URGENT')) DEFAULT 'MEDIUM', assigned_to INTEGER, notes TEXT, reminder_sent INTEGER DEFAULT 0, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE, FOREIGN KEY(service_id) REFERENCES services(id), FOREIGN KEY(assigned_to) REFERENCES users(id) );

-- TABLE: building_announcements
CREATE TABLE building_announcements ( id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER NOT NULL, title TEXT NOT NULL, content TEXT NOT NULL, announcement_type TEXT NOT NULL CHECK(announcement_type IN ('info', 'warning', 'urgent', 'event', 'maintenance')), priority INTEGER DEFAULT 0, is_pinned INTEGER DEFAULT 0, publish_date TEXT NOT NULL, expire_date TEXT, send_email INTEGER DEFAULT 0, send_sms INTEGER DEFAULT 0, created_by INTEGER NOT NULL, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE, FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE );

-- TABLE: building_documents
CREATE TABLE building_documents ( id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER NOT NULL, unit_id INTEGER, document_type TEXT NOT NULL CHECK(document_type IN ('contract', 'deed', 'permit', 'invoice', 'receipt', 'insurance', 'meeting_minutes', 'announcement', 'other')), title TEXT NOT NULL, description TEXT, file_path TEXT NOT NULL, file_name TEXT NOT NULL, file_size INTEGER, mime_type TEXT, is_public INTEGER DEFAULT 0, uploaded_by INTEGER NOT NULL, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE, FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE, FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE CASCADE );

-- TABLE: building_expenses
CREATE TABLE building_expenses ( id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER NOT NULL, category TEXT NOT NULL, subcategory TEXT, amount DECIMAL(10,2) NOT NULL, expense_date TEXT NOT NULL, invoice_number TEXT, vendor_name TEXT, vendor_tax_number TEXT, payment_method TEXT CHECK(payment_method IN ('cash', 'transfer', 'card', 'check')), is_recurring INTEGER DEFAULT 0, description TEXT, receipt_path TEXT, created_by INTEGER, approved_by INTEGER, approval_status TEXT DEFAULT 'pending' CHECK(approval_status IN ('pending', 'approved', 'rejected')), created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE, FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL, FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL );

-- TABLE: building_facilities
CREATE TABLE building_facilities ( id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER NOT NULL, facility_name TEXT NOT NULL, facility_type TEXT NOT NULL CHECK(facility_type IN ('pool', 'gym', 'party_hall', 'playground', 'barbecue', 'parking', 'storage', 'other')), description TEXT, capacity INTEGER, hourly_rate DECIMAL(10,2) DEFAULT 0, daily_rate DECIMAL(10,2) DEFAULT 0, requires_approval INTEGER DEFAULT 1, max_advance_days INTEGER DEFAULT 30, is_active INTEGER DEFAULT 1, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE );

-- TABLE: building_meetings
CREATE TABLE building_meetings ( id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER NOT NULL, meeting_type TEXT NOT NULL CHECK(meeting_type IN ('regular', 'extraordinary', 'board')), title TEXT NOT NULL, description TEXT, meeting_date TEXT NOT NULL, location TEXT, agenda TEXT, attendance_count INTEGER DEFAULT 0, quorum_reached INTEGER DEFAULT 0, minutes TEXT, minutes_document_id INTEGER, status TEXT DEFAULT 'scheduled' CHECK(status IN ('scheduled', 'completed', 'cancelled')), created_by INTEGER NOT NULL, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE, FOREIGN KEY(minutes_document_id) REFERENCES building_documents(id) ON DELETE SET NULL, FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE );

-- TABLE: building_reservations
CREATE TABLE building_reservations ( id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER NOT NULL, facility_id INTEGER NOT NULL, unit_id INTEGER, resident_name TEXT NOT NULL, resident_phone TEXT, start_date TEXT NOT NULL, end_date TEXT NOT NULL, reservation_type TEXT DEFAULT 'hourly' CHECK(reservation_type IN ('hourly', 'daily', 'weekly')), total_amount DECIMAL(10,2) DEFAULT 0, deposit_amount DECIMAL(10,2) DEFAULT 0, status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected', 'cancelled', 'completed')), approved_by INTEGER, notes TEXT, cancelled_reason TEXT, created_by INTEGER, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE, FOREIGN KEY(facility_id) REFERENCES building_facilities(id) ON DELETE CASCADE, FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE SET NULL, FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL, FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL );

-- TABLE: building_surveys
CREATE TABLE building_surveys ( id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER NOT NULL, title TEXT NOT NULL, description TEXT, survey_type TEXT NOT NULL CHECK(survey_type IN ('poll', 'vote', 'feedback', 'complaint')), start_date TEXT NOT NULL, end_date TEXT NOT NULL, is_anonymous INTEGER DEFAULT 0, allow_multiple INTEGER DEFAULT 0, status TEXT DEFAULT 'active' CHECK(status IN ('draft', 'active', 'closed')), created_by INTEGER NOT NULL, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE, FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE );

-- TABLE: buildings
CREATE TABLE buildings ( id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, building_type TEXT NOT NULL CHECK(building_type IN ('apartman', 'site', 'plaza', 'rezidans')), customer_id INTEGER, address_line TEXT NOT NULL, district TEXT, city TEXT NOT NULL, postal_code TEXT, total_floors INTEGER, total_units INTEGER NOT NULL, construction_year INTEGER, manager_name TEXT, manager_phone TEXT, manager_email TEXT, tax_office TEXT, tax_number TEXT, bank_name TEXT, bank_iban TEXT, monthly_maintenance_day INTEGER DEFAULT 1, status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'terminated')), notes TEXT, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL );

-- TABLE: comment_attachments
CREATE TABLE comment_attachments ( id INTEGER PRIMARY KEY AUTOINCREMENT, comment_id INTEGER NOT NULL, file_id INTEGER NOT NULL, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(comment_id) REFERENCES comments(id) ON DELETE CASCADE, FOREIGN KEY(file_id) REFERENCES file_uploads(id) ON DELETE CASCADE );

-- TABLE: comment_mentions
CREATE TABLE comment_mentions ( id INTEGER PRIMARY KEY AUTOINCREMENT, comment_id INTEGER NOT NULL, mentioned_user_id INTEGER NOT NULL, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(comment_id) REFERENCES comments(id) ON DELETE CASCADE, FOREIGN KEY(mentioned_user_id) REFERENCES users(id) ON DELETE CASCADE );

-- TABLE: comment_reactions
CREATE TABLE comment_reactions ( id INTEGER PRIMARY KEY AUTOINCREMENT, comment_id INTEGER NOT NULL, user_id INTEGER NOT NULL, reaction_type TEXT NOT NULL, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(comment_id) REFERENCES comments(id) ON DELETE CASCADE, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, UNIQUE(comment_id, user_id, reaction_type) );

-- TABLE: comments
CREATE TABLE comments ( id INTEGER PRIMARY KEY AUTOINCREMENT, entity_type TEXT NOT NULL, entity_id INTEGER NOT NULL, parent_id INTEGER, user_id INTEGER NOT NULL, content TEXT NOT NULL, is_internal INTEGER DEFAULT 0, is_pinned INTEGER DEFAULT 0, status TEXT DEFAULT 'active', metadata TEXT, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(parent_id) REFERENCES comments(id) ON DELETE CASCADE, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE );

-- TABLE: contract_attachments
CREATE TABLE contract_attachments ( id INTEGER PRIMARY KEY AUTOINCREMENT, contract_id INTEGER NOT NULL, file_name TEXT NOT NULL, file_path TEXT NOT NULL, file_size INTEGER, mime_type TEXT, uploaded_by INTEGER NOT NULL, created_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY(contract_id) REFERENCES contracts(id) ON DELETE CASCADE, FOREIGN KEY(uploaded_by) REFERENCES users(id) );

-- TABLE: contract_payments
CREATE TABLE contract_payments ( id INTEGER PRIMARY KEY AUTOINCREMENT, contract_id INTEGER NOT NULL, amount REAL NOT NULL, payment_date TEXT NOT NULL, payment_method TEXT CHECK(payment_method IN ('CASH','BANK_TRANSFER','CREDIT_CARD','CHECK')) DEFAULT 'CASH', status TEXT NOT NULL CHECK(status IN ('PENDING','PAID','OVERDUE','CANCELLED')) DEFAULT 'PENDING', due_date TEXT, notes TEXT, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY(contract_id) REFERENCES contracts(id) ON DELETE CASCADE );

-- TABLE: contracts
CREATE TABLE contracts ( id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER NOT NULL, contract_number TEXT UNIQUE NOT NULL, title TEXT NOT NULL, description TEXT, contract_type TEXT NOT NULL CHECK(contract_type IN ('CLEANING','MAINTENANCE','RECURRING','ONE_TIME')) DEFAULT 'CLEANING', start_date TEXT NOT NULL, end_date TEXT, total_amount REAL, payment_terms TEXT, status TEXT NOT NULL CHECK(status IN ('DRAFT','ACTIVE','SUSPENDED','COMPLETED','TERMINATED')) DEFAULT 'DRAFT', auto_renewal INTEGER DEFAULT 0, renewal_period_days INTEGER, file_path TEXT, notes TEXT, created_by INTEGER NOT NULL, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE, FOREIGN KEY(created_by) REFERENCES users(id) );

-- TABLE: customers
CREATE TABLE customers ( id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, phone TEXT, email TEXT, company_id INTEGER NOT NULL DEFAULT 1, notes TEXT, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY(company_id) REFERENCES companies(id) );

-- TABLE: email_logs
CREATE TABLE email_logs ( id INTEGER PRIMARY KEY AUTOINCREMENT, job_id INTEGER, customer_id INTEGER, to_email TEXT NOT NULL, subject TEXT NOT NULL, type TEXT NOT NULL, status TEXT NOT NULL CHECK(status IN ('pending', 'sent', 'failed')), error_message TEXT, sent_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY(job_id) REFERENCES jobs(id), FOREIGN KEY(customer_id) REFERENCES customers(id) );

-- TABLE: email_queue
CREATE TABLE email_queue ( id INTEGER PRIMARY KEY AUTOINCREMENT, to_email TEXT NOT NULL, subject TEXT NOT NULL, message TEXT NOT NULL, template TEXT DEFAULT 'default', data TEXT, status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'sent', 'failed')), attempts INTEGER DEFAULT 0, max_attempts INTEGER DEFAULT 3, scheduled_at TEXT DEFAULT (datetime('now')), last_attempt_at TEXT, sent_at TEXT, error_message TEXT, created_at TEXT DEFAULT (datetime('now')) );

-- TABLE: file_access_logs
CREATE TABLE file_access_logs ( id INTEGER PRIMARY KEY AUTOINCREMENT, file_id INTEGER NOT NULL, user_id INTEGER, action TEXT NOT NULL, ip_address TEXT, user_agent TEXT, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(file_id) REFERENCES file_uploads(id) ON DELETE CASCADE, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL );

-- TABLE: file_upload_progress
CREATE TABLE file_upload_progress ( id INTEGER PRIMARY KEY AUTOINCREMENT, session_id TEXT NOT NULL, filename TEXT NOT NULL, total_size INTEGER NOT NULL, uploaded_size INTEGER DEFAULT 0, status TEXT NOT NULL DEFAULT 'uploading', error_message TEXT, created_at TEXT DEFAULT (datetime('now')), completed_at TEXT );

-- TABLE: file_uploads
CREATE TABLE file_uploads ( id INTEGER PRIMARY KEY AUTOINCREMENT, original_name TEXT NOT NULL, filename TEXT NOT NULL, file_path TEXT NOT NULL, file_size INTEGER NOT NULL, mime_type TEXT NOT NULL, category TEXT NOT NULL DEFAULT 'documents', entity_type TEXT, entity_id INTEGER, uploaded_by INTEGER NOT NULL, thumbnail_path TEXT, metadata TEXT, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE CASCADE );

-- TABLE: job_payments
CREATE TABLE job_payments ( id INTEGER PRIMARY KEY AUTOINCREMENT, job_id INTEGER NOT NULL, amount REAL NOT NULL, paid_at TEXT NOT NULL DEFAULT (date('now')), note TEXT, finance_id INTEGER, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE CASCADE, FOREIGN KEY(finance_id) REFERENCES money_entries(id) ON DELETE SET NULL );

-- TABLE: jobs
CREATE TABLE jobs ( id INTEGER PRIMARY KEY AUTOINCREMENT, service_id INTEGER, customer_id INTEGER NOT NULL, company_id INTEGER NOT NULL DEFAULT 1, address_id INTEGER, start_at TEXT NOT NULL, end_at TEXT NOT NULL, status TEXT NOT NULL CHECK(status IN ('SCHEDULED','DONE','CANCELLED')) DEFAULT 'SCHEDULED', total_amount REAL NOT NULL DEFAULT 0, amount_paid REAL NOT NULL DEFAULT 0, payment_status TEXT NOT NULL CHECK(payment_status IN ('UNPAID','PARTIAL','PAID')) DEFAULT 'UNPAID', assigned_to INTEGER, note TEXT, income_id INTEGER, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), recurring_job_id INTEGER NULL, occurrence_id INTEGER NULL, reminder_sent INTEGER DEFAULT 0, FOREIGN KEY(service_id) REFERENCES services(id), FOREIGN KEY(customer_id) REFERENCES customers(id), FOREIGN KEY(address_id) REFERENCES addresses(id), FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE );

-- TABLE: management_fee_definitions
CREATE TABLE management_fee_definitions ( id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER NOT NULL, name TEXT NOT NULL, fee_type TEXT NOT NULL CHECK(fee_type IN ('fixed', 'per_sqm', 'per_person', 'custom')), amount DECIMAL(10,2) NOT NULL, is_mandatory INTEGER DEFAULT 1, description TEXT, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE );

-- TABLE: management_fees
CREATE TABLE management_fees ( id INTEGER PRIMARY KEY AUTOINCREMENT, unit_id INTEGER NOT NULL, building_id INTEGER NOT NULL, definition_id INTEGER, period TEXT NOT NULL, fee_name TEXT NOT NULL, base_amount DECIMAL(10,2) NOT NULL, discount_amount DECIMAL(10,2) DEFAULT 0, late_fee DECIMAL(10,2) DEFAULT 0, total_amount DECIMAL(10,2) NOT NULL, paid_amount DECIMAL(10,2) DEFAULT 0, status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'partial', 'paid', 'overdue', 'cancelled')), due_date TEXT NOT NULL, payment_date TEXT, payment_method TEXT CHECK(payment_method IN ('cash', 'transfer', 'card', 'check')), receipt_number TEXT, notes TEXT, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE, FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE, FOREIGN KEY(definition_id) REFERENCES management_fee_definitions(id) ON DELETE SET NULL );

-- TABLE: meeting_attendees
CREATE TABLE meeting_attendees ( id INTEGER PRIMARY KEY AUTOINCREMENT, meeting_id INTEGER NOT NULL, unit_id INTEGER NOT NULL, attendee_name TEXT NOT NULL, is_owner INTEGER DEFAULT 1, proxy_holder TEXT, attended INTEGER DEFAULT 0, vote_weight DECIMAL(5,2) DEFAULT 1.0, FOREIGN KEY(meeting_id) REFERENCES building_meetings(id) ON DELETE CASCADE, FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE );

-- TABLE: meeting_topics
CREATE TABLE meeting_topics ( id INTEGER PRIMARY KEY AUTOINCREMENT, meeting_id INTEGER NOT NULL, topic_title TEXT NOT NULL, topic_description TEXT, topic_type TEXT NOT NULL CHECK(topic_type IN ('information', 'voting', 'discussion', 'approval')), voting_enabled INTEGER DEFAULT 0, voting_type TEXT CHECK(voting_type IN ('yes_no', 'multi_choice', 'approval', 'budget')), voting_options TEXT, requires_quorum INTEGER DEFAULT 0, quorum_percentage DECIMAL(5,2) DEFAULT 50.00, is_approved INTEGER DEFAULT 0, display_order INTEGER DEFAULT 0, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(meeting_id) REFERENCES building_meetings(id) ON DELETE CASCADE );

-- TABLE: meeting_votes
CREATE TABLE meeting_votes ( id INTEGER PRIMARY KEY AUTOINCREMENT, meeting_id INTEGER NOT NULL, topic_id INTEGER NOT NULL, attendee_id INTEGER NOT NULL, vote_value TEXT NOT NULL, vote_weight DECIMAL(5,2) DEFAULT 1.0, comment TEXT, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(meeting_id) REFERENCES building_meetings(id) ON DELETE CASCADE, FOREIGN KEY(topic_id) REFERENCES meeting_topics(id) ON DELETE CASCADE, FOREIGN KEY(attendee_id) REFERENCES meeting_attendees(id) ON DELETE CASCADE, UNIQUE(meeting_id, topic_id, attendee_id) );

-- TABLE: migration_log
CREATE TABLE migration_log ( migration_name TEXT PRIMARY KEY, executed_at TEXT NOT NULL );

-- TABLE: money_entries
CREATE TABLE money_entries ( id INTEGER PRIMARY KEY AUTOINCREMENT, kind TEXT NOT NULL CHECK(kind IN ('INCOME','EXPENSE')), category TEXT NOT NULL, amount REAL NOT NULL, date TEXT NOT NULL, note TEXT, job_id INTEGER, created_by INTEGER NOT NULL, company_id INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), ecurring_job_id INTEGER, is_archived INTEGER DEFAULT 0, FOREIGN KEY(job_id) REFERENCES jobs(id), FOREIGN KEY(created_by) REFERENCES users(id), FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE );

-- TABLE: notification_logs
CREATE TABLE notification_logs ( id INTEGER PRIMARY KEY AUTOINCREMENT, resident_id INTEGER, type TEXT NOT NULL CHECK(type IN ('email', 'sms', 'push')), subject TEXT, template TEXT DEFAULT 'default', data TEXT, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(resident_id) REFERENCES resident_users(id) ON DELETE SET NULL );

-- TABLE: notification_preferences
CREATE TABLE notification_preferences ( id INTEGER PRIMARY KEY AUTOINCREMENT, resident_id INTEGER NOT NULL, email_enabled INTEGER DEFAULT 1, sms_enabled INTEGER DEFAULT 0, push_enabled INTEGER DEFAULT 1, fee_reminders INTEGER DEFAULT 1, meeting_notifications INTEGER DEFAULT 1, announcement_notifications INTEGER DEFAULT 1, request_updates INTEGER DEFAULT 1, payment_confirmations INTEGER DEFAULT 1, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(resident_id) REFERENCES resident_users(id) ON DELETE CASCADE, UNIQUE(resident_id) );

-- TABLE: online_payments
CREATE TABLE online_payments ( id INTEGER PRIMARY KEY AUTOINCREMENT, management_fee_id INTEGER NOT NULL, resident_user_id INTEGER, amount DECIMAL(10,2) NOT NULL, payment_method TEXT NOT NULL CHECK(payment_method IN ('card', 'bank_transfer', 'mobile_payment')), payment_provider TEXT, transaction_id TEXT UNIQUE, status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded')), payment_data TEXT, error_message TEXT, processed_at TEXT, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(management_fee_id) REFERENCES management_fees(id) ON DELETE CASCADE, FOREIGN KEY(resident_user_id) REFERENCES resident_users(id) ON DELETE SET NULL );

-- TABLE: password_reset_tokens
CREATE TABLE password_reset_tokens ( id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, token TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT (datetime('now')), used_at TEXT, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE );

-- TABLE: rate_limits
CREATE TABLE rate_limits ( rate_key TEXT PRIMARY KEY, attempts INTEGER NOT NULL DEFAULT 0, first_attempt_at INTEGER, blocked_until INTEGER );

-- TABLE: recurring_job_occurrences
CREATE TABLE recurring_job_occurrences ( id INTEGER PRIMARY KEY AUTOINCREMENT, recurring_job_id INTEGER NOT NULL, date TEXT NOT NULL, start_at TEXT NOT NULL, end_at TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'PLANNED', created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), `job_id` INTEGER, FOREIGN KEY(recurring_job_id) REFERENCES recurring_jobs(id) ON DELETE CASCADE );

-- TABLE: recurring_jobs
CREATE TABLE recurring_jobs ( id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER NOT NULL, company_id INTEGER NOT NULL DEFAULT 1, address_id INTEGER, service_id INTEGER, frequency TEXT NOT NULL, interval INTEGER NOT NULL DEFAULT 1, byweekday TEXT, byhour INTEGER, byminute INTEGER, duration_min INTEGER NOT NULL DEFAULT 60, start_date TEXT NOT NULL, end_date TEXT, timezone TEXT NOT NULL DEFAULT 'Europe/Istanbul', status TEXT NOT NULL DEFAULT 'ACTIVE', default_total_amount REAL NOT NULL DEFAULT 0, default_notes TEXT, default_assignees TEXT, exclusions TEXT, holiday_policy TEXT DEFAULT 'SKIP', created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')) , ymonthday INTEGER, pricing_model TEXT DEFAULT 'PER_JOB', monthly_amount REAL, contract_total_amount REAL, FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE);

-- TABLE: remember_tokens
CREATE TABLE remember_tokens ( id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, token TEXT, token_hash TEXT, expires_at TEXT NOT NULL, last_used_at TEXT, created_at TEXT NOT NULL, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE );

-- TABLE: resident_requests
CREATE TABLE resident_requests ( id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER NOT NULL, unit_id INTEGER NOT NULL, resident_user_id INTEGER, request_type TEXT NOT NULL CHECK(request_type IN ('maintenance', 'complaint', 'suggestion', 'question', 'security', 'noise', 'parking', 'other')), category TEXT, subject TEXT NOT NULL, description TEXT NOT NULL, priority TEXT DEFAULT 'normal' CHECK(priority IN ('low', 'normal', 'high', 'urgent')), status TEXT DEFAULT 'open' CHECK(status IN ('open', 'in_progress', 'resolved', 'closed', 'rejected')), assigned_to INTEGER, response TEXT, resolved_at TEXT, resolved_by INTEGER, satisfaction_rating INTEGER, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE, FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE, FOREIGN KEY(resident_user_id) REFERENCES resident_users(id) ON DELETE SET NULL, FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL, FOREIGN KEY(resolved_by) REFERENCES users(id) ON DELETE SET NULL );

-- TABLE: resident_users
CREATE TABLE resident_users ( id INTEGER PRIMARY KEY AUTOINCREMENT, unit_id INTEGER NOT NULL, name TEXT NOT NULL, email TEXT UNIQUE NOT NULL, phone TEXT, password_hash TEXT NOT NULL, is_owner INTEGER DEFAULT 1, is_active INTEGER DEFAULT 1, email_verified INTEGER DEFAULT 0, phone_verified INTEGER DEFAULT 0, verification_token TEXT, email_verified_at TEXT, phone_verified_at TEXT, last_login_at TEXT, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE );

-- TABLE: schema_migrations
CREATE TABLE schema_migrations ( id INTEGER PRIMARY KEY AUTOINCREMENT, migration TEXT NOT NULL UNIQUE, executed_at TEXT NOT NULL );

-- TABLE: services
CREATE TABLE services ( id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, company_id INTEGER NOT NULL DEFAULT 1, duration_min INTEGER, default_fee REAL, is_active INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE );
-- INDEX: idx_services_company_id
CREATE INDEX idx_services_company_id ON services(company_id);

-- TABLE: slow_queries
CREATE TABLE slow_queries ( id INTEGER PRIMARY KEY AUTOINCREMENT, occurred_at TEXT NOT NULL DEFAULT (datetime('now')), duration_ms REAL NOT NULL, query TEXT NOT NULL, params TEXT, rows INTEGER, path TEXT, method TEXT, ip TEXT );

-- TABLE: sms_queue
CREATE TABLE sms_queue ( id INTEGER PRIMARY KEY AUTOINCREMENT, to_phone TEXT NOT NULL, message TEXT NOT NULL, data TEXT, status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'sent', 'failed')), attempts INTEGER DEFAULT 0, max_attempts INTEGER DEFAULT 3, scheduled_at TEXT DEFAULT (datetime('now')), last_attempt_at TEXT, sent_at TEXT, error_message TEXT, created_at TEXT DEFAULT (datetime('now')) );

-- TABLE: staff
CREATE TABLE staff ( id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, surname TEXT NOT NULL, phone TEXT, email TEXT, tc_number TEXT UNIQUE, birth_date TEXT, address TEXT, position TEXT, hire_date TEXT NOT NULL, salary REAL DEFAULT 0.00, hourly_rate REAL DEFAULT 0.00, photo TEXT, notes TEXT, status TEXT NOT NULL CHECK(status IN ('active', 'inactive', 'terminated')) DEFAULT 'active', created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')) );

-- TABLE: staff_attendance
CREATE TABLE staff_attendance ( id INTEGER PRIMARY KEY AUTOINCREMENT, staff_id INTEGER NOT NULL, date TEXT NOT NULL, check_in TEXT, check_out TEXT, break_start TEXT, break_end TEXT, total_hours REAL DEFAULT 0.00, overtime_hours REAL DEFAULT 0.00, status TEXT NOT NULL CHECK(status IN ('present', 'absent', 'late', 'half_day', 'sick_leave', 'annual_leave')) DEFAULT 'present', notes TEXT, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE, UNIQUE (staff_id, date) );

-- TABLE: staff_balances
CREATE TABLE staff_balances ( id INTEGER PRIMARY KEY AUTOINCREMENT, staff_id INTEGER NOT NULL, balance_type TEXT NOT NULL CHECK(balance_type IN ('receivable', 'payable')), amount REAL NOT NULL, description TEXT, due_date TEXT, status TEXT NOT NULL CHECK(status IN ('pending', 'paid', 'cancelled')) DEFAULT 'pending', created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE );

-- TABLE: staff_job_assignments
CREATE TABLE staff_job_assignments ( id INTEGER PRIMARY KEY AUTOINCREMENT, staff_id INTEGER NOT NULL, job_id INTEGER NOT NULL, assigned_date TEXT NOT NULL, start_time TEXT, end_time TEXT, hourly_rate REAL, total_amount REAL, status TEXT NOT NULL CHECK(status IN ('assigned', 'completed', 'cancelled')) DEFAULT 'assigned', notes TEXT, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE, FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE );

-- TABLE: staff_payments
CREATE TABLE staff_payments ( id INTEGER PRIMARY KEY AUTOINCREMENT, staff_id INTEGER NOT NULL, payment_date TEXT NOT NULL, amount REAL NOT NULL, payment_type TEXT NOT NULL CHECK(payment_type IN ('salary', 'bonus', 'advance', 'deduction', 'overtime')) DEFAULT 'salary', description TEXT, reference_number TEXT, status TEXT NOT NULL CHECK(status IN ('pending', 'paid', 'cancelled')) DEFAULT 'pending', created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE );

-- TABLE: survey_questions
CREATE TABLE survey_questions ( id INTEGER PRIMARY KEY AUTOINCREMENT, survey_id INTEGER NOT NULL, question_text TEXT NOT NULL, question_type TEXT NOT NULL CHECK(question_type IN ('single', 'multiple', 'text', 'rating')), options TEXT, is_required INTEGER DEFAULT 1, display_order INTEGER DEFAULT 0, FOREIGN KEY(survey_id) REFERENCES building_surveys(id) ON DELETE CASCADE );

-- TABLE: survey_responses
CREATE TABLE survey_responses ( id INTEGER PRIMARY KEY AUTOINCREMENT, survey_id INTEGER NOT NULL, question_id INTEGER NOT NULL, unit_id INTEGER, respondent_name TEXT, response_data TEXT NOT NULL, created_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(survey_id) REFERENCES building_surveys(id) ON DELETE CASCADE, FOREIGN KEY(question_id) REFERENCES survey_questions(id) ON DELETE CASCADE, FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE SET NULL );

-- TABLE: units
CREATE TABLE units ( id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER NOT NULL, unit_type TEXT NOT NULL CHECK(unit_type IN ('daire', 'dubleks', 'ofis', 'dukkÃ¡n', 'depo')), floor_number INTEGER, unit_number TEXT NOT NULL, gross_area REAL, net_area REAL, room_count TEXT, owner_type TEXT NOT NULL DEFAULT 'owner' CHECK(owner_type IN ('owner', 'tenant', 'empty', 'company')), owner_name TEXT NOT NULL, owner_phone TEXT, owner_email TEXT, owner_id_number TEXT, owner_address TEXT, tenant_name TEXT, tenant_phone TEXT, tenant_email TEXT, tenant_contract_start TEXT, tenant_contract_end TEXT, monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0, debt_balance DECIMAL(10,2) DEFAULT 0, parking_count INTEGER DEFAULT 0, storage_count INTEGER DEFAULT 0, status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'sold')), notes TEXT, created_at TEXT DEFAULT (datetime('now')), updated_at TEXT DEFAULT (datetime('now')), FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE );

-- TABLE: users
CREATE TABLE users ( id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL, role TEXT NOT NULL CHECK(role IN ('ADMIN','OPERATOR','SITE_MANAGER','FINANCE','SUPPORT','SUPERADMIN')), is_active INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT NOT NULL DEFAULT (datetime('now')), two_factor_secret TEXT, two_factor_backup_codes TEXT, two_factor_enabled_at TEXT, two_factor_required INTEGER DEFAULT 0, email TEXT, company_id INTEGER DEFAULT 1, FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE SET NULL );

-- INDEX: idx_activity_log_action
CREATE INDEX idx_activity_log_action ON activity_log(action, created_at);

-- INDEX: idx_activity_log_action_created
CREATE INDEX idx_activity_log_action_created ON activity_log(action, created_at);

-- INDEX: idx_activity_log_actor_created
CREATE INDEX idx_activity_log_actor_created ON activity_log(actor_id, created_at);

-- INDEX: idx_activity_log_actor_id
CREATE INDEX idx_activity_log_actor_id ON activity_log(actor_id);

-- INDEX: idx_activity_log_created_at
CREATE INDEX idx_activity_log_created_at ON activity_log(created_at);

-- INDEX: idx_activity_log_created_at_desc
CREATE INDEX idx_activity_log_created_at_desc ON activity_log(created_at DESC);

-- INDEX: idx_activity_log_entity
CREATE INDEX idx_activity_log_entity ON activity_log(entity);

-- INDEX: idx_activity_log_entity_created
CREATE INDEX idx_activity_log_entity_created ON activity_log(entity, created_at);

-- INDEX: idx_addresses_city
CREATE INDEX idx_addresses_city ON addresses(city);

-- INDEX: idx_addresses_customer
CREATE INDEX idx_addresses_customer ON addresses(customer_id);

-- INDEX: idx_addresses_customer_id
CREATE INDEX idx_addresses_customer_id ON addresses(customer_id);
-- INDEX: idx_addresses_company_id
CREATE INDEX idx_addresses_company_id ON addresses(company_id);

-- INDEX: idx_announcements_building_date
CREATE INDEX idx_announcements_building_date ON building_announcements(building_id, publish_date);

-- INDEX: idx_announcements_building_id
CREATE INDEX idx_announcements_building_id ON building_announcements(building_id);

-- INDEX: idx_announcements_date
CREATE INDEX idx_announcements_date ON building_announcements(publish_date);

-- INDEX: idx_announcements_priority
CREATE INDEX idx_announcements_priority ON building_announcements(priority);

-- INDEX: idx_announcements_publish_date
CREATE INDEX idx_announcements_publish_date ON building_announcements(publish_date, building_id);

-- INDEX: idx_announcements_type
CREATE INDEX idx_announcements_type ON building_announcements(announcement_type);

-- INDEX: idx_appointments_assigned_to
CREATE INDEX idx_appointments_assigned_to ON appointments(assigned_to);

-- INDEX: idx_appointments_customer_date
CREATE INDEX idx_appointments_customer_date ON appointments(customer_id, appointment_date);

-- INDEX: idx_appointments_customer_id
CREATE INDEX idx_appointments_customer_id ON appointments(customer_id);

-- INDEX: idx_appointments_date
CREATE INDEX idx_appointments_date ON appointments(appointment_date);

-- INDEX: idx_appointments_date_status
CREATE INDEX idx_appointments_date_status ON appointments(appointment_date, status);

-- INDEX: idx_appointments_priority
CREATE INDEX idx_appointments_priority ON appointments(priority);

-- INDEX: idx_appointments_status
CREATE INDEX idx_appointments_status ON appointments(status);

-- INDEX: idx_assignments_job
CREATE INDEX idx_assignments_job ON staff_job_assignments(job_id);

-- INDEX: idx_assignments_staff
CREATE INDEX idx_assignments_staff ON staff_job_assignments(staff_id);

-- INDEX: idx_attendance_date
CREATE INDEX idx_attendance_date ON staff_attendance(date);

-- INDEX: idx_attendance_staff_date
CREATE INDEX idx_attendance_staff_date ON staff_attendance(staff_id, date);

-- INDEX: idx_balances_staff
CREATE INDEX idx_balances_staff ON staff_balances(staff_id);

-- INDEX: idx_building_expenses_building_date
CREATE INDEX idx_building_expenses_building_date ON building_expenses(building_id, expense_date);

-- INDEX: idx_building_expenses_building_id
CREATE INDEX idx_building_expenses_building_id ON building_expenses(building_id);

-- INDEX: idx_building_expenses_building_status
CREATE INDEX idx_building_expenses_building_status ON building_expenses(building_id, approval_status);

-- INDEX: idx_building_expenses_category
CREATE INDEX idx_building_expenses_category ON building_expenses(category);

-- INDEX: idx_building_expenses_created_by
CREATE INDEX idx_building_expenses_created_by ON building_expenses(created_by);

-- INDEX: idx_building_expenses_date
CREATE INDEX idx_building_expenses_date ON building_expenses(expense_date);

-- INDEX: idx_building_expenses_status
CREATE INDEX idx_building_expenses_status ON building_expenses(approval_status);

-- INDEX: idx_buildings_building_type
CREATE INDEX idx_buildings_building_type ON buildings(building_type);

-- INDEX: idx_buildings_city
CREATE INDEX idx_buildings_city ON buildings(city);

-- INDEX: idx_buildings_customer_id
CREATE INDEX idx_buildings_customer_id ON buildings(customer_id);

-- INDEX: idx_buildings_status
CREATE INDEX idx_buildings_status ON buildings(status);

-- INDEX: idx_comment_attachments_comment
CREATE INDEX idx_comment_attachments_comment ON comment_attachments(comment_id);

-- INDEX: idx_comment_attachments_file
CREATE INDEX idx_comment_attachments_file ON comment_attachments(file_id);

-- INDEX: idx_comment_mentions_comment
CREATE INDEX idx_comment_mentions_comment ON comment_mentions(comment_id);

-- INDEX: idx_comment_mentions_user
CREATE INDEX idx_comment_mentions_user ON comment_mentions(mentioned_user_id);

-- INDEX: idx_comment_reactions_comment
CREATE INDEX idx_comment_reactions_comment ON comment_reactions(comment_id);

-- INDEX: idx_comment_reactions_type
CREATE INDEX idx_comment_reactions_type ON comment_reactions(reaction_type);

-- INDEX: idx_comment_reactions_user
CREATE INDEX idx_comment_reactions_user ON comment_reactions(user_id);

-- INDEX: idx_comments_created_at
CREATE INDEX idx_comments_created_at ON comments(created_at);

-- INDEX: idx_comments_entity
CREATE INDEX idx_comments_entity ON comments(entity_type, entity_id);

-- INDEX: idx_comments_parent
CREATE INDEX idx_comments_parent ON comments(parent_id);

-- INDEX: idx_comments_status
CREATE INDEX idx_comments_status ON comments(status);

-- INDEX: idx_comments_user
CREATE INDEX idx_comments_user ON comments(user_id);

-- INDEX: idx_contract_attachments_contract_id
CREATE INDEX idx_contract_attachments_contract_id ON contract_attachments(contract_id);

-- INDEX: idx_contract_attachments_uploaded_by
CREATE INDEX idx_contract_attachments_uploaded_by ON contract_attachments(uploaded_by);

-- INDEX: idx_contract_payments_contract_id
CREATE INDEX idx_contract_payments_contract_id ON contract_payments(contract_id);

-- INDEX: idx_contract_payments_due_date
CREATE INDEX idx_contract_payments_due_date ON contract_payments(due_date);

-- INDEX: idx_contract_payments_payment_date
CREATE INDEX idx_contract_payments_payment_date ON contract_payments(payment_date);

-- INDEX: idx_contract_payments_status
CREATE INDEX idx_contract_payments_status ON contract_payments(status);

-- INDEX: idx_contracts_created_by
CREATE INDEX idx_contracts_created_by ON contracts(created_by);

-- INDEX: idx_contracts_customer
CREATE INDEX idx_contracts_customer ON contracts(customer_id, status);

-- INDEX: idx_contracts_customer_id
CREATE INDEX idx_contracts_customer_id ON contracts(customer_id);

-- INDEX: idx_contracts_end_date
CREATE INDEX idx_contracts_end_date ON contracts(end_date);

-- INDEX: idx_contracts_expiring
CREATE INDEX idx_contracts_expiring ON contracts(end_date, status) WHERE end_date IS NOT NULL AND status = 'ACTIVE';

-- INDEX: idx_contracts_number
CREATE INDEX idx_contracts_number ON contracts(contract_number);

-- INDEX: idx_contracts_start_date
CREATE INDEX idx_contracts_start_date ON contracts(start_date);

-- INDEX: idx_contracts_status
CREATE INDEX idx_contracts_status ON contracts(status);

-- INDEX: idx_contracts_status_dates
CREATE INDEX idx_contracts_status_dates ON contracts(status, start_date, end_date);

-- INDEX: idx_contracts_type
CREATE INDEX idx_contracts_type ON contracts(contract_type);

-- INDEX: idx_customers_email
CREATE INDEX idx_customers_email ON customers(email);

-- INDEX: idx_customers_name
CREATE INDEX idx_customers_name ON customers(name);

-- INDEX: idx_customers_phone
CREATE INDEX idx_customers_phone ON customers(phone);
-- INDEX: idx_customers_company_id
CREATE INDEX idx_customers_company_id ON customers(company_id);

-- INDEX: idx_documents_building_id
CREATE INDEX idx_documents_building_id ON building_documents(building_id);

-- INDEX: idx_documents_type
CREATE INDEX idx_documents_type ON building_documents(document_type);

-- INDEX: idx_documents_unit_id
CREATE INDEX idx_documents_unit_id ON building_documents(unit_id);

-- INDEX: idx_documents_uploaded_by
CREATE INDEX idx_documents_uploaded_by ON building_documents(uploaded_by);

-- INDEX: idx_email_logs_customer_id
CREATE INDEX idx_email_logs_customer_id ON email_logs(customer_id);

-- INDEX: idx_email_logs_job_id
CREATE INDEX idx_email_logs_job_id ON email_logs(job_id);

-- INDEX: idx_email_logs_sent_at
CREATE INDEX idx_email_logs_sent_at ON email_logs(sent_at);

-- INDEX: idx_email_logs_type
CREATE INDEX idx_email_logs_type ON email_logs(type);

-- INDEX: idx_email_queue_created
CREATE INDEX idx_email_queue_created ON email_queue(created_at);

-- INDEX: idx_email_queue_scheduled
CREATE INDEX idx_email_queue_scheduled ON email_queue(scheduled_at);

-- INDEX: idx_email_queue_status
CREATE INDEX idx_email_queue_status ON email_queue(status);

-- INDEX: idx_facilities_active
CREATE INDEX idx_facilities_active ON building_facilities(is_active);

-- INDEX: idx_facilities_building_id
CREATE INDEX idx_facilities_building_id ON building_facilities(building_id);

-- INDEX: idx_facilities_type
CREATE INDEX idx_facilities_type ON building_facilities(facility_type);

-- INDEX: idx_fee_definitions_building_id
CREATE INDEX idx_fee_definitions_building_id ON management_fee_definitions(building_id);

-- INDEX: idx_file_access_created_at
CREATE INDEX idx_file_access_created_at ON file_access_logs(created_at);

-- INDEX: idx_file_access_file_id
CREATE INDEX idx_file_access_file_id ON file_access_logs(file_id);

-- INDEX: idx_file_access_user_id
CREATE INDEX idx_file_access_user_id ON file_access_logs(user_id);

-- INDEX: idx_file_uploads_category
CREATE INDEX idx_file_uploads_category ON file_uploads(category);

-- INDEX: idx_file_uploads_created_at
CREATE INDEX idx_file_uploads_created_at ON file_uploads(created_at);

-- INDEX: idx_file_uploads_entity
CREATE INDEX idx_file_uploads_entity ON file_uploads(entity_type, entity_id);

-- INDEX: idx_file_uploads_uploaded_by
CREATE INDEX idx_file_uploads_uploaded_by ON file_uploads(uploaded_by);

-- INDEX: idx_job_payments_finance_id
CREATE INDEX idx_job_payments_finance_id ON job_payments(finance_id);

-- INDEX: idx_job_payments_job_id
CREATE INDEX idx_job_payments_job_id ON job_payments(job_id);

-- INDEX: idx_jobs_created_at
CREATE INDEX idx_jobs_created_at ON jobs(created_at);

-- INDEX: idx_jobs_customer_id
CREATE INDEX idx_jobs_customer_id ON jobs(customer_id);

-- INDEX: idx_jobs_customer_status
CREATE INDEX idx_jobs_customer_status ON jobs(customer_id, status);

-- INDEX: idx_jobs_occurrence_id
CREATE INDEX idx_jobs_occurrence_id ON jobs(occurrence_id);

-- INDEX: idx_jobs_payment_status
CREATE INDEX idx_jobs_payment_status ON jobs(payment_status);

-- INDEX: idx_jobs_recurring_id
CREATE INDEX idx_jobs_recurring_id ON jobs(recurring_job_id) WHERE recurring_job_id IS NOT NULL;

-- INDEX: idx_jobs_recurring_job_id
CREATE INDEX idx_jobs_recurring_job_id ON jobs(recurring_job_id);

-- INDEX: idx_jobs_reminder_sent
CREATE INDEX idx_jobs_reminder_sent ON jobs(reminder_sent);

-- INDEX: idx_jobs_service_id
CREATE INDEX idx_jobs_service_id ON jobs(service_id);

-- INDEX: idx_jobs_start_at
CREATE INDEX idx_jobs_start_at ON jobs(start_at);

-- INDEX: idx_jobs_status
CREATE INDEX idx_jobs_status ON jobs(status);

-- INDEX: idx_jobs_status_payment
CREATE INDEX idx_jobs_status_payment ON jobs(status, payment_status);

-- INDEX: idx_jobs_status_start_at
CREATE INDEX idx_jobs_status_start_at ON jobs(status, start_at);
-- INDEX: idx_jobs_company_id
CREATE INDEX idx_jobs_company_id ON jobs(company_id);

-- INDEX: idx_management_fees_building_due
CREATE INDEX idx_management_fees_building_due ON management_fees(building_id, due_date, status);

-- INDEX: idx_management_fees_building_id
CREATE INDEX idx_management_fees_building_id ON management_fees(building_id);

-- INDEX: idx_management_fees_building_period
CREATE INDEX idx_management_fees_building_period ON management_fees(building_id, period);

-- INDEX: idx_management_fees_building_status
CREATE INDEX idx_management_fees_building_status ON management_fees(building_id, status);

-- INDEX: idx_management_fees_due_date
CREATE INDEX idx_management_fees_due_date ON management_fees(due_date);

-- INDEX: idx_management_fees_period
CREATE INDEX idx_management_fees_period ON management_fees(period);

-- INDEX: idx_management_fees_status
CREATE INDEX idx_management_fees_status ON management_fees(status);

-- INDEX: idx_management_fees_unit_id
CREATE INDEX idx_management_fees_unit_id ON management_fees(unit_id);

-- INDEX: idx_management_fees_unit_period
CREATE INDEX idx_management_fees_unit_period ON management_fees(unit_id, period);

-- INDEX: idx_meeting_attendees_meeting_id
CREATE INDEX idx_meeting_attendees_meeting_id ON meeting_attendees(meeting_id);

-- INDEX: idx_meeting_attendees_unit_id
CREATE INDEX idx_meeting_attendees_unit_id ON meeting_attendees(unit_id);

-- INDEX: idx_meetings_building_date
CREATE INDEX idx_meetings_building_date ON building_meetings(building_id, meeting_date);

-- INDEX: idx_meetings_building_id
CREATE INDEX idx_meetings_building_id ON building_meetings(building_id);

-- INDEX: idx_meetings_date
CREATE INDEX idx_meetings_date ON building_meetings(meeting_date);

-- INDEX: idx_meetings_meeting_date
CREATE INDEX idx_meetings_meeting_date ON building_meetings(meeting_date);

-- INDEX: idx_meetings_status
CREATE INDEX idx_meetings_status ON building_meetings(status);

-- INDEX: idx_meetings_status_date
CREATE INDEX idx_meetings_status_date ON building_meetings(status, meeting_date);

-- INDEX: idx_money_entries_created_by
CREATE INDEX idx_money_entries_created_by ON money_entries(created_by);

-- INDEX: idx_money_entries_date
CREATE INDEX idx_money_entries_date ON money_entries(date);

-- INDEX: idx_money_entries_job_id
CREATE INDEX idx_money_entries_job_id ON money_entries(job_id);

-- INDEX: idx_money_entries_kind
CREATE INDEX idx_money_entries_kind ON money_entries(kind);

-- INDEX: idx_money_entries_kind_date
CREATE INDEX idx_money_entries_kind_date ON money_entries(kind, date);

-- INDEX: idx_money_entries_recurring_job_id
CREATE INDEX idx_money_entries_recurring_job_id ON money_entries(recurring_job_id);
-- INDEX: idx_money_entries_company_id
CREATE INDEX idx_money_entries_company_id ON money_entries(company_id);

-- INDEX: idx_notification_logs_resident
CREATE INDEX idx_notification_logs_resident ON notification_logs(resident_id);

-- INDEX: idx_notification_logs_type
CREATE INDEX idx_notification_logs_type ON notification_logs(type);

-- INDEX: idx_notification_preferences_resident
CREATE INDEX idx_notification_preferences_resident ON notification_preferences(resident_id);

-- INDEX: idx_occurrences_date
CREATE INDEX idx_occurrences_date ON recurring_job_occurrences(date);

-- INDEX: idx_occurrences_recurring
CREATE INDEX idx_occurrences_recurring ON recurring_job_occurrences(recurring_job_id);

-- INDEX: idx_online_payments_fee_id
CREATE INDEX idx_online_payments_fee_id ON online_payments(management_fee_id);

-- INDEX: idx_online_payments_status
CREATE INDEX idx_online_payments_status ON online_payments(status);

-- INDEX: idx_online_payments_transaction_id
CREATE INDEX idx_online_payments_transaction_id ON online_payments(transaction_id);

-- INDEX: idx_password_reset_expires
CREATE INDEX idx_password_reset_expires ON password_reset_tokens(expires_at);

-- INDEX: idx_password_reset_token
CREATE INDEX idx_password_reset_token ON password_reset_tokens(token);

-- INDEX: idx_password_reset_user_id
CREATE INDEX idx_password_reset_user_id ON password_reset_tokens(user_id);

-- INDEX: idx_payments_date
CREATE INDEX idx_payments_date ON staff_payments(payment_date);

-- INDEX: idx_payments_staff
CREATE INDEX idx_payments_staff ON staff_payments(staff_id);

-- INDEX: idx_rate_limits_blocked_until
CREATE INDEX idx_rate_limits_blocked_until ON rate_limits(blocked_until);

-- INDEX: idx_recurring_jobs_customer
CREATE INDEX idx_recurring_jobs_customer ON recurring_jobs(customer_id);

-- INDEX: idx_recurring_jobs_customer_id
CREATE INDEX idx_recurring_jobs_customer_id ON recurring_jobs(customer_id);

-- INDEX: idx_recurring_jobs_customer_status
CREATE INDEX idx_recurring_jobs_customer_status ON recurring_jobs(customer_id, status);

-- INDEX: idx_recurring_jobs_start_date
CREATE INDEX idx_recurring_jobs_start_date ON recurring_jobs(start_date);

-- INDEX: idx_recurring_jobs_status
CREATE INDEX idx_recurring_jobs_status ON recurring_jobs(status);
-- INDEX: idx_recurring_jobs_company_id
CREATE INDEX idx_recurring_jobs_company_id ON recurring_jobs(company_id);

-- INDEX: idx_recurring_occurrences_date
CREATE INDEX idx_recurring_occurrences_date ON recurring_job_occurrences(date);

-- INDEX: idx_recurring_occurrences_date_status
CREATE INDEX idx_recurring_occurrences_date_status ON recurring_job_occurrences(date, status);

-- INDEX: idx_recurring_occurrences_job_id
CREATE INDEX idx_recurring_occurrences_job_id ON recurring_job_occurrences(job_id);

-- INDEX: idx_recurring_occurrences_rj_id
CREATE INDEX idx_recurring_occurrences_rj_id ON recurring_job_occurrences(recurring_job_id);

-- INDEX: idx_recurring_occurrences_status
CREATE INDEX idx_recurring_occurrences_status ON recurring_job_occurrences(status);

-- INDEX: idx_remember_tokens_expires
CREATE INDEX idx_remember_tokens_expires ON remember_tokens(expires_at);

-- INDEX: idx_remember_tokens_token
CREATE INDEX idx_remember_tokens_token ON remember_tokens(token);

-- INDEX: idx_remember_tokens_token_hash
CREATE INDEX idx_remember_tokens_token_hash ON remember_tokens(token_hash);

-- INDEX: idx_remember_tokens_user
CREATE INDEX idx_remember_tokens_user ON remember_tokens(user_id);

-- INDEX: idx_remember_tokens_user_id
CREATE INDEX idx_remember_tokens_user_id ON remember_tokens(user_id);

-- INDEX: idx_reservations_building_id
CREATE INDEX idx_reservations_building_id ON building_reservations(building_id);

-- INDEX: idx_reservations_dates
CREATE INDEX idx_reservations_dates ON building_reservations(start_date, end_date);

-- INDEX: idx_reservations_facility_id
CREATE INDEX idx_reservations_facility_id ON building_reservations(facility_id);

-- INDEX: idx_reservations_status
CREATE INDEX idx_reservations_status ON building_reservations(status);

-- INDEX: idx_reservations_unit_id
CREATE INDEX idx_reservations_unit_id ON building_reservations(unit_id);

-- INDEX: idx_resident_requests_assigned_to
CREATE INDEX idx_resident_requests_assigned_to ON resident_requests(assigned_to);

-- INDEX: idx_resident_requests_building_id
CREATE INDEX idx_resident_requests_building_id ON resident_requests(building_id);

-- INDEX: idx_resident_requests_building_status
CREATE INDEX idx_resident_requests_building_status ON resident_requests(building_id, status);

-- INDEX: idx_resident_requests_created
CREATE INDEX idx_resident_requests_created ON resident_requests(created_at, building_id);

-- INDEX: idx_resident_requests_priority
CREATE INDEX idx_resident_requests_priority ON resident_requests(priority);

-- INDEX: idx_resident_requests_status
CREATE INDEX idx_resident_requests_status ON resident_requests(status);

-- INDEX: idx_resident_requests_type
CREATE INDEX idx_resident_requests_type ON resident_requests(request_type);

-- INDEX: idx_resident_requests_unit
CREATE INDEX idx_resident_requests_unit ON resident_requests(unit_id, status);

-- INDEX: idx_resident_requests_unit_id
CREATE INDEX idx_resident_requests_unit_id ON resident_requests(unit_id);

-- INDEX: idx_resident_users_active
CREATE INDEX idx_resident_users_active ON resident_users(is_active);

-- INDEX: idx_resident_users_email
CREATE INDEX idx_resident_users_email ON resident_users(email);

-- INDEX: idx_resident_users_unit_active
CREATE INDEX idx_resident_users_unit_active ON resident_users(unit_id, is_active);

-- INDEX: idx_resident_users_unit_id
CREATE INDEX idx_resident_users_unit_id ON resident_users(unit_id);

-- INDEX: idx_rjo_date_status
CREATE INDEX idx_rjo_date_status ON recurring_job_occurrences(date, status);

-- INDEX: idx_rjo_recurring_on_date_plain
CREATE INDEX idx_rjo_recurring_on_date_plain ON recurring_job_occurrences(recurring_job_id, date);

-- INDEX: idx_rjo_recurring_on_start
CREATE INDEX idx_rjo_recurring_on_start ON recurring_job_occurrences(recurring_job_id, start_at);

-- INDEX: idx_rjo_start_at
CREATE INDEX idx_rjo_start_at ON recurring_job_occurrences(start_at);

-- INDEX: idx_slow_queries_duration
CREATE INDEX idx_slow_queries_duration ON slow_queries(duration_ms);

-- INDEX: idx_slow_queries_occurred
CREATE INDEX idx_slow_queries_occurred ON slow_queries(occurred_at);

-- INDEX: idx_slow_queries_time
CREATE INDEX idx_slow_queries_time ON slow_queries(occurred_at);

-- INDEX: idx_sms_queue_created
CREATE INDEX idx_sms_queue_created ON sms_queue(created_at);

-- INDEX: idx_sms_queue_scheduled
CREATE INDEX idx_sms_queue_scheduled ON sms_queue(scheduled_at);

-- INDEX: idx_sms_queue_status
CREATE INDEX idx_sms_queue_status ON sms_queue(status);

-- INDEX: idx_staff_hire_date
CREATE INDEX idx_staff_hire_date ON staff(hire_date);

-- INDEX: idx_staff_status
CREATE INDEX idx_staff_status ON staff(status);

-- INDEX: idx_survey_questions_survey_id
CREATE INDEX idx_survey_questions_survey_id ON survey_questions(survey_id);

-- INDEX: idx_survey_responses_survey_id
CREATE INDEX idx_survey_responses_survey_id ON survey_responses(survey_id);

-- INDEX: idx_survey_responses_unit_id
CREATE INDEX idx_survey_responses_unit_id ON survey_responses(unit_id);

-- INDEX: idx_surveys_building_id
CREATE INDEX idx_surveys_building_id ON building_surveys(building_id);

-- INDEX: idx_surveys_building_status
CREATE INDEX idx_surveys_building_status ON building_surveys(building_id, status);

-- INDEX: idx_surveys_dates
CREATE INDEX idx_surveys_dates ON building_surveys(start_date, end_date);

-- INDEX: idx_surveys_status
CREATE INDEX idx_surveys_status ON building_surveys(status);

-- INDEX: idx_topics_display_order
CREATE INDEX idx_topics_display_order ON meeting_topics(meeting_id, display_order);

-- INDEX: idx_topics_meeting_id
CREATE INDEX idx_topics_meeting_id ON meeting_topics(meeting_id);

-- INDEX: idx_topics_voting_enabled
CREATE INDEX idx_topics_voting_enabled ON meeting_topics(voting_enabled);

-- INDEX: idx_units_building_id
CREATE INDEX idx_units_building_id ON units(building_id);

-- INDEX: idx_units_building_status
CREATE INDEX idx_units_building_status ON units(building_id, status);

-- INDEX: idx_units_owner_name
CREATE INDEX idx_units_owner_name ON units(owner_name);

-- INDEX: idx_units_owner_type
CREATE INDEX idx_units_owner_type ON units(owner_type);

-- INDEX: idx_units_status
CREATE INDEX idx_units_status ON units(status);

-- INDEX: idx_units_unit_number
CREATE INDEX idx_units_unit_number ON units(unit_number);

-- INDEX: idx_upload_progress_session
CREATE INDEX idx_upload_progress_session ON file_upload_progress(session_id);

-- INDEX: idx_upload_progress_status
CREATE INDEX idx_upload_progress_status ON file_upload_progress(status);

-- INDEX: idx_users_role
CREATE INDEX idx_users_role ON users(role);

-- INDEX: idx_users_username
CREATE INDEX idx_users_username ON users(username);
-- INDEX: idx_users_company_id
CREATE INDEX idx_users_company_id ON users(company_id);

-- INDEX: idx_votes_attendee_id
CREATE INDEX idx_votes_attendee_id ON meeting_votes(attendee_id);

-- INDEX: idx_votes_meeting_id
CREATE INDEX idx_votes_meeting_id ON meeting_votes(meeting_id);

-- INDEX: idx_votes_topic_id
CREATE INDEX idx_votes_topic_id ON meeting_votes(topic_id);


-- Seed data
INSERT OR IGNORE INTO services (id, name, duration_min, default_fee, is_active, created_at)
VALUES
  (1, 'Ev TemizliÄŸi', 120, 150.00, 1, datetime('now')),
  (2, 'Ofis TemizliÄŸi', 90, 100.00, 1, datetime('now')),
  (3, 'Cam TemizliÄŸi', 60, 80.00, 1, datetime('now')),
  (4, 'HalÄ± YÄ±kama', 180, 200.00, 1, datetime('now')),
  (5, 'Balkon TemizliÄŸi', 45, 60.00, 1, datetime('now'));

INSERT OR IGNORE INTO users (id, username, password_hash, role, is_active, created_at, updated_at)
VALUES
  (1, 'candas', '$2y$10$PbfiGRaH0Ip8Bsab5FqT2eETCwx0fcU.YmFuQVJnx7ljtyAE7vrPq', 'ADMIN', 1, datetime('now'), datetime('now')),
  (2, 'necla', '$2y$10$uKkkQlz44zcW1UqD3RPJTOaIKxz8gRPqBtkPS9iaSeRC0lcqspz7q', 'OPERATOR', 1, datetime('now'), datetime('now'));
















<?php
/**
 * Database Indexer
 * Ensures all necessary indexes exist for optimal performance
 */
class DatabaseIndexer
{
    /**
     * Create all missing indexes
     */
    public static function ensureIndexes(): void
    {
        // Prevent infinite recursion and ensure single execution
        static $isRunning = false;
        static $isInitialized = false;
        
        if ($isRunning || $isInitialized) {
            return;
        }
        $isRunning = true;
        
        try {
            $db = Database::getInstance();
            $pdo = $db->getPdo();
        } catch (Exception $e) {
            $isRunning = false;
            if (APP_DEBUG) {
                error_log("DatabaseIndexer: Cannot get database instance: " . $e->getMessage());
            }
            return;
        }
        
        // Check if indexes already exist (quick check)
        try {
            $existingIndexes = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name LIKE 'idx_%'")->fetchAll(PDO::FETCH_COLUMN);
            $existingIndexesSet = array_flip($existingIndexes);
        } catch (Exception $e) {
            $existingIndexesSet = [];
        }
        
        // Recurring jobs indexes (new)
        $indexes = [
            // Recurring jobs
            "CREATE INDEX IF NOT EXISTS idx_recurring_jobs_customer_id ON recurring_jobs(customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_recurring_jobs_status ON recurring_jobs(status)",
            "CREATE INDEX IF NOT EXISTS idx_recurring_jobs_start_date ON recurring_jobs(start_date)",
            "CREATE INDEX IF NOT EXISTS idx_recurring_jobs_customer_status ON recurring_jobs(customer_id, status)",
            
            // Recurring job occurrences
            "CREATE INDEX IF NOT EXISTS idx_recurring_occurrences_rj_id ON recurring_job_occurrences(recurring_job_id)",
            "CREATE INDEX IF NOT EXISTS idx_recurring_occurrences_date ON recurring_job_occurrences(date)",
            "CREATE INDEX IF NOT EXISTS idx_recurring_occurrences_status ON recurring_job_occurrences(status)",
            "CREATE INDEX IF NOT EXISTS idx_recurring_occurrences_date_status ON recurring_job_occurrences(date, status)",
            "CREATE INDEX IF NOT EXISTS idx_recurring_occurrences_job_id ON recurring_job_occurrences(job_id)",
            
            // Jobs additional indexes (OPTIMIZED: composite for common queries)
            "CREATE INDEX IF NOT EXISTS idx_jobs_recurring_job_id ON jobs(recurring_job_id)",
            "CREATE INDEX IF NOT EXISTS idx_jobs_payment_status ON jobs(payment_status)",
            "CREATE INDEX IF NOT EXISTS idx_jobs_created_at ON jobs(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_jobs_status_payment ON jobs(status, payment_status)",
            "CREATE INDEX IF NOT EXISTS idx_jobs_status_start_at ON jobs(status, start_at)",
            "CREATE INDEX IF NOT EXISTS idx_jobs_customer_status ON jobs(customer_id, status)",
            
            // Management fees indexes
            "CREATE INDEX IF NOT EXISTS idx_management_fees_building_status ON management_fees(building_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_management_fees_unit_period ON management_fees(unit_id, period_month, period_year)",
            "CREATE INDEX IF NOT EXISTS idx_management_fees_due_date ON management_fees(due_date) WHERE status != 'paid'",
            "CREATE INDEX IF NOT EXISTS idx_management_fees_building_due ON management_fees(building_id, due_date, status)",
            "CREATE INDEX IF NOT EXISTS idx_management_fees_status ON management_fees(status)",
            
            // Building expenses indexes
            "CREATE INDEX IF NOT EXISTS idx_building_expenses_building_status ON building_expenses(building_id, approval_status)",
            "CREATE INDEX IF NOT EXISTS idx_building_expenses_date ON building_expenses(expense_date, building_id)",
            "CREATE INDEX IF NOT EXISTS idx_building_expenses_category ON building_expenses(category, building_id)",
            
            // Building management indexes
            "CREATE INDEX IF NOT EXISTS idx_buildings_status ON buildings(status)",
            "CREATE INDEX IF NOT EXISTS idx_units_building_status ON units(building_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_units_status ON units(status)",
            "CREATE INDEX IF NOT EXISTS idx_addresses_customer ON addresses(customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_meetings_building_date ON building_meetings(building_id, meeting_date)",
            "CREATE INDEX IF NOT EXISTS idx_meetings_status_date ON building_meetings(status, meeting_date)",
            "CREATE INDEX IF NOT EXISTS idx_meetings_meeting_date ON building_meetings(meeting_date)",
            "CREATE INDEX IF NOT EXISTS idx_announcements_building_pinned ON building_announcements(building_id, is_pinned)",
            "CREATE INDEX IF NOT EXISTS idx_announcements_publish_date ON building_announcements(publish_date, building_id)",
            "CREATE INDEX IF NOT EXISTS idx_surveys_building_status ON building_surveys(building_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_resident_requests_building_status ON resident_requests(building_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_resident_requests_unit ON resident_requests(unit_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_resident_requests_created ON resident_requests(created_at, building_id)",
            "CREATE INDEX IF NOT EXISTS idx_resident_users_unit_active ON resident_users(unit_id, is_active)",
            "CREATE INDEX IF NOT EXISTS idx_resident_users_email ON resident_users(email) WHERE email IS NOT NULL",
            
            // Contracts indexes
            "CREATE INDEX IF NOT EXISTS idx_contracts_status_dates ON contracts(status, start_date, end_date)",
            "CREATE INDEX IF NOT EXISTS idx_contracts_customer ON contracts(customer_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_contracts_expiring ON contracts(end_date, status) WHERE end_date IS NOT NULL AND status = 'ACTIVE'",
            
            // Appointments indexes
            "CREATE INDEX IF NOT EXISTS idx_appointments_date_status ON appointments(appointment_date, status)",
            "CREATE INDEX IF NOT EXISTS idx_appointments_customer_date ON appointments(customer_id, appointment_date)",
            
            // Payments indexes
            "CREATE INDEX IF NOT EXISTS idx_payments_customer_created ON payments(customer_id, created_at)",
            "CREATE INDEX IF NOT EXISTS idx_payments_status_created ON payments(status, created_at)",
            
            // Online payments indexes
            "CREATE INDEX IF NOT EXISTS idx_online_payments_status ON online_payments(payment_status, created_at)",
            "CREATE INDEX IF NOT EXISTS idx_online_payments_fee ON online_payments(management_fee_id, status)",
            
            // Calendar sync indexes
            "CREATE INDEX IF NOT EXISTS idx_calendar_external_events_sync ON calendar_external_events(user_id, last_sync_at)",
            "CREATE INDEX IF NOT EXISTS idx_calendar_external_events_job ON calendar_external_events(job_id) WHERE job_id IS NOT NULL",
            
            // Slow queries tracking
            "CREATE INDEX IF NOT EXISTS idx_slow_queries_occurred ON slow_queries(occurred_at)",
            "CREATE INDEX IF NOT EXISTS idx_slow_queries_duration ON slow_queries(duration_ms)",
            
            // Money entries additional
            "CREATE INDEX IF NOT EXISTS idx_money_entries_recurring_job_id ON money_entries(recurring_job_id)",
            
            // Customers email index (for unique lookups)
            "CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email)",
            
            // Addresses full text search
            "CREATE INDEX IF NOT EXISTS idx_addresses_city ON addresses(city)",
            
            // Activity log performance
            "CREATE INDEX IF NOT EXISTS idx_activity_log_created_at_desc ON activity_log(created_at DESC)",
            "CREATE INDEX IF NOT EXISTS idx_activity_log_actor_created ON activity_log(actor_id, created_at)",
            "CREATE INDEX IF NOT EXISTS idx_activity_log_entity ON activity_log(entity_type, entity_id)",
            "CREATE INDEX IF NOT EXISTS idx_activity_log_action ON activity_log(action, created_at)",
        ];
        
        // Execute index creation in transaction for atomicity
        try {
            $pdo->beginTransaction();
            
            $created = 0;
            foreach ($indexes as $index) {
                try {
                    // Extract index name from SQL for existence check
                    if (preg_match('/CREATE INDEX IF NOT EXISTS\s+(\w+)\s+ON/i', $index, $matches)) {
                        $indexName = $matches[1];
                        
                        // Skip if index already exists
                        if (isset($existingIndexesSet[$indexName])) {
                            continue;
                        }
                    }
                    
                    $pdo->exec($index);
                    $created++;
                } catch (Exception $e) {
                    if (APP_DEBUG) {
                        error_log("Index creation failed: " . $e->getMessage() . " | SQL: " . substr($index, 0, 100));
                    }
                    // Continue with other indexes
                }
            }
            
            $pdo->commit();
            
            if ($created > 0 && APP_DEBUG) {
                error_log("DatabaseIndexer: Created {$created} indexes");
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            if (APP_DEBUG) {
                error_log("DatabaseIndexer: Transaction failed: " . $e->getMessage());
            }
        }
        
        $isRunning = false;
        $isInitialized = true;
    }
    
    /**
     * Analyze query performance
     */
    public static function analyzeTables(): array
    {
        $db = Database::getInstance();
        
        $tables = ['jobs', 'customers', 'money_entries', 'recurring_jobs', 'recurring_job_occurrences'];
        $results = [];
        
        foreach ($tables as $table) {
            try {
                // ===== ERR-007 FIX: Validate table name =====
                $sanitizedTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                if ($sanitizedTable !== $table) {
                    continue; // Skip invalid table names
                }
                // ===== ERR-007 FIX: End =====
                $stats = $db->fetch("SELECT COUNT(*) as row_count FROM `{$sanitizedTable}`");
                $results[$table] = [
                    'row_count' => $stats['row_count'] ?? 0,
                    'indexes' => self::getTableIndexes($table)
                ];
            } catch (Exception $e) {
                $results[$table] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    /**
     * Get indexes for a table
     */
    private static function getTableIndexes(string $table): array
    {
        $db = Database::getInstance();
        
        try {
            $indexes = $db->fetchAll("
                SELECT name, sql 
                FROM sqlite_master 
                WHERE type = 'index' 
                AND tbl_name = ?
                AND sql IS NOT NULL
            ", [$table]);
            
            return array_column($indexes, 'name');
        } catch (Exception $e) {
            return [];
        }
    }
}


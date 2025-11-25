<?php
/**
 * Migration 015: Enforce FK Cascades (SQLite Workaround)
 * 
 * SQLite doesn't support ALTER TABLE for FK constraints.
 * This migration recreates tables with proper FK cascades:
 * - jobs: ON DELETE CASCADE/SET NULL
 * - money_entries: ON DELETE SET NULL
 * - activity_log: ON DELETE SET NULL
 * 
 * Self-Audit Fix: Real migration for HIGH-006 fix (FK cascade)
 * 
 * IMPORTANT: Run validate_orphaned_records.php first!
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';

class FKCascadeMigration
{
    private $db;
    private $backupCreated = false;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Run migration
     */
    public function up()
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║     MIGRATION 015: FK CASCADE ENFORCEMENT                 ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        // Backup database
        echo "Step 1: Creating backup...\n";
        $this->createBackup();
        echo "   ✅ Backup created\n\n";
        
        // Disable FK temporarily
        echo "Step 2: Disabling FK constraints temporarily...\n";
        $this->db->execute("PRAGMA foreign_keys = OFF");
        echo "   ✅ FK constraints disabled\n\n";
        
        try {
            // Begin transaction
            $this->db->beginTransaction();
            
            // Migrate jobs table
            echo "Step 3: Migrating jobs table...\n";
            $this->migrateJobsTable();
            echo "   ✅ Jobs table migrated\n\n";
            
            // Migrate money_entries table
            echo "Step 4: Migrating money_entries table...\n";
            $this->migrateMoneyEntriesTable();
            echo "   ✅ Money entries table migrated\n\n";
            
            // Migrate activity_log table
            echo "Step 5: Migrating activity_log table...\n";
            $this->migrateActivityLogTable();
            echo "   ✅ Activity log table migrated\n\n";
            
            // Commit transaction
            $this->db->commit();
            
            // Re-enable FK
            echo "Step 6: Re-enabling FK constraints...\n";
            $this->db->execute("PRAGMA foreign_keys = ON");
            echo "   ✅ FK constraints re-enabled\n\n";
            
            // Verify FK integrity
            echo "Step 7: Verifying FK integrity...\n";
            $this->verifyIntegrity();
            echo "   ✅ FK integrity verified\n\n";
            
            // Log migration
            $this->logMigration();
            
            echo "╔═══════════════════════════════════════════════════════════╗\n";
            echo "║              ✅ MIGRATION SUCCESSFUL ✅                    ║\n";
            echo "╚═══════════════════════════════════════════════════════════╝\n";
            echo "\n";
            
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollback();
            
            echo "\n";
            echo "╔═══════════════════════════════════════════════════════════╗\n";
            echo "║              ❌ MIGRATION FAILED ❌                        ║\n";
            echo "╚═══════════════════════════════════════════════════════════╝\n";
            echo "\n";
            echo "Error: {$e->getMessage()}\n";
            echo "\n";
            echo "Database has been rolled back.\n";
            echo "Restore from backup if needed:\n";
            echo "  cp db/backups/pre-fk-migration-*.sqlite db/app.sqlite\n";
            echo "\n";
            
            return false;
        }
    }
    
    /**
     * Create backup
     */
    private function createBackup()
    {
        $dbPath = __DIR__ . '/../app.sqlite';
        $backupPath = __DIR__ . '/../backups/pre-fk-migration-' . date('Ymd-His') . '.sqlite';
        
        if (file_exists($dbPath)) {
            copy($dbPath, $backupPath);
            $this->backupCreated = true;
            echo "   Backup: {$backupPath}\n";
        }
    }
    
    /**
     * Migrate jobs table with FK cascades
     */
    private function migrateJobsTable()
    {
        // Rename old table
        $this->db->execute("ALTER TABLE jobs RENAME TO jobs_old");
        
        // Create new table with proper FK constraints
        $this->db->execute("
            CREATE TABLE jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                service_id INTEGER,
                customer_id INTEGER NOT NULL,
                address_id INTEGER,
                start_at TEXT NOT NULL,
                end_at TEXT NOT NULL,
                status TEXT NOT NULL CHECK(status IN ('SCHEDULED','DONE','CANCELLED')) DEFAULT 'SCHEDULED',
                total_amount REAL NOT NULL DEFAULT 0,
                amount_paid REAL NOT NULL DEFAULT 0,
                payment_status TEXT NOT NULL CHECK(payment_status IN ('UNPAID','PARTIAL','PAID')) DEFAULT 'UNPAID',
                assigned_to INTEGER,
                note TEXT,
                income_id INTEGER,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY(service_id) REFERENCES services(id) ON DELETE SET NULL,
                FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                FOREIGN KEY(address_id) REFERENCES addresses(id) ON DELETE SET NULL
            )
        ");
        
        // Copy data from old table
        $this->db->execute("
            INSERT INTO jobs 
            SELECT * FROM jobs_old
        ");
        
        // Drop old table
        $this->db->execute("DROP TABLE jobs_old");
        
        // Recreate indexes
        $this->recreateJobsIndexes();
    }
    
    /**
     * Migrate money_entries table with FK cascades
     */
    private function migrateMoneyEntriesTable()
    {
        // Rename old table
        $this->db->execute("ALTER TABLE money_entries RENAME TO money_entries_old");
        
        // Create new table with proper FK constraints
        $this->db->execute("
            CREATE TABLE money_entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL CHECK(type IN ('INCOME','EXPENSE')),
                category TEXT NOT NULL,
                amount REAL NOT NULL,
                date TEXT NOT NULL,
                description TEXT,
                notes TEXT,
                job_id INTEGER,
                created_by INTEGER,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE SET NULL,
                FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        // Copy data from old table
        $this->db->execute("
            INSERT INTO money_entries 
            SELECT * FROM money_entries_old
        ");
        
        // Drop old table
        $this->db->execute("DROP TABLE money_entries_old");
        
        // Recreate indexes
        $this->recreateMoneyEntriesIndexes();
    }
    
    /**
     * Migrate activity_log table with FK cascades
     */
    private function migrateActivityLogTable()
    {
        // Rename old table
        $this->db->execute("ALTER TABLE activity_log RENAME TO activity_log_old");
        
        // Create new table with proper FK constraints
        $this->db->execute("
            CREATE TABLE activity_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_id INTEGER,
                action TEXT NOT NULL,
                entity_type TEXT,
                entity_id INTEGER,
                details TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY(actor_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        // Copy data from old table
        $this->db->execute("
            INSERT INTO activity_log 
            SELECT * FROM activity_log_old
        ");
        
        // Drop old table
        $this->db->execute("DROP TABLE activity_log_old");
        
        // Recreate indexes
        $this->recreateActivityLogIndexes();
    }
    
    /**
     * Recreate jobs indexes
     */
    private function recreateJobsIndexes()
    {
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_jobs_customer ON jobs(customer_id)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_jobs_service ON jobs(service_id)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_jobs_address ON jobs(address_id)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs(status)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_jobs_start_at ON jobs(start_at)");
    }
    
    /**
     * Recreate money_entries indexes
     */
    private function recreateMoneyEntriesIndexes()
    {
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_money_entries_type ON money_entries(type)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_money_entries_category ON money_entries(category)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_money_entries_date ON money_entries(date)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_money_entries_job ON money_entries(job_id)");
    }
    
    /**
     * Recreate activity_log indexes
     */
    private function recreateActivityLogIndexes()
    {
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_activity_log_actor ON activity_log(actor_id)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_activity_log_entity ON activity_log(entity_type, entity_id)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_activity_log_created ON activity_log(created_at)");
    }
    
    /**
     * Verify FK integrity
     */
    private function verifyIntegrity()
    {
        // Check FK violations
        $violations = $this->db->fetch("PRAGMA foreign_key_check");
        
        if ($violations) {
            throw new Exception("FK integrity check failed: " . json_encode($violations));
        }
        
        // Verify data counts
        $jobsCount = $this->db->fetch("SELECT COUNT(*) as c FROM jobs")['c'];
        $moneyCount = $this->db->fetch("SELECT COUNT(*) as c FROM money_entries")['c'];
        $activityCount = $this->db->fetch("SELECT COUNT(*) as c FROM activity_log")['c'];
        
        echo "   Data integrity:\n";
        echo "     - Jobs: {$jobsCount} records\n";
        echo "     - Money entries: {$moneyCount} records\n";
        echo "     - Activity logs: {$activityCount} records\n";
    }
    
    /**
     * Log migration
     */
    private function logMigration()
    {
        try {
            $this->db->execute("
                INSERT INTO migration_log (migration_name, executed_at) 
                VALUES ('015_enforce_fk_cascades', datetime('now'))
            ");
        } catch (Exception $e) {
            // migration_log table might not exist, ignore
        }
    }
    
    /**
     * Rollback migration
     */
    public function down()
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║         ROLLBACK MIGRATION 015: FK CASCADES               ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        echo "⚠️  Manual rollback required:\n";
        echo "   1. Stop application\n";
        echo "   2. Restore from backup:\n";
        echo "      cp db/backups/pre-fk-migration-*.sqlite db/app.sqlite\n";
        echo "   3. Restart application\n";
        echo "\n";
        
        return false;
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli') {
    echo "\n";
    echo "⚠️  WARNING: This migration will recreate tables!\n";
    echo "   Make sure you have:\n";
    echo "   1. Run validate_orphaned_records.php (no issues)\n";
    echo "   2. Run cleanup_orphaned_records.php --execute (if needed)\n";
    echo "   3. Backed up your database\n";
    echo "\n";
    
    if (in_array('--execute', $argv)) {
        $migration = new FKCascadeMigration();
        $success = $migration->up();
        exit($success ? 0 : 1);
    } else {
        echo "Dry run mode. To execute migration, run:\n";
        echo "  php 015_enforce_fk_cascades.php --execute\n\n";
        exit(0);
    }
}


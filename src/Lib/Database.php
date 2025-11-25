<?php
/**
 * Veritabanı Bağlantı Sınıfı
 * PDO SQLite singleton pattern
 */

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $this->connect();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect()
    {
        try {
            $dbPath = DB_PATH;
            $dbDir = dirname($dbPath);

            // ===== PHASE 3: Directory Auto-Creation =====
            // Ensure directory exists with proper permissions
            if (!is_dir($dbDir)) {
                try {
                    if (!mkdir($dbDir, 0775, true)) {
                        throw new Exception("Failed to create database directory: {$dbDir}");
                    }
                    // Try to set permissions after creation
                    try {
                        chmod($dbDir, 0775);
                    } catch (Exception $e) {
                        // Permission setting failed, but directory was created
                        if (defined('APP_DEBUG') && APP_DEBUG) {
                            error_log("Warning: Could not set permissions on database directory: " . $e->getMessage());
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error creating database directory: " . $e->getMessage());
                    throw new Exception("Veritabanı dizini oluşturulamadı: {$dbDir}");
                }
            }

            // ===== PHASE 3: Permission Check (limited in production) =====
            // In production, limit permission fix attempts for security
            $isProduction = defined('APP_DEBUG') && !APP_DEBUG;
            $maxPermissionAttempts = $isProduction ? 1 : 2;

            // Fix directory permissions if not writable
            if (is_dir($dbDir) && !is_writable($dbDir)) {
                $attempts = 0;
                try {
                    chmod($dbDir, 0775);
                    $attempts++;
                } catch (Exception $e) {
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Warning: Could not chmod database directory: " . $e->getMessage());
                    }
                    $attempts++;
                }
                // If still not writable and not production, try 0777 (less secure)
                if (!is_writable($dbDir) && $attempts < $maxPermissionAttempts) {
                    try {
                        chmod($dbDir, 0777);
                        $attempts++;
                    } catch (Exception $e) {
                        if (defined('APP_DEBUG') && APP_DEBUG) {
                            error_log("Warning: Could not chmod database directory to 0777: " . $e->getMessage());
                        }
                        $attempts++;
                    }
                }
            }

            // Check if database file exists and is writable, or if directory is writable to create it
            $dbExists = file_exists($dbPath);
            
            // ===== PHASE 3: File Permission Check (limited in production) =====
            // If database file exists but is not writable, try to fix permissions
            if ($dbExists && !is_writable($dbPath)) {
                $attempts = 0;
                // Try to fix file permissions
                try {
                    chmod($dbPath, 0664);
                    $attempts++;
                } catch (Exception $e) {
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Warning: Could not chmod database file: " . $e->getMessage());
                    }
                    $attempts++;
                }
                // If still not writable and not production, try 0666 (less secure)
                if (!is_writable($dbPath) && $attempts < $maxPermissionAttempts) {
                    try {
                        chmod($dbPath, 0666);
                        $attempts++;
                    } catch (Exception $e) {
                        if (defined('APP_DEBUG') && APP_DEBUG) {
                            error_log("Warning: Could not chmod database file to 0666: " . $e->getMessage());
                        }
                        $attempts++;
                    }
                }
                
                // Final check after attempting to fix permissions
                if (!is_writable($dbPath)) {
                    // Check if directory is writable - if yes, we might be able to recreate the file
                    if (is_writable($dbDir)) {
                        // Try to copy to a temp file, delete original, and move back
                        $tempPath = $dbPath . '.tmp';
                        try {
                            if (copy($dbPath, $tempPath)) {
                                try {
                                    unlink($dbPath);
                                    if (rename($tempPath, $dbPath)) {
                                        try {
                                            chmod($dbPath, 0664);
                                        } catch (Exception $e) {
                                            // Ignore chmod error after successful rename
                                        }
                                    } else {
                                        // Restore if rename failed
                                        rename($tempPath, $dbPath);
                                    }
                                } catch (Exception $e) {
                                    // Restore if unlink failed
                                    if (file_exists($tempPath)) {
                                        unlink($tempPath);
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            if (defined('APP_DEBUG') && APP_DEBUG) {
                                error_log("Warning: Could not recreate database file: " . $e->getMessage());
                            }
                        }
                    }
                    
                    // Final check - if still not writable, throw error
                    if (!is_writable($dbPath)) {
                        $errorMsg = "SQLite database file is not writable: {$dbPath}. Please check file permissions (needs 0664 or 0666). Current permissions: " . substr(sprintf('%o', fileperms($dbPath)), -4);
                        if (function_exists('safe_error_log')) {
                            safe_error_log($errorMsg);
                        } else {
                        error_log($errorMsg);
                        }
                        if (defined('APP_DEBUG') && APP_DEBUG) {
                            throw new Exception($errorMsg);
                        }
                        throw new Exception("Veritabanı dosyasına yazma izni yok. Lütfen hosting sağlayıcınızla iletişime geçin ve dosya izinlerini 0664 veya 0666 olarak ayarlayın.");
                    }
                }
            } elseif (!$dbExists) {
                // Database doesn't exist - check if we can create it
                if (!is_dir($dbDir) || !is_writable($dbDir)) {
                    $errorMsg = "Database directory is not writable: {$dbDir}. Please check directory permissions (needs 0755 or 0777).";
                    if (function_exists('safe_error_log')) {
                        safe_error_log($errorMsg);
                    } else {
                    error_log($errorMsg);
                    }
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        throw new Exception($errorMsg);
                    }
                    throw new Exception("Veritabanı dizinine yazma izni yok. Lütfen hosting sağlayıcınızla iletişime geçin ve dizin izinlerini 0755 veya 0777 olarak ayarlayın.");
                }
            }

            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $this->pdo->exec("PRAGMA encoding=UTF8");
            
            // Try to set WAL mode, but fall back to DELETE if WAL files can't be created
            try {
                $this->pdo->exec("PRAGMA journal_mode=WAL");
                // ===== PHASE 3: WAL/SHM File Permissions =====
                // Ensure WAL and SHM files are writable if they exist
                $walPath = $dbPath . '-wal';
                $shmPath = $dbPath . '-shm';
                if (file_exists($walPath) && !is_writable($walPath)) {
                    try {
                        chmod($walPath, 0664);
                    } catch (Exception $e) {
                        if (defined('APP_DEBUG') && APP_DEBUG) {
                            error_log("Warning: Could not chmod WAL file: " . $e->getMessage());
                        }
                    }
                    // If still not writable and not production, try 0666
                    if (!is_writable($walPath) && !$isProduction) {
                        try {
                            chmod($walPath, 0666);
                        } catch (Exception $e) {
                            if (defined('APP_DEBUG') && APP_DEBUG) {
                                error_log("Warning: Could not chmod WAL file to 0666: " . $e->getMessage());
                            }
                        }
                    }
                }
                if (file_exists($shmPath) && !is_writable($shmPath)) {
                    try {
                        chmod($shmPath, 0664);
                    } catch (Exception $e) {
                        if (defined('APP_DEBUG') && APP_DEBUG) {
                            error_log("Warning: Could not chmod SHM file: " . $e->getMessage());
                        }
                    }
                    // If still not writable and not production, try 0666
                    if (!is_writable($shmPath) && !$isProduction) {
                        try {
                            chmod($shmPath, 0666);
                        } catch (Exception $e) {
                            if (defined('APP_DEBUG') && APP_DEBUG) {
                                error_log("Warning: Could not chmod SHM file to 0666: " . $e->getMessage());
                            }
                        }
                    }
                }
            } catch (PDOException $walError) {
                // If WAL mode fails (e.g., on NFS or read-only filesystem), fall back to DELETE mode
                error_log("WAL mode failed, using DELETE mode: " . $walError->getMessage());
                try {
                    $this->pdo->exec("PRAGMA journal_mode=DELETE");
                } catch (PDOException $deleteError) {
                    error_log("Could not set journal mode: " . $deleteError->getMessage());
                }
            }
            $this->pdo->exec("PRAGMA foreign_keys=ON");
            $this->pdo->exec("PRAGMA synchronous=NORMAL");
            $this->pdo->exec("PRAGMA cache_size=1000");
            $this->pdo->exec("PRAGMA temp_store=MEMORY");
            
            // Check if users table exists and has any records
            $usersTableExists = false;
            try {
                $checkStmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
                $usersTableExists = $checkStmt->fetch() !== false;
            } catch (PDOException $e) {
                // Database may be corrupted, recreate
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Database check failed: " . $e->getMessage());
                }
            }
            if (!$usersTableExists) {
                $this->createDatabase();
                $checkStmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
                $usersTableExists = $checkStmt->fetch() !== false;
            }
            
            $count = 0;
            if ($usersTableExists) {
                $countStmt = $this->pdo->query("SELECT COUNT(*) as c FROM users");
                $count = (int)($countStmt->fetch()['c'] ?? 0);
            }
            
            // Only create default users if database is completely empty
            // NOTE: This is for initial setup only. Change default passwords immediately!
            if ($count === 0) {
                // Get default password from environment or use a secure default
                $defaultAdminPass = $_ENV['DEFAULT_ADMIN_PASSWORD'] ?? 'ChangeMe123!';
                $defaultOpPass = $_ENV['DEFAULT_OP_PASSWORD'] ?? 'ChangeMe123!';
                
                $adminHash = password_hash($defaultAdminPass, PASSWORD_DEFAULT);
                $opHash = password_hash($defaultOpPass, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
                $stmt->execute(['candas', $adminHash, 'ADMIN']);
                $stmt->execute(['necla', $opHash, 'OPERATOR']);
                
                // Log warning in production
                if (!(defined('APP_DEBUG') && APP_DEBUG)) {
                    error_log("WARNING: Default users created with temporary passwords. Change immediately!");
                }
            }

            // Fix legacy placeholder passwords from old installs
            $placeholder = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            $rows = [];
            if ($usersTableExists) {
                $fixStmt = $this->pdo->prepare("SELECT id, username, password_hash FROM users WHERE password_hash = ?");
                $fixStmt->execute([$placeholder]);
                $rows = $fixStmt->fetchAll();
            }
            if (!empty($rows)) {
                // Get secure passwords from environment
                $adminPass = $_ENV['ADMIN_PASSWORD'] ?? null;
                $opPass = $_ENV['OP_PASSWORD'] ?? null;
                
                $upd = $this->pdo->prepare("UPDATE users SET password_hash = ?, updated_at = datetime('now') WHERE id = ?");
                foreach ($rows as $row) {
                    $newHash = null;
                    if ($row['username'] === 'candas' && $adminPass) {
                        $newHash = password_hash($adminPass, PASSWORD_DEFAULT);
                    } elseif ($row['username'] === 'necla' && $opPass) {
                        $newHash = password_hash($opPass, PASSWORD_DEFAULT);
                    } elseif ($row['username'] === 'candas') {
                        // Use a random secure password if not set
                        $newHash = password_hash('ChangeMe123!', PASSWORD_DEFAULT);
                    } elseif ($row['username'] === 'necla') {
                        $newHash = password_hash('ChangeMe123!', PASSWORD_DEFAULT);
                    }
                    if ($newHash) {
                        $upd->execute([$newHash, $row['id']]);
                    }
                }
            }

            try {
                $this->ensureSchema();
            } catch (Exception $schemaError) {
                error_log("Schema update failed (continuing anyway): " . $schemaError->getMessage());
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            error_log("Database connection failed: " . $errorMsg);
            
            // Check for readonly database error
            if (strpos($errorMsg, 'readonly') !== false || strpos($errorMsg, 'read-only') !== false) {
                $msg = "Veritabanı dosyası salt okunur modda. Lütfen hosting sağlayıcınızla iletişime geçin ve dosya izinlerini kontrol edin: " . $dbPath;
                error_log($msg);
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    throw new Exception($msg . " | Original error: " . $errorMsg);
                }
                throw new Exception("Veritabanı dosyası salt okunur modda. Lütfen hosting sağlayıcınızla iletişime geçin.");
            }
            
            if (defined('APP_DEBUG') && APP_DEBUG) {
                throw new Exception("Veritabanı bağlantısı kurulamadı: " . $errorMsg);
            }
            throw new Exception("Veritabanı bağlantısı kurulamadı");
        }
    }

    private function ensureSchema(): void
    {
        // First check if jobs table exists
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='jobs'");
        $tableExists = $stmt->fetch() !== false;
        
        if (!$tableExists) {
            // Database is not initialized, skip schema updates
            return;
        }
        
        $columns = $this->pdo->query("PRAGMA table_info(jobs)")->fetchAll();
        $columnNames = array_map(function ($row) {
            return $row['name'] ?? null;
        }, $columns);

        // Check for critical missing columns that would break the app
        $requiredColumns = [
            'start_at' => 'TEXT DEFAULT ""',
            'end_at' => 'TEXT DEFAULT ""',
            'service_id' => 'INTEGER',
            'customer_id' => 'INTEGER',
            'address_id' => 'INTEGER',
            'status' => 'TEXT DEFAULT "SCHEDULED"',
            'assigned_to' => 'INTEGER',
            'note' => 'TEXT',
            'income_id' => 'INTEGER',
        ];
        
        foreach ($requiredColumns as $colName => $colDef) {
            if (!in_array($colName, $columnNames, true)) {
                try {
                    $this->pdo->exec("ALTER TABLE jobs ADD COLUMN {$colName} {$colDef}");
                } catch (PDOException $e) {
                    error_log("Failed to add column {$colName} to jobs table: " . $e->getMessage());
                }
            }
        }

        if (!in_array('total_amount', $columnNames, true)) {
            $this->pdo->exec("ALTER TABLE jobs ADD COLUMN total_amount REAL NOT NULL DEFAULT 0");
        }

        if (!in_array('amount_paid', $columnNames, true)) {
            $this->pdo->exec("ALTER TABLE jobs ADD COLUMN amount_paid REAL NOT NULL DEFAULT 0");
        }

        if (!in_array('payment_status', $columnNames, true)) {
            $this->pdo->exec("ALTER TABLE jobs ADD COLUMN payment_status TEXT NOT NULL DEFAULT 'UNPAID'");
        }

        $this->pdo->exec("UPDATE jobs SET payment_status = 'UNPAID' WHERE payment_status IS NULL");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS job_payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            paid_at TEXT NOT NULL DEFAULT (date('now')),
            note TEXT,
            finance_id INTEGER,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            FOREIGN KEY(finance_id) REFERENCES money_entries(id) ON DELETE SET NULL
        )");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_job_payments_job_id ON job_payments(job_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_job_payments_finance_id ON job_payments(finance_id)");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            rate_key TEXT PRIMARY KEY,
            attempts INTEGER NOT NULL DEFAULT 0,
            first_attempt_at INTEGER,
            blocked_until INTEGER
        )");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_rate_limits_blocked_until ON rate_limits(blocked_until)");

        // Remember-me tokens hardening: ensure hashed column exists and indexed
        // Move ensureColumn after table creation below
        try {
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_remember_tokens_token_hash ON remember_tokens(token_hash)");
        } catch (Exception $e) {
            // ignore
        }

        // Ensure remember_tokens table exists
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT,
            token_hash TEXT,
            expires_at TEXT NOT NULL,
            last_used_at TEXT,
            created_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_remember_tokens_user ON remember_tokens(user_id)");
        
        // Ensure hashed column exists for remember_tokens
        $this->ensureColumn('remember_tokens', 'token_hash', 'TEXT');

        // Slow queries table for monitoring
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS slow_queries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            occurred_at TEXT NOT NULL DEFAULT (datetime('now')),
            duration_ms REAL NOT NULL,
            query TEXT NOT NULL,
            params TEXT,
            rows INTEGER,
            path TEXT,
            method TEXT,
            ip TEXT
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_slow_queries_time ON slow_queries(occurred_at)");

        // Recurring jobs schema
        // jobs: add recurring links if missing
        // Use the columns we already fetched
        $jobColNames = $columnNames;
        if (!in_array('recurring_job_id', $jobColNames, true)) {
            $this->pdo->exec("ALTER TABLE jobs ADD COLUMN recurring_job_id INTEGER NULL");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_jobs_recurring_job_id ON jobs(recurring_job_id)");
        }
        if (!in_array('occurrence_id', $jobColNames, true)) {
            $this->pdo->exec("ALTER TABLE jobs ADD COLUMN occurrence_id INTEGER NULL");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_jobs_occurrence_id ON jobs(occurrence_id)");
        }

        // recurring_jobs table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS recurring_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            address_id INTEGER,
            service_id INTEGER,
            frequency TEXT NOT NULL,
            interval INTEGER NOT NULL DEFAULT 1,
            byweekday TEXT,
            bymonthday INTEGER,
            byhour INTEGER,
            byminute INTEGER,
            duration_min INTEGER NOT NULL DEFAULT 60,
            start_date TEXT NOT NULL,
            end_date TEXT,
            timezone TEXT NOT NULL DEFAULT 'Europe/Istanbul',
            status TEXT NOT NULL DEFAULT 'ACTIVE',
            default_total_amount REAL NOT NULL DEFAULT 0,
            default_notes TEXT,
            default_assignees TEXT,
            exclusions TEXT,
            holiday_policy TEXT DEFAULT 'SKIP',
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_recurring_jobs_customer ON recurring_jobs(customer_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_recurring_jobs_status ON recurring_jobs(status)");
        
        // Add bymonthday column if it doesn't exist (for existing installations)
        $this->ensureColumn('recurring_jobs', 'bymonthday', 'INTEGER');
        
        // Add pricing model columns
        $this->ensureColumn('recurring_jobs', 'pricing_model', "TEXT DEFAULT 'PER_JOB'");
        $this->ensureColumn('recurring_jobs', 'monthly_amount', 'REAL');
        $this->ensureColumn('recurring_jobs', 'contract_total_amount', 'REAL');
        
        // Add recurring_job_id to money_entries for contract-based income
        $this->ensureColumn('money_entries', 'recurring_job_id', 'INTEGER');
        
        // Add is_archived column to money_entries for soft deletion support
        $this->ensureColumn('money_entries', 'is_archived', 'INTEGER DEFAULT 0');

        // recurring_job_occurrences table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS recurring_job_occurrences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recurring_job_id INTEGER NOT NULL,
            date TEXT NOT NULL,
            start_at TEXT NOT NULL,
            end_at TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'PLANNED',
            job_id INTEGER NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY(recurring_job_id) REFERENCES recurring_jobs(id) ON DELETE CASCADE
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_occurrences_recurring ON recurring_job_occurrences(recurring_job_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_occurrences_date ON recurring_job_occurrences(date)");
        
        // Add job_id column if it doesn't exist (for existing installations)
        $this->ensureColumn('recurring_job_occurrences', 'job_id', 'INTEGER');
        // Add company_id column for multi-tenancy (for existing installations)
        $this->ensureColumn('recurring_job_occurrences', 'company_id', 'INTEGER');
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_occurrences_company_id ON recurring_job_occurrences(company_id)");
        
        // Create or recreate the compatibility view with job_id
        $this->pdo->exec("DROP VIEW IF EXISTS v_recurring_job_occurrences");
        $this->pdo->exec("CREATE VIEW IF NOT EXISTS v_recurring_job_occurrences AS
            SELECT 
              id,
              recurring_job_id,
              date AS scheduled_date,
              start_at AS scheduled_start_at,
              end_at AS scheduled_end_at,
              date, 
              start_at, 
              end_at, 
              status, 
              job_id,
              company_id,
              created_at, 
              updated_at
            FROM recurring_job_occurrences");

        // Appointments table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS appointments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            start_at TEXT NOT NULL,
            end_at TEXT,
            status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'confirmed', 'cancelled', 'completed')),
            notes TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_appointments_customer_id ON appointments(customer_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_appointments_start_at ON appointments(start_at)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments(status)");
        
        // Payments table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id INTEGER,
            appointment_id INTEGER,
            customer_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            payment_method TEXT NOT NULL CHECK(payment_method IN ('cash', 'card', 'transfer', 'check')),
            status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'completed', 'failed', 'refunded')),
            transaction_id TEXT,
            notes TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE SET NULL,
            FOREIGN KEY(appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
            FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_payments_customer_id ON payments(customer_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_payments_job_id ON payments(job_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_payments_created_at ON payments(created_at)");

        // Notifications read state
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS notifications_read (
            user_id INTEGER NOT NULL,
            notif_key TEXT NOT NULL,
            read_at TEXT NOT NULL DEFAULT (datetime('now')),
            PRIMARY KEY (user_id, notif_key)
        )");

        // Notification preferences (mute categories per user)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS notification_prefs (
            user_id INTEGER PRIMARY KEY,
            mute_critical INTEGER NOT NULL DEFAULT 0,
            mute_ops INTEGER NOT NULL DEFAULT 0,
            mute_system INTEGER NOT NULL DEFAULT 0
        )");
        // Calendar-related preference columns
        $this->ensureColumn('notification_prefs', 'calendar_reminders_email', 'INTEGER DEFAULT 1');
        $this->ensureColumn('notification_prefs', 'calendar_reminders_sms', 'INTEGER DEFAULT 0');
        $this->ensureColumn('notification_prefs', 'timezone', "TEXT DEFAULT 'Europe/Istanbul'");
        $this->ensureColumn('notification_prefs', 'work_start', "TEXT DEFAULT '09:00'");
        $this->ensureColumn('notification_prefs', 'work_end', "TEXT DEFAULT '18:00'");
        $this->ensureColumn('notification_prefs', 'weekend_shading', 'INTEGER DEFAULT 1');
        $this->ensureColumn('notification_prefs', 'calendar_density', "TEXT DEFAULT 'comfortable'");

        // Calendar sync tables
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS calendar_sync (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            provider TEXT NOT NULL, -- google|microsoft
            external_user_id TEXT,
            access_token TEXT,
            refresh_token TEXT,
            token_expires_at INTEGER,
            sync_cursor TEXT,
            webhook_id TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            UNIQUE(user_id, provider)
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_calendar_sync_user ON calendar_sync(user_id)");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS calendar_external_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            provider TEXT NOT NULL,
            external_id TEXT NOT NULL,
            etag TEXT,
            job_id INTEGER,
            last_sync_at TEXT,
            fingerprint TEXT,
            UNIQUE(user_id, provider, external_id)
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_calendar_events_user ON calendar_external_events(user_id)");

        // Building Facilities and Reservations tables
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS building_facilities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            building_id INTEGER NOT NULL,
            facility_name TEXT NOT NULL,
            facility_type TEXT NOT NULL CHECK(facility_type IN ('pool', 'gym', 'party_hall', 'playground', 'barbecue', 'parking', 'storage', 'other')),
            description TEXT,
            capacity INTEGER,
            hourly_rate DECIMAL(10,2) DEFAULT 0,
            daily_rate DECIMAL(10,2) DEFAULT 0,
            requires_approval INTEGER DEFAULT 1,
            max_advance_days INTEGER DEFAULT 30,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_facilities_building_id ON building_facilities(building_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_facilities_type ON building_facilities(facility_type)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_facilities_active ON building_facilities(is_active)");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS building_reservations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            building_id INTEGER NOT NULL,
            facility_id INTEGER NOT NULL,
            unit_id INTEGER,
            resident_name TEXT NOT NULL,
            resident_phone TEXT,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            reservation_type TEXT DEFAULT 'hourly' CHECK(reservation_type IN ('hourly', 'daily', 'weekly')),
            total_amount DECIMAL(10,2) DEFAULT 0,
            deposit_amount DECIMAL(10,2) DEFAULT 0,
            status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected', 'cancelled', 'completed')),
            approved_by INTEGER,
            notes TEXT,
            cancelled_reason TEXT,
            created_by INTEGER,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY(building_id) REFERENCES buildings(id) ON DELETE CASCADE,
            FOREIGN KEY(facility_id) REFERENCES building_facilities(id) ON DELETE CASCADE,
            FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE SET NULL,
            FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
        )");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_reservations_building_id ON building_reservations(building_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_reservations_facility_id ON building_reservations(facility_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_reservations_unit_id ON building_reservations(unit_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_reservations_status ON building_reservations(status)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_reservations_dates ON building_reservations(start_date, end_date)");
    }

    private function createDatabase()
    {
        $installSql = file_get_contents(__DIR__ . '/../../db/install.sql');
        $rawStatements = explode(';', $installSql);
        foreach ($rawStatements as $raw) {
            $lines = preg_split('/\r?\n/', $raw);
            $filtered = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                    continue;
                }
                if (($commentPos = strpos($trimmed, '--')) !== false) {
                    $trimmed = trim(substr($trimmed, 0, $commentPos));
                    if ($trimmed === '') {
                        continue;
                    }
                }
                $filtered[] = $trimmed;
            }

            $statement = trim(implode(' ', $filtered));
            if ($statement === '') {
                continue;
            }

            $this->pdo->exec($statement);
        }
    }

    public function getPdo()
    {
        return $this->pdo;
    }
    
    /**
     * Initialize indexes after connection (called explicitly)
     * Only runs once per application lifetime
     */
    public function initializeIndexes(): void
    {
        // Use static flag to ensure single execution
        static $initialized = false;
        if ($initialized) {
            return;
        }
        $initialized = true;
        
        try {
            if (class_exists('DatabaseIndexer')) {
                // Run index creation in background (non-blocking)
                DatabaseIndexer::ensureIndexes();
            }
        } catch (Exception $e) {
            // Don't break the application if index creation fails
            if (APP_DEBUG) {
                error_log("Index initialization error: " . $e->getMessage());
            }
        }
    }

    public function query($sql, $params = [])
    {
        $startTime = microtime(true);
        
        try {
            // Ensure $params is an array
            if (!is_array($params)) {
                $params = [];
            }
            
            // Normalize params - convert arrays to JSON strings for logging
            $normalizedParams = [];
            // Normalize params to prevent array to string conversion warnings
            $normalizedParams = [];
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    $normalizedParams[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                } else {
                    $normalizedParams[$key] = $value;
                }
            }
            
            $stmt = $this->pdo->prepare($sql);
            // Use normalized params to prevent array to string conversion warnings
            $stmt->execute($normalizedParams);
            
            $executionTime = microtime(true) - $startTime;
            
            // Log query if Logger is available and query logging is enabled
            // Use normalized params for logging to avoid array to string conversion
            if (class_exists('Logger') && ($_ENV['LOG_QUERIES'] ?? false)) {
                Logger::query($sql, $normalizedParams, $executionTime, $stmt->rowCount());
            }
            
            // Slow query threshold from ENV (ms), default 200ms
            $thresholdMs = (float)($_ENV['SLOW_QUERY_THRESHOLD_MS'] ?? 200);
            $durationMs = $executionTime * 1000;
            if ($durationMs >= $thresholdMs) {
                // Insert into slow_queries table
                try {
                    // Use normalized params for JSON encoding to avoid array to string conversion
                    $paramsJson = json_encode($normalizedParams ?? $params, JSON_UNESCAPED_UNICODE);
                    $ins = $this->pdo->prepare("INSERT INTO slow_queries (occurred_at, duration_ms, query, params, rows, path, method, ip) VALUES (datetime('now'), ?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([
                        round($durationMs, 2),
                        substr($sql, 0, 2000),
                        $paramsJson,
                        (int)$stmt->rowCount(),
                        $_SERVER['REQUEST_URI'] ?? null,
                        $_SERVER['REQUEST_METHOD'] ?? null,
                        $_SERVER['REMOTE_ADDR'] ?? null,
                    ]);
                } catch (Exception $e) {
                    // do not interrupt main flow
                }

                if (class_exists('Logger')) {
                    Logger::warning('Slow query detected', [
                        'query' => substr($sql, 0, 200),
                        'params' => $params,
                        'execution_time_ms' => round($durationMs, 2),
                        'rows' => $stmt->rowCount()
                    ]);
                }
            }
            
            return $stmt;
        } catch (PDOException $e) {
            // Log database errors
            if (class_exists('Logger')) {
                Logger::error('Database query failed', [
                    'query' => $sql,
                    'params' => $params,
                    'error' => $e->getMessage()
                ]);
            }
            
            // ===== IMPROVEMENT: Safe error logging with sensitive data masking =====
            $errorMsg = "Database query failed: " . $e->getMessage();
            $sqlMsg = "SQL: " . $sql;
            // Mask sensitive data in params (passwords, tokens, etc.)
            $maskedParams = $params;
            if (is_array($maskedParams)) {
                foreach ($maskedParams as $key => $value) {
                    $keyLower = strtolower((string)$key);
                    if (is_string($value) && (
                        strpos($keyLower, 'password') !== false ||
                        strpos($keyLower, 'token') !== false ||
                        strpos($keyLower, 'secret') !== false ||
                        strpos($keyLower, 'key') !== false
                    )) {
                        $maskedParams[$key] = '[HIDDEN]';
                    }
                }
            }
            $paramsMsg = "Params: " . print_r($maskedParams, true);
            
            // Use safe_error_log if available, otherwise use regular error_log
            if (function_exists('safe_error_log')) {
                safe_error_log($errorMsg);
                safe_error_log($sqlMsg);
                safe_error_log($paramsMsg);
            } else {
                error_log($errorMsg);
                error_log($sqlMsg);
                error_log($paramsMsg);
            }
            throw new Exception("Veritabanı sorgusu başarısız: " . $e->getMessage());
        }
    }

    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Validate table name against whitelist
     * ===== ERR-007 FIX: Table name whitelist validation =====
     */
    private function validateTableName(string $table): string
    {
        // First sanitize
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        // Allow test tables (prefixed with test_ or _test) in test environment
        if (defined('PHPUNIT_TEST') || (defined('APP_ENV') && APP_ENV === 'test')) {
            if (preg_match('/^(test_|_test)/', $sanitized)) {
                return $sanitized;
            }
        }
        
        // Get list of valid tables from schema
        static $validTables = null;
        if ($validTables === null) {
            $validTables = $this->getValidTableNames();
        }
        
        // Check if table is in whitelist
        if (!in_array($sanitized, $validTables, true)) {
            if (class_exists('Logger')) {
                Logger::warning('Invalid table name attempted', ['table' => $sanitized]);
            }
            throw new InvalidArgumentException("Invalid table name: {$sanitized}");
        }
        
        return $sanitized;
    }
    
    /**
     * Get list of valid table names from database schema
     */
    private function getValidTableNames(): array
    {
        try {
            $tables = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
            return $tables ?: [];
        } catch (Exception $e) {
            // Fallback to hardcoded list if query fails
            return [
                'users', 'customers', 'jobs', 'services', 'money_entries', 'recurring_jobs', 'recurring_job_occurrences',
                'job_payments', 'addresses', 'companies', 'activity_log', 'appointments', 'building_announcements',
                'building_documents', 'building_expenses', 'building_facilities', 'building_meetings', 'building_reservations',
                'building_surveys', 'buildings', 'comment_attachments', 'comment_mentions', 'comment_reactions', 'comments',
                'contract_attachments', 'contracts', 'file_uploads', 'management_fees', 'management_fee_payments', 'notifications',
                'notification_preferences', 'online_payments', 'resident_users', 'resident_verifications', 'resident_requests',
                'roles', 'permissions', 'role_permissions', 'user_roles', 'sessions', 'slow_queries', 'units', 'audit_log',
                'email_queue', 'email_logs', 'sms_queue', 'sms_logs', 'calendar_syncs', 'cache', 'cache_tags', 'migrations'
            ];
        }
    }
    
    public function insert($table, $data)
    {
        // ===== ERR-007 FIX: Validate table name against whitelist =====
        $table = $this->validateTableName($table);
        
        // Sanitize column names and build SQL properly
        $sanitizedColumns = [];
        $placeholders = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $sanitizedKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $sanitizedColumns[] = "`{$sanitizedKey}`";
            $placeholders[] = ":{$key}";
            $values[$key] = $value;
        }
        
        $columns = implode(', ', $sanitizedColumns);
        $placeholdersStr = implode(', ', $placeholders);

        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholdersStr})";
        $this->query($sql, $values);

        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        // ===== ERR-007 FIX: Validate table name against whitelist =====
        $table = $this->validateTableName($table);
        
        $setParts = [];
        $params = [];

        foreach ($data as $key => $value) {
            // Sanitize column name
            $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $placeholder = "set_{$key}";
            $setParts[] = "`{$key}` = :$placeholder";
            $params[$placeholder] = $value;
        }

        $whereClause = $where;
        if (is_array($where)) {
            $parts = [];
            $index = 0;
            foreach ($where as $key => $value) {
                $sanitizedKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
                $placeholder = "where_{$sanitizedKey}_{$index}";
                $parts[] = "`{$sanitizedKey}` = :{$placeholder}";
                $params[$placeholder] = $value;
                $index++;
            }
            $whereClause = implode(' AND ', $parts);
        } elseif (!empty($whereParams)) {
            if (strpos($whereClause, '?') !== false) {
                $i = 0;
                foreach ($whereParams as $value) {
                    $placeholder = "where_{$i}";
                    $whereClause = preg_replace('/\?/', ':' . $placeholder, $whereClause, 1);
                    $params[$placeholder] = $value;
                    $i++;
                }
            } else {
                foreach ($whereParams as $key => $value) {
                    if (is_int($key)) {
                        $placeholder = "where_{$key}";
                        $whereClause = str_replace(':' . $key, ':' . $placeholder, $whereClause);
                        $params[$placeholder] = $value;
                    } else {
                        $params[$key] = $value;
                    }
                }
            }
        } elseif (empty($whereClause)) {
            throw new InvalidArgumentException('WHERE koşulu boş olamaz.');
        }
        
        // ===== ERR-016 FIX: Validate whereClause to prevent SQL injection =====
        // If whereClause is a string, ensure it only contains safe characters
        // (alphanumeric, spaces, operators, parentheses, placeholders)
        if (is_string($whereClause) && !empty($whereParams)) {
            // Already using parameters, safe
        } elseif (is_string($whereClause) && empty($whereParams)) {
            // String without parameters - validate it doesn't contain dangerous patterns
            $dangerousPatterns = [
                '/;\s*(DROP|DELETE|TRUNCATE|ALTER|CREATE|INSERT|UPDATE|EXEC|EXECUTE)/i',
                '/--/',
                '/\/\*/',
                '/UNION\s+SELECT/i',
                '/\bOR\s+1\s*=\s*1\b/i',
                '/\bAND\s+1\s*=\s*1\b/i'
            ];
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $whereClause)) {
                    throw new InvalidArgumentException('Geçersiz WHERE koşulu: Güvenlik nedeniyle reddedildi.');
                }
            }
        }
        // ===== ERR-016 FIX: End =====

        $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE $whereClause";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete($table, $where, $params = [])
    {
        // ===== ERR-007 FIX: Validate table name against whitelist =====
        $table = $this->validateTableName($table);
        
        // ===== ERR-016 FIX: Validate where clause to prevent SQL injection =====
        // If where is a string without parameters, validate it
        if (is_string($where) && empty($params)) {
            $dangerousPatterns = [
                '/;\s*(DROP|DELETE|TRUNCATE|ALTER|CREATE|INSERT|UPDATE|EXEC|EXECUTE)/i',
                '/--/',
                '/\/\*/',
                '/UNION\s+SELECT/i',
                '/\bOR\s+1\s*=\s*1\b/i',
                '/\bAND\s+1\s*=\s*1\b/i'
            ];
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $where)) {
                    throw new InvalidArgumentException('Geçersiz WHERE koşulu: Güvenlik nedeniyle reddedildi.');
                }
            }
        }
        // ===== ERR-016 FIX: End =====
        
        // NOT: PRAGMA foreign_keys ayarı burada yapılmamalı
        // Çünkü Customer::delete() gibi metodlar foreign_keys'i geçici olarak kapatabilir
        // Burada tekrar açmak, silme işlemini bozar
        
        $sql = "DELETE FROM `{$table}` WHERE $where";
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // If foreign key constraint violation, provide more context
            if ($e->getCode() === '23000' && strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
                error_log("Database::delete() - FOREIGN KEY constraint violation for table={$table}, where={$where}");
                error_log("Database::delete() - Error: " . $e->getMessage());
                throw new Exception("Silme işlemi başarısız: Bu kayıt başka kayıtlarla ilişkili. Lütfen önce ilişkili kayıtları silin. ({$e->getMessage()})", 0, $e);
            }
            throw $e;
        }
    }

    /**
     * Execute a raw SQL statement that doesn't return a result set
     * (e.g., CREATE INDEX, ANALYZE, ALTER TABLE)
     */
    public function execute(string $sql): bool
    {
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            if (class_exists('Logger')) {
                Logger::error('Database execute failed', [
                    'sql' => $sql,
                    'error' => $e->getMessage(),
                ]);
            }
            throw new Exception('Veritabanı komutu çalıştırılamadı: ' . $e->getMessage());
        }
    }

    public function beginTransaction()
    {
        // Check if already in transaction
        if ($this->pdo->inTransaction()) {
            error_log("Database::beginTransaction() - Already in transaction, skipping");
            return false;
        }
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit current transaction
     * 
     * @return bool True if commit was successful, false on error
     */
    public function commit(): bool
    {
        // Only commit if in transaction
        if (!$this->pdo->inTransaction()) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Database::commit() - Not in transaction, skipping");
            }
            return false;
        }
        
        try {
            $result = $this->pdo->commit();
            if (!$result) {
                error_log("Database::commit() - Commit failed");
            }
            return $result;
        } catch (Exception $e) {
            error_log("Database::commit() - Exception during commit: " . $e->getMessage());
            // If commit fails, try to rollback
            try {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            } catch (Exception $e2) {
                error_log("Database::commit() - Rollback after failed commit also failed: " . $e2->getMessage());
            }
            return false;
        } catch (Throwable $e) {
            error_log("Database::commit() - Throwable during commit: " . $e->getMessage());
            // If commit fails, try to rollback
            try {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            } catch (Throwable $e2) {
                error_log("Database::commit() - Rollback after failed commit also failed: " . $e2->getMessage());
            }
            return false;
        }
    }

    /**
     * Rollback current transaction
     * Ensures rollback is executed even if errors occur
     * 
     * @return bool True if rollback was successful or not needed, false on error
     */
    public function rollback(): bool
    {
        // Only rollback if in transaction
        if (!$this->pdo->inTransaction()) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Database::rollback() - Not in transaction, skipping");
            }
            return true; // Not an error if not in transaction
        }
        
        try {
            $result = $this->pdo->rollBack();
            if (!$result) {
                error_log("Database::rollback() - Rollback failed");
            }
            return $result;
        } catch (Exception $e) {
            error_log("Database::rollback() - Exception during rollback: " . $e->getMessage());
            // Try to rollback again if possible
            try {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            } catch (Exception $e2) {
                error_log("Database::rollback() - Second rollback attempt also failed: " . $e2->getMessage());
            }
            return false;
        } catch (Throwable $e) {
            error_log("Database::rollback() - Throwable during rollback: " . $e->getMessage());
            // Try to rollback again if possible
            try {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            } catch (Throwable $e2) {
                error_log("Database::rollback() - Second rollback attempt also failed: " . $e2->getMessage());
            }
            return false;
        }
    }
    
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
    
    /**
     * Execute a callback within a database transaction
     * Automatically commits on success, rolls back on exception
     * Ensures rollback is always attempted, even if rollback itself fails
     * 
     * @param callable $callback Function to execute within transaction
     * @return mixed Return value of the callback
     * @throws Exception If callback throws an exception, transaction is rolled back
     */
    public function transaction(callable $callback)
    {
        // Check if already in transaction
        if ($this->pdo->inTransaction()) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Database::transaction() - Already in transaction, executing callback directly");
            }
            return $callback();
        }
        
        $transactionStarted = false;
        try {
            $transactionStarted = $this->beginTransaction();
            if (!$transactionStarted) {
                throw new Exception("Failed to start database transaction");
            }
            
            $result = $callback();
            
            // Commit transaction
            if (!$this->commit()) {
                throw new Exception("Failed to commit database transaction");
            }
            
            return $result;
        } catch (Exception $e) {
            // Ensure rollback is attempted
            if ($transactionStarted && $this->pdo->inTransaction()) {
                $this->rollback();
            }
            throw $e;
        } catch (Throwable $e) {
            // Ensure rollback is attempted
            if ($transactionStarted && $this->pdo->inTransaction()) {
                $this->rollback();
            }
            throw new Exception($e->getMessage(), 0, $e);
        }
    }
    
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Ensure a column exists in a table, add it if it doesn't
     */
    private function ensureColumn(string $table, string $column, string $type): void
    {
        try {
            $columns = $this->pdo->query("PRAGMA table_info($table)")->fetchAll();
            $columnNames = array_map(function ($row) { return $row['name'] ?? null; }, $columns);
            
            if (!in_array($column, $columnNames, true)) {
                $this->pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $type");
            }
        } catch (Exception $e) {
            error_log("Error ensuring column $column in table $table: " . $e->getMessage());
        }
    }
    
    /**
     * Get column names for a table
     * ROUND 5 - STAGE 3: Helper method for checking table schema
     * 
     * @param string $table Table name
     * @return array Column names
     */
    public function getColumnNames(string $table): array
    {
        try {
            $columns = $this->pdo->query("PRAGMA table_info($table)")->fetchAll();
            return array_map(function ($row) { return $row['name'] ?? null; }, $columns);
        } catch (Exception $e) {
            error_log("Error getting column names for table $table: " . $e->getMessage());
            return [];
        }
    }
}
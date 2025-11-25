<?php
/**
 * Migration Manager
 * Database schema versioning and migration system
 */
class MigrationManager
{
    private static $migrationsDir = __DIR__ . '/../../db/migrations';
    private static $migrationsTable = 'schema_migrations';
    
    /**
     * Run pending migrations
     */
    public static function migrate(): array
    {
        $db = Database::getInstance();
        $pdo = $db->getPdo();
        
        // Create migrations table if not exists
        self::ensureMigrationsTable($pdo);
        
        // Get all migration files
        $migrations = self::getMigrationFiles();
        
        // Get executed migrations
        $executed = self::getExecutedMigrations($pdo);
        
        // Filter pending migrations
        $pending = array_filter($migrations, function($migration) use ($executed) {
            return !in_array($migration['name'], $executed);
        });
        
        if (empty($pending)) {
            return [
                'success' => true,
                'message' => 'No pending migrations',
                'executed' => 0
            ];
        }
        
        // Sort by timestamp
        usort($pending, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        $executedCount = 0;
        $errors = [];
        
        foreach ($pending as $migration) {
            try {
                $sql = file_get_contents($migration['path']);
                if ($sql === false) {
                    throw new RuntimeException('Unable to read migration file: ' . $migration['path']);
                }

                $useTransaction = stripos($sql, 'NO_TRANSACTION') === false && stripos($sql, 'PRAGMA foreign_keys=OFF') === false;

                if ($useTransaction) {
                    $pdo->beginTransaction();
                }

                // ROUND 6: Execute SQL statements individually to handle SQLite ALTER TABLE errors gracefully
                $statements = self::splitSqlStatements($sql);
                $executedStatements = 0;
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement) || strpos($statement, '--') === 0) {
                        continue; // Skip empty lines and comments
                    }
                    
                    try {
                        $pdo->exec($statement);
                        $executedStatements++;
                    } catch (PDOException $e) {
                        // ROUND 6: Handle SQLite "duplicate column" errors gracefully
                        // SQLite returns "duplicate column name" error when ALTER TABLE ADD COLUMN is run twice
                        $errorCode = $e->getCode();
                        $errorMessage = $e->getMessage();
                        
                        // SQLite error code for duplicate column: 1 (SQLITE_ERROR)
                        // Error message contains "duplicate column" or "already exists"
                        if (strpos($errorMessage, 'duplicate column') !== false || 
                            strpos($errorMessage, 'already exists') !== false ||
                            strpos($errorMessage, 'duplicate column name') !== false) {
                            // Column already exists, skip this statement (idempotent behavior)
                            if (class_exists('Logger')) {
                                Logger::info("Migration statement skipped (column already exists): " . substr($statement, 0, 50) . "...");
                            }
                            continue;
                        }
                        
                        // For other errors, re-throw
                        throw $e;
                    }
                }
                
                // Record migration only if at least one statement was executed
                if ($executedStatements > 0 || count($statements) === 0) {
                    $stmt = $pdo->prepare("INSERT INTO " . self::$migrationsTable . " (migration, executed_at) VALUES (?, ?)");
                    $stmt->execute([$migration['name'], date('Y-m-d H:i:s')]);
                }
                
                if ($useTransaction && $pdo->inTransaction()) {
                    $pdo->commit();
                }
                
                $executedCount++;
                
                if (class_exists('Logger')) {
                    Logger::info("Migration executed: {$migration['name']} ({$executedStatements} statements)");
                }
                
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                $errors[] = [
                    'migration' => $migration['name'],
                    'error' => $e->getMessage()
                ];
                
                if (class_exists('Logger')) {
                    Logger::error("Migration failed: {$migration['name']}", ['error' => $e->getMessage()]);
                }
                
                break; // Stop on first error
            } catch (Error $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                $errors[] = [
                    'migration' => $migration['name'],
                    'error' => $e->getMessage()
                ];
                
                if (class_exists('Logger')) {
                    Logger::error("Migration failed: {$migration['name']}", ['error' => $e->getMessage()]);
                }
                
                break;
            }
        }
        
        return [
            'success' => empty($errors),
            'executed' => $executedCount,
            'total_pending' => count($pending),
            'errors' => $errors
        ];
    }
    
    /**
     * Rollback last migration
     */
    public static function rollback(int $steps = 1): array
    {
        $db = Database::getInstance();
        $pdo = $db->getPdo();
        
        $executed = self::getExecutedMigrations($pdo);
        
        if (empty($executed)) {
            return [
                'success' => false,
                'message' => 'No migrations to rollback'
            ];
        }
        
        // Get last N migrations
        $toRollback = array_slice(array_reverse($executed), 0, $steps);
        
        $rolledBack = 0;
        $errors = [];
        
        foreach ($toRollback as $migrationName) {
            try {
                $migration = self::findMigration($migrationName);
                if (!$migration) {
                    continue;
                }
                
                $pdo->beginTransaction();
                
                // Check if migration has rollback SQL
                $rollbackPath = str_replace('.sql', '_rollback.sql', $migration['path']);
                if (file_exists($rollbackPath)) {
                    $sql = file_get_contents($rollbackPath);
                    $pdo->exec($sql);
                }
                
                // Remove from migrations table
                $stmt = $pdo->prepare("DELETE FROM " . self::$migrationsTable . " WHERE migration = ?");
                $stmt->execute([$migrationName]);
                
                $pdo->commit();
                $rolledBack++;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = [
                    'migration' => $migrationName,
                    'error' => $e->getMessage()
                ];
                break;
            }
        }
        
        return [
            'success' => empty($errors),
            'rolled_back' => $rolledBack,
            'errors' => $errors
        ];
    }
    
    /**
     * Get migration status
     */
    public static function status(): array
    {
        $db = Database::getInstance();
        $pdo = $db->getPdo();
        
        $all = self::getMigrationFiles();
        $executed = self::getExecutedMigrations($pdo);
        
        $status = [];
        foreach ($all as $migration) {
            $status[] = [
                'migration' => $migration['name'],
                'status' => in_array($migration['name'], $executed) ? 'executed' : 'pending',
                'executed_at' => in_array($migration['name'], $executed) ? 
                    self::getExecutedAt($pdo, $migration['name']) : null
            ];
        }
        
        return [
            'total' => count($all),
            'executed' => count($executed),
            'pending' => count($all) - count($executed),
            'migrations' => $status
        ];
    }
    
    /**
     * Get migration files
     */
    private static function getMigrationFiles(): array
    {
        if (!is_dir(self::$migrationsDir)) {
            mkdir(self::$migrationsDir, 0755, true);
            return [];
        }
        
        $files = glob(self::$migrationsDir . '/*.sql');
        $migrations = [];
        
        foreach ($files as $file) {
            if (strpos(basename($file), '_rollback.sql') !== false) {
                continue; // Skip rollback files
            }
            
            $migrations[] = [
                'name' => basename($file, '.sql'),
                'path' => $file
            ];
        }
        
        return $migrations;
    }
    
    /**
     * Ensure migrations table exists
     */
    private static function ensureMigrationsTable($pdo): void
    {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS " . self::$migrationsTable . " (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                executed_at TEXT NOT NULL
            )");
        } catch (Exception $e) {
            // Table might already exist, ignore
            error_log("Migration table creation warning: " . $e->getMessage());
        }
    }
    
    /**
     * Get executed migrations
     */
    private static function getExecutedMigrations($pdo): array
    {
        try {
            // Ensure table exists first
            self::ensureMigrationsTable($pdo);
            $stmt = $pdo->query("SELECT migration FROM " . self::$migrationsTable . " ORDER BY executed_at");
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Exception $e) {
            // Table doesn't exist yet or query failed
            return [];
        }
    }
    
    /**
     * Get execution timestamp
     */
    private static function getExecutedAt($pdo, string $migrationName): ?string
    {
        $stmt = $pdo->prepare("SELECT executed_at FROM " . self::$migrationsTable . " WHERE migration = ?");
        $stmt->execute([$migrationName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['executed_at'] : null;
    }
    
    /**
     * Find migration by name
     */
    private static function findMigration(string $name): ?array
    {
        $files = self::getMigrationFiles();
        foreach ($files as $file) {
            if ($file['name'] === $name) {
                return $file;
            }
        }
        return null;
    }
    
    /**
     * Split SQL file into individual statements
     * ROUND 6: Helper method to execute statements individually for better error handling
     * 
     * @param string $sql SQL content
     * @return array Array of SQL statements
     */
    private static function splitSqlStatements(string $sql): array
    {
        // Remove comments (-- style)
        $sql = preg_replace('/--.*$/m', '', $sql);
        
        // Split by semicolon, but preserve semicolons inside strings
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = null;
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            $nextChar = $i + 1 < strlen($sql) ? $sql[$i + 1] : null;
            
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($inString && $char === $stringChar && $nextChar !== $stringChar) {
                $inString = false;
                $stringChar = null;
                $current .= $char;
            } elseif (!$inString && $char === ';') {
                $statements[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        // Add remaining statement
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        // Filter out empty statements
        return array_filter($statements, function($stmt) {
            return !empty(trim($stmt));
        });
    }
}


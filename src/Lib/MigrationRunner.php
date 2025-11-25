<?php
/**
 * Database Migration Runner
 */

class MigrationRunner
{
    private $db;
    private $migrationsTable = 'migrations';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->createMigrationsTable();
    }
    
    private function createMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INTEGER NOT NULL,
            executed_at TEXT NOT NULL DEFAULT (datetime('now'))
        )";
        
        $this->db->query($sql);
    }
    
    public function run($migrationsPath = null)
    {
        if (!$migrationsPath) {
            $migrationsPath = __DIR__ . '/../../migrations';
        }
        
        if (!is_dir($migrationsPath)) {
            throw new Exception("Migrations directory not found: $migrationsPath");
        }
        
        $migrations = $this->getPendingMigrations($migrationsPath);
        
        if (empty($migrations)) {
            echo "No pending migrations found.\n";
            return;
        }
        
        $batch = $this->getNextBatch();
        
        foreach ($migrations as $migration) {
            echo "Running migration: {$migration['file']}\n";
            
            try {
                $this->db->beginTransaction();
                
                if (pathinfo($migration['file'], PATHINFO_EXTENSION) === 'sql') {
                    $this->runSqlMigration($migration['path']);
                } else {
                    $this->runPhpMigration($migration['path']);
                }
                
                $this->recordMigration($migration['file'], $batch);
                $this->db->commit();
                
                echo "✅ Migration completed: {$migration['file']}\n";
                
            } catch (Exception $e) {
                $this->db->rollback();
                echo "❌ Migration failed: {$migration['file']} - " . $e->getMessage() . "\n";
                throw $e;
            }
        }
        
        echo "All migrations completed successfully.\n";
    }
    
    private function getPendingMigrations($migrationsPath)
    {
        $files = glob($migrationsPath . '/*.{sql,php}', GLOB_BRACE);
        $executedMigrations = $this->getExecutedMigrations();
        
        $pending = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $executedMigrations)) {
                $pending[] = [
                    'file' => $filename,
                    'path' => $file
                ];
            }
        }
        
        // Sort by filename
        usort($pending, function($a, $b) {
            return strcmp($a['file'], $b['file']);
        });
        
        return $pending;
    }
    
    private function getExecutedMigrations()
    {
        $result = $this->db->fetchAll("SELECT migration FROM {$this->migrationsTable}");
        return array_column($result, 'migration');
    }
    
    private function getNextBatch()
    {
        $result = $this->db->fetch("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
        return ($result['max_batch'] ?? 0) + 1;
    }
    
    private function runSqlMigration($filePath)
    {
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new Exception("Could not read migration file: $filePath");
        }
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) { return !empty($stmt); }
        );
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->db->query($statement);
            }
        }
    }
    
    private function runPhpMigration($filePath)
    {
        require_once $filePath;
        
        // Extract class name from filename
        $className = pathinfo($filePath, PATHINFO_FILENAME);
        if (!class_exists($className)) {
            throw new Exception("Migration class not found: $className");
        }
        
        $migration = new $className();
        if (!method_exists($migration, 'up')) {
            throw new Exception("Migration class must have 'up' method: $className");
        }
        
        $migration->up();
    }
    
    private function recordMigration($filename, $batch)
    {
        $this->db->insert($this->migrationsTable, [
            'migration' => $filename,
            'batch' => $batch,
            'executed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function rollback($steps = 1)
    {
        $migrations = $this->db->fetchAll(
            "SELECT * FROM {$this->migrationsTable} ORDER BY batch DESC, id DESC LIMIT ?",
            [$steps]
        );
        
        foreach ($migrations as $migration) {
            echo "Rolling back migration: {$migration['migration']}\n";
            
            try {
                $this->db->beginTransaction();
                
                $migrationPath = __DIR__ . '/../../migrations/' . $migration['migration'];
                if (file_exists($migrationPath)) {
                    if (pathinfo($migrationPath, PATHINFO_EXTENSION) === 'sql') {
                        $this->runSqlRollback($migrationPath);
                    } else {
                        $this->runPhpRollback($migrationPath);
                    }
                }
                
                $this->db->delete($this->migrationsTable, 'id = ?', [$migration['id']]);
                $this->db->commit();
                
                echo "✅ Rollback completed: {$migration['migration']}\n";
                
            } catch (Exception $e) {
                $this->db->rollback();
                echo "❌ Rollback failed: {$migration['migration']} - " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }
    
    private function runSqlRollback($filePath)
    {
        // Look for rollback section in SQL file
        $sql = file_get_contents($filePath);
        if (preg_match('/-- ROLLBACK\s*\n(.*)/s', $sql, $matches)) {
            $rollbackSql = trim($matches[1]);
            $statements = array_filter(
                array_map('trim', explode(';', $rollbackSql)),
                function($stmt) { return !empty($stmt); }
            );
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->db->query($statement);
                }
            }
        }
    }
    
    private function runPhpRollback($filePath)
    {
        require_once $filePath;
        
        $className = pathinfo($filePath, PATHINFO_FILENAME);
        if (class_exists($className)) {
            $migration = new $className();
            if (method_exists($migration, 'down')) {
                $migration->down();
            }
        }
    }
    
    public function status()
    {
        $migrations = $this->db->fetchAll(
            "SELECT * FROM {$this->migrationsTable} ORDER BY batch, id"
        );
        
        echo "Migration Status:\n";
        echo "===============\n";
        
        if (empty($migrations)) {
            echo "No migrations executed.\n";
            return;
        }
        
        foreach ($migrations as $migration) {
            echo "✅ {$migration['migration']} (Batch: {$migration['batch']}, Executed: {$migration['executed_at']})\n";
        }
    }
}

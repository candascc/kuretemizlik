<?php
/**
 * Run only new contract-related migrations (034, 035, 036)
 * This script runs migrations directly, bypassing MigrationManager
 * to avoid issues with 033_recurring_occurrences_company
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/Lib/Database.php';

$db = Database::getInstance();
$pdo = $db->getPdo();

// Ensure migrations table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        migration TEXT NOT NULL UNIQUE,
        executed_at TEXT NOT NULL
    )");
} catch (Exception $e) {
    // Table might already exist
}

// Check which migrations are already executed
$executed = [];
try {
    $stmt = $pdo->query("SELECT migration FROM schema_migrations");
    $executed = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Exception $e) {
    // Table might not exist yet
}

$migrationsDir = __DIR__ . '/../db/migrations';
$newMigrations = [
    '034_contract_templates',
    '035_contract_otp_tokens',
    '036_job_contracts'
];

$executedCount = 0;
$errors = [];

foreach ($newMigrations as $migrationName) {
    if (in_array($migrationName, $executed)) {
        echo "Skipping {$migrationName} (already executed)" . PHP_EOL;
        continue;
    }
    
    $file = $migrationsDir . '/' . $migrationName . '.sql';
    if (!file_exists($file)) {
        echo "ERROR: Migration file not found: {$file}" . PHP_EOL;
        $errors[] = "File not found: {$migrationName}";
        continue;
    }
    
    echo "Running {$migrationName}..." . PHP_EOL;
    
    try {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('Unable to read migration file: ' . $file);
        }
        
        $pdo->beginTransaction();
        $pdo->exec($sql);
        
        // Record migration
        $stmt = $pdo->prepare("INSERT INTO schema_migrations (migration, executed_at) VALUES (?, ?)");
        $stmt->execute([$migrationName, date('Y-m-d H:i:s')]);
        
        $pdo->commit();
        $executedCount++;
        echo "✓ {$migrationName} completed successfully" . PHP_EOL;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "✗ ERROR in {$migrationName}: " . $e->getMessage() . PHP_EOL;
        $errors[] = [
            'migration' => $migrationName,
            'error' => $e->getMessage()
        ];
    }
}

echo PHP_EOL;
if (empty($errors)) {
    echo "SUCCESS: {$executedCount} migration(s) executed successfully" . PHP_EOL;
    exit(0);
} else {
    echo "FAILED: {$executedCount} migration(s) executed, " . count($errors) . " error(s)" . PHP_EOL;
    exit(1);
}


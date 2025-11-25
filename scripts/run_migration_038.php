<?php
/**
 * Run Migration 038 - Add service_key to contract_templates
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/Lib/Database.php';

$db = Database::getInstance();
$pdo = $db->getPdo();

$migrationFile = __DIR__ . '/../db/migrations/038_add_service_key_to_contract_templates.sql';

if (!file_exists($migrationFile)) {
    echo "ERROR: Migration file not found: {$migrationFile}\n";
    exit(1);
}

echo "Running migration 038...\n";

try {
    $sql = file_get_contents($migrationFile);
    if ($sql === false) {
        throw new RuntimeException('Unable to read migration file');
    }
    
    $pdo->beginTransaction();
    $pdo->exec($sql);
    $pdo->commit();
    
    echo "✓ Migration 038 completed successfully\n";
    exit(0);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}


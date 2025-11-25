<?php
/**
 * Run Building Management Migrations
 */

require_once __DIR__ . '/../../config/config.php';

$migrations = [
    '005_buildings_core.sql',
    '006_management_fees.sql',
    '007_building_expenses.sql',
    '008_documents_meetings.sql',
    '009_surveys_announcements.sql',
    '010_residents_portal.sql'
];

$db = Database::getInstance();
$basePathCandidates = [
    __DIR__ . '/../../database/migrations/',
    __DIR__ . '/../../db/migrations/',
    __DIR__ . '/../../migrations/'
];

foreach ($basePathCandidates as $candidate) {
    if (is_dir($candidate)) {
        $basePath = rtrim($candidate, '/\\') . '/';
        break;
    }
}

if (empty($basePath)) {
    echo "No migrations directory found. Checked:\n";
    foreach ($basePathCandidates as $c) { echo " - $c\n"; }
    exit(1);
}

echo "Running building management migrations...\n\n";

foreach ($migrations as $migration) {
    $file = $basePath . $migration;
    
    if (!file_exists($file)) {
        echo "✗ $migration (file not found)\n";
        continue;
    }

    try {
        $sql = file_get_contents($file);
        
        // PDO üzerinden exec kullan
        $pdo = $db->getPdo();
        
        // SQLite exec için her statement'ı ayrı çalıştır
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || preg_match('/^--/', $statement) || preg_match('/^PRAGMA/', $statement)) {
                continue;
            }
            
            try {
                $pdo->exec($statement . ';');
            } catch (PDOException $e) {
                // Index zaten varsa ignore et
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, 'already exists') === false && 
                    strpos($errorMsg, 'duplicate') === false &&
                    strpos($errorMsg, 'SQLSTATE') === false) {
                    echo "  Warning in $migration: " . $errorMsg . "\n";
                }
            }
        }
        echo "✓ $migration\n";
    } catch (Exception $e) {
        echo "✗ $migration - Error: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration process completed.\n";


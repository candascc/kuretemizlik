<?php
/**
 * Fix email tables - create missing ones
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Lib/Database.php';

$db = Database::getInstance();

echo "Fixing email tables...\n\n";

// Read migration file
$sqlFile = __DIR__ . '/../../db/migrations/004_email_queue.sql';
$sql = file_get_contents($sqlFile);

// Split SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));
$created = 0;

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    try {
        $db->query($statement);
        if (strpos($statement, 'CREATE TABLE') !== false || strpos($statement, 'CREATE INDEX') !== false) {
            $created++;
        }
    } catch (Exception $ex) {
        // Ignore "already exists" errors
        if (strpos($ex->getMessage(), 'already exists') === false && 
            strpos($ex->getMessage(), 'duplicate') === false) {
            echo "  Warning: " . $ex->getMessage() . "\n";
        }
    }
}

echo "✓ Executed $created statements\n\n";

// Verify tables
try {
    $queueCount = $db->fetch("SELECT COUNT(*) as count FROM email_queue")['count'];
    echo "✓ email_queue table exists (count: $queueCount)\n";
} catch (Exception $e) {
    echo "✗ email_queue table still missing: " . $e->getMessage() . "\n";
}

try {
    $logsCount = $db->fetch("SELECT COUNT(*) as count FROM email_logs")['count'];
    echo "✓ email_logs table exists (count: $logsCount)\n";
} catch (Exception $e) {
    echo "✗ email_logs table still missing: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";


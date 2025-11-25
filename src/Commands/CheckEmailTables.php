<?php
/**
 * Check and create email tables if missing
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Lib/Database.php';

$db = Database::getInstance();

echo "Checking email tables...\n\n";

// Check email_queue table
try {
    $db->query("SELECT 1 FROM email_queue LIMIT 1");
    $queueCount = $db->fetch("SELECT COUNT(*) as count FROM email_queue")['count'];
    echo "✓ email_queue table exists (count: $queueCount)\n";
} catch (Exception $e) {
    echo "✗ email_queue table does not exist. Creating...\n";
    $sql = file_get_contents(__DIR__ . '/../../db/migrations/004_email_queue.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->query($statement);
            } catch (Exception $ex) {
                echo "  Warning: " . $ex->getMessage() . "\n";
            }
        }
    }
    echo "✓ email_queue table created\n";
}

// Check email_logs table
try {
    $db->query("SELECT 1 FROM email_logs LIMIT 1");
    $logsCount = $db->fetch("SELECT COUNT(*) as count FROM email_logs")['count'];
    echo "✓ email_logs table exists (count: $logsCount)\n";
    
    if ($logsCount > 0) {
        $recent = $db->fetchAll("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 5");
        echo "\nRecent logs:\n";
        foreach ($recent as $log) {
            echo "  - ID: {$log['id']}, To: {$log['to_email']}, Status: {$log['status']}, Type: {$log['type']}\n";
        }
    }
} catch (Exception $e) {
    echo "✗ email_logs table does not exist. Creating...\n";
    $sql = file_get_contents(__DIR__ . '/../../db/migrations/004_email_queue.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->query($statement);
            } catch (Exception $ex) {
                echo "  Warning: " . $ex->getMessage() . "\n";
            }
        }
    }
    echo "✓ email_logs table created\n";
}

echo "\nDone!\n";


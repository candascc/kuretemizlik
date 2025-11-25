<?php
/**
 * Migration Runner Script
 * EXECUTION PHASE - STAGE 1
 * 
 * This script runs pending migrations and validates schema changes.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define APP_ROOT
define('APP_ROOT', __DIR__);

// Load config to define DB_PATH and other constants
require_once __DIR__ . '/config/config.php';

// Load required classes
require_once __DIR__ . '/src/Lib/Database.php';
require_once __DIR__ . '/src/Lib/MigrationManager.php';

echo "========================================\n";
echo "MIGRATION RUNNER - EXECUTION PHASE\n";
echo "========================================\n\n";

// Check database file
$dbPath = DB_PATH;
echo "Database Path: {$dbPath}\n";
echo "Database Exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . "\n";
echo "Database Writable: " . (is_writable($dbPath) ? 'YES' : 'NO') . "\n\n";

if (!file_exists($dbPath)) {
    echo "WARNING: Database file does not exist. It will be created on first connection.\n\n";
}

// Check migration status
echo "--- Migration Status (Before) ---\n";
try {
    $status = MigrationManager::status();
    echo "Total Migrations: {$status['total']}\n";
    echo "Executed: {$status['executed']}\n";
    echo "Pending: {$status['pending']}\n\n";
    
    if ($status['pending'] > 0) {
        echo "Pending Migrations:\n";
        foreach ($status['migrations'] as $migration) {
            if ($migration['status'] === 'pending') {
                echo "  - {$migration['migration']}\n";
            }
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "ERROR: Could not get migration status: " . $e->getMessage() . "\n";
    exit(1);
}

// Run migrations
echo "--- Running Migrations ---\n";
try {
    $result = MigrationManager::migrate();
    
    if ($result['success']) {
        echo "SUCCESS: Migrations completed successfully!\n";
        echo "Executed: {$result['executed']} migration(s)\n";
        echo "Total Pending: {$result['total_pending']}\n\n";
    } else {
        echo "ERROR: Migration failed!\n";
        echo "Executed: {$result['executed']} migration(s) before failure\n";
        if (!empty($result['errors'])) {
            echo "Errors:\n";
            foreach ($result['errors'] as $error) {
                echo "  - {$error['migration']}: {$error['error']}\n";
            }
        }
        echo "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Check migration status (after)
echo "--- Migration Status (After) ---\n";
try {
    $status = MigrationManager::status();
    echo "Total Migrations: {$status['total']}\n";
    echo "Executed: {$status['executed']}\n";
    echo "Pending: {$status['pending']}\n\n";
} catch (Exception $e) {
    echo "WARNING: Could not get migration status: " . $e->getMessage() . "\n";
}

// Schema validation
echo "--- Schema Validation ---\n";
try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    
    // Check 040: staff.company_id
    $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('staff') WHERE name = 'company_id'");
    $staffCompanyId = $stmt->fetchColumn();
    echo "staff.company_id: " . ($staffCompanyId > 0 ? 'EXISTS' : 'MISSING') . "\n";
    
    // Check 040: appointments.company_id
    $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('appointments') WHERE name = 'company_id'");
    $appointmentsCompanyId = $stmt->fetchColumn();
    echo "appointments.company_id: " . ($appointmentsCompanyId > 0 ? 'EXISTS' : 'MISSING') . "\n";
    
    // Check 041: management_fees unique index
    $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_index_list('management_fees') WHERE name = 'idx_management_fees_unique_unit_period_fee'");
    $managementFeesIndex = $stmt->fetchColumn();
    echo "management_fees.idx_management_fees_unique_unit_period_fee: " . ($managementFeesIndex > 0 ? 'EXISTS' : 'MISSING') . "\n";
    
    // Check 042: activity_log.ip_address
    $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('activity_log') WHERE name = 'ip_address'");
    $activityLogIp = $stmt->fetchColumn();
    echo "activity_log.ip_address: " . ($activityLogIp > 0 ? 'EXISTS' : 'MISSING') . "\n";
    
    // Check 042: activity_log.user_agent
    $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('activity_log') WHERE name = 'user_agent'");
    $activityLogUserAgent = $stmt->fetchColumn();
    echo "activity_log.user_agent: " . ($activityLogUserAgent > 0 ? 'EXISTS' : 'MISSING') . "\n";
    
    // Check 042: activity_log.company_id
    $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('activity_log') WHERE name = 'company_id'");
    $activityLogCompanyId = $stmt->fetchColumn();
    echo "activity_log.company_id: " . ($activityLogCompanyId > 0 ? 'EXISTS' : 'MISSING') . "\n";
    
    echo "\n";
    
    // Summary
    $allOk = ($staffCompanyId > 0 && $appointmentsCompanyId > 0 && $managementFeesIndex > 0 && 
              $activityLogIp > 0 && $activityLogUserAgent > 0 && $activityLogCompanyId > 0);
    
    if ($allOk) {
        echo "✅ All expected columns and indexes are present!\n";
    } else {
        echo "⚠️  Some columns or indexes are missing. Please check the migration logs.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: Schema validation failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n========================================\n";
echo "MIGRATION RUNNER COMPLETED\n";
echo "========================================\n";


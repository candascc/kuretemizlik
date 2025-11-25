<?php
/**
 * Schema Validation Script
 * EXECUTION PHASE - STAGE 1
 * 
 * Validates that all expected columns and indexes from migrations 040-042 exist.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define APP_ROOT
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}

// Load config to define DB_PATH and other constants
require_once __DIR__ . '/config/config.php';

// Load required classes
require_once __DIR__ . '/src/Lib/Database.php';

echo "========================================\n";
echo "SCHEMA VALIDATION - EXECUTION PHASE\n";
echo "========================================\n\n";

// Check database file
$dbPath = DB_PATH;
echo "Database Path: {$dbPath}\n";
echo "Database Exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . "\n\n";

if (!file_exists($dbPath)) {
    echo "ERROR: Database file does not exist!\n";
    exit(1);
}

// Schema validation
echo "--- Schema Validation (Migrations 040-042) ---\n\n";
try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    
    $results = [];
    
    // Check 040: staff.company_id
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('staff') WHERE name = 'company_id'");
        $staffCompanyId = $stmt->fetchColumn();
        $results['staff.company_id'] = $staffCompanyId > 0;
        echo "✓ staff.company_id: " . ($staffCompanyId > 0 ? 'EXISTS' : 'MISSING') . "\n";
    } catch (Exception $e) {
        $results['staff.company_id'] = false;
        echo "✗ staff.company_id: ERROR - " . $e->getMessage() . "\n";
    }
    
    // Check 040: appointments.company_id
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('appointments') WHERE name = 'company_id'");
        $appointmentsCompanyId = $stmt->fetchColumn();
        $results['appointments.company_id'] = $appointmentsCompanyId > 0;
        echo "✓ appointments.company_id: " . ($appointmentsCompanyId > 0 ? 'EXISTS' : 'MISSING') . "\n";
    } catch (Exception $e) {
        $results['appointments.company_id'] = false;
        echo "✗ appointments.company_id: ERROR - " . $e->getMessage() . "\n";
    }
    
    // Check 041: management_fees unique index
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_index_list('management_fees') WHERE name = 'idx_management_fees_unique_unit_period_fee'");
        $managementFeesIndex = $stmt->fetchColumn();
        $results['management_fees.idx_management_fees_unique_unit_period_fee'] = $managementFeesIndex > 0;
        echo "✓ management_fees.idx_management_fees_unique_unit_period_fee: " . ($managementFeesIndex > 0 ? 'EXISTS' : 'MISSING') . "\n";
    } catch (Exception $e) {
        $results['management_fees.idx_management_fees_unique_unit_period_fee'] = false;
        echo "✗ management_fees.idx_management_fees_unique_unit_period_fee: ERROR - " . $e->getMessage() . "\n";
    }
    
    // Check 042: activity_log.ip_address
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('activity_log') WHERE name = 'ip_address'");
        $activityLogIp = $stmt->fetchColumn();
        $results['activity_log.ip_address'] = $activityLogIp > 0;
        echo "✓ activity_log.ip_address: " . ($activityLogIp > 0 ? 'EXISTS' : 'MISSING') . "\n";
    } catch (Exception $e) {
        $results['activity_log.ip_address'] = false;
        echo "✗ activity_log.ip_address: ERROR - " . $e->getMessage() . "\n";
    }
    
    // Check 042: activity_log.user_agent
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('activity_log') WHERE name = 'user_agent'");
        $activityLogUserAgent = $stmt->fetchColumn();
        $results['activity_log.user_agent'] = $activityLogUserAgent > 0;
        echo "✓ activity_log.user_agent: " . ($activityLogUserAgent > 0 ? 'EXISTS' : 'MISSING') . "\n";
    } catch (Exception $e) {
        $results['activity_log.user_agent'] = false;
        echo "✗ activity_log.user_agent: ERROR - " . $e->getMessage() . "\n";
    }
    
    // Check 042: activity_log.company_id
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM pragma_table_info('activity_log') WHERE name = 'company_id'");
        $activityLogCompanyId = $stmt->fetchColumn();
        $results['activity_log.company_id'] = $activityLogCompanyId > 0;
        echo "✓ activity_log.company_id: " . ($activityLogCompanyId > 0 ? 'EXISTS' : 'MISSING') . "\n";
    } catch (Exception $e) {
        $results['activity_log.company_id'] = false;
        echo "✗ activity_log.company_id: ERROR - " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Summary
    $allOk = !in_array(false, $results, true);
    $missing = array_filter($results, function($v) { return !$v; });
    
    if ($allOk) {
        echo "✅ All expected columns and indexes are present!\n";
        echo "Migration 040, 041, 042: VALIDATED\n";
    } else {
        echo "⚠️  Some columns or indexes are missing:\n";
        foreach ($missing as $key => $value) {
            echo "  - {$key}\n";
        }
    }
    
} catch (Exception $e) {
    echo "FATAL ERROR: Schema validation failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n========================================\n";
echo "SCHEMA VALIDATION COMPLETED\n";
echo "========================================\n";


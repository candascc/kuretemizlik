<?php
/**
 * Verify contract-related tables were created correctly
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/Lib/Database.php';

$db = Database::getInstance();
$pdo = $db->getPdo();

$tables = [
    'contract_templates',
    'job_contracts',
    'contract_otp_tokens'
];

$requiredColumns = [
    'contract_templates' => ['id', 'type', 'name', 'version', 'template_text', 'is_active', 'is_default'],
    'job_contracts' => ['id', 'job_id', 'template_id', 'status', 'approval_method', 'approved_at', 'contract_text', 'sms_sent_count'],
    'contract_otp_tokens' => ['id', 'job_contract_id', 'customer_id', 'token', 'phone', 'expires_at', 'verified_at', 'attempts']
];

echo "Verifying contract-related tables..." . PHP_EOL . PHP_EOL;

$allOk = true;

foreach ($tables as $table) {
    echo "Checking table: {$table}" . PHP_EOL;
    
    // Check if table exists
    try {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
        $exists = $stmt->fetch() !== false;
        
        if (!$exists) {
            echo "  ✗ Table does not exist!" . PHP_EOL;
            $allOk = false;
            continue;
        }
        
        echo "  ✓ Table exists" . PHP_EOL;
        
        // Check columns
        $stmt = $pdo->query("PRAGMA table_info({$table})");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');
        
        $missingColumns = [];
        if (isset($requiredColumns[$table])) {
            foreach ($requiredColumns[$table] as $requiredCol) {
                if (!in_array($requiredCol, $columnNames)) {
                    $missingColumns[] = $requiredCol;
                }
            }
        }
        
        if (!empty($missingColumns)) {
            echo "  ✗ Missing columns: " . implode(', ', $missingColumns) . PHP_EOL;
            $allOk = false;
        } else {
            echo "  ✓ All required columns present" . PHP_EOL;
        }
        
        // Check indexes
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='{$table}' AND name NOT LIKE 'sqlite_autoindex_%'");
        $indexes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "  ℹ Indexes: " . (count($indexes) > 0 ? implode(', ', $indexes) : 'none') . PHP_EOL;
        
    } catch (Exception $e) {
        echo "  ✗ Error checking table: " . $e->getMessage() . PHP_EOL;
        $allOk = false;
    }
    
    echo PHP_EOL;
}

if ($allOk) {
    echo "SUCCESS: All tables verified successfully!" . PHP_EOL;
    exit(0);
} else {
    echo "FAILED: Some tables or columns are missing!" . PHP_EOL;
    exit(1);
}


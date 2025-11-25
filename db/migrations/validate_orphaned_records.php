<?php
/**
 * Migration Validation: Orphaned Records Detection
 * 
 * Detects orphaned records that would prevent FK constraint enforcement:
 * - Jobs without valid customer/service/address
 * - Money entries without valid job/user
 * - Activity logs without valid user
 * - Addresses without valid customer
 * 
 * Self-Audit Fix: Validation for HIGH-006 fix (FK cascade)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';

class OrphanedRecordsValidator
{
    private $db;
    private $issues = [];
    private $warnings = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Run all validation checks
     */
    public function validate()
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║      ORPHANED RECORDS VALIDATION - Pre-Migration Check   ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Checking database integrity before FK cascade migration...\n";
        echo "\n";
        
        $this->checkOrphanedJobs();
        $this->checkOrphanedMoneyEntries();
        $this->checkOrphanedActivityLogs();
        $this->checkOrphanedAddresses();
        $this->checkOrphanedManagementFees();
        $this->checkOrphanedPayments();
        
        $this->printReport();
        
        return empty($this->issues);
    }
    
    /**
     * Check orphaned jobs
     */
    private function checkOrphanedJobs()
    {
        echo "1. Checking Jobs table...\n";
        echo "   ─────────────────────────\n";
        
        // Check jobs without valid customer
        $orphanedCustomers = $this->db->fetchAll("
            SELECT j.id, j.customer_id, j.start_at
            FROM jobs j
            WHERE j.customer_id IS NOT NULL 
              AND j.customer_id NOT IN (SELECT id FROM customers)
        ");
        
        if (!empty($orphanedCustomers)) {
            $count = count($orphanedCustomers);
            $this->addIssue('jobs', "customer_id", $count, 
                "Jobs referencing deleted customers", $orphanedCustomers);
            echo "   ❌ Found {$count} jobs with invalid customer_id\n";
        } else {
            echo "   ✅ No orphaned customer references\n";
        }
        
        // Check jobs without valid service (optional FK)
        $orphanedServices = $this->db->fetchAll("
            SELECT j.id, j.service_id, j.start_at
            FROM jobs j
            WHERE j.service_id IS NOT NULL 
              AND j.service_id NOT IN (SELECT id FROM services)
        ");
        
        if (!empty($orphanedServices)) {
            $count = count($orphanedServices);
            $this->addWarning('jobs', "service_id", $count, 
                "Jobs referencing deleted services (will be set to NULL)", $orphanedServices);
            echo "   ⚠️  Found {$count} jobs with invalid service_id (non-critical)\n";
        } else {
            echo "   ✅ No orphaned service references\n";
        }
        
        // Check jobs without valid address (optional FK)
        $orphanedAddresses = $this->db->fetchAll("
            SELECT j.id, j.address_id, j.start_at
            FROM jobs j
            WHERE j.address_id IS NOT NULL 
              AND j.address_id NOT IN (SELECT id FROM addresses)
        ");
        
        if (!empty($orphanedAddresses)) {
            $count = count($orphanedAddresses);
            $this->addWarning('jobs', "address_id", $count, 
                "Jobs referencing deleted addresses (will be set to NULL)", $orphanedAddresses);
            echo "   ⚠️  Found {$count} jobs with invalid address_id (non-critical)\n";
        } else {
            echo "   ✅ No orphaned address references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Check orphaned money_entries
     */
    private function checkOrphanedMoneyEntries()
    {
        echo "2. Checking Money Entries table...\n";
        echo "   ───────────────────────────────\n";
        
        // Check money_entries without valid job (optional FK)
        $orphanedJobs = $this->db->fetchAll("
            SELECT me.id, me.job_id, me.amount, me.created_at
            FROM money_entries me
            WHERE me.job_id IS NOT NULL 
              AND me.job_id NOT IN (SELECT id FROM jobs)
        ");
        
        if (!empty($orphanedJobs)) {
            $count = count($orphanedJobs);
            $this->addWarning('money_entries', "job_id", $count, 
                "Money entries referencing deleted jobs (will be set to NULL)", $orphanedJobs);
            echo "   ⚠️  Found {$count} money entries with invalid job_id\n";
        } else {
            echo "   ✅ No orphaned job references\n";
        }
        
        // Check money_entries without valid created_by user (optional FK)
        $orphanedUsers = $this->db->fetchAll("
            SELECT me.id, me.created_by, me.amount, me.created_at
            FROM money_entries me
            WHERE me.created_by IS NOT NULL 
              AND me.created_by NOT IN (SELECT id FROM users)
        ");
        
        if (!empty($orphanedUsers)) {
            $count = count($orphanedUsers);
            $this->addWarning('money_entries', "created_by", $count, 
                "Money entries referencing deleted users (will be set to NULL)", $orphanedUsers);
            echo "   ⚠️  Found {$count} money entries with invalid created_by\n";
        } else {
            echo "   ✅ No orphaned user references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Check orphaned activity_log
     */
    private function checkOrphanedActivityLogs()
    {
        echo "3. Checking Activity Log table...\n";
        echo "   ──────────────────────────────\n";
        
        // Check activity_log without valid actor_id (optional FK)
        $orphanedActors = $this->db->fetchAll("
            SELECT al.id, al.actor_id, al.action, al.created_at
            FROM activity_log al
            WHERE al.actor_id IS NOT NULL 
              AND al.actor_id NOT IN (SELECT id FROM users)
        ");
        
        if (!empty($orphanedActors)) {
            $count = count($orphanedActors);
            $this->addWarning('activity_log', "actor_id", $count, 
                "Activity logs referencing deleted users (will be set to NULL)", $orphanedActors);
            echo "   ⚠️  Found {$count} activity logs with invalid actor_id\n";
        } else {
            echo "   ✅ No orphaned actor references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Check orphaned addresses
     */
    private function checkOrphanedAddresses()
    {
        echo "4. Checking Addresses table...\n";
        echo "   ───────────────────────────\n";
        
        // Check addresses without valid customer
        $orphanedCustomers = $this->db->fetchAll("
            SELECT a.id, a.customer_id, a.line
            FROM addresses a
            WHERE a.customer_id IS NOT NULL 
              AND a.customer_id NOT IN (SELECT id FROM customers)
        ");
        
        if (!empty($orphanedCustomers)) {
            $count = count($orphanedCustomers);
            $this->addIssue('addresses', "customer_id", $count, 
                "Addresses referencing deleted customers", $orphanedCustomers);
            echo "   ❌ Found {$count} addresses with invalid customer_id\n";
        } else {
            echo "   ✅ No orphaned customer references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Check orphaned management_fees
     */
    private function checkOrphanedManagementFees()
    {
        echo "5. Checking Management Fees table...\n";
        echo "   ─────────────────────────────────\n";
        
        // Check fees without valid building
        $orphanedBuildings = $this->db->fetchAll("
            SELECT mf.id, mf.building_id, mf.total_amount
            FROM management_fees mf
            WHERE mf.building_id IS NOT NULL 
              AND mf.building_id NOT IN (SELECT id FROM buildings)
        ");
        
        if (!empty($orphanedBuildings)) {
            $count = count($orphanedBuildings);
            $this->addIssue('management_fees', "building_id", $count, 
                "Fees referencing deleted buildings", $orphanedBuildings);
            echo "   ❌ Found {$count} fees with invalid building_id\n";
        } else {
            echo "   ✅ No orphaned building references\n";
        }
        
        // Check fees without valid unit
        $orphanedUnits = $this->db->fetchAll("
            SELECT mf.id, mf.unit_id, mf.total_amount
            FROM management_fees mf
            WHERE mf.unit_id IS NOT NULL 
              AND mf.unit_id NOT IN (SELECT id FROM units)
        ");
        
        if (!empty($orphanedUnits)) {
            $count = count($orphanedUnits);
            $this->addIssue('management_fees', "unit_id", $count, 
                "Fees referencing deleted units", $orphanedUnits);
            echo "   ❌ Found {$count} fees with invalid unit_id\n";
        } else {
            echo "   ✅ No orphaned unit references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Check orphaned online_payments
     */
    private function checkOrphanedPayments()
    {
        echo "6. Checking Online Payments table...\n";
        echo "   ─────────────────────────────────\n";
        
        // Check payments without valid management_fee
        $orphanedFees = $this->db->fetchAll("
            SELECT op.id, op.management_fee_id, op.amount
            FROM online_payments op
            WHERE op.management_fee_id IS NOT NULL 
              AND op.management_fee_id NOT IN (SELECT id FROM management_fees)
        ");
        
        if (!empty($orphanedFees)) {
            $count = count($orphanedFees);
            $this->addIssue('online_payments', "management_fee_id", $count, 
                "Payments referencing deleted fees", $orphanedFees);
            echo "   ❌ Found {$count} payments with invalid management_fee_id\n";
        } else {
            echo "   ✅ No orphaned fee references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Add critical issue
     */
    private function addIssue($table, $column, $count, $description, $records)
    {
        $this->issues[] = [
            'table' => $table,
            'column' => $column,
            'count' => $count,
            'description' => $description,
            'records' => array_slice($records, 0, 5) // First 5 records
        ];
    }
    
    /**
     * Add warning
     */
    private function addWarning($table, $column, $count, $description, $records)
    {
        $this->warnings[] = [
            'table' => $table,
            'column' => $column,
            'count' => $count,
            'description' => $description,
            'records' => array_slice($records, 0, 5) // First 5 records
        ];
    }
    
    /**
     * Print validation report
     */
    private function printReport()
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║                    VALIDATION REPORT                      ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        // Critical issues
        if (!empty($this->issues)) {
            echo "❌ CRITICAL ISSUES FOUND\n";
            echo "═══════════════════════════\n\n";
            
            foreach ($this->issues as $issue) {
                echo "Table: {$issue['table']}\n";
                echo "Column: {$issue['column']}\n";
                echo "Count: {$issue['count']} orphaned records\n";
                echo "Description: {$issue['description']}\n";
                echo "Sample records (first 5):\n";
                foreach ($issue['records'] as $record) {
                    echo "  - ID: {$record['id']}\n";
                }
                echo "\n";
            }
            
            echo "ACTION REQUIRED:\n";
            echo "  These orphaned records MUST be cleaned up before FK migration.\n";
            echo "  Run cleanup script: php cleanup_orphaned_records.php\n";
            echo "\n";
        } else {
            echo "✅ NO CRITICAL ISSUES\n";
            echo "═════════════════════\n";
            echo "  All critical FK references are valid.\n";
            echo "\n";
        }
        
        // Warnings
        if (!empty($this->warnings)) {
            echo "⚠️  WARNINGS (Non-blocking)\n";
            echo "═══════════════════════════\n\n";
            
            foreach ($this->warnings as $warning) {
                echo "Table: {$warning['table']}\n";
                echo "Column: {$warning['column']}\n";
                echo "Count: {$warning['count']} orphaned records\n";
                echo "Description: {$warning['description']}\n";
                echo "\n";
            }
            
            echo "RECOMMENDATION:\n";
            echo "  These will be handled by SET NULL FK actions.\n";
            echo "  Optional: Clean up manually for better data quality.\n";
            echo "\n";
        } else {
            echo "✅ NO WARNINGS\n";
            echo "═════════════════\n";
            echo "  All optional FK references are valid.\n";
            echo "\n";
        }
        
        // Summary
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        
        if (empty($this->issues)) {
            echo "║              ✅ MIGRATION SAFE TO PROCEED ✅              ║\n";
        } else {
            echo "║           ❌ CLEANUP REQUIRED BEFORE MIGRATION ❌         ║\n";
        }
        
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        echo "Summary:\n";
        echo "  Critical Issues: " . count($this->issues) . "\n";
        echo "  Warnings: " . count($this->warnings) . "\n";
        echo "  Safe to migrate: " . (empty($this->issues) ? "YES ✅" : "NO ❌") . "\n";
        echo "\n";
        
        // Generate JSON report
        $this->generateJsonReport();
    }
    
    /**
     * Generate JSON report
     */
    private function generateJsonReport()
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'safe_to_migrate' => empty($this->issues),
            'critical_issues' => $this->issues,
            'warnings' => $this->warnings,
            'summary' => [
                'critical_count' => count($this->issues),
                'warning_count' => count($this->warnings)
            ]
        ];
        
        $jsonPath = __DIR__ . '/../../orphaned_records_report.json';
        file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT));
        
        echo "JSON Report: {$jsonPath}\n";
        echo "\n";
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli') {
    $validator = new OrphanedRecordsValidator();
    $safe = $validator->validate();
    exit($safe ? 0 : 1);
}


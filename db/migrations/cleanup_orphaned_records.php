<?php
/**
 * Orphaned Records Cleanup Script
 * 
 * Cleans up orphaned records before FK cascade migration
 * 
 * Self-Audit Fix: Cleanup script for HIGH-006 fix (FK cascade)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';

class OrphanedRecordsCleanup
{
    private $db;
    private $cleanedRecords = [];
    private $dryRun;
    
    public function __construct($dryRun = true)
    {
        $this->db = Database::getInstance();
        $this->dryRun = $dryRun;
    }
    
    /**
     * Run cleanup
     */
    public function cleanup()
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║           ORPHANED RECORDS CLEANUP SCRIPT                 ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        if ($this->dryRun) {
            echo "⚠️  DRY RUN MODE - No changes will be made\n";
            echo "   Run with --execute flag to perform actual cleanup\n\n";
        } else {
            echo "⚠️  EXECUTE MODE - Changes will be permanent\n";
            echo "   Backing up database before cleanup...\n\n";
            $this->backupDatabase();
        }
        
        echo "Starting cleanup...\n\n";
        
        $this->cleanupOrphanedJobs();
        $this->cleanupOrphanedAddresses();
        $this->cleanupOrphanedManagementFees();
        $this->cleanupOrphanedPayments();
        $this->cleanupOrphanedMoneyEntries();
        $this->cleanupOrphanedActivityLogs();
        
        $this->printSummary();
        
        return true;
    }
    
    /**
     * Backup database
     */
    private function backupDatabase()
    {
        $dbPath = __DIR__ . '/../app.sqlite';
        $backupPath = __DIR__ . '/../backups/pre-cleanup-' . date('Ymd-His') . '.sqlite';
        
        if (file_exists($dbPath)) {
            copy($dbPath, $backupPath);
            echo "   ✅ Database backed up to: {$backupPath}\n\n";
        }
    }
    
    /**
     * Cleanup orphaned jobs
     */
    private function cleanupOrphanedJobs()
    {
        echo "1. Cleaning up Jobs table...\n";
        echo "   ─────────────────────────\n";
        
        // Delete jobs with invalid customer_id (CASCADE FK will handle this)
        $orphanedCustomers = $this->db->fetchAll("
            SELECT j.id FROM jobs j
            WHERE j.customer_id IS NOT NULL 
              AND j.customer_id NOT IN (SELECT id FROM customers)
        ");
        
        if (!empty($orphanedCustomers)) {
            $count = count($orphanedCustomers);
            if (!$this->dryRun) {
                foreach ($orphanedCustomers as $job) {
                    $this->db->delete('jobs', 'id = ?', [$job['id']]);
                }
            }
            $this->cleanedRecords['jobs_customer'] = $count;
            echo "   " . ($this->dryRun ? "Would delete" : "Deleted") . " {$count} jobs with invalid customer_id\n";
        } else {
            echo "   ✅ No orphaned customer references\n";
        }
        
        // Set NULL for jobs with invalid service_id (SET NULL FK)
        $orphanedServices = $this->db->fetchAll("
            SELECT j.id FROM jobs j
            WHERE j.service_id IS NOT NULL 
              AND j.service_id NOT IN (SELECT id FROM services)
        ");
        
        if (!empty($orphanedServices)) {
            $count = count($orphanedServices);
            if (!$this->dryRun) {
                foreach ($orphanedServices as $job) {
                    $this->db->update('jobs', ['service_id' => null], 'id = ?', [$job['id']]);
                }
            }
            $this->cleanedRecords['jobs_service'] = $count;
            echo "   " . ($this->dryRun ? "Would set NULL" : "Set NULL") . " for {$count} jobs with invalid service_id\n";
        } else {
            echo "   ✅ No orphaned service references\n";
        }
        
        // Set NULL for jobs with invalid address_id (SET NULL FK)
        $orphanedAddresses = $this->db->fetchAll("
            SELECT j.id FROM jobs j
            WHERE j.address_id IS NOT NULL 
              AND j.address_id NOT IN (SELECT id FROM addresses)
        ");
        
        if (!empty($orphanedAddresses)) {
            $count = count($orphanedAddresses);
            if (!$this->dryRun) {
                foreach ($orphanedAddresses as $job) {
                    $this->db->update('jobs', ['address_id' => null], 'id = ?', [$job['id']]);
                }
            }
            $this->cleanedRecords['jobs_address'] = $count;
            echo "   " . ($this->dryRun ? "Would set NULL" : "Set NULL") . " for {$count} jobs with invalid address_id\n";
        } else {
            echo "   ✅ No orphaned address references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Cleanup orphaned addresses
     */
    private function cleanupOrphanedAddresses()
    {
        echo "2. Cleaning up Addresses table...\n";
        echo "   ───────────────────────────────\n";
        
        $orphanedCustomers = $this->db->fetchAll("
            SELECT a.id FROM addresses a
            WHERE a.customer_id IS NOT NULL 
              AND a.customer_id NOT IN (SELECT id FROM customers)
        ");
        
        if (!empty($orphanedCustomers)) {
            $count = count($orphanedCustomers);
            if (!$this->dryRun) {
                foreach ($orphanedCustomers as $address) {
                    // Check if any jobs reference this address
                    $jobCount = $this->db->fetch(
                        "SELECT COUNT(*) as c FROM jobs WHERE address_id = ?",
                        [$address['id']]
                    )['c'];
                    
                    if ($jobCount == 0) {
                        $this->db->delete('addresses', 'id = ?', [$address['id']]);
                    } else {
                        // Keep address but mark as deleted if column exists
                        try {
                            $this->db->update('addresses', ['is_deleted' => 1], 'id = ?', [$address['id']]);
                        } catch (Exception $e) {
                            // is_deleted column doesn't exist, just leave it
                        }
                    }
                }
            }
            $this->cleanedRecords['addresses'] = $count;
            echo "   " . ($this->dryRun ? "Would clean" : "Cleaned") . " {$count} addresses with invalid customer_id\n";
        } else {
            echo "   ✅ No orphaned customer references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Cleanup orphaned management_fees
     */
    private function cleanupOrphanedManagementFees()
    {
        echo "3. Cleaning up Management Fees table...\n";
        echo "   ─────────────────────────────────────\n";
        
        // Delete fees with invalid building_id
        $orphanedBuildings = $this->db->fetchAll("
            SELECT mf.id FROM management_fees mf
            WHERE mf.building_id IS NOT NULL 
              AND mf.building_id NOT IN (SELECT id FROM buildings)
        ");
        
        if (!empty($orphanedBuildings)) {
            $count = count($orphanedBuildings);
            if (!$this->dryRun) {
                foreach ($orphanedBuildings as $fee) {
                    $this->db->delete('management_fees', 'id = ?', [$fee['id']]);
                }
            }
            $this->cleanedRecords['fees_building'] = $count;
            echo "   " . ($this->dryRun ? "Would delete" : "Deleted") . " {$count} fees with invalid building_id\n";
        } else {
            echo "   ✅ No orphaned building references\n";
        }
        
        // Delete fees with invalid unit_id
        $orphanedUnits = $this->db->fetchAll("
            SELECT mf.id FROM management_fees mf
            WHERE mf.unit_id IS NOT NULL 
              AND mf.unit_id NOT IN (SELECT id FROM units)
        ");
        
        if (!empty($orphanedUnits)) {
            $count = count($orphanedUnits);
            if (!$this->dryRun) {
                foreach ($orphanedUnits as $fee) {
                    $this->db->delete('management_fees', 'id = ?', [$fee['id']]);
                }
            }
            $this->cleanedRecords['fees_unit'] = $count;
            echo "   " . ($this->dryRun ? "Would delete" : "Deleted") . " {$count} fees with invalid unit_id\n";
        } else {
            echo "   ✅ No orphaned unit references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Cleanup orphaned payments
     */
    private function cleanupOrphanedPayments()
    {
        echo "4. Cleaning up Online Payments table...\n";
        echo "   ─────────────────────────────────────\n";
        
        $orphanedFees = $this->db->fetchAll("
            SELECT op.id FROM online_payments op
            WHERE op.management_fee_id IS NOT NULL 
              AND op.management_fee_id NOT IN (SELECT id FROM management_fees)
        ");
        
        if (!empty($orphanedFees)) {
            $count = count($orphanedFees);
            if (!$this->dryRun) {
                foreach ($orphanedFees as $payment) {
                    $this->db->delete('online_payments', 'id = ?', [$payment['id']]);
                }
            }
            $this->cleanedRecords['payments'] = $count;
            echo "   " . ($this->dryRun ? "Would delete" : "Deleted") . " {$count} payments with invalid management_fee_id\n";
        } else {
            echo "   ✅ No orphaned fee references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Cleanup orphaned money_entries
     */
    private function cleanupOrphanedMoneyEntries()
    {
        echo "5. Cleaning up Money Entries table...\n";
        echo "   ───────────────────────────────────\n";
        
        // Set NULL for invalid job_id
        $orphanedJobs = $this->db->fetchAll("
            SELECT me.id FROM money_entries me
            WHERE me.job_id IS NOT NULL 
              AND me.job_id NOT IN (SELECT id FROM jobs)
        ");
        
        if (!empty($orphanedJobs)) {
            $count = count($orphanedJobs);
            if (!$this->dryRun) {
                foreach ($orphanedJobs as $entry) {
                    $this->db->update('money_entries', ['job_id' => null], 'id = ?', [$entry['id']]);
                }
            }
            $this->cleanedRecords['money_job'] = $count;
            echo "   " . ($this->dryRun ? "Would set NULL" : "Set NULL") . " for {$count} entries with invalid job_id\n";
        } else {
            echo "   ✅ No orphaned job references\n";
        }
        
        // Set NULL for invalid created_by
        $orphanedUsers = $this->db->fetchAll("
            SELECT me.id FROM money_entries me
            WHERE me.created_by IS NOT NULL 
              AND me.created_by NOT IN (SELECT id FROM users)
        ");
        
        if (!empty($orphanedUsers)) {
            $count = count($orphanedUsers);
            if (!$this->dryRun) {
                foreach ($orphanedUsers as $entry) {
                    $this->db->update('money_entries', ['created_by' => null], 'id = ?', [$entry['id']]);
                }
            }
            $this->cleanedRecords['money_user'] = $count;
            echo "   " . ($this->dryRun ? "Would set NULL" : "Set NULL") . " for {$count} entries with invalid created_by\n";
        } else {
            echo "   ✅ No orphaned user references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Cleanup orphaned activity_log
     */
    private function cleanupOrphanedActivityLogs()
    {
        echo "6. Cleaning up Activity Log table...\n";
        echo "   ──────────────────────────────────\n";
        
        $orphanedActors = $this->db->fetchAll("
            SELECT al.id FROM activity_log al
            WHERE al.actor_id IS NOT NULL 
              AND al.actor_id NOT IN (SELECT id FROM users)
        ");
        
        if (!empty($orphanedActors)) {
            $count = count($orphanedActors);
            if (!$this->dryRun) {
                foreach ($orphanedActors as $log) {
                    $this->db->update('activity_log', ['actor_id' => null], 'id = ?', [$log['id']]);
                }
            }
            $this->cleanedRecords['activity_log'] = $count;
            echo "   " . ($this->dryRun ? "Would set NULL" : "Set NULL") . " for {$count} logs with invalid actor_id\n";
        } else {
            echo "   ✅ No orphaned actor references\n";
        }
        
        echo "\n";
    }
    
    /**
     * Print summary
     */
    private function printSummary()
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║                    CLEANUP SUMMARY                        ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        if (empty($this->cleanedRecords)) {
            echo "✅ No orphaned records found - database is clean!\n";
        } else {
            echo "Cleaned records:\n";
            echo "────────────────\n";
            $total = 0;
            foreach ($this->cleanedRecords as $table => $count) {
                echo "  {$table}: {$count}\n";
                $total += $count;
            }
            echo "\n";
            echo "Total: {$total} records " . ($this->dryRun ? "would be cleaned" : "cleaned") . "\n";
        }
        
        echo "\n";
        
        if ($this->dryRun) {
            echo "╔═══════════════════════════════════════════════════════════╗\n";
            echo "║               DRY RUN COMPLETE - NO CHANGES               ║\n";
            echo "╚═══════════════════════════════════════════════════════════╝\n";
            echo "\n";
            echo "To execute cleanup, run:\n";
            echo "  php cleanup_orphaned_records.php --execute\n";
        } else {
            echo "╔═══════════════════════════════════════════════════════════╗\n";
            echo "║                  CLEANUP COMPLETE ✅                      ║\n";
            echo "╚═══════════════════════════════════════════════════════════╝\n";
            echo "\n";
            echo "Next steps:\n";
            echo "  1. Run validation: php validate_orphaned_records.php\n";
            echo "  2. If validation passes, run FK migration\n";
        }
        
        echo "\n";
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli') {
    $dryRun = !in_array('--execute', $argv);
    $cleanup = new OrphanedRecordsCleanup($dryRun);
    $cleanup->cleanup();
}


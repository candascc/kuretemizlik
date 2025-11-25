<?php
/**
 * Migration: 033_recurring_occurrences_company
 * Adds company_id column to recurring_job_occurrences table
 * 
 * This migration is in PHP format because SQLite doesn't support 
 * IF NOT EXISTS for ALTER TABLE ADD COLUMN
 */

function migrate_033_recurring_occurrences_company($pdo) {
    try {
        // Check if company_id column already exists
        $columns = $pdo->query("PRAGMA table_info(recurring_job_occurrences)")->fetchAll(PDO::FETCH_ASSOC);
        $hasCompanyId = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'company_id') {
                $hasCompanyId = true;
                break;
            }
        }
        
        if (!$hasCompanyId) {
            // Add company_id column
            $pdo->exec("ALTER TABLE recurring_job_occurrences ADD COLUMN company_id INTEGER NOT NULL DEFAULT 1");
            
            // Update existing records: set company_id from recurring_jobs table
            $pdo->exec("
                UPDATE recurring_job_occurrences 
                SET company_id = (
                    SELECT company_id 
                    FROM recurring_jobs 
                    WHERE recurring_jobs.id = recurring_job_occurrences.recurring_job_id
                )
                WHERE company_id IS NULL OR company_id = 0
            ");
            
            // Create index
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_occurrences_company_id ON recurring_job_occurrences(company_id)");
            
            // Update view to include company_id
            $pdo->exec("DROP VIEW IF EXISTS v_recurring_job_occurrences");
            $pdo->exec("CREATE VIEW IF NOT EXISTS v_recurring_job_occurrences AS
                SELECT 
                  id,
                  recurring_job_id,
                  date AS scheduled_date,
                  start_at AS scheduled_start_at,
                  end_at AS scheduled_end_at,
                  date, 
                  start_at, 
                  end_at, 
                  status, 
                  job_id,
                  company_id,
                  created_at, 
                  updated_at
                FROM recurring_job_occurrences");
            
            return true;
        } else {
            // Column already exists, migration already applied
            return true;
        }
    } catch (Exception $e) {
        error_log("Migration 033_recurring_occurrences_company failed: " . $e->getMessage());
        throw $e;
    }
}



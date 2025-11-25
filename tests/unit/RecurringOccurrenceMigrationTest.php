<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Recurring Occurrence Migration Test Suite
 * Tests for company_id column migration
 */
class RecurringOccurrenceMigrationTest extends TestCase
{
    private $pdo;
    private $testDbPath;
    
    protected function setUp(): void
    {
        // Create temporary test database
        $this->testDbPath = sys_get_temp_dir() . '/test_recurring_' . time() . '.sqlite';
        $this->pdo = new PDO('sqlite:' . $this->testDbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create base tables
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS companies (id INTEGER PRIMARY KEY, name TEXT)");
        $this->pdo->exec("INSERT INTO companies (id, name) VALUES (1, 'Test Company')");
        
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS recurring_jobs (
            id INTEGER PRIMARY KEY,
            customer_id INTEGER NOT NULL,
            company_id INTEGER NOT NULL DEFAULT 1,
            start_date TEXT NOT NULL
        )");
        
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS recurring_job_occurrences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recurring_job_id INTEGER NOT NULL,
            date TEXT NOT NULL,
            start_at TEXT NOT NULL,
            end_at TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'PLANNED',
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            job_id INTEGER,
            FOREIGN KEY(recurring_job_id) REFERENCES recurring_jobs(id) ON DELETE CASCADE
        )");
        
        // Insert test data
        $this->pdo->exec("INSERT INTO recurring_jobs (id, customer_id, company_id, start_date) VALUES (1, 1, 1, '2025-01-01')");
        $this->pdo->exec("INSERT INTO recurring_job_occurrences (recurring_job_id, date, start_at, end_at, status) VALUES (1, '2025-01-01', '2025-01-01 09:00', '2025-01-01 10:00', 'PLANNED')");
    }
    
    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->pdo = null;
        }
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
    }
    
    /**
     * Test that migration adds company_id column
     */
    public function testMigrationAddsCompanyIdColumn(): void
    {
        // Run migration
        require_once __DIR__ . '/../../db/migrations/033_recurring_occurrences_company.php';
        migrate_033_recurring_occurrences_company($this->pdo);
        
        // Check if column exists
        $columns = $this->pdo->query("PRAGMA table_info(recurring_job_occurrences)")->fetchAll(PDO::FETCH_ASSOC);
        $hasCompanyId = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'company_id') {
                $hasCompanyId = true;
                $this->assertEquals('INTEGER', $column['type']);
                break;
            }
        }
        
        $this->assertTrue($hasCompanyId, 'company_id column should exist after migration');
    }
    
    /**
     * Test that existing records get company_id from recurring_jobs
     */
    public function testMigrationPopulatesCompanyId(): void
    {
        // Run migration
        require_once __DIR__ . '/../../db/migrations/033_recurring_occurrences_company.php';
        migrate_033_recurring_occurrences_company($this->pdo);
        
        // Check that existing record has company_id
        $occurrence = $this->pdo->query("SELECT company_id FROM recurring_job_occurrences WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, $occurrence['company_id']);
    }
    
    /**
     * Test that migration is idempotent
     */
    public function testMigrationIsIdempotent(): void
    {
        // Run migration twice
        require_once __DIR__ . '/../../db/migrations/033_recurring_occurrences_company.php';
        migrate_033_recurring_occurrences_company($this->pdo);
        migrate_033_recurring_occurrences_company($this->pdo);
        
        // Should not throw error
        $this->assertTrue(true);
    }
}



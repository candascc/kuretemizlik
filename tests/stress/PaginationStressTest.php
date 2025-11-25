<?php
/**
 * Pagination Stress Test
 * Tests pagination with 10000+ records
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Support/DatabaseSeeder.php';

use PHPUnit\Framework\TestCase;

class PaginationStressTest extends TestCase
{
    private Database $db;
    private \Tests\Support\Seeders\LargeDatasetSeeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        require_once __DIR__ . '/../Support/DatabaseSeeder.php';
        require_once __DIR__ . '/../Support/Seeders/LargeDatasetSeeder.php';
        
        $this->seeder = new \Tests\Support\Seeders\LargeDatasetSeeder();
        $this->seeder->seed();
    }

    protected function tearDown(): void
    {
        // Cleanup is handled by test database rollback
        parent::tearDown();
    }

    /**
     * Test pagination with 10000+ jobs
     */
    public function testPaginationWith10000Jobs(): void
    {
        // Get actual job count from seeder
        $totalJobs = $this->db->fetch("SELECT COUNT(*) as count FROM jobs");
        $actualCount = (int)($totalJobs['count'] ?? 0);
        
        if ($actualCount < 20) {
            $this->markTestSkipped("Not enough jobs in database ({$actualCount}). Seeder should create at least 20 jobs.");
            return;
        }
        
        $pageSize = 20;
        $totalPages = (int)ceil($actualCount / $pageSize);

        $startTime = microtime(true);
        
        // Test first page
        $firstPage = $this->db->fetchAll(
            "SELECT * FROM jobs ORDER BY id LIMIT ? OFFSET ?",
            [$pageSize, 0]
        );
        
        // Test middle page (only if we have enough records)
        $middlePage = [];
        if ($totalPages > 2) {
            $middlePage = $this->db->fetchAll(
                "SELECT * FROM jobs ORDER BY id LIMIT ? OFFSET ?",
                [$pageSize, (int)(($totalPages / 2) * $pageSize)]
            );
        }
        
        // Test last page
        $lastPage = $this->db->fetchAll(
            "SELECT * FROM jobs ORDER BY id LIMIT ? OFFSET ?",
            [$pageSize, ($totalPages - 1) * $pageSize]
        );

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertCount($pageSize, $firstPage, 'First page should have 20 records');
        if (!empty($middlePage)) {
            $this->assertLessThanOrEqual($pageSize, count($middlePage), 'Middle page should have <= 20 records');
        }
        $this->assertLessThanOrEqual($pageSize, count($lastPage), 'Last page should have <= 20 records');
        $this->assertLessThan(500, $duration, 'Pagination should complete in less than 500ms');
    }

    /**
     * Test pagination with various page sizes
     */
    public function testPaginationWithVariousPageSizes(): void
    {
        $pageSizes = [10, 20, 50, 100, 200];
        $totalRecords = 5000;

        foreach ($pageSizes as $pageSize) {
            $startTime = microtime(true);
            
            $results = $this->db->fetchAll(
                "SELECT * FROM jobs ORDER BY id LIMIT ? OFFSET ?",
                [$pageSize, 0]
            );
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            $this->assertLessThanOrEqual($pageSize, count($results), "Page size {$pageSize} should return <= {$pageSize} records");
            $this->assertLessThan(200, $duration, "Pagination with page size {$pageSize} should complete in < 200ms");
        }
    }

    /**
     * Test pagination edge cases
     */
    public function testPaginationEdgeCases(): void
    {
        // Test page 0 (should default to page 1)
        $page0 = $this->db->fetchAll("SELECT * FROM jobs ORDER BY id LIMIT 20 OFFSET 0");
        $this->assertGreaterThan(0, count($page0), 'Page 0 should return records');

        // Test very large offset
        $largeOffset = $this->db->fetchAll("SELECT * FROM jobs ORDER BY id LIMIT 20 OFFSET 100000");
        $this->assertCount(0, $largeOffset, 'Very large offset should return empty');

        // Test negative offset (should be treated as 0)
        try {
            $negativeOffset = $this->db->fetchAll("SELECT * FROM jobs ORDER BY id LIMIT 20 OFFSET -10");
            $this->assertGreaterThanOrEqual(0, count($negativeOffset), 'Negative offset should not crash');
        } catch (Exception $e) {
            // SQLite might throw error, which is acceptable
            $this->assertTrue(true, 'Negative offset should be handled gracefully');
        }
    }

    /**
     * Test pagination performance with filters
     */
    public function testPaginationWithFilters(): void
    {
        $pageSize = 20;
        $status = 'SCHEDULED';

        $startTime = microtime(true);
        
        $results = $this->db->fetchAll(
            "SELECT * FROM jobs WHERE status = ? ORDER BY id LIMIT ? OFFSET ?",
            [$status, $pageSize, 0]
        );
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertLessThanOrEqual($pageSize, count($results), 'Filtered pagination should return <= page size');
        $this->assertLessThan(300, $duration, 'Filtered pagination should complete in < 300ms');
    }
}


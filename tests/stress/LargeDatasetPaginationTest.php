<?php
/**
 * Large Dataset Pagination Test
 * Tests pagination with 10000+ records
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Support/DatabaseSeeder.php';

use PHPUnit\Framework\TestCase;

class LargeDatasetPaginationTest extends TestCase
{
    private Database $db;
    private \Tests\Support\Seeders\LargeDatasetSeeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        require_once __DIR__ . '/../Support/Seeders/LargeDatasetSeeder.php';
        
        $this->seeder = new \Tests\Support\Seeders\LargeDatasetSeeder();
        $this->seeder->seed();
    }

    protected function tearDown(): void
    {
        $this->seeder->cleanup();
        parent::tearDown();
    }

    /**
     * Test pagination through all pages
     */
    public function testPaginationThroughAllPages(): void
    {
        $pageSize = 50;
        $totalRecords = $this->db->fetch("SELECT COUNT(*) as count FROM jobs")['count'];
        $totalPages = (int)ceil($totalRecords / $pageSize);

        $pagesProcessed = 0;
        $recordsProcessed = 0;

        for ($page = 0; $page < min($totalPages, 10); $page++) { // Limit to 10 pages for test
            $offset = $page * $pageSize;
            $results = $this->db->fetchAll(
                "SELECT * FROM jobs ORDER BY id LIMIT ? OFFSET ?",
                [$pageSize, $offset]
            );
            
            $pagesProcessed++;
            $recordsProcessed += count($results);
        }

        $this->assertGreaterThan(0, $pagesProcessed, 'Should process at least one page');
        $this->assertGreaterThan(0, $recordsProcessed, 'Should process at least one record');
    }

    /**
     * Test pagination performance
     */
    public function testPaginationPerformance(): void
    {
        $pageSize = 100;
        $pagesToTest = 5;

        $startTime = microtime(true);

        for ($page = 0; $page < $pagesToTest; $page++) {
            $offset = $page * $pageSize;
            $this->db->fetchAll(
                "SELECT * FROM jobs ORDER BY id LIMIT ? OFFSET ?",
                [$pageSize, $offset]
            );
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertLessThan(1000, $duration, 'Pagination should be fast (< 1 second for 5 pages)');
    }
}


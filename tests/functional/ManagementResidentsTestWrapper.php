<?php
/**
 * PHPUnit Wrapper for ManagementResidentsTest
 * 
 * Wraps the standalone ManagementResidentsTest to make it compatible with PHPUnit
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Support/FactoryRegistry.php';
require_once __DIR__ . '/ManagementResidentsTest.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

final class ManagementResidentsTestWrapper extends TestCase
{
    private $standaloneTest;
    
    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::getInstance();
        FactoryRegistry::setDatabase($db);
        $this->standaloneTest = new ManagementResidentsTest();
    }
    
    protected function tearDown(): void
    {
        if ($this->standaloneTest) {
            try {
                $reflection = new ReflectionClass($this->standaloneTest);
                $cleanupMethod = $reflection->getMethod('cleanup');
                $cleanupMethod->setAccessible(true);
                $cleanupMethod->invoke($this->standaloneTest);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
        parent::tearDown();
    }
    
    public function testAllManagementResidentsTests(): void
    {
        $result = $this->standaloneTest->runAll();
        $this->assertTrue($result, 'All management residents tests should pass');
    }
}


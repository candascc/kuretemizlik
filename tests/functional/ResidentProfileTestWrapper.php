<?php
/**
 * PHPUnit Wrapper for ResidentProfileTest
 * 
 * Wraps the standalone ResidentProfileTest to make it compatible with PHPUnit
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Support/FactoryRegistry.php';
require_once __DIR__ . '/ResidentProfileTest.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

final class ResidentProfileTestWrapper extends TestCase
{
    private $standaloneTest;
    
    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::getInstance();
        FactoryRegistry::setDatabase($db);
        $this->standaloneTest = new ResidentProfileTest();
    }
    
    protected function tearDown(): void
    {
        if ($this->standaloneTest) {
            try {
                $reflection = new ReflectionClass($this->standaloneTest);
                if ($reflection->hasMethod('cleanup')) {
                    $cleanupMethod = $reflection->getMethod('cleanup');
                    $cleanupMethod->setAccessible(true);
                    $cleanupMethod->invoke($this->standaloneTest);
                }
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
        parent::tearDown();
    }
    
    public function testAllResidentProfileTests(): void
    {
        $result = $this->standaloneTest->runAll();
        $this->assertTrue($result, 'All resident profile tests should pass');
    }
}


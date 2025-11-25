<?php
/**
 * PHPUnit Wrapper for JobContractFlowTest
 * 
 * Wraps the standalone JobContractFlowTest to make it compatible with PHPUnit
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/JobContractFlowTest.php';

use PHPUnit\Framework\TestCase;

final class JobContractFlowTestWrapper extends TestCase
{
    private $standaloneTest;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->standaloneTest = new JobContractFlowTest();
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
    
    public function testAllJobContractFlowTests(): void
    {
        $results = $this->standaloneTest->run();
        $this->assertGreaterThan(0, $results['passed'], 'At least some job contract flow tests should pass');
        $this->assertEquals(0, $results['failed'], 'All job contract flow tests should pass');
    }
}











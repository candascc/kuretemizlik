<?php
/**
 * PHPUnit Wrapper for ContractTemplateSelectionTest
 * 
 * Wraps the standalone ContractTemplateSelectionTest to make it compatible with PHPUnit
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/ContractTemplateSelectionTest.php';

use PHPUnit\Framework\TestCase;

final class ContractTemplateSelectionTestWrapper extends TestCase
{
    private $standaloneTest;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->standaloneTest = new ContractTemplateSelectionTest();
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
    
    public function testAllContractTemplateSelectionTests(): void
    {
        $results = $this->standaloneTest->run();
        $this->assertGreaterThan(0, $results['passed'], 'At least some contract template selection tests should pass');
        $this->assertEquals(0, $results['failed'], 'All contract template selection tests should pass');
    }
}











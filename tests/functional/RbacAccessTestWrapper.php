<?php
/**
 * PHPUnit Wrapper for RbacAccessTest
 * 
 * Wraps the standalone RbacAccessTest to make it compatible with PHPUnit
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/RbacAccessTest.php';
require_once __DIR__ . '/../Support/ControllerMockHelper.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\ControllerMockHelper;
use Tests\Support\FactoryRegistry;

final class RbacAccessTestWrapper extends TestCase
{
    private Database $db;
    private $standaloneTest;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        FactoryRegistry::setDatabase($this->db);
        
        // Ensure clean session state
        ControllerMockHelper::cleanupSession();
        ControllerMockHelper::cleanupRequestGlobals();
        
        $this->standaloneTest = new RbacAccessTest();
    }
    
    protected function tearDown(): void
    {
        // Cleanup
        ControllerMockHelper::cleanupSession();
        ControllerMockHelper::cleanupRequestGlobals();
        
        parent::tearDown();
    }
    
    public function testRbacAccessControl(): void
    {
        try {
            // Setup proper session and request state
            ControllerMockHelper::setupSession();
            ControllerMockHelper::setupRequestGlobals([
                'method' => 'GET',
                'data' => [],
            ]);
            
            // Run the standalone test directly
            // The test class has a run() method or can be executed directly
            $reflection = new ReflectionClass($this->standaloneTest);
            
            // Use runAll() method
            $method = $reflection->getMethod('runAll');
            $method->setAccessible(true);
            
            // Capture output to prevent test output pollution
            ob_start();
            $results = $method->invoke($this->standaloneTest);
            $output = ob_get_clean();
            
            // Check if all tests passed
            $allPassed = count(array_filter($results)) === count($results);
            
            $this->assertTrue($allPassed, 'RBAC access control tests should pass. Output: ' . substr($output, 0, 500) . ' Results: ' . json_encode($results));
        } catch (Exception $e) {
            $this->fail('Test execution failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        } catch (Throwable $e) {
            $this->fail('Test execution failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}


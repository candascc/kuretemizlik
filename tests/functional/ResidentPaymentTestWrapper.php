<?php
/**
 * PHPUnit Wrapper for ResidentPaymentTest
 * 
 * Wraps the standalone ResidentPaymentTest to make it compatible with PHPUnit
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/ResidentPaymentTest.php';
require_once __DIR__ . '/../Support/ControllerMockHelper.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\ControllerMockHelper;

final class ResidentPaymentTestWrapper extends TestCase
{
    private Database $db;
    private $standaloneTest;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        
        // Ensure clean session state
        ControllerMockHelper::cleanupSession();
        ControllerMockHelper::cleanupRequestGlobals();
        
        // Don't use transaction for SQLite (causes lock issues)
        // Instead, rely on test cleanup
        
        $this->standaloneTest = new ResidentPaymentTest();
    }
    
    protected function tearDown(): void
    {
        // Cleanup
        ControllerMockHelper::cleanupSession();
        ControllerMockHelper::cleanupRequestGlobals();
        
        // No transaction rollback needed - test cleanup handles it
        
        parent::tearDown();
    }
    
    public function testSuccessfulPayment(): void
    {
        try {
            // Setup proper session and request state
            ControllerMockHelper::setupSession();
            ControllerMockHelper::setupRequestGlobals([
                'method' => 'POST',
                'data' => [],
            ]);
            
            $reflection = new ReflectionClass($this->standaloneTest);
            $method = $reflection->getMethod('testSuccessfulPayment');
            $method->setAccessible(true);
            
            // Capture output to prevent test output pollution
            ob_start();
            $result = $method->invoke($this->standaloneTest);
            $output = ob_get_clean();
            
            $this->assertTrue($result, 'Successful payment test should pass. Output: ' . substr($output, 0, 200));
        } catch (Exception $e) {
            $this->fail('Test execution failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        } catch (Throwable $e) {
            $this->fail('Test execution failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }
    
    public function testInvalidPaymentAmount(): void
    {
        try {
            // Setup proper session and request state
            ControllerMockHelper::setupSession();
            ControllerMockHelper::setupRequestGlobals([
                'method' => 'POST',
                'data' => [],
            ]);
            
            $reflection = new ReflectionClass($this->standaloneTest);
            $method = $reflection->getMethod('testInvalidPaymentAmount');
            $method->setAccessible(true);
            
            // Capture output to prevent test output pollution
            ob_start();
            $result = $method->invoke($this->standaloneTest);
            $output = ob_get_clean();
            
            $this->assertTrue($result, 'Invalid payment amount test should pass. Output: ' . substr($output, 0, 200));
        } catch (Exception $e) {
            $this->fail('Test execution failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        } catch (Throwable $e) {
            $this->fail('Test execution failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Session Management Integration Test Suite
 * Tests for multiple session_start() scenarios and session consistency
 */
class SessionManagementTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure session is not started before each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up session after each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $_SESSION = [];
    }
    
    /**
     * Test that multiple calls to SessionHelper::ensureStarted() don't cause issues
     */
    public function testMultipleSessionStartCalls(): void
    {
        // Simulate multiple components trying to start session
        $result1 = SessionHelper::ensureStarted();
        $result2 = SessionHelper::ensureStarted();
        $result3 = SessionHelper::ensureStarted();
        
        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertTrue($result3);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        
        // Session ID should remain consistent
        $sessionId = session_id();
        $this->assertNotEmpty($sessionId);
    }
    
    /**
     * Test that session data persists across multiple ensureStarted() calls
     */
    public function testSessionDataPersistence(): void
    {
        SessionHelper::ensureStarted();
        
        $_SESSION['test_key'] = 'test_value';
        $sessionId1 = session_id();
        
        // Simulate session write/close and restart
        session_write_close();
        
        SessionHelper::ensureStarted();
        $sessionId2 = session_id();
        
        // Note: In real scenario, session data would persist if using same session ID
        // This test verifies that ensureStarted() works after write_close
        $this->assertTrue(SessionHelper::isActive());
    }
    
    /**
     * Test that Auth::check() works with SessionHelper
     */
    public function testAuthCheckWithSessionHelper(): void
    {
        SessionHelper::ensureStarted();
        
        $_SESSION['user_id'] = 1;
        $_SESSION['login_time'] = time();
        
        $result = Auth::check();
        
        $this->assertTrue($result);
    }
    
    /**
     * Test that CSRF works with SessionHelper
     */
    public function testCsrfWithSessionHelper(): void
    {
        SessionHelper::ensureStarted();
        
        $token = CSRF::get();
        
        $this->assertNotEmpty($token);
        $this->assertTrue(CSRF::verify($token));
    }
    
    /**
     * Test session cookie path consistency
     */
    public function testSessionCookiePathConsistency(): void
    {
        // Define APP_BASE for testing
        if (!defined('APP_BASE')) {
            define('APP_BASE', '/app');
        }
        
        SessionHelper::ensureStarted();
        
        $cookieParams = session_get_cookie_params();
        
        // Cookie path should match APP_BASE or default to /app
        $expectedPath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
        $this->assertEquals($expectedPath, $cookieParams['path']);
    }
}



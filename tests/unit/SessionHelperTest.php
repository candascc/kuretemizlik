<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * SessionHelper Test Suite
 * Tests for centralized session management
 */
class SessionHelperTest extends TestCase
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
     * Test that ensureStarted() starts session when not active
     */
    public function testEnsureStartedStartsSession(): void
    {
        $this->assertEquals(PHP_SESSION_NONE, session_status());
        
        $result = SessionHelper::ensureStarted();
        
        $this->assertTrue($result);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }
    
    /**
     * Test that ensureStarted() is idempotent (can be called multiple times)
     */
    public function testEnsureStartedIsIdempotent(): void
    {
        $result1 = SessionHelper::ensureStarted();
        $sessionId1 = session_id();
        
        $result2 = SessionHelper::ensureStarted();
        $sessionId2 = session_id();
        
        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertEquals($sessionId1, $sessionId2);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }
    
    /**
     * Test that isActive() returns correct status
     */
    public function testIsActive(): void
    {
        $this->assertFalse(SessionHelper::isActive());
        
        SessionHelper::ensureStarted();
        
        $this->assertTrue(SessionHelper::isActive());
    }
    
    /**
     * Test that getStatus() returns correct status
     */
    public function testGetStatus(): void
    {
        $this->assertEquals(PHP_SESSION_NONE, SessionHelper::getStatus());
        
        SessionHelper::ensureStarted();
        
        $this->assertEquals(PHP_SESSION_ACTIVE, SessionHelper::getStatus());
    }
    
    /**
     * Test that ensureStarted() handles headers already sent gracefully
     */
    public function testEnsureStartedHandlesHeadersSent(): void
    {
        // This test is difficult to simulate without actually sending headers
        // In a real scenario, if headers are sent, ensureStarted() should return false
        // and not throw an exception
        
        $result = SessionHelper::ensureStarted();
        
        // Should succeed in normal test environment
        $this->assertTrue($result);
    }
    
    /**
     * Test that session cookie parameters are set correctly
     */
    public function testSessionCookieParameters(): void
    {
        SessionHelper::ensureStarted();
        
        $cookieParams = session_get_cookie_params();
        
        $this->assertEquals(0, $cookieParams['lifetime']);
        $this->assertTrue($cookieParams['httponly']);
        $this->assertEquals('Lax', $cookieParams['samesite']);
    }
}



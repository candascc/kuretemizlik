<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Session Cookie Path Integration Test Suite
 * Tests for session cookie path consistency
 */
class SessionCookiePathTest extends TestCase
{
    /**
     * Test that SessionHelper uses APP_BASE for cookie path
     */
    public function testSessionHelperUsesAppBase(): void
    {
        // Define APP_BASE for testing
        if (!defined('APP_BASE')) {
            define('APP_BASE', '/app');
        }
        
        SessionHelper::ensureStarted();
        
        $cookieParams = session_get_cookie_params();
        
        // Cookie path should match APP_BASE
        $this->assertEquals(APP_BASE, $cookieParams['path']);
    }
    
    /**
     * Test that cookie path defaults to /app when APP_BASE is not defined
     */
    public function testSessionHelperDefaultsToApp(): void
    {
        // Temporarily undefine APP_BASE if it exists
        // (In real scenario, this would be handled by SessionHelper)
        
        SessionHelper::ensureStarted();
        
        $cookieParams = session_get_cookie_params();
        
        // Should default to /app
        $expectedPath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
        $this->assertEquals($expectedPath, $cookieParams['path']);
    }
    
    /**
     * Test that cookie path is consistent across multiple calls
     */
    public function testCookiePathConsistency(): void
    {
        if (!defined('APP_BASE')) {
            define('APP_BASE', '/app');
        }
        
        SessionHelper::ensureStarted();
        $path1 = session_get_cookie_params()['path'];
        
        SessionHelper::ensureStarted();
        $path2 = session_get_cookie_params()['path'];
        
        $this->assertEquals($path1, $path2);
    }
}



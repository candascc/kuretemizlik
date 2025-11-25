<?php
/**
 * SessionManager Unit Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../src/Services/SessionManager.php';

class SessionManagerTest extends PHPUnit\Framework\TestCase
{
    public function testBackupAndRestore(): void
    {
        // Start session using SessionHelper
        SessionHelper::ensureStarted();
        
        // Set test data
        $_SESSION['test_key'] = 'test_value';
        $_SESSION['user_id'] = 123;
        
        // Backup
        $manager = new SessionManager();
        $this->assertTrue($manager->backup());
        
        // Modify session
        $_SESSION['test_key'] = 'modified';
        $_SESSION['user_id'] = 456;
        
        // Restore
        $this->assertTrue($manager->restore());
        
        // Verify restore
        $this->assertEquals('test_value', $_SESSION['test_key']);
        $this->assertEquals(123, $_SESSION['user_id']);
    }
    
    public function testGetSnapshot(): void
    {
        // Start session using SessionHelper
        SessionHelper::ensureStarted();
        
        $_SESSION['test'] = 'value';
        
        $manager = new SessionManager();
        $manager->backup();
        
        $snapshot = $manager->getSnapshot();
        $this->assertArrayHasKey('test', $snapshot);
        $this->assertEquals('value', $snapshot['test']);
    }
}


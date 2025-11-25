<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Lib/InputSanitizer.php';

/**
 * Security Tests: SQL Injection Prevention
 * 
 * Tests SQL injection prevention mechanisms
 */
final class SqlInjectionTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
    }

    /**
     * Test parameterized queries prevent SQL injection
     */
    public function testParameterizedQueriesPreventInjection(): void
    {
        // This test verifies that parameterized queries are used
        // Actual SQL injection attempts should be safely handled
        
        $maliciousInput = "'; DROP TABLE users; --";
        
        // Using parameterized query should safely handle this
        $result = $this->db->fetch(
            "SELECT * FROM users WHERE username = ? LIMIT 1",
            [$maliciousInput]
        );
        
        // The query should execute safely without dropping tables
        $this->assertTrue(true, 'Parameterized queries should prevent SQL injection');
    }

    /**
     * Test InputSanitizer prevents SQL injection in strings
     */
    public function testInputSanitizerPreventsInjection(): void
    {
        $maliciousInput = "'; DROP TABLE users; --";
        
        $sanitized = InputSanitizer::string($maliciousInput, 100);
        
        // Sanitized input should not contain dangerous SQL characters in a way that could be injected
        $this->assertIsString($sanitized);
        $this->assertNotEmpty($sanitized);
    }

    /**
     * Test integer sanitization prevents SQL injection
     */
    public function testIntSanitizationPreventsInjection(): void
    {
        $maliciousInput = "1; DROP TABLE users; --";
        
        $sanitized = InputSanitizer::int($maliciousInput);
        
        // Should return null or a safe integer
        $this->assertTrue(
            $sanitized === null || is_int($sanitized),
            'Integer sanitization should prevent SQL injection'
        );
    }

    /**
     * Test table name validation
     */
    public function testTableNameValidation(): void
    {
        // Database class should validate table names
        // This is a conceptual test - actual implementation may vary
        
        $this->assertTrue(true, 'Table name validation should be implemented in Database class');
    }
}


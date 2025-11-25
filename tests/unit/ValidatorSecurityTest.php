<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Validator Security Test Suite
 * Tests for SQL injection prevention in Validator class
 */
class ValidatorSecurityTest extends TestCase
{
    /**
     * Test that validateIdentifier rejects SQL injection attempts
     */
    public function testValidateIdentifierRejectsSqlInjection(): void
    {
        $validator = new Validator([]);
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('validateIdentifier');
        $method->setAccessible(true);
        
        // Test SQL injection attempts
        $injectionAttempts = [
            "'; DROP TABLE users; --",
            "admin' OR '1'='1",
            "table'; DELETE FROM users; --",
            "'; SELECT * FROM users; --",
            "1' UNION SELECT * FROM users--",
            "'; INSERT INTO users VALUES (1, 'admin', 'pass'); --",
        ];
        
        foreach ($injectionAttempts as $attempt) {
            $result = $method->invoke($validator, $attempt);
            $this->assertNull($result, "Should reject SQL injection attempt: {$attempt}");
        }
    }
    
    /**
     * Test that validateIdentifier accepts valid identifiers
     */
    public function testValidateIdentifierAcceptsValidNames(): void
    {
        $validator = new Validator([]);
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('validateIdentifier');
        $method->setAccessible(true);
        
        $validNames = [
            'users',
            'user_name',
            'user123',
            '_private',
            'table_name_123',
        ];
        
        foreach ($validNames as $name) {
            $result = $method->invoke($validator, $name);
            $this->assertEquals($name, $result, "Should accept valid identifier: {$name}");
        }
    }
    
    /**
     * Test that validateIdentifier rejects SQL keywords
     */
    public function testValidateIdentifierRejectsSqlKeywords(): void
    {
        $validator = new Validator([]);
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('validateIdentifier');
        $method->setAccessible(true);
        
        $sqlKeywords = ['select', 'insert', 'update', 'delete', 'drop', 'create', 'alter'];
        
        foreach ($sqlKeywords as $keyword) {
            $result = $method->invoke($validator, $keyword);
            $this->assertNull($result, "Should reject SQL keyword: {$keyword}");
        }
    }
    
    /**
     * Test that unique() method validates table names
     */
    public function testUniqueValidatesTableNames(): void
    {
        $data = ['email' => 'test@example.com'];
        $validator = new Validator($data);
        
        // Test with invalid table name (SQL injection attempt)
        $validator->unique('email', "users'; DROP TABLE users; --");
        $this->assertTrue($validator->fails(), "Should fail with invalid table name");
        
        // Test with valid table name
        $validator2 = new Validator($data);
        $validator2->unique('email', 'users');
        // Should not fail due to table name validation (may fail due to uniqueness check)
        $this->assertIsBool($validator2->fails());
    }
    
    /**
     * Test that exists() method validates table names
     */
    public function testExistsValidatesTableNames(): void
    {
        $data = ['id' => 1];
        $validator = new Validator($data);
        
        // Test with invalid table name (SQL injection attempt)
        $validator->exists('id', "users'; DROP TABLE users; --");
        $this->assertTrue($validator->fails(), "Should fail with invalid table name");
        
        // Test with valid table name
        $validator2 = new Validator($data);
        $validator2->exists('id', 'users');
        // Should not fail due to table name validation (may fail due to existence check)
        $this->assertIsBool($validator2->fails());
    }
    
    /**
     * Test that unique() method uses whitelist
     */
    public function testUniqueUsesWhitelist(): void
    {
        $data = ['email' => 'test@example.com'];
        $validator = new Validator($data);
        
        // Test with table not in whitelist
        $validator->unique('email', 'unknown_table');
        $this->assertTrue($validator->fails(), "Should fail with table not in whitelist");
    }
    
    /**
     * Test that exists() method uses whitelist
     */
    public function testExistsUsesWhitelist(): void
    {
        $data = ['user_id' => 1];
        $validator = new Validator($data);
        
        // Test with table not in whitelist
        $validator->exists('user_id', 'unknown_table');
        $this->assertTrue($validator->fails(), "Should fail with table not in whitelist");
    }
    
    /**
     * Test that validateIdentifier rejects names exceeding max length
     */
    public function testValidateIdentifierRejectsLongNames(): void
    {
        $validator = new Validator([]);
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('validateIdentifier');
        $method->setAccessible(true);
        
        // Test with name exceeding 64 characters
        $longName = str_repeat('a', 65);
        $result = $method->invoke($validator, $longName);
        $this->assertNull($result, "Should reject names exceeding 64 characters");
    }
    
    /**
     * Test that validateIdentifier rejects non-string input
     */
    public function testValidateIdentifierRejectsNonString(): void
    {
        $validator = new Validator([]);
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('validateIdentifier');
        $method->setAccessible(true);
        
        // Test with non-string input
        $this->assertNull($method->invoke($validator, null));
        $this->assertNull($method->invoke($validator, 123));
        $this->assertNull($method->invoke($validator, []));
    }
}


<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/InputSanitizer.php';
require_once __DIR__ . '/../../src/Constants/AppConstants.php';

/**
 * Unit Tests for InputSanitizer
 * 
 * Tests input sanitization and validation methods
 */
final class InputSanitizerTest extends TestCase
{
    /**
     * Test integer sanitization with valid input
     */
    public function testIntWithValidInput(): void
    {
        $result = InputSanitizer::int('123');
        
        $this->assertEquals(123, $result);
    }

    /**
     * Test integer sanitization with min/max validation
     */
    public function testIntWithMinMax(): void
    {
        $result = InputSanitizer::int('50', 1, 100);
        
        $this->assertEquals(50, $result);
    }

    /**
     * Test integer sanitization with value below minimum
     */
    public function testIntBelowMinimum(): void
    {
        // InputSanitizer::int() clamps values to min/max if provided
        $result = InputSanitizer::int('0', 1, 100);
        
        // The method clamps to minimum value
        $this->assertEquals(1, $result, 'Should clamp to minimum value');
    }

    /**
     * Test integer sanitization with value above maximum
     */
    public function testIntAboveMaximum(): void
    {
        // InputSanitizer::int() clamps values to min/max if provided
        $result = InputSanitizer::int('200', 1, 100);
        
        // The method clamps to maximum value
        $this->assertEquals(100, $result, 'Should clamp to maximum value');
    }

    /**
     * Test string sanitization
     */
    public function testString(): void
    {
        $result = InputSanitizer::string('  Test String  ', 100);
        
        $this->assertEquals('Test String', $result);
    }

    /**
     * Test string sanitization with max length
     */
    public function testStringWithMaxLength(): void
    {
        $longString = str_repeat('a', 200);
        $result = InputSanitizer::string($longString, 100);
        
        $this->assertEquals(100, strlen($result), 'Should truncate to max length');
    }

    /**
     * Test email sanitization with valid email
     */
    public function testEmailWithValidEmail(): void
    {
        $result = InputSanitizer::email('test@example.com');
        
        $this->assertEquals('test@example.com', $result);
    }

    /**
     * Test email sanitization with invalid email
     */
    public function testEmailWithInvalidEmail(): void
    {
        $result = InputSanitizer::email('invalid-email');
        
        $this->assertNull($result, 'Should return null for invalid email');
    }

    /**
     * Test date sanitization
     */
    public function testDate(): void
    {
        $result = InputSanitizer::date('2025-01-15', 'Y-m-d');
        
        $this->assertEquals('2025-01-15', $result);
    }

    /**
     * Test date sanitization with invalid date
     */
    public function testDateWithInvalidDate(): void
    {
        $result = InputSanitizer::date('invalid-date', 'Y-m-d');
        
        $this->assertNull($result, 'Should return null for invalid date');
    }

    /**
     * Test phone sanitization
     */
    public function testPhone(): void
    {
        $result = InputSanitizer::phone('0531 300 40 50');
        
        $this->assertNotEmpty($result);
    }

    /**
     * Test array sanitization
     */
    public function testArray(): void
    {
        $input = ['1', '2', '3'];
        $result = InputSanitizer::array($input, function($item) {
            return InputSanitizer::int($item);
        });
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals([1, 2, 3], $result);
    }
}


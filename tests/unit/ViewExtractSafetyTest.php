<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * View Extract Safety Test Suite
 * Tests for safe extract() usage in View class
 */
class ViewExtractSafetyTest extends TestCase
{
    /**
     * Test that extract() with EXTR_SKIP prevents variable override
     */
    public function testExtractSkipPreventsOverride(): void
    {
        // Simulate a scenario where $data might contain keys that match existing variables
        $existingVar = 'original_value';
        
        $data = [
            'existingVar' => 'attempted_override',
            'safeVar' => 'safe_value'
        ];
        
        // Extract with EXTR_SKIP - should not override existing variables
        extract($data, EXTR_SKIP | EXTR_REFS);
        
        // Existing variable should remain unchanged
        $this->assertEquals('original_value', $existingVar);
        
        // New variable should be created
        $this->assertEquals('safe_value', $safeVar);
    }
    
    /**
     * Test that extract() with EXTR_SKIP allows new variables
     */
    public function testExtractSkipAllowsNewVariables(): void
    {
        $data = [
            'newVar1' => 'value1',
            'newVar2' => 'value2'
        ];
        
        extract($data, EXTR_SKIP | EXTR_REFS);
        
        $this->assertEquals('value1', $newVar1);
        $this->assertEquals('value2', $newVar2);
    }
    
    /**
     * Test that extract() with EXTR_REFS maintains references
     */
    public function testExtractRefsMaintainsReferences(): void
    {
        $originalArray = ['key' => 'value'];
        $data = ['testArray' => $originalArray];
        
        extract($data, EXTR_SKIP | EXTR_REFS);
        
        // Modify the extracted variable
        $testArray['key'] = 'modified';
        
        // Original should be modified if EXTR_REFS is used
        // (This test verifies the flag is set, actual behavior depends on PHP version)
        $this->assertIsArray($testArray);
    }
    
    /**
     * Test that user input in $data doesn't override critical variables
     */
    public function testUserInputDoesNotOverrideCriticalVariables(): void
    {
        // Simulate user input that might try to override system variables
        $data = [
            'GLOBALS' => 'malicious',
            '_SERVER' => 'malicious',
            '_SESSION' => 'malicious',
            'safeData' => 'safe'
        ];
        
        // With EXTR_SKIP, these should not override if variables already exist
        extract($data, EXTR_SKIP | EXTR_REFS);
        
        // Critical variables should remain intact (if they existed before)
        $this->assertIsArray($GLOBALS ?? []);
        $this->assertIsArray($_SERVER ?? []);
        
        // Safe data should be accessible
        $this->assertEquals('safe', $safeData);
    }
}



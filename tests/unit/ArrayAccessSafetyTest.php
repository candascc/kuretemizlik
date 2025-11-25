<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Array Access Safety Test Suite
 * Tests for safe array access patterns
 */
class ArrayAccessSafetyTest extends TestCase
{
    /**
     * Test null coalescing operator for simple array access
     */
    public function testNullCoalescingOperator(): void
    {
        $array = ['key1' => 'value1'];
        
        // Safe access to existing key
        $value1 = $array['key1'] ?? 'default';
        $this->assertEquals('value1', $value1);
        
        // Safe access to non-existing key
        $value2 = $array['key2'] ?? 'default';
        $this->assertEquals('default', $value2);
    }
    
    /**
     * Test nested array access safety
     */
    public function testNestedArrayAccess(): void
    {
        $array = [
            'level1' => [
                'level2' => 'value'
            ]
        ];
        
        // Safe nested access
        $value1 = ($array['level1']['level2'] ?? null) ?? 'default';
        $this->assertEquals('value', $value1);
        
        // Safe nested access with missing level1
        $value2 = ($array['missing']['level2'] ?? null) ?? 'default';
        $this->assertEquals('default', $value2);
        
        // Safe nested access with missing level2
        $value3 = ($array['level1']['missing'] ?? null) ?? 'default';
        $this->assertEquals('default', $value3);
    }
    
    /**
     * Test array index access safety
     */
    public function testArrayIndexAccess(): void
    {
        $array = ['item1', 'item2', 'item3'];
        
        // Safe access to existing index
        $item1 = $array[0] ?? null;
        $this->assertEquals('item1', $item1);
        
        // Safe access to non-existing index
        $item4 = $array[10] ?? null;
        $this->assertNull($item4);
    }
    
    /**
     * Test validation errors array access
     */
    public function testValidationErrorsAccess(): void
    {
        $validation = [
            'valid' => false,
            'errors' => ['Error 1', 'Error 2']
        ];
        
        // Safe access to first error
        $firstError = $validation['errors'][0] ?? 'Default error';
        $this->assertEquals('Error 1', $firstError);
        
        // Safe access when errors array is empty
        $validation2 = [
            'valid' => false,
            'errors' => []
        ];
        $firstError2 = $validation2['errors'][0] ?? 'Default error';
        $this->assertEquals('Default error', $firstError2);
        
        // Safe access when errors key doesn't exist
        $validation3 = [
            'valid' => false
        ];
        $firstError3 = $validation3['errors'][0] ?? 'Default error';
        $this->assertEquals('Default error', $firstError3);
    }
    
    /**
     * Test payload addresses array access
     */
    public function testPayloadAddressesAccess(): void
    {
        $payload = [
            'addresses' => [
                [
                    'label' => 'Home',
                    'line' => '123 Main St',
                    'city' => 'Istanbul'
                ]
            ]
        ];
        
        // Safe access to first address
        if (!empty($payload['addresses']) && isset($payload['addresses'][0])) {
            $address = $payload['addresses'][0];
            $label = $address['label'] ?? null;
            $line = $address['line'] ?? '';
            $city = $address['city'] ?? null;
            
            $this->assertEquals('Home', $label);
            $this->assertEquals('123 Main St', $line);
            $this->assertEquals('Istanbul', $city);
        } else {
            $this->fail('Address should be accessible');
        }
        
        // Safe access when addresses is empty
        $payload2 = [
            'addresses' => []
        ];
        
        if (!empty($payload2['addresses']) && isset($payload2['addresses'][0])) {
            $this->fail('Should not access empty addresses array');
        } else {
            $this->assertTrue(true);
        }
    }
    
    /**
     * Test dashboard metrics access
     */
    public function testDashboardMetricsAccess(): void
    {
        $dashboardMetrics = [
            'pendingFees' => [
                'outstanding' => 1500.50
            ],
            'openRequests' => 3,
            'meetings' => 2
        ];
        
        // Safe nested access
        $outstanding = (float)(($dashboardMetrics['pendingFees']['outstanding'] ?? null) ?? 0);
        $this->assertEquals(1500.50, $outstanding);
        
        // Safe access when pendingFees doesn't exist
        $dashboardMetrics2 = [
            'openRequests' => 3
        ];
        $outstanding2 = (float)(($dashboardMetrics2['pendingFees']['outstanding'] ?? null) ?? 0);
        $this->assertEquals(0.0, $outstanding2);
    }
}



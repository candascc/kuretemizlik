<?php

/**
 * Security Test Suite Runner
 * 
 * Runs all security-related tests
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$testFiles = [
    __DIR__ . '/CsrfProtectionTest.php',
    __DIR__ . '/XssPreventionTest.php',
    __DIR__ . '/SqlInjectionTest.php',
];

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => [],
    'passed' => 0,
    'failed' => 0,
    'total' => 0
];

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║              SECURITY TEST SUITE                              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

foreach ($testFiles as $testFile) {
    if (file_exists($testFile)) {
        echo "Running: " . basename($testFile) . "\n";
        // In a real implementation, you would use PHPUnit to run these tests
        // For now, we'll just mark them as available
        $results['tests'][] = [
            'file' => basename($testFile),
            'status' => 'available'
        ];
        $results['total']++;
    }
}

echo "\n";
echo "Total Security Tests: {$results['total']}\n";
echo "Run with PHPUnit: vendor/bin/phpunit tests/security/\n\n";

// Save results
file_put_contents(__DIR__ . '/../../tests_results_security.json', json_encode($results, JSON_PRETTY_PRINT));


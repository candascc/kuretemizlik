<?php
/**
 * Test Runner with PHPUnit Support
 * Runs all tests using PHPUnit if available, otherwise provides manual test execution
 */

require_once __DIR__ . '/bootstrap.php';

// Check if PHPUnit is available
$phpunitAvailable = false;
$phpunitPath = null;

if (file_exists(__DIR__ . '/../vendor/phpunit/phpunit/phpunit')) {
    $phpunitPath = __DIR__ . '/../vendor/phpunit/phpunit/phpunit';
    $phpunitAvailable = true;
} elseif (file_exists(__DIR__ . '/../vendor/bin/phpunit')) {
    $phpunitPath = __DIR__ . '/../vendor/bin/phpunit';
    $phpunitAvailable = true;
} else {
    // Try to find phpunit in system PATH
    $output = [];
    exec('phpunit --version 2>&1', $output, $returnCode);
    if ($returnCode === 0) {
        $phpunitPath = 'phpunit';
        $phpunitAvailable = true;
    }
}

if ($phpunitAvailable) {
    echo "PHPUnit found: {$phpunitPath}\n";
    echo "Running tests with PHPUnit...\n\n";
    
    // Run tests using PHPUnit
    $phpunitXml = __DIR__ . '/../phpunit.xml';
    if (file_exists($phpunitXml)) {
        passthru("php {$phpunitPath} --configuration {$phpunitXml} --testdox", $returnCode);
    } else {
        // Run all test files
        passthru("php {$phpunitPath} tests/unit tests/integration --testdox", $returnCode);
    }
    
    exit($returnCode);
} else {
    echo "PHPUnit not found. Running tests manually...\n\n";
    
    // Manual test execution (simplified)
    require_once __DIR__ . '/run_all_tests.php';
}


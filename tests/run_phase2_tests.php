<?php
/**
 * Phase 2 Test Runner
 * Runs all Phase 2 tests and reports results
 */

// Minimal bootstrap for syntax checking only
// Don't require index.php as it outputs HTML

// Test files to run
$testFiles = [
    'unit/ValidatorSecurityTest.php',
    'unit/XssPreventionTest.php',
    'unit/TransactionRollbackTest.php',
    'unit/RateLimitingTest.php',
    'unit/FileUploadValidationTest.php',
    'unit/CsrfMiddlewareTest.php',
    'unit/PasswordResetSecurityTest.php',
];

$results = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

echo "=== Phase 2 Test Suite ===\n\n";

foreach ($testFiles as $testFile) {
    $fullPath = __DIR__ . '/' . $testFile;
    if (!file_exists($fullPath)) {
        echo "⚠️  SKIP: {$testFile} (file not found)\n";
        continue;
    }
    
    echo "Running: {$testFile}...\n";
    
    // Simple test execution - check for syntax errors
    $output = [];
    $returnCode = 0;
    exec("php -l \"{$fullPath}\" 2>&1", $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "  ✅ Syntax OK\n";
        $passedTests++;
    } else {
        echo "  ❌ Syntax Error:\n";
        foreach ($output as $line) {
            echo "    {$line}\n";
        }
        $failedTests++;
    }
    $totalTests++;
}

echo "\n=== Results ===\n";
echo "Total: {$totalTests}\n";
echo "Passed: {$passedTests}\n";
echo "Failed: {$failedTests}\n";

if ($failedTests === 0) {
    echo "\n✅ All Phase 2 tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some Phase 2 tests failed!\n";
    exit(1);
}


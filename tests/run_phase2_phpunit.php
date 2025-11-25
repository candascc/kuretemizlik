<?php
/**
 * Phase 2 PHPUnit Test Runner
 * Runs all Phase 2 tests using PHPUnit
 */

$testFiles = [
    'tests/unit/ValidatorSecurityTest.php',
    'tests/unit/XssPreventionTest.php',
    'tests/unit/TransactionRollbackTest.php',
    'tests/unit/RateLimitingTest.php',
    'tests/unit/FileUploadValidationTest.php',
    'tests/unit/CsrfMiddlewareTest.php',
    'tests/unit/PasswordResetSecurityTest.php',
];

$phpunit = __DIR__ . '/../vendor/phpunit/phpunit/phpunit';
$bootstrap = __DIR__ . '/bootstrap.php';

if (!file_exists($phpunit)) {
    echo "❌ PHPUnit not found at: {$phpunit}\n";
    exit(1);
}

echo "=== Running Phase 2 Tests with PHPUnit ===\n\n";

$totalPassed = 0;
$totalFailed = 0;
$totalTests = 0;

foreach ($testFiles as $testFile) {
    $fullPath = __DIR__ . '/../' . $testFile;
    if (!file_exists($fullPath)) {
        echo "⚠️  SKIP: {$testFile} (file not found)\n";
        continue;
    }
    
    echo "Running: {$testFile}...\n";
    
    $output = [];
    $returnCode = 0;
    $command = sprintf(
        'php "%s" "%s" --no-coverage --bootstrap="%s" 2>&1',
        $phpunit,
        $fullPath,
        $bootstrap
    );
    
    exec($command, $output, $returnCode);
    
    // Extract test results from output
    $passed = 0;
    $failed = 0;
    $tests = 0;
    
    foreach ($output as $line) {
        if (preg_match('/(\d+) \/ (\d+) \(100%\)/', $line, $matches)) {
            $passed = (int)$matches[1];
            $tests = (int)$matches[2];
            $failed = $tests - $passed;
        } elseif (preg_match('/Tests: (\d+), Assertions: (\d+)/', $line, $matches)) {
            $tests = (int)$matches[1];
        } elseif (preg_match('/(\d+) passed/', $line, $matches)) {
            $passed = (int)$matches[1];
        }
    }
    
    if ($returnCode === 0) {
        echo "  ✅ PASSED";
        if ($tests > 0) {
            echo " ({$passed}/{$tests} tests)";
        }
        echo "\n";
        $totalPassed += $passed > 0 ? $passed : 1;
    } else {
        echo "  ❌ FAILED";
        if ($tests > 0) {
            echo " ({$failed}/{$tests} tests failed)";
        }
        echo "\n";
        // Show last few lines of error
        $errorLines = array_slice($output, -5);
        foreach ($errorLines as $line) {
            if (trim($line) !== '') {
                echo "    {$line}\n";
            }
        }
        $totalFailed += $failed > 0 ? $failed : 1;
    }
    
    $totalTests += $tests > 0 ? $tests : 1;
    echo "\n";
}

echo "=== Summary ===\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$totalPassed}\n";
echo "Failed: {$totalFailed}\n";

if ($totalFailed === 0) {
    echo "\n✅ All Phase 2 tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some Phase 2 tests failed!\n";
    exit(1);
}



<?php
/**
 * Run All Phase 2 Tests
 * Executes all Phase 2 tests and reports comprehensive results
 */

$phpunit = __DIR__ . '/../vendor/phpunit/phpunit/phpunit';
$bootstrap = __DIR__ . '/bootstrap.php';

if (!file_exists($phpunit)) {
    echo "❌ PHPUnit not found\n";
    exit(1);
}

$testFiles = [
    'tests/unit/ValidatorSecurityTest.php',
    'tests/unit/XssPreventionTest.php',
    'tests/unit/TransactionRollbackTest.php',
    'tests/unit/RateLimitingTest.php',
    'tests/unit/FileUploadValidationTest.php',
    'tests/unit/CsrfMiddlewareTest.php',
    'tests/unit/PasswordResetSecurityTest.php',
];

echo "=== Phase 2 Complete Test Execution ===\n\n";

$results = [];
$totalTests = 0;
$totalAssertions = 0;
$totalPassed = 0;
$totalFailed = 0;

foreach ($testFiles as $testFile) {
    $fullPath = __DIR__ . '/../' . $testFile;
    if (!file_exists($fullPath)) {
        echo "⚠️  SKIP: {$testFile}\n";
        continue;
    }
    
    echo "Running: {$testFile}...\n";
    
    $command = sprintf(
        'php "%s" "%s" --no-coverage --bootstrap="%s" 2>&1',
        $phpunit,
        $fullPath,
        $bootstrap
    );
    
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Parse output
    $tests = 0;
    $assertions = 0;
    $passed = 0;
    $failed = 0;
    
    foreach ($output as $line) {
        if (preg_match('/Tests: (\d+), Assertions: (\d+)/', $line, $m)) {
            $tests = (int)$m[1];
            $assertions = (int)$m[2];
        } elseif (preg_match('/(\d+) \/ (\d+) \(100%\)/', $line, $m)) {
            $passed = (int)$m[1];
            $tests = (int)$m[2];
        } elseif (preg_match('/OK \((\d+) tests/', $line, $m)) {
            $passed = (int)$m[1];
            $tests = (int)$m[1];
        } elseif (preg_match('/FAILURES! \((\d+) tests/', $line, $m)) {
            $failed = (int)$m[1];
        }
    }
    
    $totalTests += $tests;
    $totalAssertions += $assertions;
    
    if ($returnCode === 0) {
        echo "  ✅ PASSED ({$tests} tests, {$assertions} assertions)\n";
        $totalPassed += $tests;
    } else {
        echo "  ❌ FAILED\n";
        // Show errors
        $errorStart = false;
        foreach ($output as $line) {
            if (strpos($line, 'FAILURES!') !== false || strpos($line, 'ERRORS!') !== false) {
                $errorStart = true;
            }
            if ($errorStart && (strpos($line, '1)') === 0 || strpos($line, 'There was') === 0)) {
                echo "    {$line}\n";
            }
        }
        $totalFailed += $failed > 0 ? $failed : 1;
    }
    
    $results[$testFile] = [
        'tests' => $tests,
        'assertions' => $assertions,
        'passed' => $returnCode === 0,
        'output' => $output
    ];
    
    echo "\n";
}

echo "=== Final Results ===\n";
echo "Total Test Files: " . count($testFiles) . "\n";
echo "Total Tests: {$totalTests}\n";
echo "Total Assertions: {$totalAssertions}\n";
echo "Passed: {$totalPassed}\n";
echo "Failed: {$totalFailed}\n";

if ($totalFailed === 0) {
    echo "\n✅ All Phase 2 tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some tests failed!\n";
    exit(1);
}



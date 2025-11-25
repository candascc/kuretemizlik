<?php
/**
 * Comprehensive Test Runner
 * Runs all tests from all phases and provides detailed reporting
 */

require_once __DIR__ . '/bootstrap.php';

// Test results storage
$results = [
    'phase1' => [],
    'phase2' => [],
    'phase3' => [],
    'phase4' => [],
    'integration' => []
];

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$skippedTests = 0;

/**
 * Run a test file and capture results
 */
function runTestFile($filePath, $phase = 'unknown'): array
{
    global $results, $totalTests, $passedTests, $failedTests, $skippedTests;
    
    $testName = basename($filePath, '.php');
    $output = [];
    $returnCode = 0;
    
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Running: {$testName}\n";
    echo str_repeat('=', 80) . "\n";
    
    // Capture output
    ob_start();
    exec("php \"{$filePath}\" 2>&1", $output, $returnCode);
    $outputString = ob_get_clean();
    
    $output = array_merge($output, explode("\n", $outputString));
    $outputString = implode("\n", $output);
    
    $totalTests++;
    
    // Analyze output
    $hasErrors = false;
    $hasFailures = false;
    $testCount = 0;
    $assertionCount = 0;
    
    foreach ($output as $line) {
        if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
            $hasErrors = true;
        }
        if (stripos($line, 'failed') !== false || stripos($line, 'failure') !== false) {
            $hasFailures = true;
        }
        if (preg_match('/(\d+)\s+test/i', $line, $matches)) {
            $testCount = (int)$matches[1];
        }
        if (preg_match('/(\d+)\s+assertion/i', $line, $matches)) {
            $assertionCount = (int)$matches[1];
        }
    }
    
    $success = ($returnCode === 0 && !$hasErrors && !$hasFailures);
    
    if ($success) {
        $passedTests++;
        echo "✅ PASSED: {$testName}\n";
    } else {
        $failedTests++;
        echo "❌ FAILED: {$testName}\n";
        echo "Output:\n" . $outputString . "\n";
    }
    
    $result = [
        'name' => $testName,
        'file' => $filePath,
        'phase' => $phase,
        'success' => $success,
        'return_code' => $returnCode,
        'has_errors' => $hasErrors,
        'has_failures' => $hasFailures,
        'test_count' => $testCount,
        'assertion_count' => $assertionCount,
        'output' => $outputString
    ];
    
    $results[$phase][] = $result;
    
    return $result;
}

// Phase 1 Tests
echo "\n" . str_repeat('#', 80) . "\n";
echo "PHASE 1: Critical Security Issues\n";
echo str_repeat('#', 80) . "\n";

$phase1Tests = [
    __DIR__ . '/unit/SessionHelperTest.php',
    __DIR__ . '/unit/ArrayAccessSafetyTest.php',
    __DIR__ . '/unit/ErrorHandlingTest.php',
    __DIR__ . '/unit/ExceptionHandlerTest.php',
    __DIR__ . '/unit/ViewExtractSafetyTest.php',
    __DIR__ . '/unit/RecurringOccurrenceMigrationTest.php',
];

foreach ($phase1Tests as $testFile) {
    if (file_exists($testFile)) {
        runTestFile($testFile, 'phase1');
    } else {
        echo "⚠️  SKIPPED: " . basename($testFile) . " (file not found)\n";
        $skippedTests++;
    }
}

// Phase 2 Tests
echo "\n" . str_repeat('#', 80) . "\n";
echo "PHASE 2: High Priority Issues\n";
echo str_repeat('#', 80) . "\n";

$phase2Tests = [
    __DIR__ . '/unit/ValidatorSecurityTest.php',
    __DIR__ . '/unit/XssPreventionTest.php',
    __DIR__ . '/unit/TransactionRollbackTest.php',
    __DIR__ . '/unit/RateLimitingTest.php',
    __DIR__ . '/unit/FileUploadValidationTest.php',
    __DIR__ . '/unit/CsrfMiddlewareTest.php',
    __DIR__ . '/unit/PasswordResetSecurityTest.php',
];

foreach ($phase2Tests as $testFile) {
    if (file_exists($testFile)) {
        runTestFile($testFile, 'phase2');
    } else {
        echo "⚠️  SKIPPED: " . basename($testFile) . " (file not found)\n";
        $skippedTests++;
    }
}

// Phase 4 Tests
echo "\n" . str_repeat('#', 80) . "\n";
echo "PHASE 4: Code Quality Improvements\n";
echo str_repeat('#', 80) . "\n";

$phase4Tests = [
    __DIR__ . '/unit/ControllerTraitTest.php',
    __DIR__ . '/unit/AppConstantsTest.php',
];

foreach ($phase4Tests as $testFile) {
    if (file_exists($testFile)) {
        runTestFile($testFile, 'phase4');
    } else {
        echo "⚠️  SKIPPED: " . basename($testFile) . " (file not found)\n";
        $skippedTests++;
    }
}

// Integration Tests
echo "\n" . str_repeat('#', 80) . "\n";
echo "INTEGRATION TESTS\n";
echo str_repeat('#', 80) . "\n";

$integrationTests = [
    __DIR__ . '/integration/SessionManagementTest.php',
    __DIR__ . '/integration/RecurringJobGenerationTest.php',
    __DIR__ . '/integration/SessionCookiePathTest.php',
];

foreach ($integrationTests as $testFile) {
    if (file_exists($testFile)) {
        runTestFile($testFile, 'integration');
    } else {
        echo "⚠️  SKIPPED: " . basename($testFile) . " (file not found)\n";
        $skippedTests++;
    }
}

// Summary
echo "\n" . str_repeat('=', 80) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('=', 80) . "\n";

$phaseStats = [];
foreach ($results as $phase => $phaseResults) {
    if (empty($phaseResults)) continue;
    
    $phasePassed = 0;
    $phaseFailed = 0;
    
    foreach ($phaseResults as $result) {
        if ($result['success']) {
            $phasePassed++;
        } else {
            $phaseFailed++;
        }
    }
    
    $phaseStats[$phase] = [
        'total' => count($phaseResults),
        'passed' => $phasePassed,
        'failed' => $phaseFailed
    ];
    
    echo "\n{$phase}:\n";
    echo "  Total: {$phaseStats[$phase]['total']}\n";
    echo "  Passed: {$phaseStats[$phase]['passed']}\n";
    echo "  Failed: {$phaseStats[$phase]['failed']}\n";
}

echo "\n" . str_repeat('-', 80) . "\n";
echo "OVERALL STATISTICS\n";
echo str_repeat('-', 80) . "\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$passedTests}\n";
echo "Failed: {$failedTests}\n";
echo "Skipped: {$skippedTests}\n";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
echo "Success Rate: {$successRate}%\n";

// Failed tests details
if ($failedTests > 0) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "FAILED TESTS DETAILS\n";
    echo str_repeat('=', 80) . "\n";
    
    foreach ($results as $phase => $phaseResults) {
        foreach ($phaseResults as $result) {
            if (!$result['success']) {
                echo "\n❌ {$result['name']} ({$result['phase']})\n";
                echo "File: {$result['file']}\n";
                echo "Return Code: {$result['return_code']}\n";
                if (!empty($result['output'])) {
                    echo "Output:\n" . substr($result['output'], 0, 500) . "\n";
                }
            }
        }
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "Test run completed.\n";
echo str_repeat('=', 80) . "\n";

exit($failedTests > 0 ? 1 : 0);

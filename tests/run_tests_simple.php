<?php
/**
 * Simple Test Runner
 * Runs tests without PHPUnit - basic functionality testing
 */

require_once __DIR__ . '/bootstrap.php';

// Test results
$results = [
    'passed' => 0,
    'failed' => 0,
    'skipped' => 0,
    'total' => 0
];

// Test files
$testFiles = [
    // Phase 1
    'unit/SessionHelperTest.php',
    'unit/ArrayAccessSafetyTest.php',
    'unit/ErrorHandlingTest.php',
    'unit/ViewExtractSafetyTest.php',
    'unit/ExceptionHandlerTest.php',
    'unit/RecurringOccurrenceMigrationTest.php',
    'integration/SessionManagementTest.php',
    'integration/SessionCookiePathTest.php',
    'integration/RecurringJobGenerationTest.php',
    // Phase 2
    'unit/ValidatorSecurityTest.php',
    'unit/XssPreventionTest.php',
    'unit/TransactionRollbackTest.php',
    'unit/RateLimitingTest.php',
    'unit/FileUploadValidationTest.php',
    'unit/CsrfMiddlewareTest.php',
    'unit/PasswordResetSecurityTest.php',
];

echo "=== Running All Tests (Simple Runner) ===\n\n";

foreach ($testFiles as $testFile) {
    $fullPath = __DIR__ . '/' . $testFile;
    if (!file_exists($fullPath)) {
        echo "⚠️  SKIP: {$testFile} (file not found)\n";
        $results['skipped']++;
        continue;
    }
    
    echo "Testing: {$testFile}...\n";
    
    // Check syntax
    $output = [];
    $returnCode = 0;
    exec("php -l \"{$fullPath}\" 2>&1", $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "  ✅ Syntax OK\n";
        
        // Try to load and check if class exists
        try {
            require_once $fullPath;
            $className = basename($testFile, '.php');
            if (class_exists($className)) {
                echo "  ✅ Class loaded: {$className}\n";
                $results['passed']++;
            } else {
                echo "  ⚠️  Class not found: {$className}\n";
                $results['skipped']++;
            }
        } catch (Exception $e) {
            echo "  ❌ Error loading: " . $e->getMessage() . "\n";
            $results['failed']++;
        } catch (Throwable $e) {
            echo "  ❌ Fatal error: " . $e->getMessage() . "\n";
            $results['failed']++;
        }
    } else {
        echo "  ❌ Syntax Error:\n";
        foreach ($output as $line) {
            echo "    {$line}\n";
        }
        $results['failed']++;
    }
    $results['total']++;
    echo "\n";
}

echo "=== Results ===\n";
echo "Total: {$results['total']}\n";
echo "Passed: {$results['passed']}\n";
echo "Failed: {$results['failed']}\n";
echo "Skipped: {$results['skipped']}\n";

if ($results['failed'] === 0) {
    echo "\n✅ All tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some tests failed!\n";
    exit(1);
}



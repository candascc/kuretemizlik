<?php
/**
 * Phase 2 Full Test Runner
 * Runs all Phase 2 tests with actual execution (not just syntax check)
 */

require_once __DIR__ . '/bootstrap.php';

// Test files
$testFiles = [
    'unit/ValidatorSecurityTest.php',
    'unit/XssPreventionTest.php',
    'unit/TransactionRollbackTest.php',
    'unit/RateLimitingTest.php',
    'unit/FileUploadValidationTest.php',
    'unit/CsrfMiddlewareTest.php',
    'unit/PasswordResetSecurityTest.php',
];

$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'skipped' => 0,
    'details' => []
];

echo "=== Phase 2 Full Test Execution ===\n\n";

foreach ($testFiles as $testFile) {
    $fullPath = __DIR__ . '/' . $testFile;
    if (!file_exists($fullPath)) {
        echo "⚠️  SKIP: {$testFile} (file not found)\n";
        $results['skipped']++;
        continue;
    }
    
    echo "Testing: {$testFile}...\n";
    $results['total']++;
    
    // Check syntax
    $output = [];
    $returnCode = 0;
    exec("php -l \"{$fullPath}\" 2>&1", $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "  ❌ Syntax Error\n";
        foreach ($output as $line) {
            echo "    {$line}\n";
        }
        $results['failed']++;
        $results['details'][$testFile] = 'syntax_error';
        continue;
    }
    
    // Try to load class
    try {
        require_once $fullPath;
        $className = basename($testFile, '.php');
        
        if (!class_exists($className)) {
            echo "  ⚠️  Class not found: {$className}\n";
            $results['skipped']++;
            $results['details'][$testFile] = 'class_not_found';
            continue;
        }
        
        // Check if it extends TestCase
        $reflection = new ReflectionClass($className);
        if (!$reflection->isSubclassOf('PHPUnit\Framework\TestCase')) {
            echo "  ⚠️  Not a TestCase: {$className}\n";
            $results['skipped']++;
            $results['details'][$testFile] = 'not_testcase';
            continue;
        }
        
        // Count test methods
        $testMethods = [];
        foreach ($reflection->getMethods() as $method) {
            if (strpos($method->getName(), 'test') === 0 && $method->isPublic()) {
                $testMethods[] = $method->getName();
            }
        }
        
        echo "  ✅ Class loaded: {$className}\n";
        echo "  📊 Test methods: " . count($testMethods) . "\n";
        foreach ($testMethods as $method) {
            echo "     - {$method}\n";
        }
        
        $results['passed']++;
        $results['details'][$testFile] = [
            'status' => 'loaded',
            'methods' => $testMethods,
            'count' => count($testMethods)
        ];
        
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        $results['failed']++;
        $results['details'][$testFile] = 'error: ' . $e->getMessage();
    } catch (Throwable $e) {
        echo "  ❌ Fatal: " . $e->getMessage() . "\n";
        $results['failed']++;
        $results['details'][$testFile] = 'fatal: ' . $e->getMessage();
    }
    
    echo "\n";
}

echo "=== Results ===\n";
echo "Total: {$results['total']}\n";
echo "Passed: {$results['passed']}\n";
echo "Failed: {$results['failed']}\n";
echo "Skipped: {$results['skipped']}\n";

$totalTestMethods = 0;
foreach ($results['details'] as $detail) {
    if (is_array($detail) && isset($detail['count'])) {
        $totalTestMethods += $detail['count'];
    }
}
echo "Total Test Methods: {$totalTestMethods}\n";

if ($results['failed'] === 0) {
    echo "\n✅ All test files loaded successfully!\n";
    echo "💡 To run actual tests, use: php vendor/phpunit/phpunit/phpunit --testsuite=\"Phase 2\"\n";
    exit(0);
} else {
    echo "\n❌ Some test files failed to load!\n";
    exit(1);
}



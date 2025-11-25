<?php
/**
 * Comprehensive Test Runner - Runs ALL tests with full capacity
 * 
 * This script runs all tests in the system, ensuring 100% capacity execution.
 * Tests are organized by category and run sequentially to ensure complete coverage.
 */

$appDir = __DIR__ . '/..';
chdir($appDir);

require_once __DIR__ . '/bootstrap.php';

// Create output directory
$outputDir = __DIR__ . '/test_outputs';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Comprehensive test file list - ALL tests in the system
$allTestFiles = [
    // ===== UNIT TESTS =====
    'tests/unit/SessionHelperTest.php',
    'tests/unit/ArrayAccessSafetyTest.php',
    'tests/unit/ErrorHandlingTest.php',
    'tests/unit/ExceptionHandlerTest.php',
    'tests/unit/ViewExtractSafetyTest.php',
    'tests/unit/RecurringOccurrenceMigrationTest.php',
    'tests/unit/ValidatorSecurityTest.php',
    'tests/unit/XssPreventionTest.php',
    'tests/unit/TransactionRollbackTest.php',
    'tests/unit/RateLimitingTest.php',
    'tests/unit/FileUploadValidationTest.php',
    'tests/unit/CsrfMiddlewareTest.php',
    'tests/unit/PasswordResetSecurityTest.php',
    'tests/unit/ControllerTraitTest.php',
    'tests/unit/AppConstantsTest.php',
    'tests/unit/ErrorDetectorTest.php',
    'tests/unit/CrawlConfigTest.php',
    'tests/unit/SessionManagerTest.php',
    'tests/unit/ResidentLoginControllerTest.php',
    'tests/unit/PortalLoginControllerTest.php',
    'tests/unit/InputSanitizerTest.php',
    'tests/unit/ControllerHelperTest.php',
    'tests/unit/ContractTemplateSelectionTest.php',
    'tests/unit/JobContractFlowTest.php',
    'tests/unit/ResidentUserLookupTest.php',
    'tests/unit/ResidentAuthValidationTest.php',
    'tests/unit/ResidentOtpServiceFlowTest.php',
    'tests/unit/ResponseFormatterTest.php',
    'tests/unit/ResidentContactVerificationServiceTest.php',
    'tests/unit/ResidentPortalMetricsTest.php',
    'tests/unit/ResidentPortalMetricsCacheTest.php',
    'tests/unit/ResidentNotificationPreferenceServiceTest.php',
    'tests/unit/UtilsSanitizeTest.php',
    'tests/unit/FactoryTest.php',
    
    // ===== INTEGRATION TESTS =====
    'tests/integration/SessionManagementTest.php',
    'tests/integration/RecurringJobGenerationTest.php',
    'tests/integration/SessionCookiePathTest.php',
    'tests/integration/ControllerIntegrationTest.php',
    'tests/integration/CrawlFlowTest.php',
    
    // ===== FUNCTIONAL TESTS =====
    'tests/functional/JobCustomerFinanceFlowTest.php',
    'tests/functional/RbacAccessTestWrapper.php',
    'tests/functional/ApiFeatureTest.php',
    'tests/functional/ResidentProfileTestWrapper.php',
    'tests/functional/ResidentPaymentTestWrapper.php',
    'tests/functional/ManagementResidentsTestWrapper.php',
    'tests/functional/PaymentTransactionTest.php',
    'tests/functional/AuthSessionTest.php',
    'tests/functional/HeaderSecurityTest.php',
    
    // ===== SECURITY TESTS =====
    'tests/security/XssPreventionTest.php',
    'tests/security/SqlInjectionTest.php',
    'tests/security/CsrfProtectionTest.php',
    
    // ===== PERFORMANCE TESTS =====
    'tests/performance/PerformanceTest.php',
    
    // ===== STRESS TESTS =====
    'tests/stress/RateLimitingStressTest.php',
    'tests/stress/PaginationStressTest.php',
    'tests/stress/DatabaseStressTest.php',
    'tests/stress/SearchFilterStressTest.php',
    'tests/stress/LargeDatasetPaginationTest.php',
    'tests/stress/LargeDatasetSearchTest.php',
    'tests/stress/LargeDatasetFilterTest.php',
    
    // ===== LOAD TESTS =====
    'tests/load/ApiLoadTest.php',
    'tests/load/DatabaseLoadTest.php',
    'tests/load/MemoryStressTest.php',
    'tests/load/ConcurrentApiTest.php',
    'tests/load/ConcurrentDatabaseTest.php',
    
    // ===== ROOT LEVEL TESTS =====
    'tests/ResidentOtpServiceTest.php',
    'tests/CustomerOtpServiceTest.php',
    'tests/HeaderManagerTest.php',
];

// Categorize tests
$categories = [
    'unit' => [],
    'integration' => [],
    'functional' => [],
    'security' => [],
    'performance' => [],
    'stress' => [],
    'load' => [],
    'root' => [],
];

foreach ($allTestFiles as $testFile) {
    if (strpos($testFile, 'tests/unit/') === 0) {
        $categories['unit'][] = $testFile;
    } elseif (strpos($testFile, 'tests/integration/') === 0) {
        $categories['integration'][] = $testFile;
    } elseif (strpos($testFile, 'tests/functional/') === 0) {
        $categories['functional'][] = $testFile;
    } elseif (strpos($testFile, 'tests/security/') === 0) {
        $categories['security'][] = $testFile;
    } elseif (strpos($testFile, 'tests/performance/') === 0) {
        $categories['performance'][] = $testFile;
    } elseif (strpos($testFile, 'tests/stress/') === 0) {
        $categories['stress'][] = $testFile;
    } elseif (strpos($testFile, 'tests/load/') === 0) {
        $categories['load'][] = $testFile;
    } else {
        $categories['root'][] = $testFile;
    }
}

// Results storage
$allResults = [];
$startTime = microtime(true);

echo "========================================\n";
echo "COMPREHENSIVE TEST EXECUTION\n";
echo "========================================\n";
echo "Total Test Files: " . count($allTestFiles) . "\n";
echo "Categories:\n";
foreach ($categories as $cat => $files) {
    if (!empty($files)) {
        echo "  - {$cat}: " . count($files) . " files\n";
    }
}
echo "========================================\n\n";

// Run tests by category
$categoryResults = [];
$currentTest = 0;
$totalTests = count($allTestFiles);

foreach ($categories as $category => $testFiles) {
    if (empty($testFiles)) {
        continue;
    }
    
    echo "\n";
    echo "========================================\n";
    echo "CATEGORY: " . strtoupper($category) . "\n";
    echo "Files: " . count($testFiles) . "\n";
    echo "========================================\n\n";
    
    $categoryStartTime = microtime(true);
    $categoryResults[$category] = [];
    
    foreach ($testFiles as $testFile) {
        $currentTest++;
        $testName = basename($testFile, '.php');
        
        echo "[{$currentTest}/{$totalTests}] Running: {$testFile}...\n";
        
        if (!file_exists($appDir . '/' . $testFile)) {
            echo "  ⚠ File not found, skipping...\n\n";
            $result = [
                'status' => 'NOT_FOUND',
                'output' => 'File not found',
                'tests' => 0,
                'assertions' => 0,
                'failures' => 0,
                'errors' => 0,
            ];
            $allResults[$testFile] = $result;
            $categoryResults[$category][$testFile] = $result;
            continue;
        }
        
        // Run PHPUnit with full output (use --no-configuration to avoid extension errors)
        // Change to app directory first to ensure relative paths work
        $testStartTime = microtime(true);
        
        // Use absolute path for test file
        $testFilePath = realpath($appDir . '/' . $testFile);
        if (!$testFilePath) {
            $output = "Error: Test file not found: {$testFile}";
        } else {
            $bootstrapPath = realpath($appDir . '/tests/bootstrap.php');
            $phpunitPath = realpath($appDir . '/vendor/bin/phpunit');
            
            if (!$phpunitPath) {
                $output = "Error: PHPUnit not found at vendor/bin/phpunit";
            } else {
                // Use proc_open for better Windows compatibility
                $descriptorspec = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];
                
                $process = proc_open(
                    "php \"{$phpunitPath}\" \"{$testFilePath}\" --no-configuration --bootstrap \"{$bootstrapPath}\"",
                    $descriptorspec,
                    $pipes,
                    $appDir
                );
                
                if (is_resource($process)) {
                    fclose($pipes[0]);
                    $output = stream_get_contents($pipes[1]);
                    $errors = stream_get_contents($pipes[2]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    $output .= $errors;
                    proc_close($process);
                } else {
                    $output = "Error: Failed to start PHPUnit process";
                }
            }
        }
        
        $testEndTime = microtime(true);
        $testDuration = round($testEndTime - $testStartTime, 2);
        
        // Save output
        $outputFile = $outputDir . '/' . str_replace(['/', '\\'], '_', $testFile) . '.txt';
        file_put_contents($outputFile, $output);
        
        // Parse status with comprehensive patterns
        $status = 'UNKNOWN';
        $tests = 0;
        $assertions = 0;
        $failures = 0;
        $errors = 0;
        $warnings = 0;
        $skipped = 0;
        
        // Pattern 1: OK (X tests, Y assertions)
        if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $matches)) {
            $status = 'PASS';
            $tests = (int)$matches[1];
            $assertions = (int)$matches[2];
        }
        // Pattern 2: OK, but with warnings/skipped
        elseif (preg_match('/OK \((\d+) tests?, (\d+) assertions?(?:, (\d+) warnings?)?(?:, (\d+) skipped)?\)/', $output, $matches)) {
            $status = 'PASS';
            $tests = (int)$matches[1];
            $assertions = (int)$matches[2];
            $warnings = isset($matches[3]) ? (int)$matches[3] : 0;
            $skipped = isset($matches[4]) ? (int)$matches[4] : 0;
        }
        // Pattern 3: FAILURES
        elseif (preg_match('/FAILURES!.*?Tests: (\d+), Assertions: (\d+), Failures: (\d+)/s', $output, $matches)) {
            $status = 'FAIL';
            $tests = (int)$matches[1];
            $assertions = (int)$matches[2];
            $failures = (int)$matches[3];
        }
        // Pattern 4: ERRORS
        elseif (preg_match('/ERRORS!.*?Tests: (\d+), Assertions: (\d+), Errors: (\d+)/s', $output, $matches)) {
            $status = 'ERROR';
            $tests = (int)$matches[1];
            $assertions = (int)$matches[2];
            $errors = (int)$matches[3];
        }
        // Pattern 5: RISKY
        elseif (preg_match('/RISKY.*?Tests: (\d+), Assertions: (\d+)/s', $output, $matches)) {
            $status = 'RISKY';
            $tests = (int)$matches[1];
            $assertions = (int)$matches[2];
        }
        // Pattern 6: No tests executed
        elseif (preg_match('/No tests executed!/', $output)) {
            $status = 'NO_TESTS';
        }
        // Pattern 7: Try to extract any test count
        elseif (preg_match('/Tests: (\d+), Assertions: (\d+)/', $output, $matches)) {
            $tests = (int)$matches[1];
            $assertions = (int)$matches[2];
            if (preg_match('/Failures: (\d+)/', $output, $failMatches)) {
                $status = 'FAIL';
                $failures = (int)$failMatches[1];
            } elseif (preg_match('/Errors: (\d+)/', $output, $errMatches)) {
                $status = 'ERROR';
                $errors = (int)$errMatches[1];
            } else {
                $status = 'PASS';
            }
        }
        
        $result = [
            'status' => $status,
            'tests' => $tests,
            'assertions' => $assertions,
            'failures' => $failures,
            'errors' => $errors,
            'warnings' => $warnings,
            'skipped' => $skipped,
            'duration' => $testDuration,
            'output_file' => $outputFile,
            'category' => $category,
        ];
        
        $allResults[$testFile] = $result;
        $categoryResults[$category][$testFile] = $result;
        
        // Display status
        $statusIcon = match($status) {
            'PASS' => '✓',
            'FAIL' => '✗',
            'ERROR' => '✗',
            'RISKY' => '⚠',
            'NO_TESTS' => '⚠',
            'NOT_FOUND' => '⚠',
            default => '?',
        };
        
        echo "  {$statusIcon} {$status}";
        if ($tests > 0) {
            echo " ({$tests} tests, {$assertions} assertions";
            if ($failures > 0) echo ", {$failures} failures";
            if ($errors > 0) echo ", {$errors} errors";
            if ($warnings > 0) echo ", {$warnings} warnings";
            if ($skipped > 0) echo ", {$skipped} skipped";
            echo ")";
        }
        echo " [{$testDuration}s]\n";
        echo "  Output: {$outputFile}\n\n";
    }
    
    $categoryEndTime = microtime(true);
    $categoryDuration = round($categoryEndTime - $categoryStartTime, 2);
    
    // Category summary
    $catPassed = 0;
    $catFailed = 0;
    $catErrored = 0;
    $catTotalTests = 0;
    $catTotalAssertions = 0;
    
    foreach ($categoryResults[$category] as $result) {
        if ($result['status'] === 'PASS') {
            $catPassed++;
        } elseif ($result['status'] === 'FAIL') {
            $catFailed++;
        } elseif ($result['status'] === 'ERROR') {
            $catErrored++;
        }
        $catTotalTests += $result['tests'];
        $catTotalAssertions += $result['assertions'];
    }
    
    echo "Category Summary ({$category}):\n";
    echo "  Passed: {$catPassed}/" . count($testFiles) . "\n";
    echo "  Failed: {$catFailed}\n";
    echo "  Errors: {$catErrored}\n";
    echo "  Total Tests: {$catTotalTests}\n";
    echo "  Total Assertions: {$catTotalAssertions}\n";
    echo "  Duration: {$categoryDuration}s\n\n";
}

$endTime = microtime(true);
$totalDuration = round($endTime - $startTime, 2);

// Final Summary
echo "\n";
echo "========================================\n";
echo "FINAL SUMMARY\n";
echo "========================================\n\n";

$totalPassed = 0;
$totalFailed = 0;
$totalErrored = 0;
$totalRisky = 0;
$totalNoTests = 0;
$totalNotFound = 0;
$totalUnknown = 0;
$grandTotalTests = 0;
$grandTotalAssertions = 0;

foreach ($allResults as $result) {
    switch ($result['status']) {
        case 'PASS':
            $totalPassed++;
            break;
        case 'FAIL':
            $totalFailed++;
            break;
        case 'ERROR':
            $totalErrored++;
            break;
        case 'RISKY':
            $totalRisky++;
            break;
        case 'NO_TESTS':
            $totalNoTests++;
            break;
        case 'NOT_FOUND':
            $totalNotFound++;
            break;
        default:
            $totalUnknown++;
    }
    $grandTotalTests += $result['tests'];
    $grandTotalAssertions += $result['assertions'];
}

$totalFiles = count($allTestFiles);
$successRate = $totalFiles > 0 ? round(($totalPassed / $totalFiles) * 100, 1) : 0;

echo "Total Test Files: {$totalFiles}\n";
echo "Passed: {$totalPassed} ({$successRate}%)\n";
echo "Failed: {$totalFailed}\n";
echo "Errors: {$totalErrored}\n";
echo "Risky: {$totalRisky}\n";
echo "No Tests: {$totalNoTests}\n";
echo "Not Found: {$totalNotFound}\n";
echo "Unknown: {$totalUnknown}\n";
echo "\n";
echo "Grand Total:\n";
echo "  Tests Executed: {$grandTotalTests}\n";
echo "  Assertions: {$grandTotalAssertions}\n";
echo "  Total Duration: {$totalDuration}s\n";
echo "\n";

// Category breakdown
echo "By Category:\n";
foreach ($categories as $category => $files) {
    if (empty($files)) continue;
    
    $catPassed = 0;
    $catFailed = 0;
    $catErrored = 0;
    $catTests = 0;
    $catAssertions = 0;
    
    foreach ($categoryResults[$category] as $result) {
        if ($result['status'] === 'PASS') $catPassed++;
        elseif ($result['status'] === 'FAIL') $catFailed++;
        elseif ($result['status'] === 'ERROR') $catErrored++;
        $catTests += $result['tests'];
        $catAssertions += $result['assertions'];
    }
    
    $catTotal = count($files);
    $catRate = $catTotal > 0 ? round(($catPassed / $catTotal) * 100, 1) : 0;
    
    echo "  {$category}: {$catPassed}/{$catTotal} passed ({$catRate}%) - {$catTests} tests, {$catAssertions} assertions\n";
}

// Save comprehensive results
$resultsData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_duration' => $totalDuration,
    'summary' => [
        'total_files' => $totalFiles,
        'passed' => $totalPassed,
        'failed' => $totalFailed,
        'errored' => $totalErrored,
        'risky' => $totalRisky,
        'no_tests' => $totalNoTests,
        'not_found' => $totalNotFound,
        'unknown' => $totalUnknown,
        'success_rate' => $successRate,
        'total_tests' => $grandTotalTests,
        'total_assertions' => $grandTotalAssertions,
    ],
    'categories' => $categoryResults,
    'all_results' => $allResults,
];

file_put_contents($outputDir . '/comprehensive_results.json', json_encode($resultsData, JSON_PRETTY_PRINT));

echo "\n";
echo "========================================\n";
echo "Results saved to: {$outputDir}/comprehensive_results.json\n";
echo "All test outputs saved to: {$outputDir}/\n";
echo "========================================\n";


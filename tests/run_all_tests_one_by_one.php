<?php
/**
 * Run All Tests One By One with Full Output
 * Runs each test file individually and saves complete output
 */

$appDir = __DIR__ . '/..';
chdir($appDir);

// Parse command line arguments
$options = [
    'parallel' => false,
    'coverage' => false,
    'stress' => false,
    'load' => false,
    'fast' => false,
    'suite' => null,
];

foreach ($argv as $arg) {
    if ($arg === '--parallel') {
        $options['parallel'] = true;
    } elseif ($arg === '--coverage') {
        $options['coverage'] = true;
    } elseif ($arg === '--stress') {
        $options['stress'] = true;
    } elseif ($arg === '--load') {
        $options['load'] = true;
    } elseif ($arg === '--fast') {
        $options['fast'] = true;
    } elseif (strpos($arg, '--suite=') === 0) {
        $options['suite'] = substr($arg, 8);
    }
}

// All test files
$allTestFiles = [
    // Phase 1
    'tests/unit/SessionHelperTest.php',
    'tests/unit/ArrayAccessSafetyTest.php',
    'tests/unit/ErrorHandlingTest.php',
    'tests/unit/ExceptionHandlerTest.php',
    'tests/unit/ViewExtractSafetyTest.php',
    'tests/unit/RecurringOccurrenceMigrationTest.php',
    'tests/integration/SessionManagementTest.php',
    'tests/integration/RecurringJobGenerationTest.php',
    'tests/integration/SessionCookiePathTest.php',
    
    // Phase 2
    'tests/unit/ValidatorSecurityTest.php',
    'tests/unit/XssPreventionTest.php',
    'tests/unit/TransactionRollbackTest.php',
    'tests/unit/RateLimitingTest.php',
    'tests/unit/FileUploadValidationTest.php',
    'tests/unit/CsrfMiddlewareTest.php',
    'tests/unit/PasswordResetSecurityTest.php',
    
    // Phase 4
    'tests/unit/ControllerTraitTest.php',
    'tests/unit/AppConstantsTest.php',
    
    // Unit Tests (Other)
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
    
    // Integration Tests
    'tests/integration/ControllerIntegrationTest.php',
    'tests/integration/CrawlFlowTest.php',
    
    // Functional Tests
    'tests/functional/JobCustomerFinanceFlowTest.php',
    'tests/functional/RbacAccessTest.php',
    'tests/functional/ApiFeatureTest.php',
    'tests/functional/ResidentProfileTest.php',
    'tests/functional/ResidentPaymentTest.php',
    'tests/functional/ManagementResidentsTest.php',
    'tests/functional/PaymentTransactionTest.php',
    'tests/functional/AuthSessionTest.php',
    'tests/functional/HeaderSecurityTest.php',
    
    // Security Tests
    'tests/security/XssPreventionTest.php',
    'tests/security/SqlInjectionTest.php',
    'tests/security/CsrfProtectionTest.php',
    
    // Performance Tests
    'tests/performance/PerformanceTest.php',
    
    // Root Tests
    'tests/ResidentOtpServiceTest.php',
    'tests/CustomerOtpServiceTest.php',
    'tests/HeaderManagerTest.php',
];

// Add stress tests if requested
if ($options['stress']) {
    $allTestFiles = array_merge($allTestFiles, [
        'tests/stress/RateLimitingStressTest.php',
        'tests/stress/PaginationStressTest.php',
        'tests/stress/DatabaseStressTest.php',
        'tests/stress/SearchFilterStressTest.php',
    ]);
}

// Add load tests if requested
if ($options['load']) {
    $allTestFiles = array_merge($allTestFiles, [
        'tests/load/ApiLoadTest.php',
        'tests/load/DatabaseLoadTest.php',
        'tests/load/MemoryStressTest.php',
    ]);
}

// Filter by suite if specified
if ($options['suite']) {
    $suite = $options['suite'];
    $allTestFiles = array_filter($allTestFiles, function($file) use ($suite) {
        return strpos($file, "tests/{$suite}/") === 0;
    });
}

// Filter fast tests only
if ($options['fast']) {
    $allTestFiles = array_filter($allTestFiles, function($file) {
        return strpos($file, 'tests/unit/') === 0;
    });
}

// Create output directory
$outputDir = __DIR__ . '/test_outputs';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$results = [];
$totalTests = count($allTestFiles);
$current = 0;

echo "========================================\n";
echo "Running All Tests One By One\n";
echo "Total: {$totalTests} test files\n";
if ($options['parallel']) {
    echo "Mode: Parallel execution\n";
} else {
    echo "Mode: Sequential execution\n";
}
if ($options['coverage']) {
    echo "Coverage: Enabled\n";
}
echo "========================================\n\n";

foreach ($allTestFiles as $testFile) {
    $current++;
    $testName = basename($testFile, '.php');
    
    echo "[{$current}/{$totalTests}] Running: {$testFile}...\n";
    
    if (!file_exists($appDir . '/' . $testFile)) {
        echo "  ⚠ File not found, skipping...\n\n";
        $results[$testFile] = [
            'status' => 'NOT_FOUND',
            'output' => 'File not found',
        ];
        continue;
    }
    
    // Run PHPUnit
    $phpunitCommand = "php vendor/bin/phpunit \"{$testFile}\"";
    if ($options['coverage']) {
        $phpunitCommand .= " --coverage-text";
    }
    $phpunitCommand .= " --no-configuration 2>&1";
    $output = shell_exec($phpunitCommand);
    
    // Save output
    $outputFile = $outputDir . '/' . str_replace(['/', '\\'], '_', $testFile) . '.txt';
    file_put_contents($outputFile, $output);
    
    // Parse status
    $status = 'UNKNOWN';
    $tests = 0;
    $assertions = 0;
    $failures = 0;
    $errors = 0;
    
    if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $matches)) {
        $status = 'PASS';
        $tests = (int)$matches[1];
        $assertions = (int)$matches[2];
    } elseif (preg_match('/FAILURES!.*?Tests: (\d+), Assertions: (\d+), Failures: (\d+)/s', $output, $matches)) {
        $status = 'FAIL';
        $tests = (int)$matches[1];
        $assertions = (int)$matches[2];
        $failures = (int)$matches[3];
    } elseif (preg_match('/ERRORS!.*?Tests: (\d+), Assertions: (\d+), Errors: (\d+)/s', $output, $matches)) {
        $status = 'ERROR';
        $tests = (int)$matches[1];
        $assertions = (int)$matches[2];
        $errors = (int)$matches[3];
    } elseif (preg_match('/No tests executed!/', $output)) {
        $status = 'NO_TESTS';
    }
    
    $results[$testFile] = [
        'status' => $status,
        'tests' => $tests,
        'assertions' => $assertions,
        'failures' => $failures,
        'errors' => $errors,
        'output_file' => $outputFile,
    ];
    
    // Display status
    if ($status === 'PASS') {
        echo "  ✓ PASS ({$tests} tests, {$assertions} assertions)\n";
    } elseif ($status === 'FAIL') {
        echo "  ✗ FAIL ({$failures} failures)\n";
    } elseif ($status === 'ERROR') {
        echo "  ✗ ERROR ({$errors} errors)\n";
    } elseif ($status === 'NO_TESTS') {
        echo "  ⚠ NO TESTS\n";
    } else {
        echo "  ? UNKNOWN\n";
    }
    
    echo "  Output saved to: {$outputFile}\n\n";
}

// Generate summary
echo "\n========================================\n";
echo "Summary\n";
echo "========================================\n\n";

$passed = 0;
$failed = 0;
$errored = 0;
$noTests = 0;
$notFound = 0;
$unknown = 0;

foreach ($results as $file => $result) {
    switch ($result['status']) {
        case 'PASS':
            $passed++;
            break;
        case 'FAIL':
            $failed++;
            break;
        case 'ERROR':
            $errored++;
            break;
        case 'NO_TESTS':
            $noTests++;
            break;
        case 'NOT_FOUND':
            $notFound++;
            break;
        default:
            $unknown++;
    }
}

echo "Total: {$totalTests}\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Errors: {$errored}\n";
echo "No Tests: {$noTests}\n";
echo "Not Found: {$notFound}\n";
echo "Unknown: {$unknown}\n";

// Save results to JSON
file_put_contents($outputDir . '/results.json', json_encode($results, JSON_PRETTY_PRINT));

echo "\nDetailed results saved to: {$outputDir}/results.json\n";
echo "All test outputs saved to: {$outputDir}/\n";

// Performance summary
$totalTime = 0;
foreach ($results as $result) {
    // Estimate time if available in output
}
echo "\nUsage: php tests/run_all_tests_one_by_one.php [--parallel] [--coverage] [--stress] [--load] [--fast] [--suite=<name>]\n";


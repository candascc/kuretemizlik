<?php
/**
 * Comprehensive Test Runner
 * Runs all tests with maximum coverage and detailed reporting
 */

require_once __DIR__ . '/bootstrap.php';

$testFiles = [
    // Unit Tests
    'unit/SessionHelperTest.php',
    'unit/ArrayAccessSafetyTest.php',
    'unit/ErrorHandlingTest.php',
    'unit/ExceptionHandlerTest.php',
    'unit/ViewExtractSafetyTest.php',
    'unit/RecurringOccurrenceMigrationTest.php',
    'unit/ValidatorSecurityTest.php',
    'unit/XssPreventionTest.php',
    'unit/TransactionRollbackTest.php',
    'unit/RateLimitingTest.php',
    'unit/FileUploadValidationTest.php',
    'unit/CsrfMiddlewareTest.php',
    'unit/PasswordResetSecurityTest.php',
    'unit/ControllerTraitTest.php',
    'unit/AppConstantsTest.php',
    'unit/ResidentLoginControllerTest.php',
    'unit/PortalLoginControllerTest.php',
    'unit/ResidentUserLookupTest.php',
    'unit/JobContractFlowTest.php',
    'unit/ContractTemplateSelectionTest.php',
    'unit/ResidentAuthValidationTest.php',
    'unit/ResidentOtpServiceFlowTest.php',
    'unit/ResponseFormatterTest.php',
    'unit/ResidentContactVerificationServiceTest.php',
    'unit/ResidentNotificationPreferenceServiceTest.php',
    'unit/ResidentPortalMetricsTest.php',
    'unit/ResidentPortalMetricsCacheTest.php',
    'unit/UtilsSanitizeTest.php',
    'unit/InputSanitizerTest.php',
    'unit/ControllerHelperTest.php',
    'unit/ErrorDetectorTest.php',
    'unit/SessionManagerTest.php',
    'unit/CrawlConfigTest.php',
    
    // Integration Tests
    'integration/SessionManagementTest.php',
    'integration/RecurringJobGenerationTest.php',
    'integration/SessionCookiePathTest.php',
    'integration/ControllerIntegrationTest.php',
    'integration/CrawlFlowTest.php',
    
    // Functional Tests
    'functional/ApiFeatureTest.php',
    'functional/JobCustomerFinanceFlowTest.php',
    'functional/RbacAccessTest.php',
    'functional/ResidentProfileTest.php',
    'functional/ResidentPaymentTest.php',
    'functional/ManagementResidentsTest.php',
    'functional/PaymentTransactionTest.php',
    'functional/AuthSessionTest.php',
    'functional/HeaderSecurityTest.php',
    
    // Security Tests
    'security/CsrfProtectionTest.php',
    'security/XssPreventionTest.php',
    'security/SqlInjectionTest.php',
    
    // Service Tests
    'CustomerOtpServiceTest.php',
    'ResidentOtpServiceTest.php',
    
    // Performance Tests
    'performance/PerformanceTest.php',
    
    // Other Tests
    'HeaderManagerTest.php',
];

$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'skipped' => 0,
    'errors' => [],
    'details' => []
];

echo "═══════════════════════════════════════════════════════════════════\n";
echo "     COMPREHENSIVE TEST SUITE - MAXIMUM COVERAGE\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

foreach ($testFiles as $testFile) {
    $fullPath = __DIR__ . '/' . $testFile;
    
    if (!file_exists($fullPath)) {
        echo "⚠ SKIP: {$testFile} (file not found)\n";
        $results['skipped']++;
        continue;
    }
    
    $results['total']++;
    echo "Running: {$testFile}...\n";
    
    // Capture output
    ob_start();
    $exitCode = 0;
    $output = '';
    
    try {
        // Run test file
        $startTime = microtime(true);
        include $fullPath;
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        $output = ob_get_clean();
        
        // Check if test has run() method or is standalone
        if (strpos($output, 'PASS') !== false || strpos($output, '✅') !== false) {
            $results['passed']++;
            echo "✅ PASS: {$testFile} ({$duration}ms)\n";
        } elseif (strpos($output, 'FAIL') !== false || strpos($output, '✗') !== false || $exitCode !== 0) {
            $results['failed']++;
            echo "✗ FAIL: {$testFile} ({$duration}ms)\n";
            $results['errors'][] = [
                'file' => $testFile,
                'output' => substr($output, 0, 500) // First 500 chars
            ];
        } else {
            // Try to run via PHPUnit if available
            if (class_exists('PHPUnit\Framework\TestCase')) {
                $phpunitOutput = shell_exec("cd " . escapeshellarg(__DIR__ . '/..') . " && php vendor/bin/phpunit " . escapeshellarg($fullPath) . " 2>&1");
                if (strpos($phpunitOutput, 'OK') !== false || strpos($phpunitOutput, 'PASS') !== false) {
                    $results['passed']++;
                    echo "✅ PASS: {$testFile} (via PHPUnit)\n";
                } else {
                    $results['failed']++;
                    echo "✗ FAIL: {$testFile} (via PHPUnit)\n";
                    $results['errors'][] = [
                        'file' => $testFile,
                        'output' => substr($phpunitOutput, 0, 500)
                    ];
                }
            } else {
                $results['skipped']++;
                echo "↩ SKIP: {$testFile} (no output detected)\n";
            }
        }
        
        $results['details'][] = [
            'file' => $testFile,
            'status' => strpos($output, 'PASS') !== false || strpos($output, '✅') !== false ? 'PASS' : (strpos($output, 'FAIL') !== false || strpos($output, '✗') !== false ? 'FAIL' : 'SKIP'),
            'duration' => $duration ?? 0
        ];
        
    } catch (Exception $e) {
        ob_end_clean();
        $results['failed']++;
        echo "✗ ERROR: {$testFile} - {$e->getMessage()}\n";
        $results['errors'][] = [
            'file' => $testFile,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    } catch (Error $e) {
        ob_end_clean();
        $results['failed']++;
        echo "✗ FATAL: {$testFile} - {$e->getMessage()}\n";
        $results['errors'][] = [
            'file' => $testFile,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    echo "\n";
}

// Summary
echo "═══════════════════════════════════════════════════════════════════\n";
echo "     TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "Total Tests: {$results['total']}\n";
echo "Passed: {$results['passed']}\n";
echo "Failed: {$results['failed']}\n";
echo "Skipped: {$results['skipped']}\n";
echo "Success Rate: " . ($results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 2) : 0) . "%\n";

if (!empty($results['errors'])) {
    echo "\n═══════════════════════════════════════════════════════════════════\n";
    echo "     ERRORS\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    foreach ($results['errors'] as $error) {
        echo "\nFile: {$error['file']}\n";
        if (isset($error['error'])) {
            echo "Error: {$error['error']}\n";
        }
        if (isset($error['output'])) {
            echo "Output: {$error['output']}\n";
        }
    }
}

// Save results
file_put_contents(__DIR__ . '/COMPREHENSIVE_TEST_RESULTS.json', json_encode($results, JSON_PRETTY_PRINT));

exit($results['failed'] > 0 ? 1 : 0);


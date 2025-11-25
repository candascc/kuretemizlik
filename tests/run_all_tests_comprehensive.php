<?php
/**
 * Comprehensive Test Runner
 * Runs all test files individually and generates detailed error reports
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define test categories
$testCategories = [
    'Phase 1' => [
        'tests/unit/SessionHelperTest.php',
        'tests/unit/ArrayAccessSafetyTest.php',
        'tests/unit/ErrorHandlingTest.php',
        'tests/unit/ExceptionHandlerTest.php',
        'tests/unit/ViewExtractSafetyTest.php',
        'tests/unit/RecurringOccurrenceMigrationTest.php',
        'tests/integration/SessionManagementTest.php',
        'tests/integration/RecurringJobGenerationTest.php',
        'tests/integration/SessionCookiePathTest.php',
    ],
    'Phase 2' => [
        'tests/unit/ValidatorSecurityTest.php',
        'tests/unit/XssPreventionTest.php',
        'tests/unit/TransactionRollbackTest.php',
        'tests/unit/RateLimitingTest.php',
        'tests/unit/FileUploadValidationTest.php',
        'tests/unit/CsrfMiddlewareTest.php',
        'tests/unit/PasswordResetSecurityTest.php',
    ],
    'Phase 4' => [
        'tests/unit/ControllerTraitTest.php',
        'tests/unit/AppConstantsTest.php',
    ],
    'Unit Tests (Other)' => [
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
    ],
    'Integration Tests (Other)' => [
        'tests/integration/ControllerIntegrationTest.php',
    ],
    'Functional Tests' => [
        'tests/functional/JobCustomerFinanceFlowTest.php',
        'tests/functional/RbacAccessTest.php',
        'tests/functional/ApiFeatureTest.php',
        'tests/functional/ResidentProfileTest.php',
        'tests/functional/ResidentPaymentTest.php',
        'tests/functional/ManagementResidentsTest.php',
        'tests/functional/PaymentTransactionTest.php',
        'tests/functional/AuthSessionTest.php',
        'tests/functional/HeaderSecurityTest.php',
    ],
    'Security Tests' => [
        'tests/security/XssPreventionTest.php',
        'tests/security/SqlInjectionTest.php',
        'tests/security/CsrfProtectionTest.php',
    ],
    'Performance Tests' => [
        'tests/performance/PerformanceTest.php',
    ],
    'Root Tests' => [
        'tests/ResidentOtpServiceTest.php',
        'tests/CustomerOtpServiceTest.php',
        'tests/HeaderManagerTest.php',
    ],
];

// Results storage
$results = [];
$errorCategories = [
    'Bootstrap' => [],
    'Database' => [],
    'Type' => [],
    'Assertion' => [],
    'Session/Auth' => [],
    'Reflection/Protected Method' => [],
    'XSS/Escape' => [],
    'Other' => [],
];

// Function to parse PHPUnit output
function parsePhpUnitOutput($output) {
    $result = [
        'status' => 'unknown',
        'tests' => 0,
        'assertions' => 0,
        'failures' => 0,
        'errors' => 0,
        'warnings' => 0,
        'skipped' => 0,
        'risky' => 0,
        'error_messages' => [],
        'failure_messages' => [],
    ];
    
    // Parse status
    if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $matches)) {
        $result['status'] = 'PASS';
        $result['tests'] = (int)$matches[1];
        $result['assertions'] = (int)$matches[2];
    } elseif (preg_match('/FAILURES!.*?Tests: (\d+), Assertions: (\d+), Failures: (\d+)/s', $output, $matches)) {
        $result['status'] = 'FAIL';
        $result['tests'] = (int)$matches[1];
        $result['assertions'] = (int)$matches[2];
        $result['failures'] = (int)$matches[3];
    } elseif (preg_match('/ERRORS!.*?Tests: (\d+), Assertions: (\d+), Errors: (\d+)/s', $output, $matches)) {
        $result['status'] = 'ERROR';
        $result['tests'] = (int)$matches[1];
        $result['assertions'] = (int)$matches[2];
        $result['errors'] = (int)$matches[3];
    }
    
    // Extract error messages
    if (preg_match_all('/✘ ([^\n]+)\n\s*│\s*\n\s*│\s*([^\n]+)/', $output, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $result['failure_messages'][] = [
                'test' => trim($match[1]),
                'error' => trim($match[2]),
            ];
        }
    }
    
    // Extract full error details
    if (preg_match_all('/Error: ([^\n]+)/', $output, $matches)) {
        $result['error_messages'] = array_merge($result['error_messages'], $matches[1]);
    }
    
    if (preg_match_all('/Exception: ([^\n]+)/', $output, $matches)) {
        $result['error_messages'] = array_merge($result['error_messages'], $matches[1]);
    }
    
    if (preg_match_all('/TypeError: ([^\n]+)/', $output, $matches)) {
        $result['error_messages'] = array_merge($result['error_messages'], $matches[1]);
    }
    
    if (preg_match_all('/Fatal error: ([^\n]+)/', $output, $matches)) {
        $result['error_messages'] = array_merge($result['error_messages'], $matches[1]);
    }
    
    return $result;
}

// Function to categorize errors
function categorizeError($errorMessage, $errorCategories) {
    $errorLower = strtolower($errorMessage);
    
    // Bootstrap errors
    if (preg_match('/class ["\']([^"\']+)["\'] not found/i', $errorMessage) ||
        preg_match('/trait ["\']([^"\']+)["\'] not found/i', $errorMessage) ||
        preg_match('/function ["\']([^"\']+)["\'] not found/i', $errorMessage) ||
        preg_match('/require_once.*failed to open/i', $errorMessage)) {
        return 'Bootstrap';
    }
    
    // Database errors
    if (preg_match('/SQLSTATE\[HY000\].*no such column/i', $errorMessage) ||
        preg_match('/SQLSTATE\[23000\].*Integrity constraint violation/i', $errorMessage) ||
        preg_match('/SQLSTATE\[HY000\].*no such table/i', $errorMessage) ||
        preg_match('/FOREIGN KEY constraint failed/i', $errorMessage)) {
        return 'Database';
    }
    
    // Type errors
    if (preg_match('/TypeError.*must be of type/i', $errorMessage) ||
        preg_match('/Failed asserting that.*is of type/i', $errorMessage) ||
        preg_match('/Cannot assign.*to property.*of type/i', $errorMessage)) {
        return 'Type';
    }
    
    // Assertion errors
    if (preg_match('/Failed asserting that/i', $errorMessage)) {
        return 'Assertion';
    }
    
    // Session/Auth errors
    if (preg_match('/SessionHelper.*not found/i', $errorMessage) ||
        preg_match('/Auth::check/i', $errorMessage) ||
        preg_match('/CSRF.*token/i', $errorMessage)) {
        return 'Session/Auth';
    }
    
    // Reflection errors
    if (preg_match('/Call to protected method/i', $errorMessage) ||
        preg_match('/Reflection/i', $errorMessage)) {
        return 'Reflection/Protected Method';
    }
    
    // XSS/Escape errors
    if (preg_match('/htmlspecialchars.*must be of type string.*array given/i', $errorMessage) ||
        preg_match('/XSS.*prevention/i', $errorMessage)) {
        return 'XSS/Escape';
    }
    
    return 'Other';
}

// Change to app directory
$appDir = __DIR__ . '/..';
chdir($appDir);

echo "Starting comprehensive test execution...\n";
echo "========================================\n\n";

$totalTests = 0;
$totalPassed = 0;
$totalFailed = 0;
$totalErrors = 0;

// Run tests by category
foreach ($testCategories as $category => $testFiles) {
    echo "\n=== Running {$category} Tests ===\n";
    
    foreach ($testFiles as $testFile) {
        $fullPath = $appDir . '/' . $testFile;
        
        if (!file_exists($fullPath)) {
            echo "⚠ Skipping {$testFile} (file not found)\n";
            continue;
        }
        
        echo "Running: {$testFile}... ";
        
        // Run PHPUnit
        $command = "php vendor/bin/phpunit \"{$testFile}\" --no-configuration 2>&1";
        $output = shell_exec($command);
        
        // Try to get exit code (works on Unix-like systems)
        $exitCode = 0;
        if (function_exists('exec')) {
            exec($command . '; echo $?', $execOutput, $exitCode);
            if (is_array($execOutput) && !empty($execOutput)) {
                $exitCode = (int)end($execOutput);
            }
        }
        
        // Parse output
        $parsed = parsePhpUnitOutput($output);
        $parsed['file'] = $testFile;
        $parsed['category'] = $category;
        $parsed['output'] = $output;
        $parsed['exit_code'] = $exitCode;
        
        // Categorize errors
        foreach ($parsed['error_messages'] as $errorMsg) {
            $category = categorizeError($errorMsg, $errorCategories);
            $errorCategories[$category][] = [
                'file' => $testFile,
                'message' => $errorMsg,
            ];
        }
        
        foreach ($parsed['failure_messages'] as $failure) {
            $category = categorizeError($failure['error'], $errorCategories);
            $errorCategories[$category][] = [
                'file' => $testFile,
                'test' => $failure['test'],
                'message' => $failure['error'],
            ];
        }
        
        $results[$testFile] = $parsed;
        
        // Update totals
        $totalTests++;
        if ($parsed['status'] === 'PASS') {
            $totalPassed++;
            echo "✓ PASS ({$parsed['tests']} tests, {$parsed['assertions']} assertions)\n";
        } elseif ($parsed['status'] === 'FAIL') {
            $totalFailed++;
            echo "✗ FAIL ({$parsed['failures']} failures)\n";
        } elseif ($parsed['status'] === 'ERROR') {
            $totalErrors++;
            echo "✗ ERROR ({$parsed['errors']} errors)\n";
        } else {
            echo "? UNKNOWN\n";
        }
    }
}

// Generate reports
echo "\n\n========================================\n";
echo "Generating reports...\n";

// Generate comprehensive error report
$report = "# Kapsamlı Test Hata Raporu\n";
$report .= "Tarih: " . date('Y-m-d H:i:s') . "\n";
$report .= "Toplam Test Dosyası: {$totalTests}\n";
$report .= "Başarılı: {$totalPassed}\n";
$report .= "Başarısız: {$totalFailed}\n";
$report .= "Hata: {$totalErrors}\n\n";

$report .= "## Kategori Bazında Hatalar\n\n";

foreach ($errorCategories as $category => $errors) {
    if (empty($errors)) {
        continue;
    }
    
    $report .= "### {$category} Hataları\n\n";
    foreach ($errors as $error) {
        $report .= "- **{$error['file']}**: {$error['message']}\n";
        if (isset($error['test'])) {
            $report .= "  - Test: {$error['test']}\n";
        }
    }
    $report .= "\n";
}

$report .= "## Test Bazında Detaylı Rapor\n\n";

foreach ($results as $file => $result) {
    $report .= "### " . basename($file, '.php') . "\n";
    $report .= "- Dosya: {$file}\n";
    $report .= "- Kategori: {$result['category']}\n";
    $report .= "- Durum: {$result['status']}\n";
    $report .= "- Test Sayısı: {$result['tests']}\n";
    $report .= "- Assertion Sayısı: {$result['assertions']}\n";
    
    if ($result['status'] !== 'PASS') {
        $report .= "- Başarısız: {$result['failures']}\n";
        $report .= "- Hatalar: {$result['errors']}\n";
        $report .= "- Hata Mesajları:\n";
        foreach ($result['error_messages'] as $msg) {
            $report .= "  - {$msg}\n";
        }
        foreach ($result['failure_messages'] as $failure) {
            $report .= "  - {$failure['test']}: {$failure['error']}\n";
        }
    }
    $report .= "\n";
}

file_put_contents(__DIR__ . '/COMPREHENSIVE_ERROR_REPORT.md', $report);

// Generate summary report
$summary = "# Test Çalıştırma Özeti\n";
$summary .= "Tarih: " . date('Y-m-d H:i:s') . "\n\n";
$summary .= "## Genel İstatistikler\n\n";
$summary .= "- Toplam Test Dosyası: {$totalTests}\n";
$summary .= "- Başarılı: {$totalPassed} (" . round($totalPassed / $totalTests * 100, 2) . "%)\n";
$summary .= "- Başarısız: {$totalFailed} (" . round($totalFailed / $totalTests * 100, 2) . "%)\n";
$summary .= "- Hata: {$totalErrors} (" . round($totalErrors / $totalTests * 100, 2) . "%)\n\n";

$summary .= "## Kategori Bazında Hata Dağılımı\n\n";
foreach ($errorCategories as $category => $errors) {
    $count = count($errors);
    if ($count > 0) {
        $summary .= "- **{$category}**: {$count} hata\n";
    }
}

$summary .= "\n## En Çok Hata Veren Test Dosyaları\n\n";
$failedTests = array_filter($results, function($r) {
    return $r['status'] !== 'PASS';
});
usort($failedTests, function($a, $b) {
    return ($b['failures'] + $b['errors']) - ($a['failures'] + $a['errors']);
});
foreach (array_slice($failedTests, 0, 10) as $file => $result) {
    $summary .= "- **{$file}**: {$result['failures']} failures, {$result['errors']} errors\n";
}

file_put_contents(__DIR__ . '/TEST_EXECUTION_SUMMARY.md', $summary);

// Generate priority fix list
$priority = "# Öncelikli Düzeltme Listesi\n\n";
$priority .= "## Kritik (Sistem Çökmesine Neden Olan Hatalar)\n\n";
// Add critical errors here

$priority .= "## Yüksek (Çok Sayıda Testi Etkileyen Hatalar)\n\n";
// Add high priority errors here

$priority .= "## Orta (Belirli Test Kategorilerini Etkileyen Hatalar)\n\n";
// Add medium priority errors here

$priority .= "## Düşük (Tekil Test Hataları)\n\n";
// Add low priority errors here

file_put_contents(__DIR__ . '/PRIORITY_FIX_LIST.md', $priority);

echo "Reports generated:\n";
echo "- COMPREHENSIVE_ERROR_REPORT.md\n";
echo "- TEST_EXECUTION_SUMMARY.md\n";
echo "- PRIORITY_FIX_LIST.md\n";
echo "\nDone!\n";


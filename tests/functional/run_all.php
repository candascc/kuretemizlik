<?php
/**
 * Functional Test Runner
 * 
 * Runs all functional tests and generates comprehensive report
 * 
 * Self-Audit Fix: Test runner for all functional tests
 */

require_once __DIR__ . '/PaymentTransactionTest.php';
require_once __DIR__ . '/AuthSessionTest.php';
require_once __DIR__ . '/HeaderSecurityTest.php';
require_once __DIR__ . '/ManagementResidentsTest.php';
require_once __DIR__ . '/ResidentProfileTest.php';
require_once __DIR__ . '/ResidentPaymentTest.php';
require_once __DIR__ . '/JobCustomerFinanceFlowTest.php';

ob_start();

class FunctionalTestRunner
{
    private $results = [];
    private $startTime;
    
    public function __construct()
    {
        $this->startTime = microtime(true);
    }
    
    /**
     * Run all functional tests
     */
    public function runAll()
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║         FUNCTIONAL TEST SUITE - Self-Audit Fix          ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Testing implementation fixes:\n";
        echo "- CRIT-007: Payment Transaction Atomicity\n";
        echo "- CRIT-005: Session Regeneration (4 flows)\n";
        echo "\n";
        echo "Started: " . date('Y-m-d H:i:s') . "\n";
        echo "\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        // Run payment transaction tests
        echo "┌─────────────────────────────────────────────────────────┐\n";
        echo "│  TEST SUITE 1: Payment Transaction Atomicity           │\n";
        echo "└─────────────────────────────────────────────────────────┘\n\n";
        
        $paymentTest = new PaymentTransactionTest();
        $paymentSuccess = $paymentTest->runAll();
        $this->results['payment'] = $paymentSuccess;
        
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        // Run auth session tests
        echo "┌─────────────────────────────────────────────────────────┐\n";
        echo "│  TEST SUITE 2: Authentication Session Regeneration     │\n";
        echo "└─────────────────────────────────────────────────────────┘\n\n";
        
        $authTest = new AuthSessionTest();
        $authSuccess = $authTest->runAll();
        $this->results['auth'] = $authSuccess;
        
        echo "═══════════════════════════════════════════════════════════\n\n";

        // Run header security tests
        echo "┌─────────────────────────────────────────────────────────┐\n";
        echo "│  TEST SUITE 3: Header Security Hardening               │\n";
        echo "└─────────────────────────────────────────────────────────┘\n\n";

        $headerTest = new HeaderSecurityTest();
        $headerSuccess = $headerTest->runAll();
        $this->results['header_security'] = $headerSuccess;

        echo "═══════════════════════════════════════════════════════════\n\n";

        // Run management residents tests
        $managementTest = new ManagementResidentsTest();
        $managementSuccess = $managementTest->runAll();
        $this->results['management_residents'] = $managementSuccess;

        echo "═══════════════════════════════════════════════════════════\n\n";

        // Job → Customer → Finance sync
        echo "┌─────────────────────────────────────────────────────────┐\n";
        echo "│  TEST SUITE 5: Job-Customer-Finance Sync               │\n";
        echo "└─────────────────────────────────────────────────────────┘\n\n";
        $flowTest = new JobCustomerFinanceFlowTest();
        $flowSuccess = $flowTest->runAll();
        $this->results['job_customer_finance'] = $flowSuccess;

        echo "═══════════════════════════════════════════════════════════\n\n";

        // Run resident profile tests
        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "┌─────────────────────────────────────────────────────────┐\n";
        echo "│  TEST SUITE 6: Resident Payment Flow                   │\n";
        echo "└─────────────────────────────────────────────────────────┘\n\n";

        $residentPaymentTest = new ResidentPaymentTest();
        $residentPaymentSuccess = $residentPaymentTest->runAll();
        $this->results['resident_payment'] = $residentPaymentSuccess;

        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "┌─────────────────────────────────────────────────────────┐\n";
        echo "│  TEST SUITE 7: Resident Profile Flow                   │\n";
        echo "└─────────────────────────────────────────────────────────┘\n\n";

        $residentProfileTest = new ResidentProfileTest();
        $profileSuccess = $residentProfileTest->runAll();
        $this->results['resident_profile'] = $profileSuccess;

        echo "═══════════════════════════════════════════════════════════\n\n";

        // Print final summary
        $this->printFinalSummary();
        
        return $this->allTestsPassed();
    }
    
    /**
     * Print final summary
     */
    private function printFinalSummary()
    {
        $duration = round(microtime(true) - $this->startTime, 2);
        
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║                    FINAL TEST REPORT                     ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        echo "Test Suites:\n";
        echo "------------\n";
        
        $passedSuites = 0;
        $totalSuites = count($this->results);
        
        foreach ($this->results as $suite => $passed) {
            $status = $passed ? '✅ PASS' : '❌ FAIL';
            $suiteName = ucfirst($suite);
            echo "{$status}  {$suiteName} Test Suite\n";
            
            if ($passed) {
                $passedSuites++;
            }
        }
        
        echo "\n";
        echo "Summary:\n";
        echo "--------\n";
        echo "Total Test Suites: {$totalSuites}\n";
        echo "Passed: {$passedSuites}\n";
        echo "Failed: " . ($totalSuites - $passedSuites) . "\n";
        echo "Success Rate: " . ($totalSuites > 0 ? round(($passedSuites / $totalSuites) * 100, 2) : 0) . "%\n";
        echo "Duration: {$duration}s\n";
        echo "\n";
        
        if ($this->allTestsPassed()) {
            echo "╔══════════════════════════════════════════════════════════╗\n";
            echo "║              ✅ ALL TESTS PASSED ✅                      ║\n";
            echo "╚══════════════════════════════════════════════════════════╝\n";
        } else {
            echo "╔══════════════════════════════════════════════════════════╗\n";
            echo "║              ❌ SOME TESTS FAILED ❌                     ║\n";
            echo "╚══════════════════════════════════════════════════════════╝\n";
        }
        
        echo "\n";
        echo "Completed: " . date('Y-m-d H:i:s') . "\n";
        echo "\n";
        
        // Generate JSON report
        $this->generateJsonReport($duration);
    }
    
    /**
     * Generate JSON report
     */
    private function generateJsonReport($duration)
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'duration' => $duration,
            'suites' => [],
            'overall_success' => $this->allTestsPassed()
        ];
        
        foreach ($this->results as $suite => $passed) {
            $report['suites'][] = [
                'name' => ucfirst($suite),
                'passed' => $passed
            ];
        }
        
        $jsonPath = __DIR__ . '/../../tests_results_functional.json';
        file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT));
        
        echo "JSON Report: {$jsonPath}\n";
    }
    
    /**
     * Check if all tests passed
     */
    private function allTestsPassed()
    {
        foreach ($this->results as $passed) {
            if (!$passed) {
                return false;
            }
        }
        return true;
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli') {
    $runner = new FunctionalTestRunner();
    $success = $runner->runAll();
    ob_end_flush();
    exit($success ? 0 : 1);
}


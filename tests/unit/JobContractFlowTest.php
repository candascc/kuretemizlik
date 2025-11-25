<?php
/**
 * Job Contract Flow Smoke Test
 * Basic smoke test for contract creation and OTP generation flow
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Models/Customer.php';
require_once __DIR__ . '/../../src/Models/Job.php';
require_once __DIR__ . '/../../src/Models/JobContract.php';
require_once __DIR__ . '/../../src/Models/ContractTemplate.php';
require_once __DIR__ . '/../../src/Models/ContractOtpToken.php';
require_once __DIR__ . '/../../src/Services/ContractTemplateService.php';
require_once __DIR__ . '/../../src/Services/ContractOtpService.php';

class JobContractFlowTest
{
    private $db;
    private $customerModel;
    private $jobModel;
    private $contractModel;
    private $otpTokenModel;
    private $templateModel;
    private $createdIds = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        
        // Initialize session for test environment
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set minimal user session for Auth::check() to pass
        // This is required for CompanyScope to work properly
        $_SESSION['user_id'] = 1;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['company_id'] = 1;
        $_SESSION['role'] = 'ADMIN'; // Required for Auth::role() to avoid warnings
        
        $this->customerModel = new Customer();
        $this->jobModel = new Job();
        $this->contractModel = new JobContract();
        $this->otpTokenModel = new ContractOtpToken();
        $this->templateModel = new ContractTemplate();
    }

    public function run(): array
    {
        $results = [
            'tests' => [],
            'passed' => 0,
            'failed' => 0
        ];

        try {
            // Test 1: Create default contract template
            $results['tests'][] = $this->testCreateDefaultTemplate();

            // Test 2: Create job contract
            $results['tests'][] = $this->testCreateJobContract();

            // Test 3: Create and send OTP
            $results['tests'][] = $this->testCreateAndSendOtp();

            // Cleanup
            $this->cleanup();

        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => 'Test Execution',
                'status' => 'FAILED',
                'message' => 'Exception: ' . $e->getMessage()
            ];
            $results['failed']++;
            $this->cleanup();
        }

        // Count passed/failed
        foreach ($results['tests'] as $test) {
            if ($test['status'] === 'PASSED') {
                $results['passed']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    private function testCreateDefaultTemplate(): array
    {
        try {
            $service = new ContractTemplateService();
            $template = $service->getDefaultCleaningJobTemplate();

            if ($template === null) {
                // Create a default template if none exists
                $templateId = $this->templateModel->create([
                    'type' => 'cleaning_job',
                    'name' => 'Default Cleaning Job Contract',
                    'version' => 1,
                    'template_text' => 'Temizlik işi sözleşmesi: {customer_name} - {job_date} - {job_price}',
                    'is_active' => 1,
                    'is_default' => 1,
                    'content_hash' => hash('sha256', 'default')
                ]);
                $template = $this->templateModel->find($templateId);
            }

            if ($template === null || empty($template['id'])) {
                return [
                    'name' => 'Create Default Template',
                    'status' => 'FAILED',
                    'message' => 'Could not create or retrieve default template'
                ];
            }

            return [
                'name' => 'Create Default Template',
                'status' => 'PASSED',
                'message' => "Template ID: {$template['id']}"
            ];

        } catch (Exception $e) {
            return [
                'name' => 'Create Default Template',
                'status' => 'FAILED',
                'message' => $e->getMessage()
            ];
        }
    }

    private function testCreateJobContract(): array
    {
        try {
            // Create test customer
            $customerId = $this->customerModel->create([
                'name' => 'Test Customer ' . time(),
                'phone' => '+905551234567',
                'email' => 'test' . time() . '@example.com'
            ]);
            
            if (!$customerId) {
                return [
                    'name' => 'Create Job Contract',
                    'status' => 'FAILED',
                    'message' => 'Failed to create test customer'
                ];
            }
            
            $this->createdIds['customer'] = $customerId;
            
            // Try to find customer - might fail due to CompanyScope, so try direct query
            $customer = $this->customerModel->find($customerId);
            if (!$customer) {
                // Try direct query if CompanyScope is blocking
                $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
            }
            
            if (!$customer) {
                return [
                    'name' => 'Create Job Contract',
                    'status' => 'FAILED',
                    'message' => 'Test customer not found after creation (ID: ' . $customerId . ')'
                ];
            }

            // Create test job
            $startAt = date('Y-m-d H:i:s', strtotime('+1 day'));
            $endAt = date('Y-m-d H:i:s', strtotime('+1 day +2 hours'));
            $jobId = $this->jobModel->create([
                'customer_id' => $customerId,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => 'SCHEDULED',
                'total_amount' => 150.00,
                'address_line' => 'Test Address',
                'service_id' => null
            ]);
            
            if (!$jobId) {
                return [
                    'name' => 'Create Job Contract',
                    'status' => 'FAILED',
                    'message' => 'Failed to create test job'
                ];
            }
            
            $this->createdIds['job'] = $jobId;
            
            // Try to find job - might fail due to CompanyScope, so try direct query
            $job = $this->jobModel->find($jobId);
            if (!$job) {
                // Try direct query if CompanyScope is blocking
                $job = $this->db->fetch("SELECT * FROM jobs WHERE id = ?", [$jobId]);
            }
            
            if (!$job) {
                return [
                    'name' => 'Create Job Contract',
                    'status' => 'FAILED',
                    'message' => 'Test job not found after creation (ID: ' . $jobId . ')'
                ];
            }

            // Create job contract
            try {
                $service = new ContractTemplateService();
                $contract = $service->createJobContractForJob($job, $customer);

                if ($contract === null || empty($contract['id'])) {
                    return [
                        'name' => 'Create Job Contract',
                        'status' => 'FAILED',
                        'message' => 'Contract creation returned null or empty'
                    ];
                }
            } catch (Exception $e) {
                return [
                    'name' => 'Create Job Contract',
                    'status' => 'FAILED',
                    'message' => 'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
                ];
            }

            if (empty($contract['contract_text'])) {
                return [
                    'name' => 'Create Job Contract',
                    'status' => 'FAILED',
                    'message' => 'Contract text is empty'
                ];
            }

            $this->createdIds['contract'] = $contract['id'];

            return [
                'name' => 'Create Job Contract',
                'status' => 'PASSED',
                'message' => "Contract ID: {$contract['id']}, Text length: " . strlen($contract['contract_text'])
            ];

        } catch (Exception $e) {
            return [
                'name' => 'Create Job Contract',
                'status' => 'FAILED',
                'message' => $e->getMessage()
            ];
        }
    }

    private function testCreateAndSendOtp(): array
    {
        try {
            if (empty($this->createdIds['contract'])) {
                return [
                    'name' => 'Create and Send OTP',
                    'status' => 'SKIPPED',
                    'message' => 'No contract ID available from previous test'
                ];
            }

            $contract = $this->contractModel->find($this->createdIds['contract']);
            $customer = $this->customerModel->find($this->createdIds['customer']);

            if (empty($contract) || empty($customer)) {
                return [
                    'name' => 'Create and Send OTP',
                    'status' => 'FAILED',
                    'message' => 'Contract or customer not found'
                ];
            }

            // Create OTP (note: SMS won't actually be sent in test, but service should work)
            $otpService = new ContractOtpService();
            $otpToken = $otpService->createAndSendOtp($contract, $customer, $customer['phone']);

            if (empty($otpToken['id'])) {
                return [
                    'name' => 'Create and Send OTP',
                    'status' => 'FAILED',
                    'message' => 'OTP token creation failed'
                ];
            }

            // Check if contract SMS count was updated
            $contractAfter = $this->contractModel->find($contract['id']);
            $smsCount = $contractAfter['sms_sent_count'] ?? 0;

            if ($smsCount < 1) {
                return [
                    'name' => 'Create and Send OTP',
                    'status' => 'FAILED',
                    'message' => 'SMS count was not incremented'
                ];
            }

            $this->createdIds['otp'] = $otpToken['id'];

            return [
                'name' => 'Create and Send OTP',
                'status' => 'PASSED',
                'message' => "OTP Token ID: {$otpToken['id']}, SMS Count: {$smsCount}"
            ];

        } catch (Exception $e) {
            return [
                'name' => 'Create and Send OTP',
                'status' => 'FAILED',
                'message' => $e->getMessage()
            ];
        }
    }

    private function cleanup(): void
    {
        // Clean up in reverse order
        if (!empty($this->createdIds['otp'])) {
            try {
                // ContractOtpToken doesn't have a delete() method, use direct database delete
                $this->db->delete('contract_otp_tokens', 'id = ?', [$this->createdIds['otp']]);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }

        if (!empty($this->createdIds['contract'])) {
            try {
                $this->contractModel->delete($this->createdIds['contract']);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }

        if (!empty($this->createdIds['job'])) {
            try {
                $this->jobModel->delete($this->createdIds['job']);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }

        if (!empty($this->createdIds['customer'])) {
            try {
                $this->customerModel->delete($this->createdIds['customer']);
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new JobContractFlowTest();
    $results = $test->run();

    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "        JOB CONTRACT FLOW SMOKE TEST RESULTS\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";

    foreach ($results['tests'] as $test) {
        $status = $test['status'];
        $icon = $status === 'PASSED' ? '✓' : ($status === 'SKIPPED' ? '○' : '✗');
        echo "{$icon} {$test['name']}: {$status}\n";
        echo "   {$test['message']}\n\n";
    }

    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "Summary: {$results['passed']} passed, {$results['failed']} failed\n";
    echo "═══════════════════════════════════════════════════════════════════\n";

    exit($results['failed'] > 0 ? 1 : 0);
}


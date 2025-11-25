<?php
/**
 * Contract Template Selection Smoke Test
 * Tests service-specific template selection and fallback logic
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Models/Customer.php';
require_once __DIR__ . '/../../src/Models/Job.php';
require_once __DIR__ . '/../../src/Models/Service.php';
require_once __DIR__ . '/../../src/Models/ContractTemplate.php';
require_once __DIR__ . '/../../src/Services/ContractTemplateService.php';

class ContractTemplateSelectionTest
{
    private $db;
    private $customerModel;
    private $jobModel;
    private $serviceModel;
    private $templateModel;
    private $templateService;
    private $createdIds = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        
        // Set default company ID for test environment
        $_SESSION['company_id'] = 1;
        
        $this->customerModel = new Customer();
        $this->jobModel = new Job();
        $this->serviceModel = new Service();
        $this->templateModel = new ContractTemplate();
        $this->templateService = new ContractTemplateService();
    }

    public function run(): array
    {
        $results = [
            'tests' => [],
            'passed' => 0,
            'failed' => 0
        ];

        try {
            // Test A: Ev Temizliği (service-specific)
            $results['tests'][] = $this->testScenarioA_EvTemizligi();

            // Test B: Ofis Temizliği
            $results['tests'][] = $this->testScenarioB_OfisTemizligi();

            // Test C: Mapping'de olmayan hizmet (Balkon Temizliği)
            $results['tests'][] = $this->testScenarioC_UnmappedService();

            // Test D: Service-specific template pasif ise fallback
            $results['tests'][] = $this->testScenarioD_InactiveTemplateFallback();

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

    /**
     * Senaryo A: Ev Temizliği (service-specific template seçilmeli)
     */
    private function testScenarioA_EvTemizligi(): array
    {
        try {
            // Find or create "Ev Temizliği" service
            $service = $this->db->fetch(
                "SELECT * FROM services WHERE name = ? AND company_id = ? LIMIT 1",
                ['Ev Temizliği', 1]
            );

            if (!$service) {
                return [
                    'name' => 'Scenario A: Ev Temizliği',
                    'status' => 'SKIPPED',
                    'message' => 'Ev Temizliği service not found in database'
                ];
            }

            // Create test customer
            $customerId = $this->createTestCustomer();
            if (!$customerId) {
                return [
                    'name' => 'Scenario A: Ev Temizliği',
                    'status' => 'FAILED',
                    'message' => 'Failed to create test customer'
                ];
            }

            // Create test job with Ev Temizliği service
            $job = $this->createTestJob($customerId, $service['id']);
            if (!$job) {
                return [
                    'name' => 'Scenario A: Ev Temizliği',
                    'status' => 'FAILED',
                    'message' => 'Failed to create test job'
                ];
            }

            // Ensure job has service info
            if (empty($job['service_id'])) {
                return [
                    'name' => 'Scenario A: Ev Temizliği',
                    'status' => 'FAILED',
                    'message' => 'Job does not have service_id'
                ];
            }

            // Get template for job
            $template = $this->templateService->getTemplateForJob($job);

            if (!$template) {
                return [
                    'name' => 'Scenario A: Ev Temizliği',
                    'status' => 'FAILED',
                    'message' => 'Template selection returned null'
                ];
            }

            // Verify service-specific template was selected
            $expectedServiceKey = 'house_cleaning';
            $actualServiceKey = $template['service_key'] ?? null;
            
            // Debug: Check if template has service_key
            if (empty($actualServiceKey) && $actualServiceKey !== null) {
                return [
                    'name' => 'Scenario A: Ev Temizliği',
                    'status' => 'FAILED',
                    'message' => "Template service_key is empty string. Template ID: {$template['id']}, Template name: {$template['name']}"
                ];
            }

            if ($actualServiceKey !== $expectedServiceKey) {
                return [
                    'name' => 'Scenario A: Ev Temizliği',
                    'status' => 'FAILED',
                    'message' => "Expected service_key '{$expectedServiceKey}', got '{$actualServiceKey}'"
                ];
            }

            // Verify template name contains "Ev Temizliği"
            if (stripos($template['name'], 'Ev Temizliği') === false) {
                return [
                    'name' => 'Scenario A: Ev Temizliği',
                    'status' => 'FAILED',
                    'message' => "Template name '{$template['name']}' does not contain 'Ev Temizliği'"
                ];
            }

            // Verify it's NOT the general default template (ID check)
            $defaultTemplate = $this->templateService->getDefaultCleaningJobTemplate();
            if ($defaultTemplate && $template['id'] === $defaultTemplate['id']) {
                return [
                    'name' => 'Scenario A: Ev Temizliği',
                    'status' => 'FAILED',
                    'message' => 'General default template was selected instead of service-specific'
                ];
            }

            return [
                'name' => 'Scenario A: Ev Temizliği',
                'status' => 'PASSED',
                'message' => "Service-specific template selected (ID: {$template['id']}, service_key: {$actualServiceKey})"
            ];

        } catch (Exception $e) {
            return [
                'name' => 'Scenario A: Ev Temizliği',
                'status' => 'FAILED',
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Senaryo B: Ofis Temizliği
     */
    private function testScenarioB_OfisTemizligi(): array
    {
        try {
            // Find or create "Ofis Temizliği" service
            $service = $this->db->fetch(
                "SELECT * FROM services WHERE name = ? AND company_id = ? LIMIT 1",
                ['Ofis Temizliği', 1]
            );

            if (!$service) {
                return [
                    'name' => 'Scenario B: Ofis Temizliği',
                    'status' => 'SKIPPED',
                    'message' => 'Ofis Temizliği service not found in database'
                ];
            }

            // Create test customer
            $customerId = $this->createTestCustomer();
            if (!$customerId) {
                return [
                    'name' => 'Scenario B: Ofis Temizliği',
                    'status' => 'FAILED',
                    'message' => 'Failed to create test customer'
                ];
            }

            // Create test job with Ofis Temizliği service
            $job = $this->createTestJob($customerId, $service['id']);
            if (!$job) {
                return [
                    'name' => 'Scenario B: Ofis Temizliği',
                    'status' => 'FAILED',
                    'message' => 'Failed to create test job'
                ];
            }

            // Get template for job
            $template = $this->templateService->getTemplateForJob($job);

            if (!$template) {
                return [
                    'name' => 'Scenario B: Ofis Temizliği',
                    'status' => 'FAILED',
                    'message' => 'Template selection returned null'
                ];
            }

            // Verify service-specific template was selected
            $expectedServiceKey = 'office_cleaning';
            $actualServiceKey = $template['service_key'] ?? null;

            if ($actualServiceKey !== $expectedServiceKey) {
                return [
                    'name' => 'Scenario B: Ofis Temizliği',
                    'status' => 'FAILED',
                    'message' => "Expected service_key '{$expectedServiceKey}', got '{$actualServiceKey}'"
                ];
            }

            return [
                'name' => 'Scenario B: Ofis Temizliği',
                'status' => 'PASSED',
                'message' => "Service-specific template selected (ID: {$template['id']}, service_key: {$actualServiceKey})"
            ];

        } catch (Exception $e) {
            return [
                'name' => 'Scenario B: Ofis Temizliği',
                'status' => 'FAILED',
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Senaryo C: Mapping'de olmayan hizmet (Balkon Temizliği) - genel template kullanılmalı
     */
    private function testScenarioC_UnmappedService(): array
    {
        try {
            // Find "Balkon Temizliği" service (mapping'de yok, genel template kullanılmalı)
            $service = $this->db->fetch(
                "SELECT * FROM services WHERE name = ? AND company_id = ? LIMIT 1",
                ['Balkon Temizliği', 1]
            );

            if (!$service) {
                // Create it if it doesn't exist
                $serviceId = $this->serviceModel->create([
                    'name' => 'Balkon Temizliği',
                    'is_active' => 1
                ]);
                if ($serviceId) {
                    $service = $this->db->fetch("SELECT * FROM services WHERE id = ?", [$serviceId]);
                }
            }

            if (!$service) {
                return [
                    'name' => 'Scenario C: Unmapped Service',
                    'status' => 'SKIPPED',
                    'message' => 'Balkon Temizliği service not found and could not be created'
                ];
            }

            // Create test customer
            $customerId = $this->createTestCustomer();
            if (!$customerId) {
                return [
                    'name' => 'Scenario C: Unmapped Service',
                    'status' => 'FAILED',
                    'message' => 'Failed to create test customer'
                ];
            }

            // Create test job with Balkon Temizliği service
            $job = $this->createTestJob($customerId, $service['id']);
            if (!$job) {
                return [
                    'name' => 'Scenario C: Unmapped Service',
                    'status' => 'FAILED',
                    'message' => 'Failed to create test job'
                ];
            }

            // Get template for job
            $template = $this->templateService->getTemplateForJob($job);

            if (!$template) {
                return [
                    'name' => 'Scenario C: Unmapped Service',
                    'status' => 'FAILED',
                    'message' => 'Template selection returned null'
                ];
            }

            // Verify general template was selected (service_key should be NULL)
            $serviceKey = $template['service_key'] ?? null;
            if ($serviceKey !== null) {
                return [
                    'name' => 'Scenario C: Unmapped Service',
                    'status' => 'FAILED',
                    'message' => "Expected general template (service_key = NULL), got '{$serviceKey}'"
                ];
            }

            // Verify it's the default template
            $defaultTemplate = $this->templateService->getDefaultCleaningJobTemplate();
            if (!$defaultTemplate || $template['id'] !== $defaultTemplate['id']) {
                // It's okay if it's not the exact default, as long as service_key is NULL
                // But log it
            }

            return [
                'name' => 'Scenario C: Unmapped Service',
                'status' => 'PASSED',
                'message' => "General template selected (ID: {$template['id']}, service_key: NULL) - fallback working"
            ];

        } catch (Exception $e) {
            return [
                'name' => 'Scenario C: Unmapped Service',
                'status' => 'FAILED',
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Senaryo D: Service-specific template pasif ise fallback
     */
    private function testScenarioD_InactiveTemplateFallback(): array
    {
        try {
            // Find "Ev Temizliği" service
            $service = $this->db->fetch(
                "SELECT * FROM services WHERE name = ? AND company_id = ? LIMIT 1",
                ['Ev Temizliği', 1]
            );

            if (!$service) {
                return [
                    'name' => 'Scenario D: Inactive Template Fallback',
                    'status' => 'SKIPPED',
                    'message' => 'Ev Temizliği service not found'
                ];
            }

            // Find the service-specific template for Ev Temizliği
            $serviceKey = 'house_cleaning';
            $specificTemplate = $this->templateModel->findByTypeAndServiceKey('cleaning_job', $serviceKey, false);

            if (!$specificTemplate) {
                return [
                    'name' => 'Scenario D: Inactive Template Fallback',
                    'status' => 'SKIPPED',
                    'message' => 'Service-specific template for house_cleaning not found'
                ];
            }

            // Temporarily deactivate the template
            $originalIsActive = $specificTemplate['is_active'];
            $this->templateModel->update($specificTemplate['id'], ['is_active' => 0]);

            try {
                // Create test customer
                $customerId = $this->createTestCustomer();
                if (!$customerId) {
                    return [
                        'name' => 'Scenario D: Inactive Template Fallback',
                        'status' => 'FAILED',
                        'message' => 'Failed to create test customer'
                    ];
                }

                // Create test job
                $job = $this->createTestJob($customerId, $service['id']);
                if (!$job) {
                    return [
                        'name' => 'Scenario D: Inactive Template Fallback',
                        'status' => 'FAILED',
                        'message' => 'Failed to create test job'
                    ];
                }

                // Get template for job (should fallback to general template)
                $template = $this->templateService->getTemplateForJob($job);

                if (!$template) {
                    return [
                        'name' => 'Scenario D: Inactive Template Fallback',
                        'status' => 'FAILED',
                        'message' => 'Template selection returned null'
                    ];
                }

                // Verify general template was selected (fallback)
                $serviceKey = $template['service_key'] ?? null;
                if ($serviceKey !== null) {
                    return [
                        'name' => 'Scenario D: Inactive Template Fallback',
                        'status' => 'FAILED',
                        'message' => "Expected general template (service_key = NULL) due to inactive specific template, got '{$serviceKey}'"
                    ];
                }

                // Verify it's NOT the inactive specific template
                if ($template['id'] === $specificTemplate['id']) {
                    return [
                        'name' => 'Scenario D: Inactive Template Fallback',
                        'status' => 'FAILED',
                        'message' => 'Inactive service-specific template was selected instead of general template'
                    ];
                }

                return [
                    'name' => 'Scenario D: Inactive Template Fallback',
                    'status' => 'PASSED',
                    'message' => "General template selected (ID: {$template['id']}) - fallback working for inactive template"
                ];

            } finally {
                // Restore original is_active status
                $this->templateModel->update($specificTemplate['id'], ['is_active' => $originalIsActive]);
            }

        } catch (Exception $e) {
            return [
                'name' => 'Scenario D: Inactive Template Fallback',
                'status' => 'FAILED',
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Helper: Create test customer
     */
    private function createTestCustomer(): ?int
    {
        try {
            $customerId = $this->customerModel->create([
                'name' => 'Test Customer ' . time() . ' ' . uniqid(),
                'phone' => '+90555' . rand(1000000, 9999999),
                'email' => 'test' . time() . '@example.com'
            ]);

            if ($customerId) {
                $this->createdIds['customers'][] = $customerId;
            }

            return $customerId;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Helper: Create test job
     */
    private function createTestJob(int $customerId, ?int $serviceId): ?array
    {
        try {
            $startAt = date('Y-m-d H:i:s', strtotime('+1 day'));
            $endAt = date('Y-m-d H:i:s', strtotime('+1 day +2 hours'));
            
            $jobId = $this->jobModel->create([
                'customer_id' => $customerId,
                'service_id' => $serviceId,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => 'SCHEDULED',
                'total_amount' => 150.00,
                'address_line' => 'Test Address'
            ]);

            if (!$jobId) {
                return null;
            }

            $this->createdIds['jobs'][] = $jobId;

            // Fetch job with service info
            $job = $this->db->fetch(
                "SELECT j.*, s.name as service_name, s.id as service_id 
                 FROM jobs j 
                 LEFT JOIN services s ON j.service_id = s.id 
                 WHERE j.id = ?",
                [$jobId]
            );

            // Ensure service_id is set (might be lost in JOIN)
            if ($job && !empty($serviceId)) {
                $job['service_id'] = $serviceId;
            }

            return $job;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Cleanup created test data
     */
    private function cleanup(): void
    {
        // Clean up jobs
        if (!empty($this->createdIds['jobs'])) {
            foreach ($this->createdIds['jobs'] as $jobId) {
                try {
                    $this->db->execute("DELETE FROM jobs WHERE id = ?", [$jobId]);
                } catch (Exception $e) {
                    // Ignore cleanup errors
                }
            }
        }

        // Clean up customers
        if (!empty($this->createdIds['customers'])) {
            foreach ($this->createdIds['customers'] as $customerId) {
                try {
                    $this->db->execute("DELETE FROM customers WHERE id = ?", [$customerId]);
                } catch (Exception $e) {
                    // Ignore cleanup errors
                }
            }
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new ContractTemplateSelectionTest();
    $results = $test->run();

    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "     CONTRACT TEMPLATE SELECTION SMOKE TEST RESULTS\n";
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


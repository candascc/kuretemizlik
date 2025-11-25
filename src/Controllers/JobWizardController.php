<?php
/**
 * Job Wizard Controller
 * UX-CRIT-001 Implementation
 * 
 * Handles 5-step job creation wizard
 */

class JobWizardController
{
    private $jobModel;
    private $customerModel;
    private $serviceModel;
    
    public function __construct()
    {
        $this->jobModel = new Job();
        $this->customerModel = new Customer();
        $this->serviceModel = new Service();
    }
    
    /**
     * Show wizard form
     */
    public function index()
    {
        Auth::require();
        
        // Get all customers with their addresses
        $customers = $this->customerModel->all();
        
        // Fetch addresses for each customer
        foreach ($customers as &$customer) {
            $customer['addresses'] = $this->customerModel->getAddresses($customer['id']);
        }
        
        // Get active services
        $services = $this->serviceModel->getActive();
        
        // Check if returning from customer creation
        $prefillCustomerId = $_GET['customer_id'] ?? null;
        $prefillCustomer = null;
        
        if ($prefillCustomerId) {
            $prefillCustomer = $this->customerModel->find($prefillCustomerId);
        }
        
        echo View::renderWithLayout('jobs/form-wizard', [
            'title' => 'Yeni İş - Wizard',
            'customers' => $customers,
            'services' => $services,
            'prefillCustomer' => $prefillCustomer,
            'job' => null
        ]);
    }
    
    /**
     * API: Get customer addresses
     */
    public function getCustomerAddresses($customerId)
    {
        header('Content-Type: application/json');
        
        try {
            $addresses = $this->customerModel->getAddresses($customerId);
            echo json_encode([
                'success' => true,
                'addresses' => $addresses
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle wizard submission (delegates to JobController)
     */
    public function submit()
    {
        // Use existing JobController::store() method
        // This ensures consistency with old form
        $jobController = new JobController();
        return $jobController->store();
    }
}


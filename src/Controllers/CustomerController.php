<?php

declare(strict_types=1);

/**
 * Customer Controller
 */

class CustomerController
{
    use CompanyScope;
    use ControllerTrait;

    private $customerModel;
    private $addressModel;
    
    public function __construct()
    {
        $this->customerModel = new Customer();
        $this->addressModel = new Address();
    }
    
    /**
     * Export customers
     */
    public function export()
    {
        Auth::requireCapability('customers.export');
        
        // Rate limiting for export
        $rateLimiter = new ApiRateLimiter();
        if (!$rateLimiter->check('export_customers_' . Auth::id())) {
            Utils::flash('error', 'Çok fazla export işlemi. Lütfen birkaç dakika sonra tekrar deneyin.');
            redirect(base_url('/customers'));
        }
        
        $filters = [
            'search' => InputSanitizer::string($_GET['search'] ?? null, 200),
        ];
        $companyFilter = $this->resolveCompanyFilter();
        if ($companyFilter !== null) {
            $filters['company_id'] = $companyFilter;
        }
        
        try {
            $content = ExportService::exportCustomers($filters);
            
            // Record successful export
            $rateLimiter->record('export_customers_' . Auth::id());
            
            $filename = 'customers_export_' . date('Y-m-d_His') . '.csv';
            
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            
            echo $content;
            exit;
        } catch (Exception $e) {
            // Phase 3.4: Use Logger if available
            if (class_exists('Logger')) {
                Logger::error("CustomerController::export() error: " . $e->getMessage());
            } else {
                error_log("Export error: " . $e->getMessage());
            }
            Utils::flash('error', 'Export işlemi sırasında bir hata oluştu. Lütfen tekrar deneyin.');
            redirect(base_url('/customers'));
        }
    }

    private function resolveCompanyFilter(): ?int
    {
        if (Auth::canSwitchCompany()) {
            $filter = InputSanitizer::int($_GET['company_filter'] ?? null, 1);
            if ($filter !== null) {
                return $filter;
            }
            return null;
        }

        return Auth::companyId();
    }

    public function index()
    {
        Auth::requireGroup('nav.operations.customers');
        
        // Prevent caching of customer list page
        if (!headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        
        $page = InputSanitizer::int($_GET['page'] ?? 1, AppConstants::MIN_PAGE, AppConstants::MAX_PAGE);
        $search = InputSanitizer::string($_GET['search'] ?? '', AppConstants::MAX_STRING_LENGTH_MEDIUM);
        
        // Phase 4.2: Use constant for pagination limit
        $limit = AppConstants::DEFAULT_PAGE_SIZE;
        $offset = ($page - 1) * $limit;
        
        // Arama
        if ($search) {
            $customers = $this->customerModel->search($search, $limit);
            $total = count($customers);
        } else {
            $customers = $this->customerModel->all($limit, $offset);
            $total = $this->customerModel->count();
        }

        $companies = $this->getCompanyOptions();
        
        // Sayfalama
        $pagination = Utils::paginate($total, $limit, $page);
        
        echo View::renderWithLayout('customers/list', [
            'customers' => $customers,
            'pagination' => $pagination,
            'search' => $search,
            'companies' => $companies,
            'companyFilter' => InputSanitizer::string($_GET['company_filter'] ?? '', 50),
            'flash' => Utils::getFlash()
        ]);
    }
    
    public function show($id)
    {
        Auth::require();
        
        // ===== PRODUCTION FIX: Prevent caching of customer detail page =====
        Utils::setNoCacheHeaders();
        // ===== PRODUCTION FIX END =====
        
        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            Utils::flash('error', 'Geçersiz müşteri ID.');
            redirect(base_url('/customers'));
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        $customer = $this->customerModel->findWithAddresses($id);
        if (!$customer) {
            Utils::flash('error', 'Müşteri bulunamadı.');
            redirect(base_url('/customers'));
            return;
        }
        
        // Müşteri işlerini getir
        $jobs = $this->customerModel->getJobs($id, 10);
        $jobCount = $this->customerModel->getJobCount($id);
        
        // Finans özeti
        $moneyModel = new MoneyEntry();
        $totals = $moneyModel->getTotalsByCustomer($id);
        
        echo View::renderWithLayout('customers/show', [
            'customer' => $customer,
            'jobs' => $jobs,
            'jobCount' => $jobCount,
            'totals' => $totals,
            'flash' => Utils::getFlash()
        ]);
    }
    
    public function create()
    {
        Auth::requireCapability('customers.create');
        
        // ===== PRODUCTION FIX: Prevent caching of customer create form =====
        Utils::setNoCacheHeaders();
        // ===== PRODUCTION FIX END =====
        
        echo View::renderWithLayout('customers/form', [
            'customer' => null,
            'flash' => Utils::getFlash()
        ]);
    }
    
    public function store()
    {
        // ===== CRITICAL FIX: Auth::requireCapability() now handles session initialization =====
        // No need to start session here - Auth::requireCapability() handles it
        Auth::requireCapability('customers.create');
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/customers')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/customers/new')) {
            return;
        }
        // ===== ERR-026 FIX: End =====
        
        // Validasyon
        $validator = new Validator($_POST);
        $validator->required('name', 'Müşteri adı zorunludur')
                 ->max('name', 100, 'Müşteri adı en fazla 100 karakter olabilir')
                 ->phone('phone', 'Geçerli bir telefon numarası girin')
                 ->email('email', 'Geçerli bir email adresi girin')
                 ->max('notes', 500, 'Notlar en fazla 500 karakter olabilir');
        
        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            redirect(base_url('/customers/new'));
        }
        
        // Telefon numarası kontrolü
        $phone = $validator->get('phone');
        if ($phone && $this->customerModel->findByPhone($phone)) {
            ControllerHelper::flashErrorAndRedirect('Bu telefon numarası zaten kayıtlı.', '/customers/new');
            return;
        }
        
        // Email kontrolü
        $email = $validator->get('email');
        if ($email && $this->customerModel->findByEmail($email)) {
            ControllerHelper::flashErrorAndRedirect('Bu email adresi zaten kayıtlı.', '/customers/new');
            return;
        }
        
        // Adresleri hazırla
        $addresses = [];
        if (!empty($_POST['addresses']) && is_array($_POST['addresses'])) {
            foreach ($_POST['addresses'] as $address) {
                if (!empty($address['line'])) {
                    $addresses[] = [
                        'label' => InputSanitizer::string($address['label'] ?? null, 50),
                        'line' => InputSanitizer::string($address['line'] ?? '', 500),
                        'city' => InputSanitizer::string($address['city'] ?? null, 100)
                    ];
                }
            }
        }
        
        // Müşteri oluştur
        $customerData = [
            'name' => $validator->get('name'),
            'phone' => $validator->get('phone') ?: null,
            'email' => $validator->get('email') ?: null,
            'notes' => $validator->get('notes') ?: null,
            'addresses' => $addresses
        ];
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            $customerId = $this->customerModel->create($customerData);
            
            // Aktivite log
            ActivityLogger::customerCreated($customerId, $customerData['name']);
        
            // ===== ERR-018 FIX: Add audit logging =====
            AuditLogger::getInstance()->logDataModification('CUSTOMER_CREATED', Auth::id(), [
                'customer_id' => $customerId,
                'customer_name' => $customerData['name'],
                'phone' => $customerData['phone'] ?? null,
                'email' => $customerData['email'] ?? null
            ]);            // ===== ERR-018 FIX: End =====
            
            ControllerHelper::flashSuccessAndRedirect('Müşteri başarıyla oluşturuldu.', '/customers');
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'CustomerController::store()', 'Müşteri oluşturulurken bir hata oluştu', '/customers');
        }
        // ===== ERR-010 FIX: End =====
    }
    
    public function edit($id)
    {
        Auth::require();
        
        // ===== PRODUCTION FIX: Prevent caching of customer edit form =====
        Utils::setNoCacheHeaders();
        // ===== PRODUCTION FIX END =====
        
        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            Utils::flash('error', 'Geçersiz müşteri ID.');
            redirect(base_url('/customers'));
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        $customer = $this->customerModel->findWithAddresses($id);
        if (!$customer) {
            error_log("Customer edit: Customer not found with ID: $id");
            View::notFound('Müşteri bulunamadı');
        }
        
        echo View::renderWithLayout('customers/form', [
            'customer' => $customer,
            'flash' => Utils::getFlash()
        ]);
    }
    
    public function update($id)
    {
        Auth::requireCapability('customers.edit');
        
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            View::notFound('Müşteri bulunamadı');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/customers'));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url("/customers/edit/$id"));
        }
        
        // Validasyon
        $validator = new Validator($_POST);
        $validator->required('name', 'Müşteri adı zorunludur')
                 ->max('name', 100, 'Müşteri adı en fazla 100 karakter olabilir')
                 ->phone('phone', 'Geçerli bir telefon numarası girin')
                 ->email('email', 'Geçerli bir email adresi girin')
                 ->max('notes', 500, 'Notlar en fazla 500 karakter olabilir');
        
        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            redirect(base_url("/customers/edit/$id"));
        }
        
        // Değişiklikleri takip et
        $changes = [];
        if ($customer['name'] != $validator->get('name')) {
            $changes['name'] = $validator->get('name');
        }
        if ($customer['phone'] != $validator->get('phone')) {
            $changes['phone'] = $validator->get('phone');
        }
        if ($customer['email'] != $validator->get('email')) {
            $changes['email'] = $validator->get('email');
        }
        
        // Adresleri hazırla
        $addresses = [];
        if (!empty($_POST['addresses']) && is_array($_POST['addresses'])) {
            foreach ($_POST['addresses'] as $address) {
                if (!empty($address['line'])) {
                    $addresses[] = [
                        'label' => InputSanitizer::string($address['label'] ?? null, 50),
                        'line' => InputSanitizer::string($address['line'] ?? '', 500),
                        'city' => InputSanitizer::string($address['city'] ?? null, 100)
                    ];
                }
            }
        }
        
        // Müşteri güncelle
        $customerData = [
            'name' => $validator->get('name'),
            'phone' => $validator->get('phone') ?: null,
            'email' => $validator->get('email') ?: null,
            'notes' => $validator->get('notes') ?: null,
            'addresses' => $addresses
        ];
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            $this->customerModel->update($id, $customerData);
            
            // Aktivite log
            if (!empty($changes)) {
                ActivityLogger::customerUpdated($id, $changes);
            }
        
            // ===== ERR-018 FIX: Add audit logging =====
            AuditLogger::getInstance()->logDataModification('CUSTOMER_UPDATED', Auth::id(), [
                'customer_id' => $id,
                'customer_name' => $customer['name'] ?? null,
                'changes' => $changes
            ]);            // ===== ERR-018 FIX: End =====
            
            Utils::flash('success', 'Müşteri başarıyla güncellendi.');
        } catch (Exception $e) {
            error_log("CustomerController::update() error: " . $e->getMessage());
            Utils::flash('error', 'Müşteri güncellenirken bir hata oluştu: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
        }
        // ===== ERR-010 FIX: End =====
        
        redirect(base_url('/customers'));
    }

    // Inline Adres: Ekle
    public function addAddress($customerId)
    {
        Auth::require();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(base_url("/customers/show/$customerId"));
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url("/customers/show/$customerId"));
        }
        $validator = new Validator($_POST);
        $validator->required('line', 'Adres satırı zorunludur');
        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            redirect(base_url("/customers/show/$customerId"));
        }
        try {
            (new Address())->create([
                'customer_id' => $customerId,
                'label' => $validator->get('label'),
                'line' => $validator->get('line'),
                'city' => $validator->get('city')
            ]);
            Utils::flash('success', 'Adres eklendi.');
            redirect(base_url("/customers/show/$customerId"));
        } catch (Exception $e) {
            Logger::error('Address creation failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Utils::flash('error', 'Adres eklenirken bir hata oluştu: ' . Utils::safeExceptionMessage($e));
            redirect(base_url("/customers/show/$customerId"));
        }
    }

    // Inline Adres: Güncelle
    public function updateAddress($addressId)
    {
        Auth::require();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(base_url('/customers'));
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url('/customers'));
        }
        $addressModel = new Address();
        $addr = $addressModel->find($addressId);
        if (!$addr) View::notFound('Adres bulunamadı');
        $validator = new Validator($_POST);
        $validator->required('line', 'Adres satırı zorunludur');
        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            redirect(base_url("/customers/show/{$addr['customer_id']}"));
        }
        try {
            $addressModel->update($addressId, [
                'label' => $validator->get('label'),
                'line' => $validator->get('line'),
                'city' => $validator->get('city')
            ]);
            Utils::flash('success', 'Adres güncellendi.');
            redirect(base_url("/customers/show/{$addr['customer_id']}"));
        } catch (Exception $e) {
            Logger::error('Address update failed', [
                'address_id' => $addressId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Utils::flash('error', 'Adres güncellenirken bir hata oluştu: ' . Utils::safeExceptionMessage($e));
            redirect(base_url("/customers/show/{$addr['customer_id']}"));
        }
    }

    // Inline Adres: Sil
    public function deleteAddress($addressId)
    {
        Auth::require();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(base_url('/customers'));
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url('/customers'));
        }
        $addressModel = new Address();
        $addr = $addressModel->find($addressId);
        if (!$addr) View::notFound('Adres bulunamadı');
        $addressModel->delete($addressId);
        
        // ===== ERR-018 FIX: Add audit logging =====
        AuditLogger::getInstance()->logDataModification('ADDRESS_DELETED', Auth::id(), [
            'address_id' => $addressId,
            'customer_id' => $addr['customer_id'],
            'address_line' => $addr['line'] ?? null
        ]);
        // ===== ERR-018 FIX: End =====
        
        Utils::flash('success', 'Adres silindi.');
        redirect(base_url("/customers/show/{$addr['customer_id']}"));
    }
    
    public function delete($id)
    {
        error_log("CustomerController::delete() - METHOD CALLED with ID: " . var_export($id, true));
        error_log("CustomerController::delete() - REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET'));
        error_log("CustomerController::delete() - POST data: " . var_export($_POST, true));
        
        Auth::requireCapability('customers.delete');
        error_log("CustomerController::delete() - Auth check passed");
        
        // Validate ID first
        $id = ControllerHelper::validateId($id);
        error_log("CustomerController::delete() - ID validated: " . var_export($id, true));
        if (!$id) {
            error_log("CustomerController::delete() - Invalid ID, redirecting");
            Utils::flash('error', 'Geçersiz müşteri ID.');
            redirect(base_url('/customers'));
            return;
        }
        
        // Check POST method
        error_log("CustomerController::delete() - Checking POST method");
        if (!ControllerHelper::requirePostOrRedirect('/customers')) {
            error_log("CustomerController::delete() - POST check failed, redirecting");
            return;
        }
        error_log("CustomerController::delete() - POST check passed");
        
        // CSRF kontrolü
        error_log("CustomerController::delete() - Checking CSRF");
        if (!ControllerHelper::verifyCsrfOrRedirect('/customers')) {
            error_log("CustomerController::delete() - CSRF check failed, redirecting");
            return;
        }
        error_log("CustomerController::delete() - CSRF check passed");
        
        // Müşteri bul - company scope kontrolü burada yapılıyor
        error_log("CustomerController::delete() - Finding customer with ID: {$id}");
        $customer = $this->customerModel->find($id);
        error_log("CustomerController::delete() - Customer find result: " . var_export($customer ? 'FOUND' : 'NOT FOUND', true));
        if (!$customer) {
            error_log("CustomerController::delete() - Customer not found, redirecting");
            Utils::flash('error', 'Müşteri bulunamadı veya bu müşteriye erişim yetkiniz yok.');
            redirect(base_url('/customers'));
            return;
        }
        
        // Debug: Müşteri bilgilerini logla
        $companyId = $customer['company_id'] ?? 'N/A';
        error_log("CustomerController::delete() - Customer found: ID={$id}, Name={$customer['name']}, Company_ID=" . $companyId);
        
        // Müşteri sil
        try {
            // ===== PRODUCTION FIX: Check for related records before deletion =====
            $db = Database::getInstance();
            $jobCount = $db->fetch(
                "SELECT COUNT(*) as count FROM jobs WHERE customer_id = ?",
                [$id]
            )['count'] ?? 0;
            
            $contractCount = $db->fetch(
                "SELECT COUNT(*) as count FROM job_contracts jc 
                 INNER JOIN jobs j ON jc.job_id = j.id 
                 WHERE j.customer_id = ?",
                [$id]
            )['count'] ?? 0;
            
            error_log("CustomerController::delete() - About to delete customer ID={$id}, JobCount={$jobCount}, ContractCount={$contractCount}");
            
            $deleted = $this->customerModel->delete($id);
            
            error_log("CustomerController::delete() - Delete result: " . ($deleted ? 'SUCCESS' : 'FAILED'));
            
            if (!$deleted) {
                error_log("CustomerController::delete() - Delete returned false/0 for customer ID={$id}");
                throw new Exception('Müşteri silinemedi. Veritabanı hatası.');
            }
            
            $message = 'Müşteri başarıyla silindi.';
            if ($jobCount > 0) {
                $message .= " ({$jobCount} iş ve {$contractCount} sözleşme de silindi.)";
            }
            
            // Aktivite log
            try {
                ActivityLogger::customerDeleted($id, $customer['name'] ?? 'Bilinmeyen');
                
                // ===== ERR-018 FIX: Add audit logging =====
                AuditLogger::getInstance()->logDataModification('CUSTOMER_DELETED', Auth::id(), [
                    'customer_id' => $id,
                    'customer_name' => $customer['name'] ?? 'Bilinmeyen',
                    'related_jobs_count' => $jobCount,
                    'related_contracts_count' => $contractCount
                ]);
                // ===== ERR-018 FIX: End =====
            } catch (Exception $logError) {
                error_log("ActivityLogger::customerDeleted failed: " . $logError->getMessage());
            }
            
            Utils::flash('success', $message);
            
            // ===== PRODUCTION FIX: Prevent cache and ensure fresh data =====
            // Set cache control headers before redirect to prevent browser from caching the redirect response
            if (!headers_sent()) {
                header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                header('ETag: "' . md5(time() . rand()) . '"');
            }
            
            // Ensure session is written before redirect
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Add cache-busting parameter to redirect URL to force fresh page load
            $redirectUrl = base_url('/customers') . '?_=' . time();
            // ===== PRODUCTION FIX END =====
            
            redirect($redirectUrl);
            
        } catch (Exception $e) {
            // Log the error for debugging
            error_log("CustomerController::delete() error for ID {$id}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Show user-friendly error message
            $errorMessage = $e->getMessage();
            if (stripos($errorMessage, 'foreign key') !== false || 
                stripos($errorMessage, 'constraint') !== false) {
                Utils::flash('error', 'Bu müşteri silinemiyor çünkü ilişkili kayıtlar var. Lütfen önce ilişkili işleri ve sözleşmeleri kontrol edin.');
            } elseif (stripos($errorMessage, 'yetkiniz yok') !== false || 
                       stripos($errorMessage, 'yetkiniz') !== false) {
                Utils::flash('error', 'Bu müşteriyi silme yetkiniz yok.');
            } elseif (stripos($errorMessage, 'bulunamadı') !== false) {
                Utils::flash('error', 'Müşteri bulunamadı.');
            } else {
                $userMessage = 'Müşteri silinirken bir hata oluştu.';
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    $userMessage .= ' ' . htmlspecialchars($errorMessage);
                }
                Utils::flash('error', $userMessage);
            }
            
            redirect(base_url('/customers'));
        }
    }
    
    // ===== KOZMOS_BULK_OPERATIONS: bulk operations methods (begin)
    public function bulkDelete()
    {
        Auth::require();
        if (Auth::role() === 'OPERATOR') { View::forbidden(); }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::error('Geçersiz istek', 405);
        }
        
        $customerIds = InputSanitizer::array($_POST['customer_ids'] ?? [], function($id) {
            return InputSanitizer::int($id, 1);
        });
        
        // Filter out null values
        $customerIds = array_filter($customerIds, function($id) {
            return $id !== null;
        });
        
        if (empty($customerIds)) {
            Utils::flash('error', 'Lütfen en az bir müşteri seçin.');
            redirect(base_url('/customers'));
        }
        
        $db = Database::getInstance();
        $deletedCount = 0;
        
        try {
            $db->beginTransaction();
            
            foreach ($customerIds as $customerId) {
                $customer = $this->customerModel->find($customerId);
                if (!$customer) continue;
                
                // İlişkili adresleri sil
                $this->addressModel->deleteByCustomerId($customerId);
                
                // Müşteriyi sil
                if ($this->customerModel->delete($customerId)) {
                    $deletedCount++;
                    ActivityLogger::customerDeleted($customerId, $customer['name']);
                }
            }
            
            $db->commit();
            
            if ($deletedCount > 0) {
                Utils::flash('success', "{$deletedCount} müşteri başarıyla silindi.");
            } else {
                Utils::flash('error', 'Hiçbir müşteri silinemedi.');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            ActivityLogger::log('ERROR', 'customers', [
                'action' => 'bulk_delete',
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            Utils::flash('error', 'Toplu silme sırasında hata oluştu.');
        }
        
        redirect(base_url('/customers'));
    }
    // ===== KOZMOS_BULK_OPERATIONS: bulk operations methods (end)
}

<?php

declare(strict_types=1);

/**
 * Contract Controller
 * 
 * Handles contract-related operations including CRUD operations
 * and contract management.
 * 
 * @package App\Controllers
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/../Constants/AppConstants.php';
require_once __DIR__ . '/../Lib/ControllerHelper.php';

class ContractController
{
    /** @var Contract $contractModel */
    private $contractModel;
    
    /** @var Customer $customerModel */
    private $customerModel;
    
    /** @var User $userModel */
    private $userModel;

    /**
     * ContractController constructor
     * Initializes required models
     */
    public function __construct()
    {
        $this->contractModel = new Contract();
        $this->customerModel = new Customer();
        $this->userModel = new User();
    }

    /**
     * Display contract list with filters and pagination
     * 
     * @return void
     */
    public function index()
    {
        Auth::require();
        
        // ===== PRODUCTION FIX: Prevent caching of contract list page =====
        Utils::setNoCacheHeaders();
        // ===== PRODUCTION FIX END =====
        
        $page = InputSanitizer::int($_GET['page'] ?? 1, AppConstants::MIN_PAGE, AppConstants::MAX_PAGE);
        $status = InputSanitizer::string($_GET['status'] ?? '', AppConstants::MAX_STRING_LENGTH_SHORT);
        $contractType = InputSanitizer::string($_GET['contract_type'] ?? '', AppConstants::MAX_STRING_LENGTH_SHORT);
        $customer = InputSanitizer::string($_GET['customer'] ?? '', AppConstants::MAX_STRING_LENGTH_MEDIUM);
        $dateFrom = InputSanitizer::date($_GET['date_from'] ?? '', AppConstants::DATE_FORMAT);
        $dateTo = InputSanitizer::date($_GET['date_to'] ?? '', AppConstants::DATE_FORMAT);
        $expiringSoon = InputSanitizer::int($_GET['expiring_soon'] ?? null, AppConstants::VALIDATION_MIN_ID);

        $limit = AppConstants::DEFAULT_PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($status) {
            $where[] = "c.status = ?";
            $params[] = $status;
        }

        if ($contractType) {
            $where[] = "c.contract_type = ?";
            $params[] = $contractType;
        }

        if ($customer) {
            $where[] = "cust.name LIKE ?";
            $params[] = "%$customer%";
        }

        if ($dateFrom) {
            $where[] = "DATE(c.start_date) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "DATE(c.start_date) <= ?";
            $params[] = $dateTo;
        }

        if ($expiringSoon) {
            $days = (int)$expiringSoon;
            $futureDate = date('Y-m-d', strtotime("+{$days} days"));
            $where[] = "c.end_date IS NOT NULL AND c.end_date <= ? AND c.status = 'ACTIVE'";
            $params[] = $futureDate;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "
            SELECT COUNT(*) as count
            FROM contracts c
            LEFT JOIN customers cust ON c.customer_id = cust.id
            LEFT JOIN users u ON c.created_by = u.id
            $whereClause
        ";
        $db = Database::getInstance();
        $total = $db->fetch($countSql, $params)['count'];

        $sql = "
            SELECT
                c.*,
                cust.name AS customer_name,
                cust.phone AS customer_phone,
                u.username AS created_by_user
            FROM contracts c
            LEFT JOIN customers cust ON c.customer_id = cust.id
            LEFT JOIN users u ON c.created_by = u.id
            $whereClause
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        $contracts = $db->fetchAll($sql, $params);
        $pagination = Utils::paginate($total, $limit, $page);
        $customers = $this->customerModel->all();
        $stats = $this->contractModel->getStats();

        echo View::renderWithLayout('contracts/list', [
            'contracts' => $contracts,
            'pagination' => $pagination,
            'customers' => $customers,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'contract_type' => $contractType,
                'customer' => $customer,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'expiring_soon' => $expiringSoon,
            ],
            'statuses' => Contract::getStatuses(),
            'types' => Contract::getTypes(),
            'flash' => Utils::getFlash()
        ]);
    }

    /**
     * Yeni sözleşme formu
     */
    public function create()
    {
        Auth::require();
        
        $customers = $this->customerModel->all();
        $contractNumber = $this->contractModel->generateContractNumber();
        
        echo View::renderWithLayout('contracts/form', [
            'contract' => null,
            'customers' => $customers,
            'contract_number' => $contractNumber,
            'statuses' => Contract::getStatuses(),
            'types' => Contract::getTypes(),
            'flash' => Utils::getFlash()
        ]);
    }

    /**
     * Sözleşme kaydet
     */
    public function store()
    {
        Auth::require();
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/contracts')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/contracts/new')) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        $data = [
            'customer_id' => $_POST['customer_id'] ?? null,
            'contract_number' => $_POST['contract_number'] ?? '',
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'contract_type' => $_POST['contract_type'] ?? 'CLEANING',
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? null,
            'total_amount' => $_POST['total_amount'] ?? null,
            'payment_terms' => $_POST['payment_terms'] ?? null,
            'status' => $_POST['status'] ?? 'DRAFT',
            'auto_renewal' => isset($_POST['auto_renewal']) ? 1 : 0,
            'renewal_period_days' => $_POST['renewal_period_days'] ?? null,
            'notes' => $_POST['notes'] ?? null,
            'created_by' => Auth::user()['id']
        ];

        // Validasyon
        $errors = [];
        if (empty($data['customer_id'])) {
            $errors[] = 'Müşteri seçimi zorunludur.';
        }
        if (empty($data['contract_number'])) {
            $errors[] = 'Sözleşme numarası zorunludur.';
        }
        if (empty($data['title'])) {
            $errors[] = 'Başlık zorunludur.';
        }
        if (empty($data['start_date'])) {
            $errors[] = 'Başlangıç tarihi zorunludur.';
        }

        if (!empty($errors)) {
            ControllerHelper::flashErrorAndRedirect(implode('<br>', $errors), '/contracts/new');
            return;
        }

        try {
            $contractId = $this->contractModel->create($data);
            
            // Dosya yükleme işlemi
            if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
                $this->handleFileUpload($contractId, $_FILES['contract_file']);
            }
            
            // Aktivite logla
            ActivityLogger::log('contract_created', 'contract', [
                'contract_id' => $contractId,
                'customer_id' => $data['customer_id'],
                'title' => $data['title']
            ]);
            
            ControllerHelper::flashSuccessAndRedirect('Sözleşme başarıyla oluşturuldu.', '/contracts');
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'ContractController::store()', 'Sözleşme oluşturulurken bir hata oluştu', '/contracts/new');
        }
    }

    /**
     * Sözleşme detayı
     */
    public function show($id)
    {
        Auth::require();
        
        if (!$id || !is_numeric($id)) {
            error_log("Contract show: Invalid ID: $id");
            View::notFound('Geçersiz sözleşme ID');
        }
        
        $contract = $this->contractModel->find($id);
        if (!$contract) {
            error_log("Contract show: Contract not found with ID: $id");
            View::notFound('Sözleşme bulunamadı');
        }
        
        $payments = $this->contractModel->getPayments($id);
        $attachments = $this->contractModel->getAttachments($id);
        
        echo View::renderWithLayout('contracts/show', [
            'contract' => $contract,
            'payments' => $payments,
            'attachments' => $attachments,
            'statuses' => Contract::getStatuses(),
            'types' => Contract::getTypes(),
            'payment_methods' => Contract::getPaymentMethods(),
            'payment_statuses' => Contract::getPaymentStatuses(),
            'flash' => Utils::getFlash()
        ]);
    }

    /**
     * Sözleşme düzenleme formu
     */
    public function edit($id)
    {
        Auth::require();
        
        if (!$id || !is_numeric($id)) {
            error_log("Contract edit: Invalid ID: $id");
            View::notFound('Geçersiz sözleşme ID');
        }
        
        $contract = $this->contractModel->find($id);
        if (!$contract) {
            error_log("Contract edit: Contract not found with ID: $id");
            View::notFound('Sözleşme bulunamadı');
        }
        
        $customers = $this->customerModel->all();
        
        echo View::renderWithLayout('contracts/form', [
            'contract' => $contract,
            'customers' => $customers,
            'statuses' => Contract::getStatuses(),
            'types' => Contract::getTypes(),
            'flash' => Utils::getFlash()
        ]);
    }

    /**
     * Sözleşme güncelle
     */
    public function update($id)
    {
        Auth::require();
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/contracts')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect("/contracts/{$id}/edit")) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        if (!$id || !is_numeric($id)) {
            error_log("Contract update: Invalid ID: $id");
            View::notFound('Geçersiz sözleşme ID');
        }

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            error_log("Contract update: Contract not found with ID: $id");
            View::notFound('Sözleşme bulunamadı');
        }

        $data = [
            'customer_id' => $_POST['customer_id'] ?? null,
            'contract_number' => $_POST['contract_number'] ?? '',
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'contract_type' => $_POST['contract_type'] ?? 'CLEANING',
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? null,
            'total_amount' => $_POST['total_amount'] ?? null,
            'payment_terms' => $_POST['payment_terms'] ?? null,
            'status' => $_POST['status'] ?? 'DRAFT',
            'auto_renewal' => isset($_POST['auto_renewal']) ? 1 : 0,
            'renewal_period_days' => $_POST['renewal_period_days'] ?? null,
            'notes' => $_POST['notes'] ?? null
        ];

        // Validasyon
        $errors = [];
        if (empty($data['customer_id'])) {
            $errors[] = 'Müşteri seçimi zorunludur.';
        }
        if (empty($data['contract_number'])) {
            $errors[] = 'Sözleşme numarası zorunludur.';
        }
        if (empty($data['title'])) {
            $errors[] = 'Başlık zorunludur.';
        }
        if (empty($data['start_date'])) {
            $errors[] = 'Başlangıç tarihi zorunludur.';
        }

        if (!empty($errors)) {
            Utils::flash('error', implode('<br>', $errors));
            redirect(base_url("/contracts/{$id}/edit"));
        }

        try {
            $this->contractModel->update($id, $data);
            
            // Aktivite logla
            ActivityLogger::log('contract_updated', 'contract', [
                'contract_id' => $id,
                'customer_id' => $data['customer_id'],
                'title' => $data['title']
            ]);
            
            Utils::flash('success', 'Sözleşme başarıyla güncellendi.');
            redirect(base_url('/contracts'));
        } catch (Exception $e) {
            error_log("Contract update error: " . $e->getMessage());
            Utils::flash('error', 'Sözleşme güncellenirken bir hata oluştu.');
            redirect(base_url("/contracts/{$id}/edit"));
        }
    }

    /**
     * Sözleşme sil
     */
    public function delete($id)
    {
        Auth::require();
        
        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            Utils::flash('error', 'Geçersiz sözleşme ID.');
            redirect(base_url('/contracts'));
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/contracts')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/contracts')) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            Utils::flash('error', 'Sözleşme bulunamadı.');
            redirect(base_url('/contracts'));
            return;
        }

        try {
            $this->contractModel->delete($id);
            
            // Aktivite logla
            ActivityLogger::log('contract_deleted', 'contract', [
                'contract_id' => $id,
                'title' => $contract['title']
            ]);
            
            ControllerHelper::flashSuccessAndRedirect('Sözleşme başarıyla silindi.', '/contracts');
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'ContractController::delete()', 'Sözleşme silinirken bir hata oluştu', '/contracts');
        }
    }

    /**
     * Sözleşme durumunu güncelle
     */
    public function updateStatus($id)
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/contracts'));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url('/contracts'));
        }

        if (!$id || !is_numeric($id)) {
            error_log("Contract updateStatus: Invalid ID: $id");
            View::notFound('Geçersiz sözleşme ID');
        }

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            error_log("Contract updateStatus: Contract not found with ID: $id");
            View::notFound('Sözleşme bulunamadı');
        }

        $status = $_POST['status'] ?? null;
        if (!$status || !array_key_exists($status, Contract::getStatuses())) {
            Utils::flash('error', 'Geçersiz durum.');
            redirect(base_url('/contracts'));
        }

        try {
            $this->contractModel->updateStatus($id, $status);
            
            // Aktivite logla
            ActivityLogger::log('contract_status_updated', 'contract', [
                'contract_id' => $id,
                'old_status' => $contract['status'],
                'new_status' => $status,
                'title' => $contract['title']
            ]);
            
            Utils::flash('success', 'Sözleşme durumu güncellendi.');
            redirect(base_url('/contracts'));
        } catch (Exception $e) {
            error_log("Contract status update error: " . $e->getMessage());
            Utils::flash('error', 'Durum güncellenirken bir hata oluştu.');
            redirect(base_url('/contracts'));
        }
    }

    /**
     * Ödeme ekle
     */
    public function addPayment($id)
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url("/contracts/{$id}"));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url("/contracts/{$id}"));
        }

        if (!$id || !is_numeric($id)) {
            error_log("Contract addPayment: Invalid ID: $id");
            View::notFound('Geçersiz sözleşme ID');
        }

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            error_log("Contract addPayment: Contract not found with ID: $id");
            View::notFound('Sözleşme bulunamadı');
        }

        $data = [
            'amount' => $_POST['amount'] ?? null,
            'payment_date' => $_POST['payment_date'] ?? '',
            'payment_method' => $_POST['payment_method'] ?? 'CASH',
            'status' => $_POST['status'] ?? 'PENDING',
            'due_date' => $_POST['due_date'] ?? null,
            'notes' => $_POST['notes'] ?? null
        ];

        // Validasyon
        $errors = [];
        if (empty($data['amount']) || !is_numeric($data['amount'])) {
            $errors[] = 'Geçerli bir tutar giriniz.';
        }
        if (empty($data['payment_date'])) {
            $errors[] = 'Ödeme tarihi zorunludur.';
        }

        if (!empty($errors)) {
            Utils::flash('error', implode('<br>', $errors));
            redirect(base_url("/contracts/{$id}"));
        }

        try {
            $this->contractModel->addPayment($id, $data);
            
            // Aktivite logla
            ActivityLogger::log('contract_payment_added', 'contract', [
                'contract_id' => $id,
                'amount' => $data['amount']
            ]);
            
            Utils::flash('success', 'Ödeme başarıyla eklendi.');
            redirect(base_url("/contracts/{$id}"));
        } catch (Exception $e) {
            error_log("Contract payment addition error: " . $e->getMessage());
            Utils::flash('error', 'Ödeme eklenirken bir hata oluştu.');
            redirect(base_url("/contracts/{$id}"));
        }
    }

    /**
     * Ödeme güncelle
     */
    public function updatePayment($id, $paymentId)
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url("/contracts/{$id}"));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url("/contracts/{$id}"));
        }

        if (!$id || !is_numeric($id) || !$paymentId || !is_numeric($paymentId)) {
            error_log("Contract updatePayment: Invalid IDs: $id, $paymentId");
            View::notFound('Geçersiz ID');
        }

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            error_log("Contract updatePayment: Contract not found with ID: $id");
            View::notFound('Sözleşme bulunamadı');
        }

        $data = [
            'amount' => $_POST['amount'] ?? null,
            'payment_date' => $_POST['payment_date'] ?? '',
            'payment_method' => $_POST['payment_method'] ?? 'CASH',
            'status' => $_POST['status'] ?? 'PENDING',
            'due_date' => $_POST['due_date'] ?? null,
            'notes' => $_POST['notes'] ?? null
        ];

        // Validasyon
        $errors = [];
        if (empty($data['amount']) || !is_numeric($data['amount'])) {
            $errors[] = 'Geçerli bir tutar giriniz.';
        }
        if (empty($data['payment_date'])) {
            $errors[] = 'Ödeme tarihi zorunludur.';
        }

        if (!empty($errors)) {
            Utils::flash('error', implode('<br>', $errors));
            redirect(base_url("/contracts/{$id}"));
        }

        try {
            $this->contractModel->updatePayment($paymentId, $data);
            
            // Aktivite logla
            ActivityLogger::log('contract_payment_updated', 'contract', [
                'contract_id' => $id,
                'payment_id' => $paymentId,
                'amount' => $data['amount']
            ]);
            
            Utils::flash('success', 'Ödeme başarıyla güncellendi.');
            redirect(base_url("/contracts/{$id}"));
        } catch (Exception $e) {
            error_log("Contract payment update error: " . $e->getMessage());
            Utils::flash('error', 'Ödeme güncellenirken bir hata oluştu.');
            redirect(base_url("/contracts/{$id}"));
        }
    }

    /**
     * Ödeme sil
     */
    public function deletePayment($id, $paymentId)
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url("/contracts/{$id}"));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url("/contracts/{$id}"));
        }

        if (!$id || !is_numeric($id) || !$paymentId || !is_numeric($paymentId)) {
            error_log("Contract deletePayment: Invalid IDs: $id, $paymentId");
            View::notFound('Geçersiz ID');
        }

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            error_log("Contract deletePayment: Contract not found with ID: $id");
            View::notFound('Sözleşme bulunamadı');
        }

        try {
            $this->contractModel->deletePayment($paymentId);
            
            // Aktivite logla
            ActivityLogger::log('contract_payment_deleted', 'contract', [
                'contract_id' => $id,
                'payment_id' => $paymentId
            ]);
            
            Utils::flash('success', 'Ödeme başarıyla silindi.');
            redirect(base_url("/contracts/{$id}"));
        } catch (Exception $e) {
            error_log("Contract payment deletion error: " . $e->getMessage());
            Utils::flash('error', 'Ödeme silinirken bir hata oluştu.');
            redirect(base_url("/contracts/{$id}"));
        }
    }

    /**
     * Dosya yükle
     */
    public function uploadFile($id)
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url("/contracts/{$id}"));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url("/contracts/{$id}"));
        }

        if (!$id || !is_numeric($id)) {
            error_log("Contract uploadFile: Invalid ID: $id");
            View::notFound('Geçersiz sözleşme ID');
        }

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            error_log("Contract uploadFile: Contract not found with ID: $id");
            View::notFound('Sözleşme bulunamadı');
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Utils::flash('error', 'Dosya yükleme hatası.');
            redirect(base_url("/contracts/{$id}"));
        }

        $file = $_FILES['file'];
        
        // Secure file upload validation
        $validationErrors = FileUploadValidator::validate($file);
        if (!empty($validationErrors)) {
            Utils::flash('error', implode('. ', $validationErrors));
            redirect(base_url("/contracts/{$id}"));
        }
        
        $uploadDir = __DIR__ . '/../../uploads/contracts/';
        
        // Upload dizinini oluştur
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate secure filename and move file
        $secureFilename = FileUploadValidator::generateSecureFilename($file['name']);
        $filePath = $uploadDir . $secureFilename;

        if (FileUploadValidator::moveToSecureLocation($file, $uploadDir)) {
            try {
                $data = [
                    'file_name' => $file['name'],
                    'file_path' => 'uploads/contracts/' . $secureFilename,
                    'file_size' => $file['size'],
                    'mime_type' => $file['type'],
                    'uploaded_by' => Auth::user()['id']
                ];
                
                $this->contractModel->addAttachment($id, $data);
                
                // Aktivite logla
                ActivityLogger::log('contract_file_uploaded', 'contract', [
                    'contract_id' => $id,
                    'file_name' => $file['name']
                ]);
                
                Utils::flash('success', 'Dosya başarıyla yüklendi.');
            } catch (Exception $e) {
                error_log("Contract file upload error: " . $e->getMessage());
                Utils::flash('error', 'Dosya bilgileri kaydedilirken bir hata oluştu.');
            }
        } else {
            Utils::flash('error', 'Dosya yüklenemedi.');
        }

        redirect(base_url("/contracts/{$id}"));
    }

    /**
     * Dosya sil
     */
    public function deleteFile($id, $attachmentId)
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url("/contracts/{$id}"));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url("/contracts/{$id}"));
        }

        if (!$id || !is_numeric($id) || !$attachmentId || !is_numeric($attachmentId)) {
            error_log("Contract deleteFile: Invalid IDs: $id, $attachmentId");
            View::notFound('Geçersiz ID');
        }

        $contract = $this->contractModel->find($id);
        if (!$contract) {
            error_log("Contract deleteFile: Contract not found with ID: $id");
            View::notFound('Sözleşme bulunamadı');
        }

        try {
            $this->contractModel->deleteAttachment($attachmentId);
            
            // Aktivite logla
            ActivityLogger::log('contract_file_deleted', 'contract', [
                'contract_id' => $id,
                'attachment_id' => $attachmentId
            ]);
            
            Utils::flash('success', 'Dosya başarıyla silindi.');
            redirect(base_url("/contracts/{$id}"));
        } catch (Exception $e) {
            error_log("Contract file deletion error: " . $e->getMessage());
            Utils::flash('error', 'Dosya silinirken bir hata oluştu.');
            redirect(base_url("/contracts/{$id}"));
        }
    }

    /**
     * Süresi yaklaşan sözleşmeler
     */
    public function expiring()
    {
        Auth::require();
        
        $days = $_GET['days'] ?? 30;
        $contracts = $this->contractModel->getExpiringSoon($days);
        $stats = $this->contractModel->getStats();

        echo View::renderWithLayout('contracts/expiring', [
            'contracts' => $contracts,
            'stats' => $stats,
            'days' => $days,
            'statuses' => Contract::getStatuses(),
            'types' => Contract::getTypes(),
            'flash' => Utils::getFlash()
        ]);
    }
    
    /**
     * Dosya yükleme işlemini handle et
     */
    private function handleFileUpload($contractId, $file)
    {
        // Dosya güvenlik kontrolleri
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Sadece JPG, PNG, GIF, WEBP ve PDF dosyaları yüklenebilir.');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('Dosya boyutu 10MB\'dan büyük olamaz.');
        }
        
        $uploadDir = __DIR__ . '/../../uploads/contracts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'contract_' . $contractId . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Dosya yüklenirken bir hata oluştu.');
        }
        
        // Veritabanına kaydet
        $attachmentData = [
            'contract_id' => $contractId,
            'file_name' => $file['name'],
            'file_path' => 'uploads/contracts/' . $fileName,
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'uploaded_by' => Auth::user()['id']
        ];
        
        $this->contractModel->addAttachment($contractId, $attachmentData);
    }
    
    // ===== KOZMOS_BULK_OPERATIONS: bulk operations methods (begin)
    public function bulkStatusUpdate()
    {
        Auth::require();
        if (Auth::role() === 'OPERATOR') { View::forbidden(); }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::error('Geçersiz istek', 405);
        }
        
        $contractIds = $_POST['contract_ids'] ?? [];
        $status = $_POST['status'] ?? '';
        
        if (empty($contractIds) || !is_array($contractIds)) {
            Utils::flash('error', 'Lütfen en az bir sözleşme seçin.');
            redirect(base_url('/contracts'));
        }
        
        if (!in_array($status, ['ACTIVE', 'EXPIRED', 'TERMINATED'])) {
            Utils::flash('error', 'Geçersiz durum.');
            redirect(base_url('/contracts'));
        }
        
        $db = Database::getInstance();
        $updatedCount = 0;
        
        try {
            $db->beginTransaction();
            
            foreach ($contractIds as $contractId) {
                $contract = $this->contractModel->find($contractId);
                if (!$contract) continue;
                
                if ($this->contractModel->update($contractId, ['status' => $status])) {
                    $updatedCount++;
                    ActivityLogger::log('contract_updated', 'contract', [
                        'contract_id' => $contractId,
                        'status' => $status,
                        'contract_number' => $contract['contract_number']
                    ]);
                }
            }
            
            $db->commit();
            
            if ($updatedCount > 0) {
                Utils::flash('success', "{$updatedCount} sözleşmenin durumu başarıyla güncellendi.");
            } else {
                Utils::flash('error', 'Hiçbir sözleşme güncellenemedi.');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            ActivityLogger::log('ERROR', 'contracts', [
                'action' => 'bulk_status_update',
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            Utils::flash('error', 'Toplu güncelleme sırasında hata oluştu.');
        }
        
        redirect(base_url('/contracts'));
    }
    
    public function bulkDelete()
    {
        Auth::require();
        if (Auth::role() === 'OPERATOR') { View::forbidden(); }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::error('Geçersiz istek', 405);
        }
        
        $contractIds = $_POST['contract_ids'] ?? [];
        
        if (empty($contractIds) || !is_array($contractIds)) {
            Utils::flash('error', 'Lütfen en az bir sözleşme seçin.');
            redirect(base_url('/contracts'));
        }
        
        $db = Database::getInstance();
        $deletedCount = 0;
        
        try {
            $db->beginTransaction();
            
            foreach ($contractIds as $contractId) {
                $contract = $this->contractModel->find($contractId);
                if (!$contract) continue;
                
                if ($this->contractModel->delete($contractId)) {
                    $deletedCount++;
                    ActivityLogger::log('contract_deleted', 'contract', [
                        'contract_id' => $contractId,
                        'contract_number' => $contract['contract_number']
                    ]);
                }
            }
            
            $db->commit();
            
            if ($deletedCount > 0) {
                Utils::flash('success', "{$deletedCount} sözleşme başarıyla silindi.");
            } else {
                Utils::flash('error', 'Hiçbir sözleşme silinemedi.');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            ActivityLogger::log('ERROR', 'contracts', [
                'action' => 'bulk_delete',
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            Utils::flash('error', 'Toplu silme sırasında hata oluştu.');
        }
        
        redirect(base_url('/contracts'));
    }
    // ===== KOZMOS_BULK_OPERATIONS: bulk operations methods (end)
}
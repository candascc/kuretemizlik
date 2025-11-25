<?php

declare(strict_types=1);

/**
 * Job Controller
 * 
 * Handles job-related operations including CRUD operations, status updates,
 * recurring job management, and job assignments.
 * 
 * @package App\Controllers
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/../Services/RecurringGenerator.php';
require_once __DIR__ . '/../Services/PaymentService.php';
require_once __DIR__ . '/../Services/ContractTemplateService.php';
require_once __DIR__ . '/../Services/ContractOtpService.php';
require_once __DIR__ . '/../Constants/AppConstants.php';
require_once __DIR__ . '/../Lib/ControllerHelper.php';
require_once __DIR__ . '/../Lib/EagerLoader.php';
require_once __DIR__ . '/../Lib/ControllerTrait.php';

class JobController
{
    use CompanyScope;
    use ControllerTrait;

    /** @var Job $jobModel */
    private $jobModel;
    
    /** @var Customer $customerModel */
    private $customerModel;
    
    /** @var Service $serviceModel */
    private $serviceModel;
    
    /** @var JobContract $contractModel */
    private $contractModel;

    /**
     * JobController constructor
     * Initializes required models
     */
    public function __construct()
    {
        $this->jobModel = new Job();
        $this->customerModel = new Customer();
        $this->serviceModel = new Service();
        $this->contractModel = new JobContract();
    }

    /**
     * Display job list with filters and pagination
     * 
     * @return void
     */
    public function index()
    {
        Auth::requireGroup('nav.operations.core');

        // ===== PRODUCTION FIX: Prevent caching of job list page =====
        Utils::setNoCacheHeaders();
        // ===== PRODUCTION FIX END =====

        $page = InputSanitizer::int($_GET['page'] ?? 1, AppConstants::MIN_PAGE, AppConstants::MAX_PAGE);
        $status = InputSanitizer::string($_GET['status'] ?? '', AppConstants::MAX_STRING_LENGTH_SHORT);
        $customer = InputSanitizer::string($_GET['customer'] ?? '', AppConstants::MAX_STRING_LENGTH_MEDIUM);
        $dateFrom = InputSanitizer::date($_GET['date_from'] ?? '', AppConstants::DATE_FORMAT);
        $dateTo = InputSanitizer::date($_GET['date_to'] ?? '', AppConstants::DATE_FORMAT);
        $recurring = InputSanitizer::int($_GET['recurring'] ?? null, AppConstants::VALIDATION_MIN_ID);
        $showPast = isset($_GET['show_past']);

        $limit = AppConstants::DEFAULT_PAGE_SIZE;
        $offset = ($page - 1) * $limit;

        $db = Database::getInstance();

        $whereClause = $this->scopeToCompany('WHERE 1=1', 'j');
        $params = [];

        if (!$showPast) {
            $whereClause .= " AND DATE(j.start_at) >= ?";
            $params[] = date('Y-m-d');
        }

        if ($status) {
            $whereClause .= " AND j.status = ?";
            $params[] = $status;
        }

        if ($customer) {
            $whereClause .= " AND c.name LIKE ?";
            $params[] = "%$customer%";
        }

        if ($recurring) {
            $whereClause .= " AND j.recurring_job_id = ?";
            $params[] = $recurring;
        }

        if ($dateFrom) {
            $whereClause .= " AND DATE(j.start_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $whereClause .= " AND DATE(j.start_at) <= ?";
            $params[] = $dateTo;
        }

        $countSql = "
            SELECT COUNT(*) as count
            FROM jobs j
            LEFT JOIN customers c ON j.customer_id = c.id
            $whereClause
        ";
        
        // ===== PRODUCTION FIX: Handle database fetch errors gracefully =====
        try {
            $countResult = $db->fetch($countSql, $params);
            $total = $countResult['count'] ?? 0;
        } catch (Throwable $e) {
            error_log("JobController::index() - Database fetch error: " . $e->getMessage());
            error_log("SQL: " . $countSql);
            error_log("Params: " . json_encode($params));
            $total = 0;
        }
        // ===== PRODUCTION FIX END =====

        $orderDirection = $showPast ? 'DESC' : 'ASC';

        $sql = "
            SELECT
                j.*,
                c.name AS customer_name,
                c.phone AS customer_phone,
                s.name AS service_name,
                s.default_fee AS service_fee,
                a.line AS address_line,
                rj.pricing_model AS pricing_model
            FROM jobs j
            LEFT JOIN customers c ON j.customer_id = c.id
            LEFT JOIN services s ON j.service_id = s.id
            LEFT JOIN addresses a ON j.address_id = a.id
            LEFT JOIN recurring_jobs rj ON j.recurring_job_id = rj.id
            $whereClause
            ORDER BY j.start_at {$orderDirection}
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        // ===== PRODUCTION FIX: Handle database fetchAll errors gracefully =====
        try {
            $jobs = $db->fetchAll($sql, $params);
        } catch (Throwable $e) {
            error_log("JobController::index() - Database fetchAll error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            $jobs = [];
        }
        // ===== PRODUCTION FIX END =====
        $pagination = Utils::paginate($total, $limit, $page);
        $customers = $this->customerModel->all();
        $companies = $this->getCompanyOptions();

        // Get recurring job info if filtered
        $recurringJobInfo = null;
        if ($recurring) {
            $recurringJobInfo = (new RecurringJob())->find($recurring);
        }

        echo View::renderWithLayout('jobs/list', [
            'jobs' => $jobs,
            'pagination' => $pagination,
            'customers' => $customers,
            'filters' => [
                'status' => $status,
                'customer' => $customer,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'recurring' => $recurring,
                'show_past' => $showPast,
            ],
            'companies' => $companies,
            'recurringJobInfo' => $recurringJobInfo,
            'flash' => Utils::getFlash(),
        ]);
    }

    public function show($id)
    {
        // Redirect to unified management page
        redirect(base_url("/jobs/manage/{$id}"));
    }

    public function create()
    {
        // ROUND 44: KAPSAYICI TRY/CATCH - Tüm method'u sar, global error handler'a ulaşmasın
        try {
            // ROUND 32: PROD hardening - Check auth and capability BEFORE any processing
            // Auth::requireCapability() calls View::forbidden() which returns 403, not exception
            // So we need to check manually and handle gracefully
            
            // First check if user is authenticated
            if (!Auth::check()) {
                Utils::flash('error', 'Bu sayfaya erişmek için giriş yapmanız gerekiyor.');
                redirect(base_url('/login'));
                return;
            }
            
            // ROUND 33: Then check capability - if not authorized, redirect instead of 403
            // Defensive: Wrap hasCapability in try/catch in case it throws exception
            try {
                if (!Auth::hasCapability('jobs.create')) {
                    error_log("JobController::create() - User " . Auth::id() . " does not have 'jobs.create' capability");
                    Utils::flash('error', 'Bu sayfaya erişim yetkiniz bulunmuyor.');
                    redirect(base_url('/jobs'));
                    return;
                }
            } catch (Throwable $e) {
                // ROUND 33: If hasCapability throws exception, log and redirect (safe default)
                error_log("JobController::create() - Auth::hasCapability() error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                Utils::flash('error', 'Yetki kontrolü sırasında bir hata oluştu.');
                redirect(base_url('/jobs'));
                return;
            }
            
            // ===== PRODUCTION FIX: Set no-cache headers before any output =====
            Utils::setNoCacheHeaders();
            // ===== PRODUCTION FIX END =====

            // ROUND 29: Initialize variables with safe defaults
            $customers = [];
            $services = [];
            $statuses = [];
            $prefill = [];
            $selectedCustomer = null;

            // ===== PRODUCTION FIX: Handle database/model errors gracefully =====
            try {
                $customers = $this->customerModel->all();
                // Ensure customers is an array
                if (!is_array($customers)) {
                    $customers = [];
                }
            } catch (Throwable $e) {
                error_log("JobController::create() - customerModel->all() error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                $customers = [];
            }
            
            try {
                $services = $this->serviceModel->getActive();
                // Ensure services is an array
                if (!is_array($services)) {
                    $services = [];
                }
            } catch (Throwable $e) {
                error_log("JobController::create() - serviceModel->getActive() error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                $services = [];
            }
            
            // ROUND 29: Handle getStatuses() safely
            try {
                $statuses = Job::getStatuses();
                // Ensure statuses is an array
                if (!is_array($statuses)) {
                    $statuses = [
                        'SCHEDULED' => 'Planlandı',
                        'IN_PROGRESS' => 'Devam Ediyor',
                        'DONE' => 'Tamamlandı',
                        'CANCELLED' => 'İptal Edildi',
                    ];
                }
            } catch (Throwable $e) {
                error_log("JobController::create() - Job::getStatuses() error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                // Fallback to default statuses
                $statuses = [
                    'SCHEDULED' => 'Planlandı',
                    'IN_PROGRESS' => 'Devam Ediyor',
                    'DONE' => 'Tamamlandı',
                    'CANCELLED' => 'İptal Edildi',
                ];
            }
            // ===== PRODUCTION FIX END =====
            
            // Session'dan form verilerini al (validasyon hatasi sonrasi)
            $formData = $_SESSION['form_data'] ?? [];
            
            // Müşteri bilgisini al
            if (!empty($formData['customer_id'])) {
                try {
                    $selectedCustomer = $this->customerModel->find($formData['customer_id']);
                    // Ensure selectedCustomer is valid
                    if (!is_array($selectedCustomer)) {
                        $selectedCustomer = null;
                    }
                } catch (Throwable $e) {
                    error_log("JobController::create() - customerModel->find() error: " . $e->getMessage());
                    $selectedCustomer = null;
                }
            }
            
            $prefill = [
                'start_at' => $formData['start_at'] ?? InputSanitizer::string($_GET['start_at'] ?? '', 50),
                'end_at' => $formData['end_at'] ?? InputSanitizer::string($_GET['end_at'] ?? '', 50),
                'customer_id' => $formData['customer_id'] ?? '',
                'customer_name' => ($selectedCustomer && isset($selectedCustomer['name'])) ? $selectedCustomer['name'] : '',
                'service_id' => $formData['service_id'] ?? '',
                'address_id' => $formData['address_id'] ?? '',
                'total_amount' => $formData['total_amount'] ?? '',
                'payment_amount' => $formData['payment_amount'] ?? '',
                'payment_date' => $formData['payment_date'] ?? date('Y-m-d'),
                'payment_note' => $formData['payment_note'] ?? '',
                'note' => $formData['note'] ?? '',
                'status' => $formData['status'] ?? 'SCHEDULED',
            ];

            // Form verilerini temizle
            unset($_SESSION['form_data']);

            // ROUND 29: Final safety check - ensure all variables are arrays
            $customers = is_array($customers) ? $customers : [];
            $services = is_array($services) ? $services : [];
            $statuses = is_array($statuses) ? $statuses : [
                'SCHEDULED' => 'Planlandı',
                'IN_PROGRESS' => 'Devam Ediyor',
                'DONE' => 'Tamamlandı',
                'CANCELLED' => 'İptal Edildi',
            ];

            // ROUND 34: PROD hardening - Clear output buffers before view rendering
            // Ensure no partial output interferes with view rendering
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ob_start();
            
            // ROUND 31: Handle view rendering errors gracefully - comprehensive error handling
            try {
                echo View::renderWithLayout('jobs/form', [
                    'customers' => $customers,
                    'services' => $services,
                    'job' => null,
                    'prefill' => $prefill,
                    'payments' => [],
                    'statuses' => $statuses,
                    'flash' => Utils::getFlash(),
                ]);
            } catch (Throwable $e) {
                // ROUND 34: Clear any partial output before error handling
                ob_clean();
                
                // ROUND 31: Log view rendering error with full context
                if (class_exists('AppErrorHandler')) {
                    AppErrorHandler::logException($e, ['context' => 'JobController::create() - View::renderWithLayout()']);
                } else {
                    error_log("JobController::create() - View::renderWithLayout() error: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
                
                // ROUND 34: Redirect to jobs list instead of showing error page (prevents 500)
                // This provides better UX and prevents 500 errors in production
                Utils::flash('error', 'İş formu yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.');
                redirect(base_url('/jobs'));
                return;
            } finally {
                // ROUND 34: Ensure output buffer is flushed
                // CRITICAL: Skip ob_end_flush() for internal requests to prevent headers already sent errors
                if (!defined('KUREAPP_INTERNAL_REQUEST') && ob_get_level() > 0) {
                    ob_end_flush();
                }
            }
            // ===== PRODUCTION FIX END =====
        } catch (Throwable $e) {
            // ROUND 44: KAPSAYICI CATCH - Tüm beklenmeyen exception'ları yakala
            // Clear any partial output
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // ROUND 44: Log error with full context
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $logLine = date('c') . ' JobController::create() - UNEXPECTED ERROR' . PHP_EOL
                . '  User ID: ' . (Auth::check() ? Auth::id() : 'not authenticated') . PHP_EOL
                . '  Role: ' . (Auth::check() ? Auth::role() : 'not authenticated') . PHP_EOL
                . '  URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL
                . '  Request Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . PHP_EOL
                . '  GET params: ' . json_encode($_GET) . PHP_EOL
                . '  Exception: ' . $e->getMessage() . PHP_EOL
                . '  Stack trace: ' . $e->getTraceAsString() . PHP_EOL
                . '---' . PHP_EOL;
            @file_put_contents($logDir . '/job_create_r44.log', $logLine, FILE_APPEND);
            
            if (class_exists('AppErrorHandler')) {
                AppErrorHandler::logException($e, [
                    'context' => 'JobController::create() - outer catch',
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'role' => Auth::check() ? Auth::role() : null,
                    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
            } else {
                error_log("JobController::create() - UNEXPECTED ERROR: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            // ROUND 44: Kullanıcıya 200 status ile sade bir hata view'i veya redirect göster (500 DEĞİL)
            // Global error handler'a ulaşmasın
            Utils::flash('error', 'İş formu yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.');
            redirect(base_url('/jobs'));
            return;
        }
    }

    public function store()
    {
        // ===== CRITICAL FIX: Auth::requireCapability() now handles session initialization =====
        // No need to start session here - Auth::requireCapability() handles it
        Auth::requireCapability('jobs.create');

        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/jobs')) {
            return;
        }

        if (!ControllerHelper::verifyCsrfOrRedirect('/jobs/new')) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        // ===== ERR-027 FIX: Extract validation logic to separate method =====
        $validation = $this->validateJobData($_POST);
        if (!$validation['valid']) {
            $_SESSION['form_data'] = $_POST;
            $firstError = $validation['errors'][0] ?? 'Geçersiz form verisi';
            ControllerHelper::flashErrorAndRedirect($firstError, '/jobs/new');
            return;
        }
        
        $validatedData = $validation['data'];
        
        $jobData = [
            'service_id' => $validatedData['service_id'],
            'customer_id' => $validatedData['customer_id'],
            'address_id' => $validatedData['address_id'],
            'start_at' => $validatedData['start_at']->format('Y-m-d H:i'),
            'end_at' => $validatedData['end_at']->format('Y-m-d H:i'),
            'note' => $validatedData['note'],
            'total_amount' => $validatedData['total_amount'],
            'status' => $validatedData['status'],
            'payment_status' => 'UNPAID',
        ];
        // ===== ERR-027 FIX: End =====

        // ===== ERR-027 FIX: Extract recurring job logic to separate method =====
        if (!empty($_POST['recurring_enabled'])) {
            try {
                $rjId = $this->createRecurringJob($validatedData, $_POST);
                
                $customer = $this->customerModel->find($jobData['customer_id']);
                ActivityLogger::log('recurring_created', 'recurring_job', ['recurring_job_id' => $rjId, 'customer' => $customer['name'] ?? '']);
                ControllerHelper::flashSuccessAndRedirect('Periyodik iş oluşturuldu ve ileri tarihli işler üretildi.', '/recurring');
            } catch (Exception $e) {
                error_log("Recurring job creation failed: " . $e->getMessage());
                $_SESSION['form_data'] = $_POST;
                ControllerHelper::handleException($e, 'JobController::store() recurring job', 'Periyodik iş oluşturulurken bir hata oluştu', '/jobs/new');
            }
            return;
        }
        // ===== ERR-027 FIX: End =====

        $db = Database::getInstance();
        $db->beginTransaction();
        
        try {
            $jobId = $this->jobModel->create($jobData);
            
            // ===== PATH_JOBPAY_STAGE1: Normalize job_id to int before passing to createJobPayment =====
            // Database insert may return string, ensure it's int to prevent TypeError
            $jobId = (int) $jobId;
            if ($jobId <= 0) {
                $db->rollback();
                error_log("PATH_JOBPAY: Invalid job_id after create: " . var_export($jobId, true));
                $_SESSION['form_data'] = $_POST;
                ControllerHelper::flashErrorAndRedirect('İş oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.', '/jobs/new');
                return;
            }
            // ===== PATH_JOBPAY_STAGE1 END =====

            // ===== ERR-027 FIX: Extract payment creation logic to separate method =====
            try {
                $this->createJobPayment($jobId, $validatedData['payment_amount'], $validatedData['payment_date'], $validatedData['payment_note']);
            } catch (Exception $e) {
                $db->rollback();
                error_log("Payment creation failed for job $jobId: " . $e->getMessage());
                $_SESSION['form_data'] = $_POST;
                ControllerHelper::flashErrorAndRedirect('İş oluşturuldu ancak ödeme kaydı yapılamadı. Lütfen tekrar deneyin.', '/jobs/new');
                return;
            }
            // ===== ERR-027 FIX: End =====

            $db->commit();
            
            $customer = $this->customerModel->find($jobData['customer_id']);
            ActivityLogger::jobCreated($jobId, $customer['name'] ?? 'Bilinmeyen Müşteri');
            
            // ===== ERR-018 FIX: Add audit logging =====
            AuditLogger::getInstance()->logDataModification('JOB_CREATED', Auth::id(), [
                'job_id' => $jobId,
                'customer_id' => $jobData['customer_id'],
                'customer_name' => $customer['name'] ?? null,
                'status' => $jobData['status'] ?? null,
                'total_amount' => $jobData['total_amount'] ?? null
            ]);
            // ===== ERR-018 FIX: End =====

            // Send email notification
            if (class_exists('EmailService')) {
                try {
                    $result = EmailService::sendJobNotification($jobId, 'created');
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Email notification result for job $jobId: " . ($result ? 'success' : 'failed'));
                    }
                } catch (Exception $e) {
                    error_log("Email notification failed: " . $e->getMessage());
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Email notification exception: " . $e->getTraceAsString());
                    }
                }
            } else {
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("EmailService class not found!");
                }
            }

            // Clear cache and notifications
            CacheHelper::clearJobCaches();
            NotificationService::clearCache();
            
            $message = 'İş başarıyla oluşturuldu.';
            if ($validatedData['payment_amount'] > 0) {
                $message .= ' İlk ödeme kaydı oluşturuldu.';
            }

            ControllerHelper::flashSuccessAndRedirect($message, '/jobs');
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log("Job creation failed: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            $_SESSION['form_data'] = $_POST;
            ControllerHelper::handleException($e, 'JobController::store()', 'İş oluşturulurken bir hata oluştu', '/jobs/new');
        }
    }

    public function edit($id)
    {
        Auth::require();
        Utils::setNoCacheHeaders();

        // Phase 4.1: Use ControllerTrait for model finding
        $job = $this->findOrFail($this->jobModel, $id, 'İş bulunamadı');
        if (!$job) {
            View::notFound('İş bulunamadı');
            return;
        }

        $customers = $this->customerModel->all();
        $services = $this->serviceModel->getActive();
        $payments = $this->jobModel->getPayments($id);

        echo View::renderWithLayout('jobs/form', [
            'job' => $job,
            'customers' => $customers,
            'services' => $services,
            'payments' => $payments,
            'statuses' => Job::getStatuses(),
            'flash' => Utils::getFlash(),
        ]);
    }

    public function update($id)
    {
        Auth::requireCapability('jobs.edit');

        // Phase 4.1: Use ControllerTrait for common patterns
        $job = $this->findOrFail($this->jobModel, $id, 'İş bulunamadı');
        if (!$job) {
            View::notFound('İş bulunamadı');
            return;
        }

        if (!$this->requirePostAndCsrf("/jobs/edit/$id")) {
            return;
        }

        // ===== ERR-027 FIX: Extract validation logic to separate method =====
        $validation = $this->validateJobUpdateData($_POST, $job);
        if (!$validation['valid']) {
            $firstError = $validation['errors'][0] ?? 'Geçersiz form verisi';
            ControllerHelper::flashErrorAndRedirect($firstError, "/jobs/edit/$id");
            return;
        }
        
        $validatedData = $validation['data'];
        
        // Calculate changes
        $changes = [];
        if ($job['customer_id'] != $validatedData['customer_id']) {
            $changes['customer_id'] = $validatedData['customer_id'];
        }
        if ($job['start_at'] != $validatedData['start_at']->format('Y-m-d H:i')) {
            $changes['start_at'] = $validatedData['start_at']->format('Y-m-d H:i');
        }
        if ($job['end_at'] != $validatedData['end_at']->format('Y-m-d H:i')) {
            $changes['end_at'] = $validatedData['end_at']->format('Y-m-d H:i');
        }
        if ((float)($job['total_amount'] ?? 0) !== $validatedData['total_amount']) {
            $changes['total_amount'] = $validatedData['total_amount'];
        }

        $jobData = [
            'service_id' => $validatedData['service_id'],
            'customer_id' => $validatedData['customer_id'],
            'address_id' => $validatedData['address_id'],
            'start_at' => $validatedData['start_at']->format('Y-m-d H:i'),
            'end_at' => $validatedData['end_at']->format('Y-m-d H:i'),
            'note' => $validatedData['note'],
            'total_amount' => $validatedData['total_amount'],
            'status' => $validatedData['status'],
        ];
        // ===== ERR-027 FIX: End =====

        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            $this->jobModel->update($id, $jobData);

            // ===== ERR-027 FIX: Extract recurring job update logic to separate method =====
            if (!empty($_POST['recurring_enabled'])) {
                try {
                    $this->updateRecurringJob($validatedData, $_POST, $job);
                } catch (Exception $recurringError) {
                    error_log("JobController::update() recurring job creation error: " . $recurringError->getMessage());
                    // Don't fail the update if recurring job creation fails
                }
            }
            // ===== ERR-027 FIX: End =====

            // ===== ERR-027 FIX: Extract payment update logic to separate method =====
            $this->updateJobPayment($id, $validatedData['payment_amount'], $validatedData['payment_date'], $validatedData['payment_note']);
            // ===== ERR-027 FIX: End =====

            if (!empty($changes)) {
                ActivityLogger::jobUpdated($id, $changes);
            }

            // ===== ERR-018 FIX: Add audit logging =====
            AuditLogger::getInstance()->logDataModification('JOB_UPDATED', Auth::id(), [
                'job_id' => $id,
                'customer_id' => $job['customer_id'] ?? null,
                'old_status' => $job['status'] ?? null,
                'new_status' => $jobData['status'] ?? null,
                'changes' => $changes
            ]);
            // ===== ERR-018 FIX: End =====
            
            $message = 'İş başarıyla güncellendi.';
            if ($validatedData['payment_amount'] > 0) {
                $message .= ' Yeni ödeme kaydı oluşturuldu.';
            }
            
            CacheHelper::clearJobCaches($id);
            $this->flashSuccess($message, '/jobs');
        } catch (Exception $e) {
            $this->handleException($e, 'JobController::update()', 'İş güncellenirken bir hata oluştu', "/jobs/edit/$id");
        }
        // ===== ERR-010 FIX: End =====
    }

    public function convertToRecurring($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası.');
            redirect(base_url("/jobs/manage/{$id}"));
        }
        
        try {
            $job = $this->jobModel->find($id);
            if (!$job) { 
                View::notFound('İş bulunamadı');
                return;
            }
            
            // Determine weekday from start_at
            $startDate = new DateTime($job['start_at']);
            $weekdayMap = [1 => 'MON', 2 => 'TUE', 3 => 'WED', 4 => 'THU', 5 => 'FRI', 6 => 'SAT', 7 => 'SUN'];
            $weekday = $weekdayMap[(int)$startDate->format('N')] ?? 'MON';
            
            $rjId = (new RecurringJob())->create([
                'customer_id' => $job['customer_id'],
                'address_id' => $job['address_id'] ?? null,
                'service_id' => $job['service_id'] ?? null,
                'frequency' => 'WEEKLY',
                'interval' => 1,
                'byweekday' => [$weekday],
                'byhour' => (int)$startDate->format('H'),
                'byminute' => (int)$startDate->format('i'),
                'duration_min' => (int)max(15, (strtotime($job['end_at']) - strtotime($job['start_at'])) / 60),
                'start_date' => substr($job['start_at'], 0, 10),
                'end_date' => null,
                'default_total_amount' => (float)($job['total_amount'] ?? 0),
                'default_notes' => $job['note'] ?? null,
                'status' => 'ACTIVE',
            ]);
            
            try {
                RecurringGenerator::generateForJob((int)$rjId, 30);
                RecurringGenerator::materializeToJobs((int)$rjId);
            } catch (Exception $e) {
                error_log("Occurrence generation failed for converted recurring job $rjId: " . $e->getMessage());
                // Continue even if generation fails
            }
            
            Utils::flash('success', 'İş başarıyla periyodik işe dönüştürüldü.');
            redirect(base_url('/recurring'));
        } catch (Exception $e) {
            error_log("Convert to recurring failed for job $id: " . $e->getMessage());
            Utils::flash('error', 'Periyodik işe dönüştürme sırasında bir hata oluştu. Lütfen tekrar deneyin.');
            redirect(base_url("/jobs/manage/{$id}"));
        }
    }

    public function manage($id)
    {
        Auth::require();
        
        // Phase 4.1: Use ControllerTrait for model finding
        $job = $this->findOrFail($this->jobModel, $id, 'İş bulunamadı');
        if (!$job) {
            View::notFound('İş bulunamadı');
            return;
        }
        
        // Phase 3.1: Use EagerLoader for batch loading customer and service
        $customer = null;
        $service = null;
        if (!empty($job['customer_id'])) {
            $customers = EagerLoader::loadCustomers([$job['customer_id']]);
            $customer = $customers[$job['customer_id']] ?? null;
        }
        if (!empty($job['service_id'])) {
            $services = EagerLoader::loadServices([$job['service_id']]);
            $service = $services[$job['service_id']] ?? null;
        }
        
        $job['customer_name'] = $customer['name'] ?? 'Bilinmeyen Müşteri';
        $job['service_name'] = $service['name'] ?? 'Bilinmeyen Hizmet';
        
        // Get payments
        $payments = $this->jobModel->getPayments($id);
        
        // Check if this is a recurring job and get recurring job data with pricing_model
        $isRecurring = !empty($job['recurring_job_id']);
        $recurringJob = null;
        $occurrences = [];
        
        if ($isRecurring) {
            // Get recurring job from database with proper JOIN to ensure pricing_model is available
            $db = Database::getInstance();
            $recurringJobData = $db->fetch("
                SELECT rj.*, c.name as customer_name
                FROM recurring_jobs rj
                LEFT JOIN customers c ON rj.customer_id = c.id
                WHERE rj.id = ?
            ", [$job['recurring_job_id']]);
            
            if ($recurringJobData) {
                $recurringJob = $recurringJobData;
                // Also add pricing_model to job array for easier access
                if (!empty($recurringJob['pricing_model'])) {
                    $job['pricing_model'] = $recurringJob['pricing_model'];
                }
                $occurrences = RecurringOccurrence::getByRecurringJobId($job['recurring_job_id']);
            }
        }
        
        // Get job contract if exists
        $contract = $this->contractModel->findByJobId($id);
        
        // Prepare contract status information for view
        $contractStatus = [
            'label' => __('contracts.panel.status.none'),
            'class' => 'bg-gray-100 text-gray-800',
            'has_contract' => false
        ];
        
        if ($contract) {
            $contractStatus['has_contract'] = true;
            $statusKey = $contract['status'] ?? 'none';
            $contractStatus['label'] = __('contracts.panel.status.' . $statusKey, [], $contractStatus['label']);
            
            switch ($contract['status']) {
                case 'APPROVED':
                    $contractStatus['class'] = 'bg-green-100 text-green-800';
                    break;
                case 'PENDING':
                case 'SENT':
                    $contractStatus['class'] = 'bg-yellow-100 text-yellow-800';
                    break;
                case 'EXPIRED':
                case 'REJECTED':
                    $contractStatus['class'] = 'bg-red-100 text-red-800';
                    break;
                default:
                    $contractStatus['class'] = 'bg-gray-100 text-gray-800';
            }
        }
        
        // Prepare timeline events
        $timelineEvents = [];
        
        // 1. İş oluşturuldu (her zaman var)
        if (!empty($job['created_at'])) {
            $timelineEvents[] = [
                'type' => 'job_created',
                'datetime' => $job['created_at'],
                'label' => __('contracts.panel.timeline.job_created'),
                'description' => __('contracts.panel.timeline.job_created_desc'),
                'icon' => 'calendar-plus',
            ];
        }
        
        // 2. Sözleşme oluşturuldu
        if ($contract && !empty($contract['created_at'])) {
            $timelineEvents[] = [
                'type' => 'contract_created',
                'datetime' => $contract['created_at'],
                'label' => __('contracts.panel.timeline.contract_created'),
                'description' => __('contracts.panel.timeline.contract_created_desc'),
                'icon' => 'file-contract',
            ];
        }
        
        // 3. SMS gönderildi
        if ($contract && !empty($contract['sms_sent_at'])) {
            $smsCount = (int)($contract['sms_sent_count'] ?? 0);
            $description = $smsCount > 1 
                ? __('contracts.panel.timeline.sms_sent_desc_multi', ['count' => $smsCount])
                : __('contracts.panel.timeline.sms_sent_desc');
            
            $timelineEvents[] = [
                'type' => 'sms_sent',
                'datetime' => $contract['sms_sent_at'],
                'label' => __('contracts.panel.timeline.sms_sent'),
                'description' => $description,
                'icon' => 'sms',
            ];
        }
        
        // 4. Onaylandı
        if ($contract && !empty($contract['approved_at'])) {
            $timelineEvents[] = [
                'type' => 'approved',
                'datetime' => $contract['approved_at'],
                'label' => __('contracts.panel.timeline.approved'),
                'description' => __('contracts.panel.timeline.approved_desc'),
                'icon' => 'check-circle',
            ];
        }
        
        // Tarihe göre sırala (ASC - en eski en üstte)
        usort($timelineEvents, function($a, $b) {
            $timeA = strtotime($a['datetime']);
            $timeB = strtotime($b['datetime']);
            return $timeA <=> $timeB;
        });
        
        echo View::renderWithLayout('jobs/manage', [
            'job' => $job,
            'recurringJob' => $recurringJob,
            'payments' => $payments,
            'occurrences' => $occurrences,
            'isRecurring' => $isRecurring,
            'contract' => $contract,
            'contractStatus' => $contractStatus,
            'timelineEvents' => $timelineEvents,
            'flash' => Utils::getFlash()
        ]);
    }
    
    /**
     * Send contract SMS to customer
     */
    public function sendContractSms($id)
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url("/jobs/manage/{$id}"));
        }
        
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url("/jobs/manage/{$id}"));
        }
        
        // Phase 4.1: Use ControllerTrait for model finding
        $job = $this->findOrFail($this->jobModel, $id, 'İş bulunamadı');
        if (!$job) {
            View::notFound('İş bulunamadı');
            return;
        }
        
        $customer = $this->customerModel->find($job['customer_id']);
        if (!$customer || empty($customer['phone'])) {
            Utils::flash('error', __('contracts.panel.flash.no_phone'));
            redirect(base_url("/jobs/manage/{$id}"));
        }
        
        try {
            $contractTemplateService = new ContractTemplateService();
            $contractOtpService = new ContractOtpService();
            
            // Check if existing contract is expired, recreate if needed
            $contract = $this->contractModel->findByJobId($id);
            $wasExpired = false;
            
            if ($contract && ($contract['status'] === 'EXPIRED' || ($contract['expires_at'] && strtotime($contract['expires_at']) < time()))) {
                // Contract expired, will create new one
                $wasExpired = true;
                $contract = null;
            }
            
            // Create or get existing contract for this job
            if (!$contract) {
                $contract = $contractTemplateService->createJobContractForJob($job, $customer);
                if (!$contract) {
                    Utils::flash('error', __('contracts.panel.flash.generic_error'));
                    redirect(base_url("/jobs/manage/{$id}"));
                }
                
                if ($wasExpired) {
                    Utils::flash('info', __('contracts.panel.flash.contract_recreated'));
                }
            }
            
            // Send OTP via SMS
            $otp = $contractOtpService->createAndSendOtp($contract, $customer, $customer['phone']);
            
            // Update contract status to SENT if it was PENDING
            if ($contract['status'] === 'PENDING') {
                $this->contractModel->updateStatus($contract['id'], 'SENT');
            }
            
            Utils::flash('success', __('contracts.panel.flash.success'));
            
        } catch (Exception $e) {
            error_log("Contract SMS send failed for job {$id}: " . $e->getMessage());
            
            // Check for specific error types
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'template') !== false || strpos($errorMessage, 'şablon') !== false) {
                Utils::flash('error', __('contracts.panel.flash.no_template'));
            } elseif (strpos($errorMessage, 'rate') !== false || strpos($errorMessage, 'bekleyin') !== false) {
                Utils::flash('error', __('contracts.panel.flash.rate_limited'));
            } else {
                Utils::flash('error', __('contracts.panel.flash.generic_error'));
            }
        }
        
        redirect(base_url("/jobs/manage/{$id}"));
    }

    public function delete($id)
    {
        Auth::requireCapability('jobs.delete');

        // Phase 4.1: Use ControllerTrait methods for common patterns
        $job = $this->findOrFail($this->jobModel, $id, 'İş bulunamadı.', '/jobs');
        if (!$job) {
            return;
        }

        if (!$this->requirePostAndCsrf('/jobs')) {
            return;
        }

        // Phase 4.1: Use ControllerTrait for exception handling
        try {
            $this->jobModel->delete($id);
            ActivityLogger::jobDeleted($id, $job['customer_name']);
            
            // ===== ERR-018 FIX: Add audit logging =====
            AuditLogger::getInstance()->logDataModification('JOB_DELETED', Auth::id(), [
                'job_id' => $id,
                'customer_id' => $job['customer_id'] ?? null,
                'customer_name' => $job['customer_name'] ?? null,
                'status' => $job['status'] ?? null,
                'total_amount' => $job['total_amount'] ?? null
            ]);
            // ===== ERR-018 FIX: End =====
            
            // Clear cache
            CacheHelper::clearJobCaches($id);

            $this->flashSuccess('İş başarıyla silindi.', '/jobs');
        } catch (Exception $e) {
            $this->handleException($e, 'JobController::delete()', 'İş silinirken bir hata oluştu', '/jobs');
        }
    }

    public function updateStatus($id)
    {
        Auth::require();

        // Phase 4.1: Use ControllerTrait for common patterns
        $job = $this->findOrFail($this->jobModel, $id, 'İş bulunamadı');
        if (!$job) {
            View::notFound('İş bulunamadı');
            return;
        }

        if (!$this->requirePostAndCsrf('/jobs')) {
            return;
        }

        $status = InputSanitizer::string($_POST['status'] ?? '', AppConstants::MAX_STRING_LENGTH_SHORT);
        $cancelReason = InputSanitizer::string($_POST['cancel_reason'] ?? '', AppConstants::MAX_STRING_LENGTH_LONG);
        // Phase 4.2: Use constants for job status strings
        $validStatuses = [
            AppConstants::JOB_STATUS_SCHEDULED,
            AppConstants::JOB_STATUS_DONE,
            AppConstants::JOB_STATUS_CANCELLED
        ];

        if (!in_array($status, $validStatuses, true)) {
            Utils::flash('error', 'Geçersiz durum.');
            redirect(base_url('/jobs'));
        }

        $oldStatus = $job['status'];
        
        // Phase 4.2: Use constant for status comparison
        if ($status === AppConstants::JOB_STATUS_CANCELLED && $cancelReason !== '') {
            $noteAppend = trim($job['note'] ?? '');
            $noteAppend .= ($noteAppend ? "\n" : '') . 'İptal Nedeni: ' . $cancelReason;
            $this->jobModel->update($id, [
                'customer_id' => $job['customer_id'],
                'start_at' => $job['start_at'],
                'end_at' => $job['end_at'],
                'status' => $status,
                'note' => $noteAppend,
            ]);
        } else {
            $this->jobModel->updateStatus($id, $status);
        }

        ActivityLogger::jobUpdated($id, ['status' => $status]);
        
        // ===== ERR-018 FIX: Add audit logging =====
        AuditLogger::getInstance()->logDataModification('JOB_STATUS_UPDATED', Auth::id(), [
            'job_id' => $id,
            'old_status' => $oldStatus,
            'new_status' => $status
        ]);
        // ===== ERR-018 FIX: End =====
        
        // Send email notification if status changed
        if ($oldStatus !== $status && class_exists('EmailService')) {
            try {
                EmailService::sendJobStatusChange($id, $oldStatus, $status);
            } catch (Exception $e) {
                error_log("Email notification failed: " . $e->getMessage());
            }
        }
        
        // Clear cache
        CacheHelper::clearJobCaches($id);
        NotificationService::clearCache();

        $this->flashSuccess('İş durumu güncellendi.', '/jobs');
    }

    private function parseMoney($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_array($value)) {
            $value = reset($value);
        }

        $normalized = str_replace([' ', ','], ['', '.'], (string)$value);
        return (float)$normalized;
    }

    /**
     * Validate job data from POST request
     * 
     * @param array $postData POST data
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    private function validateJobData(array $postData): array
    {
        $validator = new Validator($postData);
        $validator->required('customer_id', 'Müşteri seçimi zorunludur')
                 ->required('start_at', 'Başlangıç tarihi zorunludur')
                 ->required('end_at', 'Bitiş tarihi zorunludur')
                 ->required('total_amount', 'Toplam tutar zorunludur')
                 ->datetime('start_at', 'Geçerli bir başlangıç tarihi/saati girin (örn: 2024-01-15 14:00)')
                 ->datetime('end_at', 'Geçerli bir bitiş tarihi/saati girin (örn: 2024-01-15 16:00)')
                 ->numeric('total_amount', 'Toplam tutar sayısal bir değer olmalıdır')
                 ->numeric('payment_amount', 'Ödeme tutarı sayısal bir değer olmalıdır')
                 ->date('payment_date', 'Geçerli bir ödeme tarihi girin (YYYY-MM-DD formatında)')
                 ->in('status', ['SCHEDULED', 'DONE', 'CANCELLED'], 'Geçerli bir durum seçin (Planlandı, Tamamlandı, İptal)');

        if ($validator->fails()) {
            return ['valid' => false, 'errors' => [$validator->firstError()], 'data' => null];
        }

        $startAt = new DateTime(str_replace('T', ' ', $validator->get('start_at')));
        $endAt = new DateTime(str_replace('T', ' ', $validator->get('end_at')));

        if ($endAt <= $startAt) {
            return ['valid' => false, 'errors' => ['Bitiş tarihi başlangıç tarihinden sonra olmalıdır.'], 'data' => null];
        }

        $totalAmount = $this->parseMoney($validator->get('total_amount'));
        $paymentAmount = $this->parseMoney($validator->get('payment_amount'));

        if ($totalAmount < 0) {
            return ['valid' => false, 'errors' => ['Toplam tutar 0\'dan küçük olamaz.'], 'data' => null];
        }

        if ($paymentAmount < 0) {
            return ['valid' => false, 'errors' => ['Ödeme tutarı 0\'dan küçük olamaz.'], 'data' => null];
        }

        // Validate foreign keys
        $customerId = (int)$validator->get('customer_id');
        $serviceId = $validator->get('service_id') ? (int)$validator->get('service_id') : null;
        $addressId = $validator->get('address_id') ? (int)$validator->get('address_id') : null;

        // Verify customer exists
        $customerExists = $this->customerModel->find($customerId);
        if (!$customerExists) {
            return ['valid' => false, 'errors' => ['Seçilen müşteri bulunamadı. Lütfen tekrar seçin.'], 'data' => null];
        }

        // Verify service exists (if provided)
        if ($serviceId) {
            $serviceExists = $this->serviceModel->find($serviceId);
            if (!$serviceExists) {
                $serviceId = null;
            }
        }

        // Verify address exists (if provided)
        if ($addressId) {
            $db = Database::getInstance();
            $addressExists = $db->fetch("SELECT id FROM addresses WHERE id = ?", [$addressId]);
            if (!$addressExists) {
                $addressId = null;
            }
        }

        $paymentDate = InputSanitizer::date($postData['payment_date'] ?? date('Y-m-d'), 'Y-m-d') ?: date('Y-m-d');
        $paymentNote = InputSanitizer::string($postData['payment_note'] ?? null, 500);

        return [
            'valid' => true,
            'errors' => [],
            'data' => [
                'customer_id' => $customerId,
                'service_id' => $serviceId,
                'address_id' => $addressId,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'total_amount' => $totalAmount,
                'payment_amount' => $paymentAmount,
                'payment_date' => $paymentDate,
                'payment_note' => $paymentNote,
                'status' => $validator->get('status') ?: 'SCHEDULED',
                'note' => $validator->get('note') ?: null,
            ]
        ];
    }

    /**
     * Create recurring job from job data
     * 
     * @param array $jobData Validated job data
     * @param array $postData Original POST data
     * @return int|null Recurring job ID or null on failure
     * @throws Exception
     */
    private function createRecurringJob(array $jobData, array $postData): ?int
    {
        $frequency = InputSanitizer::string($postData['recurring_frequency'] ?? 'WEEKLY', 50);
        $interval = InputSanitizer::int($postData['recurring_interval'] ?? 1, 1, 365);
        $byweekday = InputSanitizer::array($postData['recurring_byweekday'] ?? [], function($day) {
            return InputSanitizer::int($day, 0, 6);
        });

        $rjId = (new RecurringJob())->create([
            'customer_id' => $jobData['customer_id'],
            'address_id' => $jobData['address_id'],
            'service_id' => $jobData['service_id'],
            'frequency' => $frequency,
            'interval' => $interval,
            'byweekday' => $byweekday,
            'byhour' => (int)$jobData['start_at']->format('H'),
            'byminute' => (int)$jobData['start_at']->format('i'),
            'duration_min' => (int)max(15, ($jobData['end_at']->getTimestamp() - $jobData['start_at']->getTimestamp()) / 60),
            'start_date' => $jobData['start_at']->format('Y-m-d'),
            'end_date' => null,
            'default_total_amount' => $jobData['total_amount'],
            'default_notes' => $jobData['note'] ?? null,
            'status' => 'ACTIVE',
        ]);

        try {
            RecurringGenerator::generateForJob((int)$rjId, 30);
            RecurringGenerator::materializeToJobs((int)$rjId);
        } catch (Exception $e) {
            error_log("Occurrence generation failed for recurring job $rjId: " . $e->getMessage());
        }

        return $rjId;
    }

    /**
     * Create payment for a job
     * 
     * @param int $jobId Job ID
     * @param float $paymentAmount Payment amount
     * @param string $paymentDate Payment date
     * @param string|null $paymentNote Payment note
     * @return void
     * @throws Exception
     */
    private function createJobPayment(int $jobId, float $paymentAmount, string $paymentDate, ?string $paymentNote): void
    {
        // ===== PATH_JOBPAY_STAGE2: Defensive check for invalid job_id =====
        if ($jobId <= 0) {
            error_log("PATH_JOBPAY: createJobPayment called with invalid job_id: " . var_export($jobId, true));
            return; // Silent return, error already logged
        }
        // ===== PATH_JOBPAY_STAGE2 END =====
        
        if ($paymentAmount > 0) {
            PaymentService::createIncomeWithPayment($jobId, $paymentAmount, $paymentDate, $paymentNote);
            
            // ===== PATH_JOBPAY_STAGE3: Log successful payment creation =====
            if (class_exists('Logger')) {
                Logger::info('JOBPAY_CREATE_SUCCESS', [
                    'job_id' => $jobId,
                    'amount' => $paymentAmount,
                    'date' => $paymentDate,
                    'note' => $paymentNote ?? null
                ]);
            }
            // ===== PATH_JOBPAY_STAGE3 END =====
        }
    }

    /**
     * Validate job update data from POST request
     * 
     * @param array $postData POST data
     * @param array $existingJob Existing job data
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    private function validateJobUpdateData(array $postData, array $existingJob): array
    {
        $validator = new Validator($postData);
        $validator->required('customer_id', 'Müşteri seçimi zorunludur')
                 ->required('start_at', 'Başlangıç tarihi zorunludur')
                 ->required('end_at', 'Bitiş tarihi zorunludur')
                 ->required('total_amount', 'Toplam tutar zorunludur')
                 ->datetime('start_at', 'Geçerli bir başlangıç tarihi/saati girin')
                 ->datetime('end_at', 'Geçerli bir bitiş tarihi/saati girin')
                 ->numeric('total_amount', 'Toplam tutar sayısal bir değer olmalıdır')
                 ->numeric('payment_amount', 'Ödeme tutarı sayısal bir değer olmalıdır')
                 ->date('payment_date', 'Geçerli bir ödeme tarihi girin')
                 ->in('status', ['SCHEDULED', 'DONE', 'CANCELLED'], 'Geçerli bir durum seçin');

        if ($validator->fails()) {
            return ['valid' => false, 'errors' => [$validator->firstError()], 'data' => null];
        }

        $startAt = new DateTime(str_replace('T', ' ', $validator->get('start_at')));
        $endAt = new DateTime(str_replace('T', ' ', $validator->get('end_at')));

        if ($endAt <= $startAt) {
            return ['valid' => false, 'errors' => ['Bitiş tarihi başlangıç tarihinden sonra olmalıdır.'], 'data' => null];
        }

        $totalAmount = $this->parseMoney($validator->get('total_amount'));
        $paymentAmount = $this->parseMoney($validator->get('payment_amount'));

        if ($totalAmount < 0) {
            return ['valid' => false, 'errors' => ['Toplam tutar 0\'dan küçük olamaz.'], 'data' => null];
        }

        if ($paymentAmount < 0) {
            return ['valid' => false, 'errors' => ['Ödeme tutarı 0\'dan küçük olamaz.'], 'data' => null];
        }

        $paymentDate = InputSanitizer::date($postData['payment_date'] ?? date('Y-m-d'), 'Y-m-d') ?: date('Y-m-d');
        $paymentNote = InputSanitizer::string($postData['payment_note'] ?? null, 500);

        return [
            'valid' => true,
            'errors' => [],
            'data' => [
                'customer_id' => (int)$validator->get('customer_id'),
                'service_id' => $validator->get('service_id') ? (int)$validator->get('service_id') : null,
                'address_id' => $validator->get('address_id') ? (int)$validator->get('address_id') : null,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'total_amount' => $totalAmount,
                'payment_amount' => $paymentAmount,
                'payment_date' => $paymentDate,
                'payment_note' => $paymentNote,
                'status' => $validator->get('status') ?: 'SCHEDULED',
                'note' => $validator->get('note') ?: null,
            ]
        ];
    }

    /**
     * Update recurring job from job data
     * 
     * @param array $jobData Validated job data
     * @param array $postData Original POST data
     * @param array $existingJob Existing job data
     * @return int|null Recurring job ID or null on failure
     * @throws Exception
     */
    private function updateRecurringJob(array $jobData, array $postData, array $existingJob): ?int
    {
        $frequency = InputSanitizer::string($postData['recurring_frequency'] ?? 'WEEKLY', 50);
        $interval = InputSanitizer::int($postData['recurring_interval'] ?? 1, 1, 365);
        $byweekday = InputSanitizer::array($postData['recurring_byweekday'] ?? [], function($day) {
            return InputSanitizer::int($day, 0, 6);
        });

        $rjId = (new RecurringJob())->create([
            'customer_id' => $existingJob['customer_id'],
            'address_id' => $existingJob['address_id'] ?? null,
            'service_id' => $existingJob['service_id'] ?? null,
            'frequency' => $frequency,
            'interval' => $interval,
            'byweekday' => $byweekday,
            'byhour' => (int)$jobData['start_at']->format('H'),
            'byminute' => (int)$jobData['start_at']->format('i'),
            'duration_min' => (int)max(15, ($jobData['end_at']->getTimestamp() - $jobData['start_at']->getTimestamp()) / 60),
            'start_date' => $jobData['start_at']->format('Y-m-d'),
            'end_date' => null,
            'default_total_amount' => (float)($existingJob['total_amount'] ?? 0),
            'default_notes' => $jobData['note'] ?? null,
            'status' => 'ACTIVE',
        ]);

        try {
            RecurringGenerator::generateForJob((int)$rjId, 30);
            RecurringGenerator::materializeToJobs((int)$rjId);
        } catch (Exception $e) {
            error_log("Occurrence generation failed for recurring job $rjId: " . $e->getMessage());
        }

        return $rjId;
    }

    /**
     * Update payment for a job
     * 
     * @param int $jobId Job ID
     * @param float $paymentAmount Payment amount
     * @param string $paymentDate Payment date
     * @param string|null $paymentNote Payment note
     * @return void
     * @throws Exception
     */
    private function updateJobPayment(int $jobId, float $paymentAmount, string $paymentDate, ?string $paymentNote): void
    {
        if ($paymentAmount > 0) {
            PaymentService::createIncomeWithPayment($jobId, $paymentAmount, $paymentDate, $paymentNote);
        } else {
            Job::syncPayments($jobId);
        }
    }

    /**
     * Export jobs
     */
    public function export()
    {
        Auth::require();
        
        // Rate limiting for export
        $rateLimiter = new ApiRateLimiter();
        if (!$rateLimiter->check('export_jobs_' . Auth::id())) {
            Utils::flash('error', 'Çok fazla export işlemi. Lütfen birkaç dakika sonra tekrar deneyin.');
            redirect(base_url('/jobs'));
        }
        
        $format = InputSanitizer::string($_GET['format'] ?? 'csv', 10);
        $filters = [
            'status' => InputSanitizer::string($_GET['status'] ?? null, 50),
            'customer' => InputSanitizer::string($_GET['customer'] ?? null, 200),
            'date_from' => InputSanitizer::date($_GET['date_from'] ?? null, 'Y-m-d'),
            'date_to' => InputSanitizer::date($_GET['date_to'] ?? null, 'Y-m-d'),
        ];
        
        try {
            $content = ExportService::exportJobs($filters, $format);
            
            // Record successful export
            $rateLimiter->record('export_jobs_' . Auth::id());
            
            $filename = 'jobs_export_' . date('Y-m-d_His') . '.' . ($format === 'csv' ? 'csv' : 'html');
            
            header('Content-Type: ' . ($format === 'csv' ? 'text/csv; charset=UTF-8' : 'text/html; charset=UTF-8'));
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            
            echo $content;
            exit;
        } catch (Exception $e) {
            error_log("Export error: " . $e->getMessage());
            Utils::flash('error', 'Export işlemi sırasında bir hata oluştu. Lütfen tekrar deneyin.');
            redirect(base_url('/jobs'));
        }
    }

    // ===== KOZMOS_BULK_OPERATIONS: bulk operations methods (begin)
    public function bulkStatusUpdate()
    {
        Auth::require();
        
        // Sadece admin ve manager toplu işlem yapabilir
        if (Auth::role() === 'OPERATOR') {
            View::forbidden();
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::error('Geçersiz istek', 405);
        }
        
        $jobIds = InputSanitizer::array($_POST['job_ids'] ?? [], function($id) {
            return InputSanitizer::int($id, 1);
        });
        $jobIds = array_filter($jobIds, function($id) {
            return $id !== null;
        });
        $status = InputSanitizer::string($_POST['status'] ?? '', 50);
        
        if (empty($jobIds) || !is_array($jobIds)) {
            Utils::flash('error', 'Lütfen en az bir iş seçin.');
            redirect(base_url('/jobs'));
        }
        
        if (!in_array($status, ['SCHEDULED', 'DONE', 'CANCELLED'])) {
            Utils::flash('error', 'Geçersiz durum.');
            redirect(base_url('/jobs'));
        }
        
        $db = Database::getInstance();
        $updatedCount = 0;
        
        try {
            $db->beginTransaction();
            
            foreach ($jobIds as $jobId) {
                $jobId = (int)$jobId;
                if ($jobId <= 0) continue;
                
                $result = $db->update('jobs', [
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$jobId]);
                
                if ($result) {
                    $updatedCount++;
                    
                    // Activity log
                    ActivityLogger::log('UPDATE', 'jobs', [
                        'job_id' => $jobId,
                        'action' => 'bulk_status_update',
                        'new_status' => $status,
                        'user_id' => Auth::id()
                    ]);
                }
            }
            
            $db->commit();
            
            // Clear cache after bulk update
            CacheHelper::clearJobCaches();
            NotificationService::clearCache();
            
            if ($updatedCount > 0) {
                Utils::flash('success', "{$updatedCount} işin durumu başarıyla güncellendi.");
            } else {
                Utils::flash('error', 'Hiçbir iş güncellenemedi.');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            ActivityLogger::log('ERROR', 'jobs', [
                'action' => 'bulk_status_update',
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            Utils::flash('error', 'Toplu güncelleme sırasında hata oluştu.');
        }
        
        redirect(base_url('/jobs'));
    }
    
    public function bulkDelete()
    {
        Auth::require();
        
        // Sadece admin ve manager toplu silme yapabilir
        if (Auth::role() === 'OPERATOR') {
            View::forbidden();
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::error('Geçersiz istek', 405);
        }
        
        // ===== ERR-002 FIX: InputSanitizer kullanımı =====
        $jobIds = InputSanitizer::array($_POST['job_ids'] ?? [], function($id) {
            return InputSanitizer::int($id, 1);
        });
        
        if (empty($jobIds) || !is_array($jobIds)) {
            Utils::flash('error', 'Lütfen en az bir iş seçin.');
            redirect(base_url('/jobs'));
        }
        
        $db = Database::getInstance();
        $deletedCount = 0;
        
        try {
            $db->beginTransaction();
            
            foreach ($jobIds as $jobId) {
                $jobId = (int)$jobId;
                if ($jobId <= 0) continue;
                
                // İş ile ilgili finans kayıtlarını kontrol et
                $financeEntries = $db->fetchAll("SELECT id FROM money_entries WHERE job_id = ?", [$jobId]);
                
                if (!empty($financeEntries)) {
                    // Finans kayıtlarını da sil
                    $db->delete('money_entries', 'job_id = ?', [$jobId]);
                }
                
                // İşi sil
                $result = $db->delete('jobs', 'id = ?', [$jobId]);
                
                if ($result) {
                    $deletedCount++;
                    
                    // Activity log
                    ActivityLogger::log('DELETE', 'jobs', [
                        'job_id' => $jobId,
                        'action' => 'bulk_delete',
                        'user_id' => Auth::id()
                    ]);
                }
            }
            
            $db->commit();
            
            // Clear cache after bulk delete
            CacheHelper::clearJobCaches();
            NotificationService::clearCache();
            
            if ($deletedCount > 0) {
                Utils::flash('success', "{$deletedCount} iş başarıyla silindi.");
            } else {
                Utils::flash('error', 'Hiçbir iş silinemedi.');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            ActivityLogger::log('ERROR', 'jobs', [
                'action' => 'bulk_delete',
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            Utils::flash('error', 'Toplu silme sırasında hata oluştu.');
        }
        
        redirect(base_url('/jobs'));
    }
    // ===== KOZMOS_BULK_OPERATIONS: bulk operations methods (end)
}

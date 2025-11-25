<?php
/**
 * API Controller
 */

class ApiController
{
    private $jobModel;
    private $customerModel;
    private $moneyModel;
    
    public function __construct()
    {
        $this->jobModel = new Job();
        $this->customerModel = new Customer();
        $this->moneyModel = new MoneyEntry();
    }
    
    public function jobs()
    {
        Auth::require();
        
        // Apply rate limiting
        require_once 'src/Lib/ApiRateLimiter.php';
        if (!ApiRateLimiter::check('api.jobs', 200, 300)) {
            ApiRateLimiter::sendLimitExceededResponse('api.jobs');
        }
        ApiRateLimiter::record('api.jobs', 200, 300);
        
        require_once 'src/Lib/InputSanitizer.php';
        $date = InputSanitizer::date($_GET['date'] ?? date('Y-m-d'), 'Y-m-d') ?: date('Y-m-d');
        $status = InputSanitizer::string($_GET['status'] ?? '', 20);
        
        $where = ["DATE(j.start_at) = ?"];
        $params = [$date];
        
        if ($status) {
            $where[] = "j.status = ?";
            $params[] = $status;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        $db = Database::getInstance();
        $jobs = $db->fetchAll(
            "SELECT 
                j.*,
                c.name as customer_name,
                c.phone as customer_phone,
                s.name as service_name,
                a.line as address_line
             FROM jobs j
             LEFT JOIN customers c ON j.customer_id = c.id
             LEFT JOIN services s ON j.service_id = s.id
             LEFT JOIN addresses a ON j.address_id = a.id
             $whereClause
             ORDER BY j.start_at",
            $params
        );
        
        View::json([
            'success' => true,
            'data' => $jobs
        ]);
    }
    
    public function customers()
    {
        Auth::require();
        
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            // Create customer via API (quick add)
            if (!CSRF::verifyRequest()) {
                View::json([
                    'success' => false,
                    'error' => 'CSRF do�Yrulaması ba�Yarısız'
                ], 403);
            }
            $raw = file_get_contents('php://input');
            $payload = json_decode($raw, true);
            if (!is_array($payload)) { $payload = $_POST; }
            
            $validator = new Validator($payload);
            $validator->required('name', 'Mü�Yteri adı zorunludur');
            if ($validator->fails()) {
                View::json([
                    'success' => false,
                    'error' => $validator->firstError()
                ], 422);
            }
            
            // Eğer customer_id varsa, mevcut müşteriye adres ekle
            if (!empty($payload['customer_id'])) {
                $customerId = $payload['customer_id'];
                $addressModel = new Address();
                
                if (!empty($payload['addresses']) && isset($payload['addresses'][0])) {
                    $address = $payload['addresses'][0];
                    $addressId = $addressModel->create([
                        'customer_id' => $customerId,
                        'label' => $address['label'] ?? null,
                        'line' => $address['line'] ?? '',
                        'city' => $address['city'] ?? null
                    ]);
                    
                    View::json([
                        'success' => true,
                        'data' => [ 'address_id' => $addressId ]
                    ]);
                } else {
                    View::json([
                        'success' => false,
                        'error' => 'Adres bilgisi gerekli'
                    ], 400);
                }
            } else {
                // Yeni müşteri oluştur
                $customerId = (new Customer())->create([
                    'name' => $validator->get('name'),
                    'phone' => $validator->get('phone'),
                    'email' => $validator->get('email'),
                    'notes' => $validator->get('notes'),
                    'addresses' => !empty($payload['addresses']) ? $payload['addresses'] : (
                        !empty($payload['address_line']) ? [[
                            'label' => $payload['address_label'] ?? null,
                            'line' => $payload['address_line'],
                            'city' => $payload['address_city'] ?? null
                        ]] : []
                    )
                ]);
                
                View::json([
                    'success' => true,
                    'data' => [ 'id' => $customerId ]
                ]);
            }
        } else {
            $search = InputSanitizer::string($_GET['search'] ?? '', 200);
            $limit = InputSanitizer::int($_GET['limit'] ?? 20, 1, 200);
            
            if ($search) {
                $customers = $this->customerModel->search($search, $limit);
            } else {
                $customers = $this->customerModel->all($limit);
            }
            
            View::json([
                'success' => true,
                'data' => $customers
            ]);
        }
    }
    
    public function finance()
    {
        Auth::require();
        
        // Apply rate limiting
        require_once 'src/Lib/ApiRateLimiter.php';
        if (!ApiRateLimiter::check('api.finance', 150, 300)) {
            ApiRateLimiter::sendLimitExceededResponse('api.finance');
        }
        ApiRateLimiter::record('api.finance', 150, 300);
        
        $kind = InputSanitizer::string($_GET['kind'] ?? '', 50);
        $dateFrom = InputSanitizer::date($_GET['date_from'] ?? '', 'Y-m-d');
        $dateTo = InputSanitizer::date($_GET['date_to'] ?? '', 'Y-m-d');
        
        if ($dateFrom && $dateTo) {
            $entries = $this->moneyModel->getByDateRange($dateFrom, $dateTo, $kind);
        } else {
            $entries = $this->moneyModel->all(50);
        }
        
        $totals = [
            'income' => $this->moneyModel->getTotalIncome($dateFrom, $dateTo),
            'expense' => $this->moneyModel->getTotalExpense($dateFrom, $dateTo),
            'profit' => $this->moneyModel->getNetProfit($dateFrom, $dateTo)
        ];
        
        View::json([
            'success' => true,
            'data' => $entries,
            'totals' => $totals
        ]);
    }
    
    /**
     * Calendar API endpoint
     * ROUND 47: JSON-only guarantee + try/catch hardening
     */
    public function calendar($date)
    {
        // ROUND 47: PROD hardening - Clear ALL output buffers and start fresh
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // ROUND 47: Set JSON headers FIRST, before any output or processing
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        
        try {
            // ROUND 47: Auth check - use check() + JSON response instead of require() to avoid exception
            if (!Auth::check()) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Authentication required',
                    'code' => 'AUTH_REQUIRED',
                    'data' => []
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                return;
            }
            
            // Apply rate limiting
            require_once 'src/Lib/ApiRateLimiter.php';
            if (!ApiRateLimiter::check('api.calendar', 200, 300)) {
                ApiRateLimiter::sendLimitExceededResponse('api.calendar');
                ob_end_flush();
                return;
            }
            ApiRateLimiter::record('api.calendar', 200, 300);
            
            // Sanitize date input
            require_once 'src/Lib/InputSanitizer.php';
            $date = InputSanitizer::date($date, 'Y-m-d');
            
            if (!$date) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Geçersiz tarih formatı',
                    'code' => 'INVALID_DATE',
                    'data' => []
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                return;
            }
            
            try {
                // ROUND 47: Safe job fetch with null check
                $jobs = $this->jobModel->getByDateRange($date, $date) ?? [];
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $jobs,
                    'date' => $date
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                return;
            } catch (Throwable $e) {
                // ROUND 47: Log job fetch error
                if (class_exists('AppErrorHandler')) {
                    AppErrorHandler::logException($e, ['context' => 'ApiController::calendar() - job fetch']);
                } else {
                    error_log("ApiController::calendar() - Job fetch error: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
                
                http_response_code(200); // Business decision to return 200 with error in JSON
                echo json_encode([
                    'success' => false,
                    'error' => 'Jobs could not be loaded',
                    'code' => 'JOB_LOAD_ERROR',
                    'data' => []
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                return;
            }
        } catch (Throwable $e) {
            // ROUND 47: Catch ALL exceptions (including Error, not just Exception) and return JSON
            // Clear any partial output
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ob_start(); // Start a new buffer for the error JSON
            
            // ROUND 47: Log error
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $logLine = date('c') . ' ApiController::calendar() - UNEXPECTED ERROR' . PHP_EOL
                . '  User ID: ' . (Auth::check() ? Auth::id() : 'not authenticated') . PHP_EOL
                . '  Role: ' . (Auth::check() ? Auth::role() : 'not authenticated') . PHP_EOL
                . '  URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL
                . '  Date param: ' . ($date ?? 'unknown') . PHP_EOL
                . '  Exception: ' . $e->getMessage() . PHP_EOL
                . '  Stack trace: ' . $e->getTraceAsString() . PHP_EOL
                . '---' . PHP_EOL;
            @file_put_contents($logDir . '/calendar_api_r47.log', $logLine, FILE_APPEND);
            
            if (class_exists('AppErrorHandler')) {
                AppErrorHandler::logException($e, ['context' => 'ApiController::calendar() - outer catch']);
            } else {
                error_log("ApiController::calendar() - UNEXPECTED ERROR: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            // ROUND 47: Always return JSON (not HTML) - ensure headers are set
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
            }
            http_response_code(500); // Changed to 500 for internal errors
            echo json_encode([
                'success' => false,
                'error' => 'Internal server error',
                'code' => 'INTERNAL_ERROR',
                'data' => []
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            return;
        }
    }
    
    public function stats()
    {
        Auth::require();
        Auth::requireAdmin();
        
        // Apply rate limiting (stricter for admin endpoints)
        require_once 'src/Lib/ApiRateLimiter.php';
        if (!ApiRateLimiter::check('api.stats', 50, 600)) {
            ApiRateLimiter::sendLimitExceededResponse('api.stats');
        }
        ApiRateLimiter::record('api.stats', 50, 600);
        
        $jobStats = $this->jobModel->getStats();
        $customerStats = $this->customerModel->getStats();
        $moneyStats = [
            'total_income' => $this->moneyModel->getTotalIncome(),
            'total_expense' => $this->moneyModel->getTotalExpense(),
            'net_profit' => $this->moneyModel->getNetProfit()
        ];
        
        View::json([
            'success' => true,
            'data' => [
                'jobs' => $jobStats,
                'customers' => $customerStats,
                'money' => $moneyStats
            ]
        ]);
    }
    
    public function activity()
    {
        Auth::require();
        Auth::requireAdmin();
        
        // Apply rate limiting (stricter for admin endpoints)
        require_once 'src/Lib/ApiRateLimiter.php';
        if (!ApiRateLimiter::check('api.activity', 50, 600)) {
            ApiRateLimiter::sendLimitExceededResponse('api.activity');
        }
        ApiRateLimiter::record('api.activity', 50, 600);
        
        require_once 'src/Lib/InputSanitizer.php';
        $limit = InputSanitizer::int($_GET['limit'] ?? 50, 1, 200);
        $offset = InputSanitizer::int($_GET['offset'] ?? 0, 0, 10000);
        
        $logs = ActivityLogger::getLogs($limit, $offset);
        $total = ActivityLogger::getLogsCount();
        
        View::json([
            'success' => true,
            'data' => $logs,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    public function searchCustomers()
    {
        Auth::require();
        
        // Apply rate limiting
        require_once 'src/Lib/ApiRateLimiter.php';
        if (!ApiRateLimiter::check('api.customers.search', 200, 300)) {
            ApiRateLimiter::sendLimitExceededResponse('api.customers.search');
        }
        
        ApiRateLimiter::record('api.customers.search', 200, 300);
        
        require_once 'src/Lib/InputSanitizer.php';
        require_once 'src/Constants/AppConstants.php';
        $query = InputSanitizer::string($_GET['q'] ?? '', 100);
        // Phase 4.2: Use constant for search minimum length
        if (strlen($query) < AppConstants::SEARCH_MIN_LENGTH) {
            View::json([
                'success' => true,
                'data' => []
            ]);
        }
        
        $customers = $this->customerModel->search($query, 10);
        
        View::json([
            'success' => true,
            'data' => $customers
        ]);
    }
    
    public function createCustomer()
    {
        Auth::require();
        
        // JSON input'u al
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['name']) || empty(trim($input['name']))) {
            View::json([
                'success' => false,
                'error' => 'Mü�Yteri adı gerekli'
            ], 400);
        }
        
        $data = [
            'name' => trim($input['name']),
            'phone' => $input['phone'] ?? null,
            'email' => $input['email'] ?? null,
            'notes' => $input['notes'] ?? null
        ];
        
        try {
            $customerId = $this->customerModel->create($data);
            
            if ($customerId) {
                // Activity log
                ActivityLogger::log('customer_created', 'customer', ['customer_id' => $customerId, 'name' => $data['name']]);
                
                $customer = $this->customerModel->find($customerId);
                
                View::json([
                    'success' => true,
                    'data' => $customer
                ]);
            } else {
                View::json([
                    'success' => false,
                    'error' => 'Mü�Yteri olu�Yturulamadı'
                ], 500);
            }
        } catch (Exception $e) {
            View::json([
                'success' => false,
                'error' => 'Bir hata olu�Ytu: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function customerAddresses($customerId)
    {
        Auth::require();
        
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            View::json([
                'success' => false,
                'error' => 'Mü�Yteri bulunamadı'
            ], 404);
        }
        
        $db = Database::getInstance();
        $addresses = $db->fetchAll(
            "SELECT * FROM addresses WHERE customer_id = ? ORDER BY created_at",
            [$customerId]
        );
        
        View::json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    public function recurringPreview()
    {
        // ===== LOGIN_500_PATHC: Log API start =====
        if (class_exists('PathCLogger')) {
            PathCLogger::log('API_RECURRING_PREVIEW_START', ['path' => '/api/recurring/preview']);
        }
        // ===== LOGIN_500_PATHC END =====
        
        Auth::require();
        
        // Apply rate limiting
        require_once 'src/Lib/ApiRateLimiter.php';
        if (!ApiRateLimiter::check('api.recurring.preview', 100, 300)) {
            ApiRateLimiter::sendLimitExceededResponse('api.recurring.preview');
        }
        ApiRateLimiter::record('api.recurring.preview', 100, 300);
        
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) { $payload = $_POST; }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $payload = $_GET;
        }
        $limit = InputSanitizer::int($payload['limit'] ?? ($_GET['limit'] ?? 10), 1, 200);
        $def = [
            'frequency' => $payload['frequency'] ?? 'WEEKLY',
            'interval' => $payload['interval'] ?? 1,
            'byweekday' => $payload['byweekday'] ?? [],
            'bymonthday' => isset($payload['bymonthday']) ? (int)$payload['bymonthday'] : null,
            'byhour' => $payload['byhour'] ?? 9,
            'byminute' => $payload['byminute'] ?? 0,
            'duration_min' => $payload['duration_min'] ?? 60,
            'start_date' => $payload['start_date'] ?? date('Y-m-d'),
            'end_date' => $payload['end_date'] ?? null,
            'timezone' => $payload['timezone'] ?? 'Europe/Istanbul',
            'exclusions' => $payload['exclusions'] ?? [],
        ];
        try {
            $out = RecurringGenerator::preview($def, $limit);
            
            // ===== LOGIN_500_PATHC: Log API success =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('API_RECURRING_PREVIEW_SUCCESS', ['path' => '/api/recurring/preview']);
            }
            // ===== LOGIN_500_PATHC END =====
            
            View::json(['success' => true, 'data' => $out]);
        } catch (Throwable $e) {
            // ===== LOGIN_500_PATHC: Log exception =====
            if (class_exists('PathCLogger')) {
                PathCLogger::logException('API_RECURRING_PREVIEW_EXCEPTION', $e, ['path' => '/api/recurring/preview']);
            }
            // ===== LOGIN_500_PATHC END =====
            
            // Graceful fallback JSON
            View::json(['success' => false, 'error' => 'recurring_preview_unavailable'], 500);
        }
    }

    public function addCustomerAddress($customerId)
    {
        Auth::require();
        
        // Apply rate limiting
        require_once 'src/Lib/ApiRateLimiter.php';
        if (!ApiRateLimiter::check('api.addresses.create', 50, 300)) {
            ApiRateLimiter::sendLimitExceededResponse('api.addresses.create');
        }
        ApiRateLimiter::record('api.addresses.create', 50, 300);
        
        // Accept token via header for fetch JSON requests
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!CSRF::verify($headerToken)) {
            // Fallback to form param if needed
            if (!CSRF::verifyRequest()) {
                View::json(['success' => false, 'error' => 'CSRF doğrulaması başarısız'], 403);
            }
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) { $payload = $_POST; }

        require_once 'src/Lib/InputSanitizer.php';
        // ===== ERR-011 FIX: Add min/max validation =====
        $customerId = InputSanitizer::int($customerId, 1);
        if (!$customerId) {
            View::json(['success' => false, 'error' => 'Geçersiz müşteri ID'], 400);
        }

        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            View::json(['success' => false, 'error' => 'Müşteri bulunamadı'], 404);
        }

        $line = InputSanitizer::string(trim($payload['line'] ?? ''), 500);
        if ($line === '') {
            View::json(['success' => false, 'error' => 'Adres satırı gerekli'], 422);
        }

        $addressId = (new Address())->create([
            'customer_id' => $customerId,
            'label' => InputSanitizer::string($payload['label'] ?? null, 100),
            'line' => $line,
            'city' => InputSanitizer::string($payload['city'] ?? null, 100),
        ]);

        View::json(['success' => true, 'data' => ['address_id' => $addressId]]);
    }
    
    public function jobStatus($id)
    {
        Auth::require();
        
        $job = $this->jobModel->find($id);
        if (!$job) {
            View::json([
                'success' => false,
                'error' => 'İ�Y bulunamadı'
            ], 404);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF kontrolü
            if (!CSRF::verifyRequest()) {
                View::json([
                    'success' => false,
                    'error' => 'Güvenlik hatası'
                ], 403);
            }
            
            $status = InputSanitizer::string($_POST['status'] ?? '', 50);
            // Phase 4.2: Use constants for job status strings
            $validStatuses = [
                AppConstants::JOB_STATUS_SCHEDULED,
                AppConstants::JOB_STATUS_DONE,
                AppConstants::JOB_STATUS_CANCELLED
            ];
            
            if (!in_array($status, $validStatuses)) {
                View::json([
                    'success' => false,
                    'error' => 'Geçersiz durum'
                ], 400);
            }
            
            $this->jobModel->updateStatus($id, $status);
            ActivityLogger::jobUpdated($id, ['status' => $status]);
            
            View::json([
                'success' => true,
                'message' => 'İ�Y durumu güncellendi'
            ]);
        }
        
        View::json([
            'success' => true,
            'data' => [
                'id' => $job['id'],
                'status' => $job['status']
            ]
        ]);
    }
    
    public function updateJobDate($id)
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::json([
                'success' => false,
                'error' => 'Geçersiz istek'
            ], 405);
        }
        
        $job = $this->jobModel->find($id);
        if (!$job) {
            View::json([
                'success' => false,
                'error' => 'İş bulunamadı'
            ], 404);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $newDate = $input['date'] ?? '';
        
        if (!$newDate) {
            View::json([
                'success' => false,
                'error' => 'Tarih gerekli'
            ], 400);
        }
        
        try {
            // Mevcut tarih ve saatleri al
            $startAt = new DateTime($job['start_at']);
            $endAt = new DateTime($job['end_at']);
            
            // Yeni tarihi ayarla
            $newStartAt = new DateTime($newDate . ' ' . $startAt->format('H:i:s'));
            $newEndAt = new DateTime($newDate . ' ' . $endAt->format('H:i:s'));
            
            // İşi güncelle
            $this->jobModel->update($id, [
                'start_at' => $newStartAt->format('Y-m-d H:i:s'),
                'end_at' => $newEndAt->format('Y-m-d H:i:s')
            ]);
            
            // Aktivite log
            ActivityLogger::log('job_date_updated', 'job', [
                'job_id' => $id,
                'old_date' => $startAt->format('Y-m-d'),
                'new_date' => $newDate
            ]);
            
            View::json([
                'success' => true,
                'message' => 'İş tarihi güncellendi'
            ]);
        } catch (Exception $e) {
            View::json([
                'success' => false,
                'error' => 'Tarih güncellenirken hata oluştu'
            ], 500);
        }
    }

    public function todayWorkingStaff()
    {
        // ===== ERR-015 FIX: Add authentication check =====
        Auth::require();
        // ===== ERR-015 FIX: End =====
        
        try {
            $staffModel = new Staff();
            $attendanceModel = new StaffAttendance();
            
            $todayWorking = $attendanceModel->getTodayAttendance();
            $absentToday = $attendanceModel->getAbsentToday();
            
            View::json([
                'success' => true,
                'data' => [
                    'working_count' => count($todayWorking),
                    'absent_count' => count($absentToday),
                    'total_staff' => count($staffModel->getActive()),
                    'working_staff' => $todayWorking,
                    'absent_staff' => $absentToday
                ]
            ]);
        } catch (Exception $e) {
            View::json([
                'success' => false,
                'error' => 'Veri alınamadı: ' . $e->getMessage()
            ], 500);
        }
    }

    public function monthlySalary()
    {
        // ===== ERR-015 FIX: Add authentication check =====
        Auth::require();
        Auth::requireAdmin(); // Salary data is sensitive, require admin access
        // ===== ERR-015 FIX: End =====
        
        try {
            $staffModel = new Staff();
            $month = InputSanitizer::date($_GET['month'] ?? date('Y-m'), 'Y-m') ?: date('Y-m');
            
            $stats = $staffModel->getTotalSalary($month);
            
            View::json([
                'success' => true,
                'data' => [
                    'total' => $stats['total_salary'] + $stats['total_hourly_pay'] + $stats['total_overtime_pay'],
                    'salary' => $stats['total_salary'],
                    'hourly_pay' => $stats['total_hourly_pay'],
                    'overtime_pay' => $stats['total_overtime_pay'],
                    'month' => $month
                ]
            ]);
        } catch (Exception $e) {
            View::json([
                'success' => false,
                'error' => 'Veri alınamadı: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Global Search - Search across all entities
     */
    public function globalSearch()
    {
        Auth::require();
        
        // Rate limiting
        require_once __DIR__ . '/../Lib/ApiRateLimiter.php';
        if (!ApiRateLimiter::check('api.global-search', 50, 60)) {
            ApiRateLimiter::sendLimitExceededResponse('api.global-search');
        }
        ApiRateLimiter::record('api.global-search', 50, 60);
        
        require_once __DIR__ . '/../Lib/InputSanitizer.php';
        $query = InputSanitizer::string($_GET['q'] ?? '', 100);
        
        if (strlen($query) < 2) {
            View::json([
                'success' => true,
                'results' => []
            ]);
        }
        
        $results = [];
        $db = Database::getInstance();
        
        try {
            // Search Jobs
            $jobs = $db->fetchAll(
                "SELECT j.id, j.status, j.start_at, c.name as customer_name, s.name as service_name
                 FROM jobs j
                 LEFT JOIN customers c ON j.customer_id = c.id
                 LEFT JOIN services s ON j.service_id = s.id
                 WHERE (c.name LIKE ? OR j.note LIKE ? OR s.name LIKE ?)
                 ORDER BY j.start_at DESC
                 LIMIT 5",
                ["%{$query}%", "%{$query}%", "%{$query}%"]
            );
            
            foreach ($jobs as $job) {
                $results[] = [
                    'type' => 'job',
                    'id' => $job['id'],
                    'title' => $job['customer_name'] . ' - ' . ($job['service_name'] ?? 'İş'),
                    'subtitle' => $job['status'] . ' • ' . date('d.m.Y H:i', strtotime($job['start_at'])),
                    'url' => base_url('/jobs/' . $job['id']),
                    'icon' => 'fas fa-tasks'
                ];
            }
            
            // Search Customers
            $customers = $db->fetchAll(
                "SELECT id, name, phone, email FROM customers 
                 WHERE name LIKE ? OR phone LIKE ? OR email LIKE ?
                 ORDER BY name
                 LIMIT 5",
                ["%{$query}%", "%{$query}%", "%{$query}%"]
            );
            
            foreach ($customers as $customer) {
                $results[] = [
                    'type' => 'customer',
                    'id' => $customer['id'],
                    'title' => $customer['name'],
                    'subtitle' => ($customer['phone'] ?? '') . ($customer['email'] ? ' • ' . $customer['email'] : ''),
                    'url' => base_url('/customers/' . $customer['id']),
                    'icon' => 'fas fa-user'
                ];
            }
            
            // Search Services
            $services = $db->fetchAll(
                "SELECT id, name FROM services 
                 WHERE name LIKE ? AND is_active = 1
                 ORDER BY name
                 LIMIT 3",
                ["%{$query}%"]
            );
            
            foreach ($services as $service) {
                $results[] = [
                    'type' => 'service',
                    'id' => $service['id'],
                    'title' => $service['name'],
                    'subtitle' => 'Hizmet',
                    'url' => base_url('/services/' . $service['id']),
                    'icon' => 'fas fa-concierge-bell'
                ];
            }
            
        } catch (Exception $e) {
            View::json([
                'success' => false,
                'error' => 'Arama sırasında hata oluştu'
            ], 500);
        }
        
        View::json([
            'success' => true,
            'results' => $results,
            'query' => $query
        ]);
    }

    public function services()
    {
        // ROUND 44: PROD hardening - Clear ALL output buffers and start fresh
        // End all existing output buffers to ensure clean start
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // ROUND 44: Start fresh output buffer
        ob_start();
        
        // ROUND 44: Set JSON headers FIRST, before any output or processing
        // This MUST be done before any exception can occur
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        
        // ROUND 44: KAPSAYICI TRY/CATCH - Tüm method'u sar, global error handler'a ulaşmasın
        try {
            // Check auth first - if not authenticated, return JSON error (not redirect)
            if (!Auth::check()) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Authentication required',
                    'code' => 'AUTH_REQUIRED',
                    'data' => []
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                exit;
            }
            
            // ROUND 31: Handle service model errors gracefully
            try {
                $services = (new Service())->all();
                
                // Ensure services is an array
                if (!is_array($services)) {
                    $services = [];
                }
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $services
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                exit;
            } catch (Throwable $e) {
                // ROUND 31: Log error and return JSON error (never HTML)
                if (class_exists('AppErrorHandler')) {
                    AppErrorHandler::logException($e, ['context' => 'ApiController::services() - Service model error']);
                } else {
                    error_log("ApiController::services() - Service model error: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
                
                // ROUND 31: Return 200 with error in JSON (business decision - don't break frontend)
                http_response_code(200);
                echo json_encode([
                    'success' => false,
                    'error' => 'Services could not be loaded',
                    'code' => 'SERVICE_LOAD_ERROR',
                    'data' => []
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                exit;
            }
        } catch (Throwable $e) {
            // ROUND 44: Catch ALL exceptions (including Error, not just Exception) and return JSON
            // Clear any partial output
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ob_start();
            
            // ROUND 44: Log error with full context
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $logLine = date('c') . ' ApiController::services() - UNEXPECTED ERROR' . PHP_EOL
                . '  User ID: ' . (Auth::check() ? Auth::id() : 'not authenticated') . PHP_EOL
                . '  Role: ' . (Auth::check() ? Auth::role() : 'not authenticated') . PHP_EOL
                . '  URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL
                . '  Request Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . PHP_EOL
                . '  GET params: ' . json_encode($_GET) . PHP_EOL
                . '  Exception: ' . $e->getMessage() . PHP_EOL
                . '  Stack trace: ' . $e->getTraceAsString() . PHP_EOL
                . '---' . PHP_EOL;
            @file_put_contents($logDir . '/api_services_r44.log', $logLine, FILE_APPEND);
            
            if (class_exists('AppErrorHandler')) {
                AppErrorHandler::logException($e, [
                    'context' => 'ApiController::services() - outer catch',
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'role' => Auth::check() ? Auth::role() : null,
                    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
            } else {
                error_log("ApiController::services() - UNEXPECTED ERROR: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            // ROUND 44: Always return JSON (not HTML) - ensure headers are set
            // Global error handler'a ulaşmasın
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
            }
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Internal server error',
                'code' => 'INTERNAL_ERROR',
                'data' => []
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            exit;
        }
    }

    public function notificationsList()
    {
        // ===== LOGIN_500_PATHC: Log API start =====
        if (class_exists('PathCLogger')) {
            PathCLogger::log('API_NOTIFICATIONS_LIST_START', ['path' => '/api/notifications/list']);
        }
        // ===== LOGIN_500_PATHC END =====
        
        try {
            Auth::require();
            $list = class_exists('NotificationService') ? NotificationService::getHeaderNotifications(20) : [];
            
            // ===== LOGIN_500_PATHC: Log API success =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('API_NOTIFICATIONS_LIST_SUCCESS', ['path' => '/api/notifications/list']);
            }
            // ===== LOGIN_500_PATHC END =====
            
            View::json(['success' => true, 'data' => $list]);
        } catch (Throwable $e) {
            // ===== LOGIN_500_PATHC: Log exception =====
            if (class_exists('PathCLogger')) {
                PathCLogger::logException('API_NOTIFICATIONS_LIST_EXCEPTION', $e, ['path' => '/api/notifications/list']);
            }
            // ===== LOGIN_500_PATHC END =====
            
            // Graceful fallback JSON
            View::json(['success' => false, 'error' => 'notifications_unavailable', 'data' => []], 500);
        }
    }

    public function notificationsMarkAllRead()
    {
        Auth::require();
        // Accept header token for fetch
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!CSRF::verify($headerToken)) {
            if (!CSRF::verifyRequest()) {
                View::json(['success' => false, 'error' => 'CSRF doğrulaması başarısız'], 403);
            }
        }
        $count = class_exists('NotificationService') ? NotificationService::markAllRead() : 0;
        View::json(['success' => true, 'marked' => $count]);
    }

    public function notificationsPrefs()
    {
        Auth::require();
        $prefs = class_exists('NotificationService') ? NotificationService::getPrefs() : ['critical'=>0,'ops'=>0,'system'=>0];
        View::json(['success'=>true, 'data'=>$prefs]);
    }

    public function notificationsMute()
    {
        Auth::require();
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!CSRF::verify($headerToken)) { if (!CSRF::verifyRequest()) { View::json(['success'=>false,'error'=>'CSRF'],403); } }
        $type = InputSanitizer::string($_POST['type'] ?? ($_GET['type'] ?? ''), 50);
        $muted = (int)($_POST['muted'] ?? ($_GET['muted'] ?? 0)) === 1;
        $ok = class_exists('NotificationService') ? NotificationService::setMuted($type, $muted) : false;
        View::json(['success'=>$ok]);
    }

    public function notificationsMarkRead()
    {
        Auth::require();
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!CSRF::verify($headerToken)) {
            if (!CSRF::verifyRequest()) {
                View::json(['success'=>false,'error'=>'CSRF'],403);
            }
        }

        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $key = InputSanitizer::string($_POST['key'] ?? ($payload['key'] ?? ($_GET['key'] ?? '')), 200);
        if ($key === '') {
            View::json(['success'=>false,'error'=>'key required'], 400);
        }

        $state = InputSanitizer::string($_POST['state'] ?? ($payload['state'] ?? ($_GET['state'] ?? 'read')), 20);
        $state = strtolower((string)$state) === 'unread' ? 'unread' : 'read';

        $count = 0;
        if (class_exists('Auth') && ($uid = Auth::id())) {
            $db = Database::getInstance();
            if ($state === 'unread') {
                $count = $db->delete('notifications_read', 'user_id = ? AND notif_key = ?', [$uid, $key]);
            } else {
                try {
                    $db->insert('notifications_read', [
                        'user_id' => $uid,
                        'notif_key' => $key,
                        'read_at' => date('Y-m-d H:i:s')
                    ]);
                    $count = 1;
                } catch (Exception $e) {
                    // duplicate entry means already read, treat as success
                    $count = 0;
                }
            }
        }

        View::json([
            'success'=>true,
            'marked'=>$count,
            'state'=>$state
        ]);
    }

    public function customer($id)
    {
        Auth::require();
        
        $customer = (new Customer())->find($id);
        if (!$customer) {
            View::json(['success' => false, 'error' => 'Müşteri bulunamadı'], 404);
        }
        
        View::json(['success' => true, 'data' => $customer]);
    }
}

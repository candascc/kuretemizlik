<?php
/**
 * Calendar Controller
 */

class CalendarController
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
     * Calendar index page
     * ROUND 47: First-load 500 hardening - kapsayıcı try/catch + safe defaults
     */
    public function index()
    {
        // ROUND 47: KAPSAYICI TRY/CATCH - Tüm method'u sar, global error handler'a ulaşmasın
        try {
            // ROUND 47: Auth check - use check() + redirect instead of require() to avoid exception
            if (!Auth::check()) {
                Utils::flash('error', 'Bu sayfaya erişmek için giriş yapmanız gerekiyor.');
                redirect(base_url('/login'));
                return;
            }
            
            // ROUND 47: Initialize with safe defaults BEFORE any DB operations
            $view = $_GET['view'] ?? 'month';
            $date = $_GET['date'] ?? date('Y-m-d');
            
            // Geçerli tarih kontrolü
            try {
                $currentDate = new DateTime($date);
            } catch (Exception $e) {
                $currentDate = new DateTime();
            }
            
            $jobs = [];
            $startDate = '';
            $endDate = '';
            
            // ROUND 47: Safe date range calculation with try/catch
            try {
                switch ($view) {
                    case 'day':
                        $startDate = $currentDate->format('Y-m-d');
                        $endDate = $startDate;
                        $jobs = $this->jobModel->getByDateRange($startDate, $endDate) ?? [];
                        break;
                        
                    case 'week':
                        $weekStart = clone $currentDate;
                        $weekStart->modify('monday this week');
                        $weekEnd = clone $currentDate;
                        $weekEnd->modify('sunday this week');
                        $startDate = $weekStart->format('Y-m-d');
                        $endDate = $weekEnd->format('Y-m-d');
                        $jobs = $this->jobModel->getByDateRange($startDate, $endDate) ?? [];
                        break;
                        
                    case 'month':
                        $monthStart = clone $currentDate;
                        $monthStart->modify('first day of this month');
                        $monthEnd = clone $currentDate;
                        $monthEnd->modify('last day of this month');
                        $startDate = $monthStart->format('Y-m-d');
                        $endDate = $monthEnd->format('Y-m-d');
                        $jobs = $this->jobModel->getByDateRange($startDate, $endDate) ?? [];
                        break;
                }
            } catch (Throwable $e) {
                // ROUND 47: Log date range calculation error
                error_log("CalendarController::index() - Date range calculation error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                // Use safe defaults - empty jobs array
                $jobs = [];
            }
            
            // ROUND 47: Müşteriler ve hizmetler (form için) - safe defaults
            try {
                $customers = $this->customerModel->all() ?? [];
            } catch (Throwable $e) {
                error_log("CalendarController::index() - Customer fetch error: " . $e->getMessage());
                $customers = [];
            }
            
            try {
                $services = $this->serviceModel->getActive() ?? [];
            } catch (Throwable $e) {
                error_log("CalendarController::index() - Service fetch error: " . $e->getMessage());
                $services = [];
            }
            
            // ROUND 47: Ensure all data is array (not null)
            $jobs = is_array($jobs) ? $jobs : [];
            $customers = is_array($customers) ? $customers : [];
            $services = is_array($services) ? $services : [];
            
            echo View::renderWithLayout('calendar/index', [
                'view' => $view,
                'date' => $date,
                'currentDate' => $currentDate,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'jobs' => $jobs,
                'customers' => $customers,
                'services' => $services,
                'flash' => Utils::getFlash()
            ]);
        } catch (Throwable $e) {
            // ROUND 47: KAPSAYICI CATCH - Tüm beklenmeyen exception'ları yakala
            // Clear any partial output
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // ROUND 47: Log error with full context
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $logLine = date('c') . ' CalendarController::index() - UNEXPECTED ERROR' . PHP_EOL
                . '  User ID: ' . (Auth::check() ? Auth::id() : 'not authenticated') . PHP_EOL
                . '  Role: ' . (Auth::check() ? Auth::role() : 'not authenticated') . PHP_EOL
                . '  URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL
                . '  Request Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . PHP_EOL
                . '  GET params: ' . json_encode($_GET) . PHP_EOL
                . '  Exception: ' . $e->getMessage() . PHP_EOL
                . '  Stack trace: ' . $e->getTraceAsString() . PHP_EOL
                . '---' . PHP_EOL;
            @file_put_contents($logDir . '/calendar_r47.log', $logLine, FILE_APPEND);
            
            if (class_exists('AppErrorHandler')) {
                AppErrorHandler::logException($e, [
                    'context' => 'CalendarController::index() - outer catch',
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'role' => Auth::check() ? Auth::role() : null,
                    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
            } else {
                error_log("CalendarController::index() - UNEXPECTED ERROR: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            // ROUND 47: Kullanıcıya 200 status ile error view göster (500 DEĞİL)
            // Global error handler'a ulaşmasın
            View::error('Takvim yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.', 200);
            return;
        }
    }

    public function sync()
    {
        Auth::require();
        $provider = $_GET['provider'] ?? 'google';
        try {
            $res = CalendarSyncService::initialSync(Auth::id(), $provider);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'result' => $res]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function create()
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/calendar'));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url('/calendar'));
        }
        
        // Validasyon
        $validator = new Validator($_POST);
        $validator->required('customer_id', 'Müşteri seçimi zorunludur')
                 ->required('start_at', 'Başlangıç tarihi zorunludur')
                 ->required('end_at', 'Bitiş tarihi zorunludur')
                 ->datetime('start_at', 'Geçerli bir başlangıç tarihi girin')
                 ->datetime('end_at', 'Geçerli bir bitiş tarihi girin');
        
        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            redirect(base_url('/calendar'));
        }
        
        // Tarih kontrolü
        $startAt = new DateTime($validator->get('start_at'));
        $endAt = new DateTime($validator->get('end_at'));
        
        if ($endAt <= $startAt) {
            Utils::flash('error', 'Bitiş tarihi başlangıç tarihinden sonra olmalıdır.');
            redirect(base_url('/calendar'));
        }
        
        // İş oluştur
        $jobData = [
            'service_id' => $validator->get('service_id') ?: null,
            'customer_id' => $validator->get('customer_id'),
            'address_id' => $validator->get('address_id') ?: null,
            'start_at' => $validator->get('start_at'),
            'end_at' => $validator->get('end_at'),
            'note' => $validator->get('note') ?: null
        ];
        
        $jobId = $this->jobModel->create($jobData);
        
        // Aktivite log
        $customer = $this->customerModel->find($jobData['customer_id']);
        ActivityLogger::jobCreated($jobId, $customer['name']);
        
        Utils::flash('success', 'İş başarıyla oluşturuldu.');
        redirect(base_url('/calendar'));
    }
    
    public function update($id)
    {
        Auth::require();
        
        $job = $this->jobModel->find($id);
        if (!$job) {
            View::notFound('İş bulunamadı');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/calendar'));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url('/calendar'));
        }
        
        // Validasyon
        $validator = new Validator($_POST);
        $validator->required('customer_id', 'Müşteri seçimi zorunludur')
                 ->required('start_at', 'Başlangıç tarihi zorunludur')
                 ->required('end_at', 'Bitiş tarihi zorunludur')
                 ->datetime('start_at', 'Geçerli bir başlangıç tarihi girin')
                 ->datetime('end_at', 'Geçerli bir bitiş tarihi girin');
        
        if ($validator->fails()) {
            Utils::flash('error', $validator->firstError());
            redirect(base_url('/calendar'));
        }
        
        // Tarih kontrolü
        $startAt = new DateTime($validator->get('start_at'));
        $endAt = new DateTime($validator->get('end_at'));
        
        if ($endAt <= $startAt) {
            Utils::flash('error', 'Bitiş tarihi başlangıç tarihinden sonra olmalıdır.');
            redirect(base_url('/calendar'));
        }
        
        // Değişiklikleri takip et
        $changes = [];
        if ($job['customer_id'] != $validator->get('customer_id')) {
            $changes['customer_id'] = $validator->get('customer_id');
        }
        if ($job['start_at'] != $validator->get('start_at')) {
            $changes['start_at'] = $validator->get('start_at');
        }
        if ($job['end_at'] != $validator->get('end_at')) {
            $changes['end_at'] = $validator->get('end_at');
        }
        
        // İş güncelle
        $jobData = [
            'service_id' => $validator->get('service_id') ?: null,
            'customer_id' => $validator->get('customer_id'),
            'address_id' => $validator->get('address_id') ?: null,
            'start_at' => $validator->get('start_at'),
            'end_at' => $validator->get('end_at'),
            'note' => $validator->get('note') ?: null
        ];
        
        $this->jobModel->update($id, $jobData);
        
        // Aktivite log
        if (!empty($changes)) {
            ActivityLogger::jobUpdated($id, $changes);
        }
        
        Utils::flash('success', 'İş başarıyla güncellendi.');
        redirect(base_url('/calendar'));
    }
    
    public function delete($id)
    {
        Auth::require();
        
        $job = $this->jobModel->find($id);
        if (!$job) {
            View::notFound('İş bulunamadı');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/calendar'));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url('/calendar'));
        }
        
        // İş sil
        $this->jobModel->delete($id);
        
        // Aktivite log
        ActivityLogger::jobDeleted($id, $job['customer_name']);
        
        Utils::flash('success', 'İş başarıyla silindi.');
        redirect(base_url('/calendar'));
    }
    
    public function updateStatus($id)
    {
        Auth::require();
        
        $job = $this->jobModel->find($id);
        if (!$job) {
            View::notFound('İş bulunamadı');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/calendar'));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url('/calendar'));
        }
        
        $status = $_POST['status'] ?? '';
        $validStatuses = ['SCHEDULED', 'DONE', 'CANCELLED'];
        
        if (!in_array($status, $validStatuses)) {
            Utils::flash('error', 'Geçersiz durum.');
            redirect(base_url('/calendar'));
        }
        
        // Durum güncelle
        $this->jobModel->updateStatus($id, $status);
        
        // Aktivite log
        ActivityLogger::jobUpdated($id, ['status' => $status]);
        
        Utils::flash('success', 'İş durumu güncellendi.');
        redirect(base_url('/calendar'));
    }
}

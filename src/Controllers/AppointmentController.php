<?php

declare(strict_types=1);

/**
 * Appointment Controller
 */

require_once __DIR__ . '/../Constants/AppConstants.php';
require_once __DIR__ . '/../Lib/ControllerHelper.php';

class AppointmentController
{
    use CompanyScope;

    private $appointmentModel;
    private $customerModel;
    private $serviceModel;
    private $userModel;

    public function __construct()
    {
        $this->appointmentModel = new Appointment();
        $this->customerModel = new Customer();
        $this->serviceModel = new Service();
        $this->userModel = new User();
    }

    /**
     * Randevu listesi
     */
    public function index()
    {
        Auth::require();
        
        // ===== PRODUCTION FIX: Prevent caching of appointment list page =====
        Utils::setNoCacheHeaders();
        // ===== PRODUCTION FIX END =====
        
        $page = (int)($_GET['page'] ?? 1);
        $status = $_GET['status'] ?? '';
        $customer = $_GET['customer'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $assignedTo = $_GET['assigned_to'] ?? '';

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $whereClause = $this->scopeToCompany('WHERE 1=1', 'c');
        $params = [];

        if ($status) {
            $whereClause .= " AND a.status = ?";
            $params[] = $status;
        }

        if ($customer) {
            $whereClause .= " AND c.name LIKE ?";
            $params[] = "%$customer%";
        }

        if ($dateFrom) {
            $whereClause .= " AND DATE(a.appointment_date) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $whereClause .= " AND DATE(a.appointment_date) <= ?";
            $params[] = $dateTo;
        }

        if ($assignedTo) {
            $whereClause .= " AND a.assigned_to = ?";
            $params[] = $assignedTo;
        }

        $countSql = "
            SELECT COUNT(*) as count
            FROM appointments a
            LEFT JOIN customers c ON a.customer_id = c.id
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN users u ON a.assigned_to = u.id
            $whereClause
        ";
        $db = Database::getInstance();
        $total = $db->fetch($countSql, $params)['count'];

        $sql = "
            SELECT
                a.*,
                c.name AS customer_name,
                c.phone AS customer_phone,
                s.name AS service_name,
                u.username AS assigned_user
            FROM appointments a
            LEFT JOIN customers c ON a.customer_id = c.id
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN users u ON a.assigned_to = u.id
            $whereClause
            ORDER BY a.appointment_date ASC, a.start_time ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        $appointments = $db->fetchAll($sql, $params);
        $pagination = Utils::paginate($total, $limit, $page);
        $customers = $this->customerModel->all();
        $users = $this->userModel->all();
        $stats = $this->appointmentModel->getStats();
        $companies = $this->getCompanyOptions();

        echo View::renderWithLayout('appointments/list', [
            'appointments' => $appointments,
            'pagination' => $pagination,
            'customers' => $customers,
            'users' => $users,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'customer' => $customer,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'assigned_to' => $assignedTo,
                'company_filter' => $_GET['company_filter'] ?? '',
            ],
            'statuses' => Appointment::getStatuses(),
            'priorities' => Appointment::getPriorities(),
            'companies' => $companies,
            'flash' => Utils::getFlash()
        ]);
    }

    /**
     * Yeni randevu formu
     */
    public function create()
    {
        Auth::require();
        
        $customers = $this->customerModel->all();
        $services = $this->serviceModel->all();
        $users = $this->userModel->all();
        
        echo View::renderWithLayout('appointments/form', [
            'appointment' => null,
            'customers' => $customers,
            'services' => $services,
            'users' => $users,
            'statuses' => Appointment::getStatuses(),
            'priorities' => Appointment::getPriorities(),
            'flash' => Utils::getFlash()
        ]);
    }

    /**
     * Randevu kaydet
     */
    public function store()
    {
        Auth::require();
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/appointments')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/appointments/new')) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        $data = [
            'customer_id' => $_POST['customer_id'] ?? null,
            'service_id' => $_POST['service_id'] ?? null,
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'appointment_date' => $_POST['appointment_date'] ?? '',
            'start_time' => $_POST['start_time'] ?? '',
            'end_time' => $_POST['end_time'] ?? null,
            'status' => $_POST['status'] ?? 'SCHEDULED',
            'priority' => $_POST['priority'] ?? 'MEDIUM',
            'assigned_to' => $_POST['assigned_to'] ?? null,
            'notes' => $_POST['notes'] ?? null
        ];

        // Validasyon
        $errors = [];
        if (empty($data['customer_id'])) {
            $errors[] = 'Müşteri seçimi zorunludur.';
        }
        if (empty($data['title'])) {
            $errors[] = 'Başlık zorunludur.';
        }
        if (empty($data['appointment_date'])) {
            $errors[] = 'Randevu tarihi zorunludur.';
        }
        if (empty($data['start_time'])) {
            $errors[] = 'Başlangıç saati zorunludur.';
        }

        if (!empty($errors)) {
            ControllerHelper::flashErrorAndRedirect(implode('<br>', $errors), '/appointments/new');
            return;
        }

        $customer = $this->customerModel->find($data['customer_id']);
        if (!$customer) {
            ControllerHelper::flashErrorAndRedirect('Seçilen müşteri bulunamadı.', '/appointments/new');
            return;
        }

        try {
            $appointmentId = $this->appointmentModel->create($data);
            
            // Aktivite logla
            ActivityLogger::log('appointment_created', 'appointment', [
                'appointment_id' => $appointmentId,
                'customer_id' => $data['customer_id'],
                'title' => $data['title']
            ]);
            
            ControllerHelper::flashSuccessAndRedirect('Randevu başarıyla oluşturuldu.', '/appointments');
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'AppointmentController::store()', 'Randevu oluşturulurken bir hata oluştu', '/appointments/new');
        }
    }

    /**
     * Randevu detayı
     */
    public function show($id)
    {
        Auth::require();
        
        if (!$id || !is_numeric($id)) {
            error_log("Appointment show: Invalid ID: $id");
            View::notFound('Geçersiz randevu ID');
        }
        
        $appointment = $this->appointmentModel->find($id);
        if (!$appointment) {
            error_log("Appointment show: Appointment not found with ID: $id");
            View::notFound('Randevu bulunamadı');
        }

        $customer = $this->customerModel->find($appointment['customer_id']);
        if (!$customer) {
            View::notFound('Randevu bulunamadı');
        }
        
        echo View::renderWithLayout('appointments/show', [
            'appointment' => $appointment,
            'statuses' => Appointment::getStatuses(),
            'priorities' => Appointment::getPriorities(),
            'flash' => Utils::getFlash()
        ]);
    }

    /**
     * Randevu düzenleme formu
     */
    public function edit($id)
    {
        Auth::require();
        
        if (!$id || !is_numeric($id)) {
            error_log("Appointment edit: Invalid ID: $id");
            View::notFound('Geçersiz randevu ID');
        }
        
        $appointment = $this->appointmentModel->find($id);
        if (!$appointment) {
            error_log("Appointment edit: Appointment not found with ID: $id");
            View::notFound('Randevu bulunamadı');
        }

        if (!$this->customerModel->find($appointment['customer_id'])) {
            View::notFound('Randevu bulunamadı');
        }
        
        $customers = $this->customerModel->all();
        $services = $this->serviceModel->all();
        $users = $this->userModel->all();
        
        echo View::renderWithLayout('appointments/form', [
            'appointment' => $appointment,
            'customers' => $customers,
            'services' => $services,
            'users' => $users,
            'statuses' => Appointment::getStatuses(),
            'priorities' => Appointment::getPriorities(),
            'flash' => Utils::getFlash()
        ]);
    }

    /**
     * Randevu güncelle
     */
    public function update($id)
    {
        Auth::require();
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/appointments')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect("/appointments/{$id}/edit")) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        if (!$id || !is_numeric($id)) {
            error_log("Appointment update: Invalid ID: $id");
            View::notFound('Geçersiz randevu ID');
        }

        $appointment = $this->appointmentModel->find($id);
        if (!$appointment) {
            error_log("Appointment update: Appointment not found with ID: $id");
            View::notFound('Randevu bulunamadı');
        }

        $data = [
            'customer_id' => $_POST['customer_id'] ?? null,
            'service_id' => $_POST['service_id'] ?? null,
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'appointment_date' => $_POST['appointment_date'] ?? '',
            'start_time' => $_POST['start_time'] ?? '',
            'end_time' => $_POST['end_time'] ?? null,
            'status' => $_POST['status'] ?? 'SCHEDULED',
            'priority' => $_POST['priority'] ?? 'MEDIUM',
            'assigned_to' => $_POST['assigned_to'] ?? null,
            'notes' => $_POST['notes'] ?? null
        ];

        // Validasyon
        $errors = [];
        if (empty($data['customer_id'])) {
            $errors[] = 'Müşteri seçimi zorunludur.';
        }
        if (empty($data['title'])) {
            $errors[] = 'Başlık zorunludur.';
        }
        if (empty($data['appointment_date'])) {
            $errors[] = 'Randevu tarihi zorunludur.';
        }
        if (empty($data['start_time'])) {
            $errors[] = 'Başlangıç saati zorunludur.';
        }

        if (!empty($errors)) {
            ControllerHelper::flashErrorAndRedirect(implode('<br>', $errors), "/appointments/{$id}/edit");
            return;
        }

        $customer = $this->customerModel->find($data['customer_id']);
        if (!$customer) {
            ControllerHelper::flashErrorAndRedirect('Seçilen müşteri bulunamadı.', "/appointments/{$id}/edit");
            return;
        }

        try {
            $this->appointmentModel->update($id, $data);
            
            // Aktivite logla
            ActivityLogger::log('appointment_updated', 'appointment', [
                'appointment_id' => $id,
                'customer_id' => $data['customer_id'],
                'title' => $data['title']
            ]);
            
            ControllerHelper::flashSuccessAndRedirect('Randevu başarıyla güncellendi.', '/appointments');
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'AppointmentController::update()', 'Randevu güncellenirken bir hata oluştu', "/appointments/{$id}/edit");
        }
    }

    /**
     * Randevu sil
     */
    public function delete($id)
    {
        Auth::require();
        
        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            Utils::flash('error', 'Geçersiz randevu ID.');
            redirect(base_url('/appointments'));
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/appointments')) {
            return;
        }
        
        if (!ControllerHelper::verifyCsrfOrRedirect('/appointments')) {
            return;
        }
        // ===== ERR-026 FIX: End =====

        $appointment = $this->appointmentModel->find($id);
        if (!$appointment) {
            Utils::flash('error', 'Randevu bulunamadı.');
            redirect(base_url('/appointments'));
            return;
        }

        if (!$this->customerModel->find($appointment['customer_id'])) {
            View::notFound('Randevu bulunamadı');
        }

        try {
            $this->appointmentModel->delete($id);
            
            // Aktivite logla
            ActivityLogger::log('appointment_deleted', 'appointment', [
                'appointment_id' => $id,
                'title' => $appointment['title']
            ]);

            ControllerHelper::flashSuccessAndRedirect('Randevu başarıyla silindi.', '/appointments');
        } catch (Exception $e) {
            ControllerHelper::handleException($e, 'AppointmentController::delete()', 'Randevu silinirken bir hata oluştu', '/appointments');
        }
    }

    /**
     * Randevu durumunu güncelle
     */
    public function updateStatus($id)
    {
        Auth::require();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/appointments'));
        }
        
        // CSRF kontrolü
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik hatası. Lütfen tekrar deneyin.');
            redirect(base_url('/appointments'));
        }

        if (!$id || !is_numeric($id)) {
            error_log("Appointment updateStatus: Invalid ID: $id");
            View::notFound('Geçersiz randevu ID');
        }

        $appointment = $this->appointmentModel->find($id);
        if (!$appointment) {
            error_log("Appointment updateStatus: Appointment not found with ID: $id");
            View::notFound('Randevu bulunamadı');
        }

        $status = $_POST['status'] ?? null;
        if (!$status || !array_key_exists($status, Appointment::getStatuses())) {
            Utils::flash('error', 'Geçersiz durum.');
            redirect(base_url('/appointments'));
        }

        try {
            $this->appointmentModel->updateStatus($id, $status);
            
            // Aktivite logla
            ActivityLogger::log('appointment_status_updated', 'appointment', [
                'appointment_id' => $id,
                'old_status' => $appointment['status'],
                'new_status' => $status,
                'title' => $appointment['title']
            ]);

            Utils::flash('success', 'Randevu durumu güncellendi.');
            redirect(base_url('/appointments'));
        } catch (Exception $e) {
            error_log("Appointment status update error: " . $e->getMessage());
            Utils::flash('error', 'Durum güncellenirken bir hata oluştu.');
            redirect(base_url('/appointments'));
        }
    }

    /**
     * Bugünkü randevular
     */
    public function today()
    {
        Auth::require();
        
        $appointments = $this->appointmentModel->getToday();
        $stats = $this->appointmentModel->getStats();

        $data = [
            'appointments' => $appointments,
            'stats' => $stats,
            'statuses' => Appointment::getStatuses(),
            'priorities' => Appointment::getPriorities()
        ];

        echo View::renderWithLayout('appointments/today', array_merge($data, ['flash' => Utils::getFlash()]));
    }

    /**
     * Bu haftaki randevular
     */
    public function thisWeek()
    {
        Auth::require();
        
        $appointments = $this->appointmentModel->getThisWeek();
        $stats = $this->appointmentModel->getStats();

        $data = [
            'appointments' => $appointments,
            'stats' => $stats,
            'statuses' => Appointment::getStatuses(),
            'priorities' => Appointment::getPriorities()
        ];

        echo View::renderWithLayout('appointments/week', array_merge($data, ['flash' => Utils::getFlash()]));
    }

    /**
     * Yaklaşan randevular
     */
    public function upcoming()
    {
        Auth::require();
        
        $days = $_GET['days'] ?? 7;
        $appointments = $this->appointmentModel->getUpcoming($days);
        $stats = $this->appointmentModel->getStats();

        $data = [
            'appointments' => $appointments,
            'stats' => $stats,
            'days' => $days,
            'statuses' => Appointment::getStatuses(),
            'priorities' => Appointment::getPriorities()
        ];

        echo View::renderWithLayout('appointments/upcoming', array_merge($data, ['flash' => Utils::getFlash()]));
    }
}
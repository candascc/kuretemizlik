<?php
/**
 * Building Controller
 * Apartman/Site yönetimi - Bina kontrolcüsü
 */

class BuildingController
{
    private $buildingModel;
    private $unitModel;
    private $customerModel;

    public function __construct()
    {
        $this->buildingModel = new Building();
        $this->unitModel = new Unit();
        $this->customerModel = new Customer();
    }

    /**
     * Bina listesi
     */
    public function index()
    {
        Auth::require();

        $page = (int)($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';
        $buildingType = $_GET['building_type'] ?? '';
        $status = $_GET['status'] ?? 'active';

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $filters = [
            'status' => ($status === '' || $status === 'all') ? null : $status,
            'building_type' => ($buildingType === '' || $buildingType === 'all') ? null : $buildingType,
        ];

        $result = $this->buildingModel->paginate($filters, $limit, $offset, $search);
        $buildings = $result['data'];
        $total = $result['total'];
        $pagination = Utils::paginate($total, $limit, $page);

        try {
            echo View::renderWithLayout('buildings/index', [
                'title' => 'Bina Yönetimi',
                'buildings' => $buildings ?: [],
                'pagination' => $pagination,
                'search' => $search,
                'buildingType' => $buildingType,
                'status' => $status
            ]);
        } catch (Exception $e) {
            error_log("BuildingController::index() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/'));
        }
    }

    /**
     * Bina detay
     */
    public function show($id)
    {
        Auth::require();

        $building = $this->buildingModel->find($id);
        if (!$building) {
            Utils::flash('error', 'Bina bulunamadı');
            redirect(base_url('/buildings'));
        }

        // İstatistikler
        $statistics = $this->buildingModel->getStatistics($id);

        // Units
        $units = $this->buildingModel->getUnits($id);
        $building['units'] = $units;

        // Son aidatlar
        $feeModel = new ManagementFee();
        $recentFees = $feeModel->all(['building_id' => $id], 10);

        // Son giderler
        $expenseModel = new BuildingExpense();
        $recentExpenses = $expenseModel->all(['building_id' => $id], 10);

        // Documents
        $docModel = new BuildingDocument();
        $recentDocuments = $docModel->all(['building_id' => $id], 10);

        // Upcoming meetings
        $meetingModel = new BuildingMeeting();
        $upcomingMeetings = $meetingModel->all(['building_id' => $id, 'status' => 'scheduled'], 5);

        // Recent announcements
        $announcementModel = new BuildingAnnouncement();
        $recentAnnouncements = $announcementModel->all(['building_id' => $id], 5);

        // Active surveys
        $surveyModel = new BuildingSurvey();
        $activeSurveys = $surveyModel->all(['building_id' => $id, 'status' => 'active'], 5);

        // Facilities
        $facilityModel = new BuildingFacility();
        $facilities = $facilityModel->all(['building_id' => $id]);

        // Recent reservations
        $reservationModel = new BuildingReservation();
        $recentReservations = $reservationModel->all(['building_id' => $id], 10);
        
        // Chart data for building dashboard
        $currentYear = (int)date('Y');
        $monthlyFees = $feeModel->getMonthlySummary($id, $currentYear);
        $monthlyExpenses = $expenseModel->getMonthlySummary($id, $currentYear);

        try {
            echo View::renderWithLayout('buildings/show', [
                'title' => $building['name'] . ' - Bina Detay',
                'building' => $building,
                'recentFees' => $recentFees ?: [],
                'recentExpenses' => $recentExpenses ?: [],
                'recentDocuments' => $recentDocuments ?: [],
                'upcomingMeetings' => $upcomingMeetings ?: [],
                'recentAnnouncements' => $recentAnnouncements ?: [],
                'activeSurveys' => $activeSurveys ?: [],
                'facilities' => $facilities ?: [],
                'recentReservations' => $recentReservations ?: [],
                'statistics' => $statistics,
                'monthlyFees' => $monthlyFees,
                'monthlyExpenses' => $monthlyExpenses
            ]);
        } catch (Exception $e) {
            error_log("BuildingController::show() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/buildings'));
        }
    }

    /**
     * Yeni bina formu
     */
    public function create()
    {
        Auth::require();

        $customers = $this->customerModel->all();

        echo View::renderWithLayout('buildings/form', [
            'title' => 'Yeni Bina Ekle',
            'building' => null,
            'customers' => $customers
        ]);
    }

    /**
     * Bina kaydet
     */
    public function store()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            throw new Exception('Güvenlik doğrulaması başarısız (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.');
        }

        try {
            $data = [
                'name' => $_POST['name'] ?? '',
                'building_type' => $_POST['building_type'] ?? 'apartman',
                'customer_id' => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
                'address_line' => $_POST['address_line'] ?? '',
                'district' => $_POST['district'] ?? '',
                'city' => $_POST['city'] ?? 'İstanbul',
                'postal_code' => $_POST['postal_code'] ?? '',
                'total_floors' => !empty($_POST['total_floors']) ? (int)$_POST['total_floors'] : null,
                'total_units' => (int)($_POST['total_units'] ?? 0),
                'construction_year' => !empty($_POST['construction_year']) ? (int)$_POST['construction_year'] : null,
                'manager_name' => $_POST['manager_name'] ?? '',
                'manager_phone' => $_POST['manager_phone'] ?? '',
                'manager_email' => $_POST['manager_email'] ?? '',
                'tax_office' => $_POST['tax_office'] ?? '',
                'tax_number' => $_POST['tax_number'] ?? '',
                'bank_name' => $_POST['bank_name'] ?? '',
                'bank_iban' => $_POST['bank_iban'] ?? '',
                'monthly_maintenance_day' => (int)($_POST['monthly_maintenance_day'] ?? 1),
                'status' => $_POST['status'] ?? 'active',
                'notes' => $_POST['notes'] ?? ''
            ];

            // Validasyon
            if (empty($data['name'])) {
                throw new Exception('Bina adı gereklidir');
            }

            if (empty($data['address_line'])) {
                throw new Exception('Adres gereklidir');
            }

            if ($data['total_units'] <= 0) {
                throw new Exception('Daire sayısı 0\'dan büyük olmalıdır');
            }

            $id = $this->buildingModel->create($data);

            ActivityLogger::log('building.created', 'building', $id, ['name' => $data['name']]);
            Utils::flash('success', 'Bina başarıyla oluşturuldu');
            redirect(base_url("/buildings/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/buildings/create'));
        }
    }

    /**
     * Bina düzenleme formu
     */
    public function edit($id)
    {
        Auth::require();

        $building = $this->buildingModel->find($id);
        if (!$building) {
            Utils::flash('error', 'Bina bulunamadı');
            redirect(base_url('/buildings'));
        }

        $customers = $this->customerModel->all();

        echo View::renderWithLayout('buildings/form', [
            'title' => 'Bina Düzenle',
            'building' => $building,
            'customers' => $customers
        ]);
    }

    /**
     * Bina güncelle
     */
    public function update($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url("/buildings/{$id}/edit"));
        }

        try {
            $building = $this->buildingModel->find($id);
            if (!$building) {
                throw new Exception('Bina bulunamadı');
            }

            $data = [
                'name' => $_POST['name'] ?? '',
                'building_type' => $_POST['building_type'] ?? 'apartman',
                'customer_id' => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
                'address_line' => $_POST['address_line'] ?? '',
                'district' => $_POST['district'] ?? '',
                'city' => $_POST['city'] ?? 'İstanbul',
                'postal_code' => $_POST['postal_code'] ?? '',
                'total_floors' => !empty($_POST['total_floors']) ? (int)$_POST['total_floors'] : null,
                'total_units' => (int)($_POST['total_units'] ?? 0),
                'construction_year' => !empty($_POST['construction_year']) ? (int)$_POST['construction_year'] : null,
                'manager_name' => $_POST['manager_name'] ?? '',
                'manager_phone' => $_POST['manager_phone'] ?? '',
                'manager_email' => $_POST['manager_email'] ?? '',
                'tax_office' => $_POST['tax_office'] ?? '',
                'tax_number' => $_POST['tax_number'] ?? '',
                'bank_name' => $_POST['bank_name'] ?? '',
                'bank_iban' => $_POST['bank_iban'] ?? '',
                'monthly_maintenance_day' => (int)($_POST['monthly_maintenance_day'] ?? 1),
                'status' => $_POST['status'] ?? 'active',
                'notes' => $_POST['notes'] ?? ''
            ];

            // Validasyon
            if (empty($data['name'])) {
                throw new Exception('Bina adı gereklidir');
            }

            $this->buildingModel->update($id, $data);

            ActivityLogger::log('building.updated', 'building', $id, ['name' => $data['name']]);
            Utils::flash('success', 'Bina başarıyla güncellendi');
            redirect(base_url("/buildings/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/buildings/{$id}/edit"));
        }
    }

    /**
     * Bina sil
     */
    public function delete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url("/buildings/{$id}"));
        }

        try {
            $building = $this->buildingModel->find($id);
            if (!$building) {
                throw new Exception('Bina bulunamadı');
            }

            $this->buildingModel->delete($id);

            ActivityLogger::log('building.deleted', 'building', $id, ['name' => $building['name']]);
            Utils::flash('success', 'Bina başarıyla silindi');
            redirect(base_url('/buildings'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/buildings/{$id}"));
        }
    }

    /**
     * Bina dashboard/analytics
     */
    public function dashboard($id)
    {
        Auth::require();

        $building = $this->buildingModel->find($id);
        if (!$building) {
            Utils::flash('error', 'Bina bulunamadı');
            redirect(base_url('/buildings'));
        }

        // İstatistikler
        $stats = $building['statistics'] ?? [];

        echo View::renderWithLayout('buildings/dashboard', [
            'title' => $building['name'] . ' - Dashboard',
            'building' => $building,
            'stats' => $stats
        ]);
    }
}


<?php
/**
 * Building Facility Controller
 */

class BuildingFacilityController
{
    private $facilityModel;
    private $buildingModel;

    public function __construct()
    {
        $this->facilityModel = new BuildingFacility();
        $this->buildingModel = new Building();
    }

    /**
     * Alan listesi
     */
    public function index()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        
        $filters = [];
        if ($buildingId) $filters['building_id'] = $buildingId;
        
        $facilities = $this->facilityModel->all($filters);
        $buildings = $this->buildingModel->active();

        try {
            echo View::renderWithLayout('facilities/index', [
                'title' => 'Rezervasyon Alanları',
                'facilities' => $facilities ?: [],
                'buildings' => $buildings ?: [],
                'buildingId' => $buildingId
            ]);
        } catch (Exception $e) {
            error_log("BuildingFacilityController::index() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/'));
        }
    }

    /**
     * Yeni alan formu
     */
    public function create()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $buildings = $this->buildingModel->active();

        echo View::renderWithLayout('facilities/form', [
            'title' => 'Yeni Alan Ekle',
            'facility' => null,
            'buildings' => $buildings,
            'buildingId' => $buildingId
        ]);
    }

    /**
     * Alan kaydet
     */
    public function store()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/facilities'));
        }

        try {
            $data = [
                'building_id' => (int)($_POST['building_id'] ?? 0),
                'facility_name' => $_POST['facility_name'] ?? '',
                'facility_type' => $_POST['facility_type'] ?? 'other',
                'description' => $_POST['description'] ?? null,
                'capacity' => !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null,
                'hourly_rate' => !empty($_POST['hourly_rate']) ? (float)$_POST['hourly_rate'] : 0,
                'daily_rate' => !empty($_POST['daily_rate']) ? (float)$_POST['daily_rate'] : 0,
                'requires_approval' => isset($_POST['requires_approval']) ? 1 : 0,
                'max_advance_days' => !empty($_POST['max_advance_days']) ? (int)$_POST['max_advance_days'] : 30,
                'is_active' => 1
            ];

            if (empty($data['building_id'])) {
                throw new Exception('Bina seçilmelidir');
            }

            if (empty($data['facility_name'])) {
                throw new Exception('Alan adı gereklidir');
            }

            $facilityId = $this->facilityModel->create($data);

            ActivityLogger::log('facility.created', 'building_facility', $facilityId);
            Utils::flash('success', 'Alan başarıyla oluşturuldu');
            redirect(base_url('/facilities'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/facilities/create'));
        }
    }

    /**
     * Alan düzenleme formu
     */
    public function edit($id)
    {
        Auth::require();

        $facility = $this->facilityModel->find($id);
        if (!$facility) {
            Utils::flash('error', 'Alan bulunamadı');
            redirect(base_url('/facilities'));
        }

        $buildings = $this->buildingModel->active();

        echo View::renderWithLayout('facilities/form', [
            'title' => 'Alan Düzenle',
            'facility' => $facility,
            'buildings' => $buildings
        ]);
    }

    /**
     * Alan güncelle
     */
    public function update($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/facilities'));
        }

        try {
            $data = [
                'facility_name' => $_POST['facility_name'] ?? '',
                'facility_type' => $_POST['facility_type'] ?? 'other',
                'description' => $_POST['description'] ?? null,
                'capacity' => !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null,
                'hourly_rate' => !empty($_POST['hourly_rate']) ? (float)$_POST['hourly_rate'] : 0,
                'daily_rate' => !empty($_POST['daily_rate']) ? (float)$_POST['daily_rate'] : 0,
                'requires_approval' => isset($_POST['requires_approval']) ? 1 : 0,
                'max_advance_days' => !empty($_POST['max_advance_days']) ? (int)$_POST['max_advance_days'] : 30,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];

            $this->facilityModel->update($id, $data);

            ActivityLogger::log('facility.updated', 'building_facility', $id);
            Utils::flash('success', 'Alan başarıyla güncellendi');
            redirect(base_url('/facilities'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/facilities/{$id}/edit"));
        }
    }

    /**
     * Alan sil
     */
    public function delete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/facilities'));
        }

        try {
            $this->facilityModel->delete($id);
            ActivityLogger::log('facility.deleted', 'building_facility', $id);
            Utils::flash('success', 'Alan başarıyla silindi');
            redirect(base_url('/facilities'));
        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/facilities'));
        }
    }
}


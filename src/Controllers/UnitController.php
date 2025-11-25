<?php
/**
 * Unit Controller
 * Apartman/Site yönetimi - Daire kontrolcüsü
 */

class UnitController
{
    private $unitModel;
    private $buildingModel;

    public function __construct()
    {
        $this->unitModel = new Unit();
        $this->buildingModel = new Building();
    }

    /**
     * Daire listesi
     */
    public function index()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $page = (int)($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $buildingIdInt = !empty($buildingId) ? (int)$buildingId : null;
        $result = $this->unitModel->paginate($buildingIdInt, $search, $limit, $offset);
        $units = $result['data'];
        $total = $result['total'];
        $pagination = Utils::paginate($total, $limit, $page);

        $buildings = $buildingId ? null : $this->buildingModel->active();

        try {
            echo View::renderWithLayout('units/index', [
                'title' => 'Daire Yönetimi',
                'units' => $units ?: [],
                'pagination' => $pagination,
                'buildingId' => $buildingId,
                'search' => $search,
                'buildings' => $buildings ?: []
            ]);
        } catch (Exception $e) {
            error_log("UnitController::index() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/'));
        }
    }

    /**
     * Daire detay
     */
    public function show($id)
    {
        Auth::require();

        $unit = $this->unitModel->find($id);
        if (!$unit) {
            Utils::flash('error', 'Daire bulunamadı');
            redirect(base_url('/units'));
        }

        // Get building
        $building = $this->buildingModel->find($unit['building_id']);

        // Get statistics
        $statistics = $this->unitModel->getStatistics($id);

        // Aidat geçmişi
        $feeModel = new ManagementFee();
        $recentFees = $feeModel->all(['unit_id' => $id], 20);

        // Documents
        $docModel = new BuildingDocument();
        $recentDocuments = $docModel->all(['unit_id' => $id], 10);

        // Resident users
        $residentModel = new ResidentUser();
        $residents = $residentModel->all(['unit_id' => $id]);

        try {
            echo View::renderWithLayout('units/show', [
                'title' => $unit['unit_number'] . ' - Daire Detay',
                'unit' => $unit,
                'building' => $building,
                'statistics' => $statistics,
                'recentFees' => $recentFees ?: [],
                'recentDocuments' => $recentDocuments ?: [],
                'residents' => $residents ?: []
            ]);
        } catch (Exception $e) {
            error_log("UnitController::show() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/units'));
        }
    }

    /**
     * Yeni daire formu
     */
    public function create()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $buildings = $this->buildingModel->active();

        if ($buildingId) {
            $building = $this->buildingModel->find($buildingId);
            if (!$building) {
                Utils::flash('error', 'Bina bulunamadı');
                redirect(base_url('/buildings'));
            }
        }

        echo View::renderWithLayout('units/form', [
            'title' => 'Yeni Daire Ekle',
            'unit' => null,
            'buildings' => $buildings,
            'buildingId' => $buildingId
        ]);
    }

    /**
     * Daire kaydet
     */
    public function store()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/units'));
        }

        try {
            $data = [
                'building_id' => (int)($_POST['building_id'] ?? 0),
                'unit_type' => $_POST['unit_type'] ?? 'daire',
                'floor_number' => !empty($_POST['floor_number']) ? (int)$_POST['floor_number'] : null,
                'unit_number' => $_POST['unit_number'] ?? '',
                'gross_area' => !empty($_POST['gross_area']) ? (float)$_POST['gross_area'] : null,
                'net_area' => !empty($_POST['net_area']) ? (float)$_POST['net_area'] : null,
                'room_count' => $_POST['room_count'] ?? '',
                'owner_type' => $_POST['owner_type'] ?? 'owner',
                'owner_name' => $_POST['owner_name'] ?? '',
                'owner_phone' => $_POST['owner_phone'] ?? '',
                'owner_email' => $_POST['owner_email'] ?? '',
                'owner_id_number' => $_POST['owner_id_number'] ?? '',
                'owner_address' => $_POST['owner_address'] ?? '',
                'tenant_name' => $_POST['tenant_name'] ?? '',
                'tenant_phone' => $_POST['tenant_phone'] ?? '',
                'tenant_email' => $_POST['tenant_email'] ?? '',
                'tenant_contract_start' => $_POST['tenant_contract_start'] ?? null,
                'tenant_contract_end' => $_POST['tenant_contract_end'] ?? null,
                'monthly_fee' => (float)($_POST['monthly_fee'] ?? 0),
                'parking_count' => (int)($_POST['parking_count'] ?? 0),
                'storage_count' => (int)($_POST['storage_count'] ?? 0),
                'status' => $_POST['status'] ?? 'active',
                'notes' => $_POST['notes'] ?? ''
            ];

            // Validasyon
            if (empty($data['building_id'])) {
                throw new Exception('Bina seçilmelidir');
            }

            if (empty($data['unit_number'])) {
                throw new Exception('Daire numarası gereklidir');
            }

            if (empty($data['owner_name'])) {
                throw new Exception('Mal sahibi adı gereklidir');
            }

            $id = $this->unitModel->create($data);

            ActivityLogger::log('unit.created', 'unit', $id, ['unit_number' => $data['unit_number']]);
            Utils::flash('success', 'Daire başarıyla oluşturuldu');
            redirect(base_url("/units/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/units/create'));
        }
    }

    /**
     * Daire düzenleme formu
     */
    public function edit($id)
    {
        Auth::require();

        $unit = $this->unitModel->find($id);
        if (!$unit) {
            Utils::flash('error', 'Daire bulunamadı');
            redirect(base_url('/units'));
        }

        $buildings = $this->buildingModel->active();

        echo View::renderWithLayout('units/form', [
            'title' => 'Daire Düzenle',
            'unit' => $unit,
            'buildings' => $buildings,
            'buildingId' => $unit['building_id']
        ]);
    }

    /**
     * Daire güncelle
     */
    public function update($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url("/units/{$id}/edit"));
        }

        try {
            $unit = $this->unitModel->find($id);
            if (!$unit) {
                throw new Exception('Daire bulunamadı');
            }

            $data = [
                'building_id' => (int)($_POST['building_id'] ?? 0),
                'unit_type' => $_POST['unit_type'] ?? 'daire',
                'floor_number' => !empty($_POST['floor_number']) ? (int)$_POST['floor_number'] : null,
                'unit_number' => $_POST['unit_number'] ?? '',
                'gross_area' => !empty($_POST['gross_area']) ? (float)$_POST['gross_area'] : null,
                'net_area' => !empty($_POST['net_area']) ? (float)$_POST['net_area'] : null,
                'room_count' => $_POST['room_count'] ?? '',
                'owner_type' => $_POST['owner_type'] ?? 'owner',
                'owner_name' => $_POST['owner_name'] ?? '',
                'owner_phone' => $_POST['owner_phone'] ?? '',
                'owner_email' => $_POST['owner_email'] ?? '',
                'owner_id_number' => $_POST['owner_id_number'] ?? '',
                'owner_address' => $_POST['owner_address'] ?? '',
                'tenant_name' => $_POST['tenant_name'] ?? '',
                'tenant_phone' => $_POST['tenant_phone'] ?? '',
                'tenant_email' => $_POST['tenant_email'] ?? '',
                'tenant_contract_start' => $_POST['tenant_contract_start'] ?? null,
                'tenant_contract_end' => $_POST['tenant_contract_end'] ?? null,
                'monthly_fee' => (float)($_POST['monthly_fee'] ?? 0),
                'parking_count' => (int)($_POST['parking_count'] ?? 0),
                'storage_count' => (int)($_POST['storage_count'] ?? 0),
                'status' => $_POST['status'] ?? 'active',
                'notes' => $_POST['notes'] ?? ''
            ];

            if (empty($data['unit_number'])) {
                throw new Exception('Daire numarası gereklidir');
            }

            $this->unitModel->update($id, $data);

            ActivityLogger::log('unit.updated', 'unit', $id, ['unit_number' => $data['unit_number']]);
            Utils::flash('success', 'Daire başarıyla güncellendi');
            redirect(base_url("/units/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/units/{$id}/edit"));
        }
    }

    /**
     * Daire sil
     */
    public function delete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url("/units/{$id}"));
        }

        try {
            $unit = $this->unitModel->find($id);
            if (!$unit) {
                throw new Exception('Daire bulunamadı');
            }

            $this->unitModel->delete($id);

            ActivityLogger::log('unit.deleted', 'unit', $id, ['unit_number' => $unit['unit_number']]);
            Utils::flash('success', 'Daire başarıyla silindi');
            redirect(base_url('/units'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/units/{$id}"));
        }
    }
}


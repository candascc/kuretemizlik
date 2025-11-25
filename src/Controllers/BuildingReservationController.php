<?php
/**
 * Building Reservation Controller
 */

class BuildingReservationController
{
    private $reservationModel;
    private $facilityModel;
    private $buildingModel;
    private $unitModel;

    public function __construct()
    {
        $this->reservationModel = new BuildingReservation();
        $this->facilityModel = new BuildingFacility();
        $this->buildingModel = new Building();
        $this->unitModel = new Unit();
    }

    /**
     * Rezervasyon listesi
     */
    public function index()
    {
        Auth::require();

        $page = (int)($_GET['page'] ?? 1);
        $buildingId = $_GET['building_id'] ?? null;
        $facilityId = $_GET['facility_id'] ?? null;
        $status = $_GET['status'] ?? '';

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if ($buildingId) $filters['building_id'] = $buildingId;
        if ($facilityId) $filters['facility_id'] = $facilityId;
        if ($status) $filters['status'] = $status;

        $reservations = $this->reservationModel->all($filters, $limit, $offset);
        $total = $this->reservationModel->all($filters);
        $total = is_array($total) ? count($total) : 0;
        $pagination = Utils::paginate($total, $limit, $page);

        $buildings = $this->buildingModel->active();

        try {
            echo View::renderWithLayout('reservations/index', [
                'title' => 'Rezervasyonlar',
                'reservations' => $reservations ?: [],
                'pagination' => $pagination,
                'buildings' => $buildings ?: [],
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            error_log("BuildingReservationController::index() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/'));
        }
    }

    /**
     * Rezervasyon detay
     */
    public function show($id)
    {
        Auth::require();

        $reservation = $this->reservationModel->find($id);
        if (!$reservation) {
            Utils::flash('error', 'Rezervasyon bulunamadı');
            redirect(base_url('/reservations'));
        }

        echo View::renderWithLayout('reservations/show', [
            'title' => 'Rezervasyon Detay',
            'reservation' => $reservation
        ]);
    }

    /**
     * Yeni rezervasyon formu
     */
    public function create()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $facilityId = $_GET['facility_id'] ?? null;
        
        $buildings = $this->buildingModel->active();
        $facilities = [];
        $units = [];
        
        if ($buildingId) {
            $facilities = $this->facilityModel->getByBuilding($buildingId);
            $units = $this->unitModel->all(['building_id' => $buildingId]);
        }

        echo View::renderWithLayout('reservations/form', [
            'title' => 'Yeni Rezervasyon',
            'buildings' => $buildings,
            'facilities' => $facilities,
            'units' => $units,
            'buildingId' => $buildingId,
            'facilityId' => $facilityId
        ]);
    }

    /**
     * Rezervasyon kaydet
     */
    public function store()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/reservations'));
        }

        try {
            $buildingId = (int)($_POST['building_id'] ?? 0);
            $facilityId = (int)($_POST['facility_id'] ?? 0);
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';

            // Check availability
            $startDateTime = $startDate . ' ' . $startTime . ':00';
            $endDateTime = $endDate . ' ' . $endTime . ':00';
            
            if (!$this->reservationModel->checkAvailability($facilityId, $startDateTime, $endDateTime)) {
                throw new Exception('Bu tarih ve saatte rezervasyon mevcut, lütfen farklı bir zaman seçin');
            }

            // Get facility info for pricing
            $facility = $this->facilityModel->find($facilityId);
            $reservationType = $_POST['reservation_type'] ?? 'hourly';
            $hourlyRate = (float)($facility['hourly_rate'] ?? 0);
            $dailyRate = (float)($facility['daily_rate'] ?? 0);

            // Calculate total amount
            $startTimestamp = strtotime($startDateTime);
            $endTimestamp = strtotime($endDateTime);
            $hours = max(1, ceil(($endTimestamp - $startTimestamp) / 3600));
            $days = max(1, ceil(($endTimestamp - $startTimestamp) / 86400));
            
            $totalAmount = $reservationType === 'daily' ? ($days * $dailyRate) : ($hours * $hourlyRate);
            $depositAmount = $totalAmount * 0.20; // 20% deposit

            $data = [
                'building_id' => $buildingId,
                'facility_id' => $facilityId,
                'unit_id' => !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null,
                'resident_name' => $_POST['resident_name'] ?? '',
                'resident_phone' => $_POST['resident_phone'] ?? '',
                'start_date' => $startDateTime,
                'end_date' => $endDateTime,
                'reservation_type' => $reservationType,
                'total_amount' => $totalAmount,
                'deposit_amount' => $depositAmount,
                'status' => ($facility['requires_approval'] ?? 0) ? 'pending' : 'approved',
                'approved_by' => ($facility['requires_approval'] ?? 0) ? null : Auth::id(),
                'notes' => $_POST['notes'] ?? null,
                'created_by' => Auth::id()
            ];

            if (empty($data['building_id'])) {
                throw new Exception('Bina seçilmelidir');
            }

            if (empty($data['facility_id'])) {
                throw new Exception('Alan seçilmelidir');
            }

            if (empty($data['resident_name'])) {
                throw new Exception('Rezervasyon sahibi adı gereklidir');
            }

            $reservationId = $this->reservationModel->create($data);

            ActivityLogger::log('reservation.created', 'building_reservation', $reservationId);
            Utils::flash('success', 'Rezervasyon başarıyla oluşturuldu');
            redirect(base_url("/reservations/{$reservationId}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/reservations/create'));
        }
    }

    /**
     * Rezervasyon onayla
     */
    public function approve($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/reservations'));
        }

        try {
            $this->reservationModel->update($id, [
                'status' => 'approved',
                'approved_by' => Auth::id()
            ]);
            
            ActivityLogger::log('reservation.approved', 'building_reservation', $id);
            Utils::flash('success', 'Rezervasyon onaylandı');
            redirect(base_url("/reservations/{$id}"));
        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/reservations'));
        }
    }

    /**
     * Rezervasyon reddet
     */
    public function reject($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/reservations'));
        }

        try {
            $reason = $_POST['reason'] ?? 'Reddedildi';
            $this->reservationModel->update($id, [
                'status' => 'rejected',
                'cancelled_reason' => $reason,
                'approved_by' => Auth::id()
            ]);
            
            ActivityLogger::log('reservation.rejected', 'building_reservation', $id);
            Utils::flash('success', 'Rezervasyon reddedildi');
            redirect(base_url("/reservations/{$id}"));
        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/reservations'));
        }
    }

    /**
     * Rezervasyon iptal
     */
    public function cancel($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/reservations'));
        }

        try {
            $reason = $_POST['reason'] ?? 'İptal edildi';
            $this->reservationModel->update($id, [
                'status' => 'cancelled',
                'cancelled_reason' => $reason
            ]);
            
            ActivityLogger::log('reservation.cancelled', 'building_reservation', $id);
            Utils::flash('success', 'Rezervasyon iptal edildi');
            redirect(base_url("/reservations/{$id}"));
        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/reservations'));
        }
    }

    /**
     * Rezervasyon sil
     */
    public function delete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/reservations'));
        }

        try {
            $this->reservationModel->delete($id);
            ActivityLogger::log('reservation.deleted', 'building_reservation', $id);
            Utils::flash('success', 'Rezervasyon başarıyla silindi');
            redirect(base_url('/reservations'));
        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/reservations'));
        }
    }
}


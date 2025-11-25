<?php
/**
 * Building Meeting Controller
 * Apartman/Site yönetimi - Toplantı kontrolcüsü
 */

class BuildingMeetingController
{
    private $meetingModel;
    private $buildingModel;
    private $unitModel;

    public function __construct()
    {
        $this->meetingModel = new BuildingMeeting();
        $this->buildingModel = new Building();
        $this->unitModel = new Unit();
    }

    /**
     * Toplantı listesi
     */
    public function index()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $status = $_GET['status'] ?? '';

        $filters = [];
        if ($buildingId) $filters['building_id'] = $buildingId;
        if ($status) $filters['status'] = $status;

        $meetings = $this->meetingModel->all($filters);

        $buildings = $this->buildingModel->active();

        try {
            echo View::renderWithLayout('meetings/index', [
                'title' => 'Toplantı Yönetimi',
                'meetings' => $meetings ?: [],
                'filters' => $filters,
                'buildings' => $buildings ?: []
            ]);
        } catch (Exception $e) {
            error_log("BuildingMeetingController::index() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/'));
        }
    }

    /**
     * Toplantı detay
     */
    public function show($id)
    {
        Auth::require();

        $meeting = $this->meetingModel->find($id);
        if (!$meeting) {
            Utils::flash('error', 'Toplantı bulunamadı');
            redirect(base_url('/meetings'));
        }

        // Katılımcıları getir
        $attendees = $this->meetingModel->getAttendees($id);

        try {
            echo View::renderWithLayout('meetings/detail', [
                'title' => $meeting['title'],
                'meeting' => $meeting,
                'attendees' => $attendees ?: []
            ]);
        } catch (Exception $e) {
            error_log("BuildingMeetingController::show() error: " . $e->getMessage());
            Utils::flash('error', 'Sayfa yüklenirken hata oluştu');
            redirect(base_url('/meetings'));
        }
    }

    /**
     * Yeni toplantı formu
     */
    public function create()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $buildings = $this->buildingModel->active();

        echo View::renderWithLayout('meetings/form', [
            'title' => 'Yeni Toplantı Planla',
            'meeting' => null,
            'buildings' => $buildings,
            'buildingId' => $buildingId
        ]);
    }

    /**
     * Toplantı kaydet
     */
    public function store()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/meetings'));
        }

        try {
            $data = [
                'building_id' => (int)($_POST['building_id'] ?? 0),
                'meeting_type' => $_POST['meeting_type'] ?? 'regular',
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'meeting_date' => $_POST['meeting_date'] ?? '',
                'location' => $_POST['location'] ?? '',
                'agenda' => $_POST['agenda'] ?? '[]',
                'created_by' => Auth::id()
            ];

            if (empty($data['building_id'])) {
                throw new Exception('Bina seçilmelidir');
            }

            if (empty($data['title'])) {
                throw new Exception('Toplantı başlığı gereklidir');
            }

            if (empty($data['meeting_date'])) {
                throw new Exception('Toplantı tarihi gereklidir');
            }

            $id = $this->meetingModel->create($data);

            ActivityLogger::log('meeting.created', 'building_meeting', $id, ['title' => $data['title']]);
            Utils::flash('success', 'Toplantı başarıyla planlandı');
            redirect(base_url("/meetings/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/meetings/create'));
        }
    }

    /**
     * Toplantı güncelle
     */
    public function update($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/meetings'));
        }

        try {
            $meeting = $this->meetingModel->find($id);
            if (!$meeting) {
                throw new Exception('Toplantı bulunamadı');
            }

            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'meeting_date' => $_POST['meeting_date'] ?? '',
                'location' => $_POST['location'] ?? '',
                'agenda' => $_POST['agenda'] ?? '[]',
                'minutes' => $_POST['minutes'] ?? '',
                'quorum_reached' => isset($_POST['quorum_reached']) ? 1 : 0
            ];

            $this->meetingModel->update($id, $data);

            ActivityLogger::log('meeting.updated', 'building_meeting', $id);
            Utils::flash('success', 'Toplantı başarıyla güncellendi');
            redirect(base_url("/meetings/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/meetings/{$id}"));
        }
    }

    /**
     * Katılım takip sayfası
     */
    public function attendance($id)
    {
        Auth::require();

        $meeting = $this->meetingModel->find($id);
        if (!$meeting) {
            Utils::flash('error', 'Toplantı bulunamadı');
            redirect(base_url('/meetings'));
        }

        // Bina dairelerini getir
        $units = $this->unitModel->all(['building_id' => $meeting['building_id'], 'status' => 'active']);
        
        // Mevcut katılımcıları getir
        $attendees = $this->meetingModel->getAttendees($id);
        
        $attendanceCount = count(array_filter($attendees, fn($a) => $a['attended'] ?? 0));
        $totalUnits = count($units);

        try {
            echo View::renderWithLayout('meetings/attendance', [
                'title' => 'Katılım Takibi',
                'meeting' => $meeting,
                'units' => $units ?: [],
                'attendees' => $attendees ?: [],
                'attendanceCount' => $attendanceCount,
                'totalUnits' => $totalUnits
            ]);
        } catch (Exception $e) {
            error_log("BuildingMeetingController::attendance() error: " . $e->getMessage());
            Utils::flash('error', 'Sayfa yüklenirken hata oluştu');
            redirect(base_url('/meetings/' . $id));
        }
    }

    /**
     * Katılım kaydet
     */
    public function saveAttendance($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/meetings'));
        }

        try {
            $meeting = $this->meetingModel->find($id);
            if (!$meeting) {
                throw new Exception('Toplantı bulunamadı');
            }

            $attendees = $_POST['attendees'] ?? [];
            $db = Database::getInstance();

            // Tüm katılımcıları sil ve yeniden ekle
            $db->delete('meeting_attendees', ['meeting_id' => $id]);

            foreach ($attendees as $unitId => $data) {
                if (!empty($data['name'])) {
                    $db->insert('meeting_attendees', [
                        'meeting_id' => $id,
                        'unit_id' => (int)$unitId,
                        'attendee_name' => $data['name'],
                        'is_owner' => (int)($data['is_owner'] ?? 1),
                        'proxy_holder' => $data['proxy_holder'] ?? null,
                        'attended' => isset($data['attended']) ? 1 : 0,
                        'vote_weight' => (float)($data['vote_weight'] ?? 1.0)
                    ]);
                }
            }

            // Katılım sayısını güncelle
            $this->meetingModel->updateAttendanceCount($id);

            ActivityLogger::log('meeting.attendance_updated', 'building_meeting', $id);
            Utils::flash('success', 'Katılım kayıtları başarıyla güncellendi');
            redirect(base_url("/meetings/{$id}/attendance"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/meetings/{$id}/attendance"));
        }
    }

    /**
     * Toplantıyı tamamlandı olarak işaretle
     */
    public function complete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url("/meetings/{$id}/attendance"));
        }

        try {
            $this->meetingModel->update($id, ['status' => 'completed']);
            ActivityLogger::log('meeting.completed', 'building_meeting', $id);
            Utils::flash('success', 'Toplantı tamamlandı olarak işaretlendi');
            redirect(base_url("/meetings/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/meetings/{$id}"));
        }
    }
}


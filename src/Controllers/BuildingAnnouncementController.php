<?php
/**
 * Building Announcement Controller
 * Apartman/Site yönetimi - Duyuru kontrolcüsü
 */

class BuildingAnnouncementController
{
    private $announcementModel;
    private $buildingModel;

    public function __construct()
    {
        $this->announcementModel = new BuildingAnnouncement();
        $this->buildingModel = new Building();
    }

    /**
     * Duyuru listesi
     */
    public function index()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $filters = [];
        if ($buildingId) $filters['building_id'] = $buildingId;

        $announcements = $this->announcementModel->all($filters + ['active_only' => true]);
        $buildings = $this->buildingModel->active();

        try {
            echo View::renderWithLayout('announcements/index', [
                'title' => 'Duyurular',
                'announcements' => $announcements ?: [],
                'buildings' => $buildings ?: [],
                'buildingId' => $buildingId,
                'filters' => $filters ?? []
            ]);
        } catch (Exception $e) {
            error_log("BuildingAnnouncementController::index() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/'));
        }
    }

    /**
     * Duyuru oluştur
     */
    public function create()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $buildings = $this->buildingModel->active();

        echo View::renderWithLayout('announcements/form', [
            'title' => 'Duyuru Oluştur',
            'announcement' => null,
            'buildings' => $buildings,
            'buildingId' => $buildingId
        ]);
    }

    /**
     * Duyuru kaydet
     */
    public function store()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/announcements'));
        }

        try {
            $data = [
                'building_id' => (int)($_POST['building_id'] ?? 0),
                'title' => $_POST['title'] ?? '',
                'content' => $_POST['content'] ?? '',
                'announcement_type' => $_POST['announcement_type'] ?? 'info',
                'priority' => (int)($_POST['priority'] ?? 0),
                'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
                'publish_date' => $_POST['publish_date'] ?? date('Y-m-d'),
                'expire_date' => $_POST['expire_date'] ?? null,
                'send_email' => isset($_POST['send_email']) ? 1 : 0,
                'send_sms' => isset($_POST['send_sms']) ? 1 : 0,
                'created_by' => Auth::id()
            ];

            if (empty($data['building_id'])) {
                throw new Exception('Bina seçilmelidir');
            }

            if (empty($data['title'])) {
                throw new Exception('Başlık gereklidir');
            }

            $id = $this->announcementModel->create($data);

            ActivityLogger::log('announcement.created', 'building_announcement', $id);
            Utils::flash('success', 'Duyuru başarıyla oluşturuldu');
            redirect(base_url('/announcements'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/announcements/create'));
        }
    }

    /**
     * Duyuru sil
     */
    public function delete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/announcements'));
        }

        try {
            $this->announcementModel->delete($id);
            ActivityLogger::log('announcement.deleted', 'building_announcement', $id);
            Utils::flash('success', 'Duyuru başarıyla silindi');
            redirect(base_url('/announcements'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/announcements'));
        }
    }
}


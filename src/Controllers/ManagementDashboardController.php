<?php
/**
 * Yönetim Hizmetleri Dashboard Controller
 */

require_once __DIR__ . '/../Lib/ResidentPortalMetrics.php';
require_once __DIR__ . '/../Lib/Mock/MockHelper.php';
require_once __DIR__ . '/../Lib/Mock/ManagementMockData.php';

class ManagementDashboardController
{
    private Building $buildingModel;
    private Unit $unitModel;
    private ManagementFee $feeModel;
    private BuildingMeeting $meetingModel;
    private BuildingReservation $reservationModel;
    private BuildingAnnouncement $announcementModel;
    private Database $db;

    public function __construct()
    {
        $this->buildingModel = new Building();
        $this->unitModel = new Unit();
        $this->feeModel = new ManagementFee();
        $this->meetingModel = new BuildingMeeting();
        $this->reservationModel = new BuildingReservation();
        $this->announcementModel = new BuildingAnnouncement();
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireRole(['ADMIN', 'SITE_MANAGER', 'FINANCE']);

        if (MockHelper::enabled()) {
            $mock = ManagementMockData::dashboard();
            echo View::renderWithLayout('management/dashboard', [
                'title' => 'Yönetim Hizmetleri Dashboard',
                'summary' => $mock['summary'],
                'portalStats' => $mock['portalStats'],
                'pendingPortalInvites' => $mock['pendingPortalInvites'],
                'topOutstandingUnits' => $mock['topOutstandingUnits'],
                'upcomingMeetings' => $mock['upcomingMeetings'],
                'upcomingReservations' => $mock['upcomingReservations'],
                'recentAnnouncements' => $mock['recentAnnouncements'],
            ]);
            return;
        }

        $buildingStats = $this->db->fetch("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active
            FROM buildings
        ") ?: ['total' => 0, 'active' => 0];

        $unitStats = $this->db->fetch("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS occupied
            FROM units
        ") ?: ['total' => 0, 'occupied' => 0];

        $feeStats = $this->db->fetch("
            SELECT
                COALESCE(SUM(total_amount), 0) AS total,
                COALESCE(SUM(CASE WHEN status IN ('pending','partial','overdue')
                    THEN total_amount - paid_amount ELSE 0 END), 0) AS outstanding,
                COALESCE(SUM(CASE WHEN status = 'overdue'
                    THEN total_amount - paid_amount ELSE 0 END), 0) AS overdue,
                COALESCE(SUM(CASE WHEN status = 'paid'
                    THEN paid_amount ELSE 0 END), 0) AS collected
            FROM management_fees
        ") ?: ['total' => 0, 'outstanding' => 0, 'overdue' => 0, 'collected' => 0];

        $collectionRate = ($feeStats['total'] ?? 0) > 0
            ? round(($feeStats['collected'] ?? 0) / $feeStats['total'] * 100, 1)
            : 0;

        $occupancyRate = ($unitStats['total'] ?? 0) > 0
            ? round(($unitStats['occupied'] ?? 0) / $unitStats['total'] * 100, 1)
            : 0;

        $topOutstandingUnits = $this->db->fetchAll("
            SELECT
                u.unit_number,
                b.name AS building_name,
                ROUND(COALESCE(mf.total_amount, 0) - COALESCE(mf.paid_amount, 0), 2) AS balance,
                mf.period
            FROM management_fees mf
            LEFT JOIN units u ON mf.unit_id = u.id
            LEFT JOIN buildings b ON mf.building_id = b.id
            WHERE COALESCE(mf.status, 'pending') IN ('pending','partial','overdue')
              AND (COALESCE(mf.total_amount, 0) - COALESCE(mf.paid_amount, 0)) > 0
            ORDER BY balance DESC
            LIMIT 5
        ");

        $upcomingMeetings = $this->db->fetchAll("
            SELECT
                bm.id,
                bm.title,
                bm.meeting_date,
                bm.location,
                b.name AS building_name
            FROM building_meetings bm
            LEFT JOIN buildings b ON bm.building_id = b.id
            WHERE bm.meeting_date >= date('now')
            ORDER BY bm.meeting_date ASC
            LIMIT 5
        ");

        $upcomingReservations = $this->db->fetchAll("
            SELECT
                r.id,
                r.start_date,
                r.end_date,
                f.facility_name AS facility_name,
                b.name AS building_name,
                r.resident_name
            FROM building_reservations r
            LEFT JOIN building_facilities f ON r.facility_id = f.id
            LEFT JOIN buildings b ON r.building_id = b.id
            WHERE r.start_date >= datetime('now')
            ORDER BY r.start_date ASC
            LIMIT 5
        ");

        try {
            $portalRaw = ResidentPortalMetrics::getStats($this->db);
            $portalStats = [
                'total' => (int)($portalRaw['total'] ?? 0),
                'active' => (int)($portalRaw['active'] ?? 0),
                'verified' => (int)($portalRaw['verified'] ?? 0),
                'unverified' => (int)($portalRaw['unverified'] ?? 0),
                'logged_in_recent' => (int)($portalRaw['logged_in'] ?? 0),
            ];
        } catch (Throwable $e) {
            Logger::warning('Yönetim dashboardu portal istatistiklerini alamadı', ['exception' => $e]);
            $portalStats = [
                'total' => 0,
                'active' => 0,
                'verified' => 0,
                'unverified' => 0,
                'logged_in_recent' => 0,
            ];
        }

        try {
            $pendingPortalInvites = $this->db->fetchAll("
                SELECT
                    ru.name,
                    ru.email,
                    ru.created_at,
                    b.name AS building_name,
                    u.unit_number
                FROM resident_users ru
                LEFT JOIN units u ON ru.unit_id = u.id
                LEFT JOIN buildings b ON u.building_id = b.id
                WHERE ru.email_verified = 0
                ORDER BY ru.created_at DESC
                LIMIT 5
            ");
        } catch (Throwable $e) {
            Logger::warning('Yönetim dashboardu portal davetlerini alamadı', ['exception' => $e]);
            $pendingPortalInvites = [];
        }

        $recentAnnouncements = $this->db->fetchAll("
            SELECT
                a.id,
                a.title,
                a.publish_date,
                b.name AS building_name
            FROM building_announcements a
            LEFT JOIN buildings b ON a.building_id = b.id
            ORDER BY datetime(a.publish_date) DESC
            LIMIT 5
        ");

        echo View::renderWithLayout('management/dashboard', [
            'title' => 'Yönetim Hizmetleri Dashboard',
            'summary' => [
                'buildings_total' => (int)($buildingStats['total'] ?? 0),
                'buildings_active' => (int)($buildingStats['active'] ?? 0),
                'units_total' => (int)($unitStats['total'] ?? 0),
                'occupancy_rate' => $occupancyRate,
                'fees_outstanding' => (float)($feeStats['outstanding'] ?? 0),
                'fees_overdue' => (float)($feeStats['overdue'] ?? 0),
                'fees_collected' => (float)($feeStats['collected'] ?? 0),
                'collection_rate' => $collectionRate,
            ],
            'portalStats' => $portalStats,
            'pendingPortalInvites' => $pendingPortalInvites,
            'topOutstandingUnits' => $topOutstandingUnits,
            'upcomingMeetings' => $upcomingMeetings,
            'upcomingReservations' => $upcomingReservations,
            'recentAnnouncements' => $recentAnnouncements,
        ]);
    }
}


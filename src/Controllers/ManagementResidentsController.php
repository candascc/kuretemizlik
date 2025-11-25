<?php
/**
 * Yönetim Hizmetleri - Sakin Yönetimi & Portal görünümü
 */

require_once __DIR__ . '/../Lib/ResidentPortalMetrics.php';
require_once __DIR__ . '/../Lib/Mock/MockHelper.php';
require_once __DIR__ . '/../Lib/Mock/ManagementMockData.php';

class ManagementResidentsController
{
    private ResidentUser $residentUserModel;
    private Database $db;
    private ResidentNotificationPreferenceService $preferenceService;

    public function __construct()
    {
        $this->residentUserModel = new ResidentUser();
        $this->db = Database::getInstance();
        $this->preferenceService = new ResidentNotificationPreferenceService();
    }

    public function index(): void
    {
        Auth::requireGroup('nav.management.residents');

        [$filters, $searchTerm, $buildingFilter, $page, $perPage] = $this->resolveFilters();
        $alerts = [];

        if (MockHelper::enabled()) {
            $mock = ManagementMockData::residents($filters, $page, $perPage);

            echo View::renderWithLayout('management/residents', [
                'title' => 'Sakin Yönetimi',
                'portalStats' => $mock['portalStats'],
                'recentPortalLogins' => $mock['recentPortalLogins'],
                'pendingVerifications' => $mock['pendingVerifications'],
                'requestStats' => $mock['requestStats'],
                'recentRequests' => $mock['recentRequests'],
                'residents' => $mock['residents'],
                'filters' => [
                    'building_id' => $buildingFilter,
                    'search' => $searchTerm,
                ],
                'pagination' => [
                    'page' => $mock['page'],
                    'per_page' => $perPage,
                    'total' => $mock['total_residents'],
                    'pages' => $mock['pages'],
                ],
                'buildings' => $mock['buildings'],
                'alerts' => $mock['alerts'],
                'notificationPreferenceStats' => $mock['notificationPreferenceStats'] ?? [],
            ]);
            return;
        }

        $portalStats = $this->fetchPortalStats($alerts);
        $recentPortalLogins = $this->fetchRecentPortalLogins($alerts);
        $pendingVerifications = $this->fetchPendingVerifications($alerts);
        [$requestStats, $recentRequests] = $this->fetchResidentRequests($alerts);
        [$residents, $totalResidents, $page] = $this->fetchResidentList($filters, $page, $perPage, $alerts);
        $buildings = $this->fetchBuildings($alerts);
        $notificationPreferenceStats = $this->preferenceService->getCategoryStats();

        $totalPages = max(1, (int)ceil(($totalResidents ?: 0) / $perPage));

        echo View::renderWithLayout('management/residents', [
            'title' => 'Sakin Yönetimi',
            'portalStats' => $portalStats,
            'recentPortalLogins' => $recentPortalLogins,
            'pendingVerifications' => $pendingVerifications,
            'requestStats' => $requestStats,
            'recentRequests' => $recentRequests,
            'residents' => $residents,
            'filters' => [
                'building_id' => $buildingFilter,
                'search' => $searchTerm,
            ],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalResidents,
                'pages' => $totalPages,
            ],
            'buildings' => $buildings,
            'alerts' => $alerts,
            'notificationPreferenceStats' => $notificationPreferenceStats,
        ]);
    }

    private function resolveFilters(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(5, min(100, (int)($_GET['per_page'] ?? 25)));
        $buildingFilter = isset($_GET['building_id']) ? (int)$_GET['building_id'] : null;
        $searchTerm = trim((string)($_GET['search'] ?? ''));

        $residentFilters = [];
        if ($buildingFilter) {
            $residentFilters['building_id'] = $buildingFilter;
        }
        if ($searchTerm !== '') {
            $residentFilters['search'] = $searchTerm;
        }

        return [$residentFilters, $searchTerm, $buildingFilter, $page, $perPage];
    }

    private function fetchPortalStats(array &$alerts): array
    {
        try {
            return ResidentPortalMetrics::getStats($this->db);
        } catch (Throwable $e) {
            $this->recordAlert(
                $alerts,
                'Portal istatistikleri getirilemedi. Lütfen daha sonra tekrar deneyin.',
                $e,
                'resident_portal_stats_failure'
            );
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'verified' => 0,
                'unverified' => 0,
                'logged_in' => 0,
            ];
        }
    }

    private function fetchRecentPortalLogins(array &$alerts): array
    {
        try {
            return $this->db->fetchAll("
                SELECT
                    ru.id,
                    ru.name,
                    ru.email,
                    ru.last_login_at,
                    ru.created_at,
                    ru.email_verified,
                    ru.is_active,
                    u.unit_number,
                    b.name AS building_name
                FROM resident_users ru
                LEFT JOIN units u ON ru.unit_id = u.id
                LEFT JOIN buildings b ON u.building_id = b.id
                ORDER BY COALESCE(ru.last_login_at, ru.created_at) DESC
                LIMIT 8
            ") ?? [];
        } catch (Throwable $e) {
            $this->recordAlert(
                $alerts,
                'Portal giriş kayıtları yüklenirken sorun oluştu.',
                $e,
                'resident_portal_logins_failure'
            );
            return [];
        }
    }

    private function fetchPendingVerifications(array &$alerts): array
    {
        try {
            return $this->db->fetchAll("
                SELECT
                    ru.id,
                    ru.name,
                    ru.email,
                    ru.created_at,
                    ru.verification_token,
                    u.unit_number,
                    b.name AS building_name
                FROM resident_users ru
                LEFT JOIN units u ON ru.unit_id = u.id
                LEFT JOIN buildings b ON u.building_id = b.id
                WHERE ru.email_verified = 0
                ORDER BY ru.created_at DESC
                LIMIT 8
            ") ?? [];
        } catch (Throwable $e) {
            $this->recordAlert(
                $alerts,
                'Bekleyen portal davetleri listesi yüklenemedi.',
                $e,
                'resident_portal_invites_failure'
            );
            return [];
        }
    }

    private function fetchResidentRequests(array &$alerts): array
    {
        try {
            $stats = $this->db->fetch("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved
                FROM resident_requests
            ") ?: [
                'total' => 0,
                'open' => 0,
                'in_progress' => 0,
                'resolved' => 0,
            ];
        } catch (Throwable $e) {
            $this->recordAlert(
                $alerts,
                'Sakin taleplerine ait özet bilgisi yüklenemedi.',
                $e,
                'resident_portal_requests_stats_failure'
            );
            $stats = [
                'total' => 0,
                'open' => 0,
                'in_progress' => 0,
                'resolved' => 0,
            ];
        }

        try {
            $recent = $this->db->fetchAll("
                SELECT
                    rr.id,
                    rr.subject,
                    rr.request_type,
                    rr.priority,
                    rr.status,
                    rr.created_at,
                    b.name AS building_name,
                    u.unit_number,
                    ru.name AS resident_name
                FROM resident_requests rr
                LEFT JOIN buildings b ON rr.building_id = b.id
                LEFT JOIN units u ON rr.unit_id = u.id
                LEFT JOIN resident_users ru ON rr.resident_user_id = ru.id
                ORDER BY rr.created_at DESC
                LIMIT 8
            ") ?? [];
        } catch (Throwable $e) {
            $this->recordAlert(
                $alerts,
                'Sakin talepleri listesi şu anda görüntülenemiyor.',
                $e,
                'resident_portal_requests_list_failure'
            );
            $recent = [];
        }

        return [$stats, $recent];
    }

    private function fetchResidentList(array $filters, int $page, int $perPage, array &$alerts): array
    {
        try {
            $totalResidents = $this->residentUserModel->count($filters);
        } catch (Throwable $e) {
            $this->recordAlert(
                $alerts,
                'Sakin listesi toplamı hesaplanamadı.',
                $e,
                'resident_portal_resident_count_failure'
            );
            return [[], 0, 1];
        }

        $totalPages = max(1, (int)ceil(($totalResidents ?: 0) / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        try {
            $residents = $this->residentUserModel->all($filters, $perPage, $offset) ?? [];
        } catch (Throwable $e) {
            $this->recordAlert(
                $alerts,
                'Sakin listesi şu anda görüntülenemiyor.',
                $e,
                'resident_portal_resident_list_failure'
            );
            $residents = [];
        }

        return [$residents, $totalResidents, $page];
    }

    private function fetchBuildings(array &$alerts): array
    {
        try {
            return $this->db->fetchAll("
                SELECT id, name
                FROM buildings
                ORDER BY name ASC
            ") ?? [];
        } catch (Throwable $e) {
            $this->recordAlert(
                $alerts,
                'Bina listesi yüklenemedi.',
                $e,
                'resident_portal_building_list_failure'
            );
            return [];
        }
    }

    private function recordAlert(array &$alerts, string $message, ?Throwable $exception, string $context): void
    {
        $reference = strtoupper(bin2hex(random_bytes(4)));
        $alerts[] = [
            'message' => $message,
            'reference' => $reference,
        ];

        if (class_exists('Logger')) {
            $payload = ['ref' => $reference];
            if ($exception) {
                $payload['error'] = $exception->getMessage();
            }
            Logger::warning($context, $payload);
        }
    }
}


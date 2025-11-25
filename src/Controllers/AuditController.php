<?php
/**
 * Audit Controller
 * Manages audit logs, compliance reports, and security monitoring
 */

class AuditController
{
    private $auditLogger;
    
    public function __construct()
    {
        $this->auditLogger = AuditLogger::getInstance();
    }
    
    /**
     * Show audit logs dashboard
     */
    public function index()
    {
        Auth::require();
        Auth::requireAdmin();
        
        // STAGE 1 ROUND 2: Enhanced filters with IP and company support
        // ROUND 3: Auto-filter by company for non-SUPERADMIN
        $userCompanyId = Auth::user()['company_id'] ?? null;
        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
            'category' => $_GET['category'] ?? '',
            'user_id' => $_GET['user_id'] ?? '',
            'action' => $_GET['action'] ?? '',
            'ip_address' => $_GET['ip_address'] ?? '', // STAGE 1 ROUND 2: IP filter
            'company_id' => Auth::hasRole('SUPERADMIN') ? ($_GET['company_id'] ?? '') : ($userCompanyId ?? '') // ROUND 3: Auto-filter by company for non-SUPERADMIN
        ];
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        // Phase 4.2: Use constant for audit log page size
        $limit = AppConstants::AUDIT_LOG_PAGE_SIZE;
        $offset = ($page - 1) * $limit;
        
        $logs = $this->auditLogger->getLogs($filters, $limit, $offset);
        $statistics = $this->auditLogger->getStatistics($filters);
        $summary = $this->auditLogger->getDashboardSummary();
        
        // ROUND 3: Get companies list for SUPERADMIN filter dropdown
        $companies = [];
        if (Auth::hasRole('SUPERADMIN')) {
            if (class_exists('Company')) {
                $companyModel = new Company();
                $companies = $companyModel->all();
            }
        }
        
        $data = [
            'title' => 'Denetim Kayıtları',
            'logs' => $logs,
            'statistics' => $statistics,
            'summary' => $summary,
            'filters' => $filters,
            'companies' => $companies, // ROUND 3: Companies for filter dropdown
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'has_more' => count($logs) === $limit
            ]
        ];
        
        echo View::renderWithLayout('audit/index', $data);
    }
    
    /**
     * Show detailed audit log
     */
    public function show($id)
    {
        Auth::require();
        Auth::requireAdmin();
        
        $db = Database::getInstance();
        $log = $db->fetch(
            "SELECT al.*, u.username, u.role as user_role 
             FROM activity_log al 
             LEFT JOIN users u ON al.actor_id = u.id 
             WHERE al.id = ?",
            [$id]
        );
        
        if (!$log) {
            set_flash('error', 'Denetim kaydı bulunamadı.');
            redirect(base_url('/audit'));
        }
        
        $data = [
            'title' => 'Denetim Kaydı Detayları',
            'log' => $log
        ];
        
        echo View::renderWithLayout('audit/show', $data);
    }
    
    /**
     * Show user activity
     */
    public function userActivity($userId)
    {
        Auth::require();
        Auth::requireAdmin();
        
        $days = (int)($_GET['days'] ?? 30);
        $activity = $this->auditLogger->getUserActivity($userId, $days);
        
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            set_flash('error', 'Kullanıcı bulunamadı.');
            redirect(base_url('/audit'));
        }
        
        $data = [
            'title' => 'Kullanıcı Etkinliği - ' . $user['username'],
            'user' => $user,
            'activity' => $activity,
            'days' => $days
        ];
        
        echo View::renderWithLayout('audit/user-activity', $data);
    }

    /**
     * Show per-role audit summary
     */
    public function roleSummary()
    {
        Auth::require();
        Auth::requireAdmin();

        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
        ];

        $summary = $this->auditLogger->getRoleSummary($filters);
        $matrix = $this->auditLogger->getRoleActionMatrix($filters);
        $anomalies = $this->auditLogger->getRoleAnomalies($filters);

        $data = [
            'title' => 'Rol Bazlı Aktivite Özeti',
            'filters' => $filters,
            'summary' => $summary,
            'action_matrix' => $matrix,
            'anomalies' => $anomalies,
        ];

        echo View::renderWithLayout('audit/role-summary', $data);
    }
    
    /**
     * Export audit logs
     */
    /**
     * Export audit logs
     * ROUND 3: Enhanced with IP and company_id filters
     */
    public function export()
    {
        Auth::require();
        Auth::requireAdmin();
        
        // ROUND 3: Multi-tenant awareness - non-SUPERADMIN can only export their company's logs
        if (!Auth::hasRole('SUPERADMIN')) {
            // Restrict to user's company
            $userCompanyId = Auth::user()['company_id'] ?? null;
            if ($userCompanyId) {
                $_GET['company_id'] = $userCompanyId;
            }
        }
        
        $format = $_GET['format'] ?? 'csv';
        $filters = [
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'category' => $_GET['category'] ?? '',
            'user_id' => $_GET['user_id'] ?? '',
            'ip_address' => $_GET['ip_address'] ?? '', // ROUND 3: IP filter
            'company_id' => $_GET['company_id'] ?? '' // ROUND 3: Company filter
        ];
        
        // Remove empty filters
        $filters = array_filter($filters);
        
        try {
            $exportData = $this->auditLogger->exportLogs($filters, $format);
            
            $filename = 'audit_logs_' . date('Y-m-d_H-i-s') . '.' . $format;
            
            if ($format === 'csv') {
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            } else {
                header('Content-Type: application/json; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            }
            
            echo $exportData;
            exit;
            
        } catch (Exception $e) {
            set_flash('error', 'Dışa aktarma başarısız: ' . Utils::safeExceptionMessage($e));
            redirect(base_url('/audit'));
        }
    }

    public function exportRoleSummary()
    {
        Auth::require();
        Auth::requireAdmin();

        $format = $_GET['format'] ?? 'csv';
        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
        ];

        $payload = $this->auditLogger->exportRoleSummary($filters, $format);
        $filename = 'role_summary_' . date('Y-m-d_H-i-s') . '.' . $format;

        if ($format === 'json') {
            header('Content-Type: application/json');
        } else {
            header('Content-Type: text/csv');
        }
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $payload;
        exit;
    }
    
    /**
     * Show security alerts
     */
    public function alerts()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $db = Database::getInstance();
        $alerts = $db->fetchAll(
            "SELECT aa.*, u.username as read_by_username 
             FROM audit_alerts aa 
             LEFT JOIN users u ON aa.read_by = u.id 
             ORDER BY aa.created_at DESC 
             LIMIT 100"
        );
        
        $unreadCount = $db->fetch(
            "SELECT COUNT(*) as count FROM audit_alerts WHERE is_read = 0"
        )['count'];
        
        $data = [
            'title' => 'Güvenlik Uyarıları',
            'alerts' => $alerts,
            'unread_count' => $unreadCount
        ];
        
        echo View::renderWithLayout('audit/alerts', $data);
    }
    
    /**
     * Mark alert as read
     */
    public function markAlertRead($id)
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/audit/alerts'));
        }
        
        $db = Database::getInstance();
        $result = $db->query(
            "UPDATE audit_alerts 
             SET is_read = 1, read_at = ?, read_by = ? 
             WHERE id = ?",
            [date('Y-m-d H:i:s'), Auth::id(), $id]
        );
        
        if ($result) {
            set_flash('success', 'Uyarı okundu olarak işaretlendi.');
        } else {
            set_flash('error', 'Uyarı işaretlenemedi.');
        }
        
        redirect(base_url('/audit/alerts'));
    }
    
    /**
     * Show compliance report
     */
    public function compliance()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $period = $_GET['period'] ?? '30';
        $dateFrom = date('Y-m-d', strtotime("-{$period} days"));
        $dateTo = date('Y-m-d');
        
        $db = Database::getInstance();
        
        // Get compliance statistics
        $stats = $db->fetch(
            "SELECT 
                COUNT(*) as total_events,
                COUNT(DISTINCT actor_id) as unique_users,
                COUNT(CASE WHEN entity = 'SECURITY' THEN 1 END) as security_events,
                COUNT(CASE WHEN entity = 'DATA_MODIFICATION' THEN 1 END) as data_changes,
                COUNT(CASE WHEN entity = 'AUTH' THEN 1 END) as auth_events
             FROM activity_log 
             WHERE created_at BETWEEN ? AND ?",
            [$dateFrom, $dateTo]
        );
        
        // Get top users by activity
        $topUsers = $db->fetchAll(
            "SELECT u.username, COUNT(al.id) as activity_count
             FROM activity_log al
             LEFT JOIN users u ON al.actor_id = u.id
             WHERE al.created_at BETWEEN ? AND ?
             GROUP BY al.actor_id, u.username
             ORDER BY activity_count DESC
             LIMIT 10",
            [$dateFrom, $dateTo]
        );
        
        // Get category breakdown
        $categoryBreakdown = $db->fetchAll(
            "SELECT entity as category, COUNT(*) as count
             FROM activity_log
             WHERE created_at BETWEEN ? AND ?
             GROUP BY entity
             ORDER BY count DESC",
            [$dateFrom, $dateTo]
        );
        
        $data = [
            'title' => 'Uyumluluk Raporu',
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'stats' => $stats,
            'top_users' => $topUsers,
            'category_breakdown' => $categoryBreakdown
        ];
        
        echo View::renderWithLayout('audit/compliance', $data);
    }
    
    /**
     * Anonymize user data (GDPR compliance)
     */
    public function anonymizeUser($userId)
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/audit'));
        }
        
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            set_flash('error', 'Bu işlem için şifre gereklidir.');
            redirect(base_url('/audit'));
        }
        
        // Verify admin password
        $admin = Auth::user();
        $passwordHash = (string)($admin['password_hash'] ?? '');
        if (empty($passwordHash) || !password_verify($password, $passwordHash)) {
            set_flash('error', 'Geçersiz şifre.');
            redirect(base_url('/audit'));
        }
        
        // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash) {
                try {
                    $userModel = new User();
                    $userModel->update($admin['id'], ['password' => $password]);
                } catch (Exception $e) {
                    // Log but don't fail if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash failed for user {$admin['id']}: " . $e->getMessage());
                    }
                }
            }
        }
        // ===== ERR-014 FIX: End =====
        
        // Anonymize user data
        if ($this->auditLogger->anonymizeUserData($userId)) {
            set_flash('success', 'Kullanıcı verileri başarıyla anonimleştirildi.');
        } else {
            set_flash('error', 'Kullanıcı verileri anonimleştirilemedi.');
        }
        
        redirect(base_url('/audit'));
    }
    
    /**
     * Clean up old audit logs
     * ROUND 3: Enhanced with config-aware retention policy
     */
    public function cleanup()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('/audit'));
        }
        
        // ROUND 3: Check if cleanup is enabled in config
        $configPath = __DIR__ . '/../../config/security.php';
        $cleanupEnabled = false;
        if (file_exists($configPath)) {
            $config = require $configPath;
            $cleanupEnabled = $config['audit']['enable_retention_cleanup'] ?? false;
        }
        
        if (!$cleanupEnabled) {
            set_flash('error', 'Audit log cleanup is disabled in configuration. Enable it in config/security.php');
            redirect(base_url('/audit'));
        }
        
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            set_flash('error', 'Bu işlem için şifre gereklidir.');
            redirect(base_url('/audit'));
        }
        
        // Verify admin password
        $admin = Auth::user();
        $passwordHash = (string)($admin['password_hash'] ?? '');
        if (empty($passwordHash) || !password_verify($password, $passwordHash)) {
            set_flash('error', 'Geçersiz şifre.');
            redirect(base_url('/audit'));
        }
        
        // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash) {
                try {
                    $userModel = new User();
                    $userModel->update($admin['id'], ['password' => $password]);
                } catch (Exception $e) {
                    // Log but don't fail if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash failed for user {$admin['id']}: " . $e->getMessage());
                    }
                }
            }
        }
        // ===== ERR-014 FIX: End =====
        
        // Clean up old logs
        // ROUND 3: Use new cleanupOldRecords method with config-aware retention
        $deletedCount = $this->auditLogger->cleanupOldRecords();
        
        set_flash('success', "{$deletedCount} eski denetim kaydı temizlendi.");
        redirect(base_url('/audit'));
    }
    
    /**
     * Get audit log statistics for API
     */
    public function statistics()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d')
        ];
        
        $statistics = $this->auditLogger->getStatistics($filters);
        
        header('Content-Type: application/json');
        echo json_encode($statistics);
        exit;
    }
}

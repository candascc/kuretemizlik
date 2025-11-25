<?php
/**
 * Security Controller
 * 
 * ROUND 5 - STAGE 3: Security Dashboard & Analytics
 * 
 * Provides security dashboard with aggregated statistics and recent security events.
 * 
 * @package App\Controllers
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/../Services/SecurityStatsService.php';
require_once __DIR__ . '/../Lib/Auth.php';
require_once __DIR__ . '/../Lib/View.php';
require_once __DIR__ . '/../Lib/Utils.php';

class SecurityController
{
    private $statsService;
    
    public function __construct()
    {
        $this->statsService = new SecurityStatsService();
    }
    
    /**
     * Show security dashboard
     * ROUND 5 - STAGE 3: Security dashboard with KPI cards and event tables
     */
    public function dashboard()
    {
        Auth::require();
        
        // Only SUPERADMIN and ADMIN can access security dashboard
        $currentUser = Auth::user();
        $userRole = $currentUser['role'] ?? null;
        
        if (!in_array($userRole, ['SUPERADMIN', 'ADMIN'])) {
            Utils::flash('error', 'Bu sayfaya erişim yetkiniz yok.');
            redirect(base_url('/'));
        }
        
        // Multi-tenant isolation
        $userCompanyId = $currentUser['company_id'] ?? null;
        $companyId = null;
        
        // SUPERADMIN can view all companies or filter by company
        if ($userRole === 'SUPERADMIN') {
            $companyId = !empty($_GET['company_id']) ? (int)$_GET['company_id'] : null;
        } else {
            // Regular ADMIN can only view their own company
            $companyId = $userCompanyId;
        }
        
        // Date filters
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d H:i:s', strtotime('-24 hours'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d H:i:s');
        
        // Get security statistics
        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'company_id' => $companyId,
        ];
        
        $stats = $this->statsService->getSecurityStats($filters);
        
        // Get companies list for SUPERADMIN filter
        $companies = [];
        if ($userRole === 'SUPERADMIN') {
            // Try to load Company model if it exists
            $companyModelPath = __DIR__ . '/../Models/Company.php';
            if (file_exists($companyModelPath)) {
                require_once $companyModelPath;
                if (class_exists('Company')) {
                    $companyModel = new Company();
                    $companies = $companyModel->all();
                }
            }
        }
        
        $data = [
            'title' => 'Security Dashboard',
            'stats' => $stats,
            'companies' => $companies,
            'selected_company_id' => $companyId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'user_role' => $userRole,
        ];
        
        echo View::renderWithLayout('security/dashboard', $data);
    }
}


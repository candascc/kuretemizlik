<?php
/**
 * Dashboard Controller
 * Main dashboard for the application
 */

require_once __DIR__ . '/../Constants/AppConstants.php';

class DashboardController
{
    use CompanyScope;
    /**
     * Show main dashboard
     */
    public function index()
    {
        // Prevent caching of dashboard page
        if (!headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        $this->today();
    }
    
    /**
     * Show today's dashboard (alias for index)
     * ROUND 19: Enhanced error handling to prevent 500 errors after login
     * ROUND 31: First-load 500 fix - comprehensive hardening for login redirect scenario
     */
    public function today()
    {
        // ===== LOGIN_500_PATHC: Generate request ID and log start =====
        require_once __DIR__ . '/../Lib/PathCLogger.php';
        try {
            $requestId = bin2hex(random_bytes(8));
        } catch (Exception $e) {
            $requestId = uniqid('app_', true);
        }
        PathCLogger::setRequestId($requestId);
        PathCLogger::log('APP_HTML_START', []);
        // ===== LOGIN_500_PATHC END =====
        
        // ===== LOGIN_500_STAGE1: Log before auth check =====
        $logFile = __DIR__ . '/../../logs/login_500_trace.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sessionIdBefore = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
        $sessionStatusBefore = session_status();
        $cookieName = session_name();
        $cookieExistsBefore = isset($_COOKIE[$cookieName]);
        $cookieValueBefore = $cookieExistsBefore ? substr($_COOKIE[$cookieName], 0, 12) . '...' : 'none';
        $userIdBefore = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'none';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [DashboardController::today] BEFORE_AUTH uri={$requestUri}, ip={$ip}, session_id={$sessionIdBefore}, session_status={$sessionStatusBefore}, cookie_name={$cookieName}, cookie_exists=" . ($cookieExistsBefore ? 'yes' : 'no') . ", cookie_value={$cookieValueBefore}, user_id={$userIdBefore}\n", FILE_APPEND | LOCK_EX);
        // ===== LOGIN_500_STAGE1 END =====
        
        // ===== LOGIN_500_PATHC: Log try enter =====
        if (class_exists('PathCLogger')) {
            PathCLogger::log('APP_HTML_TRY_ENTER', []);
        }
        // ===== LOGIN_500_PATHC END =====
        
        // ROUND 31: Wrap entire method in try/catch as final safety net
        try {
            // ===== CRITICAL FIX: Auth::require() now handles session initialization =====
            // No need to start session here - Auth::require() handles it
            try {
                Auth::require();
                
                // ===== LOGIN_500_STAGE1: Log after auth check =====
                $sessionIdAfter = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
                $sessionStatusAfter = session_status();
                $cookieExistsAfter = isset($_COOKIE[$cookieName]);
                $cookieValueAfter = $cookieExistsAfter ? substr($_COOKIE[$cookieName], 0, 12) . '...' : 'none';
                $userIdAfter = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'none';
                $loginTimeAfter = isset($_SESSION['login_time']) ? $_SESSION['login_time'] : 'none';
                $authCheckResult = Auth::check();
                $authIdResult = Auth::id();
                // ===== LOGIN_500_STAGE1_ROLE: Add role info =====
                $sessionRole = $_SESSION['role'] ?? null;
                $sessionRoleNormalized = $sessionRole ? strtoupper(trim($sessionRole)) : 'null';
                $sessionUsername = $_SESSION['username'] ?? null;
                $authRole = Auth::role();
                $authRoleNormalized = $authRole ? strtoupper(trim($authRole)) : 'null';
                $authUser = Auth::user();
                $dbRole = $authUser['role'] ?? null;
                $dbRoleNormalized = $dbRole ? strtoupper(trim($dbRole)) : 'null';
                $isAdminLike = $sessionRole ? in_array(strtoupper(trim($sessionRole)), ['ADMIN', 'SUPERADMIN'], true) : false;
                // ===== LOGIN_500_STAGE1_ROLE END =====
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [DashboardController::today] AFTER_AUTH uri={$requestUri}, session_id={$sessionIdAfter}, session_status={$sessionStatusAfter}, cookie_exists=" . ($cookieExistsAfter ? 'yes' : 'no') . ", cookie_value={$cookieValueAfter}, user_id={$userIdAfter}, login_time={$loginTimeAfter}, Auth::check()=" . ($authCheckResult ? 'true' : 'false') . ", Auth::id()={$authIdResult}, session_role={$sessionRoleNormalized}, Auth::role()={$authRoleNormalized}, db_role={$dbRoleNormalized}, username={$sessionUsername}, is_admin_like=" . ($isAdminLike ? '1' : '0') . "\n", FILE_APPEND | LOCK_EX);
                // ===== LOGIN_500_STAGE1 END =====
            } catch (Throwable $authError) {
                // ===== LOGIN_500_STAGE1: Log auth error =====
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [DashboardController::today] AUTH_ERROR uri={$requestUri}, error=" . str_replace(["\n", "\r"], [' ', ' '], $authError->getMessage()) . ", file=" . $authError->getFile() . ", line=" . $authError->getLine() . "\n", FILE_APPEND | LOCK_EX);
                // ===== LOGIN_500_STAGE1 END =====
                
                // ROUND 31: Log auth error but don't throw - redirect to login instead
                // Phase 3.4: Use Logger instead of error_log
                if (class_exists('Logger')) {
                    Logger::error("DashboardController::today() - Auth::require() error: " . $authError->getMessage(), [
                        'exception' => $authError,
                        'trace' => $authError->getTraceAsString()
                    ]);
                } else {
                    error_log("DashboardController::today() - Auth::require() error: " . $authError->getMessage());
                    error_log("Stack trace: " . $authError->getTraceAsString());
                }
                Utils::flash('error', 'Oturum süreniz dolmuş. Lütfen tekrar giriş yapın.');
                redirect(base_url('/login'));
                return;
            }
            
            // ===== PRODUCTION FIX: Prevent caching of dashboard page =====
            Utils::setNoCacheHeaders();
            // ===== PRODUCTION FIX END =====
            
            // ROUND 31: Initialize data with safe defaults BEFORE any DB operations
            $data = [
                'title' => 'Ana Sayfa',
                'stats' => [
                    'today_jobs' => 0,
                    'active_customers' => 0,
                    'week_income' => 0,
                    'pending_appointments' => 0
                ],
                'todayJobs' => [],
                'recentActivities' => [],
                'upcomingAppointments' => [],
                'weeklyIncomeTrend' => [],
                'recurringStats' => [],
                'notifications' => [],
                'companyContext' => []
            ];
            
            // Cache key based on date to auto-invalidate daily
            $today = date('Y-m-d');
            $cacheKey = "dashboard:today:{$today}";
            
            // ROUND 31: Use Cache::remember pattern with comprehensive error handling
            try {
                $cachedData = Cache::remember($cacheKey, function() use ($today) {
                    return $this->buildDashboardData($today);
                }, 300); // 5 minutes cache
                
                // Ensure cached data is valid array
                if (is_array($cachedData)) {
                    // ===== LOGIN_500_PATHC: Log cache hit =====
                    if (class_exists('PathCLogger')) {
                        PathCLogger::log('PATHC_CACHE_HIT', ['cache_key' => $cacheKey]);
                    }
                    // ===== LOGIN_500_PATHC END =====
                    $data = $cachedData;
                } else {
                    // ===== LOGIN_500_PATHC: Log cache corrupt =====
                    if (class_exists('PathCLogger')) {
                        PathCLogger::log('PATHC_CACHE_CORRUPT', ['cache_key' => $cacheKey]);
                    }
                    // ===== LOGIN_500_PATHC END =====
                }
            } catch (Throwable $cacheError) {
                // ===== LOGIN_500_PATHC: Log cache exception =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHC_CACHE_EXCEPTION', $cacheError, ['cache_key' => $cacheKey]);
                }
                // ===== LOGIN_500_PATHC END =====
                
                // If cache fails, build data directly
                if (APP_DEBUG) {
                    // Phase 3.4: Use Logger instead of error_log
                    if (class_exists('Logger')) {
                        Logger::warning("Cache error in dashboard: " . $cacheError->getMessage(), ['exception' => $cacheError]);
                    } else {
                        error_log("Cache error in dashboard: " . $cacheError->getMessage());
                    }
                }
                
                // ===== LOGIN_500_PATHC: Log cache fallback =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::log('PATHC_CACHE_FALLBACK', ['cache_key' => $cacheKey]);
                }
                // ===== LOGIN_500_PATHC END =====
                
                // ROUND 31: Catch and log errors in buildDashboardData
                try {
                    $builtData = $this->buildDashboardData($today);
                    if (is_array($builtData)) {
                        $data = $builtData;
                    }
                } catch (Throwable $buildError) {
                    // ROUND 31: Log error with AppErrorHandler if available
                    if (class_exists('AppErrorHandler')) {
                        AppErrorHandler::logException($buildError, ['context' => 'DashboardController::today() - buildDashboardData']);
                    } else {
                        error_log("Dashboard buildDashboardData error: " . $buildError->getMessage());
                        error_log("Stack trace: " . $buildError->getTraceAsString());
                    }
                    
                    // Use safe defaults (already set above)
                    // Data is already initialized with safe defaults, no need to set again
                }
            }
            
            // ROUND 31: Final validation - ensure data structure is valid before rendering
            if (!is_array($data)) {
                $data = [
                    'title' => 'Ana Sayfa',
                    'stats' => ['today_jobs' => 0, 'active_customers' => 0, 'week_income' => 0, 'pending_appointments' => 0],
                    'todayJobs' => [],
                    'recentActivities' => [],
                    'upcomingAppointments' => [],
                    'weeklyIncomeTrend' => [],
                    'recurringStats' => [],
                    'notifications' => [],
                    'companyContext' => []
                ];
            }
            
            // ROUND 31: Ensure all required keys exist with safe defaults
            $data['title'] = $data['title'] ?? 'Ana Sayfa';
            $data['stats'] = $data['stats'] ?? ['today_jobs' => 0, 'active_customers' => 0, 'week_income' => 0, 'pending_appointments' => 0];
            $data['todayJobs'] = $data['todayJobs'] ?? [];
            $data['recentActivities'] = $data['recentActivities'] ?? [];
            $data['upcomingAppointments'] = $data['upcomingAppointments'] ?? [];
            $data['weeklyIncomeTrend'] = $data['weeklyIncomeTrend'] ?? [];
            $data['recurringStats'] = $data['recurringStats'] ?? [];
            $data['notifications'] = $data['notifications'] ?? [];
            $data['companyContext'] = $data['companyContext'] ?? [];
            
            // ===== LOGIN_500_PATHC: Log before view render =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('APP_HTML_BEFORE_RENDER', []);
            }
            // ===== LOGIN_500_PATHC END =====
            
            // ROUND 31: Render view with comprehensive error handling
            try {
                echo View::renderWithLayout('dashboard', $data);
                
                // ===== LOGIN_500_PATHC: Log after view render (success) =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::log('APP_HTML_AFTER_RENDER', []);
                }
                // ===== LOGIN_500_PATHC END =====
            } catch (Throwable $viewError) {
                // ===== LOGIN_500_PATHC: Log exception =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('APP_HTML_VIEW_EXCEPTION', $viewError, []);
                }
                // ===== LOGIN_500_PATHC END =====
                
                // ROUND 31: Log view rendering error
                error_log("DashboardController::today() - View::renderWithLayout() error: " . $viewError->getMessage());
                error_log("Stack trace: " . $viewError->getTraceAsString());
                
                // Show error page instead of 500
                View::error('Dashboard yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.', 200, $viewError->getMessage());
            }
            
            // ===== LOGIN_500_PATHC: Log try exit (normal flow) =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('APP_HTML_TRY_EXIT', []);
            }
            // ===== LOGIN_500_PATHC END =====
        } catch (Throwable $e) {
            // ===== LOGIN_500_PATHC: Log fatal exception =====
            if (class_exists('PathCLogger')) {
                PathCLogger::logException('PATHC_DASHBOARD_TODAY_FATAL', $e, []);
            }
            // ===== LOGIN_500_PATHC END =====
            
            // ===== LOGIN_500_PATHC: Log exception =====
            if (class_exists('PathCLogger')) {
                PathCLogger::logException('APP_HTML_EXCEPTION', $e, []);
            }
            // ===== LOGIN_500_PATHC END =====
            
            // ROUND 31: Final safety net - catch any unexpected errors
            if (class_exists('AppErrorHandler')) {
                AppErrorHandler::logException($e, ['context' => 'DashboardController::today() - unexpected']);
            } else {
                error_log("DashboardController::today() - Unexpected error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            // ROUND 31: Show error page with 200 status (not 500) to prevent breaking user flow
            // ===== STAGE4_FIX: Show minimal dashboard instead of error page =====
            try {
                // Try to show minimal dashboard with safe defaults
                $minimalData = [
                    'title' => 'Ana Sayfa',
                    'stats' => [
                        'today_jobs' => 0,
                        'active_customers' => 0,
                        'week_income' => 0,
                        'pending_appointments' => 0
                    ],
                    'todayJobs' => [],
                    'recentActivities' => [],
                    'upcomingAppointments' => [],
                    'weeklyIncomeTrend' => [],
                    'recurringStats' => [],
                    'notifications' => [],
                    'companyContext' => [],
                    'error_message' => 'Veriler yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.'
                ];
                echo View::renderWithLayout('dashboard', $minimalData);
            } catch (Throwable $e2) {
                // Ultimate fallback: show error page
                View::error('Dashboard yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.', 200, $e->getMessage());
            }
            // ===== STAGE4_FIX END =====
        }
    }
    
    /**
     * Build dashboard data (extracted for cache remember pattern)
     * ROUND 31: Enhanced error handling for first-load scenario
     */
    private function buildDashboardData(string $today): array
    {
        // ===== LOGIN_500_PATHC: Log dashboard data build start =====
        if (class_exists('PathCLogger')) {
            PathCLogger::log('PATHC_DASHBOARD_TODAY_ENTER', ['step' => 'buildDashboardData']);
        }
        // ===== LOGIN_500_PATHC END =====
        
        // ROUND 31: Initialize with safe defaults
        $data = [
            'title' => 'Ana Sayfa',
            'stats' => [
                'today_jobs' => 0,
                'active_customers' => 0,
                'week_income' => 0,
                'pending_appointments' => 0
            ],
            'todayJobs' => [],
            'recentActivities' => [],
            'upcomingAppointments' => [],
            'weeklyIncomeTrend' => [],
            'recurringStats' => [],
            'notifications' => [],
            'companyContext' => []
        ];
        
        try {
            $db = Database::getInstance();
        } catch (Throwable $e) {
            error_log("DashboardController::buildDashboardData() - Database::getInstance() error: " . $e->getMessage());
            return $data; // Return safe defaults
        }
        
        // ROUND 31: Get company context with error handling
        try {
            $companyContext = $this->getCurrentCompanyContext();
            $data['companyContext'] = $companyContext ?? [];
        } catch (Throwable $e) {
            error_log("DashboardController::buildDashboardData() - getCurrentCompanyContext() error: " . $e->getMessage());
            $data['companyContext'] = [];
        }
        
        // Get today's stats
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        
        // ROUND 31: Get today's jobs with error handling
        try {
            $todayWhere = $this->scopeToCompany('WHERE DATE(j.start_at) = ?', 'j');
            $todayJobs = $db->fetchAll("
                SELECT j.*, c.name as customer_name, s.name as service_name
                FROM jobs j 
                LEFT JOIN customers c ON j.customer_id = c.id 
                LEFT JOIN services s ON j.service_id = s.id
                {$todayWhere}
                ORDER BY j.start_at ASC
                LIMIT " . AppConstants::DASHBOARD_RECENT_ITEMS * 2 . "
            ", [$today]);
            $data['todayJobs'] = is_array($todayJobs) ? $todayJobs : [];
        } catch (Throwable $e) {
            error_log("DashboardController::buildDashboardData() - todayJobs query error: " . $e->getMessage());
            $data['todayJobs'] = [];
        }
        
        // ROUND 31: Get stats with individual error handling
        // Ensure stats array is initialized
        if (!isset($data['stats'])) {
            $data['stats'] = [];
        }
        
        try {
            $data['stats']['today_jobs'] = count($data['todayJobs'] ?? []);
        } catch (Throwable $e) {
            $data['stats']['today_jobs'] = 0;
        }
        
        try {
            $data['stats']['active_customers'] = $this->getActiveCustomers($monthStart);
        } catch (Throwable $e) {
            error_log("DashboardController::buildDashboardData() - getActiveCustomers() error: " . $e->getMessage());
            $data['stats']['active_customers'] = 0;
        }
        
        try {
            // ===== LOGIN_500_PATHC: Log step =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('PATHC_DASHBOARD_TODAY_STEP', ['step' => 'load_stats', 'function' => 'getWeekIncome']);
            }
            // ===== LOGIN_500_PATHC END =====
            $data['stats']['week_income'] = $this->getWeekIncome($weekStart, $weekEnd);
        } catch (Throwable $e) {
            // ===== LOGIN_500_PATHC: Log exception =====
            if (class_exists('PathCLogger')) {
                PathCLogger::logException('PATHC_DASHBOARD_TODAY_STEP_EXCEPTION', $e, ['step' => 'load_stats', 'function' => 'getWeekIncome']);
            }
            // ===== LOGIN_500_PATHC END =====
            error_log("DashboardController::buildDashboardData() - getWeekIncome() error: " . $e->getMessage());
            $data['stats']['week_income'] = 0;
        }
        
        try {
            $data['stats']['pending_appointments'] = $this->getPendingAppointments();
        } catch (Throwable $e) {
            error_log("DashboardController::buildDashboardData() - getPendingAppointments() error: " . $e->getMessage());
            $data['stats']['pending_appointments'] = 0;
        }
        
        // ROUND 31: Get recent activities with error handling
        try {
            // ===== LOGIN_500_PATHC: Log step =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('PATHC_DASHBOARD_TODAY_STEP', ['step' => 'load_recent_payments', 'function' => 'getRecentActivities']);
            }
            // ===== LOGIN_500_PATHC END =====
            $recentActivities = $this->getRecentActivities();
            $data['recentActivities'] = is_array($recentActivities) ? $recentActivities : [];
        } catch (Throwable $e) {
            // ===== LOGIN_500_PATHC: Log exception =====
            if (class_exists('PathCLogger')) {
                PathCLogger::logException('PATHC_DASHBOARD_TODAY_STEP_EXCEPTION', $e, ['step' => 'load_recent_payments', 'function' => 'getRecentActivities']);
            }
            // ===== LOGIN_500_PATHC END =====
            error_log("DashboardController::buildDashboardData() - getRecentActivities() error: " . $e->getMessage());
            $data['recentActivities'] = [];
        }
        
        // ROUND 31: Get upcoming appointments with error handling
        try {
            $upcomingAppointments = $this->getUpcomingAppointments();
            $data['upcomingAppointments'] = is_array($upcomingAppointments) ? $upcomingAppointments : [];
        } catch (Throwable $e) {
            error_log("DashboardController::buildDashboardData() - getUpcomingAppointments() error: " . $e->getMessage());
            $data['upcomingAppointments'] = [];
        }
        
        // ROUND 31: Get weekly income trend with error handling
        try {
            // ===== LOGIN_500_PATHC: Log step =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('PATHC_DASHBOARD_TODAY_STEP', ['step' => 'load_week_trend', 'function' => 'getWeeklyIncomeTrend']);
            }
            // ===== LOGIN_500_PATHC END =====
            $weeklyIncomeTrend = $this->getWeeklyIncomeTrend();
            $data['weeklyIncomeTrend'] = is_array($weeklyIncomeTrend) ? $weeklyIncomeTrend : [];
        } catch (Throwable $e) {
            // ===== LOGIN_500_PATHC: Log exception =====
            if (class_exists('PathCLogger')) {
                PathCLogger::logException('PATHC_DASHBOARD_TODAY_STEP_EXCEPTION', $e, ['step' => 'load_week_trend', 'function' => 'getWeeklyIncomeTrend']);
            }
            // ===== LOGIN_500_PATHC END =====
            error_log("DashboardController::buildDashboardData() - getWeeklyIncomeTrend() error: " . $e->getMessage());
            $data['weeklyIncomeTrend'] = [];
        }
        
        // ROUND 31: Get recurring jobs stats with error handling
        try {
            $recurringStats = $this->getRecurringJobsStats();
            $data['recurringStats'] = is_array($recurringStats) ? $recurringStats : [];
        } catch (Throwable $e) {
            error_log("DashboardController::buildDashboardData() - getRecurringJobsStats() error: " . $e->getMessage());
            $data['recurringStats'] = [];
        }
        
        // ROUND 31: Get notifications with error handling
        try {
            if (class_exists('NotificationService') && method_exists('NotificationService', 'getHeaderNotifications')) {
                $notifications = NotificationService::getHeaderNotifications(10);
                $data['notifications'] = is_array($notifications) ? $notifications : [];
            } else {
                $data['notifications'] = [];
            }
        } catch (Throwable $e) {
            if (APP_DEBUG) {
                error_log("NotificationService error: " . $e->getMessage());
            }
            $data['notifications'] = [];
        }
        
        return $data;
    }
    
    /**
     * Clear dashboard cache manually (for admin actions)
     */
    public function clearCache()
    {
        Auth::require();
        
        $today = date('Y-m-d');
        Cache::delete("dashboard:today:{$today}");
        
        Utils::flash('success', 'Dashboard cache temizlendi.');
        redirect(base_url('/'));
    }
    
    /**
     * Get total users count
     */
    private function getTotalUsers(): int
    {
        try {
            $db = Database::getInstance();
            $result = $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get active jobs count
     */
    private function getActiveJobs(): int
    {
        try {
            $db = Database::getInstance();
            $result = $db->fetch("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'");
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get pending tasks count
     */
    private function getPendingTasks(): int
    {
        try {
            $db = Database::getInstance();
            $result = $db->fetch("SELECT COUNT(*) as count FROM queue_jobs WHERE reserved_at IS NULL");
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get alerts count
     */
    private function getAlerts(): int
    {
        try {
            $db = Database::getInstance();
            $result = $db->fetch("SELECT COUNT(*) as count FROM audit_logs WHERE event_type = 'ERROR' AND timestamp >= datetime('now', '-1 day')");
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get active customers count for the month
     */
    private function getActiveCustomers(string $monthStart): int
    {
        try {
            $db = Database::getInstance();
            $where = $this->scopeToCompany("WHERE DATE(j.start_at) >= ? AND j.status IN ('SCHEDULED', 'DONE')", 'j');
            $result = $db->fetch("
                SELECT COUNT(DISTINCT j.customer_id) as count 
                FROM jobs j
                {$where}
            ", [$monthStart]);
            
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Dashboard getActiveCustomers error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get week income
     */
    private function getWeekIncome(string $weekStart, string $weekEnd): float
    {
        try {
            $db = Database::getInstance();
            
            // money_entries tablosundan gelirleri çek
            $whereIncome = $this->scopeToCompany("WHERE me.kind = 'INCOME' AND DATE(me.date) BETWEEN ? AND ?", 'me');
            $result = $db->fetch("
                SELECT COALESCE(SUM(me.amount), 0) as total 
                FROM money_entries me
                {$whereIncome}
            ", [$weekStart, $weekEnd]);
            
            $incomeFromMoneyEntries = (float)($result['total'] ?? 0);
            
            // job_payments tablosundan da ödemeleri çek
            // ===== STAGE3_FIX: Use JOIN with jobs.company_id instead of jp.company_id =====
            $companyId = Auth::companyId() ?? 1;
            $wherePayments = "WHERE DATE(jp.created_at) BETWEEN ? AND ? AND j.company_id = ?";
            
            // ===== LOGIN_500_PATHC: Log DB query start =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('PATHC_DB_QUERY_START', [
                    'function' => 'getWeekIncome',
                    'table' => 'job_payments',
                    'sql' => "SELECT COALESCE(SUM(jp.amount), 0) as total FROM job_payments jp LEFT JOIN jobs j ON jp.job_id = j.id {$wherePayments}",
                    'params' => json_encode([$weekStart, $weekEnd, $companyId]),
                    'company_id' => $companyId
                ]);
            }
            // ===== LOGIN_500_PATHC END =====
            
            try {
                $result = $db->fetch("
                    SELECT COALESCE(SUM(jp.amount), 0) as total 
                    FROM job_payments jp
                    LEFT JOIN jobs j ON jp.job_id = j.id
                    {$wherePayments}
                ", [$weekStart, $weekEnd, $companyId]);
                
                // ===== LOGIN_500_PATHC: Log DB query end =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::log('PATHC_DB_QUERY_END', [
                        'function' => 'getWeekIncome',
                        'table' => 'job_payments',
                        'success' => '1'
                    ]);
                }
                // ===== LOGIN_500_PATHC END =====
            } catch (\PDOException $e) {
                // ===== LOGIN_500_PATHC: Log DB query exception =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHC_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getWeekIncome',
                        'table' => 'job_payments',
                        'sql' => "SELECT COALESCE(SUM(jp.amount), 0) as total FROM job_payments jp LEFT JOIN jobs j ON jp.job_id = j.id {$wherePayments}"
                    ]);
                }
                // ===== LOGIN_500_PATHC END =====
                
                // ===== STAGE3_FIX: Fallback - try without company_id filter =====
                if (strpos($e->getMessage(), 'no such column') !== false || strpos($e->getMessage(), 'company_id') !== false) {
                    if (class_exists('PathCLogger')) {
                        PathCLogger::log('PATHC_DB_QUERY_FALLBACK_NO_COMPANY_COLUMN', [
                            'function' => 'getWeekIncome',
                            'table' => 'job_payments'
                        ]);
                    }
                    try {
                        $result = $db->fetch("
                            SELECT COALESCE(SUM(jp.amount), 0) as total 
                            FROM job_payments jp
                            WHERE DATE(jp.created_at) BETWEEN ? AND ?
                        ", [$weekStart, $weekEnd]);
                    } catch (\PDOException $e2) {
                        // Ultimate fallback: return 0
                        $result = ['total' => 0];
                    }
                } else {
                    throw $e;
                }
            }
            
            $incomeFromJobPayments = (float)($result['total'] ?? 0);
            
            // payments tablosundan tamamlanmış ödemeleri çek (tablo yoksa atla)
            // ===== PATHD_FIX: Use JOIN with jobs.company_id instead of p.company_id =====
            $incomeFromPayments = 0.0;
            try {
                $companyId = Auth::companyId() ?? 1;
                // JOIN ile jobs.company_id kullan (payments.job_id → jobs.id → jobs.company_id)
                // Eğer job_id NULL ise, customers.company_id kullan (payments.customer_id → customers.id → customers.company_id)
                $result = $db->fetch("
                    SELECT COALESCE(SUM(p.amount), 0) as total 
                    FROM payments p
                    LEFT JOIN jobs j ON p.job_id = j.id
                    LEFT JOIN customers c ON COALESCE(p.customer_id, j.customer_id) = c.id
                    WHERE p.status = 'completed' 
                      AND DATE(p.created_at) BETWEEN ? AND ? 
                      AND (j.company_id = ? OR (j.company_id IS NULL AND c.company_id = ?))
                ", [$weekStart, $weekEnd, $companyId, $companyId]);
                $incomeFromPayments = (float)($result['total'] ?? 0);
            } catch (\PDOException $e) {
                // ===== PATHD_STAGE2: Log exception and try fallback =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHD_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getWeekIncome',
                        'table' => 'payments',
                        'sql' => 'payments with JOIN'
                    ]);
                }
                error_log("Dashboard getWeekIncome payments error: " . $e->getMessage());
                
                // Fallback: try without company_id filter
                if (strpos($e->getMessage(), 'no such column') !== false || strpos($e->getMessage(), 'company_id') !== false) {
                    if (class_exists('PathCLogger')) {
                        PathCLogger::log('PATHD_DB_QUERY_FALLBACK_NO_COMPANY_COLUMN', [
                            'function' => 'getWeekIncome',
                            'table' => 'payments'
                        ]);
                    }
                    try {
                        $result = $db->fetch("
                            SELECT COALESCE(SUM(p.amount), 0) as total 
                            FROM payments p
                            WHERE p.status = 'completed' 
                              AND DATE(p.created_at) BETWEEN ? AND ?
                        ", [$weekStart, $weekEnd]);
                        $incomeFromPayments = (float)($result['total'] ?? 0);
                    } catch (\PDOException $e2) {
                        // Ultimate fallback: return 0
                        $incomeFromPayments = 0.0;
                    }
                } else {
                    $incomeFromPayments = 0.0; // Safe default
                }
            } catch (Exception $e) {
                // payments tablosu yoksa veya sorgu başarısız olursa görmezden gel
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHD_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getWeekIncome',
                        'table' => 'payments',
                        'sql' => 'payments with JOIN'
                    ]);
                }
                $incomeFromPayments = 0.0; // Safe default
            }
            // ===== PATHD_FIX END =====
            
            // Tüm kaynakları topla (duplicate'leri önlemek için max kullanma, topla)
            return $incomeFromMoneyEntries + $incomeFromJobPayments + $incomeFromPayments;
        } catch (Exception $e) {
            error_log("Dashboard getWeekIncome error: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Get pending appointments count
     */
    private function getPendingAppointments(): int
    {
        try {
            $db = Database::getInstance();
            $today = date('Y-m-d');
            
            $where = $this->scopeToCompany("
                WHERE a.status IN ('pending', 'SCHEDULED')
                AND (
                    (a.appointment_date IS NOT NULL AND a.appointment_date >= ?) OR
                    (a.start_at IS NOT NULL AND DATE(a.start_at) >= ?)
                )
            ", 'a');
            $result = $db->fetch("
                SELECT COUNT(*) as count 
                FROM appointments a
                {$where}
            ", [$today, $today]);
            
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            // start_at kolonu yoksa sadece appointment_date kullan
            try {
                $where = $this->scopeToCompany("
                    WHERE a.status IN ('pending', 'SCHEDULED')
                    AND a.appointment_date >= ?
                ", 'a');
                $result = $db->fetch("
                    SELECT COUNT(*) as count 
                    FROM appointments a
                    {$where}
                ", [$today]);
                
                return (int)($result['count'] ?? 0);
            } catch (Exception $e2) {
                error_log("Dashboard getPendingAppointments error: " . $e2->getMessage());
                return 0;
            }
        }
    }
    
    /**
     * Get recurring jobs statistics and upcoming occurrences
     */
    private function getRecurringJobsStats(): array
    {
        try {
            $db = Database::getInstance();
            $today = date('Y-m-d');
            
            // Get active recurring jobs count
            $activeRecurring = $db->fetch("
                SELECT COUNT(*) as count 
                FROM recurring_jobs rj
                " . $this->scopeToCompany("WHERE rj.status = 'ACTIVE'", 'rj')
            )['count'] ?? 0;
            
            // Get jobs created from recurring jobs this month
            $thisMonthStart = date('Y-m-01');
            $recurringJobWhere = $this->scopeToCompany("
                WHERE j.recurring_job_id IS NOT NULL 
                AND DATE(j.created_at) >= ?
            ", 'j');
            $thisMonthRecurringJobs = $db->fetch("
                SELECT COUNT(*) as count 
                FROM jobs j
                {$recurringJobWhere}
            ", [$thisMonthStart])['count'] ?? 0;
            
            // Get upcoming occurrences (next 7 days) that are not yet generated
            $nextWeek = date('Y-m-d', strtotime('+7 days'));
            $occurrenceWhere = $this->scopeToCompany("
                WHERE ro.status = 'PLANNED' 
                AND ro.date >= ? 
                AND ro.date <= ?
                AND rj.status = 'ACTIVE'
            ", 'rj');
            $upcomingOccurrences = $db->fetchAll("
                SELECT 
                    ro.*,
                    rj.customer_id,
                    c.name as customer_name,
                    s.name as service_name
                FROM recurring_job_occurrences ro
                INNER JOIN recurring_jobs rj ON ro.recurring_job_id = rj.id
                LEFT JOIN customers c ON rj.customer_id = c.id
                LEFT JOIN services s ON rj.service_id = s.id
                {$occurrenceWhere}
                ORDER BY ro.date, ro.start_at
                LIMIT 5
            ", [$today, $nextWeek]);
            
            // Count pending occurrences
            $pendingWhere = $this->scopeToCompany("
                WHERE ro.status = 'PLANNED' 
                AND ro.date >= ?
                AND rj.status = 'ACTIVE'
            ", 'rj');
            $pendingOccurrences = $db->fetch("
                SELECT COUNT(*) as count 
                FROM recurring_job_occurrences ro
                INNER JOIN recurring_jobs rj ON ro.recurring_job_id = rj.id
                {$pendingWhere}
            ", [$today])['count'] ?? 0;
            
            return [
                'active_count' => (int)$activeRecurring,
                'this_month_jobs' => (int)$thisMonthRecurringJobs,
                'upcoming_occurrences' => $upcomingOccurrences,
                'pending_occurrences_count' => (int)$pendingOccurrences
            ];
        } catch (Exception $e) {
            // Phase 3.4: Use Logger instead of error_log
            if (class_exists('Logger')) {
                Logger::error("Dashboard getRecurringJobsStats error: " . $e->getMessage(), ['exception' => $e]);
            } else {
                error_log("Dashboard getRecurringJobsStats error: " . $e->getMessage());
            }
            return [
                'active_count' => 0,
                'this_month_jobs' => 0,
                'upcoming_occurrences' => [],
                'pending_occurrences_count' => 0
            ];
        }
    }
    
    /**
     * Get upcoming appointments (next 3 days)
     */
    private function getUpcomingAppointments(): array
    {
        try {
            $db = Database::getInstance();
            $today = date('Y-m-d');
            $threeDaysLater = date('Y-m-d', strtotime('+3 days'));
            
            // Önce start_at ile deneyelim
            try {
                $where = $this->scopeToCompany("
                    WHERE a.status IN ('pending', 'SCHEDULED', 'CONFIRMED')
                    AND (
                        (a.appointment_date IS NOT NULL AND a.appointment_date BETWEEN ? AND ?) OR
                        (a.start_at IS NOT NULL AND DATE(a.start_at) BETWEEN ? AND ?)
                    )
                ", 'a');
                $appointments = $db->fetchAll("
                    SELECT a.*, c.name as customer_name, c.phone as customer_phone
                    FROM appointments a
                    LEFT JOIN customers c ON a.customer_id = c.id
                    {$where}
                    ORDER BY 
                        COALESCE(a.appointment_date, DATE(a.start_at)) ASC,
                        COALESCE(a.start_time, TIME(a.start_at)) ASC
                    LIMIT 5
                ", [$today, $threeDaysLater, $today, $threeDaysLater]);
            } catch (Exception $e) {
                // start_at yoksa sadece appointment_date kullan
                $where = $this->scopeToCompany("
                    WHERE a.status IN ('pending', 'SCHEDULED', 'CONFIRMED')
                    AND a.appointment_date BETWEEN ? AND ?
                ", 'a');
                $appointments = $db->fetchAll("
                    SELECT a.*, c.name as customer_name, c.phone as customer_phone
                    FROM appointments a
                    LEFT JOIN customers c ON a.customer_id = c.id
                    {$where}
                    ORDER BY a.appointment_date ASC, a.start_time ASC
                    LIMIT 5
                ", [$today, $threeDaysLater]);
            }
            
            return $appointments ?? [];
        } catch (Exception $e) {
            error_log("Dashboard getUpcomingAppointments error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get weekly income trend (last 7 days)
     */
    private function getWeeklyIncomeTrend(): array
    {
        try {
            $db = Database::getInstance();
            $today = date('Y-m-d');
            $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
            
            $trend = [];
            
            // Son 7 günün gelirlerini gün gün çek
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $dayName = date('D', strtotime("-$i days"));
                
                // money_entries'den gelir
                $whereMoney = $this->scopeToCompany("WHERE me.kind = 'INCOME' AND DATE(me.date) = ?", 'me');
                $result = $db->fetch("
                    SELECT COALESCE(SUM(me.amount), 0) as total 
                    FROM money_entries me
                    {$whereMoney}
                ", [$date]);
                $income1 = (float)($result['total'] ?? 0);
                
            // job_payments'den gelir
            // ===== STAGE3_FIX: Use JOIN with jobs.company_id instead of jp.company_id =====
            $companyId = Auth::companyId() ?? 1;
            $whereJobPayments = "WHERE DATE(jp.created_at) = ? AND j.company_id = ?";
            
            // ===== LOGIN_500_PATHC: Log DB query start =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('PATHC_DB_QUERY_START', [
                    'function' => 'getWeeklyIncomeTrend',
                    'table' => 'job_payments',
                    'sql' => "SELECT COALESCE(SUM(jp.amount), 0) as total FROM job_payments jp LEFT JOIN jobs j ON jp.job_id = j.id {$whereJobPayments}",
                    'params' => json_encode([$date, $companyId]),
                    'company_id' => $companyId
                ]);
            }
            // ===== LOGIN_500_PATHC END =====
            
            try {
                $result = $db->fetch("
                    SELECT COALESCE(SUM(jp.amount), 0) as total 
                    FROM job_payments jp
                    LEFT JOIN jobs j ON jp.job_id = j.id
                    {$whereJobPayments}
                ", [$date, $companyId]);
                
                // ===== LOGIN_500_PATHC: Log DB query end =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::log('PATHC_DB_QUERY_END', [
                        'function' => 'getWeeklyIncomeTrend',
                        'table' => 'job_payments',
                        'success' => '1'
                    ]);
                }
                // ===== LOGIN_500_PATHC END =====
                
                $income2 = (float)($result['total'] ?? 0);
            } catch (\PDOException $e) {
                // ===== LOGIN_500_PATHC: Log DB query exception =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHC_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getWeeklyIncomeTrend',
                        'table' => 'job_payments',
                        'sql' => "SELECT COALESCE(SUM(jp.amount), 0) as total FROM job_payments jp LEFT JOIN jobs j ON jp.job_id = j.id {$whereJobPayments}"
                    ]);
                }
                // ===== LOGIN_500_PATHC END =====
                
                // ===== STAGE3_FIX: Fallback - try without company_id filter =====
                if (strpos($e->getMessage(), 'no such column') !== false || strpos($e->getMessage(), 'company_id') !== false) {
                    if (class_exists('PathCLogger')) {
                        PathCLogger::log('PATHC_DB_QUERY_FALLBACK_NO_COMPANY_COLUMN', [
                            'function' => 'getWeeklyIncomeTrend',
                            'table' => 'job_payments'
                        ]);
                    }
                    try {
                        $result = $db->fetch("
                            SELECT COALESCE(SUM(jp.amount), 0) as total 
                            FROM job_payments jp
                            WHERE DATE(jp.created_at) = ?
                        ", [$date]);
                        $income2 = (float)($result['total'] ?? 0);
                    } catch (\PDOException $e2) {
                        $income2 = 0.0; // Ultimate fallback
                    }
                } else {
                    $income2 = 0.0; // Fallback
                }
            }
                
                // payments'den gelir (tablo yoksa atla)
                // ===== PATHD_FIX: Use JOIN with jobs.company_id instead of p.company_id =====
                $income3 = 0.0;
                try {
                    $companyId = Auth::companyId() ?? 1;
                    // JOIN ile jobs.company_id kullan (payments.job_id → jobs.id → jobs.company_id)
                    // Eğer job_id NULL ise, customers.company_id kullan (payments.customer_id → customers.id → customers.company_id)
                    $result = $db->fetch("
                        SELECT COALESCE(SUM(p.amount), 0) as total 
                        FROM payments p
                        LEFT JOIN jobs j ON p.job_id = j.id
                        LEFT JOIN customers c ON COALESCE(p.customer_id, j.customer_id) = c.id
                        WHERE p.status = 'completed' 
                          AND DATE(p.created_at) = ? 
                          AND (j.company_id = ? OR (j.company_id IS NULL AND c.company_id = ?))
                    ", [$date, $companyId, $companyId]);
                    $income3 = (float)($result['total'] ?? 0);
                } catch (\PDOException $e) {
                    // ===== PATHD_STAGE2: Log exception and try fallback =====
                    if (class_exists('PathCLogger')) {
                        PathCLogger::logException('PATHD_DB_QUERY_EXCEPTION', $e, [
                            'function' => 'getWeeklyIncomeTrend',
                            'table' => 'payments',
                            'sql' => 'payments with JOIN',
                            'date' => $date
                        ]);
                    }
                    
                    // Fallback: try without company_id filter
                    if (strpos($e->getMessage(), 'no such column') !== false || strpos($e->getMessage(), 'company_id') !== false) {
                        if (class_exists('PathCLogger')) {
                            PathCLogger::log('PATHD_DB_QUERY_FALLBACK_NO_COMPANY_COLUMN', [
                                'function' => 'getWeeklyIncomeTrend',
                                'table' => 'payments',
                                'date' => $date
                            ]);
                        }
                        try {
                            $result = $db->fetch("
                                SELECT COALESCE(SUM(p.amount), 0) as total 
                                FROM payments p
                                WHERE p.status = 'completed' 
                                  AND DATE(p.created_at) = ?
                            ", [$date]);
                            $income3 = (float)($result['total'] ?? 0);
                        } catch (\PDOException $e2) {
                            // Ultimate fallback: return 0
                            $income3 = 0.0;
                        }
                    } else {
                        $income3 = 0.0; // Safe default
                    }
                } catch (Exception $e) {
                    // payments tablosu yoksa veya sorgu başarısız olursa görmezden gel
                    if (class_exists('PathCLogger')) {
                        PathCLogger::logException('PATHD_DB_QUERY_EXCEPTION', $e, [
                            'function' => 'getWeeklyIncomeTrend',
                            'table' => 'payments',
                            'sql' => 'payments with JOIN',
                            'date' => $date
                        ]);
                    }
                    $income3 = 0.0; // Safe default
                }
                // ===== PATHD_FIX END =====
                
                // Tüm kaynakları topla
                $totalIncome = $income1 + $income2 + $income3;
                
                $trend[] = [
                    'date' => $date,
                    'day' => $dayName === 'Mon' ? 'Pzt' : ($dayName === 'Tue' ? 'Sal' : ($dayName === 'Wed' ? 'Çar' : ($dayName === 'Thu' ? 'Per' : ($dayName === 'Fri' ? 'Cum' : ($dayName === 'Sat' ? 'Cmt' : 'Paz'))))),
                    'income' => $totalIncome
                ];
            }
            
            return $trend;
        } catch (Exception $e) {
            error_log("Dashboard getWeeklyIncomeTrend error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent activities for dashboard
     */
    private function getRecentActivities(): array
    {
        $activities = [];
        $db = Database::getInstance();
        
        try {
            // Get recent jobs (created in last 24 hours)
            $newJobWhere = $this->scopeToCompany("WHERE j.created_at >= datetime('now', '-24 hours')", 'j');
            $newJobs = $db->fetchAll("
                SELECT j.*, c.name as customer_name, 'job_created' as type
                FROM jobs j
                LEFT JOIN customers c ON j.customer_id = c.id
                {$newJobWhere}
                ORDER BY j.created_at DESC
                LIMIT " . AppConstants::DASHBOARD_RECENT_ITEMS . "
            ");
            
            // Phase 3.2: Optimize with array_map
            $newJobActivities = array_map(function($job) {
                return [
                    'type' => 'job_created',
                    'icon' => 'fas fa-plus-circle',
                    'color' => 'green',
                    'message' => 'Yeni iş eklendi: ' . ($job['note'] ?? 'İş Adı Yok'),
                    'customer' => $job['customer_name'] ?? 'Bilinmeyen',
                    'time' => $job['created_at'],
                    'time_text' => $this->timeAgo($job['created_at'])
                ];
            }, $newJobs);
            $activities = array_merge($activities, $newJobActivities);
            
            // Get updated jobs (updated in last 24 hours, but not just created)
            $updatedWhere = $this->scopeToCompany("
                WHERE j.updated_at >= datetime('now', '-24 hours')
                AND j.updated_at != j.created_at
                AND j.created_at < datetime('now', '-1 hour')
            ", 'j');
            $updatedJobs = $db->fetchAll("
                SELECT j.*, c.name as customer_name, 'job_updated' as type
                FROM jobs j
                LEFT JOIN customers c ON j.customer_id = c.id
                {$updatedWhere}
                ORDER BY j.updated_at DESC
                LIMIT 10
            ");
            
            // Phase 3.2: Optimize with array_map
            $updatedJobActivities = array_map(function($job) {
                return [
                    'type' => 'job_updated',
                    'icon' => 'fas fa-edit',
                    'color' => 'blue',
                    'message' => 'İş güncellendi: ' . ($job['note'] ?? 'İş Adı Yok'),
                    'customer' => $job['customer_name'] ?? 'Bilinmeyen',
                    'time' => $job['updated_at'],
                    'time_text' => $this->timeAgo($job['updated_at'])
                ];
            }, $updatedJobs);
            $activities = array_merge($activities, $updatedJobActivities);
            
            // Get recent job payments (last 24 hours)
            // ===== STAGE3_FIX: Use JOIN with jobs.company_id instead of jp.company_id =====
            $companyId = Auth::companyId() ?? 1;
            $jobPaymentWhere = "WHERE jp.created_at >= datetime('now', '-24 hours') AND j.company_id = ?";
            
            // ===== LOGIN_500_PATHC: Log DB query start =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('PATHC_DB_QUERY_START', [
                    'function' => 'getRecentActivities',
                    'table' => 'job_payments',
                    'sql' => "SELECT jp.*, j.note as job_note, c.name as customer_name, 'payment' as type FROM job_payments jp LEFT JOIN jobs j ON jp.job_id = j.id LEFT JOIN customers c ON j.customer_id = c.id {$jobPaymentWhere} ORDER BY jp.created_at DESC LIMIT 10",
                    'params' => json_encode([$companyId]),
                    'company_id' => $companyId
                ]);
            }
            // ===== LOGIN_500_PATHC END =====
            
            try {
                $jobPayments = $db->fetchAll("
                    SELECT jp.*, j.note as job_note, c.name as customer_name, 'payment' as type
                    FROM job_payments jp
                    LEFT JOIN jobs j ON jp.job_id = j.id
                    LEFT JOIN customers c ON j.customer_id = c.id
                    {$jobPaymentWhere}
                    ORDER BY jp.created_at DESC
                    LIMIT " . AppConstants::DASHBOARD_RECENT_ITEMS . "
                ", [$companyId]);
                
                // ===== LOGIN_500_PATHC: Log DB query end =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::log('PATHC_DB_QUERY_END', [
                        'function' => 'getRecentActivities',
                        'table' => 'job_payments',
                        'success' => '1',
                        'row_count' => count($jobPayments)
                    ]);
                }
                // ===== LOGIN_500_PATHC END =====
            } catch (\PDOException $e) {
                // ===== LOGIN_500_PATHC: Log DB query exception =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHC_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getRecentActivities',
                        'table' => 'job_payments',
                        'sql' => "SELECT jp.*, j.note as job_note, c.name as customer_name, 'payment' as type FROM job_payments jp LEFT JOIN jobs j ON jp.job_id = j.id LEFT JOIN customers c ON j.customer_id = c.id {$jobPaymentWhere} ORDER BY jp.created_at DESC LIMIT 10"
                    ]);
                }
                // ===== LOGIN_500_PATHC END =====
                
                // ===== STAGE3_FIX: Fallback - try without company_id filter =====
                if (strpos($e->getMessage(), 'no such column') !== false || strpos($e->getMessage(), 'company_id') !== false) {
                    if (class_exists('PathCLogger')) {
                        PathCLogger::log('PATHC_DB_QUERY_FALLBACK_NO_COMPANY_COLUMN', [
                            'function' => 'getRecentActivities',
                            'table' => 'job_payments'
                        ]);
                    }
                    try {
                        $jobPayments = $db->fetchAll("
                            SELECT jp.*, j.note as job_note, c.name as customer_name, 'payment' as type
                            FROM job_payments jp
                            LEFT JOIN jobs j ON jp.job_id = j.id
                            LEFT JOIN customers c ON j.customer_id = c.id
                    WHERE jp.created_at >= datetime('now', '-24 hours')
                    ORDER BY jp.created_at DESC
                    LIMIT " . AppConstants::DASHBOARD_RECENT_ITEMS . "
                        ");
                    } catch (\PDOException $e2) {
                        $jobPayments = []; // Ultimate fallback
                    }
                } else {
                    $jobPayments = []; // Fallback
                }
            }
            
            // Phase 3.2: Optimize with array_map
            $paymentActivities = array_map(function($payment) {
                return [
                    'type' => 'payment',
                    'icon' => 'fas fa-money-bill-wave',
                    'color' => 'yellow',
                    'message' => 'Ödeme eklendi: ₺' . number_format($payment['amount'], 0, ',', '.') . ' - ' . ($payment['job_note'] ?? 'İş'),
                    'customer' => $payment['customer_name'] ?? 'Bilinmeyen',
                    'time' => $payment['created_at'],
                    'time_text' => $this->timeAgo($payment['created_at'])
                ];
            }, $jobPayments);
            $activities = array_merge($activities, $paymentActivities);
            
            // Get job status changes (when status changes to DONE)
            $completedWhere = $this->scopeToCompany("
                WHERE j.status = 'DONE'
                AND j.updated_at >= datetime('now', '-24 hours')
            ", 'j');
            $completedJobs = $db->fetchAll("
                SELECT j.*, c.name as customer_name, 'job_completed' as type
                FROM jobs j
                LEFT JOIN customers c ON j.customer_id = c.id
                {$completedWhere}
                ORDER BY j.updated_at DESC
                LIMIT " . AppConstants::DASHBOARD_RECENT_ITEMS . "
            ");
            
            // Phase 3.2: Optimize with array_map
            $completedJobActivities = array_map(function($job) {
                return [
                    'type' => 'job_completed',
                    'icon' => 'fas fa-check-circle',
                    'color' => 'green',
                    'message' => 'İş tamamlandı: ' . ($job['note'] ?? 'İş Adı Yok'),
                    'customer' => $job['customer_name'] ?? 'Bilinmeyen',
                    'time' => $job['updated_at'],
                    'time_text' => $this->timeAgo($job['updated_at'])
                ];
            }, $completedJobs);
            $activities = array_merge($activities, $completedJobActivities);
            
            // Get new appointments created (last 24 hours)
            $newAppointmentWhere = $this->scopeToCompany("WHERE a.created_at >= datetime('now', '-24 hours')", 'a');
            $newAppointments = $db->fetchAll("
                SELECT a.*, c.name as customer_name, 'appointment_created' as type
                FROM appointments a
                LEFT JOIN customers c ON a.customer_id = c.id
                {$newAppointmentWhere}
                ORDER BY a.created_at DESC
                LIMIT " . AppConstants::DASHBOARD_RECENT_ITEMS . "
            ");
            
            foreach ($newAppointments as $appointment) {
                $activities[] = [
                    'type' => 'appointment_created',
                    'icon' => 'fas fa-calendar-plus',
                    'color' => 'purple',
                    'message' => 'Yeni randevu eklendi: ' . ($appointment['title'] ?? 'Başlıksız'),
                    'customer' => $appointment['customer_name'] ?? 'Bilinmeyen',
                    'time' => $appointment['created_at'],
                    'time_text' => $this->timeAgo($appointment['created_at'])
                ];
            }
            
            // Get updated appointments (last 24 hours, but not just created)
            $updatedAppointmentWhere = $this->scopeToCompany("
                WHERE a.updated_at >= datetime('now', '-24 hours')
                AND a.updated_at != a.created_at
                AND a.created_at < datetime('now', '-1 hour')
            ", 'a');
            $updatedAppointments = $db->fetchAll("
                SELECT a.*, c.name as customer_name, 'appointment_updated' as type
                FROM appointments a
                LEFT JOIN customers c ON a.customer_id = c.id
                {$updatedAppointmentWhere}
                ORDER BY a.updated_at DESC
                LIMIT " . AppConstants::DASHBOARD_RECENT_ITEMS . "
            ");
            
            foreach ($updatedAppointments as $appointment) {
                $statusText = '';
                if ($appointment['status'] === 'confirmed') {
                    $statusText = 'onaylandı';
                } elseif ($appointment['status'] === 'completed') {
                    $statusText = 'tamamlandı';
                } elseif ($appointment['status'] === 'cancelled') {
                    $statusText = 'iptal edildi';
                } else {
                    $statusText = 'güncellendi';
                }
                
                $activities[] = [
                    'type' => 'appointment_updated',
                    'icon' => 'fas fa-calendar-edit',
                    'color' => 'purple',
                    'message' => 'Randevu ' . $statusText . ': ' . ($appointment['title'] ?? 'Başlıksız'),
                    'customer' => $appointment['customer_name'] ?? 'Bilinmeyen',
                    'time' => $appointment['updated_at'],
                    'time_text' => $this->timeAgo($appointment['updated_at'])
                ];
            }
            
            // Get confirmed appointments (last 24 hours)
            $confirmedWhere = $this->scopeToCompany("
                WHERE a.status = 'confirmed'
                AND a.updated_at >= datetime('now', '-24 hours')
            ", 'a');
            $confirmedAppointments = $db->fetchAll("
                SELECT a.*, c.name as customer_name, 'appointment_confirmed' as type
                FROM appointments a
                LEFT JOIN customers c ON a.customer_id = c.id
                {$confirmedWhere}
                ORDER BY a.updated_at DESC
                LIMIT 5
            ");
            
            foreach ($confirmedAppointments as $appointment) {
                $activities[] = [
                    'type' => 'appointment_confirmed',
                    'icon' => 'fas fa-calendar-check',
                    'color' => 'purple',
                    'message' => 'Randevu onaylandı: ' . ($appointment['title'] ?? 'Başlıksız'),
                    'customer' => $appointment['customer_name'] ?? 'Bilinmeyen',
                    'time' => $appointment['updated_at'],
                    'time_text' => $this->timeAgo($appointment['updated_at'])
                ];
            }
            
            // Get new contracts created (last 24 hours)
            // ===== PATHF_STAGE1: Use JOIN with customers.company_id instead of ct.company_id =====
            // PATH F: contracts tablosunda company_id kolonu yok
            // İlişki: contracts.customer_id → customers.id → customers.company_id
            $companyId = Auth::companyId() ?? 1;
            $newContractWhere = "WHERE ct.created_at >= datetime('now', '-24 hours') AND c.company_id = ?";
            try {
                $newContracts = $db->fetchAll("
                    SELECT ct.*, c.name as customer_name, 'contract_created' as type
                    FROM contracts ct
                    LEFT JOIN customers c ON ct.customer_id = c.id
                    {$newContractWhere}
                    ORDER BY ct.created_at DESC
                    LIMIT 10
                ", [$companyId]);
            } catch (\PDOException $e) {
                // ===== PATHF_STAGE2: Log exception and try fallback =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHF_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getRecentActivities',
                        'table' => 'contracts',
                        'sql' => 'contracts with JOIN (new)'
                    ]);
                }
                error_log("Dashboard activities contracts (new) error: " . $e->getMessage());
                
                // Fallback: try without company_id filter
                if (strpos($e->getMessage(), 'no such column') !== false || strpos($e->getMessage(), 'company_id') !== false) {
                    if (class_exists('PathCLogger')) {
                        PathCLogger::log('PATHF_DB_QUERY_FALLBACK_NO_COMPANY_COLUMN', [
                            'function' => 'getRecentActivities',
                            'table' => 'contracts',
                            'type' => 'new'
                        ]);
                    }
                    try {
                        $newContracts = $db->fetchAll("
                            SELECT ct.*, c.name as customer_name, 'contract_created' as type
                            FROM contracts ct
                            LEFT JOIN customers c ON ct.customer_id = c.id
                            WHERE ct.created_at >= datetime('now', '-24 hours')
                            ORDER BY ct.created_at DESC
                            LIMIT 10
                        ");
                    } catch (\PDOException $e2) {
                        $newContracts = []; // Ultimate fallback
                    }
                } else {
                    $newContracts = []; // Safe default
                }
            } catch (Exception $e) {
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHF_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getRecentActivities',
                        'table' => 'contracts',
                        'sql' => 'contracts with JOIN (new)'
                    ]);
                }
                $newContracts = []; // Safe default
            }
            // ===== PATHF_STAGE1 END =====
            
            foreach ($newContracts as $contract) {
                $activities[] = [
                    'type' => 'contract_created',
                    'icon' => 'fas fa-file-contract',
                    'color' => 'indigo',
                    'message' => 'Yeni sözleşme eklendi: ' . ($contract['title'] ?? 'Başlıksız'),
                    'customer' => $contract['customer_name'] ?? 'Bilinmeyen',
                    'time' => $contract['created_at'],
                    'time_text' => $this->timeAgo($contract['created_at'])
                ];
            }
            
            // Get updated contracts (last 24 hours, but not just created)
            // ===== PATHF_STAGE1: Use JOIN with customers.company_id instead of ct.company_id =====
            // PATH F: contracts tablosunda company_id kolonu yok
            // İlişki: contracts.customer_id → customers.id → customers.company_id
            $updatedContractWhere = "
                WHERE ct.updated_at >= datetime('now', '-24 hours')
                AND ct.updated_at != ct.created_at
                AND ct.created_at < datetime('now', '-1 hour')
                AND c.company_id = ?
            ";
            try {
                $updatedContracts = $db->fetchAll("
                    SELECT ct.*, c.name as customer_name, 'contract_updated' as type
                    FROM contracts ct
                    LEFT JOIN customers c ON ct.customer_id = c.id
                    {$updatedContractWhere}
                    ORDER BY ct.updated_at DESC
                    LIMIT 10
                ", [$companyId]);
            } catch (\PDOException $e) {
                // ===== PATHF_STAGE2: Log exception and try fallback =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHF_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getRecentActivities',
                        'table' => 'contracts',
                        'sql' => 'contracts with JOIN (updated)'
                    ]);
                }
                error_log("Dashboard activities contracts (updated) error: " . $e->getMessage());
                
                // Fallback: try without company_id filter
                if (strpos($e->getMessage(), 'no such column') !== false || strpos($e->getMessage(), 'company_id') !== false) {
                    if (class_exists('PathCLogger')) {
                        PathCLogger::log('PATHF_DB_QUERY_FALLBACK_NO_COMPANY_COLUMN', [
                            'function' => 'getRecentActivities',
                            'table' => 'contracts',
                            'type' => 'updated'
                        ]);
                    }
                    try {
                        $updatedContracts = $db->fetchAll("
                            SELECT ct.*, c.name as customer_name, 'contract_updated' as type
                            FROM contracts ct
                            LEFT JOIN customers c ON ct.customer_id = c.id
                            WHERE ct.updated_at >= datetime('now', '-24 hours')
                            AND ct.updated_at != ct.created_at
                            AND ct.created_at < datetime('now', '-1 hour')
                            ORDER BY ct.updated_at DESC
                            LIMIT 10
                        ");
                    } catch (\PDOException $e2) {
                        $updatedContracts = []; // Ultimate fallback
                    }
                } else {
                    $updatedContracts = []; // Safe default
                }
            } catch (Exception $e) {
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHF_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getRecentActivities',
                        'table' => 'contracts',
                        'sql' => 'contracts with JOIN (updated)'
                    ]);
                }
                $updatedContracts = []; // Safe default
            }
            // ===== PATHF_STAGE1 END =====
            
            foreach ($updatedContracts as $contract) {
                $statusText = '';
                if ($contract['status'] === 'ACTIVE') {
                    $statusText = 'aktif edildi';
                } elseif ($contract['status'] === 'COMPLETED') {
                    $statusText = 'tamamlandı';
                } elseif ($contract['status'] === 'SUSPENDED') {
                    $statusText = 'askıya alındı';
                } elseif ($contract['status'] === 'TERMINATED') {
                    $statusText = 'sonlandırıldı';
                } else {
                    $statusText = 'güncellendi';
                }
                
                $activities[] = [
                    'type' => 'contract_updated',
                    'icon' => 'fas fa-file-edit',
                    'color' => 'indigo',
                    'message' => 'Sözleşme ' . $statusText . ': ' . ($contract['title'] ?? 'Başlıksız'),
                    'customer' => $contract['customer_name'] ?? 'Bilinmeyen',
                    'time' => $contract['updated_at'],
                    'time_text' => $this->timeAgo($contract['updated_at'])
                ];
            }
            
            // Get contract payments (last 24 hours) if table exists
            // ===== PATHE_STAGE1: Use JOIN with customers.company_id instead of cp.company_id =====
            // PATH E: contract_payments tablosunda company_id kolonu yok
            // İlişki: contract_payments.contract_id → contracts.id → contracts.customer_id → customers.id → customers.company_id
            try {
                $companyId = Auth::companyId() ?? 1;
                $contractPaymentWhere = "WHERE cp.created_at >= datetime('now', '-24 hours') AND c.company_id = ?";
                $contractPayments = $db->fetchAll("
                    SELECT cp.*, ct.title as contract_title, c.name as customer_name, 'contract_payment' as type
                    FROM contract_payments cp
                    LEFT JOIN contracts ct ON cp.contract_id = ct.id
                    LEFT JOIN customers c ON ct.customer_id = c.id
                    {$contractPaymentWhere}
                    ORDER BY cp.created_at DESC
                    LIMIT 10
                ", [$companyId]);
                
                foreach ($contractPayments as $payment) {
                    $activities[] = [
                        'type' => 'contract_payment',
                        'icon' => 'fas fa-money-bill-wave',
                        'color' => 'indigo',
                        'message' => 'Sözleşme ödemesi eklendi: ₺' . number_format($payment['amount'], 0, ',', '.') . ' - ' . ($payment['contract_title'] ?? 'Sözleşme'),
                        'customer' => $payment['customer_name'] ?? 'Bilinmeyen',
                        'time' => $payment['created_at'],
                        'time_text' => $this->timeAgo($payment['created_at'])
                    ];
                }
            } catch (\PDOException $e) {
                // ===== PATHE_STAGE1: Log exception and try fallback =====
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHE_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getRecentActivities',
                        'table' => 'contract_payments',
                        'sql' => 'contract_payments with JOIN'
                    ]);
                }
                error_log("Dashboard activities contract_payments error: " . $e->getMessage());
                
                // Fallback: try without company_id filter
                if (strpos($e->getMessage(), 'no such column') !== false || strpos($e->getMessage(), 'company_id') !== false) {
                    if (class_exists('PathCLogger')) {
                        PathCLogger::log('PATHE_DB_QUERY_FALLBACK_NO_COMPANY_COLUMN', [
                            'function' => 'getRecentActivities',
                            'table' => 'contract_payments'
                        ]);
                    }
                    // PATH E: company scope disabled for contract_payments (no cp.company_id column in schema)
                    // Log this as info for future reference
                    if (class_exists('PathCLogger')) {
                        PathCLogger::log('CONTRACT_PAYMENTS_SCOPE_DISABLED', [
                            'company_id' => $companyId ?? null,
                            'user_id' => Auth::id() ?? null,
                            'reason' => 'no cp.company_id column in schema'
                        ]);
                    }
                    try {
                        $contractPayments = $db->fetchAll("
                            SELECT cp.*, ct.title as contract_title, c.name as customer_name, 'contract_payment' as type
                            FROM contract_payments cp
                            LEFT JOIN contracts ct ON cp.contract_id = ct.id
                            LEFT JOIN customers c ON ct.customer_id = c.id
                            WHERE cp.created_at >= datetime('now', '-24 hours')
                            ORDER BY cp.created_at DESC
                            LIMIT 10
                        ");
                        
                        foreach ($contractPayments as $payment) {
                            $activities[] = [
                                'type' => 'contract_payment',
                                'icon' => 'fas fa-money-bill-wave',
                                'color' => 'indigo',
                                'message' => 'Sözleşme ödemesi eklendi: ₺' . number_format($payment['amount'], 0, ',', '.') . ' - ' . ($payment['contract_title'] ?? 'Sözleşme'),
                                'customer' => $payment['customer_name'] ?? 'Bilinmeyen',
                                'time' => $payment['created_at'],
                                'time_text' => $this->timeAgo($payment['created_at'])
                            ];
                        }
                    } catch (\PDOException $e2) {
                        // Ultimate fallback: skip contract payments
                    }
                }
            } catch (Exception $e) {
                // contract_payments table might not exist, skip silently
                if (class_exists('PathCLogger')) {
                    PathCLogger::logException('PATHE_DB_QUERY_EXCEPTION', $e, [
                        'function' => 'getRecentActivities',
                        'table' => 'contract_payments',
                        'sql' => 'contract_payments with JOIN'
                    ]);
                }
            }
            // ===== PATHE_STAGE1 END =====
            
            // Sort by time (newest first)
            // Timezone'u ayarla
            if (defined('TIMEZONE')) {
                date_default_timezone_set(TIMEZONE);
            }
            
            usort($activities, function($a, $b) {
                // Timestamp al - hem UTC hem lokal olarak dene
                $getTimestamp = function($datetimeStr) {
                    $now = time();
                    
                    // İlk olarak UTC olarak parse et
                    try {
                        $dtUtc = new DateTime($datetimeStr, new DateTimeZone('UTC'));
                        if (defined('TIMEZONE')) {
                            $dtUtc->setTimezone(new DateTimeZone(TIMEZONE));
                        }
                        $tsUtc = $dtUtc->getTimestamp();
                        $diffUtc = abs($now - $tsUtc);
                    } catch (Exception $e) {
                        $tsUtc = false;
                        $diffUtc = PHP_INT_MAX;
                    }
                    
                    // Lokal olarak parse et
                    try {
                        $dtLocal = new DateTime($datetimeStr);
                        $tsLocal = $dtLocal->getTimestamp();
                        $diffLocal = abs($now - $tsLocal);
                    } catch (Exception $e) {
                        $tsLocal = false;
                        $diffLocal = PHP_INT_MAX;
                    }
                    
                    // strtotime ile parse et
                    $tsStrtotime = strtotime($datetimeStr);
                    $diffStrtotime = $tsStrtotime !== false ? abs($now - $tsStrtotime) : PHP_INT_MAX;
                    
                    // En mantıklı sonucu seç (şu anki zamana en yakın olan)
                    $results = [
                        ['ts' => $tsUtc, 'diff' => $diffUtc],
                        ['ts' => $tsLocal, 'diff' => $diffLocal],
                        ['ts' => $tsStrtotime, 'diff' => $diffStrtotime]
                    ];
                    
                    // Gelecekteki zamanları ele (timezone hatası olabilir)
                    $validResults = array_filter($results, function($r) use ($now) {
                        return $r['ts'] !== false && ($now - $r['ts']) >= -300; // 5 dakikadan fazla gelecekteki zamanları ele
                    });
                    
                    if (!empty($validResults)) {
                        // En yakın geçmiş zamanı seç
                        usort($validResults, function($a, $b) {
                            return $a['diff'] <=> $b['diff'];
                        });
                        return $validResults[0]['ts'];
                    }
                    
                    // Geçerli sonuç yoksa, en yakın olanı kullan
                    usort($results, function($a, $b) {
                        return $a['diff'] <=> $b['diff'];
                    });
                    
                    return $results[0]['ts'] !== false ? $results[0]['ts'] : 0;
                };
                
                $timeA = $getTimestamp($a['time']);
                $timeB = $getTimestamp($b['time']);
                
                // En yeni önce gelecek şekilde sırala (büyükten küçüğe)
                return $timeB - $timeA;
            });
            
            // Limit total activities to prevent excessive display
            $maxActivities = AppConstants::DASHBOARD_RECENT_ITEMS * 3; // 15 total activities max
            return array_slice($activities, 0, $maxActivities);
            
        } catch (Exception $e) {
            error_log("Dashboard activities error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Convert datetime to human readable time ago
     */
    private function timeAgo(string $datetime): string
    {
        // Timezone'u ayarla
        if (defined('TIMEZONE')) {
            date_default_timezone_set(TIMEZONE);
        }
        
        $now = time();
        $timestamp = false;
        
        // UTC olarak parse et
        try {
            $dtUtc = new DateTime($datetime, new DateTimeZone('UTC'));
            if (defined('TIMEZONE')) {
                $dtUtc->setTimezone(new DateTimeZone(TIMEZONE));
            }
            $tsUtc = $dtUtc->getTimestamp();
            $diffUtc = $now - $tsUtc;
        } catch (Exception $e) {
            $tsUtc = false;
            $diffUtc = PHP_INT_MAX;
        }
        
        // Lokal olarak parse et
        try {
            $dtLocal = new DateTime($datetime);
            $tsLocal = $dtLocal->getTimestamp();
            $diffLocal = $now - $tsLocal;
        } catch (Exception $e) {
            $tsLocal = false;
            $diffLocal = PHP_INT_MAX;
        }
        
        // strtotime ile parse et
        $tsStrtotime = strtotime($datetime);
        $diffStrtotime = $tsStrtotime !== false ? ($now - $tsStrtotime) : PHP_INT_MAX;
        
        // En mantıklı sonucu seç: geçmişteki ve şu anki zamana en yakın olan
        $results = [];
        if ($tsUtc !== false && $diffUtc >= -300) {
            $results[] = ['ts' => $tsUtc, 'diff' => abs($diffUtc)];
        }
        if ($tsLocal !== false && $diffLocal >= -300) {
            $results[] = ['ts' => $tsLocal, 'diff' => abs($diffLocal)];
        }
        if ($tsStrtotime !== false && $diffStrtotime >= -300) {
            $results[] = ['ts' => $tsStrtotime, 'diff' => abs($diffStrtotime)];
        }
        
        if (!empty($results)) {
            usort($results, function($a, $b) {
                return $a['diff'] <=> $b['diff'];
            });
            $timestamp = $results[0]['ts'];
        } else {
            // Geçerli sonuç yoksa, en yakın olanı kullan
            $allResults = [
                ['ts' => $tsUtc, 'diff' => $tsUtc !== false ? abs($diffUtc) : PHP_INT_MAX],
                ['ts' => $tsLocal, 'diff' => $tsLocal !== false ? abs($diffLocal) : PHP_INT_MAX],
                ['ts' => $tsStrtotime, 'diff' => $tsStrtotime !== false ? abs($diffStrtotime) : PHP_INT_MAX]
            ];
            usort($allResults, function($a, $b) {
                return $a['diff'] <=> $b['diff'];
            });
            $timestamp = $allResults[0]['ts'] !== false ? $allResults[0]['ts'] : false;
        }
        
        // Hala başarısızsa
        if ($timestamp === false) {
            return 'Bilinmiyor';
        }
        
        // Mevcut zaman ile farkı hesapla
        $diff = time() - $timestamp;
        
        // Negatif fark (gelecekteki zaman) - bu aslında timezone düzeltmesi sonucu oluşabilir
        // Çok küçük negatif farklar (0-5 saniye) için "Az önce" göster
        if ($diff < 0 && abs($diff) <= 5) {
            return 'Az önce';
        }
        
        // Gerçekten gelecekteki zamanlar için
        if ($diff < 0) {
            return 'Şimdi';
        }
        
        // 0-59 saniye arası
        if ($diff < 60) {
            return 'Az önce';
        // 60 saniye - 59 dakika arası
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' dakika önce';
        // 1 saat - 23 saat arası
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' saat önce';
        // 1 gün ve üzeri
        } else {
            $days = floor($diff / 86400);
            return $days . ' gün önce';
        }
    }
}
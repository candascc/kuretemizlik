<?php

/**
 * Report Controller
 */
class ReportController
{
    use CompanyScope;

    private $jobModel;
    private $customerModel;
    private $moneyModel;
    private $serviceModel;

    public function __construct()
    {
        $this->jobModel = new Job();
        $this->customerModel = new Customer();
        $this->moneyModel = new MoneyEntry();
        $this->serviceModel = new Service();
    }

    /**
     * ROUND 45: Ortak auth helper - Tüm rapor endpoint'leri için tek tip auth + error handling
     * Returns null if access is granted, otherwise redirects and returns (method should return after calling this)
     */
    private function ensureReportsAccess(): void
    {
        try {
            if (!Auth::check()) {
                Utils::flash('error', 'Bu sayfaya erişmek için giriş yapmanız gerekiyor.');
                redirect(base_url('/login'));
                return;
            }
            
            // ROUND 45: ADMIN and SUPERADMIN always have access - bypass group check
            $currentRole = Auth::role();
            if ($currentRole === 'ADMIN' || $currentRole === 'SUPERADMIN') {
                return; // yetkili, devam edebilir
            }
            
            // ROUND 45: For other roles, check group (use hasGroup instead of requireGroup to avoid 403)
            // Defensive: Wrap hasGroup in try/catch in case it throws exception
            try {
                if (!Auth::hasGroup('nav.reports.core')) {
                    Utils::flash('error', 'Bu sayfaya erişim yetkiniz bulunmuyor.');
                    redirect(base_url('/'));
                    return;
                }
            } catch (Throwable $e) {
                // ROUND 45: If hasGroup throws exception, log and redirect (safe default)
                error_log("ReportController::ensureReportsAccess() - Auth::hasGroup() error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                Utils::flash('error', 'Yetki kontrolü sırasında bir hata oluştu.');
                redirect(base_url('/'));
                return;
            }
        } catch (Throwable $e) {
            // ROUND 45: Log error with full context
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $logLine = date('c') . ' ReportController::ensureReportsAccess() - UNEXPECTED ERROR' . PHP_EOL
                . '  User ID: ' . (Auth::check() ? Auth::id() : 'not authenticated') . PHP_EOL
                . '  Role: ' . (Auth::check() ? Auth::role() : 'not authenticated') . PHP_EOL
                . '  URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL
                . '  Exception: ' . $e->getMessage() . PHP_EOL
                . '  Stack trace: ' . $e->getTraceAsString() . PHP_EOL
                . '---' . PHP_EOL;
            @file_put_contents($logDir . '/report_access_r45.log', $logLine, FILE_APPEND);
            
            if (class_exists('AppErrorHandler')) {
                AppErrorHandler::logException($e, [
                    'context' => 'ReportController::ensureReportsAccess() - outer catch',
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'role' => Auth::check() ? Auth::role() : null,
                    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
            } else {
                error_log("ReportController::ensureReportsAccess() - UNEXPECTED ERROR: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            // ROUND 45: Kullanıcıya 200 status ile redirect göster (403/500 DEĞİL)
            Utils::flash('error', 'Rapor sayfası yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.');
            redirect(base_url('/'));
            return;
        }
    }

    /**
     * Reports dashboard
     * ROUND 46: Gerçek dashboard view - KPI'lar, son işler, top müşteriler, alt raporlara linkler
     */
    public function index()
    {
        // ROUND 46: Ortak auth helper kullan
        $this->ensureReportsAccess();
        
        // ROUND 46: KAPSAYICI TRY/CATCH - Tüm method'u sar, global error handler'a ulaşmasın
        try {
            // Tarih aralığı – default son 30 gün
            $dateTo = new \DateTimeImmutable('today');
            $dateFrom = $dateTo->sub(new \DateInterval('P29D'));
            
            $db = Database::getInstance();
            
            // Özet verileri hazırla
            $dashboard = [
                'period' => [
                    'from' => $dateFrom,
                    'to'   => $dateTo,
                ],
                'kpis' => [
                    'total_income_30d'     => $this->calculateTotalIncomeLast30Days($db, $dateFrom, $dateTo),
                    'total_jobs_completed'  => $this->calculateCompletedJobsLast30Days($db, $dateFrom, $dateTo),
                    'active_customers'     => $this->calculateActiveCustomers($db, $dateFrom, $dateTo),
                    'net_profit_month'     => $this->calculateNetProfitThisMonth($db, $dateTo),
                ],
                'recent_jobs' => $this->getRecentJobs($db, 10),
                'top_customers' => $this->getTopCustomersForDashboard($db, $dateFrom, $dateTo, 5),
            ];
            
            echo View::renderWithLayout('reports/index', [
                'title' => 'Raporlar',
                'dashboard' => $dashboard,
                'flash' => Utils::getFlash(),
                'companyContext' => $this->getCurrentCompanyContext(),
            ]);
        } catch (Throwable $e) {
            // ROUND 46: Log error with full context
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $logLine = date('c') . ' ReportController::index() - UNEXPECTED ERROR' . PHP_EOL
                . '  User ID: ' . (Auth::check() ? Auth::id() : 'not authenticated') . PHP_EOL
                . '  Role: ' . (Auth::check() ? Auth::role() : 'not authenticated') . PHP_EOL
                . '  URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL
                . '  Exception: ' . $e->getMessage() . PHP_EOL
                . '  Stack trace: ' . $e->getTraceAsString() . PHP_EOL
                . '---' . PHP_EOL;
            @file_put_contents($logDir . '/report_index_r46.log', $logLine, FILE_APPEND);
            
            if (class_exists('AppErrorHandler')) {
                AppErrorHandler::logException($e, [
                    'context' => 'ReportController::index() - dashboard error',
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'role' => Auth::check() ? Auth::role() : null,
                    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
            } else {
                error_log("ReportController::index() - UNEXPECTED ERROR: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            // ROUND 46: Kullanıcıya 200 status ile error view göster (500 DEĞİL)
            View::error('Raporlar yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.', 200);
            return;
        }
    }

    /**
     * Dashboard reports
     */
    public function dashboard()
    {
        Auth::requireCapability('reports.view');

        $db = Database::getInstance();
        
        // Get overall stats
        $stats = [
            'total_customers' => $db->fetch("SELECT COUNT(*) as count FROM customers c " . $this->scopeToCompany('WHERE 1=1', 'c'))['count'] ?? 0,
            'total_jobs' => $db->fetch("SELECT COUNT(*) as count FROM jobs j " . $this->scopeToCompany('WHERE 1=1', 'j'))['count'] ?? 0,
            'total_revenue' => $db->fetch("SELECT COALESCE(SUM(me.amount), 0) as total FROM money_entries me " . $this->scopeToCompany("WHERE me.kind = 'INCOME'", 'me'))['total'] ?? 0,
            'completed_jobs' => $db->fetch("SELECT COUNT(*) as count FROM jobs j " . $this->scopeToCompany("WHERE j.status = 'DONE'", 'j'))['count'] ?? 0,
            'active_jobs' => $db->fetch("SELECT COUNT(*) as count FROM jobs j " . $this->scopeToCompany("WHERE j.status = 'SCHEDULED'", 'j'))['count'] ?? 0,
            'pending_jobs' => $db->fetch("SELECT COUNT(*) as count FROM jobs j " . $this->scopeToCompany("WHERE j.status = 'SCHEDULED'", 'j'))['count'] ?? 0,
        ];
        
        // Calculate completion rate
        $stats['completion_rate'] = $stats['total_jobs'] > 0 
            ? round(($stats['completed_jobs'] / $stats['total_jobs']) * 100, 1) 
            : 0;
        
        // Get recent activities
        $stats['recent_activities'] = ActivityLogger::getLogs(10, 0);

        echo View::renderWithLayout('reports/dashboard', [
            'title' => 'Dashboard Reports',
            'stats' => $stats,
            'flash' => Utils::getFlash(),
            'companyContext' => $this->getCurrentCompanyContext(),
        ]);
    }

    /**
     * Performance reports
     */
    public function performance()
    {
        Auth::requireCapability('reports.view');

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $data = $this->getPerformanceData($dateFrom, $dateTo);

        echo View::renderWithLayout('reports/performance', [
            'title' => 'Performance Reports',
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'companyContext' => $this->getCurrentCompanyContext(),
        ]);
    }

    /**
     * Customer report
     */
    public function customer($id)
    {
        Auth::requireGroup('nav.reports.customers');

        $db = Database::getInstance();
        $customer = $db->fetch("SELECT * FROM customers WHERE id = ? AND " . $this->companyColumn('customers'), [$id]);
        
        if (!$customer) {
            View::notFound('Müşteri bulunamadı');
            return;
        }

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $data = $this->getCustomerReportData($id, $dateFrom, $dateTo);

        echo View::renderWithLayout('reports/customer', [
            'title' => 'Customer Report - ' . $customer['name'],
            'customer' => $customer,
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'companyContext' => $this->getCurrentCompanyContext(),
        ]);
    }

    /**
     * Financial reports
     * ROUND 45: ensureReportsAccess() helper kullanarak tek tip auth modeli
     */
    public function financial()
    {
        // ROUND 45: Ortak auth helper kullan
        $this->ensureReportsAccess();
        
        // ROUND 45: Check capability (use hasCapability instead of requireCapability to avoid 403)
        try {
            if (!Auth::hasCapability('reports.financial')) {
                Utils::flash('error', 'Bu sayfaya erişim yetkiniz bulunmuyor.');
                redirect(base_url('/'));
                return;
            }
        } catch (Throwable $e) {
            // ROUND 45: If hasCapability throws exception, log and redirect (safe default)
            error_log("ReportController::financial() - Auth::hasCapability() error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Utils::flash('error', 'Yetki kontrolü sırasında bir hata oluştu.');
            redirect(base_url('/'));
            return;
        }

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $reportType = $_GET['type'] ?? 'summary';
        
        // Sanitize reportType to prevent XSS
        $allowedTypes = ['summary', 'daily', 'monthly', 'by_category'];
        if (!in_array($reportType, $allowedTypes, true)) {
            $reportType = 'summary';
        }

        $data = $this->getFinancialData($dateFrom, $dateTo, $reportType);

        if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
            $this->exportFinancialPDF($data, $dateFrom, $dateTo, $reportType);
            return;
        }

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportFinancialExcel($data, $dateFrom, $dateTo, $reportType);
            return;
        }

        echo View::renderWithLayout('reports/financial', [
            'title' => 'Financial Reports',
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'reportType' => $reportType,
            'companyContext' => $this->getCurrentCompanyContext(),
        ]);
    }

    /**
     * Job reports
     * ROUND 45: ensureReportsAccess() helper kullanarak tek tip auth modeli
     */
    public function jobs()
    {
        // ROUND 45: Ortak auth helper kullan
        $this->ensureReportsAccess();

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $reportType = $_GET['type'] ?? 'summary';

        $rawData = $this->getJobData($dateFrom, $dateTo, $reportType);

        if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
            $this->exportJobPDF($rawData, $dateFrom, $dateTo, $reportType);
            return;
        }

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportJobExcel($rawData, $dateFrom, $dateTo, $reportType);
            return;
        }

        // Format data for view
        if ($reportType === 'summary') {
            $data = [
                'summary' => [
                    'total' => $rawData['total_jobs'] ?? 0,
                    'done' => $rawData['completed'] ?? 0,
                    'cancelled' => $rawData['cancelled'] ?? 0,
                    'revenue' => $rawData['total_revenue'] ?? 0
                ],
                'rows' => []
            ];
        } else {
            $data = [
                'summary' => [],
                'rows' => $rawData
            ];
        }

        echo View::renderWithLayout('reports/jobs', [
            'title' => 'Job Reports',
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'reportType' => $reportType,
            'companyContext' => $this->getCurrentCompanyContext(),
        ]);
    }

    /**
     * Customer reports
     * ROUND 45: ensureReportsAccess() helper kullanarak tek tip auth modeli
     */
    public function customers()
    {
        // ROUND 45: Ortak auth helper kullan
        $this->ensureReportsAccess();

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $reportType = $_GET['type'] ?? 'summary';

        $rawData = $this->getCustomerData($dateFrom, $dateTo, $reportType);

        if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
            $this->exportCustomerPDF($rawData, $dateFrom, $dateTo, $reportType);
            return;
        }

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportCustomerExcel($rawData, $dateFrom, $dateTo, $reportType);
            return;
        }

        // Format data for view
        $summaryData = $this->getCustomerSummary(Database::getInstance(), $dateFrom, $dateTo);
        if ($reportType === 'summary') {
            $data = [
                'summary' => [
                    'total' => $summaryData['total_customers'] ?? 0,
                    'new' => $summaryData['new_customers'] ?? 0,
                    'total_jobs' => 0,
                    'total_revenue' => 0
                ],
                'rows' => []
            ];
        } else {
            $data = [
                'summary' => [
                    'total' => $summaryData['total_customers'] ?? 0,
                    'new' => $summaryData['new_customers'] ?? 0,
                    'total_jobs' => 0,
                    'total_revenue' => 0
                ],
                'rows' => $rawData
            ];
        }

        echo View::renderWithLayout('reports/customers', [
            'title' => 'Customer Reports',
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'reportType' => $reportType,
            'companyContext' => $this->getCurrentCompanyContext(),
        ]);
    }

    /**
     * Service performance reports
     * ROUND 45: ensureReportsAccess() helper kullanarak tek tip auth modeli
     */
    public function services()
    {
        // ROUND 45: Ortak auth helper kullan
        $this->ensureReportsAccess();

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $data = $this->getServiceData($dateFrom, $dateTo);

        if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
            $this->exportServicePDF($data, $dateFrom, $dateTo);
            return;
        }

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportServiceExcel($data, $dateFrom, $dateTo);
            return;
        }

        echo View::renderWithLayout('reports/services', [
            'title' => 'Service Performance Reports',
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'companyContext' => $this->getCurrentCompanyContext(),
        ]);
    }

    /**
     * Get financial data
     */
    private function getFinancialData($dateFrom, $dateTo, $reportType)
    {
        $db = Database::getInstance();

        switch ($reportType) {
            case 'summary':
                return $this->getFinancialSummary($db, $dateFrom, $dateTo);
            case 'daily':
                return $this->getDailyFinancialData($db, $dateFrom, $dateTo);
            case 'monthly':
                return $this->getMonthlyFinancialData($db, $dateFrom, $dateTo);
            case 'by_category':
                return $this->getFinancialByCategory($db, $dateFrom, $dateTo);
            default:
                return $this->getFinancialSummary($db, $dateFrom, $dateTo);
        }
    }

    /**
     * Get job data
     */
    private function getJobData($dateFrom, $dateTo, $reportType)
    {
        $db = Database::getInstance();

        switch ($reportType) {
            case 'summary':
                return $this->getJobSummary($db, $dateFrom, $dateTo);
            case 'by_status':
                return $this->getJobsByStatus($db, $dateFrom, $dateTo);
            case 'by_customer':
                return $this->getJobsByCustomer($db, $dateFrom, $dateTo);
            case 'by_service':
                return $this->getJobsByService($db, $dateFrom, $dateTo);
            case 'performance':
                return $this->getJobPerformance($db, $dateFrom, $dateTo);
            default:
                return $this->getJobSummary($db, $dateFrom, $dateTo);
        }
    }

    /**
     * Get customer data
     */
    private function getCustomerData($dateFrom, $dateTo, $reportType)
    {
        $db = Database::getInstance();

        switch ($reportType) {
            case 'summary':
                return $this->getCustomerSummary($db, $dateFrom, $dateTo);
            case 'top_customers':
                return $this->getTopCustomers($db, $dateFrom, $dateTo);
            case 'new_customers':
                return $this->getNewCustomers($db, $dateFrom, $dateTo);
            case 'customer_activity':
                return $this->getCustomerActivity($db, $dateFrom, $dateTo);
            default:
                return $this->getCustomerSummary($db, $dateFrom, $dateTo);
        }
    }

    /**
     * Get service data
     */
    private function getServiceData($dateFrom, $dateTo)
    {
        $db = Database::getInstance();

        return $db->fetchAll("
            SELECT 
                s.id,
                s.name,
                COUNT(j.id) as total_jobs,
                SUM(CASE WHEN j.status = 'DONE' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN j.status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled_jobs,
                SUM(j.total_amount) as total_revenue,
                AVG(j.total_amount) as avg_job_value,
                ROUND(SUM(CASE WHEN j.status = 'DONE' THEN 1 ELSE 0 END) * 100.0 / COUNT(j.id), 2) as completion_rate
            FROM services s
            LEFT JOIN jobs j ON s.id = j.service_id 
                AND DATE(j.start_at) BETWEEN ? AND ?
            GROUP BY s.id, s.name
            ORDER BY total_revenue DESC
        ", [$dateFrom, $dateTo]);
    }

    /**
     * Financial summary
     */
    private function getFinancialSummary($db, $dateFrom, $dateTo)
    {
        $summary = $db->fetch("
            SELECT 
                COALESCE(SUM(CASE WHEN kind = 'INCOME' THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN kind = 'EXPENSE' THEN amount ELSE 0 END), 0) as total_expense,
                COUNT(CASE WHEN kind = 'INCOME' THEN 1 END) as income_count,
                COUNT(CASE WHEN kind = 'EXPENSE' THEN 1 END) as expense_count
            FROM money_entries
            " . $this->scopeToCompany('WHERE date BETWEEN ? AND ?', null) . "
        ", [$dateFrom, $dateTo]);

        if (!$summary) {
            $summary = [
                'total_income' => 0,
                'total_expense' => 0,
                'income_count' => 0,
                'expense_count' => 0,
            ];
        }

        $summary['net_profit'] = $summary['total_income'] - $summary['total_expense'];
        $summary['profit_margin'] = $summary['total_income'] > 0 ? 
            round(($summary['net_profit'] / $summary['total_income']) * 100, 2) : 0;

        return $summary;
    }

    /**
     * Daily financial data
     */
    private function getDailyFinancialData($db, $dateFrom, $dateTo)
    {
        $whereClause = $this->scopeToCompany('WHERE me.date BETWEEN ? AND ?', 'me');
        return $db->fetchAll("
            SELECT 
                me.date,
                COALESCE(SUM(CASE WHEN me.kind = 'INCOME' THEN me.amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN me.kind = 'EXPENSE' THEN me.amount ELSE 0 END), 0) as expense,
                COALESCE(SUM(CASE WHEN me.kind = 'INCOME' THEN me.amount ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN me.kind = 'EXPENSE' THEN me.amount ELSE 0 END), 0) as net_profit
            FROM money_entries me
            " . $whereClause . "
            GROUP BY me.date
            ORDER BY me.date
        ", [$dateFrom, $dateTo]);
    }

    /**
     * Monthly financial data
     */
    private function getMonthlyFinancialData($db, $dateFrom, $dateTo)
    {
        $whereClause = $this->scopeToCompany('WHERE me.date BETWEEN ? AND ?', 'me');
        return $db->fetchAll("
            SELECT 
                strftime('%Y-%m', me.date) as month,
                COALESCE(SUM(CASE WHEN me.kind = 'INCOME' THEN me.amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN me.kind = 'EXPENSE' THEN me.amount ELSE 0 END), 0) as expense,
                COALESCE(SUM(CASE WHEN me.kind = 'INCOME' THEN me.amount ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN me.kind = 'EXPENSE' THEN me.amount ELSE 0 END), 0) as net_profit
            FROM money_entries me
            " . $whereClause . "
            GROUP BY strftime('%Y-%m', me.date)
            ORDER BY month
        ", [$dateFrom, $dateTo]);
    }

    /**
     * Financial by category
     */
    private function getFinancialByCategory($db, $dateFrom, $dateTo)
    {
        $whereClause = $this->scopeToCompany('WHERE me.date BETWEEN ? AND ?', 'me');
        return $db->fetchAll("
            SELECT 
                me.category,
                me.kind as type,
                COUNT(*) as count,
                SUM(me.amount) as total_amount,
                AVG(me.amount) as avg_amount
            FROM money_entries me
            " . $whereClause . "
            GROUP BY me.category, me.kind
            ORDER BY total_amount DESC
        ", [$dateFrom, $dateTo]);
    }

    /**
     * Job summary
     */
    private function getJobSummary($db, $dateFrom, $dateTo)
    {
        $summary = $db->fetch("
            SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'SCHEDULED' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'DONE' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_job_value
            FROM jobs
            WHERE DATE(start_at) BETWEEN ? AND ?
        ", [$dateFrom, $dateTo]);

        $summary['completion_rate'] = $summary['total_jobs'] > 0 ? 
            round(($summary['completed'] / $summary['total_jobs']) * 100, 2) : 0;

        return $summary;
    }

    /**
     * Jobs by status
     */
    private function getJobsByStatus($db, $dateFrom, $dateTo)
    {
        return $db->fetchAll("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_value
            FROM jobs
            WHERE DATE(start_at) BETWEEN ? AND ?
            GROUP BY status
            ORDER BY count DESC
        ", [$dateFrom, $dateTo]);
    }

    /**
     * Jobs by customer
     */
    private function getJobsByCustomer($db, $dateFrom, $dateTo)
    {
        return $db->fetchAll("
            SELECT 
                c.id,
                c.name as customer_name,
                COUNT(j.id) as job_count,
                SUM(j.total_amount) as total_spent,
                AVG(j.total_amount) as avg_job_value,
                MAX(j.start_at) as last_job_date
            FROM customers c
            LEFT JOIN jobs j ON c.id = j.customer_id 
                AND DATE(j.start_at) BETWEEN ? AND ?
            GROUP BY c.id, c.name
            HAVING job_count > 0
            ORDER BY total_spent DESC
            LIMIT 20
        ", [$dateFrom, $dateTo]);
    }

    /**
     * Jobs by service
     */
    private function getJobsByService($db, $dateFrom, $dateTo)
    {
        return $db->fetchAll("
            SELECT 
                s.id,
                s.name as service_name,
                COUNT(j.id) as job_count,
                SUM(j.total_amount) as total_revenue,
                AVG(j.total_amount) as avg_job_value,
                ROUND(SUM(CASE WHEN j.status = 'DONE' THEN 1 ELSE 0 END) * 100.0 / COUNT(j.id), 2) as completion_rate
            FROM services s
            LEFT JOIN jobs j ON s.id = j.service_id 
                AND DATE(j.start_at) BETWEEN ? AND ?
            GROUP BY s.id, s.name
            HAVING job_count > 0
            ORDER BY total_revenue DESC
        ", [$dateFrom, $dateTo]);
    }

    /**
     * Job performance metrics
     */
    private function getJobPerformance($db, $dateFrom, $dateTo)
    {
        return $db->fetchAll("
            SELECT 
                DATE(start_at) as date,
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'DONE' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled,
                SUM(total_amount) as revenue,
                ROUND(SUM(CASE WHEN status = 'DONE' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as completion_rate
            FROM jobs
            WHERE DATE(start_at) BETWEEN ? AND ?
            GROUP BY DATE(start_at)
            ORDER BY date
        ", [$dateFrom, $dateTo]);
    }

    /**
     * Customer summary
     */
    private function getCustomerSummary($db, $dateFrom, $dateTo)
    {
        $summary = $db->fetch("
            SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_customers,
                COUNT(CASE WHEN id IN (
                    SELECT DISTINCT customer_id FROM jobs 
                    WHERE DATE(start_at) BETWEEN ? AND ?
                ) THEN 1 END) as active_customers
            FROM customers
        ", [$dateFrom, $dateTo, $dateFrom, $dateTo]);

        return $summary;
    }

    /**
     * Top customers
     */
    private function getTopCustomers($db, $dateFrom, $dateTo)
    {
        return $db->fetchAll("
            SELECT 
                c.id,
                c.name,
                c.phone,
                COUNT(j.id) as job_count,
                SUM(j.total_amount) as total_spent,
                AVG(j.total_amount) as avg_job_value,
                MAX(j.start_at) as last_job_date
            FROM customers c
            INNER JOIN jobs j ON c.id = j.customer_id
            WHERE DATE(j.start_at) BETWEEN ? AND ?
            GROUP BY c.id, c.name, c.phone
            ORDER BY total_spent DESC
            LIMIT 20
        ", [$dateFrom, $dateTo]);
    }

    /**
     * New customers
     */
    private function getNewCustomers($db, $dateFrom, $dateTo)
    {
        return $db->fetchAll("
            SELECT 
                c.*,
                COUNT(j.id) as job_count,
                SUM(j.total_amount) as total_spent
            FROM customers c
            LEFT JOIN jobs j ON c.id = j.customer_id
            WHERE DATE(c.created_at) BETWEEN ? AND ?
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ", [$dateFrom, $dateTo]);
    }

    /**
     * Customer activity
     */
    private function getCustomerActivity($db, $dateFrom, $dateTo)
    {
        return $db->fetchAll("
            SELECT 
                DATE(j.start_at) as date,
                COUNT(DISTINCT j.customer_id) as unique_customers,
                COUNT(j.id) as total_jobs,
                SUM(j.total_amount) as total_revenue
            FROM jobs j
            WHERE DATE(j.start_at) BETWEEN ? AND ?
            GROUP BY DATE(j.start_at)
            ORDER BY date
        ", [$dateFrom, $dateTo]);
    }

    /**
     * Export methods using HTML format (Excel-compatible) or CSV
     */
    private function exportFinancialPDF($data, $dateFrom, $dateTo, $reportType) {
        Auth::requireCapability('reports.export');
        // For now, use HTML format that can be opened by Excel/PDF viewers
        $this->exportFinancialHTML($data, $dateFrom, $dateTo, $reportType);
    }

    private function exportFinancialExcel($data, $dateFrom, $dateTo, $reportType) {
        Auth::requireCapability('reports.export');
        // Use HTML format that Excel can open
        $this->exportFinancialHTML($data, $dateFrom, $dateTo, $reportType);
    }

    private function exportFinancialHTML($data, $dateFrom, $dateTo, $reportType) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="financial-report-' . date('Y-m-d') . '.xls"');
        
        $rows = $this->convertFinancialDataToRows($data, $reportType);
        echo ExportService::generateExcel($rows);
    }

    private function exportJobPDF($data, $dateFrom, $dateTo, $reportType) {
        Auth::requireCapability('reports.export');
        $this->exportJobHTML($data, $dateFrom, $dateTo, $reportType);
    }

    private function exportJobExcel($data, $dateFrom, $dateTo, $reportType) {
        Auth::requireCapability('reports.export');
        $this->exportJobHTML($data, $dateFrom, $dateTo, $reportType);
    }

    private function exportJobHTML($data, $dateFrom, $dateTo, $reportType) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="job-report-' . date('Y-m-d') . '.xls"');
        
        $rows = $this->convertJobDataToRows($data, $reportType);
        echo ExportService::generateExcel($rows);
    }

    private function exportCustomerPDF($data, $dateFrom, $dateTo, $reportType) {
        Auth::requireCapability('reports.export');
        $this->exportCustomerHTML($data, $dateFrom, $dateTo, $reportType);
    }

    private function exportCustomerExcel($data, $dateFrom, $dateTo, $reportType) {
        Auth::requireCapability('reports.export');
        $this->exportCustomerHTML($data, $dateFrom, $dateTo, $reportType);
    }

    private function exportCustomerHTML($data, $dateFrom, $dateTo, $reportType) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="customer-report-' . date('Y-m-d') . '.xls"');
        
        $rows = $this->convertCustomerDataToRows($data, $reportType);
        echo ExportService::generateExcel($rows);
    }

    private function exportServicePDF($data, $dateFrom, $dateTo) {
        Auth::requireCapability('reports.export');
        $this->exportServiceHTML($data, $dateFrom, $dateTo);
    }

    private function exportServiceExcel($data, $dateFrom, $dateTo) {
        Auth::requireCapability('reports.export');
        $this->exportServiceHTML($data, $dateFrom, $dateTo);
    }

    private function exportServiceHTML($data, $dateFrom, $dateTo) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="service-report-' . date('Y-m-d') . '.xls"');
        
        echo ExportService::generateExcel($data);
    }

    /**
     * Convert financial data to exportable rows
     */
    private function convertFinancialDataToRows($data, $reportType) {
        if (is_array($data) && !empty($data)) {
            // If data is already in row format
            if (isset($data[0]) && is_array($data[0])) {
                return $data;
            }
        }
        // Convert summary to row format
        return [$data];
    }

    /**
     * Convert job data to exportable rows
     */
    private function convertJobDataToRows($data, $reportType) {
        if (is_array($data) && !empty($data)) {
            if (isset($data[0]) && is_array($data[0])) {
                return $data;
            }
        }
        return [$data];
    }

    /**
     * Convert customer data to exportable rows
     */
    private function convertCustomerDataToRows($data, $reportType) {
        if (is_array($data) && !empty($data)) {
            if (isset($data[0]) && is_array($data[0])) {
                return $data;
            }
        }
        return [$data];
    }

    /**
     * Get performance data
     */
    private function getPerformanceData($dateFrom, $dateTo)
    {
        $db = Database::getInstance();
        
        return [
            'job_performance' => $this->getJobPerformance($db, $dateFrom, $dateTo),
            'service_performance' => $this->getServiceData($dateFrom, $dateTo),
            'staff_efficiency' => $this->getStaffEfficiency($db, $dateFrom, $dateTo),
            'customer_satisfaction' => $this->getCustomerSatisfaction($db, $dateFrom, $dateTo)
        ];
    }

    /**
     * Get customer report data
     */
    private function getCustomerReportData($customerId, $dateFrom, $dateTo)
    {
        $db = Database::getInstance();
        
        return [
            'jobs' => $this->getCustomerJobs($db, $customerId, $dateFrom, $dateTo),
            'payments' => $this->getCustomerPayments($db, $customerId, $dateFrom, $dateTo),
            'stats' => $this->getCustomerStats($db, $customerId, $dateFrom, $dateTo)
        ];
    }

    /**
     * Get staff efficiency metrics
     */
    private function getStaffEfficiency($db, $dateFrom, $dateTo)
    {
        try {
            return $db->fetchAll("
                SELECT 
                    u.id,
                    u.username as staff_name,
                    COUNT(j.id) as total_jobs,
                    SUM(CASE WHEN j.status = 'DONE' THEN 1 ELSE 0 END) as completed_jobs,
                    SUM(CASE WHEN j.status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled_jobs,
                    SUM(j.total_amount) as total_revenue,
                    ROUND(SUM(CASE WHEN j.status = 'DONE' THEN 1 ELSE 0 END) * 100.0 / COUNT(j.id), 2) as completion_rate
                FROM users u
                LEFT JOIN jobs j ON u.id = j.assigned_to 
                    AND DATE(j.start_at) BETWEEN ? AND ?
                WHERE u.role = 'OPERATOR'
                GROUP BY u.id, u.username
                HAVING total_jobs > 0
                ORDER BY total_revenue DESC
            ", [$dateFrom, $dateTo]);
        } catch (Exception $e) {
            error_log("Staff efficiency query error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customer satisfaction metrics
     */
    private function getCustomerSatisfaction($db, $dateFrom, $dateTo)
    {
        try {
            // Placeholder - would need actual feedback/survey system
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get customer jobs
     */
    private function getCustomerJobs($db, $customerId, $dateFrom, $dateTo)
    {
        return $db->fetchAll("
            SELECT 
                j.*,
                s.name as service_name,
                a.line as address_line
            FROM jobs j
            LEFT JOIN services s ON j.service_id = s.id
            LEFT JOIN addresses a ON j.address_id = a.id
            WHERE j.customer_id = ?
            AND DATE(j.start_at) BETWEEN ? AND ?
            ORDER BY j.start_at DESC
        ", [$customerId, $dateFrom, $dateTo]);
    }

    /**
     * Get customer payments
     */
    private function getCustomerPayments($db, $customerId, $dateFrom, $dateTo)
    {
        return $db->fetchAll("
            SELECT 
                jp.*,
                j.id as job_id,
                j.start_at as job_date
            FROM job_payments jp
            LEFT JOIN jobs j ON jp.job_id = j.id
            WHERE j.customer_id = ?
            AND DATE(jp.created_at) BETWEEN ? AND ?
            ORDER BY jp.created_at DESC
        ", [$customerId, $dateFrom, $dateTo]);
    }

    /**
     * Get customer stats
     */
    private function getCustomerStats($db, $customerId, $dateFrom, $dateTo)
    {
        return $db->fetch("
            SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'DONE' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(total_amount) as total_spent,
                SUM(amount_paid) as total_paid,
                AVG(total_amount) as avg_job_value,
                MAX(start_at) as last_job_date
            FROM jobs
            WHERE customer_id = ?
            AND DATE(start_at) BETWEEN ? AND ?
        ", [$customerId, $dateFrom, $dateTo]);
    }

    /**
     * ROUND 46: Dashboard helper methods
     */
    
    /**
     * Calculate total income for last 30 days
     */
    private function calculateTotalIncomeLast30Days($db, $dateFrom, $dateTo)
    {
        try {
            $whereClause = $this->scopeToCompany("WHERE me.kind = 'INCOME' AND me.date BETWEEN ? AND ?", 'me');
            $result = $db->fetch("
                SELECT COALESCE(SUM(me.amount), 0) as total
                FROM money_entries me
                " . $whereClause . "
            ", [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')]);
            return (float)($result['total'] ?? 0);
        } catch (Throwable $e) {
            error_log("ReportController::calculateTotalIncomeLast30Days() error: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Calculate completed jobs for last 30 days
     */
    private function calculateCompletedJobsLast30Days($db, $dateFrom, $dateTo)
    {
        try {
            $whereClause = $this->scopeToCompany("WHERE j.status = 'DONE' AND DATE(j.start_at) BETWEEN ? AND ?", 'j');
            $result = $db->fetch("
                SELECT COUNT(*) as count
                FROM jobs j
                " . $whereClause . "
            ", [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')]);
            return (int)($result['count'] ?? 0);
        } catch (Throwable $e) {
            error_log("ReportController::calculateCompletedJobsLast30Days() error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate active customers (customers with jobs in last 30 days)
     */
    private function calculateActiveCustomers($db, $dateFrom, $dateTo)
    {
        try {
            $whereClause = $this->scopeToCompany('WHERE DATE(j.start_at) BETWEEN ? AND ?', 'j');
            $result = $db->fetch("
                SELECT COUNT(DISTINCT j.customer_id) as count
                FROM jobs j
                " . $whereClause . "
            ", [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')]);
            return (int)($result['count'] ?? 0);
        } catch (Throwable $e) {
            error_log("ReportController::calculateActiveCustomers() error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate net profit for current month
     */
    private function calculateNetProfitThisMonth($db, $dateTo)
    {
        try {
            $monthStart = $dateTo->modify('first day of this month')->format('Y-m-d');
            $monthEnd = $dateTo->format('Y-m-d');
            $whereClause = $this->scopeToCompany('WHERE me.date BETWEEN ? AND ?', 'me');
            $result = $db->fetch("
                SELECT 
                    COALESCE(SUM(CASE WHEN me.kind = 'INCOME' THEN me.amount ELSE 0 END), 0) as income,
                    COALESCE(SUM(CASE WHEN me.kind = 'EXPENSE' THEN me.amount ELSE 0 END), 0) as expense
                FROM money_entries me
                " . $whereClause . "
            ", [$monthStart, $monthEnd]);
            $income = (float)($result['income'] ?? 0);
            $expense = (float)($result['expense'] ?? 0);
            return $income - $expense;
        } catch (Throwable $e) {
            error_log("ReportController::calculateNetProfitThisMonth() error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recent jobs (last N jobs)
     */
    // Phase 4.2: Use constant for default limit
    private function getRecentJobs($db, $limit = AppConstants::DASHBOARD_TOP_ITEMS * 2)
    {
        try {
            $whereClause = $this->scopeToCompany('WHERE 1=1', 'j');
            return $db->fetchAll("
                SELECT 
                    j.id,
                    j.start_at as date,
                    j.status,
                    j.total_amount as amount,
                    c.name as customer_name,
                    s.name as service_name
                FROM jobs j
                LEFT JOIN customers c ON j.customer_id = c.id
                LEFT JOIN services s ON j.service_id = s.id
                " . $whereClause . "
                ORDER BY j.start_at DESC
                LIMIT ?
            ", [$limit]);
        } catch (Throwable $e) {
            error_log("ReportController::getRecentJobs() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get top customers for dashboard (top N by revenue in period)
     */
    // Phase 4.2: Use constant for default limit
    private function getTopCustomersForDashboard($db, $dateFrom, $dateTo, $limit = AppConstants::DASHBOARD_TOP_ITEMS)
    {
        try {
            $whereClause = $this->scopeToCompany('WHERE DATE(j.start_at) BETWEEN ? AND ?', 'j');
            return $db->fetchAll("
                SELECT 
                    c.id,
                    c.name,
                    COUNT(j.id) as job_count,
                    SUM(j.total_amount) as total_revenue
                FROM customers c
                INNER JOIN jobs j ON c.id = j.customer_id
                " . $whereClause . "
                GROUP BY c.id, c.name
                ORDER BY total_revenue DESC
                LIMIT ?
            ", [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d'), $limit]);
        } catch (Throwable $e) {
            error_log("ReportController::getTopCustomersForDashboard() error: " . $e->getMessage());
            return [];
        }
    }
}
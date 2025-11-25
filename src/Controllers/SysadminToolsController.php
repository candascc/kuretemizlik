<?php
/**
 * Sysadmin Tools Controller
 * 
 * PATH_CRAWL_SYSADMIN_WEB_V1: System admin tools accessible via web UI
 */

require_once __DIR__ . '/../Lib/Auth.php';
require_once __DIR__ . '/../Lib/View.php';
require_once __DIR__ . '/../Lib/Utils.php';
require_once __DIR__ . '/../Services/InternalCrawlService.php';
require_once __DIR__ . '/../Helpers/RouterHelper.php';
require_once __DIR__ . '/../Services/CrawlLogger.php';
require_once __DIR__ . '/../Services/CrawlStatusManager.php';
require_once __DIR__ . '/../Services/CrawlProgressTracker.php';
require_once __DIR__ . '/../Services/SessionManager.php';

class SysadminToolsController
{
    /**
     * Role to test user mapping
     */
    private static array $roleToTestUser = [
        'SUPERADMIN' => 'test_superadmin',
        'ADMIN' => 'test_admin',
        'OPERATOR' => 'test_operator',
        'SITE_MANAGER' => 'test_site_manager',
        'FINANCE' => 'test_finance',
        'SUPPORT' => 'test_support',
    ];
    
    /**
     * Get test user password from environment variable
     * 
     * @return string Test user password
     * @throws RuntimeException If password is not set in production
     */
    private static function getTestUserPassword(): string
    {
        $password = getenv('CRAWL_TEST_PASSWORD');
        
        if (empty($password)) {
            // Only allow default password in development
            if (defined('APP_DEBUG') && APP_DEBUG) {
                return '12dream21'; // Default for development only
            }
            
            throw new RuntimeException(
                'CRAWL_TEST_PASSWORD environment variable is not set. ' .
                'This is required in production for security reasons.'
            );
        }
        
        return $password;
    }
    
    /**
     * Parse memory limit string to bytes
     * 
     * @param string $memoryLimit Memory limit string (e.g., "512M", "1G")
     * @return int Memory limit in bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        if (empty($memoryLimit)) {
            return 0;
        }
        
        $unit = strtoupper(substr($memoryLimit, -1));
        if (is_numeric($unit)) {
            // No unit, assume bytes
            return (int)$memoryLimit;
        }
        
        $value = (int)substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return (int)$memoryLimit;
        }
    }
    
    /**
     * Sysadmin crawl action - Form page
     * 
     * GET /app/sysadmin/crawl
     * 
     * Only accessible to system admin (SUPERADMIN role)
     * Shows form to start crawl test or displays progress if test is running
     */
    public function crawl(): void
    {
        try {
            // 1) Auth + role kontrolü (sadece system admin)
            if (!Auth::check()) {
                redirect(base_url('/login'));
                return;
            }
            
            $user = Auth::user();
            $role = Auth::role();
            
            // Check if user is system admin (SUPERADMIN role only)
            if ($role !== 'SUPERADMIN') {
                http_response_code(403);
                View::error('Bu sayfaya erişim yetkiniz yok. Sadece sistem yöneticileri (SUPERADMIN) bu aracı kullanabilir.', 403);
                return;
            }
            
            // 2) Check if required classes are loaded
            if (!class_exists('CrawlStatusManager')) {
                error_log('CrawlStatusManager class not found');
                View::error('Sistem hatası: CrawlStatusManager sınıfı yüklenemedi. Lütfen sistem yöneticisine başvurun.', 500);
                return;
            }
            
            // 3) Check if a crawl test is currently running
            // CRITICAL: Always clean stale locks first before checking
            $statusManager = new CrawlStatusManager();
            
            // Force cleanup of stale locks before checking
            $cleanedCount = $statusManager->cleanStaleLocks();
            if ($cleanedCount > 0) {
                error_log("CrawlForm: Cleaned {$cleanedCount} stale lock(s)");
            }
            
            $currentLock = $statusManager->getCurrentLock();
            
            // If test is running (and not stale), redirect to progress page
            if ($currentLock) {
                // Double-check: Make sure the lock is actually active (not stale)
                $lockAge = time() - ($currentLock['startTime'] ?? 0);
                $maxLockAge = 1800; // 30 minutes
                
                if ($lockAge < $maxLockAge) {
                    // Lock is valid - redirect to progress
                    $testId = $currentLock['testId'];
                    redirect(base_url('/sysadmin/crawl/progress?testId=' . urlencode($testId)));
                    return;
                } else {
                    // Lock is stale but wasn't cleaned - force cleanup
                    error_log("CrawlForm: Found stale lock (age: {$lockAge}s), forcing cleanup");
                    $statusManager->releaseLock($currentLock['testId'] ?? '');
                    $statusManager->cleanStaleLocks();
                    // Continue to show form
                }
            }
            
            // 4) No test running - show form page
            $availableRoles = array_keys(self::$roleToTestUser);
            $defaultRole = $_GET['role'] ?? 'SUPERADMIN';
            
            // Validate and normalize role parameter
            if (!empty($defaultRole)) {
                $defaultRole = strtoupper(trim($defaultRole));
            } else {
                $defaultRole = 'SUPERADMIN';
            }
            
            // Validate default role
            if (!isset(self::$roleToTestUser[$defaultRole])) {
                $defaultRole = 'SUPERADMIN';
            }
            
            // 5) Render form page with error handling
            try {
                echo View::renderWithLayout('sysadmin/crawl_form', [
                    'currentUser' => $user,
                    'availableRoles' => $availableRoles,
                    'defaultRole' => $defaultRole,
                ]);
            } catch (Throwable $e) {
                error_log('View render error in crawl(): ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    View::error('View render hatası: ' . htmlspecialchars($e->getMessage()), 500);
                } else {
                    View::error('Sayfa yüklenirken bir hata oluştu. Lütfen tekrar deneyin.', 500);
                }
            }
        } catch (Throwable $e) {
            error_log('Fatal error in crawl(): ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine() . ' | Trace: ' . $e->getTraceAsString());
            if (defined('APP_DEBUG') && APP_DEBUG) {
                View::error('Kritik hata: ' . htmlspecialchars($e->getMessage()) . ' (File: ' . basename($e->getFile()) . ', Line: ' . $e->getLine() . ')', 500);
            } else {
                View::error('Sayfa yüklenirken bir hata oluştu. Lütfen sistem yöneticisine başvurun.', 500);
            }
        }
    }
    
    /**
     * Show crawl progress page
     * 
     * GET /app/sysadmin/crawl/progress?testId=...
     */
    public function showCrawlProgress(): void
    {
        // 1) Auth kontrolü
        if (!Auth::check()) {
            redirect(base_url('/login'));
            return;
        }
        
        $role = Auth::role();
        if ($role !== 'SUPERADMIN') {
            http_response_code(403);
            View::error('Bu sayfaya erişim yetkiniz yok.', 403);
            return;
        }
        
        // 2) Get test ID
        $testId = $_GET['testId'] ?? null;
        if (empty($testId)) {
            // If no testId, check for current lock
            $statusManager = new CrawlStatusManager();
            $currentLock = $statusManager->getCurrentLock();
            if ($currentLock) {
                $testId = $currentLock['testId'];
            } else {
                View::error('Test ID bulunamadı ve aktif test yok.', 400);
                return;
            }
        }
        
        // 3) Get status
        $statusManager = new CrawlStatusManager();
        $status = $statusManager->getStatus($testId);
        
        $user = Auth::user();
        $availableRoles = array_keys(self::$roleToTestUser);
        
        echo View::renderWithLayout('sysadmin/crawl_progress', [
            'testId' => $testId,
            'status' => $status,
            'currentUser' => $user,
            'availableRoles' => $availableRoles,
        ]);
    }
    
    /**
     * Start crawl test - Background execution
     * 
     * POST /app/sysadmin/crawl/start
     * 
     * Body: role=SUPERADMIN
     * 
     * Returns JSON: {success: true, testId: "...", message: "..."}
     */
    public function startCrawl(): void
    {
        // CRITICAL: Clear any existing output buffer to ensure clean JSON response
        // index.php may have started output buffering, we need clean output for JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // 1) Auth + role kontrolü
        if (!Auth::check()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit; // Use exit instead of return to prevent any further output
        }
        
        $user = Auth::user();
        $role = Auth::role();
        
        if ($role !== 'SUPERADMIN') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Forbidden - System admin (SUPERADMIN) only']);
            exit;
        }
        
        // 2) Parse request body (POST data)
        // NOTE: CSRF kontrolü Router tarafından yapılıyor, burada tekrar kontrol etmeye gerek yok
        $requestRole = $_POST['role'] ?? 'SUPERADMIN';
        $requestRole = strtoupper(trim($requestRole));
        
        // Validate role
        if (!isset(self::$roleToTestUser[$requestRole])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid role. Valid roles: ' . implode(', ', array_keys(self::$roleToTestUser))
            ]);
            exit;
        }
        
        // 4) Check if a test is already running
        $statusManager = new CrawlStatusManager();
        if ($statusManager->isLocked()) {
            $currentLock = $statusManager->getCurrentLock();
            http_response_code(409); // Conflict
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'A crawl test is already running',
                'testId' => $currentLock['testId'] ?? null,
                'message' => 'Please wait for the current test to complete or check the progress page.'
            ]);
            exit;
        }
        
        // 5) Get test user credentials
        $crawlUsername = self::$roleToTestUser[$requestRole];
        try {
            $crawlPassword = self::getTestUserPassword();
        } catch (RuntimeException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Crawl test password not configured'
            ]);
            exit;
        }
        
        // 6) Generate test ID
        $userId = $user['id'] ?? $_SESSION['user_id'] ?? 0;
        $testId = $statusManager->generateTestId((int)$userId);
        
        // 7) Create lock
        if (!$statusManager->createLock($testId, $requestRole, (int)$userId)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Failed to create lock file'
            ]);
            exit;
        }
        
        // 8) CRITICAL: Backup session BEFORE sending JSON response
        // This ensures we have the original session data before background process modifies it
        // Must be done BEFORE sending JSON response to avoid duplicate JSON output
        $sessionManager = new SessionManager();
        $sessionBackedUp = $sessionManager->backup();
        if (!$sessionBackedUp) {
            // If session backup fails, release lock and return error
            $statusManager->releaseLock($testId);
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Session backup failed - cannot start background crawl safely'
            ]);
            exit;
        }
        
        // 9) Send immediate response (before starting background process)
        // This allows the page to respond quickly while crawl runs in background
        // CRITICAL: Ensure no output has been sent before this point
        if (headers_sent($file, $line)) {
            // Log to file, not to output
            $logFile = defined('APP_ROOT') ? APP_ROOT . '/logs/crawl_errors.log' : sys_get_temp_dir() . '/crawl_errors.log';
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " ERROR: Headers already sent in {$file} at line {$line}\n", FILE_APPEND | LOCK_EX);
        }
        
        // CRITICAL: Clear output buffer completely before sending JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        $jsonResponse = json_encode([
            'success' => true,
            'testId' => $testId,
            'role' => $requestRole,
            'message' => 'Crawl test started'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Check for JSON encoding errors
        if ($jsonResponse === false) {
            // Log to file, not to output
            $logFile = defined('APP_ROOT') ? APP_ROOT . '/logs/crawl_errors.log' : sys_get_temp_dir() . '/crawl_errors.log';
            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' JSON encoding error: ' . json_last_error_msg() . "\n", FILE_APPEND | LOCK_EX);
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'error' => 'JSON encoding failed']);
            exit;
        }
        
        // Send JSON response
        echo $jsonResponse;
        
        // CRITICAL: Flush output immediately and close connection
        // This ensures only JSON is sent, nothing else
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            // For non-FastCGI, ensure all output is sent
            flush();
        }
        
        // NOTE: Do NOT call exit here - we need to continue with background execution
        // The fastcgi_finish_request() ensures response is sent to client
        
        // After fastcgi_finish_request, we can continue with background execution
        // But we must NOT send any more output to the client
        
        // 9) Background execution continues after fastcgi_finish_request
        // Output has already been flushed in step 8
        
        // 10) Ignore user abort - continue execution even if connection closes
        ignore_user_abort(true);
        
        // CRITICAL: After fastcgi_finish_request, close the current session
        // This prevents the background process from modifying the user's browser session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // CRITICAL: Disable error output in background process
        // This prevents PHP warnings/errors from being sent to client after JSON response
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        error_reporting(E_ALL);
        
        // CRITICAL: Close all output buffers in background process
        // This ensures no output can leak to client after fastcgi_finish_request
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Set a flag to indicate we're in background process
        // This will be checked by InternalCrawlService to use isolated session
        define('CRAWL_BACKGROUND_PROCESS', true);
        
        // 11) Initialize progress file before starting crawl (so status check doesn't fail)
        $progressFile = sys_get_temp_dir() . '/crawl_progress/' . $testId . '.json';
        $progressDir = dirname($progressFile);
        if (!is_dir($progressDir)) {
            @mkdir($progressDir, 0755, true);
        }
        $initialProgress = [
            'status' => 'running',
            'current' => 0,
            'total' => 0,
            'percentage' => 0,
            'current_url' => 'Başlatılıyor...',
            'success_count' => 0,
            'error_count' => 0,
            'timestamp' => time(),
        ];
        @file_put_contents($progressFile, json_encode($initialProgress, JSON_UNESCAPED_UNICODE), LOCK_EX);
        
        // 12) Start crawl in background
        try {
            // Increase memory and time limits
            @ini_set('memory_limit', '2048M');
            $maxExecutionTime = 300; // 5 minutes for background execution
            if (function_exists('set_time_limit')) {
                @set_time_limit($maxExecutionTime);
            }
            
            // Get router
            $router = RouterHelper::getOrCreateRouter();
            
            // Create crawl service with test ID for progress tracking
            $service = new InternalCrawlService($router, null, $testId);
            
            // Run crawl
            $crawlResult = $service->run($crawlUsername, $crawlPassword);
            
            // Save results to progress file
            $progressFile = sys_get_temp_dir() . '/crawl_progress/' . $testId . '.json';
            $finalData = [
                'status' => 'completed',
                'result' => $crawlResult,
                'completedAt' => time(),
            ];
            @file_put_contents($progressFile, json_encode($finalData, JSON_UNESCAPED_UNICODE), LOCK_EX);
            
            // Update lock status
            $statusManager->updateStatus($testId, 'completed');
            
            // CRITICAL: Do NOT restore session in background process
            // Background process uses isolated session, restoring would cause session hijacking
            // Session restore is handled inside InternalCrawlService::run() only for non-background processes
            // In background processes, session restore is skipped to prevent session hijacking
            
        } catch (Throwable $e) {
            // Update status to failed
            $statusManager->updateStatus($testId, 'failed');
            
            // Save error to progress file
            $progressFile = sys_get_temp_dir() . '/crawl_progress/' . $testId . '.json';
            $errorData = [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'failedAt' => time(),
            ];
            @file_put_contents($progressFile, json_encode($errorData, JSON_UNESCAPED_UNICODE), LOCK_EX);
            
            // CRITICAL: Do NOT restore session in background process (even on error)
            // Background process uses isolated session, restoring would cause session hijacking
            
            // Log error
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Background crawl error for testId {$testId}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Cancel crawl test - POST endpoint
     * 
     * POST /app/sysadmin/crawl/cancel
     * 
     * Cancels a running crawl test by releasing its lock
     */
    public function cancelCrawl(): void
    {
        // 1) Auth kontrolü
        if (!Auth::check()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $role = Auth::role();
        if ($role !== 'SUPERADMIN') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            return;
        }
        
        // 2) Get test ID from POST param
        $testId = $_POST['testId'] ?? $_GET['testId'] ?? null;
        
        if (empty($testId)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Test ID is required']);
            return;
        }
        
        // 3) Verify CSRF token
        if (class_exists('CSRF') && !CSRF::verifyRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'CSRF token validation failed']);
            return;
        }
        
        // 4) Cancel the crawl
        $statusManager = new CrawlStatusManager();
        
        // Check if lock exists
        $lockData = $statusManager->getStatus($testId);
        if (!$lockData) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Test not found or already completed']);
            return;
        }
        
        // Release lock and clean up progress file
        $statusManager->updateStatus($testId, 'failed');
        $statusManager->releaseLock($testId);
        
        // Clean progress file
        $progressFile = sys_get_temp_dir() . '/crawl_progress/' . $testId . '.json';
        if (file_exists($progressFile)) {
            @unlink($progressFile);
        }
        
        header('Content-Type: application/json');
        
        // Clear any previous output buffer content
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Crawl test cancelled successfully'
        ]);
        
        // Flush JSON response immediately
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }
    
    /**
     * Get crawl status - AJAX endpoint
     * 
     * GET /app/sysadmin/crawl/status?testId=...
     * 
     * Returns JSON with status and progress information
     */
    public function getCrawlStatus(): void
    {
        // CRITICAL: Clear any existing output buffer to ensure clean JSON response
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // 1) Auth kontrolü
        if (!Auth::check()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        $role = Auth::role();
        if ($role !== 'SUPERADMIN') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            exit;
        }
        
        // 2) Get test ID from query param
        $testId = $_GET['testId'] ?? null;
        
        $statusManager = new CrawlStatusManager();
        
        // If no testId provided, check for current lock
        if (empty($testId)) {
            $currentLock = $statusManager->getCurrentLock();
            if ($currentLock) {
                $testId = $currentLock['testId'];
            } else {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'status' => 'not_running',
                    'message' => 'No crawl test is currently running'
                ]);
                exit;
            }
        }
        
        // 3) Get status
        $status = $statusManager->getStatus($testId);
        
        if (!$status) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Test not found'
            ]);
            exit;
        }
        
        // 4) Merge progress data into status for easier access
        $progressData = $status['progress'] ?? null;
        if ($progressData && is_array($progressData)) {
            // Merge progress fields into status (including items list)
            $status = array_merge($status, [
                'current' => $progressData['current'] ?? 0,
                'total' => $progressData['total'] ?? 0,
                'percentage' => $progressData['percentage'] ?? 0,
                'current_url' => $progressData['current_url'] ?? '',
                'success_count' => $progressData['success_count'] ?? 0,
                'error_count' => $progressData['error_count'] ?? 0,
                'elapsed_time_seconds' => $status['elapsed'] ?? 0,
                'status_text' => $status['status'] === 'running' ? 'Çalışıyor...' : ($status['status'] === 'completed' ? 'Tamamlandı' : 'Başarısız'),
            ]);
            
            // CRITICAL: Preserve progress.items in the progress object for frontend
            // Frontend expects status.progress.items
            if (isset($progressData['items'])) {
                $status['progress']['items'] = $progressData['items'];
            } else {
                $status['progress']['items'] = [];
            }
        } else {
            // No progress data yet - initialize defaults
            $status = array_merge($status, [
                'current' => 0,
                'total' => 0,
                'percentage' => 0,
                'current_url' => 'Başlatılıyor...',
                'success_count' => 0,
                'error_count' => 0,
                'elapsed_time_seconds' => $status['elapsed'] ?? 0,
                'status_text' => $status['status'] === 'running' ? 'Başlatılıyor...' : ($status['status'] === 'completed' ? 'Tamamlandı' : 'Bilinmiyor'),
            ]);
            
            // Initialize empty progress object with empty items
            $status['progress'] = [
                'items' => [],
            ];
        }
        
        // 5) Return status
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'testId' => $testId,
            'status' => $status,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit; // Use exit to prevent any further output
    }
    
    
    /**
     * Show crawl results page
     * 
     * GET /app/sysadmin/crawl/results?testId=...
     * 
     * Displays completed crawl test results
     */
    public function showCrawlResults(): void
    {
        // 1) Auth kontrolü
        if (!Auth::check()) {
            redirect(base_url('/login'));
            return;
        }
        
        $role = Auth::role();
        if ($role !== 'SUPERADMIN') {
            http_response_code(403);
            View::error('Bu sayfaya erişim yetkiniz yok.', 403);
            return;
        }
        
        // 2) Get test ID
        $testId = $_GET['testId'] ?? null;
        if (empty($testId)) {
            View::error('Test ID bulunamadı.', 400);
            return;
        }
        
        // 3) Load results from progress file
        $progressFile = sys_get_temp_dir() . '/crawl_progress/' . $testId . '.json';
        if (!file_exists($progressFile)) {
            View::error('Test sonuçları bulunamadı.', 404);
            return;
        }
        
        $progressData = json_decode(@file_get_contents($progressFile), true);
        if (!$progressData || !isset($progressData['result'])) {
            View::error('Test sonuçları geçersiz.', 500);
            return;
        }
        
        $crawlResult = $progressData['result'];
        $statusManager = new CrawlStatusManager();
        $status = $statusManager->getStatus($testId);
        $crawlRole = $status['role'] ?? 'SUPERADMIN';
        $crawlUsername = self::$roleToTestUser[$crawlRole] ?? 'unknown';
        
        // 4) Render results view
        $user = Auth::user();
        $availableRoles = array_keys(self::$roleToTestUser);
        
        echo View::renderWithLayout('sysadmin/crawl_results', [
            'crawlResult' => $crawlResult,
            'currentUser' => $user,
            'crawlRole' => $crawlRole,
            'crawlUsername' => $crawlUsername,
            'availableRoles' => $availableRoles,
            'testId' => $testId,
        ]);
    }
    
    /**
     * Admin crawl action (deprecated - use crawl?role=ADMIN instead)
     * 
     * GET /app/sysadmin/admin-crawl
     * 
     * Only accessible to system admin (SUPERADMIN role)
     * Crawls pages as normal admin (test_admin user)
     */
    public function adminCrawl(): void
    {
        // Redirect to main crawl with role=ADMIN
        redirect(base_url('/sysadmin/crawl?role=ADMIN'));
    }
    
    /**
     * Remote crawl execution endpoint (for hosting without CLI)
     * 
     * POST /app/sysadmin/remote-crawl
     * 
     * JSON body:
     * {
     *   "role": "SUPERADMIN" | "ADMIN" | "OPERATOR" | "SITE_MANAGER" | "FINANCE" | "SUPPORT",
     *   "username": "test_admin" (optional),
     *   "password": "12dream21" (optional)
     * }
     * 
     * Returns JSON with crawl results
     */
    public function remoteCrawl(): void
    {
        // 1) Auth + role kontrolü (sadece system admin)
        if (!Auth::check()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $user = Auth::user();
        $role = Auth::role();
        
        // Check if user is system admin (SUPERADMIN role only)
        if ($role !== 'SUPERADMIN') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden - System admin (SUPERADMIN) only']);
            return;
        }
        
        // 2) Parse JSON input
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
            return;
        }
        
        // 3) Get role to crawl as
        $crawlRole = $data['role'] ?? 'SUPERADMIN';
        $crawlRole = strtoupper(trim($crawlRole));
        
        // Validate role
        if (!isset(self::$roleToTestUser[$crawlRole])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid role. Valid roles: ' . implode(', ', array_keys(self::$roleToTestUser))]);
            return;
        }
        
        // 4) Get credentials (use test user for role or provided credentials)
        $crawlUsername = $data['username'] ?? self::$roleToTestUser[$crawlRole];
        if (isset($data['password'])) {
            $crawlPassword = $data['password'];
        } else {
            try {
                $crawlPassword = self::getTestUserPassword();
            } catch (RuntimeException $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Crawl test password not configured']);
                return;
            }
        }
        
        // 5) Crawl'i çalıştır (InternalCrawlService kullan)
        try {
            // Get router using RouterHelper
            $router = RouterHelper::getOrCreateRouter();
            
            $service = new InternalCrawlService($router);
            $crawlResult = $service->run($crawlUsername, $crawlPassword);
            
            // 6) JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'role' => $crawlRole,
                'username' => $crawlUsername,
                'result' => $crawlResult,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            // Log error only in debug mode
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Remote crawl execution error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            }
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}

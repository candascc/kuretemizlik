<?php
/**
 * View Renderer Sınıfı
 */

require_once __DIR__ . '/../Exceptions/InternalHttpException.php';

class View
{
    private static $data = [];
    private static $r52LoggingActive = false; // ROUND 52: Prevent recursive 500 loop
    
    /**
     * View verisi set et
     */
    public static function with($key, $value = null)
    {
        if (is_array($key)) {
            self::$data = array_merge(self::$data, $key);
        } else {
            self::$data[$key] = $value;
        }
        return new self();
    }
    
    /**
     * View render et
     */
    public static function render($view, $variables = [])
    {
        $variables = array_merge(self::$data, $variables);
        
        // View dosya yolu
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        
        // CRITICAL: Debug logging for view file resolution
        if (strpos($view, 'crawl') !== false) {
            error_log("View::render: Resolving view '{$view}' to file: {$viewFile}");
            error_log("View::render: File exists: " . (file_exists($viewFile) ? 'yes' : 'no'));
        }
        
        if (!file_exists($viewFile)) {
            error_log("View::render: ERROR - View file not found: {$viewFile}");
            throw new Exception("View dosyası bulunamadı: $view");
        }
        
        // Verileri extract et - Use EXTR_SKIP to prevent variable override
        extract($variables, EXTR_SKIP | EXTR_REFS);
        
        // Output buffering ba�Ylat
        ob_start();
        
        // View dosyasını include et
        include $viewFile;
        
        // İçeri�Yi al
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * Layout ile render et
     */
    public static function renderWithLayout($view, $data = [], $layout = 'base')
    {
        // ===== LOGIN_500_STAGE1: Log render start =====
        $logFile = __DIR__ . '/../../logs/login_500_trace.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $sessionId = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
        $sessionStatus = session_status();
        $userId = 'none';
        $authCheck = false;
        try {
            if (class_exists('Auth') && Auth::check()) {
                $authCheck = true;
                $userId = Auth::id();
            }
        } catch (Throwable $e) {
            // Ignore
        }
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [View::renderWithLayout] START uri={$requestUri}, view={$view}, layout={$layout}, session_id={$sessionId}, session_status={$sessionStatus}, user_id={$userId}, Auth::check()=" . ($authCheck ? 'true' : 'false') . "\n", FILE_APPEND | LOCK_EX);
        // ===== LOGIN_500_STAGE1 END =====
        
        // ROUND 48: Global bootstrap 500 trace - log renderWithLayout start
        $r48_log_file = __DIR__ . '/../../logs/bootstrap_r48.log';
        $r48_timestamp = date('Y-m-d H:i:s');
        $r48_request_id = uniqid('req_', true);
        @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] RENDER_WITH_LAYOUT_START: view={$view}, layout={$layout}\n", FILE_APPEND | LOCK_EX);
        
        // ROUND 50: Targeted runtime trace for /app first-load scenario
        $r50_app_log = __DIR__ . '/../../logs/r50_app_firstload.log';
        $r50_log_dir = dirname($r50_app_log);
        if (!is_dir($r50_log_dir)) {
            @mkdir($r50_log_dir, 0755, true);
        }
        // ===== PATH_ISAPP_KILLVAR: Use helper function instead of local $isAppRequest variable =====
        if (function_exists('kureapp_is_app_request') && kureapp_is_app_request()) {
            $userId = class_exists('Auth') && Auth::check() ? Auth::id() : 'none';
            @file_put_contents($r50_app_log, "[{$r48_timestamp}] [{$r48_request_id}] APP_R50_START: uri={$requestUri}, user_id={$userId}, view={$view}, layout={$layout}\n", FILE_APPEND | LOCK_EX);
        }
        
        // ROUND 48: Hardening - Outer try/catch for complete protection
        try {
            if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
            // Prevent caching of dynamic pages (customers, jobs, dashboard, etc.)
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        $data = array_merge(self::$data, $data);

        // Get notification count for header (cached) - only if not already set
        if (APP_DEBUG) {
            error_log("[NOTIF DEBUG] View::renderWithLayout START - Auth::check=" . (Auth::check() ? 'YES' : 'NO') . ", hasCount=" . (isset($data['notificationCount']) ? 'YES' : 'NO'));
        }
        
        // ===== CRITICAL FIX: Ensure session is started before Auth::check() =====
        if (session_status() === PHP_SESSION_NONE) {
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] SESSION_START_ATTEMPT\n", FILE_APPEND | LOCK_EX);
            $cookiePath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            
            // Use SessionHelper for centralized session management
            if (SessionHelper::ensureStarted()) {
                @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] SESSION_START_SUCCESS\n", FILE_APPEND | LOCK_EX);
            } else {
                @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] SESSION_START_FAILED: Headers already sent or session disabled\n", FILE_APPEND | LOCK_EX);
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Session start failed in View::renderWithLayout: " . $e->getMessage());
                }
                // Continue without notifications if session can't be started
            }
        } else {
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] SESSION_ALREADY_STARTED\n", FILE_APPEND | LOCK_EX);
        }
        // ===== CRITICAL FIX END =====
        
        if (Auth::check() && !isset($data['notificationCount'])) {
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] NOTIFICATION_FETCH_START: Auth::check()=true\n", FILE_APPEND | LOCK_EX);
            try {
                if (class_exists('NotificationService')) {
                    if (APP_DEBUG) {
                        error_log("[NOTIF DEBUG] Using NotificationService");
                    }
                    // Always populate notifications for header (replace empty)
                    if (!isset($data['notifications']) || !is_array($data['notifications']) || count($data['notifications']) === 0) {
                        $data['notifications'] = NotificationService::getHeaderNotifications(6);
                    }
                    $data['notificationCount'] = NotificationService::getNotificationCount();
                    $data['hasNotifications'] = $data['notificationCount'] > 0;
                    @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] NOTIFICATION_FETCH_SUCCESS: count={$data['notificationCount']}\n", FILE_APPEND | LOCK_EX);
                    if (APP_DEBUG) {
                        error_log("[NOTIF DEBUG] Got " . $data['notificationCount'] . " notifications");
                    }
                } else {
                    @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] NOTIFICATION_SERVICE_NOT_FOUND\n", FILE_APPEND | LOCK_EX);
                    if (APP_DEBUG) {
                        error_log("[NOTIF DEBUG] NotificationService class NOT FOUND");
                    }
                    $data['notificationCount'] = 0;
                    $data['hasNotifications'] = false;
                }
            } catch (Exception $e) {
                @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] NOTIFICATION_FETCH_EXCEPTION: " . $e->getMessage() . ", file=" . $e->getFile() . ", line=" . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
                if (APP_DEBUG) {
                    error_log("[NOTIF DEBUG] Exception: " . $e->getMessage());
                }
                $data['notificationCount'] = 0;
                $data['hasNotifications'] = false;
            }
        } else {
            // If controller provided notifications, use them only if non-empty; otherwise fallback to service
            if (isset($data['notifications']) && is_array($data['notifications']) && count($data['notifications']) > 0) {
                if (APP_DEBUG) {
                    error_log("[NOTIF DEBUG] Controller provided " . count($data['notifications']) . " notifications");
                }
                $data['hasNotifications'] = true;
                $data['notificationCount'] = count($data['notifications']);
            } else {
                try {
                    if (class_exists('NotificationService')) {
                        if (APP_DEBUG) {
                            error_log("[NOTIF DEBUG] Fallback to NotificationService");
                        }
                        $data['notifications'] = NotificationService::getHeaderNotifications(6);
                        $data['notificationCount'] = NotificationService::getNotificationCount();
                        $data['hasNotifications'] = $data['notificationCount'] > 0;
                        if (APP_DEBUG) {
                            error_log("[NOTIF DEBUG] Got " . $data['notificationCount'] . " notifications from fallback");
                        }
                    } else {
                        if (APP_DEBUG) {
                            error_log("[NOTIF DEBUG] NotificationService class NOT FOUND in fallback");
                        }
                        $data['notifications'] = [];
                        $data['notificationCount'] = 0;
                        $data['hasNotifications'] = false;
                    }
                } catch (Exception $e) {
                    if (APP_DEBUG) {
                        error_log("[NOTIF DEBUG] Fallback exception: " . $e->getMessage());
                    }
                    $data['notifications'] = [];
                    $data['notificationCount'] = 0;
                    $data['hasNotifications'] = false;
                }
            }
        }
        
        if (APP_DEBUG) {
            error_log("[NOTIF DEBUG] Final: count=" . ($data['notificationCount'] ?? 0) . ", hasNotifs=" . ($data['hasNotifications'] ?? false));
        }

        // ROUND 48: Hardening - View render with safe fallback
        @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] VIEW_RENDER_START: view={$view}\n", FILE_APPEND | LOCK_EX);
        try {
            $content = self::render($view, $data);
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] VIEW_RENDER_SUCCESS: view={$view}\n", FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] VIEW_RENDER_EXCEPTION: " . $e->getMessage() . ", file=" . $e->getFile() . ", line=" . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
            // ROUND 48: Log and use safe fallback instead of throwing
            if (class_exists('Logger')) {
                Logger::error('View render failed', ['view' => $view, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            } else {
                error_log("View render failed: {$view} - {$e->getMessage()}");
            }
            // Safe fallback content
            $content = '<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Sayfa yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p></div>';
        }

        $data['content'] = $content;
        // Header meta (SLA/Backup/Version)
        @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_META_START\n", FILE_APPEND | LOCK_EX);
        try {
            if (!isset($data['headerMeta'])) {
                $dbPath = defined('DB_PATH') ? DB_PATH : (__DIR__ . '/../../db/app.sqlite');
                $lastBackup = file_exists($dbPath) ? date('d.m.Y H:i', filemtime($dbPath)) : '—';
                $version = $_ENV['APP_VERSION'] ?? 'v1';
                $data['headerMeta'] = [
                    'sla' => '99.9%',
                    'last_backup' => $lastBackup,
                    'version' => $version,
                ];
                @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_META_SUCCESS\n", FILE_APPEND | LOCK_EX);
            }
        } catch (Exception $e) {
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_META_EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        }
        // ===== LOGIN_500_PATHC: Log after layout load =====
        if (class_exists('PathCLogger')) {
            PathCLogger::log('VIEW_RENDER_AFTER_LAYOUT', ['view' => $view, 'layout' => $layout]);
        }
        // ===== LOGIN_500_PATHC END =====
        
        // ROUND 48: Hardening - Layout render with safe fallback
        @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] LAYOUT_RENDER_START: layout={$layout}\n", FILE_APPEND | LOCK_EX);
        try {
            $result = self::render('layout/' . $layout, $data);
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] LAYOUT_RENDER_SUCCESS: layout={$layout}\n", FILE_APPEND | LOCK_EX);
            
            // ===== LOGIN_500_PATHC: Log view render done =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('VIEW_RENDER_DONE', ['view' => $view, 'layout' => $layout]);
            }
            // ===== LOGIN_500_PATHC END =====
            
            return $result;
        } catch (Throwable $e) {
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] LAYOUT_RENDER_EXCEPTION: " . $e->getMessage() . ", file=" . $e->getFile() . ", line=" . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
            // ROUND 48: Log and use safe fallback instead of throwing
            if (class_exists('Logger')) {
                Logger::error('Layout render failed', ['layout' => $layout, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            } else {
                error_log("Layout render failed: {$layout} - {$e->getMessage()}");
            }
            // Safe fallback HTML
            http_response_code(200); // 500 yerine 200
            return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hata</title></head><body><div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Sayfa yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p></div></body></html>';
        }
        } catch (Throwable $e) {
            // ===== LOGIN_500_STAGE1: Log exception =====
            $errorMsg = str_replace(["\n", "\r"], [' ', ' '], $e->getMessage());
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            $errorClass = get_class($e);
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [View::renderWithLayout] EXCEPTION uri={$requestUri}, view={$view}, layout={$layout}, class={$errorClass}, message={$errorMsg}, file={$errorFile}, line={$errorLine}\n", FILE_APPEND | LOCK_EX);
            // ===== LOGIN_500_STAGE1 END =====
            
            // ROUND 52: Log fatal error to r52_view_fatal.log
            self::logViewFatal($e, $view, $layout);
            
            // ROUND 48: Outer catch - ultimate fallback if everything fails
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] RENDER_WITH_LAYOUT_EXCEPTION: " . $e->getMessage() . ", file=" . $e->getFile() . ", line=" . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
            
            // ROUND 50: Targeted runtime trace for /app first-load scenario
            // ===== PATH_ISAPP_KILLVAR: Use helper function instead of local $isAppRequest variable =====
            if (function_exists('kureapp_is_app_request') && kureapp_is_app_request()) {
                $errorMsg = $e->getMessage();
                $errorFile = $e->getFile();
                $errorLine = $e->getLine();
                $errorTrace = substr($e->getTraceAsString(), 0, 2000);
                @file_put_contents($r50_app_log, "[{$r48_timestamp}] [{$r48_request_id}] VIEW_R50_EXCEPTION: message={$errorMsg}, file={$errorFile}, line={$errorLine}, view={$view}, layout={$layout}\nTRACE:\n{$errorTrace}\n---\n", FILE_APPEND | LOCK_EX);
            }
            
            if (class_exists('Logger')) {
                Logger::error('renderWithLayout failed', ['view' => $view, 'layout' => $layout, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => substr($e->getTraceAsString(), 0, 1000)]);
            } else {
                error_log("renderWithLayout failed: {$view}/{$layout} - {$e->getMessage()}");
            }
            // Ultimate safe fallback HTML
            if (!headers_sent()) {
                http_response_code(200); // 500 yerine 200
                header('Content-Type: text/html; charset=UTF-8');
            }
            return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hata</title></head><body><div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Sayfa yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p></div></body></html>';
        }
        
        // ROUND 50: Targeted runtime trace - success log for /app requests
        // ===== PATH_ISAPP_KILLVAR: Use helper function instead of local $isAppRequest variable =====
        if (function_exists('kureapp_is_app_request') && kureapp_is_app_request()) {
            @file_put_contents($r50_app_log, "[{$r48_timestamp}] [{$r48_request_id}] VIEW_R50_SUCCESS: view={$view}, layout={$layout}\n", FILE_APPEND | LOCK_EX);
        }
        
        // ===== LOGIN_500_STAGE1: Log success =====
        $finalSessionId = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
        $finalStatus = session_status();
        $finalUserId = 'none';
        $finalAuthCheck = false;
        try {
            if (class_exists('Auth') && Auth::check()) {
                $finalAuthCheck = true;
                $finalUserId = Auth::id();
            }
        } catch (Throwable $e) {
            // Ignore
        }
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [View::renderWithLayout] SUCCESS uri={$requestUri}, view={$view}, layout={$layout}, session_id={$finalSessionId}, session_status={$finalStatus}, user_id={$finalUserId}, Auth::check()=" . ($finalAuthCheck ? 'true' : 'false') . "\n", FILE_APPEND | LOCK_EX);
        // ===== LOGIN_500_STAGE1 END =====
    }
    
    /**
     * ROUND 52: Log view fatal errors to r52_view_fatal.log
     * Prevents recursive 500 loop with static flag
     */
    private static function logViewFatal(Throwable $e, ?string $viewName, ?string $layoutName): void
    {
        // Prevent recursive logging
        if (self::$r52LoggingActive) {
            return;
        }
        
        self::$r52LoggingActive = true;
        
        try {
            $logFile = __DIR__ . '/../../logs/r52_view_fatal.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $requestId = uniqid('req_', true);
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            
            // Get user_id (safe, may fail)
            $userId = 'none';
            try {
                if (class_exists('Auth') && method_exists('Auth', 'id')) {
                    $userId = Auth::check() ? Auth::id() : 'none';
                }
            } catch (Throwable $authErr) {
                // Ignore auth errors during logging
            }
            
            // Shorten trace (first 5-10 frames)
            $trace = $e->getTraceAsString();
            $traceLines = explode("\n", $trace);
            $shortTrace = implode("\n", array_slice($traceLines, 0, 10));
            if (strlen($shortTrace) > 1000) {
                $shortTrace = substr($shortTrace, 0, 1000) . '...';
            }
            
            $viewStr = $viewName ?? 'null';
            $layoutStr = $layoutName ?? 'null';
            $class = get_class($e);
            $message = str_replace(["\n", "\r"], [' ', ' '], $e->getMessage());
            $file = $e->getFile();
            $line = $e->getLine();
            
            $logEntry = "[{$timestamp}] [{$requestId}] R52_VIEW_FATAL uri={$uri} method={$method} user_id={$userId} view={$viewStr} layout={$layoutStr} class={$class} message={$message} file={$file} line={$line} trace={$shortTrace}\n";
            
            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (Throwable $logErr) {
            // Silently fail - don't break error handling
        } finally {
            self::$r52LoggingActive = false;
        }
    }
    
    /**
     * Partial render et
     */
    public static function partial($partial, $data = [])
    {
        $normalized = ltrim($partial, '/');
        if (stripos($normalized, 'partials/') === 0) {
            $normalized = substr($normalized, strlen('partials/'));
        }
        return self::render('partials/' . $normalized, $data);
    }
    
    /**
     * JSON response
     */
    public static function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Redirect
     */
    public static function redirect($url, $status = 302)
    {
        // ===== PRODUCTION FIX: Prevent redirect caching =====
        // Clear any output buffers
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Prevent caching of redirect response
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Set response code and redirect
        http_response_code($status);
        header('Location: ' . $url);
        
        // Ensure session is written before redirect (if session is active)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        exit;
    }
    
    /**
     * Flash message ile redirect
     */
    public static function redirectWithFlash($url, $type, $message, $status = 302)
    {
        Utils::flash($type, $message);
        self::redirect($url, $status);
    }
    
    /**
     * Error sayfası
     * OPS HARDENING ROUND 1: Add request ID header for correlation
     */
    public static function error($message, $code = 500, $details = null)
    {
        // INTERNAL CRAWL MODE: Instead of exiting, throw an exception so crawler can continue
        if (defined('KUREAPP_INTERNAL_REQUEST') && KUREAPP_INTERNAL_REQUEST) {
            $body = self::render('errors/error', [
                'message' => $message,
                'code' => $code,
                'details' => $details
            ]);
            throw new InternalHttpException($code, $message, $body);
        }
        
        http_response_code($code);
        
        // OPS HARDENING: Add request ID header for log correlation
        if (class_exists('AppErrorHandler') && !headers_sent()) {
            header('X-Request-Id: ' . AppErrorHandler::getRequestId());
        }
        
        // ROUND 8: Error sayfası standalone HTML yapısı kullanıyor (lang="tr" için)
        echo self::render('errors/error', [
            'message' => $message,
            'code' => $code,
            'details' => $details
        ]);
        exit;
    }
    
    /**
     * 404 sayfası
     * OPS HARDENING ROUND 1: Add request ID header for correlation
     */
    public static function notFound($message = 'Sayfa bulunamadı')
    {
        // Log 404 for debugging
        if (APP_DEBUG) {
            error_log("404 NOT FOUND: " . ($_SERVER['REQUEST_URI'] ?? '/'));
        }
        
        http_response_code(404);
        
        // OPS HARDENING: Add request ID header for log correlation
        if (class_exists('AppErrorHandler') && !headers_sent()) {
            header('X-Request-Id: ' . AppErrorHandler::getRequestId());
        }
        
        // ROUND 8: 404 sayfası standalone HTML yapısı kullanıyor (lang="tr" için)
        echo self::render('errors/404', [
            'message' => $message
        ]);
        exit;
    }
    
    /**
     * Maintenance sayfası
     * OPS HARDENING ROUND 1: Maintenance mode page
     */
    public static function maintenance($message = null)
    {
        http_response_code(503); // Service Unavailable
        
        // OPS HARDENING: Add request ID header for log correlation
        if (class_exists('AppErrorHandler') && !headers_sent()) {
            header('X-Request-Id: ' . AppErrorHandler::getRequestId());
            header('Retry-After: 3600'); // Suggest retry after 1 hour
        }
        
        $maintenanceMessage = $message ?? 'Sistem bakımda. Lütfen daha sonra tekrar deneyin.';
        include __DIR__ . '/../Views/errors/maintenance.php';
        exit;
    }
    
    /**
     * 403 sayfası
     */
    public static function forbidden($message = 'Bu sayfaya eri�Yim yetkiniz yok')
    {
        // Debug logging
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $caller = $trace[1] ?? null;
        error_log("View::forbidden() called from: " . ($caller['file'] ?? 'unknown') . ":" . ($caller['line'] ?? 'unknown') . " - " . ($caller['function'] ?? 'unknown'));
        error_log("View::forbidden() - URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ", Role: " . (class_exists('Auth') && Auth::check() ? Auth::role() : 'not authenticated'));
        
        self::error($message, 403);
    }
}

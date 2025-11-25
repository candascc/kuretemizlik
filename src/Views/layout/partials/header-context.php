<?php

require_once __DIR__ . '/../../../Lib/Utils.php';

/**
 * Build header context for the internal application layout.
 *
 * @param array $options
 * @return array
 */
function build_app_header_context(array $options = []): array
{
    // ===== LOGIN_500_PATHC: Log header context start =====
    if (class_exists('PathCLogger')) {
        PathCLogger::log('HEADER_CONTEXT_START', []);
    }
    // ===== LOGIN_500_PATHC END =====
    
    // ===== LOGIN_500_STAGE1: Log header context build start =====
    $logFile = __DIR__ . '/../../../logs/login_500_trace.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $sessionId = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
    $sessionStatus = session_status();
    $userId = 'none';
    $authCheck = false;
    // ===== LOGIN_500_STAGE1_ROLE: Add role info =====
    $sessionRole = $_SESSION['role'] ?? null;
    $sessionRoleNormalized = $sessionRole ? strtoupper(trim($sessionRole)) : 'null';
    $sessionUsername = $_SESSION['username'] ?? null;
    $authRole = null;
    $authRoleNormalized = 'null';
    $dbRole = null;
    $dbRoleNormalized = 'null';
    $isAdminLike = false;
    // ===== LOGIN_500_STAGE1_ROLE END =====
    try {
        if (class_exists('Auth') && Auth::check()) {
            $authCheck = true;
            $userId = Auth::id();
            // ===== LOGIN_500_STAGE1_ROLE: Get role info =====
            $authRole = Auth::role();
            $authRoleNormalized = $authRole ? strtoupper(trim($authRole)) : 'null';
            $authUser = Auth::user();
            if ($authUser) {
                $dbRole = $authUser['role'] ?? null;
                $dbRoleNormalized = $dbRole ? strtoupper(trim($dbRole)) : 'null';
            }
            $isAdminLike = $authRole ? in_array(strtoupper(trim($authRole)), ['ADMIN', 'SUPERADMIN'], true) : false;
            // ===== LOGIN_500_STAGE1_ROLE END =====
        }
    } catch (Throwable $e) {
        // Ignore
    }
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [build_app_header_context] START uri={$requestUri}, session_id={$sessionId}, session_status={$sessionStatus}, user_id={$userId}, Auth::check()=" . ($authCheck ? 'true' : 'false') . ", session_role={$sessionRoleNormalized}, Auth::role()={$authRoleNormalized}, db_role={$dbRoleNormalized}, username={$sessionUsername}, is_admin_like=" . ($isAdminLike ? '1' : '0') . "\n", FILE_APPEND | LOCK_EX);
    // ===== LOGIN_500_STAGE1 END =====
    
    // ROUND 48: Global bootstrap 500 trace - log header context build start
    $r48_log_file = __DIR__ . '/../../../logs/bootstrap_r48.log';
    $r48_timestamp = date('Y-m-d H:i:s');
    $r48_request_id = uniqid('req_', true);
    @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] BUILD_HEADER_CONTEXT_START\n", FILE_APPEND | LOCK_EX);
    
    // ROUND 50: Targeted runtime trace for /app first-load scenario
    $r50_app_log = __DIR__ . '/../../../logs/r50_app_firstload.log';
    $r50_log_dir = dirname($r50_app_log);
    if (!is_dir($r50_log_dir)) {
        @mkdir($r50_log_dir, 0755, true);
    }
    // ===== PATH_ISAPP_KILLVAR: Use helper function instead of local $isAppRequest variable =====
    if (function_exists('kureapp_is_app_request') && kureapp_is_app_request()) {
        $userId = class_exists('Auth') && Auth::check() ? Auth::id() : 'none';
        @file_put_contents($r50_app_log, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_CTX_R50_START: uri={$requestUri}, user_id={$userId}\n", FILE_APPEND | LOCK_EX);
    }
    
    // ROUND 48: Hardening - Outer try/catch for complete protection
    try {
        $useHeaderManager = $options['useHeaderManager'] ?? true;

    if ($useHeaderManager && class_exists('HeaderManager')) {
        @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_MANAGER_BOOTSTRAP_START\n", FILE_APPEND | LOCK_EX);
        try {
            HeaderManager::bootstrap();
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_MANAGER_BOOTSTRAP_SUCCESS\n", FILE_APPEND | LOCK_EX);
            
            // ===== LOGIN_500_PATHC: Log after HeaderManager =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('HEADER_CONTEXT_AFTER_HEADERMANAGER', []);
            }
            // ===== LOGIN_500_PATHC END =====
        } catch (Exception $e) {
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_MANAGER_BOOTSTRAP_EXCEPTION: " . $e->getMessage() . ", file=" . $e->getFile() . ", line=" . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
            throw $e;
        }
    }

    // ROUND 48: Hardening - HeaderManager calls with safe defaults
    $currentMode = $options['currentMode'] ?? 'operations'; // safe default
    if ($useHeaderManager && class_exists('HeaderManager') && !isset($options['currentMode'])) {
        try {
            $currentMode = HeaderManager::getCurrentMode() ?? 'operations';
        } catch (Throwable $e) {
            if (class_exists('Logger')) {
                Logger::warning('HeaderManager::getCurrentMode failed', ['error' => $e->getMessage()]);
            }
            $currentMode = $options['mode'] ?? 'operations';
        }
    }

    $modeMeta = $options['modeMeta'] ?? [
        'theme' => [
            'class' => 'mode-default',
            'nav_gradient' => 'from-primary-600 to-primary-700',
            'quick_action' => 'variant-operations',
            'context_bg' => 'bg-primary-900/10',
            'accent' => 'text-white',
        ],
    ]; // safe default
    if ($useHeaderManager && class_exists('HeaderManager') && !isset($options['modeMeta'])) {
        try {
            $modeMeta = HeaderManager::getModeMeta($currentMode) ?? $modeMeta;
        } catch (Throwable $e) {
            if (class_exists('Logger')) {
                Logger::warning('HeaderManager::getModeMeta failed', ['error' => $e->getMessage()]);
            }
            $modeMeta = $options['modeMetaOverride'] ?? $modeMeta;
        }
    }

    $availableModes = $options['availableModes'] ?? []; // safe default
    if ($useHeaderManager && class_exists('HeaderManager') && !isset($options['availableModes'])) {
        try {
            $availableModes = HeaderManager::getModes() ?? [];
        } catch (Throwable $e) {
            if (class_exists('Logger')) {
                Logger::warning('HeaderManager::getModes failed', ['error' => $e->getMessage()]);
            }
            $availableModes = $options['availableModesOverride'] ?? [];
        }
    }

    $currentRole = $options['role'] ?? null; // safe default
    if ($useHeaderManager && class_exists('HeaderManager') && !isset($options['role'])) {
        try {
            $currentRole = HeaderManager::getCurrentRole();
        } catch (Throwable $e) {
            if (class_exists('Logger')) {
                Logger::warning('HeaderManager::getCurrentRole failed', ['error' => $e->getMessage()]);
            }
        }
    }
    // ===== LOGIN_500_STAGE2: Fallback to Auth::role() if HeaderManager failed =====
    if ($currentRole === null && class_exists('Auth') && Auth::check()) {
        try {
            $currentRole = Auth::role();
        } catch (Throwable $e) {
            // Ignore - $currentRole remains null
        }
    }
    // ===== LOGIN_500_STAGE2 END =====

    $navigationItems = $options['navigationItems'] ?? []; // safe default
    if ($useHeaderManager && class_exists('HeaderManager') && !isset($options['navigationItems'])) {
        try {
            $navigationItems = HeaderManager::getNavigationItems($currentRole, $currentMode) ?? [];
        } catch (Throwable $e) {
            if (class_exists('Logger')) {
                Logger::warning('HeaderManager::getNavigationItems failed', ['error' => $e->getMessage()]);
            }
            $navigationItems = $options['navigationItemsOverride'] ?? [];
        }
    }

    $quickActions = $options['quickActions'] ?? []; // safe default
    if ($useHeaderManager && class_exists('HeaderManager') && !isset($options['quickActions'])) {
        try {
            $quickActions = HeaderManager::getQuickActions($currentRole, $currentMode) ?? [];
        } catch (Throwable $e) {
            if (class_exists('Logger')) {
                Logger::warning('HeaderManager::getQuickActions failed', ['error' => $e->getMessage()]);
            }
            $quickActions = $options['quickActionsOverride'] ?? [];
        }
    }

    $currentPathRaw = $options['currentPathRaw']
        ?? (function () {
            $path = $_SERVER['REQUEST_URI'] ?? '/';
            return trim(str_replace(APP_BASE, '', $path), '/');
        })();

    $currentPathSegments = $options['currentPathSegments']
        ?? ($currentPathRaw === '' ? [] : explode('/', $currentPathRaw));

    $currentPath = $currentPathSegments[0] ?? '';

    $companyFilterParam = $options['companyFilterParam']
        ?? (isset($_GET['company_filter']) && $_GET['company_filter'] !== '' ? (int)$_GET['company_filter'] : null);

    // ROUND 48: Hardening - getContextLinks with null checks
    $contextLinks = $options['contextLinks'] ?? []; // safe default
    if ($useHeaderManager && class_exists('HeaderManager') && !isset($options['contextLinks'])) {
        @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_MANAGER_GET_CONTEXT_LINKS_START\n", FILE_APPEND | LOCK_EX);
        try {
            // Null checks before calling
            $currentPathSegments = $currentPathSegments ?? [];
            $companyFilterParam = $companyFilterParam ?? null;
            $currentMode = $currentMode ?? 'operations';
            if (is_array($currentPathSegments) && ($companyFilterParam === null || is_int($companyFilterParam))) {
                $result = HeaderManager::getContextLinks($currentPathSegments, $companyFilterParam, $currentMode, $currentRole);
                $contextLinks = $result ?? [];
                @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_MANAGER_GET_CONTEXT_LINKS_SUCCESS: count=" . count($contextLinks) . "\n", FILE_APPEND | LOCK_EX);
            } else {
                @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_MANAGER_GET_CONTEXT_LINKS_SKIP: invalid params\n", FILE_APPEND | LOCK_EX);
            }
        } catch (Throwable $e) {
            @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_MANAGER_GET_CONTEXT_LINKS_EXCEPTION: " . $e->getMessage() . ", file=" . $e->getFile() . ", line=" . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
            if (class_exists('Logger')) {
                Logger::warning('HeaderManager::getContextLinks failed', ['error' => $e->getMessage()]);
            }
            $contextLinks = []; // safe default
        }
    }

    $appendCompanyUrl = static function (string $path) use ($companyFilterParam): string {
        $url = base_url($path);
        if ($companyFilterParam) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'company_filter=' . $companyFilterParam;
        }
        return $url;
    };

    if (empty($contextLinks)) {
        $buildingPaths = ['buildings', 'units', 'management-fees', 'expenses', 'documents', 'meetings', 'announcements', 'surveys', 'facilities', 'reservations', 'building-reports', 'building'];
        if (in_array($currentPath, $buildingPaths, true)) {
            $contextLinks = [
                ['label' => 'Binalar', 'icon' => 'fa-building', 'url' => $appendCompanyUrl('/buildings'), 'active' => $currentPath === 'buildings'],
                ['label' => 'Daireler', 'icon' => 'fa-home', 'url' => $appendCompanyUrl('/units'), 'active' => $currentPath === 'units'],
                ['label' => 'Aidatlar', 'icon' => 'fa-money-bill', 'url' => $appendCompanyUrl('/management-fees'), 'active' => $currentPath === 'management-fees'],
                ['label' => 'Giderler', 'icon' => 'fa-receipt', 'url' => $appendCompanyUrl('/expenses'), 'active' => $currentPath === 'expenses'],
                ['label' => 'Toplantılar', 'icon' => 'fa-people-group', 'url' => $appendCompanyUrl('/meetings'), 'active' => $currentPath === 'meetings'],
                ['label' => 'Rezervasyonlar', 'icon' => 'fa-door-open', 'url' => $appendCompanyUrl('/reservations'), 'active' => $currentPath === 'reservations'],
                ['label' => 'Raporlar', 'icon' => 'fa-chart-line', 'url' => $appendCompanyUrl('/building-reports/financial'), 'active' => $currentPath === 'building-reports'],
            ];
        }
    }

    if (empty($contextLinks)) {
        $operationsPaths = ['calendar', 'jobs', 'recurring', 'services'];
        if (in_array($currentPath, $operationsPaths, true)) {
            $contextLinks = [
                ['label' => 'Takvim', 'icon' => 'fa-calendar', 'url' => base_url('/calendar'), 'active' => $currentPath === 'calendar'],
                ['label' => 'İşler', 'icon' => 'fa-tasks', 'url' => base_url('/jobs'), 'active' => $currentPath === 'jobs'],
                ['label' => 'Periyodik İşler', 'icon' => 'fa-redo', 'url' => base_url('/recurring'), 'active' => $currentPath === 'recurring'],
                ['label' => 'Hizmetler', 'icon' => 'fa-concierge-bell', 'url' => base_url('/services'), 'active' => $currentPath === 'services'],
            ];
        }
    }

    $theme = $modeMeta['theme'] ?? [];
    $navGradient = $theme['nav_gradient'] ?? 'from-primary-600 to-primary-700';
    $navThemeClass = $theme['class'] ?? 'mode-operations';
    $quickActionVariant = $theme['quick_action'] ?? 'variant-operations';
    $contextBackgroundClass = $theme['context_bg'] ?? 'bg-primary-900/10';

    $headerMeta = $options['headerMeta'] ?? [];
    $headerMetaChips = [];
    if (isset($headerMeta['sla'])) {
        $headerMetaChips[] = 'SLA ' . e($headerMeta['sla']);
    }
    if (isset($headerMeta['last_backup'])) {
        $headerMetaChips[] = 'Yedek: ' . e($headerMeta['last_backup']);
    }
    if (isset($headerMeta['version'])) {
        $headerMetaChips[] = e($headerMeta['version']);
    }

    $statusWidgets = $options['statusWidgets'] ?? [
        ['id' => 'sb-cache', 'icon' => 'fa-bolt', 'text' => 'Cache: —'],
        ['id' => 'sb-db', 'icon' => 'fa-database', 'text' => 'DB: —'],
        ['id' => 'sb-disk', 'icon' => 'fa-hdd', 'text' => 'Disk: —'],
        ['id' => 'sb-queue', 'icon' => 'fa-tasks', 'text' => 'Queue: —'],
    ];

    if (!empty($modeMeta['label'])) {
        array_unshift($statusWidgets, [
            'id' => 'sb-mode',
            'icon' => $modeIcons[$currentMode] ?? 'fa-layer-group',
            'text' => $modeMeta['label'],
            'class' => 'mode-badge',
        ]);
    }

    $isSuperAdmin = false;
    if (class_exists('SuperAdmin') && SuperAdmin::isSuperAdmin()) {
        $isSuperAdmin = true;
    } elseif (class_exists('Auth') && method_exists('Auth', 'role')) {
        $isSuperAdmin = Auth::role() === 'SUPERADMIN';
    }

    $defaultUser = [
        'isAuthenticated' => class_exists('Auth') ? Auth::check() : false,
        'username' => class_exists('Auth') ? (Auth::check() ? (Auth::user()['username'] ?? null) : null) : null,
        'logoutUrl' => base_url('/logout'),
        'loginUrl' => base_url('/login'),
        'isSuperAdmin' => $isSuperAdmin,
        'notifications' => [
            'enabled' => true,
            'count' => $options['notificationCount'] ?? ($options['user']['notifications']['count'] ?? 0),
            'items' => $options['notifications'] ?? ($options['user']['notifications']['items'] ?? []),
        ],
        'actions' => [],
    ];

    $user = array_replace_recursive($defaultUser, $options['user'] ?? []);

    $brandDefaultUrl = $currentMode === 'management'
        ? base_url('/management/dashboard')
        : base_url('/');

    $brand = array_merge([
        'label' => 'Küre Temizlik',
        'icon' => null,
        'url' => $brandDefaultUrl,
        'logo' => Utils::asset('img/logokureapp.png'),
        'logo_path' => 'img/logokureapp.png',
        'logo_fallback' => Utils::asset('img/logokureapp.png'),
        'logo_fallback_path' => 'img/logokureapp.png',
    ], $options['brand'] ?? []);

    $systemMenu = $options['systemMenu'] ?? [
        ['label' => 'Rol & Yetki Yönetimi', 'icon' => 'fa-users-cog', 'url' => base_url('/admin/roles')],
        ['label' => 'Önbellek Yönetimi', 'icon' => 'fa-memory', 'url' => base_url('/admin/cache')],
        ['label' => 'Kuyruk Yönetimi', 'icon' => 'fa-tasks', 'url' => base_url('/admin/queue')],
        ['label' => 'Denetim Kayıtları', 'icon' => 'fa-clipboard-list', 'url' => base_url('/audit')],
        ['label' => 'Performans İzleme', 'icon' => 'fa-tachometer-alt', 'url' => base_url('/performance')],
    ];

    $modeIcons = $options['modeIcons'] ?? [
        'operations' => 'fa-cogs',
        'site' => 'fa-building',
        'management' => 'fa-city',
    ];

    $ui = array_merge([
        'showSearch' => true,
        'showStatusChips' => true,
        'showQuickActions' => true,
        'showModeSwitcher' => true,
        'showNotifications' => true,
        'showSystemMenu' => true,
    ], $options['ui'] ?? []);

    return [
        'variant' => 'app',
        'brand' => $brand,
        'mode' => [
            'current' => $currentMode,
            'meta' => $modeMeta,
            'available' => $availableModes,
            'icons' => $modeIcons,
            'theme' => [
                'navGradient' => $navGradient,
                'navClass' => $navThemeClass,
                'quickVariant' => $quickActionVariant,
                'contextBg' => $contextBackgroundClass,
                'accent' => $theme['accent'] ?? 'text-white',
            ],
        ],
        'headerMetaChips' => $headerMetaChips,
        'statusWidgets' => $statusWidgets,
        'quickActions' => $quickActions,
        'navigationItems' => $navigationItems,
        'contextLinks' => $contextLinks,
        'paths' => [
            'currentRaw' => $currentPathRaw,
            'current' => $currentPath,
        ],
        'user' => $user,
        'systemMenu' => $systemMenu,
        'ui' => $ui,
    ];
    
    @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] BUILD_HEADER_CONTEXT_SUCCESS\n", FILE_APPEND | LOCK_EX);
    
    // ===== LOGIN_500_PATHC: Log header context done =====
    if (class_exists('PathCLogger')) {
        PathCLogger::log('HEADER_CONTEXT_DONE', []);
    }
    // ===== LOGIN_500_PATHC END =====
    
    return [
        'variant' => 'app',
        'brand' => $brand,
        'mode' => [
            'current' => $currentMode,
            'meta' => $modeMeta,
            'available' => $availableModes,
            'icons' => $modeIcons,
            'theme' => [
                'navGradient' => $navGradient,
                'navClass' => $navThemeClass,
                'quickVariant' => $quickActionVariant,
                'contextBg' => $contextBackgroundClass,
                'accent' => $theme['accent'] ?? 'text-white',
            ],
        ],
        'headerMetaChips' => $headerMetaChips,
        'statusWidgets' => $statusWidgets,
        'quickActions' => $quickActions,
        'navigationItems' => $navigationItems,
        'contextLinks' => $contextLinks,
        'paths' => [
            'currentRaw' => $currentPathRaw,
            'current' => $currentPath,
        ],
        'user' => $user,
        'systemMenu' => $systemMenu,
        'ui' => $ui,
    ];
    } catch (Throwable $e) {
        // ===== LOGIN_500_PATHC: Log exception =====
        if (class_exists('PathCLogger')) {
            PathCLogger::logException('HEADER_CONTEXT_EXCEPTION', $e, []);
        }
        // ===== LOGIN_500_PATHC END =====
        
        // ===== LOGIN_500_STAGE1: Log exception =====
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $errorMsg = str_replace(["\n", "\r"], [' ', ' '], $e->getMessage());
        $errorFile = $e->getFile();
        $errorLine = $e->getLine();
        $errorClass = get_class($e);
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [build_app_header_context] EXCEPTION uri={$requestUri}, class={$errorClass}, message={$errorMsg}, file={$errorFile}, line={$errorLine}\n", FILE_APPEND | LOCK_EX);
        // ===== LOGIN_500_STAGE1 END =====
        
        // ROUND 52: Log fatal error to r52_view_fatal.log
        $r52_log_file = __DIR__ . '/../../../logs/r52_view_fatal.log';
        $r52_log_dir = dirname($r52_log_file);
        if (!is_dir($r52_log_dir)) {
            @mkdir($r52_log_dir, 0755, true);
        }
        $r52_timestamp = date('Y-m-d H:i:s');
        $r52_request_id = uniqid('req_', true);
        $r52_uri = $_SERVER['REQUEST_URI'] ?? '/';
        $r52_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $r52_user_id = 'none';
        try {
            if (class_exists('Auth') && Auth::check()) {
                $r52_user_id = Auth::id();
            }
        } catch (Throwable $authErr) {
            // Ignore
        }
        $r52_trace = $e->getTraceAsString();
        $r52_trace_lines = explode("\n", $r52_trace);
        $r52_short_trace = implode("\n", array_slice($r52_trace_lines, 0, 10));
        if (strlen($r52_short_trace) > 1000) {
            $r52_short_trace = substr($r52_short_trace, 0, 1000) . '...';
        }
        $r52_class = get_class($e);
        $r52_message = str_replace(["\n", "\r"], [' ', ' '], $e->getMessage());
        $r52_file = $e->getFile();
        $r52_line = $e->getLine();
        $r52_log_entry = "[{$r52_timestamp}] [{$r52_request_id}] R52_VIEW_FATAL uri={$r52_uri} method={$r52_method} user_id={$r52_user_id} view=header-context layout=base class={$r52_class} message={$r52_message} file={$r52_file} line={$r52_line} trace={$r52_short_trace}\n";
        @file_put_contents($r52_log_file, $r52_log_entry, FILE_APPEND | LOCK_EX);
        
        // ROUND 48: Outer catch - ultimate fallback if everything fails
        @file_put_contents($r48_log_file, "[{$r48_timestamp}] [{$r48_request_id}] BUILD_HEADER_CONTEXT_EXCEPTION: " . $e->getMessage() . ", file=" . $e->getFile() . ", line=" . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
        
        // ROUND 50: Targeted runtime trace for /app first-load scenario
        // ===== PATH_ISAPP_KILLVAR: Use helper function instead of local $isAppRequest variable =====
        if (function_exists('kureapp_is_app_request') && kureapp_is_app_request()) {
            $errorMsg = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            $errorTrace = substr($e->getTraceAsString(), 0, 2000);
            @file_put_contents($r50_app_log, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_CTX_R50_EXCEPTION: message={$errorMsg}, file={$errorFile}, line={$errorLine}\nTRACE:\n{$errorTrace}\n---\n", FILE_APPEND | LOCK_EX);
        }
        
        if (class_exists('Logger')) {
            Logger::error('build_app_header_context failed', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => substr($e->getTraceAsString(), 0, 1000)]);
        } else {
            error_log("build_app_header_context failed: {$e->getMessage()}");
        }
        // Minimum viable header context
        return [
            'variant' => 'app',
            'brand' => ['label' => 'Küre Temizlik', 'url' => base_url('/')],
            'mode' => ['current' => 'operations', 'meta' => [], 'available' => [], 'icons' => [], 'theme' => []],
            'headerMetaChips' => [],
            'statusWidgets' => [],
            'quickActions' => [],
            'navigationItems' => [],
            'contextLinks' => [],
            'paths' => ['currentRaw' => '', 'current' => ''],
            'user' => [],
            'systemMenu' => [],
            'ui' => ['showSearch' => false, 'showStatusChips' => false, 'showQuickActions' => false, 'showModeSwitcher' => false, 'showNotifications' => false, 'showSystemMenu' => false],
        ];
    }
    
    // ROUND 50: Targeted runtime trace - success log for /app requests
    // ===== PATH_ISAPP_KILLVAR: Use helper function instead of local $isAppRequest variable =====
    if (function_exists('kureapp_is_app_request') && kureapp_is_app_request()) {
        @file_put_contents($r50_app_log, "[{$r48_timestamp}] [{$r48_request_id}] HEADER_CTX_R50_SUCCESS\n", FILE_APPEND | LOCK_EX);
    }
    
    // ===== LOGIN_500_STAGE1: Log success =====
    $finalSessionId = session_id() ? substr(session_id(), 0, 12) . '...' : 'none';
    $finalStatus = session_status();
    $finalUserId = 'none';
    $finalAuthCheck = false;
    // ===== LOGIN_500_STAGE1_ROLE: Add role info =====
    $finalSessionRole = $_SESSION['role'] ?? null;
    $finalSessionRoleNormalized = $finalSessionRole ? strtoupper(trim($finalSessionRole)) : 'null';
    $finalSessionUsername = $_SESSION['username'] ?? null;
    $finalAuthRole = null;
    $finalAuthRoleNormalized = 'null';
    $finalDbRole = null;
    $finalDbRoleNormalized = 'null';
    $finalIsAdminLike = false;
    // ===== LOGIN_500_STAGE1_ROLE END =====
    try {
        if (class_exists('Auth') && Auth::check()) {
            $finalAuthCheck = true;
            $finalUserId = Auth::id();
            // ===== LOGIN_500_STAGE1_ROLE: Get role info =====
            $finalAuthRole = Auth::role();
            $finalAuthRoleNormalized = $finalAuthRole ? strtoupper(trim($finalAuthRole)) : 'null';
            $finalAuthUser = Auth::user();
            if ($finalAuthUser) {
                $finalDbRole = $finalAuthUser['role'] ?? null;
                $finalDbRoleNormalized = $finalDbRole ? strtoupper(trim($finalDbRole)) : 'null';
            }
            $finalIsAdminLike = $finalAuthRole ? in_array(strtoupper(trim($finalAuthRole)), ['ADMIN', 'SUPERADMIN'], true) : false;
            // ===== LOGIN_500_STAGE1_ROLE END =====
        }
    } catch (Throwable $e) {
        // Ignore
    }
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " [STAGE1] [build_app_header_context] SUCCESS uri={$requestUri}, session_id={$finalSessionId}, session_status={$finalStatus}, user_id={$finalUserId}, Auth::check()=" . ($finalAuthCheck ? 'true' : 'false') . ", session_role={$finalSessionRoleNormalized}, Auth::role()={$finalAuthRoleNormalized}, db_role={$finalDbRoleNormalized}, username={$finalSessionUsername}, is_admin_like=" . ($finalIsAdminLike ? '1' : '0') . "\n", FILE_APPEND | LOCK_EX);
    // ===== LOGIN_500_STAGE1 END =====
}

/**
 * Build header context for the customer portal layout.
 *
 * @param array $options
 * @return array
 */
function build_portal_header_context(array $options = []): array
{
    $navigationItems = $options['navigationItems'] ?? [];

    $currentPathRaw = trim(str_replace(APP_BASE, '', $_SERVER['REQUEST_URI'] ?? '/'), '/');
    $modeTheme = [
        'class' => 'mode-portal',
        'navGradient' => $options['navGradient'] ?? 'from-blue-600 to-blue-700',
        'quickVariant' => '',
        'contextBg' => $options['contextBg'] ?? 'bg-blue-900/20',
        'accent' => 'text-white',
    ];

    $user = array_merge([
        'isAuthenticated' => true,
        'username' => $options['user']['username'] ?? null,
        'logoutUrl' => $options['user']['logoutUrl'] ?? base_url('/portal/logout'),
        'loginUrl' => $options['user']['loginUrl'] ?? base_url('/portal/login'),
        'isSuperAdmin' => false,
        'notifications' => [
            'enabled' => false,
            'count' => 0,
            'items' => [],
        ],
        'actions' => $options['user']['actions'] ?? [],
    ], $options['user'] ?? []);

    $quickActions = $options['quickActions'] ?? [];

    return [
        'variant' => 'portal',
        'brand' => [
            'label' => $options['brand']['label'] ?? 'Customer Portal',
            'icon' => $options['brand']['icon'] ?? 'fas fa-home',
            'url' => $options['brand']['url'] ?? base_url('/portal/dashboard'),
        ],
        'mode' => [
            'current' => 'portal',
            'meta' => [
                'theme' => [
                    'class' => $modeTheme['class'],
                    'nav_gradient' => $modeTheme['navGradient'],
                    'quick_action' => $modeTheme['quickVariant'],
                    'context_bg' => $modeTheme['contextBg'],
                    'accent' => $modeTheme['accent'],
                ],
            ],
            'available' => [],
            'icons' => [],
            'theme' => $modeTheme,
        ],
        'headerMetaChips' => [],
        'statusWidgets' => $options['statusWidgets'] ?? [],
        'quickActions' => $quickActions,
        'navigationItems' => $navigationItems,
        'contextLinks' => $options['contextLinks'] ?? [],
        'paths' => [
            'currentRaw' => $currentPathRaw,
            'current' => explode('/', $currentPathRaw)[0] ?? '',
        ],
        'user' => $user,
        'systemMenu' => [],
        'ui' => array_merge([
            'showSearch' => $options['ui']['showSearch'] ?? false,
            'showStatusChips' => $options['ui']['showStatusChips'] ?? false,
            'showQuickActions' => !empty($quickActions),
            'showModeSwitcher' => false,
            'showNotifications' => false,
            'showSystemMenu' => false,
        ], $options['ui'] ?? []),
    ];
}

/**
 * Build header context for resident (sakin) portal.
 *
 * @param array $options
 * @return array
 */
function build_resident_header_context(array $options = []): array
{
    $currentPathRaw = trim(str_replace(APP_BASE, '', $_SERVER['REQUEST_URI'] ?? '/'), '/');
    $currentPath = explode('/', $currentPathRaw)[0] ?? '';
    $activeKey = $options['activeKey'] ?? null;

    $resident = $options['resident'] ?? [];
    $unitLabel = $resident['unitLabel'] ?? null;
    $residentName = $resident['name'] ?? null;
    $lastLoginAt = $resident['lastLoginAt'] ?? null;

    $navigationItems = $options['navigationItems'] ?? [
        ['key' => 'resident-dashboard', 'label' => 'Ana Sayfa', 'icon' => 'fa-home', 'url' => '/resident/dashboard'],
        ['key' => 'resident-fees', 'label' => 'Aidatlar', 'icon' => 'fa-credit-card', 'url' => '/resident/fees'],
        ['key' => 'resident-requests', 'label' => 'Taleplerim', 'icon' => 'fa-screwdriver-wrench', 'url' => '/resident/requests'],
        ['key' => 'resident-announcements', 'label' => 'Duyurular', 'icon' => 'fa-bullhorn', 'url' => '/resident/announcements'],
        ['key' => 'resident-meetings', 'label' => 'Toplantılar', 'icon' => 'fa-people-group', 'url' => '/resident/meetings'],
        ['key' => 'resident-profile', 'label' => 'Profil', 'icon' => 'fa-user-circle', 'url' => '/resident/profile'],
    ];

    $headerManagerAvailable = class_exists('HeaderManager');

    foreach ($navigationItems as &$navItem) {
        $navKey = $navItem['key'] ?? null;
        if ($activeKey) {
            $navItem['active'] = ($navKey === $activeKey);
        } elseif (!empty($navItem['url']) && $headerManagerAvailable) {
            $navItem['active'] = HeaderManager::isActive($currentPathRaw, $navItem['url']);
        } else {
            $navItem['active'] = false;
        }
    }
    unset($navItem);

    $brand = array_merge([
        'label' => $options['brand']['label'] ?? 'Sakin Portalı',
        'icon' => $options['brand']['icon'] ?? 'fas fa-house-user',
        'url' => $options['brand']['url'] ?? base_url('/resident/dashboard'),
    ], $options['brand'] ?? []);

    $navGradientStyle = $options['navGradientStyle'] ?? 'background-image: linear-gradient(115deg, #5c83ff 0%, #68cfff 50%, #60f5c3 100%);';

    $modeTheme = [
        'class' => 'mode-resident',
        'navGradient' => $options['navGradient'] ?? 'from-primary-600 to-primary-700',
        'quickVariant' => 'variant-operations',
        'contextBg' => $options['contextBg'] ?? 'bg-primary-900/15',
        'accent' => 'text-white',
        'inlineGradient' => $navGradientStyle,
    ];

    $notifications = [];
    $notificationCount = 0;
    if (!empty($unit['id']) && !empty($building['id'])) {
        try {
            $metricsService = new ResidentPortalMetricsService();
            $metrics = $metricsService->getDashboardMetrics((int)$unit['id'], (int)$building['id']);

            $pendingFees = (float)($metrics['pendingFees']['outstanding'] ?? 0.0);
            $pendingFeeCount = (int)($metrics['pendingFees']['count'] ?? 0);
            if ($pendingFees > 0) {
                $notifications[] = [
                    'key' => 'resident-fees',
                    'text' => '₺' . number_format($pendingFees, 2, ',', '.') . ' aidat borcunuz var',
                    'meta' => $pendingFeeCount === 1 ? '1 bekleyen aidat' : sprintf('%d bekleyen aidat', $pendingFeeCount),
                    'href' => base_url('/resident/fees'),
                    'type' => 'critical',
                    'icon' => 'fa-wallet',
                    'read' => false,
                ];
            }

            $openRequests = (int)($metrics['openRequests'] ?? 0);
            if ($openRequests > 0) {
                $notifications[] = [
                    'key' => 'resident-requests',
                    'text' => sprintf('%d talebiniz işlem aşamasında', $openRequests),
                    'meta' => 'Talep Takibi',
                    'href' => base_url('/resident/requests'),
                    'type' => 'ops',
                    'icon' => 'fa-screwdriver-wrench',
                    'read' => false,
                ];
            }

            $upcomingMeetings = (int)($metrics['meetings'] ?? 0);
            if ($upcomingMeetings > 0) {
                $notifications[] = [
                    'key' => 'resident-meetings',
                    'text' => sprintf('%d toplantı yaklaşıyor', $upcomingMeetings),
                    'meta' => 'Gündemi kontrol edin',
                    'href' => base_url('/resident/meetings'),
                    'type' => 'system',
                    'icon' => 'fa-calendar-check',
                    'read' => false,
                ];
            }

            $announcements = (int)($metrics['announcements'] ?? 0);
            if ($announcements > 0) {
                $notifications[] = [
                    'key' => 'resident-announcements',
                    'text' => sprintf('%d yeni duyuru yayınlandı', $announcements),
                    'meta' => 'Duyuru Panosu',
                    'href' => base_url('/resident/announcements'),
                    'type' => 'system',
                    'icon' => 'fa-bullhorn',
                    'read' => false,
                ];
            }
        } catch (Throwable $e) {
            if (class_exists('Logger')) {
                Logger::warning('[ResidentHeader] Dashboard metrics unavailable: ' . $e->getMessage());
            }
        }

        $notificationCount = count($notifications);
    }

    $userDefaults = [
        'isAuthenticated' => true,
        'username' => $residentName,
        'logoutUrl' => $options['user']['logoutUrl'] ?? base_url('/resident/logout'),
        'loginUrl' => $options['user']['loginUrl'] ?? base_url('/resident/login'),
        'showProfile' => false,
        'notifications' => [
            'enabled' => $notificationCount > 0,
            'count' => $notificationCount,
            'items' => $notifications,
        ],
        'showNotifications' => true,
        'chipMeta' => array_filter([
            $unitLabel,
            $lastLoginAt ? 'Son giriş: ' . Utils::formatDateTime($lastLoginAt) : null,
        ]),
    ];
    $user = array_merge($userDefaults, $options['user'] ?? []);
    if (!isset($user['notifications'])) {
        $user['notifications'] = $userDefaults['notifications'];
    }

    return [
        'variant' => 'resident',
        'brand' => $brand,
        'mode' => [
            'current' => 'resident',
            'meta' => [
                'label' => 'Sakin Portalı',
                'theme' => [
                    'class' => $modeTheme['class'],
                    'nav_gradient' => $modeTheme['navGradient'],
                    'quick_action' => $modeTheme['quickVariant'],
                    'context_bg' => $modeTheme['contextBg'],
                    'accent' => $modeTheme['accent'],
                    'inlineGradient' => $navGradientStyle,
                ],
            ],
            'available' => [],
            'icons' => ['resident' => 'fa-house-user'],
            'theme' => $modeTheme,
        ],
        'navGradientStyle' => $navGradientStyle,
        'headerMetaChips' => [],
        'statusWidgets' => [],
        'quickActions' => [],
        'navigationItems' => $navigationItems,
        'contextLinks' => [],
        'paths' => [
            'currentRaw' => $currentPathRaw,
            'current' => $currentPath,
        ],
        'user' => $user,
        'systemMenu' => [],
        'ui' => array_merge([
            'showSearch' => false,
            'showStatusChips' => false,
            'showQuickActions' => false,
            'showModeSwitcher' => false,
            'showNotifications' => true,
            'showSystemMenu' => false,
        ], $options['ui'] ?? []),
    ];
}



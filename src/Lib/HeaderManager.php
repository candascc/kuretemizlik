<?php

class HeaderManager
{
    private const SESSION_MODE_KEY = 'app_header_mode';
    private const QUERY_MODE_KEY = 'header_mode';
    private const COOKIE_MODE_KEY = 'app_header_mode';
    private const COOKIE_TTL = 60 * 60 * 24 * 30; // 30 gün
    private const MODE_ALIASES = [
        'site' => 'management',
    ];
    private static array $config = [];

    public static function bootstrap(): void
    {
        // ROUND 13: Fix header_mode=operations 500 error
        // Wrap entire bootstrap in try/catch to prevent fatal errors
        try {
            self::loadConfig();
            
            // ===== CRITICAL FIX: Ensure session is started with correct cookie path =====
            // Use SessionHelper for centralized session management
            SessionHelper::ensureStarted();
            // ===== CRITICAL FIX END =====

            $queryMode = self::normalizeMode($_GET[self::QUERY_MODE_KEY] ?? null);
            if ($queryMode && self::isValidMode($queryMode)) {
                self::rememberMode($queryMode);
                return;
            }

            $cookieMode = self::normalizeMode($_COOKIE[self::COOKIE_MODE_KEY] ?? null);
            if ($cookieMode && self::isValidMode($cookieMode)) {
                self::rememberMode($cookieMode, false);
            }
        } catch (Throwable $e) {
            // ROUND 13: Prevent 500 error on header_mode=operations
            // Log error but continue gracefully (default mode will be used)
            error_log("HeaderManager::bootstrap() error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            // Continue with default mode - don't break the page
        }
    }

    public static function getCurrentMode(): string
    {
        // ===== CRITICAL FIX: Ensure session is active before accessing $_SESSION =====
        // Use SessionHelper for centralized session management
        if (!SessionHelper::ensureStarted()) {
            // Return default mode if session can't be started
            return 'operations';
        }
        // ===== CRITICAL FIX END =====
        
        $default = self::getConfigValue('default_mode', 'operations');
        $mode = self::normalizeMode($_SESSION[self::SESSION_MODE_KEY] ?? null);
        if ($mode && self::isValidMode($mode)) {
            return $mode;
        }

        $normalizedDefault = self::normalizeMode($default) ?? 'operations';
        self::rememberMode($normalizedDefault, false);
        return $normalizedDefault;
    }

    public static function rememberMode(string $mode, bool $setCookie = true): void
    {
        $mode = self::normalizeMode($mode) ?? $mode;
        if (!self::isValidMode($mode)) {
            return;
        }
        
        // ===== CRITICAL FIX: Ensure session is active before accessing $_SESSION =====
        // Use SessionHelper for centralized session management
        if (!SessionHelper::ensureStarted()) {
            // If session can't be started, can't store in session
            return;
        }
        // ===== CRITICAL FIX END =====
        
        $_SESSION[self::SESSION_MODE_KEY] = $mode;
        if ($setCookie && !headers_sent()) {
            // ===== CRITICAL FIX: Use APP_BASE not root path =====
            $cookiePath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            
            @setcookie(self::COOKIE_MODE_KEY, $mode, [
                'expires' => time() + self::COOKIE_TTL,
                'path' => $cookiePath, // CRITICAL: Use APP_BASE, not root
                'domain' => null,
                'secure' => $isHttps ? 1 : 0,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
            // ===== CRITICAL FIX END =====
        }
    }

    public static function getModes(): array
    {
        return self::getConfigValue('modes', []);
    }

    public static function getModeMeta(string $mode): array
    {
        $modes = self::getModes();
        $meta = $modes[$mode] ?? [];
        $defaults = [
            'label' => ucfirst($mode),
            'short_label' => ucfirst($mode),
            'description' => '',
            'theme' => [
                'class' => 'mode-default',
                'nav_gradient' => 'from-primary-600 to-primary-700',
                'accent' => 'text-primary-100',
                'chip_bg' => 'bg-white/15',
                'chip_border' => 'border-white/20',
                'quick_action' => 'variant-operations',
                'context_bg' => 'bg-primary-900/10',
            ],
        ];
        $meta = array_replace_recursive($defaults, $meta);
        return $meta;
    }

    public static function getModeToggleUrl(string $targetMode): string
    {
        $normalizedTarget = self::normalizeMode($targetMode) ?? $targetMode;

        $modeRedirects = [
            'operations' => base_url('/'),
            'management' => base_url('/management/dashboard'),
        ];

        $baseUrl = $modeRedirects[$normalizedTarget] ?? base_url('/');

        $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';
        return $baseUrl . $separator . http_build_query([self::QUERY_MODE_KEY => $normalizedTarget]);
    }

    public static function getCurrentRole(): ?string
    {
        if (class_exists('Auth') && method_exists('Auth', 'role')) {
            return Auth::role();
        }
        return null;
    }

    public static function getNavigationItems(?string $role, string $mode): array
    {
        $items = self::getConfigValue('navigation', []);
        $filtered = [];
        foreach ($items as $item) {
            if (!self::isItemAllowed($item, $role, $mode)) {
                continue;
            }
            $processed = $item;
            if (!empty($item['children']) && is_array($item['children'])) {
                $children = [];
                foreach ($item['children'] as $child) {
                    if (self::isItemAllowed($child, $role, $mode)) {
                        $children[] = $child;
                    }
                }
                $processed['children'] = $children;
                if (empty($processed['children'])) {
                    unset($processed['children']);
                }
            }
            $filtered[] = $processed;
        }
        return $filtered;
    }

    public static function getQuickActions(?string $role, string $mode): array
    {
        $actions = self::getConfigValue('quick_actions', []);
        $filtered = [];
        foreach ($actions as $action) {
            if (self::isItemAllowed($action, $role, $mode)) {
                $filtered[] = $action;
            }
        }
        return $filtered;
    }

    public static function getContextLinks(
        array $currentPathSegments,
        ?int $companyFilterParam,
        string $mode,
        ?string $role
    ): array {
        $contextConfig = self::getConfigValue('context_links', []);
        if (empty($currentPathSegments)) {
            return [];
        }
        $section = $currentPathSegments[0];
        $secondarySection = $currentPathSegments[1] ?? null;
        $links = [];

        $modeKey = self::normalizeMode($mode) ?? $mode;

        if ($secondarySection) {
            if (isset($contextConfig['operations'][$secondarySection])) {
                $links = self::buildContextLinks([$secondarySection => $contextConfig['operations'][$secondarySection]], $secondarySection, $companyFilterParam);
                if (!empty($links)) {
                    return $links;
                }
            }

            if (isset($contextConfig[$modeKey][$secondarySection])) {
                return self::buildContextLinks([$secondarySection => $contextConfig[$modeKey][$secondarySection]], $secondarySection, $companyFilterParam);
            }
        }

        if (isset($contextConfig['operations'][$section])) {
            $links = self::buildContextLinks($contextConfig['operations'], $section, $companyFilterParam);
        } elseif (isset($contextConfig[$modeKey][$section])) {
            $links = self::buildContextLinks($contextConfig[$modeKey], $section, $companyFilterParam);
        } elseif ($modeKey === 'management') {
            $links = self::buildContextLinks($contextConfig['management'] ?? [], $section, $companyFilterParam);
        }

        return $links;
    }

    public static function formatUrl(string $path, ?int $companyFilterParam = null): string
    {
        $url = base_url($path);
        if ($companyFilterParam) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'company_filter=' . $companyFilterParam;
        }
        return $url;
    }

    public static function isActive(string $currentPath, string $itemUrl): bool
    {
        $normalized = trim($currentPath, '/');
        $itemPath = trim($itemUrl, '/');
        if ($itemPath === '') {
            return $normalized === '';
        }
        return strpos($normalized, $itemPath) === 0;
    }

    private static function buildContextLinks(array $contextGroup, string $activeKey, ?int $companyFilter): array
    {
        $links = [];
        $isList = static function (array $array): bool {
            return $array === [] || array_keys($array) === range(0, count($array) - 1);
        };

        foreach ($contextGroup as $key => $definition) {
            if (!is_array($definition) || $definition === []) {
                continue;
            }

            $entries = [];
            if ($isList($definition)) {
                $entries[] = [$key, $definition];
            } else {
                $index = 0;
                foreach ($definition as $subKey => $subDefinition) {
                    if (!is_array($subDefinition) || $subDefinition === []) {
                        continue;
                    }
                    $entryKey = $index === 0 ? $key : $key . '-' . $subKey;
                    $entries[] = [$entryKey, $subDefinition];
                    $index++;
                }
            }

            foreach ($entries as [$entryKey, $entryDefinition]) {
                [$label, $icon, $path] = $entryDefinition;
                $links[] = [
                    'key' => $entryKey,
                    'label' => $label,
                    'icon' => $icon,
                    'url' => self::formatUrl($path, $companyFilter),
                    'active' => ($key === $activeKey) || ($entryKey === $activeKey),
                ];
            }
        }
        return $links;
    }

    private static function isItemAllowed(array $item, ?string $role, string $mode): bool
    {
        if (isset($item['modes']) && !self::matchesMode($item['modes'], $mode)) {
            return false;
        }
        if (!isset($item['roles'])) {
            return true;
        }
        $allowedRoles = self::normalizeRoles($item['roles']);
        if (in_array('*', $allowedRoles, true)) {
            return true;
        }
        if ($role === 'SUPERADMIN') {
            return true;
        }
        if ($role === null) {
            return false;
        }
        return in_array($role, $allowedRoles, true);
    }

    private static function matchesMode(array $modes, string $mode): bool
    {
        if (empty($modes)) {
            return true;
        }
        $canonicalMode = self::normalizeMode($mode) ?? $mode;
        foreach ($modes as $candidate) {
            $normalizedCandidate = self::normalizeMode($candidate) ?? $candidate;
            if ($normalizedCandidate === $canonicalMode) {
                return true;
            }
        }
        return false;
    }

    private static function isValidMode(string $mode): bool
    {
        $modes = array_keys(self::getModes());
        if (in_array($mode, $modes, true)) {
            return true;
        }
        $normalized = self::normalizeMode($mode);
        return $normalized !== null && in_array($normalized, $modes, true);
    }

    private static function getConfigValue(string $key, $default = null)
    {
        self::loadConfig();
        return self::$config[$key] ?? $default;
    }

    private static function loadConfig(): void
    {
        if (!empty(self::$config)) {
            return;
        }
        $configPath = __DIR__ . '/../../config/header.php';
        if (file_exists($configPath)) {
            try {
                self::$config = require $configPath;
                // Ensure config is an array
                if (!is_array(self::$config)) {
                    self::$config = [];
                }
            } catch (Exception $e) {
                // ===== PRODUCTION FIX: Log error and use empty config =====
                error_log("HeaderManager::loadConfig() error: " . $e->getMessage());
                self::$config = [];
            }
        } else {
            self::$config = [];
        }
    }

    private static function normalizeMode(?string $mode): ?string
    {
        if ($mode === null) {
            return null;
        }
        $mode = strtolower(trim($mode));
        if ($mode === '') {
            return null;
        }
        return self::MODE_ALIASES[$mode] ?? $mode;
    }

    private static function normalizeRoles(array $roles): array
    {
        if (class_exists('Roles') && method_exists('Roles', 'ensure')) {
            return Roles::ensure($roles);
        }
        return $roles;
    }
}


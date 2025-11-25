<?php

class Roles
{
    private static ?array $config = null;

    private static function load(): void
    {
        if (self::$config !== null) {
            return;
        }

        $configPath = __DIR__ . '/../../config/roles.php';
        if (!file_exists($configPath)) {
            self::$config = ['roles' => [], 'groups' => []];
            return;
        }

        $config = require $configPath;
        self::$config = [
            'roles' => $config['roles'] ?? [],
            'groups' => $config['groups'] ?? [],
        ];

        self::synchronizeDatabaseRoles();
    }

    public static function definitions(): array
    {
        self::load();
        return self::$config['roles'];
    }

    public static function get(string $role): ?array
    {
        self::load();
        return self::$config['roles'][$role] ?? null;
    }

    public static function byScope(?string $scope): array
    {
        self::load();
        if ($scope === null) {
            return self::$config['roles'];
        }

        $filtered = [];
        foreach (self::$config['roles'] as $key => $definition) {
            if (($definition['scope'] ?? 'staff') === $scope) {
                $filtered[$key] = $definition;
            }
        }

        return $filtered;
    }

    public static function all(): array
    {
        return array_keys(self::definitions());
    }

    public static function capabilities(string $role): array
    {
        $definition = self::get($role) ?? [];
        $capabilities = (array) ($definition['capabilities'] ?? []);

        if (class_exists('Permission')) {
            $permissionCaps = Permission::getRolePermissionSlugs($role);
            $capabilities = array_merge($capabilities, $permissionCaps);
        }

        return array_values(array_unique($capabilities));
    }

    public static function allows(string $role, string $capability): bool
    {
        if ($capability === '' || $role === '') {
            return false;
        }

        return in_array($capability, self::capabilities($role), true);
    }

    public static function group(string $group): array
    {
        self::load();
        $groups = self::$config['groups'];
        if (!isset($groups[$group])) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                trigger_error("Undefined role group: {$group}", E_USER_NOTICE);
            }
            return [];
        }
        return self::normalizeRoles($groups[$group]);
    }

    public static function ensure(array $roles): array
    {
        return self::normalizeRoles($roles);
    }

    private static function normalizeRoles($roles): array
    {
        self::load();

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if (!is_array($roles)) {
            return [];
        }

        $normalized = [];
        $definitions = self::definitions();
        $definitionKeys = array_keys($definitions);

        foreach ($roles as $role) {
            if (!is_string($role) || trim($role) === '') {
                continue;
            }

            if ($role === '*') {
                return ['*'];
            }

            if (!in_array($role, $definitionKeys, true)) {
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    trigger_error("Unknown role referenced: {$role}", E_USER_NOTICE);
                }
                continue;
            }

            $normalized[$role] = true;
        }

        return array_keys($normalized);
    }

    /**
     * Check if a role is admin-like (ADMIN or SUPERADMIN)
     * 
     * @param string|null $role Role name to check
     * @return bool True if role is ADMIN or SUPERADMIN
     */
    public static function isAdminLike(?string $role): bool
    {
        if ($role === null || trim($role) === '') {
            return false;
        }
        
        $roleUpper = strtoupper(trim($role));
        return in_array($roleUpper, ['ADMIN', 'SUPERADMIN'], true);
    }

    private static function synchronizeDatabaseRoles(): void
    {
        if (!class_exists('Database')) {
            return;
        }

        try {
            if (class_exists('Role')) {
                Role::syncWithConfig(self::$config['roles']);
            }

            $db = Database::getInstance();
            $db->query("SELECT 1 FROM roles LIMIT 1");
            $rows = $db->fetchAll(
                "SELECT name, description, scope, hierarchy_level, parent_role, is_system_role
                 FROM roles"
            );

            foreach ($rows as $row) {
                $key = strtoupper($row['name']);
                $existing = self::$config['roles'][$key] ?? [];
                $definition = array_merge($existing, [
                    'label' => $existing['label'] ?? $row['name'],
                    'description' => $row['description'] ?: ($existing['description'] ?? $row['name']),
                    'scope' => $row['scope'] ?? ($existing['scope'] ?? 'staff'),
                    'hierarchy' => (int) ($row['hierarchy_level'] ?? ($existing['hierarchy'] ?? 50)),
                    'meta' => array_merge($existing['meta'] ?? [], [
                        'is_system_role' => (int) ($row['is_system_role'] ?? 0) === 1,
                        'parent_role' => $row['parent_role'] ?? ($existing['parent_role'] ?? null),
                    ]),
                    'source' => ($row['is_system_role'] ?? 0) ? 'system' : 'database',
                ]);

                if (!isset($definition['capabilities'])) {
                    $definition['capabilities'] = [];
                }

                self::$config['roles'][$key] = $definition;
            }
        } catch (Throwable $e) {
            // Ignore database sync failures in runtime
        }
    }
}


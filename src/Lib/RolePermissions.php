<?php
/**
 * Lightweight permission helper for legacy views.
 * @deprecated Use Auth::can() or Permission::has() instead. This class is kept for backward compatibility.
 * Loads permissions dynamically from config/roles.php via Roles class.
 */

class RolePermissions
{
    private static ?array $permissionsCache = null;

    /**
     * Get permission matrix by role (loaded from config/roles.php)
     */
    private static function getPermissions(): array
    {
        if (self::$permissionsCache !== null) {
            return self::$permissionsCache;
        }

        self::$permissionsCache = [];

        // Try to load from Roles class (config/roles.php)
        if (class_exists('Roles')) {
            $roleDefinitions = Roles::definitions();
            
            foreach ($roleDefinitions as $roleName => $roleDef) {
                $capabilities = $roleDef['capabilities'] ?? [];
                
                // Also get permissions from database if Permission class exists
                if (class_exists('Permission')) {
                    try {
                        $dbPermissions = Permission::getRolePermissionSlugs($roleName);
                        $capabilities = array_merge($capabilities, $dbPermissions);
                    } catch (Exception $e) {
                        // Ignore errors, use config capabilities only
                    }
                }
                
                self::$permissionsCache[$roleName] = array_values(array_unique($capabilities));
            }
        } else {
            // Fallback to hardcoded permissions if Roles class doesn't exist
            self::$permissionsCache = [
                'SUPERADMIN' => ['*'],
                'ADMIN' => [
                    'jobs.view', 'jobs.create', 'jobs.edit', 'jobs.delete',
                    'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
                    'staff.view', 'staff.create', 'staff.edit', 'staff.delete',
                    'finance.view', 'finance.create', 'finance.edit', 'finance.delete',
                    'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete',
                    'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.delete',
                    'recurring.view', 'recurring.create', 'recurring.edit', 'recurring.delete',
                    'services.view', 'services.create', 'services.edit', 'services.delete',
                    'documents.view', 'documents.upload', 'documents.delete',
                    'reports.view', 'reports.export',
                    'settings.company.view', 'settings.company.edit',
                    'users.view', 'users.create', 'users.edit',
                ],
                'USER' => [
                    'jobs.view', 'jobs.create',
                    'customers.view',
                    'finance.view', 'finance.create',
                    'contracts.view',
                    'appointments.view', 'appointments.create',
                    'recurring.view',
                    'services.view',
                    'documents.view', 'documents.upload',
                    'reports.view',
                ],
                'STAFF' => [
                    'jobs.view',
                ],
            ];
        }

        return self::$permissionsCache;
    }

    public static function can(string $permission): bool
    {
        if (!Auth::check()) {
            return false;
        }

        // Prefer Permission class if available
        if (class_exists('Permission')) {
            try {
                return Permission::has($permission);
            } catch (Exception $e) {
                // Fall through to legacy check
            }
        }

        $role = Auth::role();
        return self::hasPermission($role, $permission);
    }

    private static function hasPermission(string $role, string $permission): bool
    {
        if ($role === 'SUPERADMIN') {
            return true;
        }

        $permissions = self::getPermissions();

        if (!isset($permissions[$role])) {
            return false;
        }

        $rolePermissions = $permissions[$role];

        if (in_array('*', $rolePermissions, true)) {
            return true;
        }

        if (in_array($permission, $rolePermissions, true)) {
            return true;
        }

        $parts = explode('.', $permission);
        if (count($parts) > 1) {
            $wildcard = $parts[0] . '.*';
            if (in_array($wildcard, $rolePermissions, true)) {
                return true;
            }
        }

        return false;
    }
}


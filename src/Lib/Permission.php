<?php
/**
 * Permission System
 * Granular permission management for enterprise-level access control
 */

class Permission
{
    private const PERMISSION_CACHE_KEY = 'permissions_';
    private const CACHE_TTL = 3600; // 1 hour
    
    private static $permissions = null;
    private static $userPermissions = [];
    
    /**
     * Check if user has specific permission
     * Supports wildcard matching: jobs.* matches jobs.create, jobs.edit, etc.
     */
    public static function has(string $permission, ?int $userId = null): bool
    {
        if ($userId === null) {
            $userId = Auth::id();
        }
        
        if (!$userId) {
            return false;
        }
        
        // Check cache first
        if (isset(self::$userPermissions[$userId])) {
            $permissions = self::$userPermissions[$userId];
            // Admin wildcard check
            if (in_array('*', $permissions)) {
                return true;
            }
            // Exact match
            if (in_array($permission, $permissions)) {
                return true;
            }
            // Wildcard matching: check if any permission matches with wildcard
            return self::matchesWildcard($permission, $permissions);
        }
        
        // Load user permissions
        $permissions = self::getUserPermissions($userId);
        self::$userPermissions[$userId] = $permissions;
        
        // Admin wildcard check
        if (in_array('*', $permissions)) {
            return true;
        }
        
        // Exact match
        if (in_array($permission, $permissions)) {
            return true;
        }
        
        // Wildcard matching: check if any permission matches with wildcard
        return self::matchesWildcard($permission, $permissions);
    }
    
    /**
     * Check if permission matches any wildcard pattern in permissions array
     * Example: 'jobs.create' matches 'jobs.*'
     */
    private static function matchesWildcard(string $permission, array $permissions): bool
    {
        foreach ($permissions as $perm) {
            // Convert wildcard pattern to regex
            $pattern = str_replace(['*', '.'], ['.*', '\.'], $perm);
            if (preg_match('/^' . $pattern . '$/', $permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has any of the given permissions
     */
    public static function hasAny(array $permissions, ?int $userId = null): bool
    {
        foreach ($permissions as $permission) {
            if (self::has($permission, $userId)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has all of the given permissions
     */
    public static function hasAll(array $permissions, ?int $userId = null): bool
    {
        foreach ($permissions as $permission) {
            if (!self::has($permission, $userId)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get all permissions for a user
     */
    public static function getUserPermissions(int $userId): array
    {
        try {
            $cache = new Cache();
            $cacheKey = self::PERMISSION_CACHE_KEY . $userId;
            
            // Try cache first
            $permissions = $cache->get($cacheKey);
            if ($permissions !== null) {
                return $permissions;
            }
            
            $db = Database::getInstance();
            
            // Get user's role
            $user = $db->fetch("SELECT role FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                return [];
            }
            
            // SUPERADMIN and ADMIN have all permissions
            if (in_array($user['role'], ['SUPERADMIN', 'ADMIN'])) {
                $permissions = ['*']; // Wildcard for all permissions
                $cache->set($cacheKey, $permissions, self::CACHE_TTL);
                return $permissions;
            }
            
            // Get role permissions from config FIRST (primary source of truth)
            $permissionNames = [];
            
            // If Roles class exists, use it to get capabilities from config
            if (class_exists('Roles')) {
                $currentRole = $user['role'];
                
                // Get capabilities directly from Roles config
                $capabilities = Roles::capabilities($currentRole);
                if (!empty($capabilities)) {
                    $permissionNames = array_merge($permissionNames, $capabilities);
                }
                
                // Also check hierarchy for inherited permissions
                $currentRoleDef = Roles::get($currentRole);
                $currentHierarchy = $currentRoleDef['hierarchy'] ?? 0;
                
                // Get all roles with lower hierarchy (higher hierarchy = more permissions)
                $allRoles = Roles::definitions();
                foreach ($allRoles as $roleName => $roleDef) {
                    $roleHierarchy = $roleDef['hierarchy'] ?? 0;
                    // If current role has higher hierarchy, it inherits permissions from lower roles
                    if ($roleHierarchy < $currentHierarchy && $roleHierarchy > 0) {
                        $inheritedCapabilities = Roles::capabilities($roleName);
                        $permissionNames = array_merge($permissionNames, $inheritedCapabilities);
                    }
                }
            }
            
            // Check if RBAC tables exist for additional database permissions
            $tablesExist = false;
            try {
                $db->query("SELECT 1 FROM permissions LIMIT 1");
                $db->query("SELECT 1 FROM roles LIMIT 1");
                $tablesExist = true;
            } catch (Exception $e) {
                // Tables don't exist, use only config permissions
                $tablesExist = false;
            }
            
            // Get role permissions (including hierarchy)
            $rolePermissions = [];
            try {
                // Get current role definition
                $currentRole = $user['role'];
                
                // If Roles class exists, use it to get capabilities from config
                if (class_exists('Roles')) {
                    // Get capabilities directly from Roles config
                    $capabilities = Roles::capabilities($currentRole);
                    if (!empty($capabilities)) {
                        // Convert capabilities to permission format (array of strings)
                        $rolePermissions = array_map(function($cap) {
                            return ['name' => $cap];
                        }, $capabilities);
                    }
                }
                
                // Also check database for additional permissions (if RBAC tables exist)
                try {
                    $db->query("SELECT 1 FROM permissions LIMIT 1");
                    $db->query("SELECT 1 FROM roles LIMIT 1");
                    
                    // Get permissions from database for all applicable roles
                    $rolesToCheck = [$currentRole];
                    if (class_exists('Roles')) {
                        $currentRoleDef = Roles::get($currentRole);
                        $currentHierarchy = $currentRoleDef['hierarchy'] ?? 0;
                        
                        // Get all roles with lower or equal hierarchy
                        $allRoles = Roles::definitions();
                        foreach ($allRoles as $roleName => $roleDef) {
                            $roleHierarchy = $roleDef['hierarchy'] ?? 0;
                            if ($roleHierarchy <= $currentHierarchy && $roleHierarchy > 0) {
                                $rolesToCheck[] = $roleName;
                            }
                        }
                    }
                    
                    $rolesToCheck = array_unique($rolesToCheck);
                    if (!empty($rolesToCheck)) {
                        $placeholders = str_repeat('?,', count($rolesToCheck) - 1) . '?';
                        $dbPermissions = $db->fetchAll(
                            "SELECT DISTINCT p.name 
                             FROM permissions p 
                             JOIN role_permissions rp ON p.id = rp.permission_id 
                             JOIN roles r ON rp.role_id = r.id 
                             WHERE r.name IN ($placeholders)",
                            $rolesToCheck
                        );
                        // Merge database permissions with config permissions
                        $rolePermissions = array_merge($rolePermissions, $dbPermissions);
                    }
                } catch (Exception $e) {
                    // Database RBAC tables don't exist or query failed, use only config
                }
            } catch (Exception $e) {
                $rolePermissions = [];
            }
            
            // Get direct user permissions
            try {
                $directPermissions = $db->fetchAll(
                    "SELECT p.name 
                     FROM permissions p 
                     JOIN user_permissions up ON p.id = up.permission_id 
                     WHERE up.user_id = ?",
                    [$userId]
                );
            } catch (Exception $e) {
                $directPermissions = [];
            }
            
            // Merge permissions
            $permissions = array_merge(
                array_column($rolePermissions, 'name'),
                array_column($directPermissions, 'name')
            );
            
            // Remove duplicates
            $permissions = array_unique($permissions);
            
            // Cache for 1 hour
            $cache->set($cacheKey, $permissions, self::CACHE_TTL);
            
            return $permissions;
        } catch (Exception $e) {
            // Return empty array on error
            return [];
        }
    }
    
    /**
     * Get all available permissions
     */
    public static function getAllPermissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }
        
        $db = Database::getInstance();
        $permissions = $db->fetchAll(
            "SELECT * FROM permissions ORDER BY category, name"
        );
        
        self::$permissions = self::normalizePermissions($permissions);
        return self::$permissions;
    }
    
    /**
     * Create a new permission
     */
    public static function create(string $name, string $description, string $category = 'general', bool $isSystemPermission = false): bool
    {
        $db = Database::getInstance();
        
        try {
            $db->query(
                "INSERT INTO permissions (name, description, category, is_system_permission, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
                [$name, $description, $category, $isSystemPermission ? 1 : 0, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
            );
            
            // Clear permissions cache
            self::clearCache();
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Assign permission to role
     */
    public static function assignToRole(string $permission, string $role): bool
    {
        $db = Database::getInstance();
        
        try {
            // Get permission ID
            $permissionId = $db->fetch(
                "SELECT id FROM permissions WHERE name = ?",
                [$permission]
            );
            
            if (!$permissionId) {
                return false;
            }
            
            // Get role ID
            $roleId = $db->fetch(
                "SELECT id FROM roles WHERE name = ?",
                [$role]
            );
            
            if (!$roleId) {
                return false;
            }
            
            // Check if already assigned
            $existing = $db->fetch(
                "SELECT id FROM role_permissions WHERE role_id = ? AND permission_id = ?",
                [$roleId['id'], $permissionId['id']]
            );
            
            if ($existing) {
                return true; // Already assigned
            }
            
            // Assign permission
            $db->query(
                "INSERT INTO role_permissions (role_id, permission_id, created_at) VALUES (?, ?, ?)",
                [$roleId['id'], $permissionId['id'], date('Y-m-d H:i:s')]
            );
            
            // Clear cache
            self::clearCache();
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Remove permission from role
     */
    public static function removeFromRole(string $permission, string $role): bool
    {
        $db = Database::getInstance();
        
        try {
            $db->query(
                "DELETE rp FROM role_permissions rp 
                 JOIN permissions p ON rp.permission_id = p.id 
                 JOIN roles r ON rp.role_id = r.id 
                 WHERE p.name = ? AND r.name = ?",
                [$permission, $role]
            );
            
            // Clear cache
            self::clearCache();
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Assign permission directly to user
     */
    public static function assignToUser(string $permission, int $userId): bool
    {
        $db = Database::getInstance();
        
        try {
            // Get permission ID
            $permissionId = $db->fetch(
                "SELECT id FROM permissions WHERE name = ?",
                [$permission]
            );
            
            if (!$permissionId) {
                return false;
            }
            
            // Check if already assigned
            $existing = $db->fetch(
                "SELECT id FROM user_permissions WHERE user_id = ? AND permission_id = ?",
                [$userId, $permissionId['id']]
            );
            
            if ($existing) {
                return true; // Already assigned
            }
            
            // Assign permission
            $db->query(
                "INSERT INTO user_permissions (user_id, permission_id, created_at) VALUES (?, ?, ?)",
                [$userId, $permissionId['id'], date('Y-m-d H:i:s')]
            );
            
            // Clear user cache
            unset(self::$userPermissions[$userId]);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Remove permission from user
     */
    public static function removeFromUser(string $permission, int $userId): bool
    {
        $db = Database::getInstance();
        
        try {
            $db->query(
                "DELETE up FROM user_permissions up 
                 JOIN permissions p ON up.permission_id = p.id 
                 WHERE p.name = ? AND up.user_id = ?",
                [$permission, $userId]
            );
            
            // Clear user cache
            unset(self::$userPermissions[$userId]);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get permissions by category
     */
    public static function getByCategory(string $category): array
    {
        $db = Database::getInstance();
        $permissions = $db->fetchAll(
            "SELECT * FROM permissions WHERE category = ? ORDER BY name",
            [$category]
        );

        return self::normalizePermissions($permissions);
    }
    
    /**
     * Get role permissions
     */
    public static function getRolePermissions(string $role): array
    {
        $db = Database::getInstance();
        $permissions = $db->fetchAll(
            "SELECT p.* FROM permissions p 
             JOIN role_permissions rp ON p.id = rp.permission_id 
             JOIN roles r ON rp.role_id = r.id 
             WHERE r.name = ?",
            [$role]
        );
        
        return self::normalizePermissions($permissions);
    }

    public static function getRolePermissionSlugs(string $role): array
    {
        return array_column(self::getRolePermissions($role), 'name');
    }
    
    /**
     * Check if permission exists
     */
    public static function exists(string $permission): bool
    {
        $db = Database::getInstance();
        $result = $db->fetch(
            "SELECT id FROM permissions WHERE name = ?",
            [$permission]
        );
        
        return $result !== false;
    }
    
    /**
     * Get permission categories
     */
    public static function getCategories(): array
    {
        $db = Database::getInstance();
        $categories = $db->fetchAll(
            "SELECT DISTINCT category FROM permissions ORDER BY category"
        );
        
        return array_column($categories, 'category');
    }

    private static function normalizePermissions(array $permissions): array
    {
        return array_map(function ($permission) {
            $defaults = [
                'id' => null,
                'name' => '',
                'description' => '',
                'category' => 'general',
                'is_system_permission' => 0,
                'created_at' => null,
                'updated_at' => null,
            ];

            $normalized = array_merge($defaults, (array) $permission);
            $normalized['is_system_permission'] = (int) ($normalized['is_system_permission'] ?? 0);

            return $normalized;
        }, $permissions);
    }
    
    /**
     * Clear permission cache
     */
    public static function clearCache(?int $userId = null): void
    {
        $cache = new Cache();
        
        if ($userId !== null) {
            // Clear specific user's cache
            $cacheKey = self::PERMISSION_CACHE_KEY . $userId;
            $cache->delete($cacheKey);
            unset(self::$userPermissions[$userId]);
        } else {
            // Clear all permission caches using pattern matching
            if (method_exists($cache, 'forgetPattern')) {
                $cache->forgetPattern('permissions_*');
            } else {
                // Fallback: manually clear all permission caches
                self::clearAllPermissionCaches($cache);
            }
            
            // Clear static cache
            self::$permissions = null;
            self::$userPermissions = [];
        }
    }
    
    /**
     * Clear all permission caches manually (fallback when forgetPattern not available)
     */
    private static function clearAllPermissionCaches(Cache $cache): void
    {
        // This is a fallback method - in production, Cache should support forgetPattern
        // For now, we'll clear the static cache and let TTL handle the rest
        // In a real implementation, you'd iterate through cache files matching the pattern
    }
    
    /**
     * Get permission statistics
     */
    public static function getStatistics(): array
    {
        $db = Database::getInstance();
        
        $stats = $db->fetch(
            "SELECT 
                COUNT(*) as total_permissions,
                COUNT(DISTINCT category) as categories,
                COUNT(DISTINCT rp.role_id) as roles_with_permissions,
                COUNT(DISTINCT up.user_id) as users_with_direct_permissions
             FROM permissions p
             LEFT JOIN role_permissions rp ON p.id = rp.permission_id
             LEFT JOIN user_permissions up ON p.id = up.permission_id"
        );
        
        return $stats;
    }
    
    /**
     * Bulk assign permissions to role
     */
    public static function bulkAssignToRole(array $permissions, string $role): bool
    {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            foreach ($permissions as $permission) {
                self::assignToRole($permission, $role);
            }
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollback();
            return false;
        }
    }
    
    /**
     * Bulk assign permissions to user
     */
    public static function bulkAssignToUser(array $permissions, int $userId): bool
    {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            foreach ($permissions as $permission) {
                self::assignToUser($permission, $userId);
            }
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollback();
            return false;
        }
    }
}

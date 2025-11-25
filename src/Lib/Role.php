<?php
/**
 * Role Management System
 * Enterprise-level role management with hierarchy support
 */

class Role
{
    private const ROLE_CACHE_KEY = 'roles_';
    private const CACHE_TTL = 3600; // 1 hour
    private const DEFAULT_SCOPE = 'staff';
    private const ALLOWED_SCOPES = ['staff', 'resident_portal', 'customer_portal'];
    
    private static $roles = null;
    
    /**
     * Get all roles
     */
    public static function all(): array
    {
        if (self::$roles !== null) {
            return self::$roles;
        }
        
        $db = Database::getInstance();
        $roles = $db->fetchAll(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM users u WHERE u.role = r.name) AS user_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permission_count
             FROM roles r
             ORDER BY hierarchy_level DESC, name"
        );
        
        self::$roles = $roles;
        return $roles;
    }
    
    /**
     * Get role by name
     */
    public static function getByName(string $name): ?array
    {
        $db = Database::getInstance();
        $role = $db->fetch(
            "SELECT * FROM roles WHERE name = ?",
            [$name]
        );
        
        return $role ?: null;
    }
    
    /**
     * Get role by ID
     */
    public static function getById(int $id): ?array
    {
        $db = Database::getInstance();
        $role = $db->fetch(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM users u WHERE u.role = r.name) AS user_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permission_count
             FROM roles r
             WHERE r.id = ?",
            [$id]
        );
        
        return $role ?: null;
    }
    
    /**
     * Create a new role
     */
    public static function create(
        string $name,
        string $description,
        int $hierarchyLevel = 0,
        ?string $parentRole = null,
        string $scope = self::DEFAULT_SCOPE,
        bool $isSystemRole = false
    ): bool
    {
        $db = Database::getInstance();
        
        try {
            // Check if role already exists
            $existing = self::getByName($name);
            if ($existing) {
                return false;
            }
            
            $db->query(
                "INSERT INTO roles (name, description, scope, hierarchy_level, parent_role, is_system_role, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $name,
                    $description,
                    self::normalizeScope($scope),
                    $hierarchyLevel,
                    $parentRole,
                    $isSystemRole ? 1 : 0,
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
            
            // Clear cache
            self::clearCache();
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Update role
     */
    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        
        try {
            $allowedFields = ['name', 'description', 'hierarchy_level', 'parent_role', 'scope', 'is_system_role'];
            $updateFields = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    if ($field === 'scope') {
                        $value = self::normalizeScope($value);
                    }
                    if ($field === 'is_system_role') {
                        $value = $value ? 1 : 0;
                    }
                    $updateFields[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($updateFields)) {
                return false;
            }
            
            $params[] = $id;
            
            $db->query(
                "UPDATE roles SET " . implode(', ', $updateFields) . ", updated_at = ? WHERE id = ?",
                array_merge($params, [date('Y-m-d H:i:s'), $id])
            );
            
            // Clear cache
            self::clearCache();
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Delete role
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        
        try {
            // Check if role is in use
            $users = $db->fetch(
                "SELECT COUNT(*) as count FROM users WHERE role = (SELECT name FROM roles WHERE id = ?)",
                [$id]
            );
            
            if ($users['count'] > 0) {
                return false; // Role is in use
            }
            
            // Delete role permissions
            $db->query("DELETE FROM role_permissions WHERE role_id = ?", [$id]);
            
            // Delete role
            $db->query("DELETE FROM roles WHERE id = ?", [$id]);
            
            // Clear cache
            self::clearCache();
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get role hierarchy
     */
    public static function getHierarchy(): array
    {
        $db = Database::getInstance();
        $roles = $db->fetchAll(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM users u WHERE u.role = r.name) AS user_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permission_count
             FROM roles r
             ORDER BY hierarchy_level DESC, name"
        );
        
        $hierarchy = [];
        foreach ($roles as $role) {
            $hierarchy[$role['hierarchy_level']][] = $role;
        }
        
        return $hierarchy;
    }
    
    /**
     * Get child roles
     */
    public static function getChildren(string $parentRole): array
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM roles WHERE parent_role = ? ORDER BY hierarchy_level DESC, name",
            [$parentRole]
        );
    }
    
    /**
     * Get parent role
     */
    public static function getParent(string $roleName): ?array
    {
        $db = Database::getInstance();
        $role = $db->fetch(
            "SELECT parent_role FROM roles WHERE name = ?",
            [$roleName]
        );
        
        if (!$role || !$role['parent_role']) {
            return null;
        }
        
        return self::getByName($role['parent_role']);
    }
    
    /**
     * Check if role can manage another role
     */
    public static function canManage(string $managerRole, string $targetRole): bool
    {
        $manager = self::getByName($managerRole);
        $target = self::getByName($targetRole);
        
        if (!$manager || !$target) {
            return false;
        }
        
        // Higher hierarchy level can manage lower levels
        return $manager['hierarchy_level'] > $target['hierarchy_level'];
    }
    
    /**
     * Get role statistics
     */
    public static function getStatistics(): array
    {
        $db = Database::getInstance();
        
        $stats = $db->fetch(
            "SELECT 
                COUNT(*) as total_roles,
                COUNT(DISTINCT hierarchy_level) as hierarchy_levels,
                COUNT(DISTINCT parent_role) as parent_roles,
                MAX(hierarchy_level) as max_hierarchy_level
             FROM roles"
        );
        
        // Get user count per role
        $userCounts = $db->fetchAll(
            "SELECT r.name, COUNT(u.id) as user_count
             FROM roles r
             LEFT JOIN users u ON r.name = u.role
             GROUP BY r.id, r.name
             ORDER BY user_count DESC"
        );
        
        $scopeBreakdown = $db->fetchAll(
            "SELECT scope, COUNT(*) as count FROM roles GROUP BY scope"
        );
        $stats['scope_counts'] = [];
        foreach ($scopeBreakdown as $scopeRow) {
            $stats['scope_counts'][$scopeRow['scope']] = (int) ($scopeRow['count'] ?? 0);
        }
        $stats['user_counts'] = $userCounts;
        
        return $stats;
    }
    
    /**
     * Get role permissions
     */
    public static function getPermissions(string $roleName): array
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT p.* FROM permissions p 
             JOIN role_permissions rp ON p.id = rp.permission_id 
             JOIN roles r ON rp.role_id = r.id 
             WHERE r.name = ?",
            [$roleName]
        );
    }
    
    /**
     * Assign permissions to role
     */
    public static function assignPermissions(string $roleName, array $permissions): bool
    {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Get role ID
            $role = self::getByName($roleName);
            if (!$role) {
                throw new Exception('Role not found');
            }
            
            // Clear existing permissions
            $db->query("DELETE FROM role_permissions WHERE role_id = ?", [$role['id']]);
            
            // Assign new permissions
            foreach ($permissions as $permission) {
                Permission::assignToRole($permission, $roleName);
            }
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollback();
            return false;
        }
    }
    
    /**
     * Get users with role
     */
    public static function getUsers(string $roleName): array
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM users WHERE role = ? ORDER BY username",
            [$roleName]
        );
    }
    
    /**
     * Get role activity (recent actions by users with this role)
     */
    public static function getActivity(string $roleName, int $days = 7): array
    {
        $db = Database::getInstance();
        $since = date('Y-m-d H:i:s', strtotime("-$days days"));
        return $db->fetchAll(
            "SELECT al.*, u.username 
             FROM activity_log al
             JOIN users u ON al.actor_id = u.id
             WHERE u.role = ? 
             AND al.created_at >= ?
             ORDER BY al.created_at DESC
             LIMIT 100",
            [$roleName, $since]
        );
    }
    
    /**
     * Check if role exists
     */
    public static function exists(string $name): bool
    {
        return self::getByName($name) !== null;
    }
    
    /**
     * Get role hierarchy tree
     */
    public static function getTree(): array
    {
        $roles = self::all(); // This already includes user_count and permission_count
        $tree = [];
        
        // Build tree structure
        foreach ($roles as $role) {
            if (!$role['parent_role']) {
                $tree[$role['name']] = [
                    'role' => $role,
                    'children' => self::buildChildren($role['name'], $roles)
                ];
            }
        }
        
        return $tree;
    }
    
    /**
     * Build children for tree structure
     */
    private static function buildChildren(string $parentName, array $roles): array
    {
        $children = [];
        
        foreach ($roles as $role) {
            if ($role['parent_role'] === $parentName) {
                $children[$role['name']] = [
                    'role' => $role,
                    'children' => self::buildChildren($role['name'], $roles)
                ];
            }
        }
        
        return $children;
    }
    
    /**
     * Clear role cache
     */
    public static function clearCache(): void
    {
        $cache = Cache::getInstance();
        $cache->forget(self::ROLE_CACHE_KEY . '*');
        self::$roles = null;
    }

    public static function allowedScopes(): array
    {
        return self::ALLOWED_SCOPES;
    }

    public static function syncWithConfig(array $definitions): void
    {
        if (empty($definitions)) {
            return;
        }

        $db = Database::getInstance();
        try {
            $db->query("SELECT 1 FROM roles LIMIT 1");
        } catch (Exception $e) {
            return;
        }

        foreach ($definitions as $name => $definition) {
            $description = $definition['description'] ?? ($definition['label'] ?? $name);
            $scope = self::normalizeScope($definition['scope'] ?? self::DEFAULT_SCOPE);
            $hierarchy = (int) ($definition['hierarchy'] ?? 50);

            $db->query(
                "INSERT INTO roles (name, description, scope, hierarchy_level, parent_role, is_system_role, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 1, ?, ?)
                 ON CONFLICT(name) DO UPDATE SET
                    description = excluded.description,
                    scope = excluded.scope,
                    hierarchy_level = excluded.hierarchy_level,
                    is_system_role = 1,
                    updated_at = excluded.updated_at",
                [
                    $name,
                    $description,
                    $scope,
                    $hierarchy,
                    $definition['parent_role'] ?? null,
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
        }

        self::clearCache();
    }

    private static function normalizeScope(?string $scope): string
    {
        $scope = $scope ? strtolower(trim($scope)) : self::DEFAULT_SCOPE;
        if (!in_array($scope, self::ALLOWED_SCOPES, true)) {
            return self::DEFAULT_SCOPE;
        }
        return $scope;
    }
    
    /**
     * Get role suggestions based on hierarchy
     */
    public static function getSuggestions(string $userRole): array
    {
        $userRoleData = self::getByName($userRole);
        if (!$userRoleData) {
            return [];
        }
        
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM roles 
             WHERE hierarchy_level < ? 
             ORDER BY hierarchy_level DESC, name",
            [$userRoleData['hierarchy_level']]
        );
    }
}

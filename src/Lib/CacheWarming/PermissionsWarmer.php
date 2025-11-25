<?php
/**
 * Permissions Cache Warmer
 * Pre-loads permissions data into cache for better performance
 */

class PermissionsWarmer
{
    private $cacheManager;
    
    public function __construct()
    {
        $this->cacheManager = CacheManager::getInstance();
    }
    
    /**
     * Warm permissions cache
     */
    public function warm(): void
    {
        Logger::info('Starting permissions cache warming');
        
        try {
            // Warm all permissions
            $this->warmAllPermissions();
            
            // Warm role permissions
            $this->warmRolePermissions();
            
            // Warm user permissions
            $this->warmUserPermissions();
            
            Logger::info('Permissions cache warming completed');
            
        } catch (Exception $e) {
            Logger::error('Permissions cache warming failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Warm all permissions
     */
    private function warmAllPermissions(): void
    {
        $permissions = Permission::getAllPermissions();
        $this->cacheManager->set('permissions:all', $permissions, 3600);
        
        // Group by category
        $permissionsByCategory = [];
        foreach ($permissions as $permission) {
            $permissionsByCategory[$permission['category']][] = $permission;
        }
        
        $this->cacheManager->set('permissions:by_category', $permissionsByCategory, 3600);
        
        Logger::info('All permissions cached', [
            'count' => count($permissions),
            'categories' => count($permissionsByCategory)
        ]);
    }
    
    /**
     * Warm role permissions
     */
    private function warmRolePermissions(): void
    {
        $roles = Role::all();
        
        foreach ($roles as $role) {
            $permissions = Role::getPermissions($role['name']);
            $this->cacheManager->set(
                "permissions:role:{$role['name']}",
                $permissions,
                3600
            );
        }
        
        Logger::info('Role permissions cached', [
            'roles' => count($roles)
        ]);
    }
    
    /**
     * Warm user permissions
     */
    private function warmUserPermissions(): void
    {
        $db = Database::getInstance();
        $users = $db->fetchAll("SELECT id FROM users WHERE is_active = 1");
        
        foreach ($users as $user) {
            $permissions = Permission::getUserPermissions($user['id']);
            $this->cacheManager->set(
                "permissions:user:{$user['id']}",
                $permissions,
                1800 // 30 minutes
            );
        }
        
        Logger::info('User permissions cached', [
            'users' => count($users)
        ]);
    }
}

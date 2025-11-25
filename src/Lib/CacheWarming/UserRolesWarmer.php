<?php
/**
 * User Roles Cache Warmer
 * Pre-loads user roles and hierarchy data
 */

class UserRolesWarmer
{
    private $cacheManager;
    
    public function __construct()
    {
        $this->cacheManager = CacheManager::getInstance();
    }
    
    /**
     * Warm user roles cache
     */
    public function warm(): void
    {
        Logger::info('Starting user roles cache warming');
        
        try {
            // Warm all roles
            $this->warmAllRoles();
            
            // Warm role hierarchy
            $this->warmRoleHierarchy();
            
            // Warm user role assignments
            $this->warmUserRoleAssignments();
            
            Logger::info('User roles cache warming completed');
            
        } catch (Exception $e) {
            Logger::error('User roles cache warming failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Warm all roles
     */
    private function warmAllRoles(): void
    {
        $roles = Role::all();
        $this->cacheManager->set('roles:all', $roles, 3600);
        
        // Create role lookup by name
        $rolesByName = [];
        foreach ($roles as $role) {
            $rolesByName[$role['name']] = $role;
        }
        
        $this->cacheManager->set('roles:by_name', $rolesByName, 3600);
        
        Logger::info('All roles cached', [
            'count' => count($roles)
        ]);
    }
    
    /**
     * Warm role hierarchy
     */
    private function warmRoleHierarchy(): void
    {
        $hierarchy = Role::getHierarchy();
        $this->cacheManager->set('roles:hierarchy', $hierarchy, 3600);
        
        $tree = Role::getTree();
        $this->cacheManager->set('roles:tree', $tree, 3600);
        
        Logger::info('Role hierarchy cached');
    }
    
    /**
     * Warm user role assignments
     */
    private function warmUserRoleAssignments(): void
    {
        $db = Database::getInstance();
        $users = $db->fetchAll("SELECT id, role FROM users WHERE is_active = 1");
        
        $userRoles = [];
        foreach ($users as $user) {
            $userRoles[$user['id']] = $user['role'];
        }
        
        $this->cacheManager->set('users:roles', $userRoles, 1800);
        
        // Group users by role
        $usersByRole = [];
        foreach ($users as $user) {
            $usersByRole[$user['role']][] = $user['id'];
        }
        
        $this->cacheManager->set('roles:users', $usersByRole, 1800);
        
        Logger::info('User role assignments cached', [
            'users' => count($users),
            'roles' => count($usersByRole)
        ]);
    }
}

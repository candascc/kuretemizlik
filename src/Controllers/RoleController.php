<?php

declare(strict_types=1);

/**
 * Role Management Controller
 * Handles role and permission management
 */

require_once __DIR__ . '/../Constants/AppConstants.php';
require_once __DIR__ . '/../Lib/ControllerHelper.php';

class RoleController
{
    /**
     * Show roles index
     */
    public function index()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $roles = Role::all();
        $statistics = Role::getStatistics();
        
        $data = [
            'title' => 'Rol Yönetimi',
            'roles' => $roles,
            'statistics' => $statistics,
            'scope_labels' => $this->scopeLabelMap(),
        ];
        
        echo View::renderWithLayout('admin/roles/index', $data);
    }
    
    /**
     * Show role details
     */
    public function show($id)
    {
        Auth::require();
        Auth::requireAdmin();
        
        $role = Role::getById($id);
        if (!$role) {
            set_flash('error', 'Role not found.');
            redirect('/admin/roles');
        }
        
        $permissions = Role::getPermissions($role['name']);
        $users = Role::getUsers($role['name']);
        $activity = Role::getActivity($role['name']);
        
        // Ensure user_count is set
        if (!isset($role['user_count'])) {
            $role['user_count'] = count($users);
        }
        
        $data = [
            'title' => 'Rol Detayları - ' . $role['name'],
            'role' => $role,
            'permissions' => $permissions,
            'users' => $users,
            'activity' => $activity,
            'scope_labels' => $this->scopeLabelMap(),
        ];
        
        echo View::renderWithLayout('admin/roles/show', $data);
    }
    
    /**
     * Show users with this role
     */
    public function users($id)
    {
        Auth::require();
        Auth::requireAdmin();
        
        $role = Role::getById($id);
        if (!$role) {
            set_flash('error', 'Role not found.');
            redirect('/admin/roles');
        }
        
        $users = Role::getUsers($role['name']);
        
        // Pagination
        $page = InputSanitizer::int($_GET['page'] ?? 1, 1, 10000);
        $perPage = 20;
        $total = count($users);
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedUsers = array_slice($users, $offset, $perPage);
        
        $data = [
            'title' => 'Rol Kullanıcıları - ' . $role['name'],
            'role' => $role,
            'users' => $paginatedUsers,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'scope_labels' => $this->scopeLabelMap(),
        ];
        
        echo View::renderWithLayout('admin/roles/users', $data);
    }
    
    /**
     * Show create role form
     */
    public function create()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $parentRoles = Role::getSuggestions(Auth::role());
        
        $data = [
            'title' => 'Rol Oluştur',
            'parent_roles' => $parentRoles,
            'scope_labels' => $this->scopeLabelMap(),
        ];
        
        echo View::renderWithLayout('admin/roles/create', $data);
    }
    
    /**
     * Store new role
     */
    public function store()
    {
        Auth::require();
        Auth::requireAdmin();
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/admin/roles')) {
            return;
        }
        // ===== ERR-026 FIX: End =====
        
        $validator = new Validator($_POST);
        $validator->required('name', 'Rol adı zorunludur')
                 ->required('description', 'Açıklama zorunludur')
                 ->min('name', 2, 'Rol adı en az 2 karakter olmalıdır')
                 ->max('name', 50, 'Rol adı en fazla 50 karakter olabilir');
        
        if ($validator->fails()) {
            set_flash('error', $validator->firstError());
            redirect('/admin/roles/create');
        }
        
        $name = strtoupper($_POST['name']);
        $description = $_POST['description'];
        $hierarchyLevel = (int)($_POST['hierarchy_level'] ?? 0);
        $parentRole = $_POST['parent_role'] ?: null;
        $scope = $_POST['scope'] ?? Role::allowedScopes()[0];
        $isSystemRole = isset($_POST['is_system_role']);

        if (!in_array($scope, Role::allowedScopes(), true)) {
            set_flash('error', 'Geçerli bir kapsam seçmelisiniz.');
            redirect('/admin/roles/create');
        }
        
        // Check if role already exists
        if (Role::exists($name)) {
            set_flash('error', 'Bu rol zaten mevcut.');
            redirect('/admin/roles/create');
        }
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            // Create role
            if (Role::create($name, $description, $hierarchyLevel, $parentRole, $scope, $isSystemRole)) {
                // ===== ERR-018 FIX: Add audit logging =====
                AuditLogger::getInstance()->logAdmin('ROLE_CREATED', Auth::id(), [
                    'role_name' => $name,
                    'description' => $description,
                    'hierarchy_level' => $hierarchyLevel,
                    'scope' => $scope,
                    'is_system_role' => $isSystemRole
                ]);
                // ===== ERR-018 FIX: End =====
                
                set_flash('success', 'Rol başarıyla oluşturuldu.');
                redirect('/admin/roles');
            } else {
                set_flash('error', 'Rol oluşturulamadı.');
                redirect('/admin/roles/create');
            }
        } catch (Exception $e) {
            error_log("RoleController::store() error: " . $e->getMessage());
            set_flash('error', 'Rol oluşturulurken bir hata oluştu: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
            redirect('/admin/roles/create');
        }
        // ===== ERR-010 FIX: End =====
    }
    
    /**
     * Show edit role form
     */
    public function edit($id)
    {
        Auth::require();
        Auth::requireAdmin();
        
        $role = Role::getById($id);
        if (!$role) {
            set_flash('error', 'Role not found.');
            redirect('/admin/roles');
        }
        
        // Ensure user_count is set
        if (!isset($role['user_count'])) {
            $users = Role::getUsers($role['name']);
            $role['user_count'] = count($users);
        }
        
        $parentRoles = Role::getSuggestions(Auth::role());
        
        $data = [
            'title' => 'Rol Düzenle - ' . $role['name'],
            'role' => $role,
            'parent_roles' => $parentRoles,
            'scope_labels' => $this->scopeLabelMap(),
        ];
        
        echo View::renderWithLayout('admin/roles/edit', $data);
    }
    
    /**
     * Update role
     */
    public function update($id)
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/roles');
        }
        
        $role = Role::getById($id);
        if (!$role) {
            set_flash('error', 'Rol bulunamadı.');
            redirect('/admin/roles');
        }
        
        $validator = new Validator($_POST);
        $validator->required('name', 'Rol adı zorunludur')
                 ->required('description', 'Açıklama zorunludur')
                 ->min('name', 2, 'Rol adı en az 2 karakter olmalıdır')
                 ->max('name', 50, 'Rol adı en fazla 50 karakter olabilir');
        
        if ($validator->fails()) {
            set_flash('error', $validator->firstError());
            redirect('/admin/roles/' . $id . '/edit');
        }
        
        $desiredScope = $_POST['scope'] ?? $role['scope'];
        if (!in_array($desiredScope, Role::allowedScopes(), true)) {
            $desiredScope = $role['scope'];
        }

        $data = [
            'name' => strtoupper($_POST['name']),
            'description' => $_POST['description'],
            'hierarchy_level' => (int)($_POST['hierarchy_level']),
            'parent_role' => $_POST['parent_role'] ?: null,
        ];

        if (!$role['is_system_role']) {
            $data['scope'] = $desiredScope;
            $data['is_system_role'] = isset($_POST['is_system_role']) ? 1 : 0;
        } else {
            $data['scope'] = $role['scope'];
            $data['is_system_role'] = 1;
        }
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            if (Role::update($id, $data)) {
                // ===== ERR-018 FIX: Add audit logging =====
                AuditLogger::getInstance()->logAdmin('ROLE_UPDATED', Auth::id(), [
                    'role_id' => $id,
                    'role_name' => $role['name'] ?? null,
                    'changes' => array_diff_assoc($data, $role)
                ]);
                // ===== ERR-018 FIX: End =====
                
                set_flash('success', 'Rol başarıyla güncellendi.');
                redirect('/admin/roles');
            } else {
                set_flash('error', 'Rol güncellenemedi.');
                redirect('/admin/roles/' . $id . '/edit');
            }
        } catch (Exception $e) {
            error_log("RoleController::update() error: " . $e->getMessage());
            set_flash('error', 'Rol güncellenirken bir hata oluştu: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
            redirect('/admin/roles/' . $id . '/edit');
        }
        // ===== ERR-010 FIX: End =====
    }
    
    /**
     * Delete role
     */
    public function delete($id)
    {
        Auth::require();
        Auth::requireAdmin();
        
        // ===== IMPROVEMENT: Validate ID using ControllerHelper =====
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            set_flash('error', 'Geçersiz rol ID.');
            redirect('/admin/roles');
            return;
        }
        // ===== IMPROVEMENT: End =====
        
        // ===== ERR-026 FIX: Use ControllerHelper for common patterns =====
        if (!ControllerHelper::requirePostOrRedirect('/admin/roles')) {
            return;
        }
        // ===== ERR-026 FIX: End =====
        
        $role = Role::getById($id);
        if (!$role) {
            set_flash('error', 'Rol bulunamadı.');
            redirect('/admin/roles');
            return;
        }
        
        // Check if it's a system role
        if ($role['is_system_role']) {
            set_flash('error', 'Sistem rolleri silinemez.');
            redirect('/admin/roles');
        }
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            if (Role::delete($id)) {
                // ===== ERR-018 FIX: Add audit logging =====
                AuditLogger::getInstance()->logAdmin('ROLE_DELETED', Auth::id(), [
                    'role_id' => $id,
                    'role_name' => $role['name'] ?? null,
                    'is_system_role' => $role['is_system_role'] ?? false
                ]);
                // ===== ERR-018 FIX: End =====
                
                set_flash('success', 'Rol başarıyla silindi.');
            } else {
                set_flash('error', 'Rol silinemedi. Rol kullanımda olabilir.');
            }
        } catch (Exception $e) {
            error_log("RoleController::delete() error: " . $e->getMessage());
            set_flash('error', 'Rol silinirken bir hata oluştu: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
        }
        // ===== ERR-010 FIX: End =====
        
        redirect('/admin/roles');
    }
    
    /**
     * Show role permissions
     */
    public function permissions($id)
    {
        Auth::require();
        Auth::requireAdmin();
        
        $role = Role::getById($id);
        if (!$role) {
            set_flash('error', 'Role not found.');
            redirect('/admin/roles');
        }
        
        $rolePermissions = Role::getPermissions($role['name']);
        $allPermissions = Permission::getAllPermissions();
        $categories = Permission::getCategories();
        
        // Group permissions by category
        $permissionsByCategory = [];
        foreach ($allPermissions as $permission) {
            $permissionsByCategory[$permission['category']][] = $permission;
        }
        
        $data = [
            'title' => 'Rol İzinleri - ' . $role['name'],
            'role' => $role,
            'role_permissions' => array_column($rolePermissions, 'name'),
            'permissions_by_category' => $permissionsByCategory,
            'categories' => $categories
        ];
        
        echo View::renderWithLayout('admin/roles/permissions', $data);
    }
    
    /**
     * Update role permissions
     */
    public function updatePermissions($id)
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/roles');
        }
        
        $role = Role::getById($id);
        if (!$role) {
            set_flash('error', 'Rol bulunamadı.');
            redirect('/admin/roles');
        }
        
        $permissions = $_POST['permissions'] ?? [];
        
        if (Role::assignPermissions($role['name'], $permissions)) {
            set_flash('success', 'Rol izinleri başarıyla güncellendi.');
        } else {
            set_flash('error', 'Rol izinleri güncellenemedi.');
        }
        
        redirect('/admin/roles/' . $id . '/permissions');
    }
    
    /**
     * Show permission management
     */
    public function permissionIndex()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $permissions = Permission::getAllPermissions();
        $categories = Permission::getCategories();
        $statistics = Permission::getStatistics();
        
        // Group permissions by category
        $permissionsByCategory = [];
        foreach ($permissions as $permission) {
            $permissionsByCategory[$permission['category']][] = $permission;
        }
        
        $data = [
            'title' => 'İzin Yönetimi',
            'permissions_by_category' => $permissionsByCategory,
            'categories' => $categories,
            'statistics' => $statistics
        ];
        
        echo View::renderWithLayout('admin/roles/permissions-index', $data);
    }
    
    /**
     * Create new permission
     */
    public function createPermission()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/roles/permissions');
        }
        
        $validator = new Validator($_POST);
        $validator->required('name', 'İzin adı zorunludur')
                 ->required('description', 'Açıklama zorunludur')
                 ->required('category', 'Kategori zorunludur')
                 ->min('name', 3, 'İzin adı en az 3 karakter olmalıdır');
        
        if ($validator->fails()) {
            set_flash('error', $validator->firstError());
            redirect('/admin/roles/permissions');
        }
        
        $name = strtolower($_POST['name']);
        $description = $_POST['description'];
        $category = $_POST['category'];
        
        // Check if permission already exists
        if (Permission::exists($name)) {
            set_flash('error', 'Bu izin zaten mevcut.');
            redirect('/admin/roles/permissions');
        }
        
        if (Permission::create($name, $description, $category)) {
            set_flash('success', 'İzin başarıyla oluşturuldu.');
        } else {
            set_flash('error', 'İzin oluşturulamadı.');
        }
        
        redirect('/admin/roles/permissions');
    }
    
    /**
     * Get role hierarchy
     */
    public function hierarchy()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $hierarchy = Role::getHierarchy();
        $tree = Role::getTree();
        
        // Ensure user_count and permission_count are set for all roles
        $allRoles = Role::all();
        $rolesWithCounts = [];
        foreach ($allRoles as $role) {
            if (!isset($role['user_count'])) {
                $users = Role::getUsers($role['name']);
                $role['user_count'] = count($users);
            }
            if (!isset($role['permission_count'])) {
                $permissions = Role::getPermissions($role['name']);
                $role['permission_count'] = count($permissions);
            }
            $rolesWithCounts[$role['id']] = $role;
        }
        
        // Update hierarchy and tree with counts
        foreach ($hierarchy as $level => $roles) {
            foreach ($roles as $key => $role) {
                if (isset($rolesWithCounts[$role['id']])) {
                    $hierarchy[$level][$key] = $rolesWithCounts[$role['id']];
                }
            }
        }
        
        $data = [
            'title' => 'Rol Hiyerarşisi',
            'hierarchy' => $hierarchy,
            'tree' => $tree,
            'roles' => $allRoles
        ];
        
        echo View::renderWithLayout('admin/roles/hierarchy', $data);
    }
    
    /**
     * Export roles and permissions
     */
    public function export()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $format = $_GET['format'] ?? 'json';
        
        $roles = Role::all();
        $permissions = Permission::getAllPermissions();
        
        $data = [
            'roles' => $roles,
            'permissions' => $permissions,
            'exported_at' => date('Y-m-d H:i:s'),
            'exported_by' => Auth::user()['username']
        ];
        
        if ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="rbac_export_' . date('Y-m-d') . '.json"');
            echo json_encode($data, JSON_PRETTY_PRINT);
        } else {
            // CSV export
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="rbac_export_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Type', 'Name', 'Description', 'Category', 'Hierarchy Level']);
            
            foreach ($roles as $role) {
                fputcsv($output, ['Role', $role['name'], $role['description'], '', $role['hierarchy_level']]);
            }
            
            foreach ($permissions as $permission) {
                fputcsv($output, ['Permission', $permission['name'], $permission['description'], $permission['category'], '']);
            }
            
            fclose($output);
        }
        
        exit;
    }

    private function scopeLabelMap(): array
    {
        return [
            'staff' => 'Personel',
            'resident_portal' => 'Sakin Portalı',
            'customer_portal' => 'Müşteri Portalı',
        ];
    }
}

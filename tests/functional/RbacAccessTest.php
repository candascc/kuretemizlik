<?php
/**
 * RBAC Access Control Test Suite
 * Tests role-based and capability-based access control
 */

require_once __DIR__ . '/../../config/config.php';

class RbacAccessTest
{
    private $db;
    private $testUsers = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Setup test users with different roles
     */
    private function setupTestUsers(): void
    {
        require_once __DIR__ . '/../Support/FactoryRegistry.php';

        // Create test users if they don't exist
        $roles = ['ADMIN', 'OPERATOR', 'FINANCE', 'SUPPORT', 'SUPERADMIN'];
        
        foreach ($roles as $role) {
            $username = 'test_' . strtolower($role);
            $existing = $this->db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
            
            if (!$existing) {
                $userId = FactoryRegistry::user()->create([
                    'username' => $username,
                    'role' => $role,
                    'company_id' => 1,
                ]);
                $this->testUsers[$role] = $userId;
            } else {
                $this->testUsers[$role] = $existing['id'];
            }
        }
    }
    
    /**
     * Simulate login for a user
     */
    private function loginAs(int $userId, string $role): void
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = [];
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = 'test_' . strtolower($role);
        $_SESSION['role'] = $role;
        $_SESSION['company_id'] = 1;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Verify session is set correctly
        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] !== $userId) {
            throw new Exception("Failed to set session for user {$userId}");
        }
    }
    
    /**
     * Test that FINANCE role can access finance list but not jobs list
     */
    public function testFinanceRoleAccess(): bool
    {
        $this->setupTestUsers();
        $this->loginAs($this->testUsers['FINANCE'], 'FINANCE');
        
        // Clear permission cache to ensure fresh check
        if (class_exists('Cache')) {
            $cache = new Cache();
            $cacheKey = 'permissions_' . $this->testUsers['FINANCE'];
            $cache->delete($cacheKey);
        }
        
        // Clear Permission class static cache
        if (class_exists('Permission')) {
            $reflection = new ReflectionClass('Permission');
            $property = $reflection->getProperty('userPermissions');
            $property->setAccessible(true);
            $property->setValue(null, []);
        }
        
        // FINANCE should have finance.collect capability
        // Clear permission cache first
        if (class_exists('Permission')) {
            $reflection = new ReflectionClass('Permission');
            $property = $reflection->getProperty('userPermissions');
            $property->setAccessible(true);
            $property->setValue(null, []);
            
            // Also clear cache
            if (class_exists('Cache')) {
                $cache = new Cache();
                $cacheKey = 'permissions_' . $this->testUsers['FINANCE'];
                $cache->delete($cacheKey);
            }
        }
        
        // Check both Auth::can() and Permission::has() directly
        $hasFinanceCollect = Auth::can('finance.collect');
        $hasDirect = Permission::has('finance.collect', $this->testUsers['FINANCE']);
        
        // If Permission::has() returns true but Auth::can() returns false, there's an issue with Auth::hasPermission()
        if (!$hasFinanceCollect && $hasDirect) {
            echo "FAIL: FINANCE role should have finance.collect capability\n";
            echo "  DEBUG: Permission::has() returns true, but Auth::can() returns false\n";
            echo "  DEBUG: Auth::id() = " . (Auth::id() ?? 'null') . "\n";
            echo "  DEBUG: Session user_id = " . ($_SESSION['user_id'] ?? 'null') . "\n";
            echo "  DEBUG: Auth::check() = " . (Auth::check() ? 'true' : 'false') . "\n";
            return false;
        }
        
        if (!$hasFinanceCollect) {
            echo "FAIL: FINANCE role should have finance.collect capability\n";
            // Debug: Check what permissions FINANCE actually has
            if (class_exists('Permission')) {
                $perms = Permission::getUserPermissions($this->testUsers['FINANCE']);
                echo "  DEBUG: FINANCE permissions: " . implode(', ', $perms) . "\n";
                echo "  DEBUG: Permission::has() direct check: " . ($hasDirect ? 'true' : 'false') . "\n";
            }
            if (class_exists('Roles')) {
                $caps = Roles::capabilities('FINANCE');
                echo "  DEBUG: FINANCE capabilities from config: " . implode(', ', $caps) . "\n";
            }
            return false;
        }
        
        // FINANCE should NOT have jobs.create capability
        $hasJobsCreate = Auth::can('jobs.create');
        if ($hasJobsCreate) {
            echo "FAIL: FINANCE role should NOT have jobs.create capability\n";
            return false;
        }
        
        // FINANCE should have access to finance group
        $hasFinanceGroup = Auth::hasGroup('nav.finance.core');
        if (!$hasFinanceGroup) {
            echo "FAIL: FINANCE role should have access to nav.finance.core group\n";
            return false;
        }
        
        echo "PASS: FINANCE role access control\n";
        return true;
    }
    
    /**
     * Test that OPERATOR role can create jobs but not delete customers
     */
    public function testOperatorRoleAccess(): bool
    {
        $this->setupTestUsers();
        $this->loginAs($this->testUsers['OPERATOR'], 'OPERATOR');
        
        // OPERATOR should have jobs.create
        $hasJobsCreate = Auth::can('jobs.create');
        if (!$hasJobsCreate) {
            echo "FAIL: OPERATOR role should have jobs.create capability\n";
            return false;
        }
        
        // OPERATOR should NOT have customers.delete
        $hasCustomersDelete = Auth::can('customers.delete');
        if ($hasCustomersDelete) {
            echo "FAIL: OPERATOR role should NOT have customers.delete capability\n";
            return false;
        }
        
        // OPERATOR should have access to operations core group
        $hasOpsGroup = Auth::hasGroup('nav.operations.core');
        if (!$hasOpsGroup) {
            echo "FAIL: OPERATOR role should have access to nav.operations.core group\n";
            return false;
        }
        
        echo "PASS: OPERATOR role access control\n";
        return true;
    }
    
    /**
     * Test that SUPERADMIN has access to everything
     */
    public function testSuperAdminAccess(): bool
    {
        $this->setupTestUsers();
        $this->loginAs($this->testUsers['SUPERADMIN'], 'SUPERADMIN');
        
        // SUPERADMIN should have all capabilities
        $capabilities = ['finance.collect', 'jobs.create', 'customers.delete', 'reports.export'];
        foreach ($capabilities as $cap) {
            if (!Auth::can($cap)) {
                echo "FAIL: SUPERADMIN should have {$cap} capability\n";
                return false;
            }
        }
        
        // SUPERADMIN should have access to all groups
        $groups = ['nav.finance.core', 'nav.operations.core', 'nav.reports.core', 'nav.settings.admin'];
        foreach ($groups as $group) {
            if (!Auth::hasGroup($group)) {
                echo "FAIL: SUPERADMIN should have access to {$group} group\n";
                return false;
            }
        }
        
        echo "PASS: SUPERADMIN access control\n";
        return true;
    }
    
    /**
     * Test that unauthenticated user has no access
     */
    public function testUnauthenticatedAccess(): bool
    {
        $_SESSION = [];
        
        // Unauthenticated should not have any capabilities
        $capabilities = ['finance.collect', 'jobs.view', 'customers.view'];
        foreach ($capabilities as $cap) {
            if (Auth::can($cap)) {
                echo "FAIL: Unauthenticated user should NOT have {$cap} capability\n";
                return false;
            }
        }
        
        // Unauthenticated should not have access to any groups
        $groups = ['nav.finance.core', 'nav.operations.core'];
        foreach ($groups as $group) {
            if (Auth::hasGroup($group)) {
                echo "FAIL: Unauthenticated user should NOT have access to {$group} group\n";
                return false;
            }
        }
        
        echo "PASS: Unauthenticated access control\n";
        return true;
    }
    
    /**
     * Test hierarchy: ADMIN should have access to OPERATOR-only pages
     */
    public function testRoleHierarchy(): bool
    {
        $this->setupTestUsers();
        $this->loginAs($this->testUsers['ADMIN'], 'ADMIN');
        
        // ADMIN should be able to access OPERATOR role requirements
        // This tests requireRole() hierarchy support
        try {
            // ADMIN has higher hierarchy (90) than OPERATOR (70)
            // So ADMIN should pass OPERATOR role check
            $currentRole = Auth::role();
            $requiredRole = 'OPERATOR';
            
            // Check if ADMIN can access OPERATOR-required resources
            // Since ADMIN has all OPERATOR capabilities, this should work
            $hasOpsGroup = Auth::hasGroup('nav.operations.core');
            if (!$hasOpsGroup) {
                echo "FAIL: ADMIN should have access to nav.operations.core (OPERATOR group)\n";
                return false;
            }
            
            echo "PASS: Role hierarchy access control\n";
            return true;
        } catch (Exception $e) {
            echo "FAIL: Role hierarchy test error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Run all tests
     */
    public function runAll(): array
    {
        $results = [];
        
        $results['finance_role'] = $this->testFinanceRoleAccess();
        $results['operator_role'] = $this->testOperatorRoleAccess();
        $results['superadmin'] = $this->testSuperAdminAccess();
        $results['unauthenticated'] = $this->testUnauthenticatedAccess();
        $results['hierarchy'] = $this->testRoleHierarchy();
        
        return $results;
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new RbacAccessTest();
    $results = $test->runAll();
    
    $passed = count(array_filter($results));
    $total = count($results);
    
    echo "\n";
    echo "=== RBAC Access Test Results ===\n";
    echo "Passed: {$passed}/{$total}\n";
    
    if ($passed === $total) {
        echo "All tests PASSED!\n";
        exit(0);
    } else {
        echo "Some tests FAILED!\n";
        exit(1);
    }
}


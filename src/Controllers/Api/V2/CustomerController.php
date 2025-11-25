<?php
/**
 * API v2 Customer Controller
 * RESTful API for customer management
 */

namespace App\Controllers\Api\V2;

class CustomerController
{
    private $db;
    private $userId;
    private $userRole;
    private $companyId;
    
    public function __construct()
    {
        $this->db = \Database::getInstance();
    }
    
    /**
     * Load user context from JWT payload and check permissions
     */
    private function requireAuthAndPermission(string $permission): array
    {
        $payload = \JWTAuth::require();
        $this->userId = $payload['user_id'] ?? null;
        
        if (!$this->userId) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Invalid token']);
            exit;
        }
        
        // Load user data for permission checks
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$this->userId]);
        if (!$user) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'message' => 'User not found']);
            exit;
        }
        
        $this->userRole = $user['role'] ?? null;
        $this->companyId = $user['company_id'] ?? null;
        
        // Check permission using Permission service
        if (class_exists('Permission')) {
            // Temporarily set session for Permission checks
            $_SESSION['user_id'] = $this->userId;
            $_SESSION['role'] = $this->userRole;
            $_SESSION['company_id'] = $this->companyId;
            
            if (!\Permission::has($permission, $this->userId)) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden', 'message' => 'Insufficient permissions']);
                exit;
            }
        }
        
        return $payload;
    }
    
    /**
     * Apply company scope to query
     */
    private function applyCompanyScope(string $query, array $params = []): array
    {
        // SUPERADMIN can see all companies
        if ($this->userRole === 'SUPERADMIN') {
            if (isset($_GET['company_filter']) && $_GET['company_filter'] !== '') {
                $query .= (strpos($query, 'WHERE') !== false ? ' AND' : ' WHERE') . ' company_id = ?';
                $params[] = InputSanitizer::int($_GET['company_filter'], 1);
            }
            return [$query, $params];
        }
        
        // Others see only their company
        if ($this->companyId) {
            $query .= (strpos($query, 'WHERE') !== false ? ' AND' : ' WHERE') . ' company_id = ?';
            $params[] = (int)$this->companyId;
        } else {
            // No company assigned - return empty result
            $query .= (strpos($query, 'WHERE') !== false ? ' AND' : ' WHERE') . ' 1=0';
        }
        
        return [$query, $params];
    }
    
    /**
     * List all customers
     */
    public function index()
    {
        $this->requireAuthAndPermission('customers.view');
        header('Content-Type: application/json');
        
        $page = InputSanitizer::int($_GET['page'] ?? 1, 1, 10000);
        $perPage = InputSanitizer::int($_GET['per_page'] ?? 20, 1, 100);
        $offset = ($page - 1) * $perPage;
        
        $query = "SELECT id, name, phone, email, created_at FROM customers";
        list($query, $params) = $this->applyCompanyScope($query);
        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $customers = $this->db->fetchAll($query, $params);
        
        $countQuery = "SELECT COUNT(*) as count FROM customers";
        list($countQuery, $countParams) = $this->applyCompanyScope($countQuery);
        $total = $this->db->fetch($countQuery, $countParams);
        
        echo json_encode([
            'success' => true,
            'data' => $customers,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => $total['count'],
                'total_pages' => ceil($total['count'] / $perPage)
            ]
        ]);
    }
    
    /**
     * Get single customer
     */
    public function show(int $id)
    {
        $this->requireAuthAndPermission('customers.view');
        header('Content-Type: application/json');
        
        $query = "SELECT * FROM customers WHERE id = ?";
        list($query, $params) = $this->applyCompanyScope($query, [$id]);
        
        $customer = $this->db->fetch($query, $params);
        
        if (!$customer) {
            http_response_code(404);
            echo json_encode(['error' => 'Not Found', 'message' => 'Customer not found']);
            return;
        }
        
        // SECURITY: Get addresses with company_id filter via customer JOIN
        $addresses = $this->db->fetchAll(
            "SELECT a.* FROM addresses a
             INNER JOIN customers c ON a.customer_id = c.id
             WHERE a.customer_id = ? AND c.company_id = ?",
            [$id, $customer['company_id'] ?? null]
        );
        $customer['addresses'] = $addresses;
        
        echo json_encode(['success' => true, 'data' => $customer]);
    }
    
    /**
     * Create customer
     */
    public function create()
    {
        $this->requireAuthAndPermission('customers.create');
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name']) || empty($input['phone'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'message' => 'Name and phone required']);
            return;
        }
        
        // Get company_id for insert
        $companyId = $this->companyId;
        if ($this->userRole === 'SUPERADMIN' && isset($input['company_id'])) {
            $companyId = (int)$input['company_id'];
        }
        
        if (!$companyId) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'message' => 'No company assigned to user']);
            return;
        }
        
        $this->db->query(
            "INSERT INTO customers (name, phone, email, notes, company_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $input['name'],
                $input['phone'],
                $input['email'] ?? null,
                $input['notes'] ?? null,
                $companyId,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ]
        );
        
        $customerId = $this->db->lastInsertId();
        
        echo json_encode(['success' => true, 'data' => ['id' => $customerId], 'message' => 'Customer created']);
    }
    
    /**
     * Update customer
     */
    public function update(int $id)
    {
        $this->requireAuthAndPermission('customers.edit');
        header('Content-Type: application/json');
        
        // Verify company access
        $query = "SELECT company_id FROM customers WHERE id = ?";
        list($query, $params) = $this->applyCompanyScope($query, [$id]);
        $customer = $this->db->fetch($query, $params);
        
        if (!$customer) {
            http_response_code(404);
            echo json_encode(['error' => 'Not Found', 'message' => 'Customer not found']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $this->db->query(
            "UPDATE customers SET name = ?, phone = ?, email = ?, notes = ?, updated_at = ? WHERE id = ?",
            [
                $input['name'] ?? null,
                $input['phone'] ?? null,
                $input['email'] ?? null,
                $input['notes'] ?? null,
                date('Y-m-d H:i:s'),
                $id
            ]
        );
        
        echo json_encode(['success' => true, 'message' => 'Customer updated']);
    }
    
    /**
     * Delete customer
     */
    public function delete(int $id)
    {
        $this->requireAuthAndPermission('customers.delete');
        header('Content-Type: application/json');
        
        // Verify company access
        $query = "SELECT company_id FROM customers WHERE id = ?";
        list($query, $params) = $this->applyCompanyScope($query, [$id]);
        $customer = $this->db->fetch($query, $params);
        
        if (!$customer) {
            http_response_code(404);
            echo json_encode(['error' => 'Not Found', 'message' => 'Customer not found']);
            return;
        }
        
        $this->db->query("DELETE FROM customers WHERE id = ?", [$id]);
        
        echo json_encode(['success' => true, 'message' => 'Customer deleted']);
    }
}


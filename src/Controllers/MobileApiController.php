<?php

/**
 * Mobile API Controller
 */
class MobileApiController
{
    private $jobModel;
    private $customerModel;
    private $serviceModel;
    private $moneyModel;
    private $userModel;
    private array $apiAccessRoles;
    private array $jobModifyRoles;
    private array $customerAccessRoles;

    public function __construct()
    {
        $this->jobModel = new Job();
        $this->customerModel = new Customer();
        $this->serviceModel = new Service();
        $this->moneyModel = new MoneyEntry();
        $this->userModel = new User();

        $this->apiAccessRoles = class_exists('Roles')
            ? Roles::group('api.access')
            : ['ADMIN', 'SUPERADMIN', 'OPERATOR', 'SITE_MANAGER', 'SUPPORT', 'FINANCE'];
        $this->jobModifyRoles = class_exists('Roles')
            ? Roles::group('api.jobs.modify')
            : ['ADMIN', 'SUPERADMIN', 'OPERATOR'];
        $this->customerAccessRoles = class_exists('Roles')
            ? Roles::group('api.customers.read')
            : ['ADMIN', 'SUPERADMIN', 'FINANCE', 'SITE_MANAGER', 'SUPPORT'];
    }

    /**
     * API Authentication
     */
    public function authenticate()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $input = is_array($input) ? $input : [];

        $sanitized = [
            'username' => \InputSanitizer::string($input['username'] ?? null, 120),
            'password' => is_string($input['password'] ?? null) ? trim($input['password']) : null,
        ];

        $validator = new \Validator($sanitized);
        $validator->required('username', 'Username is required')->min('username', 3, 'Username must be at least 3 characters');
        $validator->required('password', 'Password is required')->min('password', 6, 'Password must be at least 6 characters');

        if ($validator->fails()) {
            $this->jsonResponse([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $username = $validator->get('username');
        $password = $sanitized['password'];

        if (class_exists('ApiRateLimiter')) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $rateKey = 'mobile.auth:' . strtolower($username) . ':' . $ip;
            if (!\ApiRateLimiter::check($rateKey, 10, 600)) {
                \ApiRateLimiter::sendLimitExceededResponse($rateKey, 600);
            }
            \ApiRateLimiter::record($rateKey, 10, 600);
        } else {
            $rateKey = null;
        }

        $user = $this->userModel->findByUsername($username);
        $passwordHash = (string)($user['password_hash'] ?? '');
        if (!$user || empty($passwordHash) || !password_verify($password, $passwordHash)) {
            $this->jsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash) {
                try {
                    $this->userModel->update($user['id'], ['password' => $password]);
                } catch (Exception $e) {
                    // Log but don't fail login if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash failed for user {$user['id']}: " . $e->getMessage());
                    }
                }
            }
        }
        // ===== ERR-014 FIX: End =====

        if (isset($rateKey)) {
            \ApiRateLimiter::reset($rateKey);
        }

        $token = JWTAuth::generateToken($user['id'], [
            'username' => $user['username'],
            'role' => $user['role'],
            'name' => $user['name']
        ]);

        $this->jsonResponse([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            $this->jsonResponse(['error' => 'Token required'], 401);
        }

        $newToken = JWTAuth::refreshToken($token);
        if (!$newToken) {
            $this->jsonResponse(['error' => 'Invalid token'], 401);
        }

        $this->jsonResponse([
            'success' => true,
            'token' => $newToken
        ]);
    }

    /**
     * Get user profile
     */
    public function profile()
    {
        $user = $this->requireUser();

        $this->jsonResponse([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'created_at' => $user['created_at']
            ]
        ]);
    }

    /**
     * Get jobs list
     */
    public function jobs()
    {
        $this->requireUser();

        $page = (int)($_GET['page'] ?? 1);
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        $offset = ($page - 1) * $limit;

        $status = $_GET['status'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $where = [];
        $params = [];

        if ($status) {
            $where[] = "j.status = ?";
            $params[] = $status;
        }

        if ($dateFrom) {
            $where[] = "DATE(j.start_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "DATE(j.start_at) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $db = Database::getInstance();
        $jobs = $db->fetchAll(
            "SELECT 
                j.*,
                c.name as customer_name,
                c.phone as customer_phone,
                s.name as service_name,
                a.line as address_line,
                a.city as address_city
             FROM jobs j
             LEFT JOIN customers c ON j.customer_id = c.id
             LEFT JOIN services s ON j.service_id = s.id
             LEFT JOIN addresses a ON j.address_id = a.id
             $whereClause
             ORDER BY j.start_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        $total = $db->fetch(
            "SELECT COUNT(*) as count FROM jobs j $whereClause",
            $params
        )['count'];

        $this->jsonResponse([
            'success' => true,
            'data' => $jobs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Get single job
     */
    public function job($id)
    {
        $this->requireUser();

        $job = $this->jobModel->find($id);
        if (!$job) {
            $this->jsonResponse(['error' => 'Job not found'], 404);
        }

        // Get payments
        $payments = $this->jobModel->getPayments($id);

        $this->jsonResponse([
            'success' => true,
            'data' => array_merge($job, ['payments' => $payments])
        ]);
    }

    /**
     * Update job status
     */
    public function updateJobStatus($id)
    {
        $user = $this->requireUser($this->jobModifyRoles);

        $input = json_decode(file_get_contents('php://input'), true);
        $status = $input['status'] ?? '';

        $validStatuses = ['SCHEDULED', 'DONE', 'CANCELLED'];
        if (!in_array($status, $validStatuses)) {
            $this->jsonResponse(['error' => 'Invalid status'], 400);
        }

        $job = $this->jobModel->find($id);
        if (!$job) {
            $this->jsonResponse(['error' => 'Job not found'], 404);
        }

        $this->jobModel->updateStatus($id, $status);

        // Log activity
        ActivityLogger::log('job_status_updated', 'job', $id, [
            'status' => $status,
            'updated_by' => $user['id']
        ]);

        $this->jsonResponse([
            'success' => true,
            'message' => 'Job status updated'
        ]);
    }

    /**
     * Get customers list
     */
    public function customers()
    {
        $this->requireUser();

        $page = (int)($_GET['page'] ?? 1);
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';

        $where = [];
        $params = [];

        if ($search) {
            $where[] = "(c.name LIKE ? OR c.phone LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $db = Database::getInstance();
        $customers = $db->fetchAll(
            "SELECT c.*, COUNT(a.id) as address_count
             FROM customers c
             LEFT JOIN addresses a ON c.id = a.customer_id
             $whereClause
             GROUP BY c.id
             ORDER BY c.name
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        $total = $db->fetch(
            "SELECT COUNT(*) as count FROM customers c $whereClause",
            $params
        )['count'];

        $this->jsonResponse([
            'success' => true,
            'data' => $customers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Get customer details
     */
    public function customer($id)
    {
        $this->requireUser($this->customerAccessRoles);

        $customer = $this->customerModel->find($id);
        if (!$customer) {
            $this->jsonResponse(['error' => 'Customer not found'], 404);
        }

        // Get addresses
        $db = Database::getInstance();
        $addresses = $db->fetchAll(
            "SELECT * FROM addresses WHERE customer_id = ? ORDER BY created_at",
            [$id]
        );

        // Get recent jobs
        $jobs = $db->fetchAll(
            "SELECT j.*, s.name as service_name
             FROM jobs j
             LEFT JOIN services s ON j.service_id = s.id
             WHERE j.customer_id = ?
             ORDER BY j.start_at DESC
             LIMIT 10",
            [$id]
        );

        $this->jsonResponse([
            'success' => true,
            'data' => array_merge($customer, [
                'addresses' => $addresses,
                'recent_jobs' => $jobs
            ])
        ]);
    }

    /**
     * Get dashboard stats
     */
    public function dashboard()
    {
        $this->requireUser($this->customerAccessRoles);

        $today = date('Y-m-d');
        $thisMonth = date('Y-m');

        // Job stats
        $jobStats = $this->jobModel->getStats();
        $todayJobs = $this->jobModel->getByDateRange($today, $today);
        $monthlyStats = $this->jobModel->getMonthlyStats(date('Y'), date('m'));

        // Customer stats
        $customerStats = $this->customerModel->getStats();

        // Finance stats
        $financeStats = [
            'total_income' => $this->moneyModel->getTotalIncome(),
            'total_expense' => $this->moneyModel->getTotalExpense(),
            'net_profit' => $this->moneyModel->getNetProfit(),
            'monthly_income' => $this->moneyModel->getTotalIncome($thisMonth . '-01', $thisMonth . '-31'),
            'monthly_expense' => $this->moneyModel->getTotalExpense($thisMonth . '-01', $thisMonth . '-31')
        ];

        $this->jsonResponse([
            'success' => true,
            'data' => [
                'jobs' => $jobStats,
                'customers' => $customerStats,
                'finance' => $financeStats,
                'today_jobs' => count($todayJobs),
                'monthly_stats' => $monthlyStats
            ]
        ]);
    }

    /**
     * Get services list
     */
    public function services()
    {
        $this->requireUser();

        $services = $this->serviceModel->getActive();

        $this->jsonResponse([
            'success' => true,
            'data' => $services
        ]);
    }

    /**
     * Create new job
     */
    public function createJob()
    {
        $user = $this->requireUser($this->jobModifyRoles);

        $input = json_decode(file_get_contents('php://input'), true);
        
        $validator = new Validator($input);
        $validator->required('customer_id', 'Customer ID required')
                 ->required('start_at', 'Start time required')
                 ->required('end_at', 'End time required')
                 ->datetime('start_at', 'Invalid start time format')
                 ->datetime('end_at', 'Invalid end time format');

        if ($validator->fails()) {
            $this->jsonResponse(['error' => $validator->firstError()], 400);
        }

        $jobData = [
            'service_id' => $input['service_id'] ?? null,
            'customer_id' => $input['customer_id'],
            'address_id' => $input['address_id'] ?? null,
            'start_at' => $input['start_at'],
            'end_at' => $input['end_at'],
            'status' => $input['status'] ?? 'SCHEDULED',
            'note' => $input['note'] ?? null,
            'total_amount' => $input['total_amount'] ?? 0,
            'assigned_to' => $user['id']
        ];

        $jobId = $this->jobModel->create($jobData);

        // Log activity
        ActivityLogger::log('job_created', 'job', $jobId, [
            'created_by' => $user['id'],
            'customer_id' => $jobData['customer_id']
        ]);

        $this->jsonResponse([
            'success' => true,
            'data' => ['id' => $jobId],
            'message' => 'Job created successfully'
        ], 201);
    }

    /**
     * Helper methods
     */
    private function getBearerToken()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    private function getAuthenticatedUser()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            return null;
        }

        return JWTAuth::getUserFromToken($token);
    }

    private function requireUser(?array $allowedRoles = null): array
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $role = $user['role'] ?? null;
        $allowed = $allowedRoles ?? $this->apiAccessRoles;

        if (!empty($allowed) && !in_array('*', $allowed, true)) {
            if ($role === null || !in_array($role, $allowed, true)) {
                $this->jsonResponse(['error' => 'Forbidden', 'message' => 'Role is not permitted to access this resource'], 403);
            }
        }

        if (isset($user['is_active']) && (int) $user['is_active'] !== 1) {
            $this->jsonResponse(['error' => 'Forbidden', 'message' => 'User is inactive'], 403);
        }

        return $user;
    }

    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

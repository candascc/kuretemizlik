<?php
/**
 * API v2 Job Controller
 * RESTful API for job management
 */

namespace App\Controllers\Api\V2;

class JobController
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
                $query .= (strpos($query, 'WHERE') !== false ? ' AND' : ' WHERE') . ' j.company_id = ?';
                $params[] = InputSanitizer::int($_GET['company_filter'], 1);
            }
            return [$query, $params];
        }
        
        // Others see only their company
        if ($this->companyId) {
            $query .= (strpos($query, 'WHERE') !== false ? ' AND' : ' WHERE') . ' j.company_id = ?';
            $params[] = (int)$this->companyId;
        } else {
            // No company assigned - return empty result
            $query .= (strpos($query, 'WHERE') !== false ? ' AND' : ' WHERE') . ' 1=0';
        }
        
        return [$query, $params];
    }
    
    /**
     * List jobs
     */
    public function index()
    {
        $this->requireAuthAndPermission('jobs.view');
        
        $page = InputSanitizer::int($_GET['page'] ?? 1, 1, 10000);
        $perPage = InputSanitizer::int($_GET['per_page'] ?? 20, 1, 100);
        $offset = ($page - 1) * $perPage;
        $status = isset($_GET['status']) ? strtoupper((string)$_GET['status']) : null;
        
        $query = "
            SELECT
                j.id,
                j.customer_id,
                j.service_id,
                j.assigned_to,
                j.start_at,
                j.end_at,
                j.status,
                j.total_amount,
                j.amount_paid,
                j.payment_status,
                j.note,
                j.created_at,
                j.updated_at,
                c.name AS customer_name,
                s.name AS staff_name
            FROM jobs j
            LEFT JOIN customers c ON j.customer_id = c.id
            LEFT JOIN staff s ON j.assigned_to = s.id
        ";
        
        $params = [];
        if ($status !== null && $status !== '') {
            $query .= " WHERE j.status = ?";
            $params[] = $status;
        }
        
        // Apply company scope
        list($query, $params) = $this->applyCompanyScope($query, $params);
        
        $query .= " ORDER BY j.start_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $jobs = $this->db->fetchAll($query, $params);
        
        $countQuery = "SELECT COUNT(*) as count FROM jobs j";
        $countParams = [];
        if ($status !== null && $status !== '') {
            $countQuery .= " WHERE j.status = ?";
            $countParams[] = $status;
        }
        list($countQuery, $countParams) = $this->applyCompanyScope($countQuery, $countParams);
        $total = $this->db->fetch($countQuery, $countParams);
        
        \ResponseFormatter::json([
            'success' => true,
            'data' => $jobs,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => (int)($total['count'] ?? 0),
                'total_pages' => $perPage > 0 ? (int)ceil(($total['count'] ?? 0) / $perPage) : 0
            ]
        ]);
        return;
    }
    
    /**
     * Get single job
     */
    public function show(int $id)
    {
        $this->requireAuthAndPermission('jobs.view');
        
        $query = "SELECT 
                j.id,
                j.customer_id,
                j.service_id,
                j.assigned_to,
                j.start_at,
                j.end_at,
                j.status,
                j.total_amount,
                j.amount_paid,
                j.payment_status,
                j.note,
                j.created_at,
                j.updated_at,
                c.name as customer_name,
                s.name as staff_name 
             FROM jobs j 
             LEFT JOIN customers c ON j.customer_id = c.id 
             LEFT JOIN staff s ON j.assigned_to = s.id 
             WHERE j.id = ?";
        list($query, $params) = $this->applyCompanyScope($query, [$id]);
        
        $job = $this->db->fetch($query, $params);
        
        if (!$job) {
            \ResponseFormatter::json(['error' => 'Not Found', 'message' => 'Job not found'], 404);
            return;
        }
        
        \ResponseFormatter::json(['success' => true, 'data' => $job]);
        return;
    }
    
    /**
     * Create job
     */
    public function create()
    {
        $this->requireAuthAndPermission('jobs.create');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $input = is_array($input) ? $input : [];
        if (!empty($_POST)) {
            $input = array_merge($input, $_POST);
        }

        $sanitized = [
            // ===== ERR-011 FIX: Add min/max validation =====
            'customer_id' => \InputSanitizer::int($input['customer_id'] ?? null, 1),
            'service_id' => \InputSanitizer::int($input['service_id'] ?? null, 1),
            'assigned_to' => \InputSanitizer::int($input['assigned_to'] ?? null, 1),
            'start_at' => \InputSanitizer::string($input['start_at'] ?? null, 32),
            'end_at' => \InputSanitizer::string($input['end_at'] ?? null, 32),
            'status' => strtoupper(\InputSanitizer::string($input['status'] ?? null, 32) ?? ''),
            'note' => \InputSanitizer::xss($input['note'] ?? null),
        ];

        if ($sanitized['status'] === '') {
            $sanitized['status'] = 'SCHEDULED';
        }

        $validator = new \Validator($sanitized);
        $validator
            ->required('customer_id', 'customer_id field is required')
            ->integer('customer_id', 'customer_id must be numeric');
        $validator
            ->required('start_at', 'start_at field is required')
            ->datetime('start_at', 'start_at must be a valid datetime (YYYY-MM-DD HH:MM)');

        if ($sanitized['service_id'] !== null) {
            $validator->integer('service_id', 'service_id must be numeric');
        }

        if ($sanitized['assigned_to'] !== null) {
            $validator->integer('assigned_to', 'assigned_to must be numeric');
        }

        if ($sanitized['end_at'] !== null) {
            $validator->datetime('end_at', 'end_at must be a valid datetime (YYYY-MM-DD HH:MM)');
            if ($sanitized['start_at'] !== null) {
                $validator->datetimeAfter('end_at', 'start_at', 'end_at must be after start_at');
            }
        }

        $validStatuses = ['SCHEDULED', 'DONE', 'CANCELLED'];
        $validator->in('status', $validStatuses, 'status must be one of: ' . implode(', ', $validStatuses));

        if ($sanitized['note'] !== null) {
            $validator->max('note', 1000, 'note cannot exceed 1000 characters');
        }

        if ($validator->fails()) {
            \ResponseFormatter::error('Validasyon hatası', $validator->errors(), 422);
            return;
        }

        $validated = $validator->validated();
        $validated['end_at'] = $validated['end_at'] ?? $validated['start_at'];
        $validated['status'] = $validated['status'] ?? 'SCHEDULED';

        // Get company_id for insert
        $companyId = $this->companyId;
        if ($this->userRole === 'SUPERADMIN' && isset($input['company_id'])) {
            $companyId = (int)$input['company_id'];
        }
        
        if (!$companyId) {
            \ResponseFormatter::error('No company assigned to user', 400);
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO jobs (customer_id, service_id, assigned_to, start_at, end_at, status, note, company_id, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $validated['customer_id'],
                $validated['service_id'],
                $validated['assigned_to'],
                $validated['start_at'],
                $validated['end_at'],
                $validated['status'],
                $validated['note'],
                $companyId,
                $now,
                $now
            ]
        );
        
        $jobId = $this->db->lastInsertId();
        
        \ResponseFormatter::json(['success' => true, 'data' => ['id' => $jobId], 'message' => 'Job created'], 201);
        return;
    }
    
    /**
     * Update job
     */
    public function update(int $id)
    {
        $this->requireAuthAndPermission('jobs.edit');
        
        // Verify company access
        $query = "SELECT company_id FROM jobs j WHERE j.id = ?";
        list($query, $params) = $this->applyCompanyScope($query, [$id]);
        $job = $this->db->fetch($query, $params);
        
        if (!$job) {
            \ResponseFormatter::json(['error' => 'Not Found', 'message' => 'Job not found'], 404);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $input = is_array($input) ? $input : [];
        if (!empty($_POST)) {
            $input = array_merge($input, $_POST);
        }

        // SECURITY: Remove company_id from input if present (prevent tenant switching)
        unset($input['company_id']);

        $allowedFields = ['status', 'assigned_to', 'note', 'start_at', 'end_at', 'service_id'];
        $payload = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                switch ($field) {
                    case 'status':
                        $payload[$field] = strtoupper(\InputSanitizer::string($input[$field], 32) ?? '');
                        break;
                    case 'note':
                        $payload[$field] = \InputSanitizer::xss($input[$field] ?? null);
                        break;
                    case 'assigned_to':
                    case 'service_id':
                        // ===== ERR-011 FIX: Add min/max validation =====
                        $payload[$field] = \InputSanitizer::int($input[$field] ?? null, 1);
                        break;
                    case 'start_at':
                    case 'end_at':
                        $payload[$field] = \InputSanitizer::string($input[$field] ?? null, 32);
                        break;
                }
            }
        }

        if (empty($payload)) {
            \ResponseFormatter::error('Güncelleme için alan belirtilmedi', 400);
            return;
        }

        $validator = new \Validator($payload);
        $validStatuses = ['SCHEDULED', 'DONE', 'CANCELLED'];

        if (array_key_exists('status', $payload)) {
            $validator->in('status', $validStatuses, 'status must be one of: ' . implode(', ', $validStatuses));
        }

        if (array_key_exists('assigned_to', $payload)) {
            $validator->integer('assigned_to', 'assigned_to must be numeric');
        }

        if (array_key_exists('service_id', $payload)) {
            $validator->integer('service_id', 'service_id must be numeric');
        }

        if (array_key_exists('start_at', $payload)) {
            $validator->datetime('start_at', 'start_at must be a valid datetime (YYYY-MM-DD HH:MM)');
        }

        if (array_key_exists('end_at', $payload)) {
            $validator->datetime('end_at', 'end_at must be a valid datetime (YYYY-MM-DD HH:MM)');
        }

        if (array_key_exists('note', $payload)) {
            $validator->max('note', 1000, 'note cannot exceed 1000 characters');
        }

        if (array_key_exists('start_at', $payload) && array_key_exists('end_at', $payload)) {
            $validator->datetimeAfter('end_at', 'start_at', 'end_at must be after start_at');
        }

        if ($validator->fails()) {
            \ResponseFormatter::error('Validasyon hatası', $validator->errors(), 422);
            return;
        }

        $validated = $validator->validated();

        $fields = [];
        $params = [];

        foreach ($validated as $field => $value) {
            if ($field === 'status' && $value === '') {
                continue;
            }
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }

        if (empty($fields)) {
            \ResponseFormatter::error('Güncellenecek geçerli alan bulunamadı', 400);
            return;
        }

        $fields[] = 'updated_at = ?';
        $params[] = date('Y-m-d H:i:s');
        $params[] = $id;

        // SECURITY: Verify company_id hasn't changed (enforce tenant isolation)
        // Re-fetch to ensure company_id is still the same (race condition protection)
        $currentJob = $this->db->fetch("SELECT company_id FROM jobs WHERE id = ?", [$id]);
        if ($currentJob && $originalCompanyId !== null && $currentJob['company_id'] != $originalCompanyId) {
            \ResponseFormatter::json(['error' => 'Forbidden', 'message' => 'Cannot change company_id'], 403);
            return;
        }

        $sql = "UPDATE jobs SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->query($sql, $params);
        
        // SECURITY: Post-update verification - ensure company_id is unchanged
        $verifyJob = $this->db->fetch("SELECT company_id FROM jobs WHERE id = ?", [$id]);
        if ($verifyJob && $originalCompanyId !== null && $verifyJob['company_id'] != $originalCompanyId) {
            // This should never happen, but log it if it does
            error_log("SECURITY WARNING: Job {$id} company_id changed during update from {$originalCompanyId} to {$verifyJob['company_id']}");
        }
        
        \ResponseFormatter::json(['success' => true, 'message' => 'Job updated', 'data' => ['id' => $id]]);
        return;
    }
}


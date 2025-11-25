<?php

/**
 * Resident Request Model
 */
class ResidentRequest
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id)
    {
        return $this->db->fetch('SELECT * FROM resident_requests WHERE id = ?', [$id]);
    }

    public function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['building_id'])) { 
            $where[] = 'building_id = ?'; 
            $params[] = $filters['building_id']; 
        }
        if (!empty($filters['unit_id'])) { 
            $where[] = 'unit_id = ?'; 
            $params[] = $filters['unit_id']; 
        }
        if (!empty($filters['resident_user_id'])) { 
            $where[] = 'resident_user_id = ?'; 
            $params[] = $filters['resident_user_id']; 
        }
        if (!empty($filters['request_type'])) { 
            $where[] = 'request_type = ?'; 
            $params[] = $filters['request_type']; 
        }
        if (!empty($filters['status'])) { 
            $where[] = 'status = ?'; 
            $params[] = $filters['status']; 
        }
        if (!empty($filters['priority'])) { 
            $where[] = 'priority = ?'; 
            $params[] = $filters['priority']; 
        }
        if (!empty($filters['assigned_to'])) { 
            $where[] = 'assigned_to = ?'; 
            $params[] = $filters['assigned_to']; 
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT * FROM resident_requests {$whereSql} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    public function paginate(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['building_id'])) {
            $where[] = 'building_id = ?';
            $params[] = $filters['building_id'];
        }
        if (!empty($filters['unit_id'])) {
            $where[] = 'unit_id = ?';
            $params[] = $filters['unit_id'];
        }
        if (!empty($filters['resident_user_id'])) {
            $where[] = 'resident_user_id = ?';
            $params[] = $filters['resident_user_id'];
        }
        if (!empty($filters['request_type'])) {
            $where[] = 'request_type = ?';
            $params[] = $filters['request_type'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[] = 'priority = ?';
            $params[] = $filters['priority'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = 'assigned_to = ?';
            $params[] = $filters['assigned_to'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countRow = $this->db->fetch("SELECT COUNT(*) as c FROM resident_requests {$whereSql}", $params);
        $total = (int)($countRow['c'] ?? 0);

        $dataSql = "SELECT * FROM resident_requests {$whereSql} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $dataParams = array_merge($params, [$limit, $offset]);
        $data = $this->db->fetchAll($dataSql, $dataParams);

        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    public function statusSummary(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (isset($filters['status'])) {
            unset($filters['status']);
        }

        if (!empty($filters['building_id'])) {
            $where[] = 'building_id = ?';
            $params[] = $filters['building_id'];
        }
        if (!empty($filters['unit_id'])) {
            $where[] = 'unit_id = ?';
            $params[] = $filters['unit_id'];
        }
        if (!empty($filters['resident_user_id'])) {
            $where[] = 'resident_user_id = ?';
            $params[] = $filters['resident_user_id'];
        }
        if (!empty($filters['request_type'])) {
            $where[] = 'request_type = ?';
            $params[] = $filters['request_type'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[] = 'priority = ?';
            $params[] = $filters['priority'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = 'assigned_to = ?';
            $params[] = $filters['assigned_to'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS total FROM resident_requests {$whereSql} GROUP BY status",
            $params
        );

        $statuses = [
            'open' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'closed' => 0,
        ];

        $total = 0;
        foreach ($rows as $row) {
            $status = $row['status'] ?? null;
            $count = (int)($row['total'] ?? 0);
            if ($status && array_key_exists($status, $statuses)) {
                $statuses[$status] = $count;
            }
            $total += $count;
        }

        return [
            'total' => $total,
            'by_status' => $statuses,
        ];
    }

    public function create(array $data)
    {
        $payload = [
            'building_id' => (int)$data['building_id'],
            'unit_id' => (int)$data['unit_id'],
            'resident_user_id' => $data['resident_user_id'] ?? null,
            'request_type' => $data['request_type'] ?? 'other',
            'category' => $data['category'] ?? null,
            'subject' => $data['subject'] ?? '',
            'description' => $data['description'] ?? '',
            'priority' => $data['priority'] ?? 'normal',
            'status' => $data['status'] ?? 'open',
            'assigned_to' => $data['assigned_to'] ?? null,
            'response' => $data['response'] ?? null,
            'resolved_at' => $data['resolved_at'] ?? null,
            'resolved_by' => $data['resolved_by'] ?? null,
            'satisfaction_rating' => $data['satisfaction_rating'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        return $this->db->insert('resident_requests', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = [
            'request_type', 'category', 'subject', 'description', 'priority', 
            'status', 'assigned_to', 'response', 'resolved_at', 'resolved_by', 
            'satisfaction_rating'
        ];
        
        $payload = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $payload[$f] = $data[$f];
            }
        }
        
        if (empty($payload)) {
            return 0;
        }
        
        $payload['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('resident_requests', $payload, 'id = ?', [$id]);
    }

    public function delete(int $id)
    {
        return $this->db->delete('resident_requests', 'id = ?', [$id]);
    }

    public function assign(int $id, int $assignedTo)
    {
        return $this->db->update('resident_requests', [
            'assigned_to' => $assignedTo,
            'status' => 'in_progress',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    public function resolve(int $id, int $resolvedBy, string $response = '')
    {
        return $this->db->update('resident_requests', [
            'status' => 'resolved',
            'response' => $response,
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolved_by' => $resolvedBy,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    public function close(int $id, int $closedBy)
    {
        return $this->db->update('resident_requests', [
            'status' => 'closed',
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolved_by' => $closedBy,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    public function rate(int $id, int $rating)
    {
        if ($rating < 1 || $rating > 5) {
            return false;
        }
        
        return $this->db->update('resident_requests', [
            'satisfaction_rating' => $rating,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    public function getStats(array $filters = []): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['building_id'])) { 
            $where[] = 'building_id = ?'; 
            $params[] = $filters['building_id']; 
        }
        if (!empty($filters['unit_id'])) { 
            $where[] = 'unit_id = ?'; 
            $params[] = $filters['unit_id']; 
        }
        if (!empty($filters['date_from'])) { 
            $where[] = 'date(created_at) >= date(?)'; 
            $params[] = $filters['date_from']; 
        }
        if (!empty($filters['date_to'])) { 
            $where[] = 'date(created_at) <= date(?)'; 
            $params[] = $filters['date_to']; 
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                AVG(satisfaction_rating) as avg_rating
             FROM resident_requests {$whereSql}",
            $params
        );

        return $stats ?: [
            'total' => 0,
            'open' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'closed' => 0,
            'avg_rating' => 0
        ];
    }

    public function getByType(array $filters = []): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['building_id'])) { 
            $where[] = 'building_id = ?'; 
            $params[] = $filters['building_id']; 
        }
        if (!empty($filters['date_from'])) { 
            $where[] = 'date(created_at) >= date(?)'; 
            $params[] = $filters['date_from']; 
        }
        if (!empty($filters['date_to'])) { 
            $where[] = 'date(created_at) <= date(?)'; 
            $params[] = $filters['date_to']; 
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        
        return $this->db->fetchAll(
            "SELECT 
                request_type,
                COUNT(*) as count,
                AVG(satisfaction_rating) as avg_rating
             FROM resident_requests {$whereSql}
             GROUP BY request_type
             ORDER BY count DESC",
            $params
        );
    }
}
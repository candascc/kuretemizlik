<?php
/**
 * Building Reservation Model
 */

class BuildingReservation
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id)
    {
        return $this->db->fetch("
            SELECT 
                br.*,
                b.name as building_name,
                f.facility_name,
                u.unit_number
            FROM building_reservations br
            LEFT JOIN buildings b ON br.building_id = b.id
            LEFT JOIN building_facilities f ON br.facility_id = f.id
            LEFT JOIN units u ON br.unit_id = u.id
            WHERE br.id = ?
        ", [$id]);
    }

    public function all($filters = [], $limit = 100, $offset = 0): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['building_id'])) {
            $where[] = 'br.building_id = ?';
            $params[] = $filters['building_id'];
        }
        if (!empty($filters['facility_id'])) {
            $where[] = 'br.facility_id = ?';
            $params[] = $filters['facility_id'];
        }
        if (!empty($filters['unit_id'])) {
            $where[] = 'br.unit_id = ?';
            $params[] = $filters['unit_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'br.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $where[] = 'br.start_date >= ?';
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = 'br.end_date <= ?';
            $params[] = $filters['end_date'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "
            SELECT 
                br.*,
                b.name as building_name,
                f.facility_name,
                f.facility_type,
                u.unit_number
            FROM building_reservations br
            LEFT JOIN buildings b ON br.building_id = b.id
            LEFT JOIN building_facilities f ON br.facility_id = f.id
            LEFT JOIN units u ON br.unit_id = u.id
            {$whereSql}
            ORDER BY br.start_date DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data)
    {
        $payload = [
            'building_id' => (int)$data['building_id'],
            'facility_id' => (int)$data['facility_id'],
            'unit_id' => isset($data['unit_id']) ? (int)$data['unit_id'] : null,
            'resident_name' => $data['resident_name'] ?? '',
            'resident_phone' => $data['resident_phone'] ?? null,
            'start_date' => $data['start_date'] ?? '',
            'end_date' => $data['end_date'] ?? '',
            'reservation_type' => $data['reservation_type'] ?? 'hourly',
            'total_amount' => isset($data['total_amount']) ? (float)$data['total_amount'] : 0,
            'deposit_amount' => isset($data['deposit_amount']) ? (float)$data['deposit_amount'] : 0,
            'status' => $data['status'] ?? 'pending',
            'approved_by' => isset($data['approved_by']) ? (int)$data['approved_by'] : null,
            'notes' => $data['notes'] ?? null,
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->insert('building_reservations', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = ['unit_id','resident_name','resident_phone','start_date','end_date','reservation_type','total_amount','deposit_amount','status','approved_by','notes','cancelled_reason'];
        $payload = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                if ($f === 'unit_id' || $f === 'approved_by') {
                    $payload[$f] = (int)$data[$f];
                } elseif ($f === 'total_amount' || $f === 'deposit_amount') {
                    $payload[$f] = (float)$data[$f];
                } else {
                    $payload[$f] = $data[$f];
                }
            }
        }
        if (empty($payload)) {
            return 0;
        }
        $payload['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('building_reservations', $payload, 'id = ?', [$id]);
    }

    public function delete(int $id)
    {
        return $this->db->delete('building_reservations', 'id = ?', [$id]);
    }

    public function checkAvailability($facilityId, $startDate, $endDate, $excludeId = null): bool
    {
        $params = [$facilityId, $startDate, $endDate, $startDate, $endDate];
        $whereClause = "facility_id = ? AND status NOT IN ('cancelled', 'rejected') AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?))";
        
        if ($excludeId) {
            $whereClause .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM building_reservations WHERE {$whereClause}",
            $params
        );
        
        return ($result['count'] ?? 0) == 0;
    }
}


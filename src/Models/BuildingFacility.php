<?php
/**
 * Building Facility Model
 */

class BuildingFacility
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id)
    {
        return $this->db->fetch('SELECT * FROM building_facilities WHERE id = ?', [$id]);
    }

    public function all($filters = [], $limit = 100, $offset = 0): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['building_id'])) {
            $where[] = 'building_id = ?';
            $params[] = $filters['building_id'];
        }
        if (!empty($filters['facility_type'])) {
            $where[] = 'facility_type = ?';
            $params[] = $filters['facility_type'];
        }
        if (!empty($filters['is_active'])) {
            $where[] = 'is_active = ?';
            $params[] = $filters['is_active'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT * FROM building_facilities {$whereSql} ORDER BY facility_name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data)
    {
        $payload = [
            'building_id' => (int)$data['building_id'],
            'facility_name' => $data['facility_name'] ?? '',
            'facility_type' => $data['facility_type'] ?? 'other',
            'description' => $data['description'] ?? null,
            'capacity' => isset($data['capacity']) ? (int)$data['capacity'] : null,
            'hourly_rate' => isset($data['hourly_rate']) ? (float)$data['hourly_rate'] : 0,
            'daily_rate' => isset($data['daily_rate']) ? (float)$data['daily_rate'] : 0,
            'requires_approval' => !empty($data['requires_approval']) ? 1 : 0,
            'max_advance_days' => isset($data['max_advance_days']) ? (int)$data['max_advance_days'] : 30,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->insert('building_facilities', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = ['facility_name','facility_type','description','capacity','hourly_rate','daily_rate','requires_approval','max_advance_days','is_active'];
        $payload = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                if ($f === 'capacity' || $f === 'max_advance_days') {
                    $payload[$f] = (int)$data[$f];
                } elseif ($f === 'hourly_rate' || $f === 'daily_rate') {
                    $payload[$f] = (float)$data[$f];
                } elseif ($f === 'requires_approval' || $f === 'is_active') {
                    $payload[$f] = !empty($data[$f]) ? 1 : 0;
                } else {
                    $payload[$f] = $data[$f];
                }
            }
        }
        if (empty($payload)) {
            return 0;
        }
        $payload['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('building_facilities', $payload, 'id = ?', [$id]);
    }

    public function delete(int $id)
    {
        return $this->db->delete('building_facilities', 'id = ?', [$id]);
    }

    public function getByBuilding(int $buildingId): array
    {
        return $this->all(['building_id' => $buildingId, 'is_active' => 1]);
    }
}


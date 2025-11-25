<?php

/**
 * Building Document Model
 */
class BuildingDocument
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id)
    {
        return $this->db->fetch('SELECT * FROM building_documents WHERE id = ?', [$id]);
    }

    public function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        return $this->all($filters, $limit, $offset);
    }

    public function all(array $filters = [], int $limit = null, int $offset = 0): array
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
        if (!empty($filters['document_type'])) { 
            $where[] = 'document_type = ?'; 
            $params[] = $filters['document_type']; 
        }
        if (!empty($filters['is_public'])) { 
            $where[] = 'is_public = ?'; 
            $params[] = $filters['is_public']; 
        }
        if (!empty($filters['uploaded_by'])) { 
            $where[] = 'uploaded_by = ?'; 
            $params[] = $filters['uploaded_by']; 
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT * FROM building_documents {$whereSql} ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data)
    {
        $payload = [
            'building_id' => (int)$data['building_id'],
            'unit_id' => isset($data['unit_id']) ? (int)$data['unit_id'] : null,
            'document_type' => $data['document_type'] ?? 'other',
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? null,
            'file_path' => $data['file_path'] ?? '',
            'file_name' => $data['file_name'] ?? '',
            'file_size' => isset($data['file_size']) ? (int)$data['file_size'] : 0,
            'mime_type' => $data['mime_type'] ?? '',
            'is_public' => !empty($data['is_public']) ? 1 : 0,
            'uploaded_by' => (int)$data['uploaded_by'],
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        return $this->db->insert('building_documents', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = [
            'title', 'description', 'document_type', 'is_public'
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
        
        return $this->db->update('building_documents', $payload, 'id = ?', [$id]);
    }

    public function delete(int $id)
    {
        $document = $this->find($id);
        if ($document && file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }
        return $this->db->delete('building_documents', 'id = ?', [$id]);
    }

    public function getByBuilding(int $buildingId, bool $publicOnly = false): array
    {
        $where = ['building_id = ?'];
        $params = [$buildingId];
        
        if ($publicOnly) {
            $where[] = 'is_public = 1';
        }
        
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        return $this->db->fetchAll(
            "SELECT * FROM building_documents {$whereSql} ORDER BY created_at DESC",
            $params
        );
    }

    public function getByUnit(int $unitId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM building_documents WHERE unit_id = ? ORDER BY created_at DESC",
            [$unitId]
        );
    }

    public function getStats(array $filters = []): array
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
        
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN document_type = 'contract' THEN 1 ELSE 0 END) as contracts,
                SUM(CASE WHEN document_type = 'invoice' THEN 1 ELSE 0 END) as invoices,
                SUM(CASE WHEN document_type = 'receipt' THEN 1 ELSE 0 END) as receipts,
                SUM(CASE WHEN document_type = 'meeting_minutes' THEN 1 ELSE 0 END) as minutes,
                SUM(CASE WHEN document_type = 'announcement' THEN 1 ELSE 0 END) as announcements,
                SUM(file_size) as total_size
             FROM building_documents {$whereSql}",
            $params
        );

        return $stats ?: [
            'total' => 0,
            'contracts' => 0,
            'invoices' => 0,
            'receipts' => 0,
            'minutes' => 0,
            'announcements' => 0,
            'total_size' => 0
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
                document_type,
                COUNT(*) as count,
                SUM(file_size) as total_size
             FROM building_documents {$whereSql}
             GROUP BY document_type
             ORDER BY count DESC",
            $params
        );
    }
}
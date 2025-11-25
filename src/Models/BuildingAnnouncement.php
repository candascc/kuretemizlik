<?php
/**
 * Building Announcement Model
 */

class BuildingAnnouncement
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id)
    {
        return $this->db->fetch('SELECT * FROM building_announcements WHERE id = ?', [$id]);
    }

    public function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['building_id'])) { $where[] = 'building_id = ?'; $params[] = $filters['building_id']; }
        if (!empty($filters['type'])) { $where[] = 'announcement_type = ?'; $params[] = $filters['type']; }
        if (isset($filters['is_pinned'])) { $where[] = 'is_pinned = ?'; $params[] = (int)$filters['is_pinned']; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT * FROM building_announcements {$whereSql} ORDER BY is_pinned DESC, date(publish_date) DESC, id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data)
    {
        $payload = [
            'building_id' => (int)$data['building_id'],
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'announcement_type' => $data['announcement_type'] ?? 'info',
            'priority' => isset($data['priority']) ? (int)$data['priority'] : 0,
            'is_pinned' => !empty($data['is_pinned']) ? 1 : 0,
            'publish_date' => $data['publish_date'] ?? date('Y-m-d'),
            'expire_date' => $data['expire_date'] ?? null,
            'send_email' => !empty($data['send_email']) ? 1 : 0,
            'send_sms' => !empty($data['send_sms']) ? 1 : 0,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('building_announcements', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = ['title','content','announcement_type','priority','is_pinned','publish_date','expire_date','send_email','send_sms'];
        $payload = [];
        foreach ($fields as $f) { if (array_key_exists($f, $data)) { $payload[$f] = $data[$f]; } }
        if (empty($payload)) { return 0; }
        return $this->db->update('building_announcements', $payload, 'id = ?', [$id]);
    }

    public function delete(int $id)
    {
        return $this->db->delete('building_announcements', 'id = ?', [$id]);
    }

    /**
     * Duyuruları getir (extended version with joins)
     */
    public function all($filters = [], $limit = null, $offset = 0)
    {
        $sql = "
            SELECT 
                ba.*,
                b.name as building_name,
                u.username as created_by_name
            FROM building_announcements ba
            LEFT JOIN buildings b ON ba.building_id = b.id
            LEFT JOIN users u ON ba.created_by = u.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['building_id'])) {
            $sql .= " AND ba.building_id = ?";
            $params[] = $filters['building_id'];
        }

        if (!empty($filters['announcement_type'])) {
            $sql .= " AND ba.announcement_type = ?";
            $params[] = $filters['announcement_type'];
        }

        if (!empty($filters['active_only'])) {
            $sql .= " AND ba.publish_date <= date('now') 
                     AND (ba.expire_date IS NULL OR ba.expire_date >= date('now'))";
        }

        $sql .= " ORDER BY ba.is_pinned DESC, ba.priority DESC, ba.publish_date DESC";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * ID ile duyuru getir (extended version with joins)
     */
    public function findWithDetails($id)
    {
        return $this->db->fetch(
            "SELECT 
                ba.*,
                b.name as building_name,
                u.username as created_by_name
            FROM building_announcements ba
            LEFT JOIN buildings b ON ba.building_id = b.id
            LEFT JOIN users u ON ba.created_by = u.id
            WHERE ba.id = ?",
            [$id]
        );
    }

    /**
     * Aktif duyuruları getir
     */
    public function getActive($buildingId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM building_announcements 
            WHERE building_id = ? 
            AND publish_date <= date('now')
            AND (expire_date IS NULL OR expire_date >= date('now'))
            ORDER BY is_pinned DESC, priority DESC, publish_date DESC",
            [$buildingId]
        );
    }
}


<?php
/**
 * ActivityLog Model
 */

class ActivityLog
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Tüm aktivite loglarını getir
     */
    public function all($limit = null, $offset = 0)
    {
        $sql = "
            SELECT 
                al.*,
                u.username as actor_name
            FROM activity_log al
            LEFT JOIN users u ON al.actor_id = u.id
            ORDER BY al.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * ID ile aktivite log getir
     */
    public function find($id)
    {
        return $this->db->fetch(
            "SELECT 
                al.*,
                u.username as actor_name
             FROM activity_log al
             LEFT JOIN users u ON al.actor_id = u.id
             WHERE al.id = ?",
            [$id]
        );
    }
    
    /**
     * Kullanıcı aktivitelerini getir
     */
    public function getByUser($userId, $limit = null)
    {
        $sql = "
            SELECT 
                al.*,
                u.username as actor_name
            FROM activity_log al
            LEFT JOIN users u ON al.actor_id = u.id
            WHERE al.actor_id = ?
            ORDER BY al.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$userId, $limit]);
        }
        
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    /**
     * Entity aktivitelerini getir
     */
    public function getByEntity($entity, $limit = null)
    {
        $sql = "
            SELECT 
                al.*,
                u.username as actor_name
            FROM activity_log al
            LEFT JOIN users u ON al.actor_id = u.id
            WHERE al.entity = ?
            ORDER BY al.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$entity, $limit]);
        }
        
        return $this->db->fetchAll($sql, [$entity]);
    }
    
    /**
     * Action aktivitelerini getir
     */
    public function getByAction($action, $limit = null)
    {
        $sql = "
            SELECT 
                al.*,
                u.username as actor_name
            FROM activity_log al
            LEFT JOIN users u ON al.actor_id = u.id
            WHERE al.action = ?
            ORDER BY al.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$action, $limit]);
        }
        
        return $this->db->fetchAll($sql, [$action]);
    }
    
    /**
     * Tarih aralı�Yına göre getir
     */
    public function getByDateRange($startDate, $endDate, $limit = null)
    {
        $sql = "
            SELECT 
                al.*,
                u.username as actor_name
            FROM activity_log al
            LEFT JOIN users u ON al.actor_id = u.id
            WHERE DATE(al.created_at) BETWEEN ? AND ?
            ORDER BY al.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$startDate, $endDate, $limit]);
        }
        
        return $this->db->fetchAll($sql, [$startDate, $endDate]);
    }
    
    /**
     * Yeni aktivite log olu�Ytur
     */
    public function create($data)
    {
        $logData = [
            'actor_id' => $data['actor_id'] ?? null,
            'actor_role' => $data['actor_role'] ?? null,
            'action' => $data['action'],
            'entity' => $data['entity'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'meta_json' => !empty($data['meta']) ? json_encode($data['meta'], JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('activity_log', $logData);
    }
    
    /**
     * Aktivite log sil
     */
    public function delete($id)
    {
        return $this->db->delete('activity_log', 'id = ?', [$id]);
    }
    
    /**
     * Eski logları temizle
     */
    public function cleanup($days = 90)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $this->db->delete(
            'activity_log',
            'created_at < ?',
            [$cutoffDate]
        );
    }
    
    /**
     * Aktivite log sayısı
     */
    public function count()
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM activity_log");
        return $result['count'];
    }
    
    /**
     * Kullanıcı aktivite sayısı
     */
    public function countByUser($userId)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM activity_log WHERE actor_id = ?",
            [$userId]
        );
        return $result['count'];
    }
    
    /**
     * Entity aktivite sayısı
     */
    public function countByEntity($entity)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM activity_log WHERE entity = ?",
            [$entity]
        );
        return $result['count'];
    }
    
    /**
     * Action aktivite sayısı
     */
    public function countByAction($action)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM activity_log WHERE action = ?",
            [$action]
        );
        return $result['count'];
    }
    
    /**
     * Aktivite istatistikleri
     */
    public function getStats()
    {
        $total = $this->count();
        $today = $this->db->fetch(
            "SELECT COUNT(*) as count FROM activity_log WHERE DATE(created_at) = DATE('now')"
        )['count'];
        
        $thisWeek = $this->db->fetch(
            "SELECT COUNT(*) as count FROM activity_log WHERE DATE(created_at) >= DATE('now', '-7 days')"
        )['count'];
        
        $thisMonth = $this->db->fetch(
            "SELECT COUNT(*) as count FROM activity_log WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')"
        )['count'];
        
        return [
            'total' => $total,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth
        ];
    }
    
    /**
     * En aktif kullanıcılar
     */
    public function getTopUsers($limit = 10)
    {
        return $this->db->fetchAll(
            "SELECT 
                u.username,
                COUNT(al.id) as activity_count
             FROM activity_log al
             LEFT JOIN users u ON al.actor_id = u.id
             WHERE al.actor_id IS NOT NULL
             GROUP BY al.actor_id, u.username
             ORDER BY activity_count DESC
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * En çok yapılan i�Ylemler
     */
    public function getTopActions($limit = 10)
    {
        return $this->db->fetchAll(
            "SELECT 
                action,
                COUNT(*) as count
             FROM activity_log
             GROUP BY action
             ORDER BY count DESC
             LIMIT ?",
            [$limit]
        );
    }
}

<?php
/**
 * Appointment Model
 * SECURITY: Multi-tenant support with CompanyScope
 */

class Appointment
{
    use CompanyScope; // SECURITY: Multi-tenant isolation
    
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Tüm randevuları getir
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT a.*, c.name as customer_name, c.phone as customer_phone, 
                       s.name as service_name, u.username as assigned_user
                FROM appointments a
                LEFT JOIN customers c ON a.customer_id = c.id
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN users u ON a.assigned_to = u.id";
        
        $conditions = [];
        $params = [];
        
        // SECURITY: Add company scope filter
        $where = $this->scopeToCompany('WHERE 1=1', 'a');
        if (strpos($where, 'AND') !== false) {
            $conditions[] = str_replace('WHERE 1=1 AND', '', $where);
        }

        // Filtreler
        if (!empty($filters['status'])) {
            $conditions[] = "a.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "a.appointment_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "a.appointment_date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['customer_id'])) {
            $conditions[] = "a.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $conditions[] = "a.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        } else {
            // If no filters but company scope exists, add it
            if (strpos($where, 'AND') !== false) {
                $sql .= " " . $where;
            }
        }

        $sql .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * ID'ye göre randevu getir
     */
    public function find($id)
    {
        $where = $this->scopeToCompany('WHERE a.id = ?', 'a');
        $sql = "SELECT a.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                       s.name as service_name, u.username as assigned_user
                FROM appointments a
                LEFT JOIN customers c ON a.customer_id = c.id
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN users u ON a.assigned_to = u.id
                " . $where;
        
        $appointment = $this->db->fetch($sql, [$id]);
        
        // SECURITY: Verify company access
        if ($appointment && !$this->verifyCompanyAccess($appointment['company_id'] ?? null)) {
            return null;
        }
        
        return $appointment;
    }

    /**
     * Yeni randevu oluştur
     */
    public function create($data)
    {
        // SECURITY: Enforce tenant isolation (company_id injection)
        $sql = "INSERT INTO appointments (
                    customer_id, service_id, title, description, appointment_date, 
                    start_time, end_time, status, priority, assigned_to, notes, company_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['customer_id'],
            $data['service_id'] ?? null,
            $data['title'],
            $data['description'] ?? null,
            $data['appointment_date'],
            $data['start_time'],
            $data['end_time'] ?? null,
            $data['status'] ?? 'SCHEDULED',
            $data['priority'] ?? 'MEDIUM',
            $data['assigned_to'] ?? null,
            $data['notes'] ?? null,
            $this->getCompanyIdForInsert() // SECURITY: Auto-inject company_id
        ];

        $this->db->query($sql, $params);
        return $this->db->getPdo()->lastInsertId();
    }

    /**
     * Randevu güncelle
     */
    public function update($id, $data)
    {
        $sql = "UPDATE appointments SET 
                    customer_id = ?, service_id = ?, title = ?, description = ?, 
                    appointment_date = ?, start_time = ?, end_time = ?, status = ?, 
                    priority = ?, assigned_to = ?, notes = ?, updated_at = datetime('now')
                WHERE id = ?";
        
        $params = [
            $data['customer_id'],
            $data['service_id'] ?? null,
            $data['title'],
            $data['description'] ?? null,
            $data['appointment_date'],
            $data['start_time'],
            $data['end_time'] ?? null,
            $data['status'] ?? 'SCHEDULED',
            $data['priority'] ?? 'MEDIUM',
            $data['assigned_to'] ?? null,
            $data['notes'] ?? null,
            $id
        ];

        $this->db->query($sql, $params);
        return $this->db->getPdo()->lastInsertId();
    }

    /**
     * Randevu sil
     */
    public function delete($id)
    {
        $sql = "DELETE FROM appointments WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->rowCount();
    }

    /**
     * Randevu durumunu güncelle
     */
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE appointments SET status = ?, updated_at = datetime('now') WHERE id = ?";
        $stmt = $this->db->query($sql, [$status, $id]);
        return $stmt->rowCount();
    }

    /**
     * Bugünkü randevuları getir
     */
    public function getToday()
    {
        $today = date('Y-m-d');
        $where = $this->scopeToCompany('WHERE a.appointment_date = ? AND a.status IN (\'SCHEDULED\', \'CONFIRMED\')', 'a');
        $sql = "SELECT a.*, c.name as customer_name, c.phone as customer_phone,
                       s.name as service_name, u.username as assigned_user
                FROM appointments a
                LEFT JOIN customers c ON a.customer_id = c.id
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN users u ON a.assigned_to = u.id
                " . $where . "
                ORDER BY a.start_time ASC";
        
        return $this->db->fetchAll($sql, [$today]);
    }

    /**
     * Bu haftaki randevuları getir
     */
    public function getThisWeek()
    {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        
        $where = $this->scopeToCompany('WHERE a.appointment_date BETWEEN ? AND ? AND a.status IN (\'SCHEDULED\', \'CONFIRMED\')', 'a');
        $sql = "SELECT a.*, c.name as customer_name, c.phone as customer_phone,
                       s.name as service_name, u.username as assigned_user
                FROM appointments a
                LEFT JOIN customers c ON a.customer_id = c.id
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN users u ON a.assigned_to = u.id
                " . $where . "
                ORDER BY a.appointment_date ASC, a.start_time ASC";
        
        return $this->db->fetchAll($sql, [$startOfWeek, $endOfWeek]);
    }

    /**
     * Yaklaşan randevuları getir (sonraki 7 gün)
     */
    public function getUpcoming($days = 7)
    {
        $today = date('Y-m-d');
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $where = $this->scopeToCompany('WHERE a.appointment_date BETWEEN ? AND ? AND a.status IN (\'SCHEDULED\', \'CONFIRMED\')', 'a');
        $sql = "SELECT a.*, c.name as customer_name, c.phone as customer_phone,
                       s.name as service_name, u.username as assigned_user
                FROM appointments a
                LEFT JOIN customers c ON a.customer_id = c.id
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN users u ON a.assigned_to = u.id
                " . $where . "
                ORDER BY a.appointment_date ASC, a.start_time ASC";
        
        return $this->db->fetchAll($sql, [$today, $futureDate]);
    }

    /**
     * Müşteriye ait randevuları getir
     */
    public function getByCustomer($customerId)
    {
        $where = $this->scopeToCompany('WHERE a.customer_id = ?', 'a');
        $sql = "SELECT a.*, s.name as service_name, u.username as assigned_user
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN users u ON a.assigned_to = u.id
                " . $where . "
                ORDER BY a.appointment_date DESC, a.start_time DESC";
        
        return $this->db->fetchAll($sql, [$customerId]);
    }

    /**
     * Randevu istatistikleri
     */
    public function getStats()
    {
        $today = date('Y-m-d');
        $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
        $thisWeekEnd = date('Y-m-d', strtotime('sunday this week'));
        $thisMonthStart = date('Y-m-01');
        $thisMonthEnd = date('Y-m-t');

        $stats = [];

        // SECURITY: Add company scope to all stats queries
        $whereBase = $this->scopeToCompany('WHERE 1=1', 'a');
        $whereClause = str_replace('WHERE 1=1', '', $whereBase);
        if ($whereClause) {
            $whereClause = ' ' . trim($whereClause);
        }
        
        // Bugünkü randevular
        $sql = "SELECT COUNT(*) as count FROM appointments a WHERE a.appointment_date = ? AND a.status IN ('SCHEDULED', 'CONFIRMED')" . $whereClause;
        $stats['today'] = $this->db->fetch($sql, [$today])['count'];

        // Bu haftaki randevular
        $sql = "SELECT COUNT(*) as count FROM appointments a WHERE a.appointment_date BETWEEN ? AND ? AND a.status IN ('SCHEDULED', 'CONFIRMED')" . $whereClause;
        $stats['this_week'] = $this->db->fetch($sql, [$thisWeekStart, $thisWeekEnd])['count'];

        // Bu ayki randevular
        $sql = "SELECT COUNT(*) as count FROM appointments a WHERE a.appointment_date BETWEEN ? AND ? AND a.status IN ('SCHEDULED', 'CONFIRMED')" . $whereClause;
        $stats['this_month'] = $this->db->fetch($sql, [$thisMonthStart, $thisMonthEnd])['count'];

        // Toplam randevular
        $sql = "SELECT COUNT(*) as count FROM appointments a" . ($whereClause ? ' ' . trim($whereClause) : '');
        $stats['total'] = $this->db->fetch($sql)['count'];

        // Durum bazında sayılar
        $sql = "SELECT a.status, COUNT(*) as count FROM appointments a" . ($whereClause ? ' ' . trim($whereClause) : '') . " GROUP BY a.status";
        $statusCounts = $this->db->fetchAll($sql);
        $stats['by_status'] = [];
        foreach ($statusCounts as $row) {
            $stats['by_status'][$row['status']] = $row['count'];
        }

        return $stats;
    }

    /**
     * Randevu durumları
     */
    public static function getStatuses()
    {
        return [
            'SCHEDULED' => 'Planlandı',
            'CONFIRMED' => 'Onaylandı',
            'COMPLETED' => 'Tamamlandı',
            'CANCELLED' => 'İptal Edildi',
            'NO_SHOW' => 'Gelmedi'
        ];
    }

    /**
     * Öncelik seviyeleri
     */
    public static function getPriorities()
    {
        return [
            'LOW' => 'Düşük',
            'MEDIUM' => 'Orta',
            'HIGH' => 'Yüksek',
            'URGENT' => 'Acil'
        ];
    }
}

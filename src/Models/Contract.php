<?php

class Contract
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Tüm sözleşmeleri getir
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT c.*, cust.name as customer_name, cust.phone as customer_phone, 
                       u.username as created_by_user
                FROM contracts c
                LEFT JOIN customers cust ON c.customer_id = cust.id
                LEFT JOIN users u ON c.created_by = u.id";
        
        $conditions = [];
        $params = [];

        // Filtreler
        if (!empty($filters['status'])) {
            $conditions[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['contract_type'])) {
            $conditions[] = "c.contract_type = ?";
            $params[] = $filters['contract_type'];
        }

        if (!empty($filters['customer_id'])) {
            $conditions[] = "c.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "c.start_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "c.start_date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['expiring_soon'])) {
            $days = $filters['expiring_soon'];
            $futureDate = date('Y-m-d', strtotime("+{$days} days"));
            $conditions[] = "c.end_date IS NOT NULL AND c.end_date <= ? AND c.status = 'ACTIVE'";
            $params[] = $futureDate;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY c.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * ID'ye göre sözleşme getir
     */
    public function find($id)
    {
        $sql = "SELECT c.*, cust.name as customer_name, cust.phone as customer_phone, 
                       cust.email as customer_email, u.username as created_by_user
                FROM contracts c
                LEFT JOIN customers cust ON c.customer_id = cust.id
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Yeni sözleşme oluştur
     */
    public function create($data)
    {
        $sql = "INSERT INTO contracts (
                    customer_id, contract_number, title, description, contract_type,
                    start_date, end_date, total_amount, payment_terms, status,
                    auto_renewal, renewal_period_days, file_path, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['customer_id'],
            $data['contract_number'],
            $data['title'],
            $data['description'] ?? null,
            $data['contract_type'] ?? 'CLEANING',
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['total_amount'] ?? null,
            $data['payment_terms'] ?? null,
            $data['status'] ?? 'DRAFT',
            $data['auto_renewal'] ?? 0,
            $data['renewal_period_days'] ?? null,
            $data['file_path'] ?? null,
            $data['notes'] ?? null,
            $data['created_by']
        ];

        $this->db->query($sql, $params);
        return $this->db->getPdo()->lastInsertId();
    }

    /**
     * Sözleşme güncelle
     */
    public function update($id, $data)
    {
        $sql = "UPDATE contracts SET 
                    customer_id = ?, contract_number = ?, title = ?, description = ?, 
                    contract_type = ?, start_date = ?, end_date = ?, total_amount = ?, 
                    payment_terms = ?, status = ?, auto_renewal = ?, renewal_period_days = ?, 
                    file_path = ?, notes = ?, updated_at = datetime('now')
                WHERE id = ?";
        
        $params = [
            $data['customer_id'],
            $data['contract_number'],
            $data['title'],
            $data['description'] ?? null,
            $data['contract_type'] ?? 'CLEANING',
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['total_amount'] ?? null,
            $data['payment_terms'] ?? null,
            $data['status'] ?? 'DRAFT',
            $data['auto_renewal'] ?? 0,
            $data['renewal_period_days'] ?? null,
            $data['file_path'] ?? null,
            $data['notes'] ?? null,
            $id
        ];

        $stmt = $this->db->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Sözleşme sil
     */
    public function delete($id)
    {
        $sql = "DELETE FROM contracts WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->rowCount();
    }

    /**
     * Sözleşme durumunu güncelle
     */
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE contracts SET status = ?, updated_at = datetime('now') WHERE id = ?";
        $stmt = $this->db->query($sql, [$status, $id]);
        return $stmt->rowCount();
    }

    /**
     * Müşteriye ait sözleşmeleri getir
     */
    public function getByCustomer($customerId)
    {
        $sql = "SELECT c.*, u.username as created_by_user
                FROM contracts c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.customer_id = ?
                ORDER BY c.created_at DESC";
        
        return $this->db->fetchAll($sql, [$customerId]);
    }

    /**
     * Aktif sözleşmeleri getir
     */
    public function getActive()
    {
        $sql = "SELECT c.*, cust.name as customer_name, cust.phone as customer_phone
                FROM contracts c
                LEFT JOIN customers cust ON c.customer_id = cust.id
                WHERE c.status = 'ACTIVE'
                ORDER BY c.end_date ASC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Süresi yaklaşan sözleşmeleri getir
     */
    public function getExpiringSoon($days = 30)
    {
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $sql = "SELECT c.*, cust.name as customer_name, cust.phone as customer_phone
                FROM contracts c
                LEFT JOIN customers cust ON c.customer_id = cust.id
                WHERE c.end_date IS NOT NULL AND c.end_date <= ? AND c.status = 'ACTIVE'
                ORDER BY c.end_date ASC";
        
        return $this->db->fetchAll($sql, [$futureDate]);
    }

    /**
     * Sözleşme ödemelerini getir
     */
    public function getPayments($contractId)
    {
        $sql = "SELECT * FROM contract_payments WHERE contract_id = ? ORDER BY due_date ASC";
        return $this->db->fetchAll($sql, [$contractId]);
    }

    /**
     * Sözleşme eklerini getir
     */
    public function getAttachments($contractId)
    {
        $sql = "SELECT ca.*, u.username as uploaded_by_user
                FROM contract_attachments ca
                LEFT JOIN users u ON ca.uploaded_by = u.id
                WHERE ca.contract_id = ?
                ORDER BY ca.created_at DESC";
        
        return $this->db->fetchAll($sql, [$contractId]);
    }

    /**
     * Ödeme ekle
     */
    public function addPayment($contractId, $data)
    {
        $sql = "INSERT INTO contract_payments (
                    contract_id, amount, payment_date, payment_method, 
                    status, due_date, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $contractId,
            $data['amount'],
            $data['payment_date'],
            $data['payment_method'] ?? 'CASH',
            $data['status'] ?? 'PENDING',
            $data['due_date'] ?? null,
            $data['notes'] ?? null
        ];

        $this->db->query($sql, $params);
        return $this->db->getPdo()->lastInsertId();
    }

    /**
     * Ödeme güncelle
     */
    public function updatePayment($paymentId, $data)
    {
        $sql = "UPDATE contract_payments SET 
                    amount = ?, payment_date = ?, payment_method = ?, 
                    status = ?, due_date = ?, notes = ?, updated_at = datetime('now')
                WHERE id = ?";
        
        $params = [
            $data['amount'],
            $data['payment_date'],
            $data['payment_method'] ?? 'CASH',
            $data['status'] ?? 'PENDING',
            $data['due_date'] ?? null,
            $data['notes'] ?? null,
            $paymentId
        ];

        $stmt = $this->db->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Ödeme sil
     */
    public function deletePayment($paymentId)
    {
        $sql = "DELETE FROM contract_payments WHERE id = ?";
        $stmt = $this->db->query($sql, [$paymentId]);
        return $stmt->rowCount();
    }

    /**
     * Dosya ekle
     */
    public function addAttachment($contractId, $data)
    {
        $sql = "INSERT INTO contract_attachments (
                    contract_id, file_name, file_path, file_size, 
                    mime_type, uploaded_by
                ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $contractId,
            $data['file_name'],
            $data['file_path'],
            $data['file_size'] ?? null,
            $data['mime_type'] ?? null,
            $data['uploaded_by']
        ];

        $this->db->query($sql, $params);
        return $this->db->getPdo()->lastInsertId();
    }

    /**
     * Dosya sil
     */
    public function deleteAttachment($attachmentId)
    {
        $sql = "DELETE FROM contract_attachments WHERE id = ?";
        $stmt = $this->db->query($sql, [$attachmentId]);
        return $stmt->rowCount();
    }

    /**
     * Sözleşme numarası oluştur
     */
    public function generateContractNumber()
    {
        $year = date('Y');
        
        // Mevcut numaraları al ve en yüksek numarayı bul
        $sql = "SELECT contract_number FROM contracts WHERE contract_number LIKE ? ORDER BY contract_number DESC LIMIT 1";
        $result = $this->db->fetch($sql, ["SZL-{$year}-%"]);
        
        if ($result && $result['contract_number']) {
            // Mevcut numaradan sonraki numarayı üret
            $lastNumber = $result['contract_number'];
            $parts = explode('-', $lastNumber);
            $lastSequence = (int)end($parts);
            $nextSequence = $lastSequence + 1;
        } else {
            // İlk numara
            $nextSequence = 1;
        }
        
        return "SZL-{$year}-" . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Sözleşme istatistikleri
     */
    public function getStats()
    {
        $stats = [];

        // Toplam sözleşmeler
        $sql = "SELECT COUNT(*) as count FROM contracts";
        $stats['total'] = $this->db->fetch($sql)['count'];

        // Durum bazında sayılar
        $sql = "SELECT status, COUNT(*) as count FROM contracts GROUP BY status";
        $statusCounts = $this->db->fetchAll($sql);
        $stats['by_status'] = [];
        foreach ($statusCounts as $row) {
            $stats['by_status'][$row['status']] = $row['count'];
        }

        // Tip bazında sayılar
        $sql = "SELECT contract_type, COUNT(*) as count FROM contracts GROUP BY contract_type";
        $typeCounts = $this->db->fetchAll($sql);
        $stats['by_type'] = [];
        foreach ($typeCounts as $row) {
            $stats['by_type'][$row['contract_type']] = $row['count'];
        }

        // Toplam değer
        $sql = "SELECT SUM(total_amount) as total FROM contracts WHERE total_amount IS NOT NULL";
        $result = $this->db->fetch($sql);
        $stats['total_value'] = $result['total'] ?? 0;

        // Süresi yaklaşan sözleşmeler (30 gün)
        $futureDate = date('Y-m-d', strtotime('+30 days'));
        $sql = "SELECT COUNT(*) as count FROM contracts WHERE end_date IS NOT NULL AND end_date <= ? AND status = 'ACTIVE'";
        $stats['expiring_soon'] = $this->db->fetch($sql, [$futureDate])['count'];

        return $stats;
    }

    /**
     * Sözleşme durumları
     */
    public static function getStatuses()
    {
        return [
            'DRAFT' => 'Taslak',
            'ACTIVE' => 'Aktif',
            'SUSPENDED' => 'Askıya Alındı',
            'COMPLETED' => 'Tamamlandı',
            'TERMINATED' => 'Feshedildi'
        ];
    }

    /**
     * Sözleşme tipleri
     */
    public static function getTypes()
    {
        return [
            'CLEANING' => 'Temizlik',
            'MAINTENANCE' => 'Bakım',
            'RECURRING' => 'Tekrarlayan',
            'ONE_TIME' => 'Tek Seferlik'
        ];
    }

    /**
     * Ödeme yöntemleri
     */
    public static function getPaymentMethods()
    {
        return [
            'CASH' => 'Nakit',
            'BANK_TRANSFER' => 'Banka Havalesi',
            'CREDIT_CARD' => 'Kredi Kartı',
            'CHECK' => 'Çek'
        ];
    }

    /**
     * Ödeme durumları
     */
    public static function getPaymentStatuses()
    {
        return [
            'PENDING' => 'Beklemede',
            'PAID' => 'Ödendi',
            'OVERDUE' => 'Gecikmiş',
            'CANCELLED' => 'İptal Edildi'
        ];
    }
}
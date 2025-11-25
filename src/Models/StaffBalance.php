<?php
/**
 * Staff Balance Model
 */

class StaffBalance
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getByStaff($staffId)
    {
        $db = Database::getInstance();
        $sql = "
            SELECT * FROM staff_balances 
            WHERE staff_id = ? 
            ORDER BY created_at DESC
        ";
        return $db->fetchAll($sql, [$staffId]);
    }

    public static function getPendingBalances()
    {
        $db = Database::getInstance();
        $sql = "
            SELECT sb.*, s.name, s.surname
            FROM staff_balances sb
            JOIN staff s ON sb.staff_id = s.id
            WHERE sb.status = 'pending'
            ORDER BY sb.due_date ASC
        ";
        return $db->fetchAll($sql);
    }

    public static function getOverdueBalances()
    {
        $db = Database::getInstance();
        $sql = "
            SELECT sb.*, s.name, s.surname
            FROM staff_balances sb
            JOIN staff s ON sb.staff_id = s.id
            WHERE sb.status = 'pending' 
            AND sb.due_date < date('now')
            ORDER BY sb.due_date ASC
        ";
        return $db->fetchAll($sql);
    }

    public static function getTotalBalances()
    {
        $db = Database::getInstance();
        $sql = "
            SELECT 
                SUM(CASE WHEN balance_type = 'receivable' THEN amount ELSE 0 END) as total_receivable,
                SUM(CASE WHEN balance_type = 'payable' THEN amount ELSE 0 END) as total_payable,
                COUNT(*) as total_count
            FROM staff_balances 
            WHERE status = 'pending'
        ";
        
        $result = $db->fetchAll($sql);
        return $result[0] ?? [
            'total_receivable' => 0,
            'total_payable' => 0,
            'total_count' => 0
        ];
    }

    public static function create($data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO staff_balances (staff_id, balance_type, amount, description, due_date, status) VALUES (?, ?, ?, ?, ?, ?)";
        
        return $db->execute($sql, [
            $data['staff_id'],
            $data['balance_type'],
            $data['amount'],
            $data['description'] ?? null,
            $data['due_date'] ?? null,
            $data['status']
        ]);
    }

    public static function update($id, $data)
    {
        $db = Database::getInstance();
        $sql = "UPDATE staff_balances SET status = ?, updated_at = datetime('now') WHERE id = ?";
        
        return $db->query($sql, [$data['status'], $id]);
    }
}

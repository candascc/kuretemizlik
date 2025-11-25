<?php
/**
 * Staff Payment Model
 */

class StaffPayment
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
            SELECT * FROM staff_payments 
            WHERE staff_id = ? 
            ORDER BY payment_date DESC
        ";
        return $db->fetchAll($sql, [$staffId]);
    }

    public static function getByDateRange($startDate, $endDate)
    {
        $db = Database::getInstance();
        $sql = "
            SELECT sp.*, s.name, s.surname
            FROM staff_payments sp
            JOIN staff s ON sp.staff_id = s.id
            WHERE sp.payment_date BETWEEN ? AND ?
            ORDER BY sp.payment_date DESC
        ";
        return $db->fetchAll($sql, [$startDate, $endDate]);
    }

    public static function getMonthlyPayments($month = null)
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $db = Database::getInstance();
        $sql = "
            SELECT 
                sp.*,
                s.name,
                s.surname,
                s.position
            FROM staff_payments sp
            JOIN staff s ON sp.staff_id = s.id
            WHERE strftime('%Y-%m', sp.payment_date) = ?
            ORDER BY sp.payment_date DESC
        ";
        return $db->fetchAll($sql, [$month]);
    }

    public static function getTotalPayments($month = null)
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $db = Database::getInstance();
        $sql = "
            SELECT 
                SUM(amount) as total_amount,
                COUNT(*) as payment_count,
                payment_type,
                status
            FROM staff_payments 
            WHERE strftime('%Y-%m', payment_date) = ?
            GROUP BY payment_type, status
        ";
        return $db->fetchAll($sql, [$month]);
    }

    public static function getPendingPayments()
    {
        $db = Database::getInstance();
        $sql = "
            SELECT sp.*, s.name, s.surname
            FROM staff_payments sp
            JOIN staff s ON sp.staff_id = s.id
            WHERE sp.status = 'pending'
            ORDER BY sp.payment_date ASC
        ";
        return $db->fetchAll($sql);
    }

    public static function create($data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO staff_payments (staff_id, payment_date, amount, payment_type, description, reference_number, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        return $db->execute($sql, [
            $data['staff_id'],
            $data['payment_date'],
            $data['amount'],
            $data['payment_type'],
            $data['description'] ?? null,
            $data['reference_number'] ?? null,
            $data['status']
        ]);
    }
}

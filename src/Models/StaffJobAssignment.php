<?php
/**
 * Staff Job Assignment Model
 */

class StaffJobAssignment
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
            SELECT sja.*, j.title as job_title, c.name as customer_name
            FROM staff_job_assignments sja
            LEFT JOIN jobs j ON sja.job_id = j.id
            LEFT JOIN customers c ON j.customer_id = c.id
            WHERE sja.staff_id = ?
            ORDER BY sja.assigned_date DESC
        ";
        return $db->fetchAll($sql, [$staffId]);
    }

    public static function getByJob($jobId)
    {
        $db = Database::getInstance();
        $sql = "
            SELECT sja.*, s.name, s.surname, s.position
            FROM staff_job_assignments sja
            LEFT JOIN staff s ON sja.staff_id = s.id
            WHERE sja.job_id = ?
            ORDER BY sja.assigned_date DESC
        ";
        return $db->fetchAll($sql, [$jobId]);
    }

    public static function getActiveAssignments()
    {
        $db = Database::getInstance();
        $sql = "
            SELECT sja.*, s.name, s.surname, j.title as job_title, c.name as customer_name
            FROM staff_job_assignments sja
            LEFT JOIN staff s ON sja.staff_id = s.id
            LEFT JOIN jobs j ON sja.job_id = j.id
            LEFT JOIN customers c ON j.customer_id = c.id
            WHERE sja.status = 'assigned'
            ORDER BY sja.assigned_date DESC
        ";
        return $db->fetchAll($sql);
    }

    public function calculateAmount()
    {
        if (!$this->start_time || !$this->end_time || !$this->hourly_rate) {
            return 0;
        }

        $start = new \DateTime($this->assigned_date . ' ' . $this->start_time);
        $end = new \DateTime($this->assigned_date . ' ' . $this->end_time);
        
        $hours = $end->diff($start)->h + ($end->diff($start)->i / 60);
        
        return round($hours * $this->hourly_rate, 2);
    }

    public function updateAmount()
    {
        $amount = $this->calculateAmount();
        $this->update(['total_amount' => $amount]);
    }

    public static function create($data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO staff_job_assignments (staff_id, job_id, assigned_date, start_time, end_time, hourly_rate, total_amount, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return $db->execute($sql, [
            $data['staff_id'],
            $data['job_id'],
            $data['assigned_date'],
            $data['start_time'],
            $data['end_time'],
            $data['hourly_rate'],
            $data['total_amount'] ?? 0,
            $data['status'] ?? 'assigned',
            $data['notes'] ?? null
        ]);
    }
}

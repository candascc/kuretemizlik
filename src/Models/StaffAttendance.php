<?php
/**
 * Staff Attendance Model
 */

class StaffAttendance
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getByStaffAndDate($staffId, $date)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM staff_attendance WHERE staff_id = ? AND date = ?";
        $result = $db->fetchAll($sql, [$staffId, $date]);
        return $result[0] ?? null;
    }

    public static function getByStaffAndMonth($staffId, $month)
    {
        $db = Database::getInstance();
        $sql = "
            SELECT * FROM staff_attendance 
            WHERE staff_id = ? 
            AND strftime('%Y-%m', date) = ?
            ORDER BY date DESC
        ";
        return $db->fetchAll($sql, [$staffId, $month]);
    }

    public static function create($data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO staff_attendance (staff_id, date, check_in, check_out, break_start, break_end, total_hours, overtime_hours, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return $db->execute($sql, [
            $data['staff_id'],
            $data['date'],
            $data['check_in'],
            $data['check_out'],
            $data['break_start'] ?? null,
            $data['break_end'] ?? null,
            $data['total_hours'] ?? 0,
            $data['overtime_hours'] ?? 0,
            $data['status'],
            $data['notes'] ?? null
        ]);
    }

    public static function update($id, $data)
    {
        $db = Database::getInstance();
        $sql = "UPDATE staff_attendance SET check_in = ?, check_out = ?, break_start = ?, break_end = ?, total_hours = ?, overtime_hours = ?, status = ?, notes = ? WHERE id = ?";
        
        return $db->execute($sql, [
            $data['check_in'],
            $data['check_out'],
            $data['break_start'] ?? null,
            $data['break_end'] ?? null,
            $data['total_hours'] ?? 0,
            $data['overtime_hours'] ?? 0,
            $data['status'],
            $data['notes'] ?? null,
            $id
        ]);
    }

    public static function getMonthlyStats($month = null)
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $db = Database::getInstance();
        $sql = "
            SELECT 
                s.name,
                s.surname,
                COUNT(sa.id) as working_days,
                SUM(sa.total_hours) as total_hours,
                SUM(sa.overtime_hours) as overtime_hours,
                AVG(sa.total_hours) as avg_daily_hours
            FROM staff s
            LEFT JOIN staff_attendance sa ON s.id = sa.staff_id 
                AND strftime('%Y-%m', sa.date) = ?
                AND sa.status = 'present'
            WHERE s.status = 'active'
            GROUP BY s.id, s.name, s.surname
            ORDER BY s.name, s.surname
        ";

        return $db->fetchAll($sql, [$month]);
    }

    public static function getTodayAttendance()
    {
        $db = Database::getInstance();
        $sql = "
            SELECT sa.*, s.name, s.surname, s.position
            FROM staff_attendance sa
            JOIN staff s ON sa.staff_id = s.id
            WHERE sa.date = date('now')
            ORDER BY sa.check_in DESC
        ";

        return $db->fetchAll($sql);
    }

    public static function getAbsentToday()
    {
        $db = Database::getInstance();
        $sql = "
            SELECT s.*
            FROM staff s
            WHERE s.status = 'active'
            AND s.id NOT IN (
                SELECT staff_id FROM staff_attendance 
                WHERE date = date('now')
            )
            ORDER BY s.name, s.surname
        ";

        return $db->fetchAll($sql);
    }

    public function calculateHours()
    {
        if (!$this->check_in || !$this->check_out) {
            return 0;
        }

        $checkIn = new \DateTime($this->date . ' ' . $this->check_in);
        $checkOut = new \DateTime($this->date . ' ' . $this->check_out);
        
        $totalMinutes = $checkOut->diff($checkIn)->h * 60 + $checkOut->diff($checkIn)->i;
        
        // Mola süresini çıkar
        if ($this->break_start && $this->break_end) {
            $breakStart = new \DateTime($this->date . ' ' . $this->break_start);
            $breakEnd = new \DateTime($this->date . ' ' . $this->break_end);
            $breakMinutes = $breakEnd->diff($breakStart)->h * 60 + $breakEnd->diff($breakStart)->i;
            $totalMinutes -= $breakMinutes;
        }

        $hours = $totalMinutes / 60;
        
        // 8 saatten fazla çalışma mesai sayılır
        $overtime = max(0, $hours - 8);
        $regular = min(8, $hours);

        return [
            'total_hours' => round($hours, 2),
            'regular_hours' => round($regular, 2),
            'overtime_hours' => round($overtime, 2)
        ];
    }

    public function updateHours()
    {
        $hours = $this->calculateHours();
        
        $this->update([
            'total_hours' => $hours['total_hours'],
            'overtime_hours' => $hours['overtime_hours']
        ]);
    }
}

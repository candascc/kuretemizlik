<?php
/**
 * Staff Model
 * SECURITY: Multi-tenant support with CompanyScope
 */

class Staff
{
    use CompanyScope; // SECURITY: Multi-tenant isolation
    
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getAll($status = null)
    {
        $db = Database::getInstance();
        $instance = new self();
        $sql = "SELECT * FROM staff";
        $where = $instance->scopeToCompany('WHERE 1=1');
        
        if ($status) {
            $where .= " AND status = ?";
            return $db->fetchAll($sql . ' ' . $where, [$status]);
        }
        
        return $db->fetchAll($sql . ' ' . $where);
    }

    public static function getActive()
    {
        return self::getAll('active');
    }

    public static function find($id)
    {
        $db = Database::getInstance();
        $instance = new self();
        $where = $instance->scopeToCompany('WHERE id = ?');
        $staff = $db->fetch("SELECT * FROM staff " . $where, [$id]);
        
        // SECURITY: Verify company access
        if ($staff && !$instance->verifyCompanyAccess($staff['company_id'] ?? null)) {
            return null;
        }
        
        return $staff;
    }

    public static function findByEmail($email)
    {
        $db = Database::getInstance();
        $instance = new self();
        $where = $instance->scopeToCompany('WHERE email = ?');
        return $db->fetch("SELECT * FROM staff " . $where, [$email]);
    }

    public static function findByTcNumber($tcNumber)
    {
        $db = Database::getInstance();
        $instance = new self();
        $where = $instance->scopeToCompany('WHERE tc_number = ?');
        return $db->fetch("SELECT * FROM staff " . $where, [$tcNumber]);
    }

    public static function create($data)
    {
        // ===== KOZMOS_STAFF_FIX: use insert method (begin)
        $db = Database::getInstance();
        $instance = new self();
        
        // SECURITY: Enforce tenant isolation (company_id injection)
        $insertData = [
            'name' => $data['name'],
            'surname' => $data['surname'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'tc_number' => $data['tc_number'],
            'birth_date' => $data['birth_date'],
            'address' => $data['address'],
            'position' => $data['position'],
            'hire_date' => $data['hire_date'],
            'salary' => $data['salary'],
            'hourly_rate' => $data['hourly_rate'],
            'photo' => $data['photo'] ?? null,
            'notes' => $data['notes'],
            'status' => $data['status'],
            'company_id' => $instance->getCompanyIdForInsert(), // SECURITY: Auto-inject company_id
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->insert('staff', $insertData);
        // ===== KOZMOS_STAFF_FIX: use insert method (end)
    }

    public static function update($id, $data)
    {
        // ===== KOZMOS_STAFF_FIX: use update method (begin)
        $db = Database::getInstance();
        $updateData = [
            'name' => $data['name'],
            'surname' => $data['surname'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'tc_number' => $data['tc_number'],
            'birth_date' => $data['birth_date'],
            'address' => $data['address'],
            'position' => $data['position'],
            'hire_date' => $data['hire_date'],
            'salary' => $data['salary'],
            'hourly_rate' => $data['hourly_rate'],
            'notes' => $data['notes'],
            'status' => $data['status'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->update('staff', $updateData, 'id = ?', [$id]);
        // ===== KOZMOS_STAFF_FIX: use update method (end)
    }

    public static function delete($id)
    {
        $db = Database::getInstance();
        $sql = "DELETE FROM staff WHERE id = ?";
        return $db->query($sql, [$id]);
    }

    public static function getByPosition($position)
    {
        $db = Database::getInstance();
        $instance = new self();
        $where = $instance->scopeToCompany('WHERE position = ? AND status = \'active\'');
        return $db->fetchAll("SELECT * FROM staff " . $where, [$position]);
    }

    public static function getMonthlyStats($month = null)
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $db = Database::getInstance();
        $sql = "
            SELECT 
                s.id,
                s.name,
                s.surname,
                s.position,
                s.salary,
                s.hourly_rate,
                COUNT(sa.id) as working_days,
                SUM(sa.total_hours) as total_hours,
                SUM(sa.overtime_hours) as overtime_hours,
                AVG(sa.total_hours) as avg_daily_hours
            FROM staff s
            LEFT JOIN staff_attendance sa ON s.id = sa.staff_id 
                AND strftime('%Y-%m', sa.date) = ?
                AND sa.status = 'present'
            WHERE s.status = 'active'
            GROUP BY s.id, s.name, s.surname, s.position, s.salary, s.hourly_rate
            ORDER BY s.name, s.surname
        ";
        
        // SECURITY: Add company scope filter
        $instance = new self();
        $sql = str_replace('WHERE s.status = \'active\'', 
                          'WHERE s.status = \'active\' ' . str_replace('WHERE 1=1', '', $instance->scopeToCompany('AND s.company_id = ?', 's')),
                          $sql);
        $params = [$month];
        if (!Auth::canSwitchCompany()) {
            $companyId = Auth::companyId();
            if ($companyId) {
                $params[] = $companyId;
            }
        }

        return $db->fetchAll($sql, $params);
    }

    public static function getTotalSalary($month = null)
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $db = Database::getInstance();
        $sql = "
            SELECT 
                SUM(s.salary) as total_salary,
                SUM(sa.total_hours * s.hourly_rate) as total_hourly_pay,
                SUM(sa.overtime_hours * s.hourly_rate * 1.5) as total_overtime_pay
            FROM staff s
            LEFT JOIN staff_attendance sa ON s.id = sa.staff_id 
                AND strftime('%Y-%m', sa.date) = ?
                AND sa.status = 'present'
            WHERE s.status = 'active'
        ";

        $result = $db->fetchAll($sql, [$month]);
        return $result[0] ?? [
            'total_salary' => 0,
            'total_hourly_pay' => 0,
            'total_overtime_pay' => 0
        ];
    }

    public function getFullName()
    {
        return $this->name . ' ' . $this->surname;
    }

    public function getAge()
    {
        if (!$this->birth_date) {
            return null;
        }

        $birthDate = new \DateTime($this->birth_date);
        $today = new \DateTime();
        return $today->diff($birthDate)->y;
    }

    public function getWorkingDays($month = null)
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $db = Database::getInstance();
        $sql = "
            SELECT COUNT(*) as working_days
            FROM staff_attendance 
            WHERE staff_id = ? 
            AND strftime('%Y-%m', date) = ? 
            AND status = 'present'
        ";

        $result = $db->fetchAll($sql, [$this->id, $month]);
        return $result[0]['working_days'] ?? 0;
    }

    public function getTotalHours($month = null)
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $db = Database::getInstance();
        $sql = "
            SELECT 
                SUM(total_hours) as total_hours,
                SUM(overtime_hours) as overtime_hours
            FROM staff_attendance 
            WHERE staff_id = ? 
            AND strftime('%Y-%m', date) = ? 
            AND status = 'present'
        ";

        $result = $db->fetchAll($sql, [$this->id, $month]);
        return [
            'total_hours' => $result[0]['total_hours'] ?? 0,
            'overtime_hours' => $result[0]['overtime_hours'] ?? 0
        ];
    }

    public function getCurrentBalance()
    {
        $db = Database::getInstance();
        $sql = "
            SELECT 
                SUM(CASE WHEN balance_type = 'receivable' THEN amount ELSE 0 END) as total_receivable,
                SUM(CASE WHEN balance_type = 'payable' THEN amount ELSE 0 END) as total_payable
            FROM staff_balances 
            WHERE staff_id = ? AND status = 'pending'
        ";

        $result = $db->fetchAll($sql, [$this->id]);
        return [
            'receivable' => $result[0]['total_receivable'] ?? 0,
            'payable' => $result[0]['total_payable'] ?? 0,
            'net' => ($result[0]['total_receivable'] ?? 0) - ($result[0]['total_payable'] ?? 0)
        ];
    }

    public function getRecentAttendance($days = 7)
    {
        $db = Database::getInstance();
        $sql = "
            SELECT * FROM staff_attendance 
            WHERE staff_id = ? 
            AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ORDER BY date DESC
        ";

        return $db->fetchAll($sql, [$this->id, $days]);
    }

    public function getJobAssignments($status = null)
    {
        $db = Database::getInstance();
        $sql = "
            SELECT sja.*, j.title as job_title, c.name as customer_name
            FROM staff_job_assignments sja
            LEFT JOIN jobs j ON sja.job_id = j.id
            LEFT JOIN customers c ON j.customer_id = c.id
            WHERE sja.staff_id = ?
        ";

        if ($status) {
            $sql .= " AND sja.status = ?";
            return $db->fetchAll($sql, [$this->id, $status]);
        }

        return $db->fetchAll($sql, [$this->id]);
    }
}

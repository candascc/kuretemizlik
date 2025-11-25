<?php
/**
 * Building Model
 */

class Building
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id)
    {
        return $this->db->fetch("SELECT * FROM buildings WHERE id = ?", [$id]);
    }

    public function all($arg1 = null, $arg2 = null): array
    {
        // Backward-compat: all($limit,$offset)
        if (is_int($arg1) || is_int($arg2)) {
            $limit = (int)($arg1 ?? 50);
            $offset = (int)($arg2 ?? 0);
            return $this->db->fetchAll('SELECT * FROM buildings ORDER BY created_at DESC LIMIT ? OFFSET ?', [$limit, $offset]);
        }

        $filters = is_array($arg1) ? $arg1 : [];
        $where = [];
        $params = [];
        if (!empty($filters['status'])) { $where[] = 'status = ?'; $params[] = $filters['status']; }
        if (!empty($filters['type'])) { $where[] = 'building_type = ?'; $params[] = $filters['type']; }
        if (!empty($filters['customer_id'])) { $where[] = 'customer_id = ?'; $params[] = $filters['customer_id']; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT * FROM buildings {$whereSql} ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function paginate(array $filters, int $limit, int $offset, ?string $search = null): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['building_type'])) {
            $where[] = 'building_type = ?';
            $params[] = $filters['building_type'];
        }
        if (!empty($filters['customer_id'])) {
            $where[] = 'customer_id = ?';
            $params[] = $filters['customer_id'];
        }

        $search = trim((string)$search);
        if ($search !== '') {
            $where[] = '(name LIKE ? OR manager_name LIKE ? OR address_line LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countRow = $this->db->fetch("SELECT COUNT(*) as c FROM buildings {$whereSql}", $params);
        $total = (int)($countRow['c'] ?? 0);

        $dataParams = $params;
        $dataParams[] = $limit;
        $dataParams[] = $offset;

        $rows = $this->db->fetchAll(
            "SELECT * FROM buildings {$whereSql} ORDER BY created_at DESC LIMIT ? OFFSET ?",
            $dataParams
        );

        return [
            'data' => $rows,
            'total' => $total,
        ];
    }

    public function create(array $data)
    {
        $payload = [
            'name' => trim((string)($data['name'] ?? '')),
            'building_type' => $data['building_type'] ?? 'apartman',
            'customer_id' => $data['customer_id'] ?? null,
            'address_line' => $data['address_line'] ?? '',
            'district' => $data['district'] ?? null,
            'city' => $data['city'] ?? '',
            'postal_code' => $data['postal_code'] ?? null,
            'total_floors' => isset($data['total_floors']) ? (int)$data['total_floors'] : null,
            'total_units' => isset($data['total_units']) ? (int)$data['total_units'] : 0,
            'construction_year' => isset($data['construction_year']) ? (int)$data['construction_year'] : null,
            'manager_name' => $data['manager_name'] ?? null,
            'manager_phone' => $data['manager_phone'] ?? null,
            'manager_email' => $data['manager_email'] ?? null,
            'tax_office' => $data['tax_office'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_iban' => $data['bank_iban'] ?? null,
            'monthly_maintenance_day' => isset($data['monthly_maintenance_day']) ? (int)$data['monthly_maintenance_day'] : 1,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('buildings', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = ['name','building_type','customer_id','address_line','district','city','postal_code','total_floors','total_units','construction_year','manager_name','manager_phone','manager_email','tax_office','tax_number','bank_name','bank_iban','monthly_maintenance_day','status','notes'];
        $payload = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $payload[$f] = $data[$f];
            }
        }
        if (empty($payload)) { return 0; }
        $payload['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('buildings', $payload, 'id = ?', [$id]);
    }

    public function delete(int $id)
    {
        return $this->db->delete('buildings', 'id = ?', [$id]);
    }

    public function getUnits(int $buildingId): array
    {
        return $this->db->fetchAll('SELECT * FROM units WHERE building_id = ? ORDER BY floor_number, unit_number', [$buildingId]);
    }

    public function stats(int $buildingId): array
    {
        // Date ranges for optimized queries
        $thisMonthStart = date('Y-m-01');
        $thisMonthEnd = date('Y-m-t');
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
        $thisYearStart = date('Y-01-01');
        $thisYearEnd = date('Y-12-31');

        // OPTIMIZED: Single query for units statistics
        $unitsStats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_units,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_units,
                COUNT(CASE WHEN status = 'empty' THEN 1 END) as empty_units,
                COALESCE(SUM(debt_balance), 0) as total_debt
            FROM units 
            WHERE building_id = ?
        ", [$buildingId]);

        $totalUnits = (int)($unitsStats['total_units'] ?? 0);
        $activeUnits = (int)($unitsStats['active_units'] ?? 0);
        $emptyUnits = (int)($unitsStats['empty_units'] ?? 0);
        $occupiedRate = $totalUnits > 0 ? round(($activeUnits / $totalUnits) * 100, 1) : 0;
        $totalDebt = (float)($unitsStats['total_debt'] ?? 0);

        // OPTIMIZED: Single query for management fees statistics
        $feesStats = $this->db->fetch("
            SELECT 
                COALESCE(SUM(CASE WHEN status IN ('pending', 'partial') THEN total_amount - paid_amount ELSE 0 END), 0) as pending_amount,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN paid_amount ELSE 0 END), 0) as paid_amount,
                COALESCE(SUM(CASE WHEN status = 'overdue' THEN total_amount - paid_amount ELSE 0 END), 0) as overdue_amount,
                COALESCE(SUM(total_amount), 0) as total_expected_amount
            FROM management_fees 
            WHERE building_id = ?
        ", [$buildingId]);

        $pendingAmount = (float)($feesStats['pending_amount'] ?? 0);
        $paidAmount = (float)($feesStats['paid_amount'] ?? 0);
        $overdueAmount = (float)($feesStats['overdue_amount'] ?? 0);
        $totalExpectedAmount = (float)($feesStats['total_expected_amount'] ?? 0);
        $collectionRate = $totalExpectedAmount > 0 ? round(($paidAmount / $totalExpectedAmount) * 100, 1) : 0;

        // OPTIMIZED: Single query for expenses statistics (including by category)
        $expensesStats = $this->db->fetch("
            SELECT 
                COALESCE(SUM(CASE WHEN expense_date >= ? AND expense_date <= ? AND approval_status = 'approved' THEN amount ELSE 0 END), 0) as this_month,
                COALESCE(SUM(CASE WHEN expense_date >= ? AND expense_date <= ? AND approval_status = 'approved' THEN amount ELSE 0 END), 0) as last_month,
                COALESCE(SUM(CASE WHEN expense_date >= ? AND expense_date <= ? AND approval_status = 'approved' THEN amount ELSE 0 END), 0) as this_year
            FROM building_expenses 
            WHERE building_id = ?
        ", [$thisMonthStart, $thisMonthEnd, $lastMonthStart, $lastMonthEnd, $thisYearStart, $thisYearEnd, $buildingId]);

        $thisMonthExpenses = (float)($expensesStats['this_month'] ?? 0);
        $lastMonthExpenses = (float)($expensesStats['last_month'] ?? 0);
        $thisYearExpenses = (float)($expensesStats['this_year'] ?? 0);

        // Expenses by category (separate query but still needed)
        $expensesByCategory = $this->db->fetchAll(
            "SELECT category, COALESCE(SUM(amount),0) as total 
             FROM building_expenses 
             WHERE building_id = ? AND approval_status = 'approved' 
             GROUP BY category 
             ORDER BY total DESC",
            [$buildingId]
        );

        // OPTIMIZED: Single query for residents statistics
        $residentsStats = $this->db->fetch("
            SELECT 
                COUNT(CASE WHEN ru.is_active = 1 THEN 1 END) as total,
                COUNT(*) as registered_users
            FROM resident_users ru
            INNER JOIN units u ON ru.unit_id = u.id
            WHERE u.building_id = ?
        ", [$buildingId]);

        $residentCount = (int)($residentsStats['total'] ?? 0);
        $registeredCount = (int)($residentsStats['registered_users'] ?? 0);

        // Meetings statistics
        $upcomingMeetings = $this->db->fetchAll(
            "SELECT id, title, meeting_date, meeting_type 
             FROM building_meetings 
             WHERE building_id = ? AND meeting_date >= date('now') AND status = 'scheduled' 
             ORDER BY meeting_date ASC 
             LIMIT 5",
            [$buildingId]
        );
        $thisMonthMeetings = (int)($this->db->fetch("SELECT COUNT(*) as c FROM building_meetings WHERE building_id = ? AND meeting_date >= ? AND meeting_date <= ?", [$buildingId, $thisMonthStart, $thisMonthEnd])['c'] ?? 0);

        // OPTIMIZED: Single query for requests statistics
        $requestsStats = $this->db->fetch("
            SELECT 
                COUNT(CASE WHEN status = 'open' THEN 1 END) as open,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as pending
            FROM resident_requests 
            WHERE building_id = ?
        ", [$buildingId]);

        $openRequests = (int)($requestsStats['open'] ?? 0);
        $pendingRequests = (int)($requestsStats['pending'] ?? 0);

        return [
            'units' => [
                'total_units' => $totalUnits,
                'active_units' => $activeUnits,
                'empty_units' => $emptyUnits,
                'occupied_rate' => $occupiedRate,
                'total_debt' => $totalDebt,
            ],
            'fees' => [
                'pending_amount' => $pendingAmount,
                'paid_amount' => $paidAmount,
                'overdue_amount' => $overdueAmount,
                'collection_rate' => $collectionRate,
            ],
            'expenses' => [
                'this_month' => $thisMonthExpenses,
                'last_month' => $lastMonthExpenses,
                'this_year' => $thisYearExpenses,
                'by_category' => $expensesByCategory ?: [],
            ],
            'residents' => [
                'total' => $residentCount,
                'registered_users' => $registeredCount,
            ],
            'meetings' => [
                'upcoming' => $upcomingMeetings ?: [],
                'this_month' => $thisMonthMeetings,
            ],
            'requests' => [
                'open' => $openRequests,
                'pending' => $pendingRequests,
            ]
        ];
    }

    /**
     * Backward-compat: controllers call getStatistics()
     */
    public function getStatistics(int $buildingId): array
    {
        return $this->stats($buildingId);
    }

    /**
     * Backward-compat: controllers call active()
     */
    public function active(): array
    {
        return $this->db->fetchAll("SELECT * FROM buildings WHERE status = 'active' ORDER BY name ASC");
    }
}

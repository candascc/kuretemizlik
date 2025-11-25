<?php
/**
 * MoneyEntry Model
 */

class MoneyEntry
{
    use CompanyScope;

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all($limit = null, $offset = 0)
    {
        $where = $this->scopeToCompany('WHERE 1=1', 'me');

        $sql = "
            SELECT
                me.*,
                j.id as job_id,
                j.total_amount,
                j.amount_paid,
                j.payment_status,
                c.name as customer_name,
                u.username as created_by_name,
                rj.id as recurring_job_id,
                rj.pricing_model as recurring_pricing_model,
                rc.name as recurring_customer_name
            FROM money_entries me
            LEFT JOIN jobs j ON me.job_id = j.id
            LEFT JOIN customers c ON j.customer_id = c.id
            LEFT JOIN users u ON me.created_by = u.id
            LEFT JOIN recurring_jobs rj ON me.recurring_job_id = rj.id
            LEFT JOIN customers rc ON rj.customer_id = rc.id
            {$where}
            ORDER BY me.date DESC, me.created_at DESC
        ";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }

        return $this->db->fetchAll($sql);
    }

    public function find($id)
    {
        $where = $this->scopeToCompany('WHERE me.id = ?', 'me');

        return $this->db->fetch(
            "SELECT
                me.*,
                j.id as job_id,
                j.total_amount,
                j.amount_paid,
                j.payment_status,
                c.name as customer_name,
                s.name as service_name,
                u.username as created_by_name,
                rj.id as recurring_job_id,
                rj.pricing_model as recurring_pricing_model,
                rc.name as recurring_customer_name
             FROM money_entries me
             LEFT JOIN jobs j ON me.job_id = j.id
             LEFT JOIN customers c ON j.customer_id = c.id
             LEFT JOIN services s ON j.service_id = s.id
             LEFT JOIN users u ON me.created_by = u.id
             LEFT JOIN recurring_jobs rj ON me.recurring_job_id = rj.id
             LEFT JOIN customers rc ON rj.customer_id = rc.id
             {$where}",
            [$id]
        );
    }

    public function getIncomes($limit = null, $offset = 0)
    {
        $where = $this->scopeToCompany("WHERE me.kind = 'INCOME'", 'me');

        $sql = "
            SELECT
                me.*,
                j.id as job_id,
                j.total_amount,
                j.amount_paid,
                j.payment_status,
                c.name as customer_name,
                u.username as created_by_name
            FROM money_entries me
            LEFT JOIN jobs j ON me.job_id = j.id
            LEFT JOIN customers c ON j.customer_id = c.id
            LEFT JOIN users u ON me.created_by = u.id
            {$where}
            ORDER BY me.date DESC, me.created_at DESC
        ";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }

        return $this->db->fetchAll($sql);
    }

    public function getExpenses($limit = null, $offset = 0)
    {
        $where = $this->scopeToCompany("WHERE me.kind = 'EXPENSE'", 'me');

        $sql = "
            SELECT
                me.*,
                u.username as created_by_name
            FROM money_entries me
            LEFT JOIN users u ON me.created_by = u.id
            {$where}
            ORDER BY me.date DESC, me.created_at DESC
        ";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }

        return $this->db->fetchAll($sql);
    }

    public function getByDateRange($startDate, $endDate, $kind = null)
    {
        $where = $this->scopeToCompany('WHERE me.date BETWEEN ? AND ?', 'me');

        $sql = "
            SELECT
                me.*,
                j.id as job_id,
                j.total_amount,
                j.amount_paid,
                j.payment_status,
                c.name as customer_name,
                u.username as created_by_name
            FROM money_entries me
            LEFT JOIN jobs j ON me.job_id = j.id
            LEFT JOIN customers c ON j.customer_id = c.id
            LEFT JOIN users u ON me.created_by = u.id
            {$where}
        ";
        $params = [$startDate, $endDate];

        if ($kind) {
            $sql .= " AND me.kind = ?";
            $params[] = $kind;
        }

        $sql .= " ORDER BY me.date DESC, me.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data)
    {
        $entryData = [
            'kind' => $data['kind'],
            'category' => $data['category'],
            'amount' => (float)$data['amount'],
            'date' => $data['date'] ?? date('Y-m-d'),
            'note' => $data['note'] ?? null,
            'job_id' => $data['job_id'] ?? null,
            'recurring_job_id' => $data['recurring_job_id'] ?? null,
            'created_by' => $data['created_by'] ?? Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'company_id' => $this->getCompanyIdForInsert()
        ];

        return $this->db->insert('money_entries', $entryData);
    }

    public function update($id, array $data)
    {
        $entry = $this->find($id);
        if (!$entry) {
            return 0;
        }

        $entryData = [];

        $mutable = ['kind', 'category', 'amount', 'date', 'note', 'job_id', 'recurring_job_id'];
        foreach ($mutable as $field) {
            if (array_key_exists($field, $data)) {
                $entryData[$field] = $field === 'amount' ? (float)$data[$field] : $data[$field];
            }
        }

        if (empty($entryData)) {
            return 0;
        }

        $entryData['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('money_entries', $entryData, 'id = :id', ['id' => $id]);
    }

    public function delete($id)
    {
        $entry = $this->find($id);
        if (!$entry) {
            return 0;
        }

        return $this->db->delete('money_entries', 'id = ?', [$id]);
    }

    public function getTotalIncome($startDate = null, $endDate = null)
    {
        $where = $this->scopeToCompany("WHERE me.kind = 'INCOME'", 'me');
        $sql = "SELECT SUM(amount) as total FROM money_entries me {$where}";
        $params = [];

        if ($startDate && $endDate) {
            $sql .= " AND date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }

        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    public function getTotalExpense($startDate = null, $endDate = null)
    {
        $where = $this->scopeToCompany("WHERE me.kind = 'EXPENSE'", 'me');
        $sql = "SELECT SUM(amount) as total FROM money_entries me {$where}";
        $params = [];

        if ($startDate && $endDate) {
            $sql .= " AND date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }

        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    public function getNetProfit($startDate = null, $endDate = null)
    {
        $income = $this->getTotalIncome($startDate, $endDate);
        $expense = $this->getTotalExpense($startDate, $endDate);
        return $income - $expense;
    }

    public function getCategoryTotals($kind = null, $startDate = null, $endDate = null)
    {
        $where = $this->scopeToCompany('WHERE 1=1', 'me');
        $sql = "SELECT category, SUM(amount) as total FROM money_entries me {$where}";
        $params = [];

        if ($kind) {
            $sql .= " AND me.kind = ?";
            $params[] = $kind;
        }

        if ($startDate && $endDate) {
            $sql .= " AND me.date BETWEEN ? AND ?";
            $params = array_merge($params, [$startDate, $endDate]);
        }
        $sql .= " GROUP BY category ORDER BY total DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getMonthlyStats($year = null, $month = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        if (!$month) {
            $month = date('m');
        }

        $where = $this->scopeToCompany("WHERE strftime('%Y', date) = ? AND strftime('%m', date) = ?", 'me');

        return $this->db->fetchAll(
            "SELECT
                DATE(date) as date,
                SUM(CASE WHEN kind = 'INCOME' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN kind = 'EXPENSE' THEN amount ELSE 0 END) as total_expense,
                COUNT(CASE WHEN kind = 'INCOME' THEN 1 END) as income_count,
                COUNT(CASE WHEN kind = 'EXPENSE' THEN 1 END) as expense_count
             FROM money_entries
             {$where}
             GROUP BY DATE(date)
             ORDER BY date",
            [$year, $month]
        );
    }

    public function count()
    {
        $where = $this->scopeToCompany('WHERE 1=1', 'me');
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM money_entries me {$where}");
        return $result['count'];
    }

    public function getTotalsByCustomer($customerId)
    {
        if (!(new Customer())->find($customerId)) {
            return [
                'income' => 0,
                'expense' => 0,
                'profit' => 0
            ];
        }

        $where = $this->scopeToCompany('WHERE j.customer_id = ?', 'me');

        $sql = "
            SELECT
                SUM(CASE WHEN me.kind='INCOME' THEN me.amount ELSE 0 END) as income,
                SUM(CASE WHEN me.kind='EXPENSE' THEN me.amount ELSE 0 END) as expense
            FROM money_entries me
            LEFT JOIN jobs j ON me.job_id = j.id
            {$where}
        ";
        $row = $this->db->fetch($sql, [$customerId]);
        $income = (float)($row['income'] ?? 0);
        $expense = (float)($row['expense'] ?? 0);
        return [
            'income' => $income,
            'expense' => $expense,
            'profit' => $income - $expense
        ];
    }
}


<?php
/**
 * Job Model
 */

class Job
{
    use CompanyScope;

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all($limit = null, $offset = 0)
    {
        $where = $this->scopeToCompany('WHERE 1=1', 'j');
        $sql = "
            SELECT
                j.*,
                c.name as customer_name,
                c.phone as customer_phone,
                s.name as service_name,
                a.line as address_line,
                a.city as address_city
            FROM jobs j
            LEFT JOIN customers c ON j.customer_id = c.id
            LEFT JOIN services s ON j.service_id = s.id
            LEFT JOIN addresses a ON j.address_id = a.id
            {$where}
            ORDER BY j.start_at DESC
        ";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }

        return $this->db->fetchAll($sql);
    }

    public function find($id)
    {
        $where = $this->scopeToCompany('WHERE j.id = ?', 'j');

        return $this->db->fetch(
            "SELECT
                j.*,
                c.name as customer_name,
                c.phone as customer_phone,
                c.email as customer_email,
                s.name as service_name,
                a.line as address_line,
                a.city as address_city,
                a.label as address_label
             FROM jobs j
             LEFT JOIN customers c ON j.customer_id = c.id
             LEFT JOIN services s ON j.service_id = s.id
             LEFT JOIN addresses a ON j.address_id = a.id
             {$where}",
            [$id]
        );
    }

    public function getToday()
    {
        $today = date('Y-m-d');
        $where = $this->scopeToCompany('WHERE DATE(j.start_at) = ?', 'j');

        return $this->db->fetchAll(
            "SELECT
                j.*,
                c.name as customer_name,
                c.phone as customer_phone,
                s.name as service_name,
                a.line as address_line
             FROM jobs j
             LEFT JOIN customers c ON j.customer_id = c.id
             LEFT JOIN services s ON j.service_id = s.id
             LEFT JOIN addresses a ON j.address_id = a.id
             {$where}
             ORDER BY j.start_at",
            [$today]
        );
    }

    public function getByDateRange($startDate, $endDate)
    {
        $where = $this->scopeToCompany('WHERE DATE(j.start_at) BETWEEN ? AND ?', 'j');

        return $this->db->fetchAll(
            "SELECT
                j.*,
                c.name as customer_name,
                c.phone as customer_phone,
                s.name as service_name,
                a.line as address_line
             FROM jobs j
             LEFT JOIN customers c ON j.customer_id = c.id
             LEFT JOIN services s ON j.service_id = s.id
             LEFT JOIN addresses a ON j.address_id = a.id
             {$where}
             ORDER BY j.start_at",
            [$startDate, $endDate]
        );
    }

    public function getByStatus($status, $limit = null)
    {
        $where = $this->scopeToCompany('WHERE j.status = ?', 'j');

        $sql = "
            SELECT
                j.*,
                c.name as customer_name,
                c.phone as customer_phone,
                s.name as service_name,
                a.line as address_line
             FROM jobs j
             LEFT JOIN customers c ON j.customer_id = c.id
             LEFT JOIN services s ON j.service_id = s.id
             LEFT JOIN addresses a ON j.address_id = a.id
             {$where}
             ORDER BY j.start_at DESC
        ";

        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$status, $limit]);
        }

        return $this->db->fetchAll($sql, [$status]);
    }

    public function getOutstandingJobs($limit = null)
    {
        $where = $this->scopeToCompany("WHERE j.payment_status != 'PAID' AND j.status != 'CANCELLED'", 'j');

        $sql = "
            SELECT
                j.*,
                c.name as customer_name,
                c.phone as customer_phone,
                s.name as service_name,
                a.line as address_line,
                rj.pricing_model as pricing_model,
                rj.monthly_amount as monthly_amount,
                rj.contract_total_amount as contract_total_amount
            FROM jobs j
            LEFT JOIN customers c ON j.customer_id = c.id
            LEFT JOIN services s ON j.service_id = s.id
            LEFT JOIN addresses a ON j.address_id = a.id
            LEFT JOIN recurring_jobs rj ON j.recurring_job_id = rj.id
            {$where}
            ORDER BY j.start_at DESC
        ";

        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$limit]);
        }

        return $this->db->fetchAll($sql);
    }

    public function create($data)
    {
        $jobData = [
            'service_id' => $data['service_id'] ?? null,
            'customer_id' => $data['customer_id'],
            'address_id' => $data['address_id'] ?? null,
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'status' => $data['status'] ?? 'SCHEDULED',
            'assigned_to' => $data['assigned_to'] ?? null,
            'note' => $data['note'] ?? null,
            'total_amount' => isset($data['total_amount']) ? (float)$data['total_amount'] : 0,
            'amount_paid' => isset($data['amount_paid']) ? (float)$data['amount_paid'] : 0,
            'payment_status' => $data['payment_status'] ?? 'UNPAID',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'company_id' => $this->getCompanyIdForInsert()
        ];
        // Persist linkage to recurring job/occurrence when provided
        if (isset($data['recurring_job_id'])) {
            $jobData['recurring_job_id'] = (int)$data['recurring_job_id'];
        }
        if (isset($data['occurrence_id'])) {
            $jobData['occurrence_id'] = (int)$data['occurrence_id'];
        }

        return $this->db->insert('jobs', $jobData);
    }

    public function update($id, $data)
    {
        $job = $this->find($id);
        if (!$job || !$this->verifyCompanyAccess((int)($job['company_id'] ?? null))) {
            return 0;
        }

        $jobData = [
            'service_id' => $data['service_id'] ?? null,
            'customer_id' => $data['customer_id'],
            'address_id' => $data['address_id'] ?? null,
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'status' => $data['status'] ?? 'SCHEDULED',
            'assigned_to' => $data['assigned_to'] ?? null,
            'note' => $data['note'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (isset($data['total_amount'])) {
            $jobData['total_amount'] = (float)$data['total_amount'];
        }

        if (isset($data['payment_status'])) {
            $jobData['payment_status'] = $data['payment_status'];
        }

        return $this->db->update('jobs', $jobData, 'id = ?', [$id]);
    }

    public function delete($id)
    {
        $job = $this->find($id);
        if (!$job || !$this->verifyCompanyAccess((int)($job['company_id'] ?? null))) {
            return 0;
        }

        // Ensure we don't violate FK constraints from money_entries(job_id)
        $this->db->beginTransaction();
        try {
            // Keep finance history but detach from the job
            $this->db->update('money_entries', ['job_id' => null], 'job_id = ?', [$id]);

            // job_payments has ON DELETE CASCADE; direct delete is safe
            $this->db->delete('jobs', 'id = ?', [$id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function updateStatus($id, $status)
    {
        $job = $this->find($id);
        if (!$job || !$this->verifyCompanyAccess((int)($job['company_id'] ?? null))) {
            return 0;
        }

        return $this->db->update('jobs', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    public function count()
    {
        $where = $this->scopeToCompany('WHERE 1=1');
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM jobs {$where}");
        return $result['count'];
    }

    public function countByStatus($status)
    {
        $where = $this->scopeToCompany('WHERE status = ?');
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM jobs {$where}",
            [$status]
        );
        return $result['count'];
    }

    public function getStats()
    {
        $total = $this->count();
        $scheduled = $this->countByStatus('SCHEDULED');
        $done = $this->countByStatus('DONE');
        $cancelled = $this->countByStatus('CANCELLED');

        return [
            'total' => $total,
            'scheduled' => $scheduled,
            'done' => $done,
            'cancelled' => $cancelled
        ];
    }

    public function getMonthlyStats($year = null, $month = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        if (!$month) {
            $month = date('m');
        }

        $where = $this->scopeToCompany("WHERE strftime('%Y', j.start_at) = ? AND strftime('%m', j.start_at) = ?", 'j');

        return $this->db->fetchAll(
            "SELECT
                DATE(j.start_at) as date,
                COUNT(*) as total_jobs,
                SUM(CASE WHEN j.status = 'DONE' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN j.status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled_jobs
             FROM jobs j
             {$where}
             GROUP BY DATE(j.start_at)
             ORDER BY date",
            [$year, $month]
        );
    }

    public static function syncPayments(int $jobId): void
    {
        $db = Database::getInstance();
        $job = $db->fetch('SELECT id, total_amount FROM jobs WHERE id = ?', [$jobId]);
        if (!$job) {
            return;
        }

        $sumRow = $db->fetch('SELECT COALESCE(SUM(amount), 0) as paid FROM job_payments WHERE job_id = ?', [$jobId]);
        $paid = (float)($sumRow['paid'] ?? 0);
        $total = (float)($job['total_amount'] ?? 0);

        $status = 'UNPAID';
        if ($paid <= 0) {
            $status = 'UNPAID';
        } elseif ($total > 0) {
            if ($paid >= $total - 0.01) {
                $status = 'PAID';
            } else {
                $status = 'PARTIAL';
            }
        } else {
            $status = $paid > 0 ? 'PAID' : 'UNPAID';
        }

        // ===== KOZMOS_PAYMENT_FIX: fix SQL syntax (begin)
        $db->update('jobs', [
            'amount_paid' => $paid,
            'payment_status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$jobId]);
        // ===== KOZMOS_PAYMENT_FIX: fix SQL syntax (end)
    }

    public function getPayments($jobId)
    {
        $paymentModel = new JobPayment();
        return $paymentModel->getByJob($jobId);
    }

    public function hasPayments($jobId): bool
    {
        $row = $this->db->fetch('SELECT COUNT(*) as cnt FROM job_payments WHERE job_id = ?', [$jobId]);
        return (int)($row['cnt'] ?? 0) > 0;
    }

    public function getJobsWithoutPayments()
    {
        $where = $this->scopeToCompany('WHERE NOT EXISTS (SELECT 1 FROM job_payments jp WHERE jp.job_id = j.id)', 'j');

        return $this->db->fetchAll("
            SELECT j.*
            FROM jobs j
            {$where}
        ");
    }

    /**
     * İş durumları
     */
    public static function getStatuses()
    {
        return [
            'SCHEDULED' => 'Planlandı',
            'DONE' => 'Tamamlandı',
            'CANCELLED' => 'İptal',
        ];
    }
}

<?php
/**
 * Job Payment Model
 */

class JobPayment
{
    use CompanyScope;

    private $db;
    private ?bool $hasCompanyId = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function hasCompanyIdColumn(): bool
    {
        if ($this->hasCompanyId !== null) {
            return $this->hasCompanyId;
        }
        try {
            $cols = $this->db->fetchAll("PRAGMA table_info(job_payments)");
            foreach ($cols as $c) {
                if (($c['name'] ?? '') === 'company_id') {
                    $this->hasCompanyId = true;
                    return true;
                }
            }
        } catch (Throwable $e) {
            // fallthrough
        }
        $this->hasCompanyId = false;
        return false;
    }

    public function create(array $data)
    {
        $job = $this->findJob($data['job_id'] ?? null);
        if (!$job) {
            return 0;
        }

        $payload = [
            'job_id' => (int)$data['job_id'],
            'amount' => (float)$data['amount'],
            'paid_at' => $data['paid_at'] ?? date('Y-m-d'),
            'note' => $data['note'] ?? null,
            'finance_id' => $data['finance_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($this->hasCompanyIdColumn()) {
            $payload['company_id'] = (int)($job['company_id'] ?? $this->getCompanyIdForInsert());
        }

        return $this->db->insert('job_payments', $payload);
    }

    public function getByJob(int $jobId): array
    {
        $job = $this->findJob($jobId);
        if (!$job) {
            return [];
        }

        if ($this->hasCompanyIdColumn()) {
            $where = $this->scopeToCompany('WHERE job_id = ?', null);
        } else {
            $where = 'WHERE job_id = ?';
        }

        return $this->db->fetchAll(
            "SELECT * FROM job_payments {$where} ORDER BY paid_at DESC, created_at DESC",
            [$jobId]
        );
    }

    public function findByFinance(int $financeId)
    {
        if ($this->hasCompanyIdColumn()) {
            $where = $this->scopeToCompany('WHERE finance_id = ?', null);
        } else {
            $where = 'WHERE finance_id = ?';
        }
        return $this->db->fetch("SELECT * FROM job_payments {$where}", [$financeId]);
    }

    public function update(int $id, array $data)
    {
        $existing = $this->db->fetch(
            "SELECT * FROM job_payments WHERE id = ?",
            [$id]
        );

        if (!$existing) {
            return 0;
        }
        if ($this->hasCompanyIdColumn() && !$this->verifyCompanyAccess((int)($existing['company_id'] ?? 0))) {
            return 0;
        }

        $payload = [];
        $allowed = ['job_id', 'amount', 'paid_at', 'note', 'finance_id'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (empty($payload)) {
            return 0;
        }

        $payload['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('job_payments', $payload, 'id = :id', ['id' => $id]);
    }

    public function deleteByFinance(int $financeId): bool
    {
        $payment = $this->findByFinance($financeId);
        if (!$payment) {
            return false;
        }

        $this->db->delete('job_payments', 'id = ?', [$payment['id']]);
        return true;
    }

    public function deleteByJob(int $jobId): array
    {
        $payments = $this->getByJob($jobId);
        if (!empty($payments)) {
            $this->db->delete('job_payments', 'job_id = ?', [$jobId]);
        }
        return $payments;
    }

    private function findJob($jobId)
    {
        if (!$jobId) {
            return null;
        }

        return (new Job())->find((int)$jobId);
    }
}

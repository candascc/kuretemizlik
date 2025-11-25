<?php
/**
 * RecurringJob Model
 */

class RecurringJob
{
    use CompanyScope;

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all($status = null)
    {
        $params = [];
        $where = $status
            ? $this->scopeToCompany('WHERE status = ?')
            : $this->scopeToCompany('WHERE 1=1');

        if ($status) {
            $params[] = $status;
        }

        $sql = "SELECT * FROM recurring_jobs {$where} ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function allWithDetails($status = null)
    {
        $where = $status
            ? $this->scopeToCompany('WHERE rj.status = ?', 'rj')
            : $this->scopeToCompany('WHERE 1=1', 'rj');

        $params = $status ? [$status] : [];

        $sql = "SELECT 
                    rj.*, 
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    s.name AS service_name,
                    (SELECT COUNT(*) FROM jobs WHERE recurring_job_id = rj.id) as jobs_count
                FROM recurring_jobs rj
                LEFT JOIN customers c ON rj.customer_id = c.id
                LEFT JOIN services s ON rj.service_id = s.id
                {$where}
                ORDER BY rj.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function find($id)
    {
        $where = $this->scopeToCompany('WHERE rj.id = ?', 'rj');
        return $this->db->fetch("SELECT * FROM recurring_jobs rj {$where}", [$id]);
    }

    public function create(array $data)
    {
        $payload = [
            'customer_id' => (int)$data['customer_id'],
            'address_id' => $data['address_id'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'frequency' => $data['frequency'],
            'interval' => isset($data['interval']) ? (int)$data['interval'] : 1,
            'byweekday' => isset($data['byweekday']) ? json_encode(array_values((array)$data['byweekday'])) : null,
            'bymonthday' => isset($data['bymonthday']) ? (int)$data['bymonthday'] : null,
            'byhour' => isset($data['byhour']) ? (int)$data['byhour'] : null,
            'byminute' => isset($data['byminute']) ? (int)$data['byminute'] : null,
            'duration_min' => isset($data['duration_min']) ? (int)$data['duration_min'] : 60,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'timezone' => $data['timezone'] ?? 'Europe/Istanbul',
            'status' => $data['status'] ?? 'ACTIVE',
            'default_total_amount' => isset($data['default_total_amount']) ? (float)$data['default_total_amount'] : 0,
            'default_notes' => $data['default_notes'] ?? null,
            'default_assignees' => isset($data['default_assignees']) ? json_encode(array_values((array)$data['default_assignees'])) : null,
            'exclusions' => isset($data['exclusions']) ? json_encode(array_values((array)$data['exclusions'])) : null,
            'holiday_policy' => $data['holiday_policy'] ?? 'SKIP',
            'pricing_model' => $data['pricing_model'] ?? 'PER_JOB',
            'monthly_amount' => isset($data['monthly_amount']) ? (float)$data['monthly_amount'] : null,
            'contract_total_amount' => isset($data['contract_total_amount']) ? (float)$data['contract_total_amount'] : null,
            'company_id' => $this->getCompanyIdForInsert(),
        ];
        return $this->db->insert('recurring_jobs', $payload);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        if (!$record) {
            return 0;
        }

        $payload = [];
        $fields = [
            'customer_id','address_id','service_id','frequency','interval','byweekday','bymonthday','byhour','byminute','duration_min',
            'start_date','end_date','timezone','status','default_total_amount','default_notes','default_assignees','exclusions','holiday_policy',
            'pricing_model','monthly_amount','contract_total_amount'
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if (in_array($field, ['byweekday','default_assignees','exclusions'], true) && $value !== null) {
                    $value = json_encode(array_values((array)$value));
                }
                if (in_array($field, ['interval','bymonthday','byhour','byminute','duration_min'], true) && $value !== null) {
                    $value = (int)$value;
                }
                if (in_array($field, ['default_total_amount','monthly_amount','contract_total_amount'], true) && $value !== null) {
                    $value = (float)$value;
                }
                $payload[$field] = $value;
            }
        }
        if (empty($payload)) {
            return 0;
        }
        $payload['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('recurring_jobs', $payload, 'id = ?', [$id]);
    }

    public function toggleStatus($id)
    {
        $row = $this->find($id);
        if (!$row) { return 0; }
        $new = ($row['status'] === 'ACTIVE') ? 'PAUSED' : 'ACTIVE';
        return $this->db->update('recurring_jobs', ['status' => $new, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public function delete($id)
    {
        $row = $this->find($id);
        if (!$row) {
            return 0;
        }
        return $this->db->delete('recurring_jobs', 'id = ?', [$id]);
    }

    public function getActive()
    {
        return $this->all('ACTIVE');
    }

    public static function decodeJsonList($value)
    {
        if ($value === null || $value === '') { return []; }
        if (is_array($value)) { return $value; }
        $arr = json_decode((string)$value, true);
        return is_array($arr) ? $arr : [];
    }
}

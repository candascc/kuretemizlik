<?php
/**
 * Job Contract Model
 * İş bazlı sözleşmeler modeli
 */

class JobContract
{
    use CompanyScope;

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Tüm sözleşmeleri getir
     */
    public function all($filters = [], $limit = null, $offset = 0)
    {
        $sql = "
            SELECT 
                jc.*,
                j.customer_id as job_customer_id,
                j.start_at as job_start_at,
                j.end_at as job_end_at,
                j.status as job_status,
                c.name as customer_name,
                c.phone as customer_phone
            FROM job_contracts jc
            INNER JOIN jobs j ON jc.job_id = j.id
            LEFT JOIN customers c ON j.customer_id = c.id
            WHERE 1=1
        ";
        $params = [];

        // Company scope
        $companyWhere = $this->scopeToCompany('AND j.company_id', 'j');
        if ($companyWhere) {
            $sql .= " " . str_replace('WHERE', 'AND', $companyWhere);
        }

        if (!empty($filters['status'])) {
            $sql .= " AND jc.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['job_id'])) {
            $sql .= " AND jc.job_id = ?";
            $params[] = (int)$filters['job_id'];
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND j.customer_id = ?";
            $params[] = (int)$filters['customer_id'];
        }

        if (!empty($filters['expired'])) {
            if ($filters['expired'] === true || $filters['expired'] === '1') {
                $sql .= " AND jc.expires_at < datetime('now') AND jc.status != 'APPROVED'";
            } else {
                $sql .= " AND (jc.expires_at IS NULL OR jc.expires_at >= datetime('now') OR jc.status = 'APPROVED')";
            }
        }

        $sql .= " ORDER BY jc.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * ID ile sözleşme getir
     */
    public function find($id)
    {
        $sql = "
            SELECT 
                jc.*,
                j.customer_id as job_customer_id,
                j.start_at as job_start_at,
                j.end_at as job_end_at,
                j.status as job_status,
                j.total_amount as job_total_amount,
                c.name as customer_name,
                c.phone as customer_phone,
                c.email as customer_email
            FROM job_contracts jc
            INNER JOIN jobs j ON jc.job_id = j.id
            LEFT JOIN customers c ON j.customer_id = c.id
            WHERE jc.id = ?
        ";

        // Company scope
        $companyWhere = $this->scopeToCompany('AND j.company_id', 'j');
        if ($companyWhere) {
            $sql .= " " . str_replace('WHERE', 'AND', $companyWhere);
        }

        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Job ID ile sözleşme getir
     */
    public function findByJobId($jobId)
    {
        $job = (new Job())->find($jobId);
        if (!$job || !$this->verifyCompanyAccess((int)($job['company_id'] ?? null))) {
            return null;
        }

        return $this->db->fetch(
            "SELECT * FROM job_contracts WHERE job_id = ?",
            [$jobId]
        );
    }

    /**
     * Yeni sözleşme oluştur
     */
    public function create($data)
    {
        $job = (new Job())->find($data['job_id'] ?? null);
        if (!$job || !$this->verifyCompanyAccess((int)($job['company_id'] ?? null))) {
            return 0;
        }

        // Aynı job için zaten sözleşme var mı kontrol et
        $existing = $this->findByJobId($data['job_id']);
        if ($existing) {
            return 0; // UNIQUE constraint
        }

        $contractData = [
            'job_id' => (int)$data['job_id'],
            'template_id' => isset($data['template_id']) ? (int)$data['template_id'] : null,
            'status' => $data['status'] ?? 'PENDING',
            'approval_method' => $data['approval_method'] ?? 'SMS_OTP',
            'approved_at' => null,
            'approved_phone' => null,
            'approved_ip' => null,
            'approved_user_agent' => null,
            'approved_customer_id' => null,
            'sms_sent_at' => null,
            'sms_sent_count' => 0,
            'last_sms_token_id' => null,
            'contract_text' => $data['contract_text'] ?? null,
            'contract_pdf_path' => $data['contract_pdf_path'] ?? null,
            'contract_hash' => $data['contract_hash'] ?? null,
            'metadata' => isset($data['metadata']) 
                ? (is_string($data['metadata']) ? $data['metadata'] : json_encode($data['metadata']))
                : null,
            'expires_at' => $data['expires_at'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return $this->db->insert('job_contracts', $contractData);
    }

    /**
     * Sözleşme güncelle
     */
    public function update($id, $data)
    {
        $contract = $this->find($id);
        if (!$contract) {
            return 0;
        }

        $contractData = [];
        $allowed = [
            'template_id', 'status', 'approval_method',
            'approved_at', 'approved_phone', 'approved_ip', 'approved_user_agent', 'approved_customer_id',
            'sms_sent_at', 'sms_sent_count', 'last_sms_token_id',
            'contract_text', 'contract_pdf_path', 'contract_hash',
            'metadata', 'expires_at'
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'metadata' && is_array($data[$field])) {
                    $contractData[$field] = json_encode($data[$field]);
                } else {
                    $contractData[$field] = $data[$field];
                }
            }
        }

        if (empty($contractData)) {
            return 0;
        }

        $contractData['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('job_contracts', $contractData, 'id = ?', [$id]);
    }

    /**
     * Sözleşme sil
     */
    public function delete($id)
    {
        $contract = $this->find($id);
        if (!$contract) {
            return 0;
        }

        return $this->db->delete('job_contracts', 'id = ?', [$id]);
    }

    /**
     * Durum güncelle
     */
    public function updateStatus($id, $status)
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * SMS gönderim sayısını artır
     */
    public function incrementSmsCount($id, $tokenId = null)
    {
        $contract = $this->find($id);
        if (!$contract) {
            return false;
        }

        $data = [
            'sms_sent_count' => (int)$contract['sms_sent_count'] + 1,
            'sms_sent_at' => date('Y-m-d H:i:s')
        ];

        if ($tokenId) {
            $data['last_sms_token_id'] = (int)$tokenId;
        }

        return $this->update($id, $data);
    }

    /**
     * Sözleşme onayla
     */
    public function approve($id, $phone, $customerId = null, $ipAddress = null, $userAgent = null)
    {
        return $this->update($id, [
            'status' => 'APPROVED',
            'approved_at' => date('Y-m-d H:i:s'),
            'approved_phone' => $phone,
            'approved_ip' => $ipAddress,
            'approved_user_agent' => $userAgent,
            'approved_customer_id' => $customerId ? (int)$customerId : null
        ]);
    }

    /**
     * Süresi dolmuş sözleşmeleri işaretle
     */
    public function markExpired()
    {
        return $this->db->execute(
            "UPDATE job_contracts 
             SET status = 'EXPIRED', updated_at = datetime('now')
             WHERE expires_at < datetime('now') 
               AND status IN ('PENDING', 'SENT')"
        );
    }

    /**
     * Sözleşme sayısı
     */
    public function count($filters = [])
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM job_contracts jc
            INNER JOIN jobs j ON jc.job_id = j.id
            WHERE 1=1
        ";
        $params = [];

        // Company scope
        $companyWhere = $this->scopeToCompany('AND j.company_id', 'j');
        if ($companyWhere) {
            $sql .= " " . str_replace('WHERE', 'AND', $companyWhere);
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                // Multiple status values (e.g., ['PENDING', 'SENT'])
                $placeholders = implode(',', array_fill(0, count($filters['status']), '?'));
                $sql .= " AND jc.status IN ($placeholders)";
                $params = array_merge($params, $filters['status']);
            } else {
                // Single status value
                $sql .= " AND jc.status = ?";
                $params[] = $filters['status'];
            }
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND j.customer_id = ?";
            $params[] = (int)$filters['customer_id'];
        }

        if (!empty($filters['expired'])) {
            if ($filters['expired'] === true || $filters['expired'] === '1') {
                $sql .= " AND jc.expires_at < datetime('now') AND jc.status != 'APPROVED'";
            } else {
                $sql .= " AND (jc.expires_at IS NULL OR jc.expires_at >= datetime('now') OR jc.status = 'APPROVED')";
            }
        }

        $result = $this->db->fetch($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    /**
     * İlişki: Bu sözleşmenin ait olduğu iş
     */
    public function job($contractId)
    {
        $contract = $this->find($contractId);
        if (!$contract) {
            return null;
        }

        $jobModel = new Job();
        return $jobModel->find($contract['job_id']);
    }

    /**
     * İlişki: Bu sözleşmenin şablonu
     */
    public function template($contractId)
    {
        $contract = $this->find($contractId);
        if (!$contract || !$contract['template_id']) {
            return null;
        }

        $templateModel = new ContractTemplate();
        return $templateModel->find($contract['template_id']);
    }

    /**
     * İlişki: Bu sözleşmeyi onaylayan müşteri
     */
    public function approvedCustomer($contractId)
    {
        $contract = $this->find($contractId);
        if (!$contract || !$contract['approved_customer_id']) {
            return null;
        }

        $customerModel = new Customer();
        return $customerModel->find($contract['approved_customer_id']);
    }

    /**
     * İlişki: Son gönderilen OTP token
     */
    public function lastOtpToken($contractId)
    {
        $contract = $this->find($contractId);
        if (!$contract || !$contract['last_sms_token_id']) {
            return null;
        }

        $tokenModel = new ContractOtpToken();
        return $tokenModel->find($contract['last_sms_token_id']);
    }

    /**
     * İlişki: Bu sözleşme için tüm OTP token'ları
     */
    public function otpTokens($contractId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM contract_otp_tokens 
             WHERE job_contract_id = ? 
             ORDER BY created_at DESC",
            [$contractId]
        );
    }
}


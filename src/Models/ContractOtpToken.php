<?php
/**
 * Contract OTP Token Model
 * Sözleşme onayı için OTP kodları modeli
 */

class ContractOtpToken
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * ID ile token getir
     */
    public function find($id)
    {
        return $this->db->fetch(
            "SELECT * FROM contract_otp_tokens WHERE id = ?",
            [$id]
        );
    }

    /**
     * Job contract ID ile aktif token getir
     */
    public function findActiveByJobContract($jobContractId)
    {
        return $this->db->fetch(
            "SELECT * FROM contract_otp_tokens 
             WHERE job_contract_id = ? 
               AND verified_at IS NULL 
               AND expires_at > datetime('now')
             ORDER BY created_at DESC 
             LIMIT 1",
            [$jobContractId]
        );
    }

    /**
     * Token hash ile token getir (doğrulama için)
     */
    public function findByToken($tokenHash)
    {
        return $this->db->fetch(
            "SELECT * FROM contract_otp_tokens WHERE token = ?",
            [$tokenHash]
        );
    }

    /**
     * Yeni OTP token oluştur
     */
    public function create($data)
    {
        $tokenData = [
            'job_contract_id' => (int)$data['job_contract_id'],
            'customer_id' => (int)$data['customer_id'],
            'token' => $data['token'], // Hashlenmiş token
            'phone' => $data['phone'],
            'channel' => $data['channel'] ?? 'sms',
            'expires_at' => $data['expires_at'],
            'sent_at' => $data['sent_at'] ?? date('Y-m-d H:i:s'),
            'verified_at' => null,
            'attempts' => 0,
            'max_attempts' => $data['max_attempts'] ?? 5,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'meta' => isset($data['meta']) 
                ? (is_string($data['meta']) ? $data['meta'] : json_encode($data['meta']))
                : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return $this->db->insert('contract_otp_tokens', $tokenData);
    }

    /**
     * Token güncelle
     */
    public function update($id, $data)
    {
        $token = $this->find($id);
        if (!$token) {
            return 0;
        }

        $tokenData = [];
        $allowed = [
            'verified_at', 'attempts', 'ip_address', 'user_agent', 'meta'
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'meta' && is_array($data[$field])) {
                    $tokenData[$field] = json_encode($data[$field]);
                } else {
                    $tokenData[$field] = $data[$field];
                }
            }
        }

        if (empty($tokenData)) {
            return 0;
        }

        $tokenData['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('contract_otp_tokens', $tokenData, 'id = ?', [$id]);
    }

    /**
     * Deneme sayısını artır
     */
    public function incrementAttempts($id)
    {
        $token = $this->find($id);
        if (!$token) {
            return false;
        }

        return $this->update($id, [
            'attempts' => (int)$token['attempts'] + 1
        ]);
    }

    /**
     * Token'ı doğrulanmış olarak işaretle
     */
    public function markAsVerified($id)
    {
        return $this->update($id, [
            'verified_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Süresi dolmuş token'ları temizle
     */
    public function cleanupExpired()
    {
        return $this->db->execute(
            "DELETE FROM contract_otp_tokens 
             WHERE expires_at < datetime('now') 
               AND verified_at IS NULL"
        );
    }

    /**
     * İlişki: Bu token'ın ait olduğu iş sözleşmesi
     */
    public function jobContract($tokenId)
    {
        $token = $this->find($tokenId);
        if (!$token) {
            return null;
        }

        $jobContractModel = new JobContract();
        return $jobContractModel->find($token['job_contract_id']);
    }

    /**
     * İlişki: Bu token'ın ait olduğu müşteri
     */
    public function customer($tokenId)
    {
        $token = $this->find($tokenId);
        if (!$token) {
            return null;
        }

        $customerModel = new Customer();
        return $customerModel->find($token['customer_id']);
    }
}


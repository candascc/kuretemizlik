<?php
/**
 * Online Payment Model
 */

class OnlinePayment
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id)
    {
        return $this->db->fetch('SELECT * FROM online_payments WHERE id = ?', [$id]);
    }

    public function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['building_id'])) { $where[] = 'building_id = ?'; $params[] = $filters['building_id']; }
        if (!empty($filters['unit_id'])) { $where[] = 'unit_id = ?'; $params[] = $filters['unit_id']; }
        if (!empty($filters['status'])) { $where[] = 'status = ?'; $params[] = $filters['status']; }
        if (!empty($filters['method'])) { $where[] = 'payment_method = ?'; $params[] = $filters['method']; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT * FROM online_payments {$whereSql} ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data)
    {
        $payload = [
            'management_fee_id' => $data['management_fee_id'] ?? null,
            'building_id' => (int)$data['building_id'],
            'unit_id' => (int)$data['unit_id'],
            'resident_user_id' => $data['resident_user_id'] ?? null,
            'amount' => isset($data['amount']) ? (float)$data['amount'] : 0,
            'payment_method' => $data['payment_method'] ?? 'credit_card',
            'payment_gateway' => $data['payment_gateway'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'gateway_response' => isset($data['gateway_response']) && is_array($data['gateway_response']) ? json_encode($data['gateway_response']) : ($data['gateway_response'] ?? null),
            'status' => $data['status'] ?? 'pending',
            'paid_at' => $data['paid_at'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('online_payments', $payload);
    }

    public function update(int $id, array $data)
    {
        $fields = ['amount','payment_method','payment_gateway','transaction_id','gateway_response','status','paid_at'];
        $payload = [];
        foreach ($fields as $f) { if (array_key_exists($f, $data)) { $payload[$f] = $data[$f]; } }
        if (empty($payload)) { return 0; }
        return $this->db->update('online_payments', $payload, 'id = ?', [$id]);
    }

    /**
     * Ödemeleri getir (extended version with joins)
     */
    public function all($filters = [], $limit = null, $offset = 0)
    {
        $sql = "
            SELECT 
                op.*,
                u.unit_number,
                u.owner_name,
                b.name as building_name,
                mf.period,
                mf.fee_name
            FROM online_payments op
            LEFT JOIN units u ON op.unit_id = u.id
            LEFT JOIN buildings b ON op.building_id = b.id
            LEFT JOIN management_fees mf ON op.management_fee_id = mf.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['building_id'])) {
            $sql .= " AND op.building_id = ?";
            $params[] = $filters['building_id'];
        }

        if (!empty($filters['unit_id'])) {
            $sql .= " AND op.unit_id = ?";
            $params[] = $filters['unit_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND op.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['transaction_id'])) {
            $sql .= " AND op.transaction_id = ?";
            $params[] = $filters['transaction_id'];
        }

        $sql .= " ORDER BY op.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * ID ile ödeme getir (extended version with joins)
     */
    public function findWithDetails($id)
    {
        return $this->db->fetch(
            "SELECT 
                op.*,
                u.unit_number,
                u.owner_name,
                b.name as building_name,
                mf.period,
                mf.fee_name
            FROM online_payments op
            LEFT JOIN units u ON op.unit_id = u.id
            LEFT JOIN buildings b ON op.building_id = b.id
            LEFT JOIN management_fees mf ON op.management_fee_id = mf.id
            WHERE op.id = ?",
            [$id]
        );
    }

    /**
     * Transaction ID ile ödeme getir
     */
    public function findByTransactionId($transactionId)
    {
        return $this->db->fetch(
            "SELECT * FROM online_payments WHERE transaction_id = ?",
            [$transactionId]
        );
    }

    /**
     * Ödeme başarılı olarak işaretle
     */
    public function markCompleted($id, $transactionId, $gatewayResponse = null): bool
    {
        return $this->update($id, [
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'gateway_response' => $gatewayResponse ? json_encode($gatewayResponse) : null,
            'paid_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Ödeme başarısız olarak işaretle
     */
    public function markFailed($id, $gatewayResponse = null): bool
    {
        return $this->update($id, [
            'status' => 'failed',
            'gateway_response' => $gatewayResponse ? json_encode($gatewayResponse) : null
        ]);
    }
}


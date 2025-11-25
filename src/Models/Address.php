<?php
/**
 * Address Model
 */

class Address
{
    use CompanyScope;

    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Mü�Yteri adreslerini getir
     */
    public function getByCustomer($customerId)
    {
        if (!$this->ensureCustomerAccess($customerId)) {
            return [];
        }

        return $this->db->fetchAll(
            "SELECT a.*
             FROM addresses a
             INNER JOIN customers c ON a.customer_id = c.id
             {$this->scopeToCompany('WHERE c.id = ?', 'c')}
             ORDER BY a.created_at",
            [$customerId]
        );
    }
    
    /**
     * ID ile adres getir
     */
    public function find($id)
    {
        $where = $this->scopeToCompany('WHERE a.id = ?', 'a');
        return $this->db->fetch("SELECT a.* FROM addresses a {$where}", [$id]);
    }
    
    /**
     * Yeni adres olu�Ytur
     */
    public function create($data)
    {
        $companyId = $this->ensureCustomerAccess($data['customer_id']);
        if (!$companyId) {
            return 0;
        }

        $addressData = [
            'customer_id' => $data['customer_id'],
            'label' => $data['label'] ?? null,
            'line' => $data['line'],
            'city' => $data['city'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'company_id' => $companyId
        ];
        
        return $this->db->insert('addresses', $addressData);
    }
    
    /**
     * Adres güncelle
     */
    public function update($id, $data)
    {
        $address = $this->find($id);
        if (!$address) {
            return 0;
        }

        $addressData = [
            'label' => $data['label'] ?? null,
            'line' => $data['line'],
            'city' => $data['city'] ?? null
        ];
        
        return $this->db->update('addresses', $addressData, 'id = ?', [$id]);
    }
    
    /**
     * Adres sil
     */
    public function delete($id)
    {
        $address = $this->find($id);
        if (!$address) {
            return 0;
        }

        return $this->db->delete('addresses', 'id = ?', [$id]);
    }
    
    /**
     * Adres sayısı
     */
    public function count()
    {
        $where = $this->scopeToCompany('WHERE 1=1', 'a');
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM addresses a {$where}");
        return $result['count'];
    }
    
    /**
     * Mü�Yteri adres sayısı
     */
    public function countByCustomer($customerId)
    {
        if (!$this->ensureCustomerAccess($customerId)) {
            return 0;
        }

        $where = $this->scopeToCompany('WHERE a.customer_id = ?', 'a');
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM addresses a {$where}",
            [$customerId]
        );
        return $result['count'];
    }
    
    /**
     * Müşteriye ait tüm adresleri sil
     */
    public function deleteByCustomerId($customerId)
    {
        if (!$this->ensureCustomerAccess($customerId)) {
            return 0;
        }

        return $this->db->delete('addresses', 'customer_id = ?', [$customerId]);
    }

    private function ensureCustomerAccess(int $customerId): ?int
    {
        $customer = (new Customer())->find($customerId);
        if (!$customer) {
            return null;
        }

        $companyId = (int)($customer['company_id'] ?? 0);
        if (!$this->verifyCompanyAccess($companyId)) {
            return null;
        }

        return $companyId;
    }
}

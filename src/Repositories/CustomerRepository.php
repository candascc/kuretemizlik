<?php
/**
 * Customer Repository
 */

class CustomerRepository implements RepositoryInterface
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function find(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$id]);
    }
    
    public function all(array $filters = [], int $limit = null, int $offset = 0): array
    {
        $sql = "SELECT * FROM customers";
        $params = [];
        $where = [];
        
        if (!empty($filters['search'])) {
            $where[] = "(name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function create(array $data): int
    {
        $customerData = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $customerId = $this->db->insert('customers', $customerData);
        
        // Add addresses if provided
        if (!empty($data['addresses'])) {
            foreach ($data['addresses'] as $address) {
                if (!empty($address['line'])) {
                    $this->db->insert('addresses', [
                        'customer_id' => $customerId,
                        'label' => $address['label'] ?? null,
                        'line' => $address['line'],
                        'city' => $address['city'] ?? null,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
        
        return $customerId;
    }
    
    public function update(int $id, array $data): bool
    {
        $customerData = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $this->db->update('customers', $customerData, 'id = ?', [$id]);
        
        // Update addresses if provided
        if (isset($data['addresses'])) {
            // Delete existing addresses
            $this->db->delete('addresses', 'customer_id = ?', [$id]);
            
            // Add new addresses
            foreach ($data['addresses'] as $address) {
                if (!empty($address['line'])) {
                    $this->db->insert('addresses', [
                        'customer_id' => $id,
                        'label' => $address['label'] ?? null,
                        'line' => $address['line'],
                        'city' => $address['city'] ?? null,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
        
        return $result > 0;
    }
    
    public function delete(int $id): bool
    {
        // Addresses will be deleted automatically due to CASCADE
        return $this->db->delete('customers', 'id = ?', [$id]) > 0;
    }
    
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM customers";
        $params = [];
        $where = [];
        
        if (!empty($filters['search'])) {
            $where[] = "(name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $result = $this->db->fetch($sql, $params);
        return (int)$result['count'];
    }
    
    public function findWithAddresses(int $id): ?array
    {
        $customer = $this->find($id);
        if (!$customer) {
            return null;
        }
        
        $addresses = $this->db->fetchAll(
            "SELECT * FROM addresses WHERE customer_id = ? ORDER BY created_at",
            [$id]
        );
        
        $customer['addresses'] = $addresses;
        return $customer;
    }
    
    public function search(string $query, int $limit = 20): array
    {
        $searchTerm = '%' . $query . '%';
        
        return $this->db->fetchAll(
            "SELECT * FROM customers 
             WHERE name LIKE ? OR phone LIKE ? OR email LIKE ?
             ORDER BY name
             LIMIT ?",
            [$searchTerm, $searchTerm, $searchTerm, $limit]
        );
    }
}

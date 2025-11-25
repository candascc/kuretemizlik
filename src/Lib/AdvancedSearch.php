<?php
/**
 * Advanced Search
 * Full-text search with fuzzy matching and filters
 */
class AdvancedSearch
{
    /**
     * Search jobs with advanced filters
     */
    public static function searchJobs(array $filters = [], int $limit = 50): array
    {
        $db = Database::getInstance();
        
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
            WHERE 1=1
        ";
        
        $params = [];
        
        // Full-text search
        if (!empty($filters['q'])) {
            $searchTerm = '%' . $filters['q'] . '%';
            $sql .= " AND (
                c.name LIKE ? OR
                c.phone LIKE ? OR
                c.email LIKE ? OR
                j.note LIKE ? OR
                s.name LIKE ? OR
                a.line LIKE ?
            )";
            $params = array_merge($params, array_fill(0, 6, $searchTerm));
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $sql .= " AND j.status = ?";
            $params[] = $filters['status'];
        }
        
        // Payment status filter
        if (!empty($filters['payment_status'])) {
            $sql .= " AND j.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(j.start_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(j.start_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Amount range filter
        if (!empty($filters['amount_min'])) {
            $sql .= " AND j.total_amount >= ?";
            $params[] = $filters['amount_min'];
        }
        
        if (!empty($filters['amount_max'])) {
            $sql .= " AND j.total_amount <= ?";
            $params[] = $filters['amount_max'];
        }
        
        // Customer filter
        if (!empty($filters['customer_id'])) {
            $sql .= " AND j.customer_id = ?";
            $params[] = $filters['customer_id'];
        }
        
        // Sort
        $sortBy = $filters['sort_by'] ?? 'start_at';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');
        $allowedSort = ['start_at', 'end_at', 'total_amount', 'created_at', 'customer_name'];
        if (in_array($sortBy, $allowedSort)) {
            $sortBy = $sortBy === 'customer_name' ? 'c.name' : "j.{$sortBy}";
            $sql .= " ORDER BY {$sortBy} {$sortOrder}";
        } else {
            $sql .= " ORDER BY j.start_at DESC";
        }
        
        // Limit
        $sql .= " LIMIT ?";
        $params[] = $limit;
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Search customers with advanced filters
     */
    public static function searchCustomers(array $filters = [], int $limit = 50): array
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                c.*,
                COUNT(j.id) as total_jobs,
                SUM(j.total_amount) as total_revenue
            FROM customers c
            LEFT JOIN jobs j ON c.id = j.customer_id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Full-text search
        if (!empty($filters['q'])) {
            $searchTerm = '%' . $filters['q'] . '%';
            $sql .= " AND (
                c.name LIKE ? OR
                c.phone LIKE ? OR
                c.email LIKE ? OR
                c.notes LIKE ?
            )";
            $params = array_merge($params, array_fill(0, 4, $searchTerm));
        }
        
        // Active customers filter
        if (isset($filters['has_jobs']) && $filters['has_jobs']) {
            $sql .= " AND EXISTS (SELECT 1 FROM jobs WHERE customer_id = c.id)";
        }
        
        // Sort
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'ASC');
        $allowedSort = ['name', 'phone', 'created_at', 'total_jobs', 'total_revenue'];
        if (in_array($sortBy, $allowedSort)) {
            $sql .= " ORDER BY {$sortBy} {$sortOrder}";
        } else {
            $sql .= " ORDER BY c.name ASC";
        }
        
        $sql .= " GROUP BY c.id";
        
        // Limit
        $sql .= " LIMIT ?";
        $params[] = $limit;
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Get search suggestions
     */
    public static function getSuggestions(string $query, string $type = 'all'): array
    {
        $suggestions = [];
        
        if ($type === 'customers' || $type === 'all') {
            $db = Database::getInstance();
            $customers = $db->fetchAll(
                "SELECT DISTINCT name, phone FROM customers WHERE name LIKE ? OR phone LIKE ? LIMIT 5",
                ['%' . $query . '%', '%' . $query . '%']
            );
            foreach ($customers as $customer) {
                $suggestions[] = [
                    'type' => 'customer',
                    'text' => $customer['name'] . ' (' . $customer['phone'] . ')',
                    'value' => $customer['name']
                ];
            }
        }
        
        return $suggestions;
    }
}


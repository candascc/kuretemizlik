<?php
declare(strict_types=1);

/**
 * Eager Loader Helper
 * 
 * Phase 3.1: N+1 Query Optimization - Batch loading for related data
 * 
 * This helper provides methods to batch load related data to prevent N+1 queries.
 * Instead of loading related records one by one in loops, this class loads all
 * related records in a single query, significantly improving performance.
 * 
 * @package App\Lib
 * @since Phase 3.1
 */
class EagerLoader
{
    /**
     * Batch load customers by IDs
     * 
     * Loads multiple customer records in a single query instead of loading them
     * individually. Returns an associative array keyed by customer ID for O(1) lookup.
     * 
     * @param array $customerIds Array of customer IDs to load
     * @return array Associative array [customer_id => customer_data] or empty array if no IDs provided
     * 
     * @example
     * $customerIds = [1, 2, 3];
     * $customers = EagerLoader::loadCustomers($customerIds);
     * // Returns: [1 => [...], 2 => [...], 3 => [...]]
     */
    public static function loadCustomers(array $customerIds): array
    {
        if (empty($customerIds)) {
            return [];
        }
        
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
        
        $customers = $db->fetchAll(
            "SELECT * FROM customers WHERE id IN ({$placeholders})",
            $customerIds
        );
        
        $result = [];
        foreach ($customers as $customer) {
            $result[$customer['id']] = $customer;
        }
        
        return $result;
    }
    
    /**
     * Batch load addresses by customer IDs
     * 
     * Loads all addresses for multiple customers in a single query and groups them
     * by customer ID. This prevents N+1 queries when displaying customer addresses.
     * 
     * @param array $customerIds Array of customer IDs to load addresses for
     * @return array Associative array [customer_id => [address1, address2, ...]] or empty array if no IDs provided
     * 
     * @example
     * $customerIds = [1, 2];
     * $addresses = EagerLoader::loadAddressesByCustomers($customerIds);
     * // Returns: [1 => [[...], [...]], 2 => [[...]]]
     */
    public static function loadAddressesByCustomers(array $customerIds): array
    {
        if (empty($customerIds)) {
            return [];
        }
        
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
        
        $addresses = $db->fetchAll(
            "SELECT * FROM addresses WHERE customer_id IN ({$placeholders}) ORDER BY id",
            $customerIds
        );
        
        $result = [];
        foreach ($addresses as $address) {
            $customerId = $address['customer_id'];
            if (!isset($result[$customerId])) {
                $result[$customerId] = [];
            }
            $result[$customerId][] = $address;
        }
        
        return $result;
    }
    
    /**
     * Batch load services by IDs
     * 
     * @param array $serviceIds Array of service IDs
     * @return array Associative array [service_id => service_data]
     */
    public static function loadServices(array $serviceIds): array
    {
        if (empty($serviceIds)) {
            return [];
        }
        
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
        
        $services = $db->fetchAll(
            "SELECT * FROM services WHERE id IN ({$placeholders})",
            $serviceIds
        );
        
        $result = [];
        foreach ($services as $service) {
            $result[$service['id']] = $service;
        }
        
        return $result;
    }
    
    /**
     * Batch load units by IDs
     * 
     * @param array $unitIds Array of unit IDs
     * @return array Associative array [unit_id => unit_data]
     */
    public static function loadUnits(array $unitIds): array
    {
        if (empty($unitIds)) {
            return [];
        }
        
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
        
        $units = $db->fetchAll(
            "SELECT * FROM units WHERE id IN ({$placeholders})",
            $unitIds
        );
        
        $result = [];
        foreach ($units as $unit) {
            $result[$unit['id']] = $unit;
        }
        
        return $result;
    }
    
    /**
     * Batch load buildings by IDs
     * 
     * @param array $buildingIds Array of building IDs
     * @return array Associative array [building_id => building_data]
     */
    public static function loadBuildings(array $buildingIds): array
    {
        if (empty($buildingIds)) {
            return [];
        }
        
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($buildingIds), '?'));
        
        $buildings = $db->fetchAll(
            "SELECT * FROM buildings WHERE id IN ({$placeholders})",
            $buildingIds
        );
        
        $result = [];
        foreach ($buildings as $building) {
            $result[$building['id']] = $building;
        }
        
        return $result;
    }
    
    /**
     * Batch load jobs with all related data (customers, services, addresses)
     * 
     * @param array $jobs Array of job records (must have customer_id, service_id, address_id)
     * @return array Jobs with eager-loaded related data
     */
    public static function loadJobsWithRelations(array $jobs): array
    {
        if (empty($jobs)) {
            return [];
        }
        
        // Collect unique IDs
        $customerIds = [];
        $serviceIds = [];
        $addressIds = [];
        
        foreach ($jobs as $job) {
            if (!empty($job['customer_id'])) {
                $customerIds[] = $job['customer_id'];
            }
            if (!empty($job['service_id'])) {
                $serviceIds[] = $job['service_id'];
            }
            if (!empty($job['address_id'])) {
                $addressIds[] = $job['address_id'];
            }
        }
        
        // Batch load all related data
        $customers = self::loadCustomers(array_unique($customerIds));
        $services = self::loadServices(array_unique($serviceIds));
        $addresses = self::loadAddresses(array_unique($addressIds));
        
        // Attach related data to jobs
        foreach ($jobs as &$job) {
            if (!empty($job['customer_id']) && isset($customers[$job['customer_id']])) {
                $job['customer'] = $customers[$job['customer_id']];
            }
            if (!empty($job['service_id']) && isset($services[$job['service_id']])) {
                $job['service'] = $services[$job['service_id']];
            }
            if (!empty($job['address_id']) && isset($addresses[$job['address_id']])) {
                $job['address'] = $addresses[$job['address_id']];
            }
        }
        
        return $jobs;
    }
    
    /**
     * Batch load addresses by IDs
     * 
     * @param array $addressIds Array of address IDs
     * @return array Associative array [address_id => address_data]
     */
    public static function loadAddresses(array $addressIds): array
    {
        if (empty($addressIds)) {
            return [];
        }
        
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($addressIds), '?'));
        
        $addresses = $db->fetchAll(
            "SELECT * FROM addresses WHERE id IN ({$placeholders})",
            $addressIds
        );
        
        $result = [];
        foreach ($addresses as $address) {
            $result[$address['id']] = $address;
        }
        
        return $result;
    }
    
    /**
     * Batch load users by IDs
     * 
     * @param array $userIds Array of user IDs
     * @return array Associative array [user_id => user_data]
     */
    public static function loadUsers(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        
        $users = $db->fetchAll(
            "SELECT id, name, email, phone, role, is_active FROM users WHERE id IN ({$placeholders})",
            $userIds
        );
        
        $result = [];
        foreach ($users as $user) {
            $result[$user['id']] = $user;
        }
        
        return $result;
    }
}


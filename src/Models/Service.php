<?php
/**
 * Service Model
 */

class Service
{
    use CompanyScope;

    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Tüm hizmetleri getir
     */
    public function all()
    {
        $where = $this->scopeToCompany('WHERE 1=1');
        $cacheKey = $this->cacheKey('services_all');

        return Cache::remember($cacheKey, function() use ($where) {
            return $this->db->fetchAll("SELECT * FROM services {$where} ORDER BY name");
        }, 3600); // Cache for 1 hour
    }
    
    /**
     * Get active services only
     * UX-CRIT-001: Helper for wizard
     */
    public function getActive()
    {
        // ===== PRODUCTION FIX: Handle scopeToCompany and database errors gracefully =====
        try {
            $where = $this->scopeToCompany('WHERE is_active = 1');
            $cacheKey = $this->cacheKey('services_active');

            return Cache::remember($cacheKey, function() use ($where) {
                try {
                    return $this->db->fetchAll("SELECT * FROM services {$where} ORDER BY name");
                } catch (Throwable $e) {
                    error_log("Service::getActive() database error: " . $e->getMessage());
                    error_log("SQL: SELECT * FROM services {$where} ORDER BY name");
                    return [];
                }
            }, 3600); // Cache for 1 hour
        } catch (Throwable $e) {
            error_log("Service::getActive() error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            // Return empty array instead of crashing
            return [];
        }
        // ===== PRODUCTION FIX END =====
    }
    
    /**
     * ID ile hizmet getir
     */
    public function find($id)
    {
        $where = $this->scopeToCompany('WHERE s.id = ?', 's');
        return $this->db->fetch("SELECT * FROM services s {$where}", [$id]);
    }
    
    /**
     * Yeni hizmet olu�Ytur
     */
    public function create($data)
    {
        $companyId = $this->getCompanyIdForInsert();

        $serviceData = [
            'name' => $data['name'],
            'duration_min' => $data['duration_min'] ?? null,
            'default_fee' => $data['default_fee'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'company_id' => $companyId
        ];
        
        return $this->db->insert('services', $serviceData);
    }
    
    /**
     * Hizmet güncelle
     */
    public function update($id, $data)
    {
        $service = $this->find($id);
        if (!$service) {
            return 0;
        }

        $serviceData = [
            'name' => $data['name'],
            'duration_min' => $data['duration_min'] ?? null,
            'default_fee' => $data['default_fee'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ];
        
        $result = $this->db->update('services', $serviceData, 'id = ?', [$id]);
        
        // Clear cache when service is updated
        if ($result) {
            Cache::delete($this->cacheKey('services_all'));
            Cache::delete($this->cacheKey('services_active'));
        }
        
        return $result;
    }
    
    /**
     * Hizmet sil
     */
    public function delete($id)
    {
        $service = $this->find($id);
        if (!$service) {
            return 0;
        }

        $result = $this->db->delete('services', 'id = ?', [$id]);
        
        // Clear cache when service is deleted
        if ($result) {
            Cache::delete($this->cacheKey('services_all'));
            Cache::delete($this->cacheKey('services_active'));
        }
        
        return $result;
    }
    
    /**
     * Hizmeti aktif/pasif yap
     */
    public function toggleActive($id)
    {
        $service = $this->find($id);
        if (!$service) return false;
        
        $newStatus = $service['is_active'] ? 0 : 1;
        
        return $this->db->update('services', [
            'is_active' => $newStatus
        ], 'id = ?', [$id]);
    }
    
    /**
     * Hizmet sayısı
     */
    public function count()
    {
        $where = $this->scopeToCompany('WHERE 1=1');
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM services {$where}");
        return $result['count'];
    }
    
    /**
     * Hizmet istatistikleri
     */
    public function getStats()
    {
        $total = $this->count();
        $whereActive = $this->scopeToCompany('WHERE is_active = 1');
        $active = $this->db->fetch("SELECT COUNT(*) as count FROM services {$whereActive}")['count'];
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active
        ];
    }
    
    /**
     * Hizmet kullanım istatistikleri
     */
    public function getUsageStats()
    {
        $where = $this->scopeToCompany('WHERE s.is_active = 1', 's');

        return $this->db->fetchAll(
            "SELECT 
                s.name,
                COUNT(j.id) as job_count,
                SUM(CASE WHEN j.status = 'DONE' THEN 1 ELSE 0 END) as completed_count,
                AVG(CASE WHEN j.status = 'DONE' THEN 
                    (julianday(j.end_at) - julianday(j.start_at)) * 24 * 60 
                END) as avg_duration_min
             FROM services s
             LEFT JOIN jobs j ON s.id = j.service_id
             {$where}
             GROUP BY s.id, s.name
             ORDER BY job_count DESC"
        );
    }

    private function cacheKey(string $base): string
    {
        if (Auth::canSwitchCompany()) {
            $filter = isset($_GET['company_filter']) && $_GET['company_filter'] !== ''
                ? (int)$_GET['company_filter']
                : 'all';
            return "{$base}_admin_{$filter}";
        }

        $companyId = Auth::companyId();
        return "{$base}_company_" . ($companyId ?? 'anon');
    }
}

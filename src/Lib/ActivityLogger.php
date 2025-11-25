<?php
/**
 * Aktivite Log Sınıfı
 */

class ActivityLogger
{
    /**
     * Aktivite kaydet
     */
    public static function log($action, $entity = null, $entityId = null, $meta = [])
    {
        if (is_array($entityId) && empty($meta)) {
            $meta = $entityId;
            $entityId = null;
        }

        try {
            $db = Database::getInstance();
            
            // Get user ID safely
            $actorId = null;
            $actorRole = null;
            if (class_exists('Auth') && method_exists('Auth', 'check')) {
                try {
                    $actorId = Auth::check() ? Auth::id() : null;
                    if (method_exists('Auth', 'role')) {
                        $actorRole = Auth::role();
                    }
                } catch (Exception $e) {
                    // Silently fail - actor_id can be null
                }
            }
            
            $data = [
                'actor_id' => $actorId,
                'actor_role' => $actorRole,
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'meta_json' => !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return $db->insert('activity_log', $data);
        } catch (Exception $e) {
            // Silently fail - activity logging is not critical
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("ActivityLogger error: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Kullanıcı giri�Yi
     */
    public static function login($username)
    {
        return self::log('LOGIN', 'user', ['username' => $username]);
    }
    
    /**
     * Kullanıcı çıkı�Yı
     */
    public static function logout($username)
    {
        return self::log('LOGOUT', 'user', ['username' => $username]);
    }
    
    /**
     * İ�Y olu�Yturuldu
     */
    public static function jobCreated($jobId, $customerName)
    {
        return self::log('CREATE', 'job', [
            'job_id' => $jobId,
            'customer_name' => $customerName
        ]);
    }
    
    /**
     * İ�Y güncellendi
     */
    public static function jobUpdated($jobId, $changes = [])
    {
        return self::log('UPDATE', 'job', [
            'job_id' => $jobId,
            'changes' => $changes
        ]);
    }
    
    /**
     * İ�Y silindi
     */
    public static function jobDeleted($jobId, $customerName)
    {
        return self::log('DELETE', 'job', [
            'job_id' => $jobId,
            'customer_name' => $customerName
        ]);
    }
    
    /**
     * Mü�Yteri olu�Yturuldu
     */
    public static function customerCreated($customerId, $customerName)
    {
        return self::log('CREATE', 'customer', [
            'customer_id' => $customerId,
            'customer_name' => $customerName
        ]);
    }
    
    /**
     * Mü�Yteri güncellendi
     */
    public static function customerUpdated($customerId, $changes = [])
    {
        return self::log('UPDATE', 'customer', [
            'customer_id' => $customerId,
            'changes' => $changes
        ]);
    }
    
    /**
     * Mü�Yteri silindi
     */
    public static function customerDeleted($customerId, $customerName)
    {
        return self::log('DELETE', 'customer', [
            'customer_id' => $customerId,
            'customer_name' => $customerName
        ]);
    }
    
    /**
     * Gelir eklendi
     */
    public static function incomeAdded($amount, $category)
    {
        return self::log('CREATE', 'income', [
            'amount' => $amount,
            'category' => $category
        ]);
    }
    
    /**
     * Gider eklendi
     */
    public static function expenseAdded($amount, $category)
    {
        return self::log('CREATE', 'expense', [
            'amount' => $amount,
            'category' => $category
        ]);
    }
    
    /**
     * Şifre de�Yi�Ytirildi
     */
    public static function passwordChanged($userId)
    {
        return self::log('UPDATE', 'user', [
            'user_id' => $userId,
            'field' => 'password'
        ]);
    }
    
    /**
     * Aktivite logları getir
     */
    public static function getLogs($limit = 50, $offset = 0)
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                al.*,
                u.username as actor_name
            FROM activity_log al
            LEFT JOIN users u ON al.actor_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        return $db->fetchAll($sql, [$limit, $offset]);
    }
    
    /**
     * Aktivite logları sayısı
     */
    public static function getLogsCount()
    {
        $db = Database::getInstance();
        $result = $db->fetch("SELECT COUNT(*) as count FROM activity_log");
        return $result['count'];
    }
    
    /**
     * Aktivite logları temizle (eski kayıtlar)
     */
    public static function cleanup($days = 90)
    {
        $db = Database::getInstance();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $db->delete(
            'activity_log',
            'created_at < ?',
            [$cutoffDate]
        );
    }
}

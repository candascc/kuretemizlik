<?php
/**
 * Security Stats Service
 * 
 * ROUND 5 - STAGE 3: Aggregate security statistics for dashboard
 * 
 * Provides aggregated security metrics from audit logs, analytics, and rate limit events.
 * 
 * @package App\Services
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/../Lib/AuditLogger.php';
require_once __DIR__ . '/../Lib/Database.php';

class SecurityStatsService
{
    private $db;
    private $auditLogger;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
    }
    
    /**
     * Get security statistics for dashboard
     * 
     * @param array $filters Filters (date_from, date_to, company_id)
     * @return array Security statistics
     */
    public function getSecurityStats(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? date('Y-m-d H:i:s', strtotime('-24 hours'));
        $dateTo = $filters['date_to'] ?? date('Y-m-d H:i:s');
        $companyId = $filters['company_id'] ?? null;
        
        return [
            'failed_logins_24h' => $this->getFailedLoginsCount($dateFrom, $dateTo, $companyId),
            'failed_logins_7d' => $this->getFailedLoginsCount(date('Y-m-d H:i:s', strtotime('-7 days')), $dateTo, $companyId),
            'rate_limit_exceeded_24h' => $this->getRateLimitExceededCount($dateFrom, $dateTo, $companyId),
            'rate_limit_exceeded_7d' => $this->getRateLimitExceededCount(date('Y-m-d H:i:s', strtotime('-7 days')), $dateTo, $companyId),
            'security_anomalies_24h' => $this->getSecurityAnomaliesCount($dateFrom, $dateTo, $companyId),
            'security_anomalies_7d' => $this->getSecurityAnomaliesCount(date('Y-m-d H:i:s', strtotime('-7 days')), $dateTo, $companyId),
            'mfa_events_24h' => $this->getMfaEventsCount($dateFrom, $dateTo, $companyId),
            'mfa_events_7d' => $this->getMfaEventsCount(date('Y-m-d H:i:s', strtotime('-7 days')), $dateTo, $companyId),
            'active_mfa_users' => $this->getActiveMfaUsersCount($companyId),
            'recent_security_events' => $this->getRecentSecurityEvents(20, $companyId),
        ];
    }
    
    /**
     * Get failed login attempts count
     * 
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @param int|null $companyId Company ID filter
     * @return int Count
     */
    private function getFailedLoginsCount(string $dateFrom, string $dateTo, ?int $companyId): int
    {
        // Check if company_id column exists
        $columnNames = $this->db->getColumnNames('activity_log');
        $hasCompanyColumn = in_array('company_id', $columnNames);
        
        $sql = "SELECT COUNT(*) as count FROM activity_log 
                WHERE action IN ('LOGIN_FAILED', 'PORTAL_LOGIN_FAILED', 'RESIDENT_LOGIN_FAILED')
                AND created_at >= ? AND created_at <= ?";
        $params = [$dateFrom, $dateTo];
        
        if ($companyId !== null && $hasCompanyColumn) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $row = $this->db->fetch($sql, $params);
        return (int)($row['count'] ?? 0);
    }
    
    /**
     * Get rate limit exceeded events count
     * 
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @param int|null $companyId Company ID filter
     * @return int Count
     */
    private function getRateLimitExceededCount(string $dateFrom, string $dateTo, ?int $companyId): int
    {
        // Check if company_id column exists
        $columnNames = $this->db->getColumnNames('activity_log');
        $hasCompanyColumn = in_array('company_id', $columnNames);
        
        $sql = "SELECT COUNT(*) as count FROM activity_log 
                WHERE action IN ('LOGIN_RATE_LIMIT_EXCEEDED', 'PORTAL_LOGIN_RATE_LIMIT_EXCEEDED', 'RESIDENT_LOGIN_RATE_LIMIT_EXCEEDED')
                AND created_at >= ? AND created_at <= ?";
        $params = [$dateFrom, $dateTo];
        
        if ($companyId !== null && $hasCompanyColumn) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $row = $this->db->fetch($sql, $params);
        return (int)($row['count'] ?? 0);
    }
    
    /**
     * Get security anomalies count
     * 
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @param int|null $companyId Company ID filter
     * @return int Count
     */
    private function getSecurityAnomaliesCount(string $dateFrom, string $dateTo, ?int $companyId): int
    {
        // Check if company_id column exists
        $columnNames = $this->db->getColumnNames('activity_log');
        $hasCompanyColumn = in_array('company_id', $columnNames);
        
        $sql = "SELECT COUNT(*) as count FROM activity_log 
                WHERE action = 'SECURITY_ANOMALY_DETECTED'
                AND created_at >= ? AND created_at <= ?";
        $params = [$dateFrom, $dateTo];
        
        if ($companyId !== null && $hasCompanyColumn) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $row = $this->db->fetch($sql, $params);
        return (int)($row['count'] ?? 0);
    }
    
    /**
     * Get MFA events count
     * 
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @param int|null $companyId Company ID filter
     * @return array MFA events breakdown
     */
    private function getMfaEventsCount(string $dateFrom, string $dateTo, ?int $companyId): array
    {
        // Check if company_id column exists
        $columnNames = $this->db->getColumnNames('activity_log');
        $hasCompanyColumn = in_array('company_id', $columnNames);
        
        $sql = "SELECT action, COUNT(*) as count FROM activity_log 
                WHERE action IN ('MFA_ENABLED', 'MFA_DISABLED', 'MFA_CHALLENGE_STARTED', 'MFA_CHALLENGE_PASSED', 'MFA_CHALLENGE_FAILED')
                AND created_at >= ? AND created_at <= ?";
        $params = [$dateFrom, $dateTo];
        
        if ($companyId !== null && $hasCompanyColumn) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $sql .= " GROUP BY action";
        
        $result = $this->db->fetchAll($sql, $params);
        
        $events = [
            'enabled' => 0,
            'disabled' => 0,
            'challenge_started' => 0,
            'challenge_passed' => 0,
            'challenge_failed' => 0,
            'total' => 0,
        ];
        
        foreach ($result as $row) {
            $action = $row['action'] ?? '';
            $count = (int)($row['count'] ?? 0);
            
            switch ($action) {
                case 'MFA_ENABLED':
                    $events['enabled'] = $count;
                    break;
                case 'MFA_DISABLED':
                    $events['disabled'] = $count;
                    break;
                case 'MFA_CHALLENGE_STARTED':
                    $events['challenge_started'] = $count;
                    break;
                case 'MFA_CHALLENGE_PASSED':
                    $events['challenge_passed'] = $count;
                    break;
                case 'MFA_CHALLENGE_FAILED':
                    $events['challenge_failed'] = $count;
                    break;
            }
            
            $events['total'] += $count;
        }
        
        return $events;
    }
    
    /**
     * Get active MFA users count
     * 
     * @param int|null $companyId Company ID filter
     * @return int Count
     */
    private function getActiveMfaUsersCount(?int $companyId): int
    {
        $sql = "SELECT COUNT(*) as count FROM users 
                WHERE two_factor_required = 1 
                AND two_factor_secret IS NOT NULL 
                AND two_factor_secret != ''";
        $params = [];
        
        if ($companyId !== null) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $row = $this->db->fetch($sql, $params);
        return (int)($row['count'] ?? 0);
    }
    
    /**
     * Get recent security events
     * ROUND 50: PHP 8 compatibility - fixed parameter order (required before optional)
     * 
     * @param int $limit Limit
     * @param int|null $companyId Company ID filter
     * @return array Recent events
     */
    private function getRecentSecurityEvents(int $limit = 20, ?int $companyId = null): array
    {
        // Check if company_id column exists
        $columnNames = $this->db->getColumnNames('activity_log');
        $hasCompanyColumn = in_array('company_id', $columnNames);
        
        $sql = "SELECT al.id, al.actor_id, al.action, al.entity, al.meta_json, al.created_at";
        if ($hasCompanyColumn) {
            $sql .= ", al.company_id";
        }
        $sql .= ", u.username, u.role";
        if ($hasCompanyColumn) {
            $sql .= ", c.name as company_name";
        }
        $sql .= " FROM activity_log al
                LEFT JOIN users u ON al.actor_id = u.id";
        if ($hasCompanyColumn) {
            $sql .= " LEFT JOIN companies c ON al.company_id = c.id";
        }
        $sql .= " WHERE al.action IN (
                    'LOGIN_FAILED', 'LOGIN_SUCCESS', 'LOGIN_RATE_LIMIT_EXCEEDED',
                    'PORTAL_LOGIN_FAILED', 'PORTAL_LOGIN_SUCCESS',
                    'RESIDENT_LOGIN_FAILED', 'RESIDENT_LOGIN_SUCCESS',
                    'SECURITY_ANOMALY_DETECTED',
                    'MFA_ENABLED', 'MFA_DISABLED', 'MFA_CHALLENGE_STARTED', 'MFA_CHALLENGE_PASSED', 'MFA_CHALLENGE_FAILED',
                    'IP_BLOCKED', 'IP_ALLOWED'
                )";
        $params = [];
        
        if ($companyId !== null && $hasCompanyColumn) {
            $sql .= " AND al.company_id = ?";
            $params[] = $companyId;
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $result = $this->db->query($sql, $params);
        
        // Format events
        $events = [];
        foreach ($result as $row) {
            $events[] = [
                'id' => $row['id'] ?? null,
                'action' => $row['action'] ?? '',
                'user_id' => $row['actor_id'] ?? null, // Use actor_id instead of user_id
                'username' => $row['username'] ?? null,
                'role' => $row['role'] ?? null,
                'company_id' => $hasCompanyColumn ? ($row['company_id'] ?? null) : null,
                'company_name' => $hasCompanyColumn ? ($row['company_name'] ?? null) : null,
                'ip_address' => $this->extractIpFromMetadata($row['meta_json'] ?? null),
                'created_at' => $row['created_at'] ?? null,
            ];
        }
        
        return $events;
    }
    
    /**
     * Extract IP address from metadata JSON
     * 
     * @param string|null $metadata JSON metadata
     * @return string|null IP address
     */
    private function extractIpFromMetadata(?string $metadata): ?string
    {
        if (empty($metadata)) {
            return null;
        }
        
        $data = json_decode($metadata, true);
        if (!is_array($data)) {
            return null;
        }
        
        return $data['ip_address'] ?? $data['ip'] ?? null;
    }
}


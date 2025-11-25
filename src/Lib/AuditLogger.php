<?php
/**
 * Advanced Audit Logger
 * GDPR compliant audit logging system
 * Tracks all user activities, data changes, and system events
 */

class AuditLogger
{
    private const AUDIT_TABLE = 'activity_log';
    private const RETENTION_DAYS = 2555; // 7 years for compliance
    private const SENSITIVE_FIELDS = ['password', 'password_hash', 'secret', 'token', 'key', 'ssn', 'credit_card'];
    
    private static $instance = null;
    private $db;
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Log user authentication events
     */
    public function logAuth(string $action, ?int $userId = null, array $metadata = []): void
    {
        $this->log('AUTH', $action, $userId, $metadata);
    }
    
    /**
     * Log data access events
     */
    public function logDataAccess(string $action, ?int $userId = null, array $metadata = []): void
    {
        $this->log('DATA_ACCESS', $action, $userId, $metadata);
    }
    
    /**
     * Log data modification events
     */
    public function logDataModification(string $action, ?int $userId = null, array $metadata = []): void
    {
        $this->log('DATA_MODIFICATION', $action, $userId, $metadata);
    }
    
    /**
     * Log system configuration changes
     */
    public function logSystemConfig(string $action, ?int $userId = null, array $metadata = []): void
    {
        $this->log('SYSTEM_CONFIG', $action, $userId, $metadata);
    }
    
    /**
     * Log security events
     */
    public function logSecurity(string $action, ?int $userId = null, array $metadata = []): void
    {
        $this->log('SECURITY', $action, $userId, $metadata);
    }
    
    /**
     * Log business operations
     */
    public function logBusiness(string $action, ?int $userId = null, array $metadata = []): void
    {
        $this->log('BUSINESS', $action, $userId, $metadata);
    }
    
    /**
     * Log administrative actions
     */
    public function logAdmin(string $action, ?int $userId = null, array $metadata = []): void
    {
        $this->log('ADMIN', $action, $userId, $metadata);
    }
    
    /**
     * Core logging method
     */
    public function log(string $category, string $action, ?int $userId = null, array $metadata = []): void
    {
        try {
            // Add additional metadata
            $metadata['category'] = $category;
            $metadata['ip_address'] = $this->getClientIp();
            $metadata['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $metadata['session_id'] = session_id();
            
            // STAGE 1 ROUND 2: Extract IP and user_agent for direct column storage
            $ipAddress = $metadata['ip_address'] ?? $this->getClientIp();
            $userAgent = $metadata['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
            
            // STAGE 1 ROUND 2: Get company_id from user if available
            $companyId = null;
            if ($userId) {
                try {
                    $user = $this->db->fetch("SELECT company_id FROM users WHERE id = ?", [$userId]);
                    $companyId = $user['company_id'] ?? 1;
                } catch (Exception $e) {
                    $companyId = 1; // Default fallback
                }
            } else {
                $companyId = 1; // Default for anonymous actions
            }
            
            $logData = [
                'actor_id' => $userId,
                'action' => $action,
                'entity' => $category, // Use category as entity for activity_log
                'meta_json' => !empty($metadata) ? json_encode($this->sanitizeMetadata($metadata), JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $ipAddress, // STAGE 1 ROUND 2: Direct column storage
                'user_agent' => $userAgent, // STAGE 1 ROUND 2: Direct column storage
                'company_id' => $companyId, // STAGE 1 ROUND 2: Multi-tenant support
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // STAGE 1 ROUND 2: Check if new columns exist before inserting
            $columns = $this->db->fetchAll('PRAGMA table_info(' . self::AUDIT_TABLE . ')');
            $columnNames = array_column($columns, 'name');
            
            $insertColumns = ['actor_id', 'action', 'entity', 'meta_json', 'created_at'];
            $insertValues = [$logData['actor_id'], $logData['action'], $logData['entity'], $logData['meta_json'], $logData['created_at']];
            
            // Add new columns if they exist in table
            if (in_array('ip_address', $columnNames)) {
                $insertColumns[] = 'ip_address';
                $insertValues[] = $logData['ip_address'];
            }
            if (in_array('user_agent', $columnNames)) {
                $insertColumns[] = 'user_agent';
                $insertValues[] = $logData['user_agent'];
            }
            if (in_array('company_id', $columnNames)) {
                $insertColumns[] = 'company_id';
                $insertValues[] = $logData['company_id'];
            }
            
            $placeholders = str_repeat('?,', count($insertValues) - 1) . '?';
            $this->db->query(
                "INSERT INTO " . self::AUDIT_TABLE . " (" . implode(', ', $insertColumns) . ") VALUES ({$placeholders})",
                $insertValues
            );
            
        } catch (Exception $e) {
            // Fallback to file logging if database fails
            error_log("Audit log failed: " . $e->getMessage());
            $this->logToFile($category, $action, $userId, $metadata);
        }
    }
    
    /**
     * Log data changes with before/after values
     */
    public function logDataChange(string $table, string $action, ?int $recordId, ?int $userId, array $beforeData = [], array $afterData = []): void
    {
        $metadata = [
            'table' => $table,
            'record_id' => $recordId,
            'before' => $this->sanitizeData($beforeData),
            'after' => $this->sanitizeData($afterData),
            'changes' => $this->calculateChanges($beforeData, $afterData)
        ];
        
        $this->log('DATA_MODIFICATION', "{$action}_{$table}", $userId, $metadata);
    }
    
    /**
     * Log file operations
     */
    public function logFileOperation(string $action, string $filePath, ?int $userId = null, array $metadata = []): void
    {
        $metadata['file_path'] = $filePath;
        $metadata['file_size'] = file_exists($filePath) ? filesize($filePath) : null;
        
        $this->log('FILE_OPERATION', $action, $userId, $metadata);
    }
    
    /**
     * Log API access
     */
    public function logApiAccess(string $endpoint, string $method, ?int $userId = null, array $metadata = []): void
    {
        $metadata['endpoint'] = $endpoint;
        $metadata['method'] = $method;
        $metadata['response_code'] = http_response_code();
        
        $this->log('API_ACCESS', "{$method}_{$endpoint}", $userId, $metadata);
    }
    
    /**
     * Get audit logs with filtering
     * STAGE 1 ROUND 2: Enhanced with multi-tenant support and IP/user_agent filtering
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];
        
        // STAGE 1 ROUND 2: Multi-tenant filtering
        $user = Auth::user();
        $userCompanyId = $user['company_id'] ?? null;
        $isSuperAdmin = Auth::hasRole('SUPERADMIN');
        
        if (!$isSuperAdmin && $userCompanyId) {
            // Non-superadmin users can only see their company's logs
            $where[] = '(al.company_id = ? OR al.company_id IS NULL)';
            $params[] = $userCompanyId;
        }
        
        if (!empty($filters['category'])) {
            $where[] = 'entity = ?'; // Use entity instead of category
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = 'al.actor_id = ?';
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = 'al.action LIKE ?';
            $params[] = '%' . $filters['action'] . '%';
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'al.created_at >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'al.created_at <= ?';
            $params[] = $filters['date_to'];
        }
        
        // STAGE 1 ROUND 2: IP address filtering (if column exists)
        if (!empty($filters['ip_address'])) {
            $where[] = 'al.ip_address LIKE ?';
            $params[] = '%' . $filters['ip_address'] . '%';
        }
        
        // STAGE 1 ROUND 2: Company filtering (for superadmin)
        if (!empty($filters['company_id']) && $isSuperAdmin) {
            $where[] = 'al.company_id = ?';
            $params[] = $filters['company_id'];
        }
        
        // Check if new columns exist
        $columns = $this->db->fetchAll('PRAGMA table_info(' . self::AUDIT_TABLE . ')');
        $columnNames = array_column($columns, 'name');
        $hasIpColumn = in_array('ip_address', $columnNames);
        $hasUserAgentColumn = in_array('user_agent', $columnNames);
        $hasCompanyColumn = in_array('company_id', $columnNames);
        
        $selectFields = 'al.id, al.actor_id, al.action, al.entity, al.meta_json, al.created_at';
        if ($hasIpColumn) {
            $selectFields .= ', al.ip_address';
        }
        if ($hasUserAgentColumn) {
            $selectFields .= ', al.user_agent';
        }
        if ($hasCompanyColumn) {
            $selectFields .= ', al.company_id';
        }
        
        $sql = "SELECT {$selectFields}, u.username, u.role as user_role, c.name as company_name
                FROM " . self::AUDIT_TABLE . " al 
                LEFT JOIN users u ON al.actor_id = u.id 
                LEFT JOIN companies c ON al.company_id = c.id
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY al.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        try {
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log("Audit getLogs error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . print_r($params, true));
            return [];
        }
    }
    
    /**
     * Get audit statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'];
        }
        
                  $sql = "SELECT 
                      entity as category,
                      COUNT(*) as count,
                      COUNT(DISTINCT actor_id) as unique_users,
                      MIN(created_at) as first_log,
                      MAX(created_at) as last_log
                  FROM " . self::AUDIT_TABLE . " 
                  WHERE " . implode(' AND ', $where) . " 
                  GROUP BY entity 
                  ORDER BY count DESC";
          
          return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get user activity summary
     */
          public function getUserActivity(int $userId, int $days = 30): array
      {
          $cutoffDate = date('Y-m-d', strtotime("-$days days"));
          $sql = "SELECT 
                      DATE(created_at) as date,
                      entity as category,
                      COUNT(*) as count
                  FROM " . self::AUDIT_TABLE . " 
                  WHERE actor_id = ? 
                  AND date(created_at) >= ?
                  GROUP BY date, entity 
                  ORDER BY date DESC, entity";
          
          return $this->db->fetchAll($sql, [$userId, $cutoffDate]);
      }
    
    /**
     * Export audit logs for compliance
     */
    public function exportLogs(array $filters = [], string $format = 'csv'): string
    {
        $logs = $this->getLogs($filters, 10000, 0);
        
        if ($format === 'csv') {
            return $this->exportToCsv($logs);
        } elseif ($format === 'json') {
            return json_encode($logs, JSON_PRETTY_PRINT);
        }
        
        throw new InvalidArgumentException('Unsupported export format');
    }
    
    /**
     * Clean up old audit logs (GDPR compliance)
     */
          public function cleanupOldLogs(): int
      {
          $cutoffDate = date('Y-m-d H:i:s', strtotime("-" . self::RETENTION_DAYS . " days"));
          $sql = "DELETE FROM " . self::AUDIT_TABLE . " 
                  WHERE created_at < ?";
          
          $result = $this->db->query($sql, [$cutoffDate]);
          return $result->rowCount();
      }
    
    /**
     * Anonymize user data for GDPR compliance
     */
    public function anonymizeUserData(int $userId): bool
    {
        try {
            $this->db->beginTransaction();
            
                          // Anonymize audit logs - only set actor_id to NULL for activity_log table
              $this->db->query(
                  "UPDATE " . self::AUDIT_TABLE . " 
                   SET actor_id = NULL
                   WHERE actor_id = ?",
                  [$userId]
              );
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function getRoleSummary(array $filters = []): array
    {
        [$dateFrom, $dateTo] = $this->normalizeDateFilters($filters);

        return $this->db->fetchAll(
            "SELECT 
                COALESCE(actor_role, 'Tanımsız') AS role,
                COUNT(*) AS total_events,
                COUNT(DISTINCT actor_id) AS unique_users,
                MIN(created_at) AS first_seen,
                MAX(created_at) AS last_seen
             FROM " . self::AUDIT_TABLE . "
             WHERE created_at BETWEEN ? AND ?
             GROUP BY actor_role
             ORDER BY total_events DESC",
            [$dateFrom, $dateTo]
        );
    }

    public function getRoleActionMatrix(array $filters = [], int $limit = 25): array
    {
        [$dateFrom, $dateTo] = $this->normalizeDateFilters($filters);

        return $this->db->fetchAll(
            "SELECT 
                COALESCE(actor_role, 'Tanımsız') AS role,
                action,
                COUNT(*) AS total_events
             FROM " . self::AUDIT_TABLE . "
             WHERE created_at BETWEEN ? AND ?
             GROUP BY actor_role, action
             ORDER BY total_events DESC
             LIMIT ?",
            [$dateFrom, $dateTo, $limit]
        );
    }

    public function getRoleAnomalies(array $filters = [], int $limit = 10): array
    {
        [$dateFrom, $dateTo] = $this->normalizeDateFilters($filters);

        $rows = $this->db->fetchAll(
            "SELECT 
                COALESCE(actor_role, 'Tanımsız') AS role,
                action,
                entity,
                COUNT(*) AS total_events,
                MIN(created_at) AS first_seen,
                MAX(created_at) AS last_seen
             FROM " . self::AUDIT_TABLE . "
             WHERE created_at BETWEEN ? AND ?
             GROUP BY actor_role, action, entity
             ORDER BY total_events DESC
             LIMIT 200",
            [$dateFrom, $dateTo]
        );

        $anomalies = [];
        foreach ($rows as $row) {
            $keyword = $this->detectSensitiveKeyword($row['action'] ?? '', $row['entity'] ?? '');
            if (!$keyword) {
                continue;
            }

            $allowedRoles = $this->keywordRoleWhitelist()[$keyword] ?? [];
            $role = $row['role'] ?? 'Tanımsız';
            if (!in_array($role, $allowedRoles, true)) {
                $row['keyword'] = $keyword;
                $row['allowed_roles'] = $allowedRoles;
                $anomalies[] = $row;
                if (count($anomalies) >= $limit) {
                    break;
                }
            }
        }

        return $anomalies;
    }

    public function exportRoleSummary(array $filters = [], string $format = 'csv'): string
    {
        $summary = $this->getRoleSummary($filters);

        if ($format === 'json') {
            return json_encode($summary, JSON_PRETTY_PRINT);
        }

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Rol', 'Toplam Olay', 'Benzersiz Kullanıcı', 'İlk Aktivite', 'Son Aktivite']);
        foreach ($summary as $row) {
            fputcsv($handle, [
                $row['role'],
                $row['total_events'],
                $row['unique_users'],
                $row['first_seen'],
                $row['last_seen'],
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }
    
    private function normalizeDateFilters(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $filters['date_to'] ?? date('Y-m-d');

        $from = date('Y-m-d 00:00:00', strtotime($dateFrom));
        $to = date('Y-m-d 23:59:59', strtotime($dateTo));

        return [$from, $to];
    }

    private function detectSensitiveKeyword(?string $action, ?string $entity): ?string
    {
        $haystack = strtolower(trim(($action ?? '') . ' ' . ($entity ?? '')));
        foreach ($this->keywordRoleWhitelist() as $keyword => $roles) {
            if (strpos($haystack, $keyword) !== false) {
                return $keyword;
            }
        }
        return null;
    }

    private function keywordRoleWhitelist(): array
    {
        return [
            'finance' => ['FINANCE', 'ADMIN', 'SUPERADMIN'],
            'fee' => ['FINANCE', 'ADMIN', 'SITE_MANAGER'],
            'invoice' => ['FINANCE', 'ADMIN'],
            'payment' => ['FINANCE', 'ADMIN'],
            'auth' => ['ADMIN', 'SUPERADMIN', 'SUPPORT'],
            'resident' => ['ADMIN', 'SITE_MANAGER', 'SUPPORT'],
            'portal' => ['ADMIN', 'SITE_MANAGER', 'SUPPORT'],
        ];
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Sanitize metadata to remove sensitive information
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];
        
        foreach ($metadata as $key => $value) {
            if (in_array(strtolower($key), self::SENSITIVE_FIELDS)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeMetadata($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize data arrays
     */
    private function sanitizeData(array $data): array
    {
        return $this->sanitizeMetadata($data);
    }
    
    /**
     * Calculate changes between before and after data
     */
    private function calculateChanges(array $before, array $after): array
    {
        $changes = [];
        
        foreach ($after as $key => $value) {
            if (!isset($before[$key]) || $before[$key] !== $value) {
                $changes[$key] = [
                    'from' => $before[$key] ?? null,
                    'to' => $value
                ];
            }
        }
        
        // Check for deleted fields
        foreach ($before as $key => $value) {
            if (!isset($after[$key])) {
                $changes[$key] = [
                    'from' => $value,
                    'to' => null
                ];
            }
        }
        
        return $changes;
    }
    
    /**
     * Export logs to CSV format
     * ROUND 3: Enhanced with IP address and company_id columns
     */
    private function exportToCsv(array $logs): string
    {
        $output = fopen('php://temp', 'r+');
        
        // CSV headers (ROUND 3: Added IP Address and Company ID)
        fputcsv($output, [
            'ID', 'Category (Entity)', 'Action', 'User ID', 'Username', 'IP Address', 'Company ID', 'Metadata', 'Created At'
        ]);
        
        // CSV data
        foreach ($logs as $log) {
            // Extract additional info from meta_json if available
            $metadata = json_decode($log['meta_json'] ?? '{}', true);
            
            fputcsv($output, [
                $log['id'],
                $log['entity'] ?? '',
                $log['action'] ?? '',
                $log['actor_id'] ?? '',
                $log['username'] ?? '',
                $log['ip_address'] ?? '', // ROUND 3: IP address column
                $log['company_id'] ?? '', // ROUND 3: Company ID column
                $log['meta_json'] ?? '',
                $log['created_at'] ?? ''
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Fallback file logging
     */
    private function logToFile(string $category, string $action, ?int $userId, array $metadata): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'category' => $category,
            'action' => $action,
            'user_id' => $userId,
            'ip' => $this->getClientIp(),
            'metadata' => $metadata
        ];
        
        $logFile = __DIR__ . '/../../logs/audit_fallback.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get audit log summary for dashboard
     */
    public function getDashboardSummary(): array
    {
        $today = date('Y-m-d H:i:s', strtotime('today'));
        
        $sql = "SELECT 
                    COUNT(*) as total_logs,
                    COUNT(DISTINCT actor_id) as active_users,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as logs_today,
                    COUNT(CASE WHEN entity = 'SECURITY' AND created_at >= ? THEN 1 END) as security_events_today
                FROM " . self::AUDIT_TABLE;
        
        return $this->db->fetch($sql, [$today, $today]);
    }
    
    /**
     * Cleanup old audit records based on retention policy
     * ROUND 3: Retention policy skeleton
     * 
     * @param string|null $beforeDate Delete records before this date (Y-m-d H:i:s). If null, uses retention_days from config.
     * @return int Number of records deleted
     */
    public function cleanupOldRecords(?string $beforeDate = null): int
    {
        try {
            // Load security config for retention_days
            $configPath = __DIR__ . '/../../config/security.php';
            $retentionDays = 2555; // Default: 7 years
            if (file_exists($configPath)) {
                $config = require $configPath;
                $retentionDays = (int)($config['audit']['retention_days'] ?? 2555);
            }
            
            // Calculate cutoff date
            if ($beforeDate === null) {
                $beforeDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
            }
            
            // Delete old records
            $sql = "DELETE FROM " . self::AUDIT_TABLE . " WHERE created_at < ?";
            $this->db->query($sql, [$beforeDate]);
            
            $deletedCount = $this->db->lastAffectedRows();
            
            // Log the cleanup operation
            $this->logSystemConfig('AUDIT_CLEANUP', null, [
                'before_date' => $beforeDate,
                'records_deleted' => $deletedCount,
                'retention_days' => $retentionDays
            ]);
            
            return $deletedCount;
        } catch (Exception $e) {
            error_log("AuditLogger::cleanupOldRecords() error: " . $e->getMessage());
            return 0;
        }
    }
}

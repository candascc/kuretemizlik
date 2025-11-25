<?php
/**
 * Security Analytics Service
 * 
 * Provides rule-based anomaly detection for security events.
 * Analyzes audit logs and rate limit events to detect suspicious patterns.
 * 
 * SECURITY HARDENING ROUND 2 - STAGE 3
 * 
 * @package App\Services
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/../Lib/AuditLogger.php';
require_once __DIR__ . '/../Lib/RateLimit.php';

class SecurityAnalyticsService
{
    private $auditLogger;
    private $db;
    private $config;
    
    // Anomaly detection thresholds
    private const BRUTE_FORCE_THRESHOLD = 10; // Failed login attempts in 15 minutes
    private const BRUTE_FORCE_WINDOW = 900; // 15 minutes in seconds
    private const MULTI_TENANT_ENUM_THRESHOLD = 5; // Different companies accessed from same IP in 1 hour
    private const MULTI_TENANT_ENUM_WINDOW = 3600; // 1 hour in seconds
    private const RATE_LIMIT_ABUSE_THRESHOLD = 3; // Rate limit exceeded events in 30 minutes
    private const RATE_LIMIT_ABUSE_WINDOW = 1800; // 30 minutes in seconds
    
    public function __construct()
    {
        $this->auditLogger = AuditLogger::getInstance();
        $this->db = Database::getInstance();
        $this->config = $this->loadSecurityConfig();
    }
    
    /**
     * Load security configuration
     * ROUND 3: Load from config/security.php
     * 
     * @return array Security configuration
     */
    private function loadSecurityConfig(): array
    {
        $configPath = __DIR__ . '/../../config/security.php';
        if (file_exists($configPath)) {
            return require $configPath;
        }
        // Fallback to default config
        return [
            'analytics' => ['enabled' => true, 'rules' => ['brute_force' => true, 'multi_tenant_enumeration' => true, 'rate_limit_abuse' => true]],
            'alerts' => ['enabled' => false, 'channels' => ['log']],
        ];
    }
    
    /**
     * Check if analytics is enabled
     * ROUND 3: Config-aware check
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config['analytics']['enabled'] ?? true;
    }
    
    /**
     * Check if a specific rule is enabled
     * ROUND 3: Rule-specific enablement check
     * 
     * @param string $rule Rule name (brute_force, multi_tenant_enumeration, rate_limit_abuse)
     * @return bool
     */
    public function isRuleEnabled(string $rule): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        return $this->config['analytics']['rules'][$rule] ?? true;
    }
    
    /**
     * Run anomaly detection analysis
     * ROUND 3: Config-aware analysis with scheduling support
     * This should be called periodically (e.g., via cron job or background task)
     * 
     * @return array Detected anomalies
     */
    public function analyze(): array
    {
        // ROUND 3: Check if analytics is enabled
        if (!$this->isEnabled()) {
            return [];
        }
        
        $anomalies = [];
        
        // 1. Detect brute force attempts (if rule enabled)
        if ($this->isRuleEnabled('brute_force')) {
            $bruteForceAnomalies = $this->detectBruteForce();
            $anomalies = array_merge($anomalies, $bruteForceAnomalies);
        }
        
        // 2. Detect multi-tenant enumeration attempts (if rule enabled)
        if ($this->isRuleEnabled('multi_tenant_enumeration')) {
            $enumAnomalies = $this->detectMultiTenantEnumeration();
            $anomalies = array_merge($anomalies, $enumAnomalies);
        }
        
        // 3. Detect rate limit abuse (if rule enabled)
        if ($this->isRuleEnabled('rate_limit_abuse')) {
            $rateLimitAnomalies = $this->detectRateLimitAbuse();
            $anomalies = array_merge($anomalies, $rateLimitAnomalies);
        }
        
        // Log detected anomalies
        foreach ($anomalies as $anomaly) {
            $this->logAnomaly($anomaly);
        }
        
        return $anomalies;
    }
    
    /**
     * Run scheduled analysis (for cron/background tasks)
     * ROUND 3: Public entry point for scheduled execution
     * 
     * @return array Summary of analysis results
     */
    public static function runScheduledAnalysis(): array
    {
        try {
            $service = new self();
            $anomalies = $service->analyze();
            
            return [
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'anomalies_detected' => count($anomalies),
                'anomalies' => $anomalies,
            ];
        } catch (Exception $e) {
            error_log("SecurityAnalyticsService::runScheduledAnalysis() error: " . $e->getMessage());
            return [
                'success' => false,
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => $e->getMessage(),
                'anomalies_detected' => 0,
            ];
        }
    }
    
    /**
     * Detect brute force login attempts
     * 
     * @return array Detected brute force anomalies
     */
    private function detectBruteForce(): array
    {
        $anomalies = [];
        $windowStart = date('Y-m-d H:i:s', time() - self::BRUTE_FORCE_WINDOW);
        
        // Get failed login attempts in the time window
        $filters = [
            'action' => 'LOGIN_FAILED',
            'date_from' => $windowStart
        ];
        
        $failedLogins = $this->auditLogger->getLogs($filters, 1000, 0);
        
        // Group by IP address
        $ipAttempts = [];
        foreach ($failedLogins as $log) {
            $ip = $this->extractIpAddress($log);
            if (!$ip || $ip === 'unknown') {
                continue;
            }
            
            if (!isset($ipAttempts[$ip])) {
                $ipAttempts[$ip] = [];
            }
            $ipAttempts[$ip][] = $log;
        }
        
        // Check for IPs exceeding threshold
        foreach ($ipAttempts as $ip => $attempts) {
            if (count($attempts) >= self::BRUTE_FORCE_THRESHOLD) {
                $anomalies[] = [
                    'type' => 'BRUTE_FORCE',
                    'severity' => 'HIGH',
                    'ip_address' => $ip,
                    'count' => count($attempts),
                    'window_seconds' => self::BRUTE_FORCE_WINDOW,
                    'threshold' => self::BRUTE_FORCE_THRESHOLD,
                    'details' => [
                        'failed_attempts' => count($attempts),
                        'time_window' => self::BRUTE_FORCE_WINDOW,
                        'usernames_attempted' => array_unique(array_column($attempts, 'username'))
                    ]
                ];
            }
        }
        
        return $anomalies;
    }
    
    /**
     * Detect multi-tenant enumeration attempts
     * (Same IP accessing multiple different companies in short time)
     * 
     * @return array Detected enumeration anomalies
     */
    private function detectMultiTenantEnumeration(): array
    {
        $anomalies = [];
        $windowStart = date('Y-m-d H:i:s', time() - self::MULTI_TENANT_ENUM_WINDOW);
        
        // Get all login attempts (successful and failed) in the time window
        $filters = [
            'action' => 'LOGIN',
            'date_from' => $windowStart
        ];
        
        $loginAttempts = $this->auditLogger->getLogs($filters, 1000, 0);
        
        // Group by IP address and collect unique company_ids
        $ipCompanies = [];
        foreach ($loginAttempts as $log) {
            $ip = $this->extractIpAddress($log);
            if (!$ip || $ip === 'unknown') {
                continue;
            }
            
            $companyId = $this->extractCompanyId($log);
            if (!$companyId) {
                continue;
            }
            
            if (!isset($ipCompanies[$ip])) {
                $ipCompanies[$ip] = [];
            }
            
            if (!in_array($companyId, $ipCompanies[$ip])) {
                $ipCompanies[$ip][] = $companyId;
            }
        }
        
        // Check for IPs accessing multiple companies
        foreach ($ipCompanies as $ip => $companyIds) {
            if (count($companyIds) >= self::MULTI_TENANT_ENUM_THRESHOLD) {
                $anomalies[] = [
                    'type' => 'MULTI_TENANT_ENUMERATION',
                    'severity' => 'MEDIUM',
                    'ip_address' => $ip,
                    'count' => count($companyIds),
                    'window_seconds' => self::MULTI_TENANT_ENUM_WINDOW,
                    'threshold' => self::MULTI_TENANT_ENUM_THRESHOLD,
                    'details' => [
                        'companies_accessed' => $companyIds,
                        'time_window' => self::MULTI_TENANT_ENUM_WINDOW
                    ]
                ];
            }
        }
        
        return $anomalies;
    }
    
    /**
     * Detect rate limit abuse
     * (Multiple rate limit exceeded events from same IP)
     * 
     * @return array Detected rate limit abuse anomalies
     */
    private function detectRateLimitAbuse(): array
    {
        $anomalies = [];
        $windowStart = date('Y-m-d H:i:s', time() - self::RATE_LIMIT_ABUSE_WINDOW);
        
        // Get rate limit exceeded events in the time window
        $filters = [
            'action' => 'RATE_LIMIT',
            'date_from' => $windowStart
        ];
        
        $rateLimitEvents = $this->auditLogger->getLogs($filters, 1000, 0);
        
        // Also check for LOGIN_RATE_LIMIT_EXCEEDED, PORTAL_LOGIN_RATE_LIMIT_EXCEEDED, etc.
        $additionalFilters = [
            'action' => 'RATE_LIMIT_EXCEEDED',
            'date_from' => $windowStart
        ];
        $additionalEvents = $this->auditLogger->getLogs($additionalFilters, 1000, 0);
        $rateLimitEvents = array_merge($rateLimitEvents, $additionalEvents);
        
        // Group by IP address
        $ipRateLimits = [];
        foreach ($rateLimitEvents as $event) {
            $ip = $this->extractIpAddress($event);
            if (!$ip || $ip === 'unknown') {
                continue;
            }
            
            if (!isset($ipRateLimits[$ip])) {
                $ipRateLimits[$ip] = [];
            }
            $ipRateLimits[$ip][] = $event;
        }
        
        // Check for IPs exceeding threshold
        foreach ($ipRateLimits as $ip => $events) {
            if (count($events) >= self::RATE_LIMIT_ABUSE_THRESHOLD) {
                $anomalies[] = [
                    'type' => 'RATE_LIMIT_ABUSE',
                    'severity' => 'MEDIUM',
                    'ip_address' => $ip,
                    'count' => count($events),
                    'window_seconds' => self::RATE_LIMIT_ABUSE_WINDOW,
                    'threshold' => self::RATE_LIMIT_ABUSE_THRESHOLD,
                    'details' => [
                        'rate_limit_events' => count($events),
                        'time_window' => self::RATE_LIMIT_ABUSE_WINDOW,
                        'endpoints_affected' => array_unique(array_column($events, 'action'))
                    ]
                ];
            }
        }
        
        return $anomalies;
    }
    
    /**
     * Log detected anomaly to audit log
     * 
     * @param array $anomaly Anomaly data
     * @return void
     */
    private function logAnomaly(array $anomaly): void
    {
        try {
            // Log to audit log
            $this->auditLogger->logSecurity(
                'SECURITY_ANOMALY_DETECTED',
                null,
                [
                    'anomaly_type' => $anomaly['type'],
                    'severity' => $anomaly['severity'],
                    'ip_address' => $anomaly['ip_address'],
                    'count' => $anomaly['count'],
                    'threshold' => $anomaly['threshold'],
                    'window_seconds' => $anomaly['window_seconds'],
                    'details' => $anomaly['details'] ?? []
                ]
            );
            
            // ROUND 3: Trigger alerting (if enabled)
            if (class_exists('SecurityAlertService')) {
                try {
                    SecurityAlertService::notifyAnomaly($anomaly);
                } catch (Exception $e) {
                    error_log("SecurityAnalyticsService: Failed to trigger alert: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("SecurityAnalyticsService: Failed to log anomaly: " . $e->getMessage());
        }
    }
    
    /**
     * Extract IP address from log entry
     * 
     * @param array $log Log entry
     * @return string|null IP address
     */
    private function extractIpAddress(array $log): ?string
    {
        // Try direct column first
        if (!empty($log['ip_address'])) {
            return $log['ip_address'];
        }
        
        // Try metadata JSON
        if (!empty($log['meta_json'])) {
            $meta = json_decode($log['meta_json'], true);
            if (isset($meta['ip_address'])) {
                return $meta['ip_address'];
            }
        }
        
        return null;
    }
    
    /**
     * Extract company_id from log entry
     * 
     * @param array $log Log entry
     * @return int|null Company ID
     */
    private function extractCompanyId(array $log): ?int
    {
        // Try direct column first
        if (!empty($log['company_id'])) {
            return (int)$log['company_id'];
        }
        
        // Try metadata JSON
        if (!empty($log['meta_json'])) {
            $meta = json_decode($log['meta_json'], true);
            if (isset($meta['company_id'])) {
                return (int)$meta['company_id'];
            }
        }
        
        return null;
    }
}


<?php
/**
 * Security Alert Service
 * 
 * Provides alerting functionality for security anomalies.
 * Supports multiple channels: log, email, webhook.
 * 
 * ROUND 3 - STAGE 2: Alerting Skeleton
 * 
 * @package App\Services
 * @author System
 * @version 1.0
 */

class SecurityAlertService
{
    private static $config = null;
    private static $throttleCache = [];
    private static $throttleFile = null;
    
    /**
     * Load security configuration
     * 
     * @return array Security configuration
     */
    private static function loadConfig(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }
        
        $configPath = __DIR__ . '/../../config/security.php';
        if (file_exists($configPath)) {
            self::$config = require $configPath;
        } else {
            // Fallback to default config
            self::$config = [
                'alerts' => [
                    'enabled' => false,
                    'channels' => ['log'],
                    'email' => ['to' => '', 'from' => 'security@kuretemizlik.com'],
                    'webhook' => ['url' => '', 'secret' => '', 'timeout' => 5],
                    'throttle' => ['max_per_minute' => 10, 'memory_backend' => 'file'],
                ],
            ];
        }
        
        return self::$config;
    }
    
    /**
     * Get throttle file path
     * 
     * @return string
     */
    private static function getThrottleFile(): string
    {
        if (self::$throttleFile === null) {
            $cacheDir = __DIR__ . '/../../storage/cache';
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            self::$throttleFile = $cacheDir . '/security_alerts_throttle.json';
        }
        return self::$throttleFile;
    }
    
    /**
     * Check if alert should be throttled
     * ROUND 4: Throttling implementation
     * 
     * @param string $eventType Event type (e.g., 'brute_force', 'anomaly')
     * @param string $key Unique key (e.g., IP address)
     * @return bool True if throttled (should not send), false if allowed
     */
    private static function isThrottled(string $eventType, string $key): bool
    {
        $config = self::loadConfig();
        $throttleConfig = $config['alerts']['throttle'] ?? [];
        $maxPerMinute = $throttleConfig['max_per_minute'] ?? 10;
        $backend = $throttleConfig['memory_backend'] ?? 'file';
        
        $throttleKey = $eventType . ':' . $key;
        $currentMinute = (int)(time() / 60);
        
        if ($backend === 'file') {
            // File-based throttling
            $throttleFile = self::getThrottleFile();
            $throttleData = [];
            
            if (file_exists($throttleFile)) {
                try {
                    $content = file_get_contents($throttleFile);
                    if ($content !== false && $content !== '') {
                        $throttleData = json_decode($content, true) ?: [];
                    }
                } catch (Exception $e) {
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("SecurityAlertService: Failed to read throttle file: " . $e->getMessage());
                    }
                    $throttleData = [];
                } catch (Throwable $e) {
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("SecurityAlertService: Failed to read throttle file: " . $e->getMessage());
                    }
                    $throttleData = [];
                }
            }
            
            // Clean old entries (older than current minute)
            foreach ($throttleData as $k => $data) {
                if (($data['minute'] ?? 0) < $currentMinute) {
                    unset($throttleData[$k]);
                }
            }
            
            // Check current count
            if (isset($throttleData[$throttleKey])) {
                $count = $throttleData[$throttleKey]['count'] ?? 0;
                if ($count >= $maxPerMinute) {
                    return true; // Throttled
                }
                $throttleData[$throttleKey]['count'] = $count + 1;
            } else {
                $throttleData[$throttleKey] = [
                    'minute' => $currentMinute,
                    'count' => 1
                ];
            }
            
            // Save throttle data
            @file_put_contents($throttleFile, json_encode($throttleData), LOCK_EX);
        } else {
            // In-memory throttling (for single request)
            if (!isset(self::$throttleCache[$throttleKey])) {
                self::$throttleCache[$throttleKey] = [
                    'minute' => $currentMinute,
                    'count' => 0
                ];
            }
            
            $cache = self::$throttleCache[$throttleKey];
            if ($cache['minute'] < $currentMinute) {
                // Reset for new minute
                self::$throttleCache[$throttleKey] = [
                    'minute' => $currentMinute,
                    'count' => 1
                ];
                return false; // Not throttled
            }
            
            if ($cache['count'] >= $maxPerMinute) {
                return true; // Throttled
            }
            
            self::$throttleCache[$throttleKey]['count']++;
        }
        
        return false; // Not throttled
    }
    
    /**
     * Check if alerting is enabled
     * 
     * @return bool
     */
    public static function isEnabled(): bool
    {
        $config = self::loadConfig();
        return $config['alerts']['enabled'] ?? false;
    }
    
    /**
     * Notify about a security anomaly
     * ROUND 4: Real alerting with throttling
     * 
     * @param array $anomaly Anomaly data from SecurityAnalyticsService
     * @return void
     */
    public static function notifyAnomaly(array $anomaly): void
    {
        // Always log (non-blocking)
        self::logAnomaly($anomaly);
        
        if (!self::isEnabled()) {
            return; // Alerting disabled, only log
        }
        
        // Check throttling
        $eventType = $anomaly['type'] ?? 'unknown';
        $key = $anomaly['ip_address'] ?? $anomaly['user_id'] ?? 'global';
        
        if (self::isThrottled($eventType, $key)) {
            // Throttled - don't send external alerts, but still log
            error_log(sprintf(
                "[SECURITY ALERT THROTTLED] Event type: %s, Key: %s (max per minute reached)",
                $eventType,
                $key
            ));
            return;
        }
        
        $config = self::loadConfig();
        $channels = $config['alerts']['channels'] ?? ['log'];
        
        foreach ($channels as $channel) {
            try {
                switch ($channel) {
                    case 'log':
                        // Already logged above
                        break;
                    case 'email':
                        self::sendEmailAlert($anomaly, $config);
                        break;
                    case 'webhook':
                        self::sendWebhookAlert($anomaly, $config);
                        break;
                }
            } catch (Exception $e) {
                // Non-blocking: log error but don't fail
                error_log("SecurityAlertService: Failed to send alert via {$channel}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Notify about a critical error
     * ROUND 4: Integration with AppErrorHandler
     * 
     * @param array $errorData Error data (exception, context, etc.)
     * @return void
     */
    public static function notifyCriticalError(array $errorData): void
    {
        if (!self::isEnabled()) {
            return; // Alerting disabled
        }
        
        // Check throttling (use 'critical_error' as event type)
        $key = $errorData['ip_address'] ?? $errorData['user_id'] ?? 'global';
        if (self::isThrottled('critical_error', $key)) {
            return; // Throttled
        }
        
        $config = self::loadConfig();
        $channels = $config['alerts']['channels'] ?? ['log'];
        
        $alertData = [
            'type' => 'CRITICAL_ERROR',
            'severity' => 'CRITICAL',
            'message' => $errorData['message'] ?? 'Unknown error',
            'file' => $errorData['file'] ?? 'unknown',
            'line' => $errorData['line'] ?? 0,
            'ip_address' => $errorData['ip_address'] ?? 'unknown',
            'user_id' => $errorData['user_id'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        foreach ($channels as $channel) {
            try {
                switch ($channel) {
                    case 'log':
                        error_log(sprintf(
                            "[SECURITY ALERT] CRITICAL ERROR: %s in %s:%d (IP: %s)",
                            $alertData['message'],
                            $alertData['file'],
                            $alertData['line'],
                            $alertData['ip_address']
                        ));
                        break;
                    case 'email':
                        self::sendEmailAlert($alertData, $config);
                        break;
                    case 'webhook':
                        self::sendWebhookAlert($alertData, $config);
                        break;
                }
            } catch (Exception $e) {
                error_log("SecurityAlertService: Failed to send critical error alert via {$channel}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Log anomaly to error log
     * 
     * @param array $anomaly Anomaly data
     * @return void
     */
    private static function logAnomaly(array $anomaly): void
    {
        $message = sprintf(
            "[SECURITY ALERT] %s detected: IP=%s, Count=%d, Severity=%s, Type=%s",
            $anomaly['type'] ?? 'UNKNOWN',
            $anomaly['ip_address'] ?? 'unknown',
            $anomaly['count'] ?? 0,
            $anomaly['severity'] ?? 'UNKNOWN',
            $anomaly['type'] ?? 'UNKNOWN'
        );
        
        error_log($message);
    }
    
    /**
     * Send email alert
     * ROUND 4: Real email sending implementation
     * 
     * @param array $anomaly Anomaly data
     * @param array $config Security configuration
     * @return void
     */
    private static function sendEmailAlert(array $anomaly, array $config): void
    {
        $emailTo = $config['alerts']['email']['to'] ?? '';
        if (empty($emailTo)) {
            return; // No email recipient configured
        }
        
        $emailFrom = $config['alerts']['email']['from'] ?? 'security@kuretemizlik.com';
        
        // Prepare email subject and body
        $subject = sprintf(
            '[SECURITY ALERT] %s - %s',
            $anomaly['type'] ?? 'UNKNOWN',
            date('Y-m-d H:i:s')
        );
        
        $body = self::formatEmailBody($anomaly);
        
        // Try to use PHP mail() function (non-blocking)
        try {
            $headers = [
                'From: ' . $emailFrom,
                'Reply-To: ' . $emailFrom,
                'X-Mailer: PHP/' . phpversion(),
                'Content-Type: text/html; charset=UTF-8',
            ];
            
            $result = @mail($emailTo, $subject, $body, implode("\r\n", $headers));
            
            if (!$result) {
                error_log("SecurityAlertService: Failed to send email alert to {$emailTo}");
            }
        } catch (Exception $e) {
            error_log("SecurityAlertService: Email alert exception: " . $e->getMessage());
        }
    }
    
    /**
     * Format email body for security alert
     * 
     * @param array $anomaly Anomaly data
     * @return string HTML email body
     */
    private static function formatEmailBody(array $anomaly): string
    {
        $type = $anomaly['type'] ?? 'UNKNOWN';
        $severity = $anomaly['severity'] ?? 'UNKNOWN';
        $ipAddress = $anomaly['ip_address'] ?? 'unknown';
        $count = $anomaly['count'] ?? 0;
        $message = $anomaly['message'] ?? '';
        $file = $anomaly['file'] ?? null;
        $line = $anomaly['line'] ?? null;
        $timestamp = $anomaly['timestamp'] ?? date('Y-m-d H:i:s');
        
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h2>Security Alert</h2>';
        $html .= '<table style="border-collapse: collapse; width: 100%;">';
        $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Type:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($type) . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Severity:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($severity) . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>IP Address:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($ipAddress) . '</td></tr>';
        
        if ($count > 0) {
            $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Count:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars((string)$count) . '</td></tr>';
        }
        
        if (!empty($message)) {
            $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Message:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($message) . '</td></tr>';
        }
        
        if ($file) {
            $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>File:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($file) . ($line ? ':' . $line : '') . '</td></tr>';
        }
        
        $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Timestamp:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($timestamp) . '</td></tr>';
        $html .= '</table>';
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Send webhook alert
     * ROUND 4: Real webhook implementation
     * 
     * @param array $anomaly Anomaly data
     * @param array $config Security configuration
     * @return void
     */
    private static function sendWebhookAlert(array $anomaly, array $config): void
    {
        $webhookUrl = $config['alerts']['webhook']['url'] ?? '';
        if (empty($webhookUrl)) {
            return; // No webhook URL configured
        }
        
        $webhookSecret = $config['alerts']['webhook']['secret'] ?? '';
        $timeout = $config['alerts']['webhook']['timeout'] ?? 5;
        
        // Prepare webhook payload
        $payload = [
            'type' => 'security_alert',
            'event' => $anomaly['type'] ?? 'UNKNOWN',
            'severity' => $anomaly['severity'] ?? 'UNKNOWN',
            'data' => $anomaly,
            'timestamp' => date('c'), // ISO 8601
        ];
        
        // Add signature if secret is provided
        if (!empty($webhookSecret)) {
            $payload['signature'] = hash_hmac('sha256', json_encode($payload), $webhookSecret);
        }
        
        // Send webhook using curl (non-blocking)
        try {
            $ch = curl_init($webhookUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: SecurityAlertService/1.0',
                ],
            ]);
            
            $response = @curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("SecurityAlertService: Webhook curl error: {$error}");
            } elseif ($httpCode < 200 || $httpCode >= 300) {
                error_log("SecurityAlertService: Webhook returned HTTP {$httpCode}");
            }
        } catch (Exception $e) {
            error_log("SecurityAlertService: Webhook alert exception: " . $e->getMessage());
        }
    }
}


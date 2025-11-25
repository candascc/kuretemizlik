<?php
/**
 * Generic Webhook Error Sink
 * 
 * ROUND 5 - STAGE 1: Generic webhook endpoint for custom monitoring systems
 * 
 * This implementation sends error data to any HTTP endpoint via POST request.
 * Supports HMAC-SHA256 signature for authentication.
 * 
 * @package App\Services
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/ErrorSinkInterface.php';

class GenericWebhookErrorSink implements ErrorSinkInterface
{
    private $endpoint;
    private $enabled;
    private $timeout;
    private $secret;
    
    /**
     * Constructor
     * 
     * @param array $config Configuration array with 'endpoint', 'timeout', 'secret'
     */
    public function __construct(array $config)
    {
        $this->endpoint = $config['endpoint'] ?? $config['dsn'] ?? '';
        $this->enabled = !empty($this->endpoint);
        $this->timeout = (int)($config['timeout'] ?? 2);
        $this->secret = $config['secret'] ?? '';
    }
    
    /**
     * Check if sink is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
    
    /**
     * Send error data to webhook endpoint
     * 
     * @param array $payload Structured error data
     * @return void
     * @throws Exception If sending fails
     */
    public function send(array $payload): void
    {
        if (!$this->isEnabled()) {
            return;
        }
        
        // Prepare payload
        $webhookPayload = [
            'type' => 'error',
            'timestamp' => $payload['timestamp'] ?? date('c'),
            'level' => $payload['level'] ?? 'error',
            'data' => $payload,
        ];
        
        // Add signature if secret is provided
        if (!empty($this->secret)) {
            $webhookPayload['signature'] = hash_hmac('sha256', json_encode($webhookPayload), $this->secret);
            $webhookPayload['signature_algorithm'] = 'hmac-sha256';
        }
        
        // Send HTTP POST request
        $this->sendWebhook($webhookPayload);
    }
    
    /**
     * Send webhook POST request
     * 
     * @param array $payload Webhook payload
     * @return void
     * @throws Exception If sending fails
     */
    private function sendWebhook(array $payload): void
    {
        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: AppErrorHandler/1.0',
            ],
        ]);
        
        $response = @curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Webhook curl error: {$error}");
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("Webhook returned HTTP {$httpCode}");
        }
    }
}


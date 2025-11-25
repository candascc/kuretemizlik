<?php
/**
 * Sentry Error Sink
 * 
 * ROUND 5 - STAGE 1: Sentry integration via HTTP POST (SDK-free)
 * 
 * This implementation sends error data to Sentry's ingestion endpoint
 * without requiring the Sentry SDK. It uses Sentry's Envelope API format.
 * 
 * @package App\Services
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/ErrorSinkInterface.php';

class SentryErrorSink implements ErrorSinkInterface
{
    private $dsn;
    private $enabled;
    private $timeout;
    private $environment;
    
    /**
     * Constructor
     * 
     * @param array $config Configuration array with 'dsn', 'timeout', 'environment'
     */
    public function __construct(array $config)
    {
        $this->dsn = $config['dsn'] ?? '';
        $this->enabled = !empty($this->dsn);
        $this->timeout = (int)($config['timeout'] ?? 2);
        $this->environment = $config['environment'] ?? ($_ENV['APP_ENV'] ?? 'production');
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
     * Send error data to Sentry
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
        
        // Parse Sentry DSN
        $dsnParts = $this->parseDsn($this->dsn);
        if (!$dsnParts) {
            throw new Exception('Invalid Sentry DSN');
        }
        
        // Build Sentry envelope (Sentry's ingestion format)
        $envelope = $this->buildEnvelope($payload, $dsnParts);
        
        // Send to Sentry ingestion endpoint
        $this->sendToSentry($dsnParts['host'], $dsnParts['project_id'], $envelope);
    }
    
    /**
     * Parse Sentry DSN
     * Format: https://{key}@{host}/{project_id}
     * 
     * @param string $dsn Sentry DSN
     * @return array|null Parsed DSN parts or null if invalid
     */
    private function parseDsn(string $dsn): ?array
    {
        if (empty($dsn)) {
            return null;
        }
        
        // Parse DSN: https://{public_key}@{host}/{project_id}
        if (preg_match('#^https://([^@]+)@([^/]+)/(\d+)$#', $dsn, $matches)) {
            return [
                'public_key' => $matches[1],
                'host' => $matches[2],
                'project_id' => $matches[3],
            ];
        }
        
        return null;
    }
    
    /**
     * Build Sentry envelope
     * Sentry Envelope format: https://develop.sentry.dev/sdk/envelopes/
     * 
     * @param array $payload Error payload
     * @param array $dsnParts Parsed DSN parts
     * @return string Envelope string
     */
    private function buildEnvelope(array $payload, array $dsnParts): string
    {
        // Build event payload (Sentry event format)
        $event = [
            'event_id' => $this->generateEventId(),
            'timestamp' => $payload['timestamp'] ?? date('c'),
            'level' => $this->mapErrorLevel($payload['level'] ?? 'error'),
            'platform' => 'php',
            'environment' => $this->environment,
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'release' => defined('APP_VERSION') ? APP_VERSION : null,
            'exception' => $this->buildExceptionData($payload),
            'request' => $this->buildRequestData($payload),
            'user' => $this->buildUserData($payload),
            'tags' => [
                'request_id' => $payload['request_id'] ?? null,
            ],
            'extra' => $payload['context'] ?? [],
        ];
        
        // Remove null values
        $event = array_filter($event, function($value) {
            return $value !== null;
        });
        
        // Build envelope header
        $envelopeHeader = json_encode([
            'event_id' => $event['event_id'],
            'sent_at' => date('c'),
            'sdk' => [
                'name' => 'sentry.php.custom',
                'version' => '1.0.0',
            ],
        ]);
        
        // Build item header (event type)
        $itemHeader = json_encode([
            'type' => 'event',
            'content_type' => 'application/json',
        ]);
        
        // Combine envelope parts
        $envelope = $envelopeHeader . "\n";
        $envelope .= $itemHeader . "\n";
        $envelope .= json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        return $envelope;
    }
    
    /**
     * Build exception data from payload
     * 
     * @param array $payload Error payload
     * @return array Exception data
     */
    private function buildExceptionData(array $payload): array
    {
        $exception = $payload['exception'] ?? [];
        
        if (empty($exception)) {
            return [];
        }
        
        return [
            'values' => [
                [
                    'type' => $exception['class'] ?? 'Exception',
                    'value' => $exception['message'] ?? 'Unknown error',
                    'stacktrace' => [
                        'frames' => $this->parseStackTrace($exception['trace'] ?? ''),
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Parse stack trace string into frames
     * 
     * @param string $trace Stack trace string
     * @return array Stack frames
     */
    private function parseStackTrace(string $trace): array
    {
        if (empty($trace)) {
            return [];
        }
        
        $lines = explode("\n", $trace);
        $frames = [];
        
        foreach ($lines as $line) {
            if (preg_match('/#\d+\s+(.+?):\s+(.+?)\s+\((\d+)\)/', $line, $matches)) {
                $frames[] = [
                    'function' => $matches[2] ?? 'unknown',
                    'filename' => $matches[1] ?? 'unknown',
                    'lineno' => (int)($matches[3] ?? 0),
                ];
            }
        }
        
        // Reverse frames (Sentry expects oldest first)
        return array_reverse($frames);
    }
    
    /**
     * Build request data from payload
     * 
     * @param array $payload Error payload
     * @return array Request data
     */
    private function buildRequestData(array $payload): array
    {
        $request = $payload['request'] ?? [];
        
        return [
            'method' => $request['method'] ?? 'GET',
            'url' => $request['uri'] ?? '/',
            'headers' => [
                'User-Agent' => $request['user_agent'] ?? 'unknown',
            ],
            'env' => [
                'REMOTE_ADDR' => $request['ip'] ?? 'unknown',
            ],
        ];
    }
    
    /**
     * Build user data from payload
     * 
     * @param array $payload Error payload
     * @return array|null User data or null
     */
    private function buildUserData(array $payload): ?array
    {
        $user = $payload['user'] ?? null;
        
        if (empty($user)) {
            return null;
        }
        
        return [
            'id' => $user['id'] ?? null,
            'username' => $user['username'] ?? null,
        ];
    }
    
    /**
     * Map error level to Sentry level
     * 
     * @param string $level Error level
     * @return string Sentry level
     */
    private function mapErrorLevel(string $level): string
    {
        $mapping = [
            'CRITICAL' => 'fatal',
            'ERROR' => 'error',
            'WARNING' => 'warning',
            'INFO' => 'info',
            'DEBUG' => 'debug',
        ];
        
        return $mapping[strtoupper($level)] ?? 'error';
    }
    
    /**
     * Generate unique event ID
     * 
     * @return string Event ID (32 hex characters)
     */
    private function generateEventId(): string
    {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Send envelope to Sentry ingestion endpoint
     * 
     * @param string $host Sentry host
     * @param string $projectId Project ID
     * @param string $envelope Envelope string
     * @return void
     * @throws Exception If sending fails
     */
    private function sendToSentry(string $host, string $projectId, string $envelope): void
    {
        $url = "https://{$host}/api/{$projectId}/envelope/";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $envelope,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-sentry-envelope',
                'X-Sentry-Auth: Sentry sentry_version=7, sentry_client=sentry.php.custom/1.0.0',
            ],
        ]);
        
        $response = @curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Sentry curl error: {$error}");
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("Sentry returned HTTP {$httpCode}");
        }
    }
}


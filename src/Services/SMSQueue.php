<?php

/**
 * SMS Queue Service
 * SMS kuyruğu yönetimi
 */
class SMSQueue
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Add SMS to queue
     */
    public function add($smsData)
    {
        $data = [
            'to_phone' => $smsData['to'],
            'message' => $smsData['message'],
            'data' => json_encode($smsData['data'] ?? []),
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
            'scheduled_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert('sms_queue', $data);
    }

    /**
     * Process SMS queue
     */
    public function process($limit = 10)
    {
        $smses = $this->db->fetchAll(
            "SELECT * FROM sms_queue 
             WHERE status = 'pending' 
             AND scheduled_at <= datetime('now', 'localtime') 
             AND attempts < max_attempts 
             ORDER BY created_at ASC 
             LIMIT ?",
            [$limit]
        );

        $processed = 0;
        foreach ($smses as $sms) {
            if ($this->sendSMS($sms)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Send individual SMS
     */
    private function sendSMS($sms)
    {
        try {
            // Update attempt count
            $this->db->update('sms_queue', [
                'attempts' => $sms['attempts'] + 1,
                'last_attempt_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$sms['id']]);

            // Send SMS using external service (implement based on provider)
            $result = $this->sendViaProvider($sms);
            $success = is_array($result) ? (bool)($result['success'] ?? false) : (bool)$result;
            $errorMessage = is_array($result) ? ($result['error'] ?? null) : null;
            $errorContext = is_array($result) ? ($result['details'] ?? []) : [];

            if ($success) {
                $this->db->update('sms_queue', [
                    'status' => 'sent',
                    'sent_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$sms['id']]);
            } else {
                $meta = [];
                if (!empty($sms['data'])) {
                    $decoded = json_decode($sms['data'], true);
                    if (is_array($decoded)) {
                        $meta = $decoded;
                    }
                }
                $meta['last_error'] = [
                    'message' => $errorMessage ?? 'SMS provider failed',
                    'details' => $errorContext,
                    'occurred_at' => date('Y-m-d H:i:s'),
                ];

                $this->db->update('sms_queue', [
                    'status' => 'failed',
                    'error_message' => $errorMessage ?? 'SMS provider failed',
                    'data' => json_encode($meta),
                ], 'id = ?', [$sms['id']]);
            }

            return $success;

        } catch (Exception $e) {
            $this->db->update('sms_queue', [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ], 'id = ?', [$sms['id']]);

            error_log("SMS send error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS via external provider
     */
    private function sendViaProvider($sms)
    {
        // Check if SMS is enabled
        $smsEnabled = env('SMS_ENABLED', 'false') === 'true';
        if (!$smsEnabled) {
            Logger::info('SMS sending disabled');
            return $this->ensureResultArray($this->sendViaMock($sms));
        }
        
        $provider = env('SMS_PROVIDER', 'netgsm');
        
        switch ($provider) {
            case 'twilio':
                return $this->ensureResultArray($this->sendViaTwilio($sms));
            case 'netgsm':
                return $this->ensureResultArray($this->sendViaNetgsm($sms));
            case 'mock':
            default:
                return $this->ensureResultArray($this->sendViaMock($sms));
        }
    }

    /**
     * Normalize provider response to array format
     */
    private function ensureResultArray($result): array
    {
        if (is_array($result)) {
            return $result;
        }

        return [
            'success' => (bool)$result,
        ];
    }

    /**
     * Mock SMS sending (for development)
     */
    private function sendViaMock($sms)
    {
        // Log to file for development
        $logMessage = "SMS to {$sms['to_phone']}: {$sms['message']}";
        error_log($logMessage);
        
        // Simulate success
        return [
            'success' => true,
            'provider' => 'mock',
        ];
    }

    /**
     * Send via Twilio (example implementation)
     */
    private function sendViaTwilio($sms)
    {
        // Twilio implementation would go here
        // $client = new Twilio\Rest\Client($accountSid, $authToken);
        // $message = $client->messages->create($sms['to_phone'], [
        //     'from' => $twilioNumber,
        //     'body' => $sms['message']
        // ]);
        // return $message->sid ? true : false;
        
        return [
            'success' => false,
            'provider' => 'twilio',
            'error' => 'Twilio provider not implemented',
        ];
    }

    /**
     * Send via Netgsm with cURL
     */
    private function sendViaNetgsm($sms)
    {
        $username = env('NETGSM_USERNAME', '');
        $password = env('NETGSM_PASSWORD', '');
        $brandCode = trim(env('NETGSM_BRAND_CODE', ''));
        $senderOverride = trim(env('NETGSM_SENDER', ''));
        
        if (empty($username) || empty($password)) {
            Logger::warning('Netgsm credentials not configured, falling back to mock');
            return [
                'success' => true,
                'provider' => 'netgsm',
                'error' => null,
                'details' => ['reason' => 'Credentials missing, mock fallback'],
            ];
        }
        
        // Clean phone number (remove spaces, dashes, etc.)
        $phone = preg_replace('/[^0-9]/', '', $sms['to_phone']);
        // Add country code if missing
        if (strpos($phone, '90') !== 0) {
            $phone = '90' . $phone;
        }
        
        $url = 'https://api.netgsm.com.tr/sms/send/get';
        $msgHeader = $senderOverride !== '' ? $senderOverride : $brandCode;
        if ($msgHeader !== '') {
            $sanitizedHeader = preg_replace('/[^0-9A-Za-z]/', '', $msgHeader);
            if (preg_match('/^\+?[0-9]{10,15}$/', $msgHeader)) {
                $sanitizedHeader = ltrim(preg_replace('/\D+/', '', $msgHeader), '+');
            }
            if ($sanitizedHeader === '') {
                Logger::warning('Netgsm sender header sanitized to empty, falling back to brand code', ['original' => $msgHeader]);
                $msgHeader = $brandCode;
            } else {
                $msgHeader = $sanitizedHeader;
            }
        }

        if ($msgHeader === '') {
            Logger::warning('Netgsm sender header missing; please configure NETGSM_SENDER or NETGSM_BRAND_CODE');
        }

        $params = [
            'usercode' => $username,
            'password' => $password,
            'gsmno' => $phone,
            'message' => $sms['message'],
            'msgheader' => $msgHeader,
            'language' => 'TR',
            'tur' => 'Normal'
        ];
        
        $verifyPeer = env('NETGSM_VERIFY_SSL', 'true') !== 'false';
        $caBundle = env('NETGSM_CA_BUNDLE', '');

        $ch = curl_init($url . '?' . http_build_query($params));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => $verifyPeer ? 1 : 0,
            CURLOPT_SSL_VERIFYHOST => $verifyPeer ? 2 : 0,
            CURLOPT_SSLVERSION => defined('CURL_SSLVERSION_TLSv1_2') ? CURL_SSLVERSION_TLSv1_2 : 0,
        ]);

        if ($verifyPeer && !empty($caBundle) && file_exists($caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
        } elseif (!$verifyPeer) {
            Logger::warning('Netgsm SSL verification disabled via NETGSM_VERIFY_SSL');
        }
        
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError || $httpCode >= 400) {
            Logger::error('Netgsm API error', [
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'response' => $response
            ]);
            return [
                'success' => false,
                'provider' => 'netgsm',
                'error' => $curlError ?: "HTTP {$httpCode}",
                'details' => [
                    'http_code' => $httpCode,
                    'response' => $response,
                ],
            ];
        }
        
        // Netgsm returns job ID starting with '00' for success
        if (strpos($response, '00') === 0) {
            Logger::info('SMS sent via Netgsm', ['job_id' => $response]);
            return [
                'success' => true,
                'provider' => 'netgsm',
                'details' => [
                    'job_id' => trim($response),
                ],
            ];
        } else {
            Logger::warning('Netgsm SMS failed', ['response' => $response]);
            return [
                'success' => false,
                'provider' => 'netgsm',
                'error' => trim($response) ?: 'Unknown Netgsm response',
                'details' => [
                    'response' => $response,
                ],
            ];
        }
    }

    /**
     * Get queue statistics
     */
    public function getStats()
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
             FROM sms_queue"
        );

        return $stats ?: [
            'total' => 0,
            'pending' => 0,
            'sent' => 0,
            'failed' => 0
        ];
    }

    /**
     * Clean old SMS
     */
    public function clean($days = 30)
    {
        return $this->db->delete(
            'sms_queue',
            "status IN ('sent', 'failed') AND created_at < datetime('now', 'localtime', '-{$days} days')"
        );
    }

    /**
     * Retry failed SMS
     */
    public function retryFailed()
    {
        return $this->db->update(
            'sms_queue',
            [
                'status' => 'pending',
                'attempts' => 0,
                'scheduled_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ],
            "status = 'failed' AND attempts < max_attempts"
        );
    }
}

<?php

class ResidentOtpService
{
    public const OTP_LENGTH = 6;
    public const EXPIRY_INTERVAL = '+5 minutes';
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_GENERATE_PER_HOUR = 10;
    public const MAX_ATTEMPTS = 5;

    private Database $db;
    private EmailQueue $emailQueue;
    private SMSQueue $smsQueue;
    private ResidentUser $residentUserModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->emailQueue = new EmailQueue();
        $this->smsQueue = new SMSQueue();
        $this->residentUserModel = new ResidentUser();
    }

    /**
     * Request a new OTP token for resident login.
     *
     * @throws Exception when rate limit exceeded or contact missing
     */
    public function requestToken(array $resident, string $channel = 'sms', ?string $ipAddress = null, string $context = 'login'): array
    {
        $residentId = (int)($resident['id'] ?? 0);
        if (!$residentId) {
            throw new Exception('Geçersiz sakin kaydı');
        }

        $channel = $channel === 'sms' ? 'sms' : 'email';
        $contactInfo = $this->resolveContact($resident, $channel);
        if (!$contactInfo) {
            throw new Exception($channel === 'sms' ? 'Kayıtlı telefon numarası bulunamadı.' : 'Kayıtlı e-posta adresi bulunamadı.');
        }

        $this->assertRateLimit($resident, $channel, $context);

        $code = $this->generateOtpCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime(self::EXPIRY_INTERVAL));

        $tokenId = $this->db->insert('resident_login_tokens', [
            'resident_user_id' => $residentId,
            'token' => password_hash($code, PASSWORD_DEFAULT),
            'channel' => $channel,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'meta' => json_encode([
                'ip' => $ipAddress,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'context' => $context,
            ]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $masked = $channel === 'sms'
            ? $this->maskPhone($resident['phone'] ?? '')
            : $this->maskEmail($resident['email'] ?? '');

        $this->dispatchToken($code, $resident, $channel, $contactInfo);
        if ($channel === 'sms') {
            $this->residentUserModel->markOtpIssued($residentId, $context);
        }

        return [
            'token_id' => $tokenId,
            'channel' => $channel,
            'expires_at' => $expiresAt,
            'masked_contact' => $masked,
        ];
    }

    /**
     * Verify a submitted OTP code.
     */
    public function verifyToken(int $tokenId, string $code): array
    {
        $token = $this->db->fetch(
            'SELECT * FROM resident_login_tokens WHERE id = ?',
            [$tokenId]
        );

        if (!$token) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        if (!empty($token['consumed_at'])) {
            return ['success' => false, 'reason' => 'consumed'];
        }

        if (strtotime($token['expires_at']) < time()) {
            return ['success' => false, 'reason' => 'expired'];
        }

        if ((int)$token['attempts'] >= (int)$token['max_attempts']) {
            return ['success' => false, 'reason' => 'attempts_exceeded'];
        }

        $isValid = password_verify($code, $token['token']);
        if (!$isValid) {
            $this->db->update(
                'resident_login_tokens',
                [
                    'attempts' => (int)$token['attempts'] + 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                'id = ?',
                [$tokenId]
            );

            $this->residentUserModel->incrementOtpAttempt((int)$token['resident_user_id']);

            return [
                'success' => false,
                'reason' => 'mismatch',
                'attempts_remaining' => max(0, (int)$token['max_attempts'] - ((int)$token['attempts'] + 1)),
            ];
        }

        $this->db->update(
            'resident_login_tokens',
            [
                'consumed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'id = ?',
            [$tokenId]
        );

        $this->residentUserModel->resetOtpState((int)$token['resident_user_id']);

        return [
            'success' => true,
            'resident_user_id' => (int)$token['resident_user_id'],
        ];
    }

    /**
     * Mask email address for display.
     */
    public function maskEmail(string $email): string
    {
        if (!$email || strpos($email, '@') === false) {
            return '***@***';
        }
        [$local, $domain] = explode('@', $email, 2);
        $localMasked = mb_substr($local, 0, 1) . str_repeat('*', max(1, mb_strlen($local) - 1));
        $domainParts = explode('.', $domain);
        $domainMasked = implode('.', array_map(function ($part) {
            return mb_substr($part, 0, 1) . str_repeat('*', max(1, mb_strlen($part) - 1));
        }, $domainParts));
        return $localMasked . '@' . $domainMasked;
    }

    /**
     * Mask phone number for display.
     */
    public function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) <= 4) {
            return str_repeat('*', strlen($digits));
        }
        $suffix = substr($digits, -4);
        return '+** ' . str_repeat('*', max(0, strlen($digits) - 4)) . $suffix;
    }

    private function resolveContact(array $resident, string $channel): ?string
    {
        if ($channel === 'sms') {
            return $resident['phone'] ?? null;
        }
        return $resident['email'] ?? null;
    }

    private function assertRateLimit(array $resident, string $channel, string $context): void
    {
        $residentId = (int)($resident['id'] ?? 0);
        if (!$residentId) {
            throw new Exception('Geçersiz sakin kaydı');
        }

        $lastSent = $resident['last_otp_sent_at'] ?? null;
        $lastContext = $resident['otp_context'] ?? null;
        if ($lastSent && $lastContext === $context) {
            $secondsSinceLast = time() - strtotime($lastSent);
            if ($secondsSinceLast < self::RESEND_COOLDOWN_SECONDS) {
                throw new Exception('Lütfen yeni kod istemeden önce biraz bekleyin.');
            }
        }

        $hourCount = $this->db->fetch(
            "SELECT COUNT(*) as cnt FROM resident_login_tokens 
             WHERE resident_user_id = ? 
               AND channel = ? 
               AND created_at >= datetime('now', 'localtime', '-1 hour')",
            [$residentId, $channel]
        );
        if ((int)($hourCount['cnt'] ?? 0) >= self::MAX_GENERATE_PER_HOUR) {
            throw new Exception('Çok fazla deneme yaptınız. Lütfen daha sonra tekrar deneyin.');
        }
    }

    private function generateOtpCode(): string
    {
        $code = random_int(0, (10 ** self::OTP_LENGTH) - 1);
        return str_pad((string)$code, self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    private function dispatchToken(string $code, array $resident, string $channel, string $contact): void
    {
        if ($channel === 'sms') {
            $id = $this->smsQueue->add([
                'to' => $contact,
                'message' => "Giriş doğrulama kodunuz: {$code}. Bu kod 5 dakika geçerlidir.",
                'data' => [
                    'type' => 'resident_login',
                    'resident_id' => $resident['id'] ?? null,
                ],
            ]);
            // Process immediately for OTP delivery
            $this->smsQueue->process(1);
            return;
        }

        $message = sprintf(
            '<p>Merhaba %s,</p><p>Sakin portalına giriş için doğrulama kodunuz: <strong style="font-size:20px;">%s</strong></p><p>Bu kod 5 dakika boyunca geçerlidir. Siz talep etmediyseniz destek ekibimizle iletişime geçin.</p>',
            htmlspecialchars($resident['name'] ?? 'Kullanıcı'),
            $code
        );

        $this->emailQueue->add([
            'to' => $contact,
            'subject' => 'Giriş Doğrulama Kodunuz',
            'message' => $message,
            'template' => 'resident_login_otp',
            'data' => [
                'resident_id' => $resident['id'] ?? null,
            ],
        ]);
    }
}


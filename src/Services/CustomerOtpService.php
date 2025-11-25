<?php

class CustomerOtpService
{
    public const OTP_LENGTH = 6;
    public const EXPIRY_INTERVAL = '+5 minutes';
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_GENERATE_PER_HOUR = 10;
    public const MAX_ATTEMPTS = 5;

    private Database $db;
    private EmailQueue $emailQueue;
    private SMSQueue $smsQueue;
    private Customer $customerModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->emailQueue = new EmailQueue();
        $this->smsQueue = new SMSQueue();
        $this->customerModel = new Customer();
    }

    /**
     * Request an OTP for customer portal login.
     */
    public function requestToken(array $customer, string $channel, ?string $ipAddress = null, string $context = 'login'): array
    {
        $customerId = (int)($customer['id'] ?? 0);
        if (!$customerId) {
            throw new Exception('Geçersiz müşteri kaydı');
        }

        $channel = $channel === 'sms' ? 'sms' : 'email';
        $contact = $this->resolveContact($customer, $channel);
        if (!$contact) {
            throw new Exception($channel === 'sms'
                ? 'Kayıtlı telefon numarası bulunamadı.'
                : 'Kayıtlı e-posta adresi bulunamadı.');
        }

        $this->assertRateLimit($customer, $channel, $context);

        $code = $this->generateOtpCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime(self::EXPIRY_INTERVAL));

        $tokenId = $this->db->insert('customer_login_tokens', [
            'customer_id' => $customerId,
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
            ? $this->maskPhone($customer['phone'] ?? '')
            : $this->maskEmail($customer['email'] ?? '');

        $this->dispatchToken($code, $customer, $channel, $contact);
        // Mark OTP issued for both SMS and email channels
        $this->customerModel->markOtpIssued($customerId, $context);

        return [
            'token_id' => $tokenId,
            'channel' => $channel,
            'expires_at' => $expiresAt,
            'masked_contact' => $masked,
        ];
    }

    /**
     * Verify submitted OTP.
     */
    public function verifyToken(int $tokenId, string $code): array
    {
        $token = $this->db->fetch(
            'SELECT * FROM customer_login_tokens WHERE id = ?',
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
                'customer_login_tokens',
                [
                    'attempts' => (int)$token['attempts'] + 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                'id = ?',
                [$tokenId]
            );

            $this->customerModel->incrementOtpAttempt((int)$token['customer_id']);

            return [
                'success' => false,
                'reason' => 'mismatch',
                'attempts_remaining' => max(0, (int)$token['max_attempts'] - ((int)$token['attempts'] + 1)),
            ];
        }

        $this->db->update(
            'customer_login_tokens',
            [
                'consumed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'id = ?',
            [$tokenId]
        );

        $this->customerModel->resetOtpState((int)$token['customer_id']);

        return [
            'success' => true,
            'customer_id' => (int)$token['customer_id'],
        ];
    }

    public function maskEmail(string $email): string
    {
        if (!$email || strpos($email, '@') === false) {
            return '***@***';
        }
        [$local, $domain] = explode('@', $email, 2);
        $localMasked = mb_substr($local, 0, 1) . str_repeat('*', max(1, mb_strlen($local) - 1));
        $domainPartMasked = implode('.', array_map(function ($part) {
            return mb_substr($part, 0, 1) . str_repeat('*', max(1, mb_strlen($part) - 1));
        }, explode('.', $domain)));
        return $localMasked . '@' . $domainPartMasked;
    }

    public function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) <= 4) {
            return str_repeat('*', strlen($digits));
        }
        $suffix = substr($digits, -4);
        return '+** ' . str_repeat('*', max(0, strlen($digits) - 4)) . $suffix;
    }

    private function resolveContact(array $customer, string $channel): ?string
    {
        if ($channel === 'sms') {
            return $customer['phone'] ?? null;
        }

        return $customer['email'] ?? null;
    }

    private function assertRateLimit(array $customer, string $channel, string $context): void
    {
        $customerId = (int)($customer['id'] ?? 0);
        if (!$customerId) {
            throw new Exception('Geçersiz müşteri kaydı');
        }

        $lastSentAt = $customer['last_otp_sent_at'] ?? null;
        $lastContext = $customer['otp_context'] ?? null;
        if ($lastSentAt && $lastContext === $context) {
            $secondsSinceLast = time() - strtotime($lastSentAt);
            if ($secondsSinceLast < self::RESEND_COOLDOWN_SECONDS) {
                throw new Exception('Lütfen yeni kod istemeden önce biraz bekleyin.');
            }
        }

        $hourCount = $this->db->fetch(
            "SELECT COUNT(*) as cnt FROM customer_login_tokens
             WHERE customer_id = ?
               AND channel = ?
               AND created_at >= datetime('now', 'localtime', '-1 hour')",
            [$customerId, $channel]
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

    private function dispatchToken(string $code, array $customer, string $channel, string $contact): void
    {
        if ($channel === 'sms') {
            $this->smsQueue->add([
                'to' => $contact,
                'message' => "Portal giriş doğrulama kodunuz: {$code}. Bu kod 5 dakika geçerlidir.",
                'data' => [
                    'type' => 'customer_portal_login',
                    'customer_id' => $customer['id'] ?? null,
                ],
            ]);
            $this->smsQueue->process(1);
            return;
        }

        $message = sprintf(
            '<p>Merhaba %s,</p><p>Müşteri portalına giriş için doğrulama kodunuz: <strong style="font-size:20px;">%s</strong></p><p>Bu kod 5 dakika boyunca geçerlidir. Siz talep etmediyseniz lütfen destek ekibimizle iletişime geçin.</p>',
            htmlspecialchars($customer['name'] ?? 'Değerli Müşterimiz'),
            $code
        );

        $this->emailQueue->add([
            'to' => $contact,
            'subject' => 'Portal Giriş Doğrulama Kodunuz',
            'message' => $message,
            'template' => 'customer_login_otp',
            'data' => [
                'customer_id' => $customer['id'] ?? null,
            ],
        ]);
    }
}


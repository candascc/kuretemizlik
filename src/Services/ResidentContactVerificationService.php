<?php

class ResidentContactVerificationService
{
    private const CODE_LENGTH = 6;
    private const EXPIRY_INTERVAL = '+10 minutes';
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const MAX_PER_HOUR = 5;

    public const ERROR_INVALID_PHONE = 'Telefon numarası geçerli bir formatta olmalıdır (örn. +90 5XX XXX XX XX).';
    public const ERROR_PHONE_LENGTH = 'Telefon numarası en az 11 haneli olmalıdır.';
    public const ERROR_PHONE_COUNTRY = 'Telefon numarası ülke kodu ile başlamalıdır (örn. +90...).';
    public const ERROR_PHONE_DUPLICATE = 'İkincil telefon numarası birincil telefon numarası ile aynı olamaz.';
    public const ERROR_PENDING_VERIFICATION_ACTIVE = 'Gönderilen kodu kullanın veya süresi dolduktan sonra yeniden deneyin.';

    private Database $db;
    private EmailQueue $emailQueue;
    private SMSQueue $smsQueue;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->emailQueue = new EmailQueue();
        $this->smsQueue = new SMSQueue();
    }

    public function listPending(int $residentId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM resident_contact_verifications 
             WHERE resident_user_id = ? AND status = 'pending' 
             ORDER BY created_at DESC",
            [$residentId]
        ) ?: [];
    }

    public function requestVerification(array $resident, string $type, string $newValue): array
    {
        $residentId = (int)($resident['id'] ?? 0);
        if (!$residentId) {
            throw new Exception('Geçersiz sakin kaydı.');
        }

        $type = $type === 'phone' ? 'phone' : 'email';
        $channel = $type === 'phone' ? 'sms' : 'email';
        $newValue = trim($newValue);
        $normalizedValue = $newValue;

        if ($type === 'email' && !filter_var($newValue, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Geçerli bir e-posta adresi girin.');
        }
        if ($type === 'phone') {
            $normalizedValue = Utils::normalizePhone($newValue);
            if ($normalizedValue === null) {
                throw new Exception(self::ERROR_INVALID_PHONE);
            }
            $this->assertPhoneIntegrity($normalizedValue);
        }

        $this->assertRateLimit($residentId, $type);

        $now = date('Y-m-d H:i:s');
        $existingPending = $this->db->fetch(
            "SELECT * FROM resident_contact_verifications
             WHERE resident_user_id = ? AND verification_type = ? AND status = 'pending'
             ORDER BY id DESC LIMIT 1",
            [$residentId, $type]
        );

        if ($existingPending) {
            $pendingValue = $existingPending['new_value'] ?? '';
            $matchesPending = $type === 'phone'
                ? Utils::normalizePhone($pendingValue) === $normalizedValue
                : strcasecmp($pendingValue, $normalizedValue) === 0;

            if ($matchesPending) {
                $expiryTime = isset($existingPending['expires_at']) ? strtotime($existingPending['expires_at']) : false;
                if ($expiryTime !== false && $expiryTime <= time()) {
                    $this->db->update('resident_contact_verifications', [
                        'status' => 'expired',
                        'updated_at' => $now,
                    ], 'id = ?', [$existingPending['id']]);
                } else {
                    $sentAt = isset($existingPending['sent_at']) ? strtotime($existingPending['sent_at']) : time();
                    $cooldownRemaining = max(0, ($sentAt + self::RESEND_COOLDOWN_SECONDS) - time());
                    if ($cooldownRemaining > 0) {
                        throw new Exception(sprintf('Gönderilen kodu kullanın veya %d saniye sonra yeniden deneyin.', max(1, $cooldownRemaining)));
                    }

                    throw new Exception(self::ERROR_PENDING_VERIFICATION_ACTIVE);
                }
            }
        }

        $this->supersedeExisting($residentId, $type);

        $code = $this->generateCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime(self::EXPIRY_INTERVAL));

        $verificationId = $this->db->insert('resident_contact_verifications', [
            'resident_user_id' => $residentId,
            'verification_type' => $type,
            'new_value' => $type === 'phone' ? $normalizedValue : $newValue,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'channel' => $channel,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => $expiresAt,
            'sent_at' => $now,
            'meta' => json_encode([
                'previous_value' => $type === 'email' ? ($resident['email'] ?? null) : ($resident['phone'] ?? null),
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $contactForDispatch = $channel === 'sms' ? $normalizedValue : $newValue;
        $this->dispatchCode($channel, $contactForDispatch, $code, $type);
        $this->notifyPreviousContact($resident, $type, $newValue);

        return [
            'verification_id' => $verificationId,
            'expires_at' => $expiresAt,
            'masked_contact' => $type === 'email'
                ? $this->maskEmail($newValue)
                : $this->maskPhone($normalizedValue),
        ];
    }

    public function resend(int $residentId, int $verificationId): array
    {
        $verification = $this->getVerification($residentId, $verificationId);
        $this->assertResendAllowed($verification);

        $code = $this->generateCode();
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime(self::EXPIRY_INTERVAL));

        $this->db->update('resident_contact_verifications', [
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'sent_at' => $now,
            'expires_at' => $expiresAt,
            'updated_at' => $now,
        ], 'id = ?', [$verificationId]);

        $contact = $verification['new_value'];
        if ($verification['verification_type'] === 'phone') {
            $contact = Utils::normalizePhone($contact);
        }

        $this->dispatchCode($verification['channel'], $contact, $code, $verification['verification_type']);

        return [
            'expires_at' => $expiresAt,
        ];
    }

    public function verify(int $residentId, int $verificationId, string $code): array
    {
        $verification = $this->getVerification($residentId, $verificationId);
        $now = date('Y-m-d H:i:s');

        if ($verification['status'] !== 'pending') {
            throw new Exception('Bu doğrulama talebi artık geçerli değil.');
        }

        if (strtotime($verification['expires_at']) < time()) {
            $this->db->update('resident_contact_verifications', [
                'status' => 'expired',
                'updated_at' => $now,
            ], 'id = ?', [$verificationId]);
            throw new Exception('Doğrulama kodunun süresi doldu, lütfen yeniden deneyin.');
        }

        if ($verification['attempts'] >= $verification['max_attempts']) {
            $this->db->update('resident_contact_verifications', [
                'status' => 'expired',
                'updated_at' => $now,
            ], 'id = ?', [$verificationId]);
            throw new Exception('Çok fazla başarısız deneme yaptınız, lütfen yeni kod isteyin.');
        }

        if (!password_verify($code, $verification['code_hash'])) {
            $this->db->update('resident_contact_verifications', [
                'attempts' => $verification['attempts'] + 1,
                'updated_at' => $now,
            ], 'id = ?', [$verificationId]);

            $remaining = max(0, $verification['max_attempts'] - ($verification['attempts'] + 1));
            throw new Exception('Kod hatalı. Kalan deneme: ' . $remaining);
        }

        $this->db->update('resident_contact_verifications', [
            'status' => 'verified',
            'updated_at' => $now,
        ], 'id = ?', [$verificationId]);

        return [
            'new_value' => $verification['new_value'],
            'type' => $verification['verification_type'],
        ];
    }

    private function getVerification(int $residentId, int $verificationId): array
    {
        $verification = $this->db->fetch(
            "SELECT * FROM resident_contact_verifications 
             WHERE id = ? AND resident_user_id = ?",
            [$verificationId, $residentId]
        );

        if (!$verification) {
            throw new Exception('Doğrulama isteği bulunamadı.');
        }

        return $verification;
    }

    private function assertPhoneIntegrity(string $normalizedPhone): void
    {
        $digits = preg_replace('/\D+/', '', $normalizedPhone);
        if (strlen($digits) < 11) {
            throw new Exception(self::ERROR_PHONE_LENGTH);
        }
        if (!str_starts_with($normalizedPhone, '+')) {
            throw new Exception(self::ERROR_PHONE_COUNTRY);
        }
    }

    private function assertRateLimit(int $residentId, string $type): void
    {
        $recent = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM resident_contact_verifications
             WHERE resident_user_id = ?
               AND verification_type = ?
               AND created_at >= datetime('now', '-1 hour')",
            [$residentId, $type]
        );

        if ((int)($recent['cnt'] ?? 0) >= self::MAX_PER_HOUR) {
            throw new Exception('Çok fazla doğrulama isteği oluşturdunuz. Lütfen daha sonra tekrar deneyin.');
        }
    }

    private function assertResendAllowed(array $verification): void
    {
        if ($verification['status'] !== 'pending') {
            throw new Exception('Bu doğrulama isteği artık geçerli değil.');
        }

        if (strtotime($verification['expires_at']) < time()) {
            throw new Exception('Doğrulama kodunun süresi doldu. Lütfen yeni kod isteyin.');
        }

        $cooldown = strtotime($verification['sent_at']) + self::RESEND_COOLDOWN_SECONDS;
        if ($cooldown > time()) {
            $remaining = $cooldown - time();
            throw new Exception('Yeni kod istemek için ' . $remaining . ' saniye bekleyin.');
        }
    }

    private function supersedeExisting(int $residentId, string $type): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->update('resident_contact_verifications', [
            'status' => 'superseded',
            'updated_at' => $now,
        ], 'resident_user_id = :resident AND verification_type = :type AND status = \'pending\'', [
            ':resident' => $residentId,
            ':type' => $type,
        ]);
    }

    private function dispatchCode(string $channel, string $contact, string $code, string $type): void
    {
        if ($channel === 'sms') {
            $this->smsQueue->add([
                'to' => $contact,
                'message' => "İletişim bilgisi doğrulama kodunuz: {$code}. Kod 10 dakika geçerlidir.",
                'data' => [
                    'type' => 'resident_contact_verification',
                    'contact_type' => $type,
                ],
            ]);
            $this->smsQueue->process(1);
            return;
        }

        $message = sprintf(
            '<p>Merhaba,</p><p>İletişim bilgilerinizde değişiklik talep edildi. Doğrulama kodunuz: <strong style="font-size:20px;">%s</strong></p><p>Bu işlem size ait değilse lütfen yönetiminizle iletişime geçin.</p>',
            $code
        );

        $this->emailQueue->add([
            'to' => $contact,
            'subject' => 'İletişim Bilgisi Doğrulama Kodunuz',
            'message' => $message,
            'template' => 'resident_contact_verification',
        ]);
    }

    private function notifyPreviousContact(array $resident, string $type, string $newValue): void
    {
        $previous = $type === 'email' ? ($resident['email'] ?? null) : ($resident['phone'] ?? null);
        if (!$previous) {
            return;
        }

        $message = sprintf(
            'Merhaba %s, hesabınızda kayıtlı %s bilgisi değiştirilmeye çalışıldı. Bu işlemi siz gerçekleştirmediyseniz lütfen yönetiminizle iletişime geçin.',
            $resident['name'] ?? 'Sakin',
            $type === 'email' ? 'e-posta' : 'telefon'
        );

        if ($type === 'email') {
            $this->emailQueue->add([
                'to' => $previous,
                'subject' => 'İletişim Bilgisi Güncelleme Bildirimi',
                'message' => '<p>' . htmlspecialchars($message) . '</p><p>Yeni değer: ' . htmlspecialchars($newValue) . '</p>',
                'template' => 'resident_contact_notice',
            ]);
        } else {
            $normalized = Utils::normalizePhone($previous);
            if ($normalized) {
                $this->smsQueue->add([
                    'to' => $normalized,
                    'message' => "{$message} Yeni numara: {$newValue}",
                    'data' => [
                        'type' => 'resident_contact_notice',
                    ],
                ]);
                $this->smsQueue->process(1);
            }
        }
    }

    private function generateCode(): string
    {
        $code = random_int(0, (10 ** self::CODE_LENGTH) - 1);
        return str_pad((string)$code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function maskEmail(string $email): string
    {
        if (!$email || strpos($email, '@') === false) {
            return '***@***';
        }
        [$local, $domain] = explode('@', $email, 2);
        $localMasked = substr($local, 0, 1) . str_repeat('*', max(1, strlen($local) - 1));
        $domainParts = explode('.', $domain);
        $domainMasked = implode('.', array_map(function ($part) {
            return substr($part, 0, 1) . str_repeat('*', max(1, strlen($part) - 1));
        }, $domainParts));
        return $localMasked . '@' . $domainMasked;
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) <= 4) {
            return str_repeat('*', strlen($digits));
        }
        $suffix = substr($digits, -4);
        return '+** ' . str_repeat('*', max(0, strlen($digits) - 4)) . $suffix;
    }
}


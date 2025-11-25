<?php
/**
 * Contract OTP Service
 * Sözleşme onayı için OTP üretme, gönderme ve doğrulama servisi
 */

class ContractOtpService
{
    public const OTP_LENGTH = 6;
    public const EXPIRY_INTERVAL = '+10 minutes'; // Sözleşme OTP için 10 dakika geçerli
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60; // 60 saniye içinde tekrar gönderilmesini engelle

    private Database $db;
    private SMSQueue $smsQueue;
    private ContractOtpToken $tokenModel;
    private JobContract $contractModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->smsQueue = new SMSQueue();
        $this->tokenModel = new ContractOtpToken();
        $this->contractModel = new JobContract();
    }

    /**
     * OTP oluştur ve SMS ile gönder
     *
     * Eğer bu sözleşme için hala geçerli (unverified ve süresi dolmamış) bir OTP varsa:
     * - Yeni OTP oluşturulur, eskisi expire edilmez (kullanıcı eski veya yeni kodu deneyebilir)
     * - Ancak cooldown kontrolü yapılır (60 saniye içinde tekrar gönderilmesini engeller)
     *
     * @param array $contract JobContract kaydı
     * @param array $customer Customer kaydı
     * @param string $phone Gönderilecek telefon numarası
     * @return array Oluşturulan ContractOtpToken kaydı
     * @throws Exception Telefon numarası geçersizse veya cooldown varsa exception fırlatır
     */
    public function createAndSendOtp(array $contract, array $customer, string $phone): array
    {
        // Telefon numarası validasyonu
        $normalizedPhone = Utils::normalizePhone($phone);
        if (!$normalizedPhone) {
            throw new Exception('Geçerli bir telefon numarası girin.');
        }

        // Cooldown kontrolü (60 saniye içinde tekrar gönderilmesini engelle)
        $lastToken = $this->tokenModel->findActiveByJobContract($contract['id']);
        if ($lastToken && !empty($lastToken['sent_at'])) {
            $secondsSinceLast = time() - strtotime($lastToken['sent_at']);
            if ($secondsSinceLast < self::RESEND_COOLDOWN_SECONDS) {
                $remaining = self::RESEND_COOLDOWN_SECONDS - $secondsSinceLast;
                throw new Exception("Lütfen yeni kod istemeden önce {$remaining} saniye bekleyin.");
            }
        }

        // Rate limit kontrolü (saat başına maksimum OTP sayısı)
        $hourCount = $this->db->fetch(
            "SELECT COUNT(*) as cnt FROM contract_otp_tokens
             WHERE job_contract_id = ?
               AND customer_id = ?
               AND sent_at >= datetime('now', 'localtime', '-1 hour')",
            [$contract['id'], $customer['id']]
        );
        
        if ((int)($hourCount['cnt'] ?? 0) >= 10) {
            throw new Exception('Çok fazla OTP talebi oluşturdunuz. Lütfen daha sonra tekrar deneyin.');
        }

        // 6 haneli OTP üret
        $code = $this->generateOtpCode();
        
        // OTP'yi hashle
        $tokenHash = password_hash($code, PASSWORD_DEFAULT);
        
        // Expiry zamanı hesapla
        $expiresAt = date('Y-m-d H:i:s', strtotime(self::EXPIRY_INTERVAL));

        // ContractOtpToken kaydı oluştur
        $tokenId = $this->tokenModel->create([
            'job_contract_id' => $contract['id'],
            'customer_id' => $customer['id'],
            'token' => $tokenHash,
            'phone' => $normalizedPhone,
            'channel' => 'sms',
            'expires_at' => $expiresAt,
            'sent_at' => date('Y-m-d H:i:s'),
            'attempts' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        if (!$tokenId) {
            throw new Exception('OTP token oluşturulurken hata oluştu.');
        }

        // Job bilgilerini hazırla (SMS mesajı için)
        $jobModel = new Job();
        $job = $jobModel->find($contract['job_id']);
        $jobDate = $job ? Utils::formatDateTime($job['start_at'], 'd.m.Y') : '';

        // Public sözleşme linkini üret (tam URL - SMS için)
        // TODO: İleride public_token (UUID) eklenip linkler daha güvenli hale getirilecek.
        $publicLink = $this->generateFullUrl('/contract/' . (int)$contract['id']);

        // SMS mesajı oluştur (link ile birlikte)
        // Not: SMS servisleri bazen Türkçe karakterleri desteklemediği için "Küre" yerine "Kure" kullanıyoruz
        // Portal bilgisi ekleniyor: Eğer müşteri portal'a giriş yaparsa, otomatik olarak sözleşme onay ekranına yönlendirilecek
        $portalUrl = $this->generateFullUrl('/portal/login');
        $smsMessage = sprintf(
            "Kure Temizlik - %s tarihli temizlik hizmeti sozlesmenizi onaylamak icin dogrulama kodunuz: %s\n\nSozlesme linkiniz: %s\n\nNot: Portal'a giris yaparsaniz (%s), otomatik olarak sozlesme onay ekranina yonlendirilirsiniz.\n\n(Kod 10 dakika gecerlidir.)",
            $jobDate,
            $code,
            $publicLink,
            $portalUrl
        );

        // SMS'i kuyruğa ekle ve gönder
        $this->smsQueue->add([
            'to' => $normalizedPhone,
            'message' => $smsMessage,
            'data' => [
                'type' => 'contract_approval_otp',
                'job_contract_id' => $contract['id'],
                'customer_id' => $customer['id'],
                'job_id' => $contract['job_id'],
            ],
        ]);
        
        // SMS'i hemen işle (OTP için kritik)
        try {
            $this->smsQueue->process(1);
        } catch (Exception $e) {
            error_log("SMS queue process error for contract {$contract['id']}: " . $e->getMessage());
            if (class_exists('Logger')) {
                Logger::error('SMS queue process failed', [
                    'contract_id' => $contract['id'],
                    'token_id' => $tokenId,
                    'error' => $e->getMessage(),
                ]);
            }
            // SMS gönderim hatası kritik ama token oluşturuldu, yine de devam et
        }

        // JobContract'ta SMS gönderim sayısını artır
        try {
            $this->contractModel->incrementSmsCount($contract['id'], $tokenId);
        } catch (Exception $e) {
            error_log("Failed to increment SMS count for contract {$contract['id']}: " . $e->getMessage());
            // Kritik değil, devam et
        }

        // Activity log
        if (class_exists('ActivityLogger')) {
            try {
                ActivityLogger::log('contract.otp.sent', 'job_contract', (int)$contract['id'], [
                    'job_id' => $contract['job_id'],
                    'customer_id' => $customer['id'] ?? null,
                    'phone' => $normalizedPhone,
                    'token_id' => $tokenId,
                ]);
            } catch (Exception $e) {
                // Activity log hatası kritik değil
                error_log("Activity log error: " . $e->getMessage());
            }
        }

        // Oluşturulan token kaydını döndür
        $token = $this->tokenModel->find($tokenId);
        if (!$token) {
            error_log("Created OTP token not found: token_id={$tokenId}");
            if (class_exists('Logger')) {
                Logger::error('Created OTP token not found', [
                    'token_id' => $tokenId,
                    'contract_id' => $contract['id'],
                ]);
            }
            throw new Exception('Oluşturulan OTP token bulunamadı.');
        }

        return $token;
    }

    /**
     * OTP'yi doğrula ve başarılıysa sözleşmeyi onayla
     *
     * @param array $contract JobContract kaydı
     * @param string $rawCode Kullanıcının girdiği ham OTP kodu
     * @param string|null $ip IP adresi
     * @param string|null $userAgent User agent
     * @return array ['success' => bool, 'reason' => string|null, 'attempts_remaining' => int|null]
     */
    public function verifyOtp(array $contract, string $rawCode, ?string $ip = null, ?string $userAgent = null, ?\DateTimeInterface $now = null): array
    {
        // Aktif token bul (unverified, not expired, attempts < max)
        $token = $this->tokenModel->findActiveByJobContract($contract['id']);
        
        if (!$token) {
            return [
                'success' => false,
                'reason' => 'not_found',
                'error_type' => 'expired',
                'message' => 'Geçerli bir OTP kodu bulunamadı. Lütfen yeni kod isteyin.'
            ];
        }

        // Süre kontrolü
        if (strtotime($token['expires_at']) < time()) {
            return [
                'success' => false,
                'reason' => 'expired',
                'error_type' => 'expired',
                'message' => 'OTP kodunun süresi dolmuş. Lütfen yeni kod isteyin.'
            ];
        }

        // Attempt kontrolü
        if ((int)$token['attempts'] >= (int)$token['max_attempts']) {
            return [
                'success' => false,
                'reason' => 'attempts_exceeded',
                'error_type' => 'blocked',
                'message' => 'Çok fazla hatalı deneme yaptınız. Lütfen yeni kod isteyin.'
            ];
        }

        // OTP doğrulama
        $isValid = password_verify($rawCode, $token['token']);
        
        if (!$isValid) {
            // Hatalı deneme sayısını artır
            $this->tokenModel->incrementAttempts($token['id']);
            
            $updatedToken = $this->tokenModel->find($token['id']);
            $attemptsRemaining = max(0, (int)$token['max_attempts'] - ((int)$updatedToken['attempts'] ?? $token['attempts']));

            return [
                'success' => false,
                'reason' => 'mismatch',
                'error_type' => 'invalid',
                'message' => 'OTP kodu hatalı.',
                'attempts_remaining' => $attemptsRemaining
            ];
        }

        // OTP doğru - token'ı verified olarak işaretle
        $this->tokenModel->update($token['id'], [
            'verified_at' => date('Y-m-d H:i:s'),
            'ip_address' => $ip ?? ($token['ip_address'] ?? null),
            'user_agent' => $userAgent ?? ($token['user_agent'] ?? null),
        ]);

        // Sözleşmeyi onayla
        $customerId = $token['customer_id'] ?? $contract['job_customer_id'] ?? null;
        $phone = $token['phone'] ?? null;

        $this->contractModel->approve(
            $contract['id'],
            $phone,
            $customerId ? (int)$customerId : null,
            $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? null)
        );

        // Activity log (eğer varsa)
        if (class_exists('ActivityLogger')) {
            try {
                ActivityLogger::log('contract.approved', 'job_contract', $contract['id'], [
                    'job_id' => $contract['job_id'],
                    'customer_id' => $customerId,
                    'method' => 'SMS_OTP',
                ]);
            } catch (Exception $e) {
                // Activity log hatası kritik değil, sessizce geç
                error_log("Activity log error: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'reason' => null,
            'message' => 'Sözleşme başarıyla onaylandı.',
        ];
    }

    /**
     * Bu sözleşme için aktif bir OTP var mı kontrol et
     *
     * @param array $contract JobContract kaydı
     * @return bool
     */
    public function hasActiveOtp(array $contract): bool
    {
        $token = $this->tokenModel->findActiveByJobContract($contract['id']);
        return $token !== null;
    }

    /**
     * 6 haneli OTP kodu üret
     *
     * @return string
     */
    private function generateOtpCode(): string
    {
        $code = random_int(0, (10 ** self::OTP_LENGTH) - 1);
        return str_pad((string)$code, self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Tam URL üret (SMS için)
     * base_url() relative path döndürdüğü için, SMS'te kullanılacak tam URL'i üretir
     *
     * @param string $path URL path (örn: '/contract/1')
     * @return string Tam URL (örn: 'https://kuretemizlik.com/contract/1')
     */
    private function generateFullUrl(string $path): string
    {
        // Önce environment variable'dan site URL'ini kontrol et
        $siteUrl = $_ENV['SITE_URL'] ?? $_ENV['APP_URL'] ?? null;
        
        if ($siteUrl) {
            $siteUrl = rtrim($siteUrl, '/');
            $path = ltrim($path, '/');
            // APP_BASE'i path'ten çıkar (eğer varsa)
            $basePath = defined('APP_BASE') ? trim(APP_BASE, '/') : 'app';
            if (strpos($path, $basePath . '/') === 0) {
                $path = substr($path, strlen($basePath) + 1);
            }
            return $siteUrl . '/' . $path;
        }
        
        // Environment variable yoksa, HTTP_HOST ve scheme'den oluştur
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Path'i temizle ve APP_BASE'i çıkar
        $path = ltrim($path, '/');
        $basePath = defined('APP_BASE') ? trim(APP_BASE, '/') : 'app';
        
        // Eğer path zaten base path içeriyorsa, onu çıkar
        if (strpos($path, $basePath . '/') === 0) {
            $path = substr($path, strlen($basePath) + 1);
        }
        
        return $scheme . '://' . $host . '/' . $path;
    }
}


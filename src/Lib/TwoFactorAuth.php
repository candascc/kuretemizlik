<?php
/**
 * Two-Factor Authentication (2FA) Implementation
 * TOTP (Time-based One-Time Password) support
 * Google Authenticator compatible
 */

class TwoFactorAuth
{
    private const SECRET_LENGTH = 32;
    private const WINDOW_SIZE = 1;
    private const TIME_STEP = 30;
    
    /**
     * Generate a random secret key for TOTP
     */
    public static function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Generate QR code data for Google Authenticator
     */
    public static function getQRCodeData(string $username, string $secret, string $issuer = 'Temizlik İş Takip'): string
    {
        $label = urlencode($username);
        $issuer = urlencode($issuer);
        $secret = urlencode($secret);
        
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}";
    }
    
    /**
     * Generate QR code image URL (using Google Charts API)
     */
    public static function getQRCodeUrl(string $username, string $secret, string $issuer = 'Temizlik İş Takip'): string
    {
        $qrData = self::getQRCodeData($username, $secret, $issuer);
        $encodedData = urlencode($qrData);
        
        return "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl={$encodedData}";
    }
    
    /**
     * Generate TOTP code for current time
     */
    public static function generateCode(string $secret, ?int $timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        $timeSlice = floor($timestamp / self::TIME_STEP);
        return self::generateHOTP($secret, $timeSlice);
    }
    
    /**
     * Verify TOTP code
     */
    public static function verifyCode(string $secret, string $code, ?int $timestamp = null): bool
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        // Check current time slice and surrounding windows
        for ($i = -self::WINDOW_SIZE; $i <= self::WINDOW_SIZE; $i++) {
            $timeSlice = floor($timestamp / self::TIME_STEP) + $i;
            $expectedCode = self::generateHOTP($secret, $timeSlice);
            
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate HOTP (HMAC-based One-Time Password)
     */
    private static function generateHOTP(string $secret, int $counter): string
    {
        $secretBytes = self::base32Decode($secret);
        $counterBytes = pack('N*', 0, $counter);
        
        $hash = hash_hmac('sha1', $counterBytes, $secretBytes, true);
        $offset = ord($hash[19]) & 0xf;
        
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 decode
     */
    private static function base32Decode(string $data): string
    {
        $map = [
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
            'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15,
            'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
            'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31
        ];
        
        $data = strtoupper($data);
        $data = str_replace('=', '', $data);
        
        $bits = '';
        foreach (str_split($data) as $char) {
            if (isset($map[$char])) {
                $bits .= str_pad(decbin($map[$char]), 5, '0', STR_PAD_LEFT);
            }
        }
        
        $bytes = '';
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $byte = substr($bits, $i, 8);
            if (strlen($byte) == 8) {
                $bytes .= chr(bindec($byte));
            }
        }
        
        return $bytes;
    }
    
    /**
     * Generate backup codes for 2FA
     */
    public static function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        }
        return $codes;
    }
    
    /**
     * Hash backup codes for storage
     */
    public static function hashBackupCodes(array $codes): array
    {
        return array_map('password_hash', $codes);
    }
    
    /**
     * Verify backup code
     */
    public static function verifyBackupCode(string $code, array $hashedCodes): bool
    {
        foreach ($hashedCodes as $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has 2FA enabled
     */
    public static function isEnabled(int $userId): bool
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT two_factor_secret FROM users WHERE id = ? AND two_factor_secret IS NOT NULL",
            [$userId]
        );
        
        return !empty($user['two_factor_secret']);
    }
    
    /**
     * Enable 2FA for user
     */
    public static function enable(int $userId, string $secret, array $backupCodes): bool
    {
        $db = Database::getInstance();
        $hashedBackupCodes = json_encode(self::hashBackupCodes($backupCodes));
        
        return $db->query(
            "UPDATE users SET two_factor_secret = ?, two_factor_backup_codes = ?, two_factor_enabled_at = ? WHERE id = ?",
            [$secret, $hashedBackupCodes, date('Y-m-d H:i:s'), $userId]
        ) !== false;
    }
    
    /**
     * Disable 2FA for user
     */
    public static function disable(int $userId): bool
    {
        $db = Database::getInstance();
        return $db->query(
            "UPDATE users SET two_factor_secret = NULL, two_factor_backup_codes = NULL, two_factor_enabled_at = NULL WHERE id = ?",
            [$userId]
        ) !== false;
    }
    
    /**
     * Get user's 2FA secret
     */
    public static function getSecret(int $userId): ?string
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT two_factor_secret FROM users WHERE id = ?",
            [$userId]
        );
        
        return $user['two_factor_secret'] ?? null;
    }
    
    /**
     * Get user's backup codes
     */
    public static function getBackupCodes(int $userId): array
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT two_factor_backup_codes FROM users WHERE id = ?",
            [$userId]
        );
        
        if (empty($user['two_factor_backup_codes'])) {
            return [];
        }
        
        return json_decode($user['two_factor_backup_codes'], true) ?? [];
    }
    
    /**
     * Remove used backup code
     */
    public static function removeBackupCode(int $userId, string $usedCode): bool
    {
        $backupCodes = self::getBackupCodes($userId);
        $newBackupCodes = [];
        
        foreach ($backupCodes as $hashedCode) {
            if (!password_verify($usedCode, $hashedCode)) {
                $newBackupCodes[] = $hashedCode;
            }
        }
        
        $db = Database::getInstance();
        return $db->query(
            "UPDATE users SET two_factor_backup_codes = ? WHERE id = ?",
            [json_encode($newBackupCodes), $userId]
        ) !== false;
    }
    
    /**
     * Verify 2FA code (TOTP or backup code)
     */
    public static function verify(int $userId, string $code): bool
    {
        $secret = self::getSecret($userId);
        if (!$secret) {
            return false;
        }
        
        // Try TOTP first
        if (self::verifyCode($secret, $code)) {
            return true;
        }
        
        // Try backup codes
        if (self::verifyBackupCode($code, self::getBackupCodes($userId))) {
            self::removeBackupCode($userId, $code);
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if 2FA is required for user
     */
    public static function isRequired(int $userId): bool
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT role, two_factor_required FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            return false;
        }
        
        // Check if 2FA is globally required for admin users
        if ($user['role'] === 'ADMIN' && ($_ENV['REQUIRE_2FA_ADMIN'] ?? false)) {
            return true;
        }
        
        // Check if user has 2FA required flag
        return (bool) $user['two_factor_required'];
    }
    
    /**
     * Set 2FA requirement for user
     */
    public static function setRequired(int $userId, bool $required): bool
    {
        $db = Database::getInstance();
        return $db->query(
            "UPDATE users SET two_factor_required = ? WHERE id = ?",
            [$required ? 1 : 0, $userId]
        ) !== false;
    }
}

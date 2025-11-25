<?php
/**
 * Multi-Factor Authentication Service
 * 
 * Provides MFA functionality with TOTP (Time-Based One-Time Password) support.
 * 
 * ROUND 4 - STAGE 1: Real TOTP Implementation (RFC 6238)
 * 
 * @package App\Services
 * @author System
 * @version 2.0
 */

class MfaService
{
    private static $config = null;
    
    // TOTP Configuration (RFC 6238)
    private const TOTP_TIME_STEP = 30; // 30 seconds
    private const TOTP_DIGITS = 6; // 6-digit codes
    private const TOTP_ALGORITHM = 'SHA1';
    private const TOTP_WINDOW = 1; // ±1 time step tolerance for clock skew
    
    // Recovery codes configuration
    private const RECOVERY_CODES_COUNT = 10;
    private const RECOVERY_CODE_LENGTH = 8;
    
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
                'mfa' => [
                    'enabled' => false,
                    'methods' => ['otp_sms', 'totp'],
                    'required_for_roles' => ['SUPERADMIN'],
                ],
            ];
        }
        
        return self::$config;
    }
    
    /**
     * Check if MFA is enabled
     * 
     * @return bool
     */
    public static function isEnabled(): bool
    {
        $config = self::loadConfig();
        return $config['mfa']['enabled'] ?? false;
    }
    
    /**
     * Check if MFA is required for a user
     * 
     * @param array $user User data array
     * @return bool
     */
    public static function isRequiredForUser(array $user): bool
    {
        if (!self::isEnabled()) {
            return false;
        }
        
        $config = self::loadConfig();
        $requiredRoles = $config['mfa']['required_for_roles'] ?? [];
        $userRole = $user['role'] ?? '';
        
        return in_array($userRole, $requiredRoles);
    }
    
    /**
     * Check if user has MFA enabled
     * 
     * @param array $user User data array
     * @return bool
     */
    public static function isEnabledForUser(array $user): bool
    {
        $mfaEnabled = (int)($user['two_factor_required'] ?? 0);
        $mfaSecret = $user['two_factor_secret'] ?? null;
        
        return $mfaEnabled === 1 && !empty($mfaSecret);
    }
    
    /**
     * Generate a secure TOTP secret (Base32 encoded)
     * ROUND 4: Real secret generation
     * 
     * @return string Base32 encoded secret
     */
    public static function generateSecret(): string
    {
        // Generate 20 random bytes (160 bits) as per RFC 4226 recommendation
        $randomBytes = random_bytes(20);
        return self::base32Encode($randomBytes);
    }
    
    /**
     * Generate recovery codes for a user
     * ROUND 4: Recovery codes generation
     * 
     * @return array Array of recovery codes
     */
    public static function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODES_COUNT; $i++) {
            // Generate random alphanumeric code
            $code = strtoupper(bin2hex(random_bytes(self::RECOVERY_CODE_LENGTH / 2)));
            // Format as XXXX-XXXX for readability
            $formatted = substr($code, 0, 4) . '-' . substr($code, 4, 4);
            $codes[] = $formatted;
        }
        return $codes;
    }
    
    /**
     * Get OTP URI for QR code generation (Google Authenticator compatible)
     * ROUND 4: Real OTP URI generation
     * 
     * @param array $user User data array
     * @param string $secret Base32 encoded secret
     * @return string otpauth:// URI
     */
    public static function getOtpUri(array $user, string $secret): string
    {
        $issuer = defined('APP_NAME') ? APP_NAME : 'Kuretemizlik';
        $username = $user['username'] ?? $user['email'] ?? 'user';
        $label = rawurlencode($issuer . ':' . $username);
        
        $params = [
            'secret' => $secret,
            'issuer' => rawurlencode($issuer),
            'algorithm' => self::TOTP_ALGORITHM,
            'digits' => self::TOTP_DIGITS,
            'period' => self::TOTP_TIME_STEP,
        ];
        
        $queryString = http_build_query($params);
        
        return 'otpauth://totp/' . $label . '?' . $queryString;
    }
    
    /**
     * Verify TOTP code
     * ROUND 4: Real TOTP verification (RFC 6238)
     * 
     * @param string $secret Base32 encoded secret
     * @param string $code 6-digit code to verify
     * @param int|null $timestamp Unix timestamp (null = current time)
     * @return bool
     */
    public static function verifyTotpCode(string $secret, string $code, ?int $timestamp = null): bool
    {
        if (empty($secret) || empty($code)) {
            return false;
        }
        
        // Normalize code (remove spaces, ensure 6 digits)
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        
        $timestamp = $timestamp ?? time();
        
        // Try current time step and ±1 step (for clock skew tolerance)
        for ($i = -self::TOTP_WINDOW; $i <= self::TOTP_WINDOW; $i++) {
            $timeStep = floor(($timestamp + ($i * self::TOTP_TIME_STEP)) / self::TOTP_TIME_STEP);
            $expectedCode = self::generateTotpCode($secret, $timeStep);
            
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate TOTP code for a given time step
     * ROUND 4: Real TOTP code generation (RFC 6238)
     * 
     * @param string $secret Base32 encoded secret
     * @param int $timeStep Time step (counter)
     * @return string 6-digit code
     */
    private static function generateTotpCode(string $secret, int $timeStep): string
    {
        // Decode Base32 secret
        $key = self::base32Decode($secret);
        
        // Pack time step as 8-byte big-endian integer
        $counter = pack('N*', 0) . pack('N*', $timeStep);
        
        // Generate HMAC-SHA1
        $hmac = hash_hmac('sha1', $counter, $key, true);
        
        // Dynamic truncation (RFC 4226)
        $offset = ord($hmac[19]) & 0x0F;
        $binary = (
            ((ord($hmac[$offset]) & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
            (ord($hmac[$offset + 3]) & 0xFF)
        );
        
        // Generate 6-digit code
        $otp = $binary % pow(10, self::TOTP_DIGITS);
        
        return str_pad((string)$otp, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }
    
    /**
     * Verify recovery code
     * ROUND 4: Recovery code verification
     * 
     * @param array $user User data array
     * @param string $code Recovery code to verify
     * @return bool
     */
    public static function verifyRecoveryCode(array $user, string $code): bool
    {
        $backupCodesJson = $user['two_factor_backup_codes'] ?? null;
        if (empty($backupCodesJson)) {
            return false;
        }
        
        $backupCodes = json_decode($backupCodesJson, true);
        if (!is_array($backupCodes)) {
            return false;
        }
        
        // Normalize code (remove spaces, uppercase)
        $code = strtoupper(preg_replace('/\s+/', '', $code));
        
        // Check if code exists in backup codes
        $index = array_search($code, $backupCodes, true);
        if ($index === false) {
            return false;
        }
        
        // Remove used code from array
        unset($backupCodes[$index]);
        $backupCodes = array_values($backupCodes); // Re-index
        
        // Update user's backup codes (remove used code)
        $userModel = new User();
        $userModel->update($user['id'], [
            'two_factor_backup_codes' => json_encode($backupCodes)
        ]);
        
        return true;
    }
    
    /**
     * Start MFA challenge for a user
     * ROUND 4: Real challenge initiation (TOTP-based)
     * 
     * @param array $user User data array
     * @param string $method MFA method (otp_sms, totp, etc.)
     * @return array ['success' => bool, 'challenge_id' => string|null, 'message' => string]
     */
    public static function startMfaChallenge(array $user, string $method = 'totp'): array
    {
        if (!self::isEnabled()) {
            return [
                'success' => false,
                'challenge_id' => null,
                'message' => 'MFA is not enabled'
            ];
        }
        
        // Check if user has MFA enabled
        if (!self::isEnabledForUser($user)) {
            return [
                'success' => false,
                'challenge_id' => null,
                'message' => 'MFA is not enabled for this user'
            ];
        }
        
        $challengeId = bin2hex(random_bytes(16));
        
        // Store challenge in session (temporary storage)
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['mfa_challenge'] = [
                'challenge_id' => $challengeId,
                'user_id' => $user['id'] ?? null,
                'method' => $method,
                'created_at' => time(),
                'expires_at' => time() + 300 // 5 minutes
            ];
        }
        
        return [
            'success' => true,
            'challenge_id' => $challengeId,
            'method' => $method,
            'message' => 'MFA challenge started'
        ];
    }
    
    /**
     * Verify MFA code
     * ROUND 4: Real TOTP/recovery code verification
     * 
     * @param array $user User data array
     * @param string $code MFA code to verify (TOTP or recovery code)
     * @param string|null $challengeId Challenge ID (if null, uses session)
     * @return array ['success' => bool, 'message' => string, 'used_recovery_code' => bool]
     */
    public static function verifyMfaCode(array $user, string $code, ?string $challengeId = null): array
    {
        if (!self::isEnabled()) {
            return [
                'success' => false,
                'message' => 'MFA is not enabled'
            ];
        }
        
        // Check if user has MFA enabled
        if (!self::isEnabledForUser($user)) {
            return [
                'success' => false,
                'message' => 'MFA is not enabled for this user'
            ];
        }
        
        // Verify challenge exists and is valid
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['mfa_challenge'])) {
            $challenge = $_SESSION['mfa_challenge'];
            
            // Check expiration
            if (time() > ($challenge['expires_at'] ?? 0)) {
                unset($_SESSION['mfa_challenge']);
                return [
                    'success' => false,
                    'message' => 'MFA challenge expired'
                ];
            }
            
            // Check user match
            if (($challenge['user_id'] ?? null) !== ($user['id'] ?? null)) {
                return [
                    'success' => false,
                    'message' => 'Invalid MFA challenge'
                ];
            }
            
            // Check challenge ID if provided
            if ($challengeId !== null && ($challenge['challenge_id'] ?? null) !== $challengeId) {
                return [
                    'success' => false,
                    'message' => 'Invalid MFA challenge ID'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'No active MFA challenge'
            ];
        }
        
        $secret = $user['two_factor_secret'] ?? null;
        if (empty($secret)) {
            return [
                'success' => false,
                'message' => 'MFA secret not found'
            ];
        }
        
        // Try TOTP verification first
        if (self::verifyTotpCode($secret, $code)) {
            // Update last verified timestamp
            $userModel = new User();
            $userModel->update($user['id'], [
                'two_factor_enabled_at' => date('Y-m-d H:i:s')
            ]);
            
            // Clear challenge
            if (session_status() === PHP_SESSION_ACTIVE) {
                unset($_SESSION['mfa_challenge']);
            }
            
            return [
                'success' => true,
                'message' => 'MFA verified successfully',
                'used_recovery_code' => false
            ];
        }
        
        // Try recovery code verification
        if (self::verifyRecoveryCode($user, $code)) {
            // Clear challenge
            if (session_status() === PHP_SESSION_ACTIVE) {
                unset($_SESSION['mfa_challenge']);
            }
            
            return [
                'success' => true,
                'message' => 'MFA verified with recovery code',
                'used_recovery_code' => true
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid MFA code'
        ];
    }
    
    /**
     * Check if user has pending MFA challenge
     * 
     * @param array $user User data array
     * @return bool
     */
    public static function hasPendingChallenge(array $user): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        if (!isset($_SESSION['mfa_challenge'])) {
            return false;
        }
        
        $challenge = $_SESSION['mfa_challenge'];
        
        // Check expiration
        if (time() > ($challenge['expires_at'] ?? 0)) {
            unset($_SESSION['mfa_challenge']);
            return false;
        }
        
        // Check user match
        return ($challenge['user_id'] ?? null) === ($user['id'] ?? null);
    }
    
    /**
     * Enable MFA for a user
     * ROUND 4: Enable MFA with secret and recovery codes
     * 
     * @param int $userId User ID
     * @param string $secret Base32 encoded secret
     * @param array $recoveryCodes Recovery codes
     * @return bool
     */
    public static function enableMfa(int $userId, string $secret, array $recoveryCodes): bool
    {
        $userModel = new User();
        return $userModel->update($userId, [
            'two_factor_secret' => $secret,
            'two_factor_backup_codes' => json_encode($recoveryCodes),
            'two_factor_required' => 1,
            'two_factor_enabled_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Disable MFA for a user
     * ROUND 4: Disable MFA and clear secrets
     * 
     * @param int $userId User ID
     * @return bool
     */
    public static function disableMfa(int $userId): bool
    {
        $userModel = new User();
        return $userModel->update($userId, [
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null,
            'two_factor_required' => 0,
            'two_factor_enabled_at' => null
        ]);
    }
    
    /**
     * Enable MFA for a user (admin helper)
     * ROUND 4: Enable MFA with auto-generated secret and recovery codes
     * 
     * @param array $user User data array
     * @return array ['success' => bool, 'message' => string, 'secret' => string|null, 'recovery_codes' => array|null]
     */
    public static function enableForUser(array $user): array
    {
        if (!self::isEnabled()) {
            return [
                'success' => false,
                'message' => 'MFA is not enabled globally'
            ];
        }
        
        $userId = (int)($user['id'] ?? 0);
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'Invalid user ID'
            ];
        }
        
        // Generate secret and recovery codes
        $secret = self::generateSecret();
        $recoveryCodes = self::generateRecoveryCodes();
        
        // Enable MFA
        $result = self::enableMfa($userId, $secret, $recoveryCodes);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'MFA enabled successfully',
                'secret' => $secret,
                'recovery_codes' => $recoveryCodes
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to enable MFA'
            ];
        }
    }
    
    /**
     * Disable MFA for a user (admin helper)
     * ROUND 4: Disable MFA and clear secrets
     * 
     * @param array $user User data array
     * @return array ['success' => bool, 'message' => string]
     */
    public static function disableForUser(array $user): array
    {
        $userId = (int)($user['id'] ?? 0);
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'Invalid user ID'
            ];
        }
        
        // Disable MFA
        $result = self::disableMfa($userId);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'MFA disabled successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to disable MFA'
            ];
        }
    }
    
    /**
     * Base32 encode (RFC 4648)
     * ROUND 4: Base32 encoding for TOTP secrets
     * 
     * @param string $data Binary data
     * @return string Base32 encoded string
     */
    private static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $encoded = '';
        $bits = 0;
        $value = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $value = ($value << 8) | ord($data[$i]);
            $bits += 8;
            
            while ($bits >= 5) {
                $encoded .= $alphabet[($value >> ($bits - 5)) & 31];
                $bits -= 5;
            }
        }
        
        if ($bits > 0) {
            $encoded .= $alphabet[($value << (5 - $bits)) & 31];
        }
        
        return $encoded;
    }
    
    /**
     * Base32 decode (RFC 4648)
     * ROUND 4: Base32 decoding for TOTP secrets
     * 
     * @param string $data Base32 encoded string
     * @return string Binary data
     */
    private static function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper($data);
        $decoded = '';
        $bits = 0;
        $value = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            $index = strpos($alphabet, $char);
            
            if ($index === false) {
                continue; // Skip invalid characters
            }
            
            $value = ($value << 5) | $index;
            $bits += 5;
            
            if ($bits >= 8) {
                $decoded .= chr(($value >> ($bits - 8)) & 255);
                $bits -= 8;
            }
        }
        
        return $decoded;
    }
}


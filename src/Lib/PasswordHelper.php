<?php
/**
 * Password Helper
 * Centralized password verification and rehashing
 * ===== ERR-014 FIX: Password hashing helper =====
 */

class PasswordHelper
{
    /**
     * Verify password and automatically rehash if needed
     * 
     * @param string $password Plain text password
     * @param string $hash Stored password hash
     * @param callable|null $updateCallback Callback to update hash in database (receives new hash)
     * @return bool True if password is valid, false otherwise
     */
    public static function verifyPassword(string $password, string $hash, ?callable $updateCallback = null): bool
    {
        if (empty($hash) || empty($password)) {
            return false;
        }
        
        // Verify password
        if (!password_verify($password, $hash)) {
            return false;
        }
        
        // Check if rehash is needed
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash && $updateCallback !== null) {
                try {
                    $updateCallback($newHash);
                } catch (Exception $e) {
                    // Log but don't fail verification if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash update failed: " . $e->getMessage());
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Hash password using current default algorithm
     * 
     * @param string $password Plain text password
     * @return string|false Password hash or false on failure
     */
    public static function hashPassword(string $password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Check if password needs rehashing
     * 
     * @param string $hash Stored password hash
     * @return bool True if rehash is needed
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}
// ===== ERR-014 FIX: End =====


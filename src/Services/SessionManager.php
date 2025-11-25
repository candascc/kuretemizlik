<?php
/**
 * Session Manager
 * 
 * Centralized session backup and restore with locking mechanism
 * Prevents race conditions during session operations
 */

require_once __DIR__ . '/../Lib/SessionHelper.php';

class SessionManager
{
    /**
     * Session snapshot data
     */
    private array $snapshot = [];
    
    /**
     * Whether session was active before backup
     */
    private bool $sessionWasActive = false;
    
    /**
     * Create a session snapshot (backup)
     * 
     * @return bool True if backup was successful
     */
    public function backup(): bool
    {
        // Ensure session is started
        if (!SessionHelper::ensureStarted()) {
            $this->sessionWasActive = false;
            return false;
        }
        
        $this->sessionWasActive = true;
        
        // Create selective backup (only important keys, not deep copy)
        $this->snapshot = [];
        
        if (isset($_SESSION) && is_array($_SESSION)) {
            // Only backup critical session variables to avoid overhead
            $criticalKeys = [
                'user_id',
                'username',
                'role',
                'logged_in',
                'login_time',
                'last_activity',
                'csrf_token',
            ];
            
            foreach ($criticalKeys as $key) {
                if (isset($_SESSION[$key])) {
                    // Shallow copy for simple values, deep copy only for arrays
                    $value = $_SESSION[$key];
                    if (is_array($value)) {
                        $this->snapshot[$key] = unserialize(serialize($value));
                    } else {
                        $this->snapshot[$key] = $value;
                    }
                }
            }
            
            // Also backup any other keys that might be important
            // But avoid large objects to prevent memory issues
            foreach ($_SESSION as $key => $value) {
                if (!isset($this->snapshot[$key]) && !in_array($key, $criticalKeys, true)) {
                    // Only backup if value is not too large
                    $serialized = serialize($value);
                    if (strlen($serialized) < 10240) { // 10KB limit
                        if (is_array($value)) {
                            $this->snapshot[$key] = unserialize($serialized);
                        } else {
                            $this->snapshot[$key] = $value;
                        }
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Restore session from snapshot
     * Uses session locking to prevent race conditions
     * 
     * @return bool True if restore was successful
     */
    public function restore(): bool
    {
        if (!$this->sessionWasActive) {
            return false;
        }
        
        if (empty($this->snapshot)) {
            return false;
        }
        
        // Session locking: Close current session to release lock
        $sessionWasActive = session_status() === PHP_SESSION_ACTIVE;
        if ($sessionWasActive) {
            session_write_close(); // Release lock
        }
        
        // Restart session with exclusive lock
        if (!SessionHelper::ensureStarted()) {
            return false;
        }
        
        try {
            // Clear current session
            $_SESSION = [];
            
            // Restore snapshot
            foreach ($this->snapshot as $key => $value) {
                if (is_array($value)) {
                    $_SESSION[$key] = unserialize(serialize($value));
                } else {
                    $_SESSION[$key] = $value;
                }
            }
            
            // Ensure critical variables are set
            if (isset($this->snapshot['last_activity'])) {
                $_SESSION['last_activity'] = time(); // Update to prevent timeout
            }
            
            // Write session changes immediately to persist
            session_write_close();
            
            // Reopen session for continued use
            SessionHelper::ensureStarted();
            
            return true;
            
        } catch (Throwable $e) {
            // On error, try to restore session state
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            SessionHelper::ensureStarted();
            
            // Log error only in debug mode
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("SessionManager::restore error: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Get session snapshot (read-only)
     * 
     * @return array Session snapshot
     */
    public function getSnapshot(): array
    {
        return $this->snapshot;
    }
    
    /**
     * Check if session was active before backup
     * 
     * @return bool
     */
    public function wasActive(): bool
    {
        return $this->sessionWasActive;
    }
    
    /**
     * Clear snapshot (free memory)
     */
    public function clear(): void
    {
        $this->snapshot = [];
        $this->sessionWasActive = false;
    }
}


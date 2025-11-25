<?php
/**
 * Request rate limiting backed by SQLite.
 * 
 * Provides rate limiting functionality to prevent abuse and brute force attacks.
 * Uses SQLite database for persistent storage of rate limit state.
 * 
 * @package App\Lib
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/../Constants/AppConstants.php';

class RateLimit
{
    // ===== ERR-024 FIX: Magic numbers replaced with constants =====
    private const WINDOW_SECONDS = AppConstants::HOUR; // 3600 seconds = 1 hour
    private const CLEANUP_INTERVAL = 75;
    private const CLEANUP_GRACE_SECONDS = 7200; // 2 hours

    private static int $writeCounter = 0;

    /**
     * Check if request is within rate limit
     * 
     * @param string $key Rate limit key (usually IP address or user identifier)
     * @param int $maxAttempts Maximum number of attempts allowed (default: 5)
     * @param int $blockDuration Block duration in seconds (default: 5 minutes)
     * @return bool True if request is allowed, false if rate limit exceeded
     */
    public static function check($key, $maxAttempts = AppConstants::RATE_LIMIT_LOGIN_ATTEMPTS, $blockDuration = AppConstants::RATE_LIMIT_LOGIN_WINDOW)
    {
        $rateKey = self::buildKey($key);
        $pdo = self::pdo();
        $now = time();

        $state = self::fetchState($pdo, $rateKey);
        if (!$state) {
            return true;
        }

        $state = self::normalizeState($pdo, $rateKey, $state, $now);
        if ($state === null) {
            return true;
        }

        return $state['blocked_until'] === null;
    }

    /**
     * Record a rate limit attempt
     * 
     * @param string $key Rate limit key
     * @param int $maxAttempts Maximum number of attempts allowed
     * @param int $blockDuration Block duration in seconds
     * @return bool True if request is allowed, false if blocked
     */
    public static function recordAttempt($key, $maxAttempts = AppConstants::RATE_LIMIT_LOGIN_ATTEMPTS, $blockDuration = AppConstants::RATE_LIMIT_LOGIN_WINDOW)
    {
        $rateKey = self::buildKey($key);
        $pdo = self::pdo();
        $now = time();
        $windowStart = $now - self::WINDOW_SECONDS;

        $pdo->beginTransaction();

        try {
            $state = self::fetchState($pdo, $rateKey);

            $attempts = 0;
            $firstAttemptAt = null;
            $blockedUntil = null;

            if ($state) {
                $attempts = (int)$state['attempts'];
                $firstAttemptAt = $state['first_attempt_at'] !== null ? (int)$state['first_attempt_at'] : null;
                $blockedUntil = $state['blocked_until'] !== null ? (int)$state['blocked_until'] : null;

                if ($blockedUntil !== null && $blockedUntil > $now) {
                    $pdo->rollBack();
                    return false;
                }

                if ($firstAttemptAt === null || $firstAttemptAt < $windowStart) {
                    $attempts = 0;
                    $firstAttemptAt = null;
                }
            }

            $attempts++;
            if ($firstAttemptAt === null) {
                $firstAttemptAt = $now;
            }

            $blockedUntil = $attempts >= $maxAttempts ? $now + $blockDuration : null;

            self::upsertState($pdo, $rateKey, $attempts, $firstAttemptAt, $blockedUntil);

            $pdo->commit();

            self::safeCleanup($pdo, $now);

            return $blockedUntil === null;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get remaining attempts for a rate limit key
     * 
     * @param string $key Rate limit key
     * @param int $maxAttempts Maximum number of attempts allowed
     * @return int Number of remaining attempts
     */
    public static function getRemainingAttempts($key, $maxAttempts = AppConstants::RATE_LIMIT_LOGIN_ATTEMPTS)
    {
        $rateKey = self::buildKey($key);
        $pdo = self::pdo();
        $now = time();

        $state = self::fetchState($pdo, $rateKey);
        if (!$state) {
            return $maxAttempts;
        }

        $state = self::normalizeState($pdo, $rateKey, $state, $now);
        if ($state === null) {
            return $maxAttempts;
        }

        if ($state['blocked_until'] !== null) {
            return 0;
        }

        return max(0, $maxAttempts - (int)$state['attempts']);
    }

    public static function getBlockTimeRemaining($key)
    {
        $rateKey = self::buildKey($key);
        $pdo = self::pdo();
        $now = time();

        $state = self::fetchState($pdo, $rateKey);
        if (!$state) {
            return 0;
        }

        $state = self::normalizeState($pdo, $rateKey, $state, $now);
        if ($state === null || $state['blocked_until'] === null) {
            return 0;
        }

        return max(0, (int)$state['blocked_until'] - $now);
    }

    public static function clear($key)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare('DELETE FROM rate_limits WHERE rate_key = ?');
        $stmt->execute([self::buildKey($key)]);
    }

    private static function buildKey($key)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return $ip . ':' . (string)$key;
    }

    private static function pdo(): PDO
    {
        return Database::getInstance()->getPdo();
    }

    private static function fetchState(PDO $pdo, string $rateKey): ?array
    {
        $stmt = $pdo->prepare('SELECT attempts, first_attempt_at, blocked_until FROM rate_limits WHERE rate_key = ?');
        $stmt->execute([$rateKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'attempts' => (int)$row['attempts'],
            'first_attempt_at' => $row['first_attempt_at'] !== null ? (int)$row['first_attempt_at'] : null,
            'blocked_until' => $row['blocked_until'] !== null ? (int)$row['blocked_until'] : null,
        ];
    }

    private static function normalizeState(PDO $pdo, string $rateKey, array $state, int $now): ?array
    {
        $windowStart = $now - self::WINDOW_SECONDS;

        $attempts = (int)$state['attempts'];
        $firstAttemptAt = $state['first_attempt_at'] !== null ? (int)$state['first_attempt_at'] : null;
        $blockedUntil = $state['blocked_until'] !== null ? (int)$state['blocked_until'] : null;

        $dirty = false;

        if ($blockedUntil !== null && $blockedUntil <= $now) {
            $blockedUntil = null;
            $dirty = true;
        }

        if ($firstAttemptAt === null || $firstAttemptAt < $windowStart) {
            if ($attempts !== 0 || $firstAttemptAt !== null) {
                $attempts = 0;
                $firstAttemptAt = null;
                $dirty = true;
            }
        }

        if ($attempts === 0 && $firstAttemptAt === null && $blockedUntil === null) {
            self::deleteState($pdo, $rateKey);
            return null;
        }

        if ($dirty) {
            self::upsertState($pdo, $rateKey, $attempts, $firstAttemptAt, $blockedUntil);
        }

        return [
            'attempts' => $attempts,
            'first_attempt_at' => $firstAttemptAt,
            'blocked_until' => $blockedUntil,
        ];
    }

    private static function upsertState(PDO $pdo, string $rateKey, int $attempts, ?int $firstAttemptAt, ?int $blockedUntil): void
    {
        $stmt = $pdo->prepare('INSERT INTO rate_limits (rate_key, attempts, first_attempt_at, blocked_until) VALUES (:key, :attempts, :first_at, :blocked_until) ON CONFLICT(rate_key) DO UPDATE SET attempts = excluded.attempts, first_attempt_at = excluded.first_attempt_at, blocked_until = excluded.blocked_until');
        $stmt->execute([
            ':key' => $rateKey,
            ':attempts' => $attempts,
            ':first_at' => $firstAttemptAt,
            ':blocked_until' => $blockedUntil,
        ]);
    }

    private static function deleteState(PDO $pdo, string $rateKey): void
    {
        $stmt = $pdo->prepare('DELETE FROM rate_limits WHERE rate_key = ?');
        $stmt->execute([$rateKey]);
    }

    private static function safeCleanup(PDO $pdo, int $now): void
    {
        try {
            self::maybeCleanup($pdo, $now);
        } catch (Throwable $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('RateLimit cleanup failed: ' . $e->getMessage());
            }
        }
    }

    private static function maybeCleanup(PDO $pdo, int $now): void
    {
        if (++self::$writeCounter < self::CLEANUP_INTERVAL) {
            return;
        }

        self::$writeCounter = 0;

        $threshold = $now - (self::WINDOW_SECONDS + self::CLEANUP_GRACE_SECONDS);
        $stmt = $pdo->prepare('DELETE FROM rate_limits WHERE (blocked_until IS NULL AND (first_attempt_at IS NULL OR first_attempt_at < :threshold)) OR (blocked_until IS NOT NULL AND blocked_until < :threshold)');
        $stmt->execute([':threshold' => $threshold]);
    }
}

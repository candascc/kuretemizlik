<?php

/**
 * JWT Authentication Library
 */
class JWTAuth
{
    private static $algorithm = 'HS256';
    private static $expirationTime = 86400; // 24 hours

    private static function getSecretKeys(): array
    {
        // ===== ERR-006 FIX: Validate JWT secret =====
        try {
            $primary = InputSanitizer::getEnvApiKey('JWT_SECRET', 32, true);
        } catch (Exception $e) {
            error_log('CRITICAL: JWT_SECRET validation failed: ' . $e->getMessage());
            throw new Exception('JWT_SECRET environment variable is required and must be at least 32 characters. Please configure in env.local file.');
        }
        
        $previous = InputSanitizer::getEnvApiKey('JWT_SECRET_PREVIOUS', 32, false);
        return $previous ? [$primary, $previous] : [$primary];
        // ===== ERR-006 FIX: End =====
    }

    private static function getTtl(): int
    {
        $ttl = (int)($_ENV['JWT_TTL'] ?? self::$expirationTime);
        return $ttl > 0 ? $ttl : 3600; // default to 1h if misconfigured
    }

    /**
     * Generate JWT token
     */
    public static function generateToken($userOrPayload, $userData = [])
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);

        // Backward compatibility: accept either (userId, userData) or (payloadArray)
        if (is_array($userOrPayload) && empty($userData)) {
            $basePayload = $userOrPayload;
        } else {
            $basePayload = [
                'user_id' => $userOrPayload,
                'data' => $userData,
            ];
        }

        $now = time();
        $ttl = self::getTtl();
        $payloadArr = array_merge([
            'iat' => $now,
            'exp' => $now + $ttl,
        ], $basePayload);

        $payload = json_encode($payloadArr);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $secret = self::getSecretKeys()[0];
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    /**
     * Verify JWT token
     */
    public static function verifyToken($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        // Try primary then previous secret for rotation compatibility
        $valid = false;
        foreach (self::getSecretKeys() as $secret) {
            $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
            $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            if (hash_equals($expectedSignature, $base64Signature)) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            return false;
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
        
        if (!$payload || $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    /**
     * Get user from token
     */
    public static function getUserFromToken($token)
    {
        $payload = self::verifyToken($token);
        if (!$payload) {
            return null;
        }

        $db = Database::getInstance();
        return $db->fetch('SELECT * FROM users WHERE id = ?', [$payload['user_id']]);
    }

    /**
     * Refresh token
     */
    public static function refreshToken($token)
    {
        $payload = self::verifyToken($token);
        if (!$payload) {
            return false;
        }

        // If payload already has user_id, reuse it; otherwise resolve from DB
        $userId = $payload['user_id'] ?? null;
        if ($userId === null) {
            $user = self::getUserFromToken($token);
            if (!$user) {
                return false;
            }
            $userId = $user['id'];
        }

        return self::generateToken([
            'user_id' => $userId,
            'data' => $payload['data'] ?? [],
        ]);
    }

    /**
     * Extract token from Authorization header or query param.
     */
    public static function getTokenFromRequest(): ?string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? null;
        if (is_string($auth) && stripos($auth, 'Bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        if (!empty($_GET['token'])) {
            return (string)$_GET['token'];
        }
        if (!empty($_POST['token'])) {
            return (string)$_POST['token'];
        }
        return null;
    }

    /**
     * Authenticate request and return payload or null.
     */
    public static function authenticate(): ?array
    {
        $token = self::getTokenFromRequest();
        if (!$token) {
            return null;
        }
        $payload = self::verifyToken($token);
        return $payload ?: null;
    }

    /**
     * Require valid token; returns payload or sends 401 JSON and exits.
     */
    public static function require(): array
    {
        $payload = self::authenticate();
        if (!$payload) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Token required or invalid']);
            exit;
        }
        return $payload;
    }
}
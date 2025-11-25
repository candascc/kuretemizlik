<?php
/**
 * API v2 Authentication Controller
 * Handles JWT-based authentication for mobile/API clients
 */

namespace App\Controllers\Api\V2;

class AuthController
{
    /**
     * Login and get JWT token
     */
    public function login()
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'message' => 'Username and password required']);
            return;
        }
        
        // ===== ERR-012 FIX: Rate limiting for API login =====
        require_once __DIR__ . '/../../../Lib/ApiRateLimiter.php';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitKey = 'api.v2.auth.login:' . strtolower($username) . ':' . $ipAddress;
        if (!\ApiRateLimiter::check($rateLimitKey, 5, 300)) {
            http_response_code(429);
            echo json_encode(['error' => 'Too Many Requests', 'message' => 'Too many login attempts. Please try again later.']);
            return;
        }
        // ===== ERR-012 FIX: End =====
        
        $db = \Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE username = ? AND is_active = 1", [$username]);
        
        $passwordHash = (string)($user['password_hash'] ?? '');
        if (!$user || empty($passwordHash) || !password_verify($password, $passwordHash)) {
            // ===== ERR-012 FIX: Record failed login attempt =====
            \ApiRateLimiter::record($rateLimitKey, 5, 300);
            // ===== ERR-012 FIX: End =====
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Invalid credentials']);
            return;
        }
        
        // ===== ERR-012 FIX: Clear rate limit on successful login =====
        require_once __DIR__ . '/../../../Lib/ApiRateLimiter.php';
        if (class_exists('ApiRateLimiter')) {
            \ApiRateLimiter::reset($rateLimitKey);
        }
        // ===== ERR-012 FIX: End =====
        
        // ===== ERR-014 FIX: Rehash password if needed (upgrade old hashes) =====
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash) {
                try {
                    $db->update('users', 
                        ['password_hash' => $newHash, 'updated_at' => date('Y-m-d H:i:s')],
                        'id = ?',
                        [$user['id']]
                    );
                } catch (Exception $e) {
                    // Log but don't fail login if rehash update fails
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Password rehash failed for user {$user['id']}: " . $e->getMessage());
                    }
                }
            }
        }
        // ===== ERR-014 FIX: End =====
        
        // Generate JWT token
        $token = \JWTAuth::generateToken([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]);
        
        \Logger::info('API v2 login successful', ['user_id' => $user['id'], 'username' => $username]);
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'expires_in' => 86400,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);
    }
    
    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        header('Content-Type: application/json');
        
        $token = \JWTAuth::getTokenFromRequest();
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Token required']);
            return;
        }
        
        $newToken = \JWTAuth::refreshToken($token);
        
        if (!$newToken) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Invalid token']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'token' => $newToken,
            'expires_in' => 86400
        ]);
    }
    
    /**
     * Verify token
     */
    public function verify()
    {
        header('Content-Type: application/json');
        
        $payload = \JWTAuth::authenticate();
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Invalid token']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'valid' => true,
            'payload' => $payload
        ]);
    }
}


<?php

class CalendarSync
{
    public $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getByUserProvider(int $userId, string $provider): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM calendar_sync WHERE user_id=? AND provider=?",
            [$userId, $provider]
        ) ?: null;
    }

    public function refreshToken(int $userId, string $provider): bool
    {
        $row = $this->getByUserProvider($userId, $provider);
        if (!$row || !$row['refresh_token']) {
            return false;
        }
        $newToken = self::fetchRefreshedToken($provider, $row['refresh_token']);
        if (!$newToken) {
            return false;
        }
        $this->db->update('calendar_sync', [
            'access_token' => $newToken['access_token'],
            'refresh_token' => $newToken['refresh_token'] ?? $row['refresh_token'],
            'token_expires_at' => time() + max(0, (int)($newToken['expires_in'] ?? 3600) - 60),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $row['id']]);
        return true;
    }

    public static function fetchRefreshedToken(string $provider, string $refreshToken): ?array
    {
        // ===== ERR-006 FIX: Validate OAuth credentials =====
        if ($provider === 'microsoft') {
            $clientId = InputSanitizer::getEnvApiKey('MS_CLIENT_ID', 16, false);
            $clientSecret = InputSanitizer::getEnvApiKey('MS_CLIENT_SECRET', 16, false);
            $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        } else {
            $clientId = InputSanitizer::getEnvApiKey('GOOGLE_CLIENT_ID', 16, false);
            $clientSecret = InputSanitizer::getEnvApiKey('GOOGLE_CLIENT_SECRET', 16, false);
            $url = 'https://oauth2.googleapis.com/token';
        }
        
        if (!$clientId || !$clientSecret) {
            return null;
        }
        // ===== ERR-006 FIX: End =====
        $post = http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_TIMEOUT => 10
        ]);
        $resp = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http >= 400) {
            return null;
        }
        return json_decode($resp, true) ?: null;
    }

    public function getValidAccessToken(int $userId, string $provider): ?string
    {
        $row = $this->getByUserProvider($userId, $provider);
        if (!$row) {
            return null;
        }
        $expires = (int)($row['token_expires_at'] ?? 0);
        if ($expires <= time()) {
            if (!$this->refreshToken($userId, $provider)) {
                return null;
            }
            $row = $this->getByUserProvider($userId, $provider);
        }
        return $row['access_token'] ?? null;
    }

    public function updateCursor(int $userId, string $provider, ?string $cursor): void
    {
        $this->db->update('calendar_sync', ['sync_cursor' => $cursor, 'updated_at' => date('Y-m-d H:i:s')], 'user_id = :uid AND provider = :p', ['uid' => $userId, 'p' => $provider]);
    }

    public function getCursor(int $userId, string $provider): ?string
    {
        $row = $this->getByUserProvider($userId, $provider);
        return $row['sync_cursor'] ?? null;
    }
}


<?php

class OAuthController
{
    public function google()
    {
        if (!Auth::check()) { redirect('/login'); }
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? null;
        $redirect = base_url('/oauth/google/callback');
        if (!$clientId) {
            View::renderWithLayout('errors/simple.php', [
                'title' => 'Google OAuth yapılandırılmamış',
                'message' => 'Lütfen GOOGLE_CLIENT_ID ve GOOGLE_CLIENT_SECRET ortam değişkenlerini ayarlayın.'
            ]);
            return;
        }
        $scope = urlencode('https://www.googleapis.com/auth/calendar');
        $state = bin2hex(random_bytes(12));
        $_SESSION['oauth_state'] = $state;
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth'
            . '?response_type=code'
            . '&client_id=' . urlencode($clientId)
            . '&redirect_uri=' . urlencode($redirect)
            . '&scope=' . $scope
            . '&access_type=offline'
            . '&include_granted_scopes=true'
            . '&state=' . $state;
        redirect($authUrl);
    }

    public function googleCallback()
    {
        if (!Auth::check()) { redirect('/login'); }
        $state = $_GET['state'] ?? '';
        if (!$state || ($state !== ($_SESSION['oauth_state'] ?? ''))) {
            View::notFound('Geçersiz OAuth durumu');
            return;
        }
        unset($_SESSION['oauth_state']);
        $code = $_GET['code'] ?? null;
        if (!$code) {
            View::notFound('Kod alınamadı');
            return;
        }
        // ===== ERR-006 FIX: Validate OAuth credentials =====
        $clientId = InputSanitizer::getEnvApiKey('GOOGLE_CLIENT_ID', 16, false);
        $clientSecret = InputSanitizer::getEnvApiKey('GOOGLE_CLIENT_SECRET', 16, false);
        $redirect = base_url('/oauth/google/callback');
        if (!$clientId || !$clientSecret) {
            set_flash('error', 'Google OAuth yapılandırılmamış. Lütfen yöneticinizle iletişime geçin.');
            redirect('/settings');
        }
        // ===== ERR-006 FIX: End =====
        // Token alma isteği (gerçek değişim)
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $post = http_build_query([
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirect,
            'grant_type' => 'authorization_code'
        ]);
        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_TIMEOUT => 20
        ]);
        $resp = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false || $http >= 400) {
            set_flash('error', 'Google token alınamadı: ' . ($err ?: ('HTTP ' . $http))); redirect('/settings');
        }
        $data = json_decode($resp, true) ?: [];
        $access = $data['access_token'] ?? null;
        $refresh = $data['refresh_token'] ?? null; // ilk yetkilendirmede gelir
        $expiresIn = (int)($data['expires_in'] ?? 0);
        if (!$access) { set_flash('error', 'Geçersiz token yanıtı'); redirect('/settings'); }
        $db = Database::getInstance();
        $existing = $db->fetch("SELECT id FROM calendar_sync WHERE user_id=? AND provider='google'", [Auth::id()]);
        $payload = [
            'user_id' => Auth::id(),
            'provider' => 'google',
            'access_token' => $access,
            'refresh_token' => $refresh,
            'token_expires_at' => time() + max(0, $expiresIn - 60),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        if ($existing) {
            unset($payload['user_id'], $payload['provider']);
            $db->update('calendar_sync', $payload, 'user_id = :uid AND provider = :p', ['uid' => Auth::id(), 'p' => 'google']);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $db->insert('calendar_sync', $payload);
        }
        set_flash('success', 'Google hesabı başarıyla bağlandı.');
        redirect('/settings/calendar');
    }

    public function microsoft()
    {
        if (!Auth::check()) { redirect('/login'); }
        // ===== ERR-006 FIX: Validate OAuth credentials =====
        $clientId = InputSanitizer::getEnvApiKey('MS_CLIENT_ID', 16, false);
        $redirect = base_url('/oauth/microsoft/callback');
        if (!$clientId) {
            View::renderWithLayout('errors/simple.php', [
                'title' => 'Microsoft OAuth yapılandırılmamış',
                'message' => 'Lütfen MS_CLIENT_ID ve MS_CLIENT_SECRET ortam değişkenlerini ayarlayın.'
            ]);
            return;
        }
        // ===== ERR-006 FIX: End =====
        $scope = urlencode('offline_access Calendars.ReadWrite');
        $state = bin2hex(random_bytes(12));
        $_SESSION['oauth_state'] = $state;
        $authUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize'
            . '?response_type=code'
            . '&client_id=' . urlencode($clientId)
            . '&redirect_uri=' . urlencode($redirect)
            . '&scope=' . $scope
            . '&state=' . $state;
        redirect($authUrl);
    }

    public function microsoftCallback()
    {
        if (!Auth::check()) { redirect('/login'); }
        $state = $_GET['state'] ?? '';
        if (!$state || ($state !== ($_SESSION['oauth_state'] ?? ''))) {
            View::notFound('Geçersiz OAuth durumu');
            return;
        }
        unset($_SESSION['oauth_state']);
        $code = $_GET['code'] ?? null;
        if (!$code) {
            View::notFound('Kod alınamadı');
            return;
        }
        // ===== ERR-006 FIX: Validate OAuth credentials =====
        $clientId = InputSanitizer::getEnvApiKey('MS_CLIENT_ID', 16, false);
        $clientSecret = InputSanitizer::getEnvApiKey('MS_CLIENT_SECRET', 16, false);
        $redirect = base_url('/oauth/microsoft/callback');
        if (!$clientId || !$clientSecret) {
            set_flash('error', 'Microsoft OAuth yapılandırılmamış.');
            redirect('/settings');
        }
        // ===== ERR-006 FIX: End =====
        $tokenUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        $post = http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirect
        ]);
        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_TIMEOUT => 20
        ]);
        $resp = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false || $http >= 400) { set_flash('error', 'Microsoft token alınamadı: '.($err?:('HTTP '.$http))); redirect('/settings'); }
        $data = json_decode($resp, true) ?: [];
        $access = $data['access_token'] ?? null;
        $refresh = $data['refresh_token'] ?? null;
        $expiresIn = (int)($data['expires_in'] ?? 0);
        if (!$access) { set_flash('error', 'Geçersiz token yanıtı'); redirect('/settings'); }
        $db = Database::getInstance();
        $existing = $db->fetch("SELECT id FROM calendar_sync WHERE user_id=? AND provider='microsoft'", [Auth::id()]);
        $payload = [
            'user_id' => Auth::id(),
            'provider' => 'microsoft',
            'access_token' => $access,
            'refresh_token' => $refresh,
            'token_expires_at' => time() + max(0, $expiresIn - 60),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        if ($existing) {
            unset($payload['user_id'], $payload['provider']);
            $db->update('calendar_sync', $payload, 'user_id = :uid AND provider = :p', ['uid' => Auth::id(), 'p' => 'microsoft']);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $db->insert('calendar_sync', $payload);
        }
        set_flash('success', 'Microsoft hesabı başarıyla bağlandı.');
        redirect('/settings/calendar');
    }
}



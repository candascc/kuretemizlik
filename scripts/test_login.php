<?php

$baseUrl = $argv[1] ?? 'http://localhost/app';
$baseUrl = rtrim($baseUrl, '/');

$envAdminPassword = getenv('ADMIN_PASSWORD');
if (!is_string($envAdminPassword) || $envAdminPassword === '') {
    $envAdminPassword = getenv('DEFAULT_ADMIN_PASSWORD');
}
if (!is_string($envAdminPassword) || $envAdminPassword === '') {
    $envAdminPassword = null;
}

$envAdminUsername = getenv('ADMIN_USERNAME');
if (!is_string($envAdminUsername) || $envAdminUsername === '') {
    $envAdminUsername = null;
}

$defaultPassword = $envAdminPassword ?? 'SecureAdmin2025!';
$username = $argv[2] ?? $envAdminUsername ?? 'candas';
$password = $argv[3] ?? $defaultPassword;

if (getenv('TEST_LOGIN_DEBUG')) {
    echo "[debug] using credentials username={$username} password={$password}\n";
}
if (getenv('TEST_LOGIN_DEBUG')) {
    echo "[info] attempting login with username={$username}\n";
}

function request(string $url, array $opts = []): array {
    $ch = curl_init($url);
    $defaultOpts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
    ];
    if (stripos($url, 'https://') === 0) {
        $defaultOpts[CURLOPT_SSL_VERIFYPEER] = false;
        $defaultOpts[CURLOPT_SSL_VERIFYHOST] = false;
    }
    curl_setopt_array($ch, $opts + $defaultOpts);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException($error ?: 'cURL exec failed');
    }
    $info = curl_getinfo($ch);
    curl_close($ch);

    $header = substr($raw, 0, $info['header_size']);
    $body = substr($raw, $info['header_size']);
    return [$header, $body, $info];
}

[$header, $body] = request($baseUrl . '/login');

echo "=== GET Headers ===\n";
echo $header . "\n";

echo "=== First 500 bytes of Body ===\n";
echo substr($body, 0, 500) . "\n\n";

if (!preg_match('/name="csrf_token"\s+value="([^"]+)"/i', $body, $matches)) {
    fwrite(STDERR, "Could not extract csrf_token" . PHP_EOL);
    exit(1);
}

$csrfToken = $matches[1];

echo "GET csrf_token=$csrfToken\n";

preg_match_all('/^Set-Cookie:\s*([^\r\n]+)/mi', $header, $cookieMatches);
$cookieJar = [];
foreach ($cookieMatches[1] ?? [] as $rawCookie) {
    $parts = explode(';', $rawCookie);
    $kv = explode('=', trim($parts[0]), 2);
    if (count($kv) === 2) {
        $cookieJar[$kv[0]] = $kv[1];
    }
}
$cookieHeader = implode('; ', array_map(fn ($name) => $name . '=' . $cookieJar[$name], array_keys($cookieJar)));

echo "Set-Cookie header (raw):\n";
foreach ($cookieMatches[1] ?? [] as $rawCookie) {
    echo "  $rawCookie\n";
}

echo "Cookie header for POST: $cookieHeader\n";

$postFields = http_build_query([
    'username' => $username,
    'password' => $password,
    'csrf_token' => $csrfToken,
]);

[$postHeader, $postBody, $postInfo] = request($baseUrl . '/login', [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_HTTPHEADER => array_filter([
        $cookieHeader ? 'Cookie: ' . $cookieHeader : null,
        'Content-Type: application/x-www-form-urlencoded',
    ]),
]);

$redirect = $postInfo['redirect_url'] ?? null;
if (!$redirect && preg_match('/^Location:\s*([^\r\n]+)/mi', $postHeader, $locMatch)) {
    $redirect = trim($locMatch[1]);
}

if ($redirect) {
    echo "Redirect Location: {$redirect}\n";
    preg_match_all('/^Set-Cookie:\s*([^\r\n]+)/mi', $postHeader, $postSetCookies);
    foreach ($postSetCookies[1] ?? [] as $rawCookie) {
        $parts = explode(';', $rawCookie);
        $kv = explode('=', trim($parts[0]), 2);
        if (count($kv) === 2) {
            $cookieJar[$kv[0]] = $kv[1];
        }
    }
    $redirectCookies = implode('; ', array_map(fn ($name) => $name . '=' . $cookieJar[$name], array_keys($cookieJar)));

    $redirectUrl = $redirect;
    if (!preg_match('#^https?://#i', $redirectUrl)) {
        $parsed = parse_url($baseUrl);
        $origin = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
        $redirectUrl = rtrim($origin . $redirect, '/');
    }

    [$finalHeader, $finalBody, $finalInfo] = request($redirectUrl, [
        CURLOPT_HTTPHEADER => array_filter([
            $redirectCookies ? 'Cookie: ' . $redirectCookies : null,
        ]),
    ]);

    echo "=== Redirected Headers ===\n";
    echo $finalHeader . "\n";
    echo "Final status={$finalInfo['http_code']}\n";
    echo "Final body preview:\n" . substr($finalBody, 0, 400) . "\n";

    if ($finalInfo['http_code'] >= 400 || str_contains($redirectUrl, '/login')) {
        fwrite(STDERR, "Login did not reach dashboard (redirect={$redirectUrl})" . PHP_EOL);
        exit(1);
    }

    echo "Login flow succeeded (user={$username})." . PHP_EOL;
    exit(0);
}

echo "=== POST Headers ===\n";
echo $postHeader . "\n";
echo "POST status={$postInfo['http_code']}\n";
echo "POST response body:\n$postBody\n";

if ($postInfo['http_code'] !== 302) {
    fwrite(STDERR, "Login failed, expected redirect to dashboard." . PHP_EOL);
    exit(1);
}

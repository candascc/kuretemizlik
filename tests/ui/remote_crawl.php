<?php
/**
 * Remote Crawl Client Script
 * 
 * PATH_CRAWL_REMOTE_V1: Execute crawl remotely via web API
 * 
 * Usage:
 *   php tests/ui/remote_crawl.php [type] [base_url] [username] [password]
 * 
 * Type: sysadmin or admin (default: sysadmin)
 * 
 * This script calls the remote crawl endpoint via HTTP POST
 */

// Get parameters
$crawlType = $argv[1] ?? 'sysadmin';
$baseUrl = $argv[2] ?? null;
$username = $argv[3] ?? null;
$password = $argv[4] ?? null;

// Validate type
if (!in_array($crawlType, ['sysadmin', 'admin'], true)) {
    fwrite(STDERR, "Error: Type must be 'sysadmin' or 'admin'\n");
    exit(1);
}

// Determine API endpoint
if (!$baseUrl) {
    // Try to detect from environment or use default
    $baseUrl = getenv('KUREAPP_BASE_URL') ?: 'https://www.kuretemizlik.com/app';
}
$baseUrl = rtrim($baseUrl, '/');
$apiUrl = $baseUrl . '/sysadmin/remote-crawl';

// Build request data
$requestData = [
    'type' => $crawlType,
];

if ($baseUrl) {
    $requestData['base_url'] = $baseUrl;
}

if ($username) {
    $requestData['username'] = $username;
}

if ($password) {
    $requestData['password'] = $password;
}

echo "=== PATH_CRAWL_REMOTE_V1: Remote Crawl Execution ===\n";
echo "API URL: {$apiUrl}\n";
echo "Type: {$crawlType}\n";
if ($username) {
    echo "Username: {$username}\n";
}
echo "\n";

// Make HTTP POST request
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    fwrite(STDERR, "Error: cURL failed: {$curlError}\n");
    exit(1);
}

if ($httpCode !== 200) {
    fwrite(STDERR, "Error: HTTP {$httpCode}\n");
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['error'])) {
        fwrite(STDERR, "Message: {$errorData['error']}\n");
    } else {
        fwrite(STDERR, "Response: {$response}\n");
    }
    exit(1);
}

$result = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    fwrite(STDERR, "Error: Invalid JSON response: " . json_last_error_msg() . "\n");
    fwrite(STDERR, "Response: {$response}\n");
    exit(1);
}

if (!$result['success']) {
    fwrite(STDERR, "Error: " . ($result['error'] ?? 'Unknown error') . "\n");
    if (isset($result['file'])) {
        fwrite(STDERR, "File: {$result['file']}:{$result['line']}\n");
    }
    exit(1);
}

$crawlResult = $result['result'] ?? [];

// Display results
echo "Crawl completed successfully!\n\n";
echo "=== SUMMARY ===\n";
echo "Total URLs: " . ($crawlResult['total_count'] ?? 0) . "\n";
echo "Success: " . ($crawlResult['success_count'] ?? 0) . "\n";
echo "Errors: " . ($crawlResult['error_count'] ?? 0) . "\n\n";

if (isset($crawlResult['error'])) {
    echo "=== ERROR ===\n";
    echo $crawlResult['error'] . "\n\n";
}

if (isset($crawlResult['items']) && !empty($crawlResult['items'])) {
    echo "=== URL DETAILS ===\n";
    foreach ($crawlResult['items'] as $item) {
        $url = $item['url'] ?? '';
        $status = $item['status'] ?? 0;
        $hasError = $item['has_error'] ?? false;
        $hasMarker = $item['has_marker'] ?? false ? 'YES' : 'NO';
        $depth = $item['depth'] ?? 0;
        $note = $item['note'] ?? '';
        
        $depthStr = $depth > 0 ? " [depth={$depth}]" : " [seed]";
        
        if ($hasError) {
            echo "GET {$url}{$depthStr}... ERROR (status={$status})";
            if ($note) {
                echo " - {$note}";
            }
            echo "\n";
        } else {
            echo "GET {$url}{$depthStr}... OK (status={$status}, marker={$hasMarker})\n";
        }
    }
    echo "\n";
}

// Save full result to file
$outputFile = __DIR__ . '/../../logs/remote_crawl_' . date('Y-m-d_H-i-s') . '.json';
$outputDir = dirname($outputFile);
if (!is_dir($outputDir)) {
    @mkdir($outputDir, 0755, true);
}
file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Full result saved to: {$outputFile}\n";

exit(0);


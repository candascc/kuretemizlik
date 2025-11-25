<?php
/**
 * Test Results Dashboard Generator
 * Generates HTML visualization of test results
 */

$appDir = __DIR__ . '/..';
chdir($appDir);

$outputDir = __DIR__ . '/test_outputs';
$dashboardFile = __DIR__ . '/test_dashboard.html';

// Read test results if available
$resultsFile = $outputDir . '/results.json';
$results = [];

if (file_exists($resultsFile)) {
    $results = json_decode(file_get_contents($resultsFile), true) ?? [];
}

// Calculate statistics
$total = count($results);
$passed = 0;
$failed = 0;
$errors = 0;
$notFound = 0;
$noTests = 0;

foreach ($results as $result) {
    switch ($result['status'] ?? 'UNKNOWN') {
        case 'PASS':
            $passed++;
            break;
        case 'FAIL':
            $failed++;
            break;
        case 'ERROR':
            $errors++;
            break;
        case 'NOT_FOUND':
            $notFound++;
            break;
        case 'NO_TESTS':
            $noTests++;
            break;
    }
}

$successRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

// Generate HTML dashboard
$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Results Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
        }
        .stat-card.passed .value { color: #28a745; }
        .stat-card.failed .value { color: #dc3545; }
        .stat-card.errors .value { color: #ffc107; }
        .stat-card.total .value { color: #007bff; }
        .progress-bar {
            background: #e9ecef;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .test-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .test-item {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .test-item:last-child {
            border-bottom: none;
        }
        .test-item.passed {
            background: #d4edda;
        }
        .test-item.failed {
            background: #f8d7da;
        }
        .test-item.error {
            background: #fff3cd;
        }
        .test-name {
            flex: 1;
            font-weight: 500;
        }
        .test-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .test-status.pass {
            background: #28a745;
            color: white;
        }
        .test-status.fail {
            background: #dc3545;
            color: white;
        }
        .test-status.error {
            background: #ffc107;
            color: #333;
        }
        .test-details {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Results Dashboard</h1>
        
        <div class="stats">
            <div class="stat-card total">
                <h3>Total Tests</h3>
                <div class="value">{$total}</div>
            </div>
            <div class="stat-card passed">
                <h3>Passed</h3>
                <div class="value">{$passed}</div>
            </div>
            <div class="stat-card failed">
                <h3>Failed</h3>
                <div class="value">{$failed}</div>
            </div>
            <div class="stat-card errors">
                <h3>Errors</h3>
                <div class="value">{$errors}</div>
            </div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: {$successRate}%">
                {$successRate}%
            </div>
        </div>
        
        <div class="test-list">
HTML;

foreach ($results as $testFile => $result) {
    $status = strtolower($result['status'] ?? 'unknown');
    $testName = basename($testFile);
    $tests = $result['tests'] ?? 0;
    $assertions = $result['assertions'] ?? 0;
    $failures = $result['failures'] ?? 0;
    $errors = $result['errors'] ?? 0;
    
    $html .= <<<HTML
            <div class="test-item {$status}">
                <div>
                    <div class="test-name">{$testName}</div>
                    <div class="test-details">
                        Tests: {$tests} | Assertions: {$assertions}
                        {($failures > 0 ? " | Failures: {$failures}" : "")}
                        {($errors > 0 ? " | Errors: {$errors}" : "")}
                    </div>
                </div>
                <div class="test-status {$status}">{$result['status']}</div>
            </div>
HTML;
}

$html .= <<<HTML
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666; font-size: 14px;">
            Generated: {$date = date('Y-m-d H:i:s')}
        </div>
    </div>
</body>
</html>
HTML;

file_put_contents($dashboardFile, $html);

echo "Dashboard generated: {$dashboardFile}\n";
echo "Total: {$total} | Passed: {$passed} | Failed: {$failed} | Errors: {$errors}\n";
echo "Success Rate: {$successRate}%\n";


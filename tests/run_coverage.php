<?php
/**
 * Coverage Reporting Script
 * Runs all tests with coverage and generates HTML and JSON reports
 */

$appDir = __DIR__ . '/..';
chdir($appDir);

echo "========================================\n";
echo "Running Tests with Coverage\n";
echo "========================================\n\n";

// Check if Xdebug is available
if (!extension_loaded('xdebug') && !extension_loaded('pcov')) {
    echo "WARNING: Xdebug or PCOV extension is required for code coverage.\n";
    echo "Install Xdebug or PCOV to generate coverage reports.\n\n";
    echo "Running tests without coverage...\n\n";
    $coverageFlag = '';
} else {
    $coverageFlag = '--coverage-html tests/coverage --coverage-text';
}

// Run PHPUnit with coverage
$command = "php vendor/bin/phpunit {$coverageFlag} --configuration phpunit.xml 2>&1";
echo "Command: {$command}\n\n";

$output = shell_exec($command);
echo $output;

// Generate JSON coverage report if Xdebug/PCOV is available
if (extension_loaded('xdebug') || extension_loaded('pcov')) {
    $jsonCommand = "php vendor/bin/phpunit --coverage-json tests/coverage/coverage.json --configuration phpunit.xml 2>&1";
    shell_exec($jsonCommand);
    
    // Check coverage threshold (80%)
    if (file_exists('tests/coverage/coverage.json')) {
        $coverageData = json_decode(file_get_contents('tests/coverage/coverage.json'), true);
        if (isset($coverageData['totals']['lines']['percent'])) {
            $coverage = $coverageData['totals']['lines']['percent'];
            echo "\n========================================\n";
            echo "Coverage Summary\n";
            echo "========================================\n";
            echo "Line Coverage: {$coverage}%\n";
            
            if ($coverage >= 80) {
                echo "Status: PASS (>= 80%)\n";
            } else {
                echo "Status: FAIL (< 80%)\n";
                echo "Target: 80%\n";
            }
        }
    }
}

echo "\nCoverage report saved to: tests/coverage/\n";
echo "HTML report: tests/coverage/index.html\n";


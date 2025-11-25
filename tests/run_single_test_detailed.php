<?php
/**
 * Single Test Runner with Full Output
 * Runs a single test file and captures complete output
 */

if ($argc < 2) {
    echo "Usage: php run_single_test_detailed.php <test_file_path>\n";
    exit(1);
}

$testFile = $argv[1];
$appDir = __DIR__ . '/..';

if (!file_exists($appDir . '/' . $testFile)) {
    echo "Error: Test file not found: {$testFile}\n";
    exit(1);
}

chdir($appDir);

echo "========================================\n";
echo "Running: {$testFile}\n";
echo "========================================\n\n";

// Run PHPUnit with full output
$command = "php vendor/bin/phpunit \"{$testFile}\" --no-configuration 2>&1";
$output = shell_exec($command);

// Display full output
echo $output;

// Save to file
$outputFile = __DIR__ . '/test_outputs/' . str_replace(['/', '\\'], '_', $testFile) . '.txt';
$outputDir = dirname($outputFile);
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}
file_put_contents($outputFile, $output);

echo "\n========================================\n";
echo "Output saved to: {$outputFile}\n";
echo "========================================\n";


<?php
/**
 * Parallel Test Execution Script
 * Runs tests in parallel for faster execution
 */

$appDir = __DIR__ . '/..';
chdir($appDir);

echo "========================================\n";
echo "Running Tests in Parallel\n";
echo "========================================\n\n";

// Check if parallel execution is available
$phpunitVersion = shell_exec('php vendor/bin/phpunit --version 2>&1');
echo "PHPUnit Version: {$phpunitVersion}\n\n";

// Get number of CPU cores
$cores = 1;
if (function_exists('sys_getloadavg')) {
    // Try to detect CPU cores
    if (PHP_OS_FAMILY === 'Windows') {
        $cores = (int)shell_exec('echo %NUMBER_OF_PROCESSORS%');
    } else {
        $cores = (int)shell_exec('nproc 2>/dev/null || echo 1');
    }
}
$cores = max(1, min($cores, 4)); // Limit to 4 processes max

echo "Detected CPU cores: {$cores}\n";
echo "Using {$cores} parallel processes\n\n";

// Run tests in parallel
$startTime = microtime(true);

$command = "php vendor/bin/phpunit --configuration phpunit.xml --processes={$cores} 2>&1";
echo "Command: {$command}\n\n";

$output = shell_exec($command);
echo $output;

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "\n========================================\n";
echo "Parallel Execution Summary\n";
echo "========================================\n";
echo "Duration: {$duration} seconds\n";
echo "Processes: {$cores}\n";

// Compare with sequential (if available)
echo "\nNote: Run 'php tests/run_all_tests_one_by_one.php' for sequential execution comparison\n";


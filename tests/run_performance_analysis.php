<?php
/**
 * Test Performance Analysis Script
 * Identifies slow tests and performance bottlenecks
 */

$appDir = __DIR__ . '/..';
chdir($appDir);

require_once __DIR__ . '/bootstrap.php';

$testSuites = ['Fast', 'Slow', 'Stress', 'Load'];
$results = [];
$slowTests = [];
$totalTime = 0;

echo "========================================\n";
echo "TEST PERFORMANCE ANALYSIS\n";
echo "========================================\n\n";

foreach ($testSuites as $suite) {
    echo "Running {$suite} suite...\n";
    $startTime = microtime(true);
    
    $command = "php vendor/bin/phpunit --testsuite={$suite} --configuration phpunit.xml 2>&1";
    exec($command, $output, $returnCode);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    $totalTime += $duration;
    
    // Parse output for individual test times
    $testTimes = [];
    foreach ($output as $line) {
        if (preg_match('/(\d+\.\d+)s.*?::\s*(.+)/', $line, $matches)) {
            $testTime = (float)$matches[1];
            $testName = trim($matches[2]);
            if ($testTime > 1.0) { // Tests taking more than 1 second
                $slowTests[] = [
                    'suite' => $suite,
                    'test' => $testName,
                    'time' => $testTime,
                ];
            }
            $testTimes[] = $testTime;
        }
    }
    
    $results[$suite] = [
        'duration' => $duration,
        'test_count' => count($testTimes),
        'avg_time' => count($testTimes) > 0 ? round(array_sum($testTimes) / count($testTimes), 2) : 0,
        'max_time' => count($testTimes) > 0 ? round(max($testTimes), 2) : 0,
        'min_time' => count($testTimes) > 0 ? round(min($testTimes), 2) : 0,
    ];
    
    echo "  Duration: {$duration}s\n";
    echo "  Tests: " . $results[$suite]['test_count'] . "\n";
    echo "  Avg: " . $results[$suite]['avg_time'] . "s\n";
    echo "  Max: " . $results[$suite]['max_time'] . "s\n\n";
}

echo "========================================\n";
echo "PERFORMANCE SUMMARY\n";
echo "========================================\n\n";
echo "Total Execution Time: " . round($totalTime, 2) . "s\n";
echo "Slow Tests (>1s): " . count($slowTests) . "\n\n";

if (!empty($slowTests)) {
    echo "Slow Tests:\n";
    echo "-----------\n";
    usort($slowTests, function($a, $b) {
        return $b['time'] <=> $a['time'];
    });
    foreach (array_slice($slowTests, 0, 20) as $test) {
        echo sprintf("  [%s] %s: %.2fs\n", $test['suite'], $test['test'], $test['time']);
    }
    echo "\n";
}

// Save results
$report = [
    'generated_at' => date('Y-m-d H:i:s'),
    'total_time' => round($totalTime, 2),
    'suites' => $results,
    'slow_tests' => $slowTests,
];

file_put_contents(__DIR__ . '/PERFORMANCE_REPORT.json', json_encode($report, JSON_PRETTY_PRINT));
echo "Results saved to: tests/PERFORMANCE_REPORT.json\n";





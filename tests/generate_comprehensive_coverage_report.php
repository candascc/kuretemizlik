<?php
/**
 * Comprehensive Coverage Report Generator
 * Generates per-test coverage analysis and detailed report
 */

$appDir = __DIR__ . '/..';
chdir($appDir);

require_once __DIR__ . '/bootstrap.php';

echo "========================================\n";
echo "COMPREHENSIVE COVERAGE ANALYSIS\n";
echo "========================================\n\n";

// Test suites to analyze
$testSuites = [
    'Phase 1' => 'Phase 1',
    'Phase 2' => 'Phase 2',
    'Phase 4' => 'Phase 4',
    'Fast' => 'Fast',
    'Slow' => 'Slow',
    'Stress' => 'Stress',
    'Load' => 'Load',
];

$coverageDir = __DIR__ . '/coverage';
if (!is_dir($coverageDir)) {
    mkdir($coverageDir, 0755, true);
}

$allCoverageData = [];
$totalFiles = 0;
$totalLines = 0;
$totalCoveredLines = 0;
$totalClasses = 0;
$totalCoveredClasses = 0;
$totalMethods = 0;
$totalCoveredMethods = 0;

foreach ($testSuites as $suiteName => $suiteValue) {
    echo "Analyzing suite: {$suiteName}...\n";
    
    $suiteCoverageDir = $coverageDir . '/' . strtolower(str_replace(' ', '_', $suiteName));
    if (!is_dir($suiteCoverageDir)) {
        mkdir($suiteCoverageDir, 0755, true);
    }
    
    // Run coverage for this suite
    $command = "phpdbg -qrr -d output_buffering=1 -d implicit_flush=0 vendor/bin/phpunit --configuration phpunit.xml --testsuite \"{$suiteValue}\" --coverage-html {$suiteCoverageDir} --coverage-clover {$suiteCoverageDir}/clover.xml 2>&1";
    
    echo "  Running: {$suiteName}...\n";
    $output = shell_exec($command);
    
    // Parse clover.xml for coverage data
    $cloverFile = $suiteCoverageDir . '/clover.xml';
    if (file_exists($cloverFile)) {
        $xml = simplexml_load_file($cloverFile);
        if ($xml) {
            $metrics = $xml->project->metrics[0];
            $files = (int)$metrics['files'];
            $lines = (int)$metrics['loc'];
            $coveredLines = (int)$metrics['coveredstatements'];
            $classes = (int)$metrics['classes'];
            $coveredClasses = (int)$metrics['coveredclasses'];
            $methods = (int)$metrics['methods'];
            $coveredMethods = (int)$metrics['coveredmethods'];
            
            $lineCoverage = $lines > 0 ? round(($coveredLines / $lines) * 100, 2) : 0;
            $classCoverage = $classes > 0 ? round(($coveredClasses / $classes) * 100, 2) : 0;
            $methodCoverage = $methods > 0 ? round(($coveredMethods / $methods) * 100, 2) : 0;
            
            $allCoverageData[$suiteName] = [
                'files' => $files,
                'lines' => $lines,
                'covered_lines' => $coveredLines,
                'line_coverage' => $lineCoverage,
                'classes' => $classes,
                'covered_classes' => $coveredClasses,
                'class_coverage' => $classCoverage,
                'methods' => $methods,
                'covered_methods' => $coveredMethods,
                'method_coverage' => $methodCoverage,
            ];
            
            $totalFiles += $files;
            $totalLines += $lines;
            $totalCoveredLines += $coveredLines;
            $totalClasses += $classes;
            $totalCoveredClasses += $coveredClasses;
            $totalMethods += $methods;
            $totalCoveredMethods += $coveredMethods;
            
            echo "  ✓ {$suiteName}: Lines {$lineCoverage}%, Classes {$classCoverage}%, Methods {$methodCoverage}%\n";
        }
    } else {
        echo "  ⚠ No coverage data for {$suiteName}\n";
    }
}

// Generate comprehensive report
$reportFile = $coverageDir . '/comprehensive_coverage_report.md';
$report = "# Comprehensive Test Coverage Report\n\n";
$report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
$report .= "## Summary\n\n";
$report .= "| Metric | Total | Covered | Coverage % |\n";
$report .= "|--------|-------|---------|------------|\n";

$overallLineCoverage = $totalLines > 0 ? round(($totalCoveredLines / $totalLines) * 100, 2) : 0;
$overallClassCoverage = $totalClasses > 0 ? round(($totalCoveredClasses / $totalClasses) * 100, 2) : 0;
$overallMethodCoverage = $totalMethods > 0 ? round(($totalCoveredMethods / $totalMethods) * 100, 2) : 0;

$report .= "| Files | {$totalFiles} | - | - |\n";
$report .= "| Lines | {$totalLines} | {$totalCoveredLines} | {$overallLineCoverage}% |\n";
$report .= "| Classes | {$totalClasses} | {$totalCoveredClasses} | {$overallClassCoverage}% |\n";
$report .= "| Methods | {$totalMethods} | {$totalCoveredMethods} | {$overallMethodCoverage}% |\n\n";

$report .= "## Per-Suite Coverage\n\n";
$report .= "| Suite | Files | Lines | Line Coverage | Classes | Class Coverage | Methods | Method Coverage |\n";
$report .= "|-------|-------|-------|---------------|---------|----------------|---------|-----------------|\n";

foreach ($allCoverageData as $suiteName => $data) {
    $report .= sprintf(
        "| %s | %d | %d | %.2f%% | %d | %.2f%% | %d | %.2f%% |\n",
        $suiteName,
        $data['files'],
        $data['lines'],
        $data['line_coverage'],
        $data['classes'],
        $data['class_coverage'],
        $data['methods'],
        $data['method_coverage']
    );
}

$report .= "\n## Coverage Reports\n\n";
foreach ($testSuites as $suiteName => $suiteValue) {
    $suiteDir = strtolower(str_replace(' ', '_', $suiteName));
    $report .= "- **{$suiteName}**: [HTML Report](coverage/{$suiteDir}/index.html) | [Clover XML](coverage/{$suiteDir}/clover.xml)\n";
}

file_put_contents($reportFile, $report);

echo "\n========================================\n";
echo "Coverage Report Generated\n";
echo "========================================\n";
echo "Report: {$reportFile}\n";
echo "Overall Coverage: Lines {$overallLineCoverage}%, Classes {$overallClassCoverage}%, Methods {$overallMethodCoverage}%\n";
echo "\n";





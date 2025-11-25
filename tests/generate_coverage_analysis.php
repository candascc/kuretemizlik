<?php
/**
 * Alternative Coverage Analysis
 * Analyzes test files to identify which source files are tested
 * Works without Xdebug/PCOV extension
 */

$appDir = __DIR__ . '/..';
$srcDir = $appDir . '/src';
$testsDir = __DIR__;

// Get all PHP files in src
function getSourceFiles(string $dir, array &$files = []): array
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            // Skip Views and Console
            if (strpos($relativePath, 'Views') === false && strpos($relativePath, 'Console') === false) {
                $files[] = $relativePath;
            }
        }
    }
    
    return $files;
}

// Get all test files
function getTestFiles(string $dir): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getFilename(), 'Test.php') !== false) {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

// Analyze test file to find which classes it tests
function analyzeTestFile(string $testFile): array
{
    $content = file_get_contents($testFile);
    $testedClasses = [];
    
    // Look for require_once or use statements
    preg_match_all('/require_once\s+[^\s]+\/([^\/\s]+)\.php/', $content, $requires);
    preg_match_all('/use\s+([A-Za-z0-9_\\\]+);/', $content, $uses);
    
    // Extract class names from requires
    foreach ($requires[1] ?? [] as $class) {
        if (strpos($class, 'Test') === false) {
            $testedClasses[] = $class;
        }
    }
    
    // Extract class names from use statements (App namespace)
    foreach ($uses[1] ?? [] as $use) {
        if (strpos($use, 'App\\') === 0 || strpos($use, 'Tests\\') === false) {
            $parts = explode('\\', $use);
            $className = end($parts);
            if (strpos($className, 'Test') === false && strpos($className, 'Factory') === false) {
                $testedClasses[] = $className;
            }
        }
    }
    
    return array_unique($testedClasses);
}

echo "Generating coverage analysis...\n\n";

$sourceFiles = getSourceFiles($srcDir);
$testFiles = getTestFiles($testsDir);

echo "Found " . count($sourceFiles) . " source files\n";
echo "Found " . count($testFiles) . " test files\n\n";

$testedFiles = [];
$untestedFiles = [];

foreach ($testFiles as $testFile) {
    $tested = analyzeTestFile($testFile);
    foreach ($tested as $class) {
        // Try to find corresponding source file
        foreach ($sourceFiles as $sourceFile) {
            if (strpos($sourceFile, $class . '.php') !== false || basename($sourceFile, '.php') === $class) {
                $testedFiles[$sourceFile] = true;
            }
        }
    }
}

foreach ($sourceFiles as $sourceFile) {
    if (!isset($testedFiles[$sourceFile])) {
        $untestedFiles[] = $sourceFile;
    }
}

$coverage = count($testedFiles) / count($sourceFiles) * 100;

echo "Coverage Analysis Results:\n";
echo "==========================\n\n";
echo "Total Source Files: " . count($sourceFiles) . "\n";
echo "Tested Files: " . count($testedFiles) . "\n";
echo "Untested Files: " . count($untestedFiles) . "\n";
echo "Estimated Coverage: " . number_format($coverage, 2) . "%\n\n";

if (!empty($untestedFiles)) {
    echo "Untested Files:\n";
    echo "---------------\n";
    foreach (array_slice($untestedFiles, 0, 50) as $file) {
        echo "- {$file}\n";
    }
    if (count($untestedFiles) > 50) {
        echo "... and " . (count($untestedFiles) - 50) . " more\n";
    }
}

// Save results
$results = [
    'total_files' => count($sourceFiles),
    'tested_files' => count($testedFiles),
    'untested_files' => count($untestedFiles),
    'coverage_percentage' => round($coverage, 2),
    'untested_file_list' => $untestedFiles,
    'generated_at' => date('Y-m-d H:i:s'),
];

file_put_contents($testsDir . '/coverage_analysis.json', json_encode($results, JSON_PRETTY_PRINT));
echo "\nResults saved to: tests/coverage_analysis.json\n";











<?php
/**
 * Check if coverage extension is available
 * Alternative coverage analysis if Xdebug/PCOV not available
 */

echo "Checking coverage extension availability...\n\n";

// Check for Xdebug
if (extension_loaded('xdebug')) {
    echo "✓ Xdebug is loaded\n";
    $xdebugVersion = phpversion('xdebug');
    echo "  Version: {$xdebugVersion}\n";
    
    if (function_exists('xdebug_info')) {
        $info = xdebug_info('mode');
        echo "  Mode: " . (isset($info['mode']) ? implode(', ', $info['mode']) : 'unknown') . "\n";
    }
    
    if (ini_get('xdebug.mode')) {
        echo "  xdebug.mode: " . ini_get('xdebug.mode') . "\n";
    }
    
    echo "\n✓ Coverage is available via Xdebug\n";
    exit(0);
}

// Check for PCOV
if (extension_loaded('pcov')) {
    echo "✓ PCOV is loaded\n";
    $pcovVersion = phpversion('pcov');
    echo "  Version: {$pcovVersion}\n";
    echo "\n✓ Coverage is available via PCOV\n";
    exit(0);
}

// No coverage extension found
echo "✗ No coverage extension found (Xdebug or PCOV)\n\n";
echo "Options:\n";
echo "1. Install Xdebug: https://xdebug.org/docs/install\n";
echo "2. Install PCOV: https://github.com/krakjoe/pcov\n";
echo "3. Use alternative coverage analysis (file-based)\n\n";

echo "Creating alternative coverage analysis script...\n";
echo "This will analyze test files to identify which source files are tested.\n";

exit(1);











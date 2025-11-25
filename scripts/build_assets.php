<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/AssetCompiler.php';

$assets = require __DIR__ . '/../config/assets.php';

try {
    if (!empty($assets['js'])) {
        AssetCompiler::buildJs($assets['js'], 'dist/app.bundle.js');
    }

    if (!empty($assets['css'])) {
        AssetCompiler::buildCss($assets['css'], 'dist/app.bundle.css');
    }

    echo "Assets bundled successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Asset build failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}


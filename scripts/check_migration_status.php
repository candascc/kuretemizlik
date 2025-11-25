<?php
require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/Lib/Database.php';
require __DIR__ . '/../src/Lib/MigrationManager.php';

$status = MigrationManager::status();
echo "Total migrations: " . $status['total'] . PHP_EOL;
echo "Executed: " . $status['executed'] . PHP_EOL;
echo "Pending: " . $status['pending'] . PHP_EOL . PHP_EOL;

echo "Pending migrations:" . PHP_EOL;
foreach ($status['migrations'] as $m) {
    if ($m['status'] === 'pending') {
        echo "  - " . $m['migration'] . PHP_EOL;
    }
}


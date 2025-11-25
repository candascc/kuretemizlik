#!/usr/bin/env php
<?php

declare(strict_types=1);

$options = getopt('', [
    'source::',
    'target::',
    'dry-run',
    'truncate',
    'help',
]);

if (isset($options['help'])) {
    echo <<<TXT
Usage: php bin/legacy-import.php [--source=/path/to/legacy.sqlite] [--target=./db/app.sqlite] [--dry-run] [--truncate]

Options:
  --source    Legacy SQLite dosya yolu (varsayılan: ../eskiler/.../app/db/app.sqlite)
  --target    Yeni uygulama SQLite dosya yolu (varsayılan: ./db/app.sqlite)
  --dry-run   Hiçbir değişiklik yapmadan planlanan işlemleri ve satır sayılarını raporlar
  --truncate  Insert öncesinde hedef tablolardaki verileri temizler
  --help      Bu iletiyi gösterir

TXT;
    exit(0);
}

$appRoot = realpath(__DIR__ . '/..');
$workspaceRoot = $appRoot ? realpath($appRoot . '/..') : null;
$globalRoot = $workspaceRoot ? realpath($workspaceRoot . '/..') : null;
$defaultSource = $globalRoot ? $globalRoot . '/eskiler/kuretemizlik.com1/kuretemizlik.com/app/db/app.sqlite' : '';
$defaultTarget = $appRoot ? $appRoot . '/db/app.sqlite' : '';

$sourcePath = $options['source'] ?? ($defaultSource ?: '');
$targetPath = $options['target'] ?? ($defaultTarget ?: '');
$dryRun = array_key_exists('dry-run', $options);
$truncate = array_key_exists('truncate', $options);

if (!$sourcePath || !is_file($sourcePath)) {
    fwrite(STDERR, "Legacy database not found. Provide --source=/absolute/path/to/app.sqlite\n");
    exit(1);
}

if (!$targetPath) {
    fwrite(STDERR, "Target database path missing. Provide --target path.\n");
    exit(1);
}

if (!is_file($targetPath)) {
    fwrite(STDERR, "Target database not found at {$targetPath}\n");
    exit(1);
}

$mappings = [
    [
        'name' => 'users',
        'source_table' => 'users',
        'truncate' => true,
        'columns' => [
            'id' => 'id',
            'username' => 'username',
            'password_hash' => 'password_hash',
            'role' => 'role',
            'is_active' => 'is_active',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'two_factor_secret' => 'NULL',
            'two_factor_backup_codes' => 'NULL',
            'two_factor_enabled_at' => 'NULL',
            'two_factor_required' => '0',
            'email' => 'NULL',
            'company_id' => '1',
        ],
    ],
    [
        'name' => 'customers',
        'source_table' => 'customers',
        'truncate' => true,
        'columns' => [
            'id' => 'id',
            'name' => 'name',
            'phone' => 'phone',
            'email' => 'email',
            'notes' => 'notes',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ],
    ],
    [
        'name' => 'addresses',
        'source_table' => 'addresses',
        'truncate' => true,
        'columns' => [
            'id' => 'id',
            'customer_id' => 'customer_id',
            'label' => 'label',
            'line' => 'line',
            'city' => 'city',
            'created_at' => 'created_at',
        ],
    ],
    [
        'name' => 'services',
        'source_table' => 'services',
        'truncate' => true,
        'columns' => [
            'id' => 'id',
            'name' => 'name',
            'duration_min' => 'duration_min',
            'default_fee' => 'default_fee',
            'is_active' => 'is_active',
            'created_at' => 'created_at',
        ],
    ],
    [
        'name' => 'jobs',
        'source_table' => 'jobs',
        'truncate' => true,
        'columns' => [
            'id' => 'id',
            'service_id' => 'service_id',
            'customer_id' => 'customer_id',
            'address_id' => 'address_id',
            'start_at' => 'start_at',
            'end_at' => 'end_at',
            'status' => 'status',
            'total_amount' => 'total_amount',
            'amount_paid' => 'amount_paid',
            'payment_status' => 'payment_status',
            'assigned_to' => 'assigned_to',
            'note' => 'note',
            'income_id' => 'income_id',
            'created_at' => 'created_at',
            'updated_at' => 'COALESCE(updated_at, created_at)',
            'recurring_job_id' => 'NULL',
            'occurrence_id' => 'NULL',
            'reminder_sent' => '0',
        ],
    ],
    [
        'name' => 'job_payments',
        'source_table' => 'job_payments',
        'truncate' => true,
        'columns' => [
            'id' => 'id',
            'job_id' => 'job_id',
            'amount' => 'amount',
            'paid_at' => 'paid_at',
            'note' => 'note',
            'finance_id' => 'finance_id',
            'created_at' => 'COALESCE(created_at, paid_at)',
            'updated_at' => 'COALESCE(updated_at, paid_at)',
        ],
    ],
    [
        'name' => 'money_entries',
        'source_table' => 'money_entries',
        'truncate' => true,
        'columns' => [
            'id' => 'id',
            'kind' => 'kind',
            'category' => 'category',
            'amount' => 'amount',
            'date' => 'date',
            'note' => 'note',
            'job_id' => 'job_id',
            'created_by' => 'created_by',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'recurring_job_id' => 'NULL',
            'is_archived' => '0',
        ],
    ],
    [
        'name' => 'rate_limits',
        'source_table' => 'rate_limits',
        'truncate' => true,
        'columns' => [
            'rate_key' => 'rate_key',
            'attempts' => 'attempts',
            'first_attempt_at' => 'first_attempt_at',
            'blocked_until' => 'blocked_until',
        ],
    ],
    [
        'name' => 'activity_log',
        'source_table' => 'activity_log',
        'truncate' => true,
        'columns' => [
            'id' => 'id',
            'actor_id' => 'actor_id',
            'action' => 'action',
            'entity' => 'entity',
            'meta_json' => 'meta_json',
            'created_at' => 'created_at',
        ],
    ],
];

printf("Legacy DB : %s\n", $sourcePath);
printf("Target DB : %s\n", $targetPath);
printf("Dry run   : %s\n", $dryRun ? 'yes' : 'no');
printf("Truncate  : %s\n\n", $truncate ? 'yes' : 'no');

$pdo = new PDO('sqlite:' . $targetPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec('PRAGMA foreign_keys = OFF');
$pdo->exec('ATTACH DATABASE ' . $pdo->quote($sourcePath) . ' AS legacy');

if ($dryRun) {
    foreach ($mappings as $mapping) {
        $table = $mapping['name'];
        $sourceTable = $mapping['source_table'];
        $legacyCount = (int)$pdo->query("SELECT COUNT(*) FROM legacy.{$sourceTable}")->fetchColumn();
        $targetCount = (int)$pdo->query("SELECT COUNT(*) FROM main.{$table}")->fetchColumn();
        printf("[DRY-RUN] %s legacy=%d target=%d columns=%d\n", $table, $legacyCount, $targetCount, count($mapping['columns']));
    }
    $pdo->exec('DETACH DATABASE legacy');
    $pdo->exec('PRAGMA foreign_keys = ON');
    exit(0);
}

$pdo->beginTransaction();

try {
    foreach ($mappings as $mapping) {
        $table = $mapping['name'];
        $sourceTable = $mapping['source_table'];
        $columns = $mapping['columns'];
        $shouldTruncate = $truncate && ($mapping['truncate'] ?? false);

        if ($shouldTruncate) {
            $pdo->exec("DELETE FROM main.{$table}");
            $pdo->exec("DELETE FROM sqlite_sequence WHERE name = {$pdo->quote($table)}");
        }

        $targetColumns = implode(', ', array_keys($columns));
        $selectColumns = implode(', ', array_values($columns));
        $sql = "INSERT INTO main.{$table} ({$targetColumns}) SELECT {$selectColumns} FROM legacy.{$sourceTable}";
        $inserted = $pdo->exec($sql);

        printf("Migrated %-15s rows=%d\n", $table, $inserted ?? 0);
    }

    $pdo->commit();
    echo "\nMigration completed successfully.\n";
} catch (Throwable $exception) {
    $pdo->rollBack();
    fwrite(STDERR, "Migration failed: {$exception->getMessage()}\n");
    exit(1);
} finally {
    $pdo->exec('DETACH DATABASE legacy');
    $pdo->exec('PRAGMA foreign_keys = ON');
}


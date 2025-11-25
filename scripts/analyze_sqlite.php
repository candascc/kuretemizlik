<?php

declare(strict_types=1);

$options = getopt('', ['db:', 'profile:']);
$dbPath = $options['db'] ?? null;
$profile = $options['profile'] ?? 'legacy';

if ($dbPath === null) {
    fwrite(STDERR, "Missing --db path\n");
    exit(1);
}

if (!is_file($dbPath)) {
    fwrite(STDERR, "Database file not found: {$dbPath}\n");
    exit(1);
}

$profiles = [
    'legacy' => [
        'scalar' => [
            'users' => ['sql' => 'SELECT COUNT(*) FROM users', 'tables' => ['users']],
            'customers' => ['sql' => 'SELECT COUNT(*) FROM customers', 'tables' => ['customers']],
            'addresses' => ['sql' => 'SELECT COUNT(*) FROM addresses', 'tables' => ['addresses']],
            'services' => ['sql' => 'SELECT COUNT(*) FROM services', 'tables' => ['services']],
            'jobs' => ['sql' => 'SELECT COUNT(*) FROM jobs', 'tables' => ['jobs']],
            'job_payments' => ['sql' => 'SELECT COUNT(*) FROM job_payments', 'tables' => ['job_payments']],
            'money_entries' => ['sql' => 'SELECT COUNT(*) FROM money_entries', 'tables' => ['money_entries']],
            'appointments' => ['sql' => 'SELECT COUNT(*) FROM appointments', 'tables' => ['appointments']],
            'payments' => ['sql' => 'SELECT COUNT(*) FROM payments', 'tables' => ['payments']],
            'rate_limits' => ['sql' => 'SELECT COUNT(*) FROM rate_limits', 'tables' => ['rate_limits']],
            'activity_log' => ['sql' => 'SELECT COUNT(*) FROM activity_log', 'tables' => ['activity_log']],
            'jobs_missing_start' => ['sql' => "SELECT COUNT(*) FROM jobs WHERE start_at IS NULL OR TRIM(start_at) = ''", 'tables' => ['jobs']],
            'jobs_missing_end' => ['sql' => "SELECT COUNT(*) FROM jobs WHERE end_at IS NULL OR TRIM(end_at) = ''", 'tables' => ['jobs']],
            'jobs_amount_mismatch' => ['sql' => 'SELECT COUNT(*) FROM jobs WHERE amount_paid > total_amount', 'tables' => ['jobs']],
            'appointments_missing_dates' => ['sql' => "SELECT COUNT(*) FROM appointments WHERE start_at IS NULL OR TRIM(start_at) = ''", 'tables' => ['appointments']],
            'payments_non_positive' => ['sql' => 'SELECT COUNT(*) FROM payments WHERE amount <= 0', 'tables' => ['payments']],
        ],
        'pairs' => [
            'jobs_status_distribution' => ['sql' => 'SELECT status AS label, COUNT(*) AS value FROM jobs GROUP BY status ORDER BY status', 'tables' => ['jobs']],
            'appointments_status_distribution' => ['sql' => 'SELECT status AS label, COUNT(*) AS value FROM appointments GROUP BY status ORDER BY status', 'tables' => ['appointments']],
            'payments_method_distribution' => ['sql' => 'SELECT payment_method AS label, COUNT(*) AS value FROM payments GROUP BY payment_method ORDER BY payment_method', 'tables' => ['payments']],
        ],
    ],
    'new' => [
        'scalar' => [
            'users' => ['sql' => 'SELECT COUNT(*) FROM users', 'tables' => ['users']],
            'customers' => ['sql' => 'SELECT COUNT(*) FROM customers', 'tables' => ['customers']],
            'addresses' => ['sql' => 'SELECT COUNT(*) FROM addresses', 'tables' => ['addresses']],
            'services' => ['sql' => 'SELECT COUNT(*) FROM services', 'tables' => ['services']],
            'jobs' => ['sql' => 'SELECT COUNT(*) FROM jobs', 'tables' => ['jobs']],
            'job_payments' => ['sql' => 'SELECT COUNT(*) FROM job_payments', 'tables' => ['job_payments']],
            'money_entries' => ['sql' => 'SELECT COUNT(*) FROM money_entries', 'tables' => ['money_entries']],
            'appointments' => ['sql' => 'SELECT COUNT(*) FROM appointments', 'tables' => ['appointments']],
            'payments_table_exists' => ['sql' => "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='payments'"],
            'buildings' => ['sql' => 'SELECT COUNT(*) FROM buildings', 'tables' => ['buildings']],
            'units' => ['sql' => 'SELECT COUNT(*) FROM units', 'tables' => ['units']],
            'management_fees' => ['sql' => 'SELECT COUNT(*) FROM management_fees', 'tables' => ['management_fees']],
            'resident_users' => ['sql' => 'SELECT COUNT(*) FROM resident_users', 'tables' => ['resident_users']],
            'staff' => ['sql' => 'SELECT COUNT(*) FROM staff', 'tables' => ['staff']],
            'jobs_missing_updated' => ['sql' => "SELECT COUNT(*) FROM jobs WHERE updated_at IS NULL OR TRIM(updated_at) = ''", 'tables' => ['jobs']],
            'addresses_missing_updated' => ['sql' => "SELECT COUNT(*) FROM addresses WHERE updated_at IS NULL OR TRIM(updated_at) = ''", 'tables' => ['addresses']],
            'money_entries_missing_created_by' => ['sql' => 'SELECT COUNT(*) FROM money_entries WHERE created_by IS NULL', 'tables' => ['money_entries']],
        ],
        'pairs' => [
            'jobs_status_distribution' => ['sql' => 'SELECT status AS label, COUNT(*) AS value FROM jobs GROUP BY status ORDER BY status', 'tables' => ['jobs']],
            'appointments_status_distribution' => ['sql' => 'SELECT status AS label, COUNT(*) AS value FROM appointments GROUP BY status ORDER BY status', 'tables' => ['appointments']],
            'management_fee_status_distribution' => ['sql' => 'SELECT status AS label, COUNT(*) AS value FROM management_fees GROUP BY status ORDER BY status', 'tables' => ['management_fees']],
        ],
    ],
];

if (!isset($profiles[$profile])) {
    fwrite(STDERR, "Unknown profile: {$profile}\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$existingTables = [];
$tablesStmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
if ($tablesStmt) {
    while ($row = $tablesStmt->fetch(PDO::FETCH_ASSOC)) {
        $existingTables[$row['name']] = true;
    }
}

$result = [
    'profile' => $profile,
    'db' => realpath($dbPath),
    'generated_at' => date(DATE_ATOM),
    'scalar' => [],
    'pairs' => [],
    'warnings' => [],
];

$runScalar = function (PDO $pdo, string $sql): ?int {
    $stmt = $pdo->query($sql);
    if (!$stmt) {
        return null;
    }
    $value = $stmt->fetchColumn();
    return ($value === false) ? null : (int)$value;
};

$runPairs = function (PDO $pdo, string $sql): array {
    $stmt = $pdo->query($sql);
    $pairs = [];
    if (!$stmt) {
        return $pairs;
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pairs[] = [
            'label' => $row['label'] ?? null,
            'value' => isset($row['value']) ? (int)$row['value'] : null,
        ];
    }
    return $pairs;
};

foreach ($profiles[$profile]['scalar'] as $label => $definition) {
    $sql = $definition['sql'];
    $requiredTables = $definition['tables'] ?? [];
    $missing = array_filter($requiredTables, static fn ($table) => !isset($existingTables[$table]));
    if ($missing) {
        $result['scalar'][$label] = null;
        $result['warnings'][$label] = 'Missing tables: ' . implode(',', $missing);
        continue;
    }
    try {
        $result['scalar'][$label] = $runScalar($pdo, $sql);
    } catch (PDOException $exception) {
        $result['scalar'][$label] = null;
        $result['warnings'][$label] = $exception->getMessage();
    }
}

foreach ($profiles[$profile]['pairs'] as $label => $definition) {
    $sql = $definition['sql'];
    $requiredTables = $definition['tables'] ?? [];
    $missing = array_filter($requiredTables, static fn ($table) => !isset($existingTables[$table]));
    if ($missing) {
        $result['pairs'][$label] = [];
        $result['warnings'][$label] = 'Missing tables: ' . implode(',', $missing);
        continue;
    }
    try {
        $result['pairs'][$label] = $runPairs($pdo, $sql);
    } catch (PDOException $exception) {
        $result['pairs'][$label] = [];
        $result['warnings'][$label] = $exception->getMessage();
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;


<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/Database.php';
require_once __DIR__ . '/../src/Lib/Auth.php';
require_once __DIR__ . '/../src/Models/RecurringJob.php';
require_once __DIR__ . '/../src/Models/RecurringOccurrence.php';
require_once __DIR__ . '/../src/Models/Job.php';
require_once __DIR__ . '/../src/Services/RecurringGenerator.php';

// Emulate an ADMIN session scoped to company 1
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'ADMIN';
$_SESSION['company_id'] = 1;
$_SESSION['login_time'] = time();

$id = isset($argv[1]) ? (int)$argv[1] : 2;

try {
    $db = Database::getInstance();

    $beforeOcc = (int)($db->fetch("SELECT COUNT(*) AS c FROM recurring_job_occurrences WHERE recurring_job_id = ?", [$id])['c'] ?? 0);
    $beforeJobs = (int)($db->fetch("SELECT COUNT(*) AS c FROM jobs WHERE recurring_job_id = ?", [$id])['c'] ?? 0);

    $occ = RecurringGenerator::generateForJob($id, 30);
    $jobs = RecurringGenerator::materializeToJobs($id);

    $afterOcc = (int)($db->fetch("SELECT COUNT(*) AS c FROM recurring_job_occurrences WHERE recurring_job_id = ?", [$id])['c'] ?? 0);
    $afterJobs = (int)($db->fetch("SELECT COUNT(*) AS c FROM jobs WHERE recurring_job_id = ?", [$id])['c'] ?? 0);

    echo json_encode([
        'ok' => true,
        'recurring_job_id' => $id,
        'generated' => ['occurrences' => $occ, 'jobs' => $jobs],
        'counts' => [
            'occurrences' => ['before' => $beforeOcc, 'after' => $afterOcc],
            'jobs' => ['before' => $beforeJobs, 'after' => $afterJobs],
        ]
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}



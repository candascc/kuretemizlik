<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/Database.php';
require_once __DIR__ . '/../src/Models/RecurringJob.php';
require_once __DIR__ . '/../src/Models/RecurringOccurrence.php';
require_once __DIR__ . '/../src/Models/Job.php';
require_once __DIR__ . '/../src/Services/RecurringGenerator.php';

try {
    $db = Database::getInstance();
    $row = $db->fetch("SELECT id FROM recurring_jobs WHERE status = 'ACTIVE' ORDER BY id LIMIT 1");
    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'No ACTIVE recurring_jobs found'], JSON_UNESCAPED_UNICODE) . PHP_EOL;
        exit(0);
    }
    $id = (int)$row['id'];

    $beforeOcc = $db->fetch("SELECT COUNT(*) AS c FROM recurring_job_occurrences WHERE recurring_job_id = ?", [$id])['c'] ?? 0;
    $beforeJobs = $db->fetch("SELECT COUNT(*) AS c FROM jobs WHERE recurring_job_id = ?", [$id])['c'] ?? 0;

    $occ = RecurringGenerator::generateForJob($id, 30);
    $jobs = RecurringGenerator::materializeToJobs($id);

    $afterOcc = $db->fetch("SELECT COUNT(*) AS c FROM recurring_job_occurrences WHERE recurring_job_id = ?", [$id])['c'] ?? 0;
    $afterJobs = $db->fetch("SELECT COUNT(*) AS c FROM jobs WHERE recurring_job_id = ?", [$id])['c'] ?? 0;

    echo json_encode([
        'ok' => true,
        'recurring_job_id' => $id,
        'generated' => ['occurrences' => $occ, 'jobs' => $jobs],
        'counts' => [
            'occurrences' => ['before' => (int)$beforeOcc, 'after' => (int)$afterOcc],
            'jobs' => ['before' => (int)$beforeJobs, 'after' => (int)$afterJobs],
        ]
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}



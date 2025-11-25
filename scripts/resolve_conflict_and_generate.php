<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/Database.php';
require_once __DIR__ . '/../src/Lib/Auth.php';
require_once __DIR__ . '/../src/Models/RecurringJob.php';
require_once __DIR__ . '/../src/Models/RecurringOccurrence.php';
require_once __DIR__ . '/../src/Models/Job.php';
require_once __DIR__ . '/../src/Services/RecurringGenerator.php';

// Emulate ADMIN session (tenant 1)
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'ADMIN';
$_SESSION['company_id'] = 1;
$_SESSION['login_time'] = time();

$id = isset($argv[1]) ? (int)$argv[1] : 2;
$newHour = isset($argv[2]) ? (int)$argv[2] : 10;
$newMinute = isset($argv[3]) ? (int)$argv[3] : 0;

try {
    $db = Database::getInstance();

    // 1) Shift recurring job default time
    $db->update('recurring_jobs', [
        'byhour' => $newHour,
        'byminute' => $newMinute,
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$id]);

    // 2) Delete future occurrences (clean slate)
    $gen = new RecurringGenerator();
    $deleted = $gen->deleteFutureOccurrences($id);

    // 3) Generate next 30 days and materialize
    $occ = RecurringGenerator::generateForJob($id, 30);
    $jobs = RecurringGenerator::materializeToJobs($id);

    // 4) Pull summary
    $occList = $db->fetchAll("SELECT id, date, start_at, end_at, status FROM recurring_job_occurrences WHERE recurring_job_id = ? ORDER BY date LIMIT 10", [$id]);
    $jobsList = $db->fetchAll("SELECT id, start_at, end_at, status, total_amount FROM jobs WHERE recurring_job_id = ? ORDER BY start_at LIMIT 10", [$id]);

    echo json_encode([
        'ok' => true,
        'recurring_job_id' => $id,
        'time_shift' => sprintf('%02d:%02d', $newHour, $newMinute),
        'deleted_future_occurrences' => $deleted,
        'generated' => ['occurrences' => $occ, 'jobs' => $jobs],
        'sample' => ['occurrences' => $occList, 'jobs' => $jobsList]
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}



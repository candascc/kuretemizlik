<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/Lib/Database.php';
require __DIR__ . '/../src/Models/Job.php';
require __DIR__ . '/../src/Models/JobPayment.php';

const MONTHLY_AMOUNT = 29411.76;
const TOLERANCE = 0.02;

function out($msg) { echo $msg, PHP_EOL; }
function err($msg) { fwrite(STDERR, $msg . PHP_EOL); }

try {
	$db = Database::getInstance();
	$db->execute('PRAGMA foreign_keys = ON');

	// 1) Find customer (case-insensitive)
	$customer = $db->fetch("SELECT * FROM customers WHERE LOWER(name) LIKE LOWER(?) LIMIT 1", ['%selim dervişoğlu%']);
	if (!$customer) {
		$customer = $db->fetch("SELECT * FROM customers WHERE LOWER(name) LIKE LOWER(?) LIMIT 1", ['%selim dervis%']);
	}
	if (!$customer) {
		err('Customer not found: Selim Dervişoğlu');
		exit(2);
	}
	$customerId = (int)$customer['id'];
	out("Customer: {$customer['name']} (#{$customerId})");

	// 2) Find jobs for this customer
	$jobs = $db->fetchAll("SELECT id FROM jobs WHERE customer_id = ?", [$customerId]);
	if (empty($jobs)) {
		err('No jobs found for customer.');
		exit(0);
	}
	$jobIds = array_map(fn($r) => (int)$r['id'], $jobs);
	out('Jobs: ' . implode(',', $jobIds));

	// 3) Find daily income finance entries ~= 29,411.76 for these jobs
	$placeholders = implode(',', array_fill(0, count($jobIds), '?'));
	$params = $jobIds;
	$params[] = MONTHLY_AMOUNT - TOLERANCE;
	$params[] = MONTHLY_AMOUNT + TOLERANCE;

	$sql = "
		SELECT id, date, amount, note, job_id
		FROM money_entries
		WHERE kind = 'INCOME'
		  AND job_id IN ($placeholders)
		  AND amount BETWEEN ? AND ?
		ORDER BY date ASC
	";
	$rows = $db->fetchAll($sql, $params);
	if (empty($rows)) {
		out('No daily-like income entries found to collapse.');
		exit(0);
	}

	// 4) Group by YYYY-MM, keep one per month, mark others for deletion
	$byMonth = [];
	foreach ($rows as $r) {
		$month = substr((string)$r['date'], 0, 7);
		$byMonth[$month][] = $r;
	}

	$toDelete = [];
	$keepers = [];
	foreach ($byMonth as $month => $list) {
		// Keep the earliest entry in that month
		usort($list, fn($a, $b) => strcmp($a['date'], $b['date']));
		$keepers[] = $list[0]['id'];
		for ($i = 1; $i < count($list); $i++) {
			$toDelete[] = $list[$i]['id'];
		}
	}

	out('Months found: ' . implode(', ', array_keys($byMonth)));
	out('Keepers: ' . count($keepers) . ' | To delete: ' . count($toDelete));

	if (empty($toDelete)) {
		out('Nothing to delete.');
		exit(0);
	}

	// 5) Delete redundant finance entries (and related job_payments)
	$db->beginTransaction();
	try {
		$jp = new JobPayment();
		$deleted = 0;
		foreach ($toDelete as $financeId) {
			// delete payment row if exists
			$jp->deleteByFinance((int)$financeId);
			// delete finance row
			$db->delete('money_entries', 'id = ?', [(int)$financeId]);
			$deleted++;
		}
		$db->commit();
		out("Deleted redundant finance entries: {$deleted}");
	} catch (Throwable $e) {
		$db->rollback();
		throw $e;
	}

	out('Done.');
	exit(0);
} catch (Throwable $e) {
	err('Error: ' . $e->getMessage());
	exit(1);
}



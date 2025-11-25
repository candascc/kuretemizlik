<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/Lib/Database.php';

const MONTHLY_AMOUNT = 29411.76;
const TOLERANCE = 0.05;

function out($m){ echo $m, PHP_EOL; }
function approx(float $v, float $target, float $tol): bool {
	return abs($v - $target) <= $tol;
}

try {
	$db = Database::getInstance();
	$db->execute('PRAGMA foreign_keys = ON');

	// Find customer id
	$customer = $db->fetch("SELECT * FROM customers WHERE LOWER(name) LIKE LOWER(?) LIMIT 1", ['%selim dervişoğlu%']);
	if (!$customer) {
		$customer = $db->fetch("SELECT * FROM customers WHERE LOWER(name) LIKE LOWER(?) LIMIT 1", ['%selim dervis%']);
	}
	if (!$customer) {
		out('Customer not found.');
		exit(2);
	}
	$customerId = (int)$customer['id'];
	out("Customer: {$customer['name']} (#{$customerId})");

	// Pull jobs
	$jobs = $db->fetchAll("
		SELECT id, start_at, end_at, total_amount, amount_paid, payment_status, status
		FROM jobs
		WHERE customer_id = ?
		ORDER BY start_at ASC, id ASC
	", [$customerId]);
	if (empty($jobs)) {
		out('No jobs found.');
		exit(0);
	}

	// Group by YYYY-MM
	$byMonth = [];
	foreach ($jobs as $j) {
		$start = (string)($j['start_at'] ?? '');
		$month = substr($start, 0, 7);
		if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
			// fallback to created_at if start_at missing
			$created = $db->fetch("SELECT created_at FROM jobs WHERE id = ?", [$j['id']]);
			$month = substr((string)($created['created_at'] ?? ''), 0, 7);
		}
		if (!$month) { $month = 'unknown'; }
		$byMonth[$month][] = $j;
	}

	$updatedZero = 0;
	$keptMonthly = 0;

	$db->beginTransaction();
	try {
		foreach ($byMonth as $month => $list) {
			// Pick the first job in month as billing candidate if any has monthly amount
			usort($list, fn($a,$b)=>strcmp((string)$a['start_at'], (string)$b['start_at']) ?: ($a['id']<=>$b['id']));

			// Determine if there is already a monthly billing job in this month
			$billingIndex = null;
			foreach ($list as $idx => $job) {
				if (approx((float)$job['total_amount'], MONTHLY_AMOUNT, TOLERANCE)) {
					$billingIndex = $idx;
					break;
				}
			}
			if ($billingIndex === null) {
				// No monthly-amount job found; skip this month
				continue;
			}

			// Keep this one as the monthly bill; others set to zero amount so they don't accrue receivables
			$keptMonthly++;
			foreach ($list as $idx => $job) {
				if ($idx === $billingIndex) {
					// Ensure payment_status aligns with amounts
					$paid = (float)$job['amount_paid'];
					$newStatus = ($paid + 0.0001 >= MONTHLY_AMOUNT) ? 'PAID' : (($paid > 0) ? 'PARTIAL' : 'UNPAID');
					$db->update('jobs', [
						'payment_status' => $newStatus,
						'updated_at' => date('Y-m-d H:i:s'),
					], 'id = ?', [$job['id']]);
					continue;
				}
				// For other jobs in the month, if they look like daily mistakenly billed as monthly, zero them out
				if (approx((float)$job['total_amount'], MONTHLY_AMOUNT, TOLERANCE) && (float)$job['amount_paid'] == 0.0) {
					$db->update('jobs', [
						'total_amount' => 0,
						'payment_status' => 'PAID', // zero total considered paid
						'updated_at' => date('Y-m-d H:i:s'),
					], 'id = ?', [$job['id']]);
					$updatedZero++;
				}
			}
		}
		$db->commit();
	} catch (Throwable $e) {
		$db->rollback();
		throw $e;
	}

	out("Kept monthly billing jobs: {$keptMonthly}");
	out("Zeroed mistaken daily jobs: {$updatedZero}");
	out("Done.");
	exit(0);
} catch (Throwable $e) {
	fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
	exit(1);
}



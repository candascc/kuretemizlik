<?php
/**
 * Management Fee Model (Aidat Kayıtları)
 */

class ManagementFee
{
	private $db;

	private function buildFilterQuery(array $filters = []): array
	{
		$where = [];
		$params = [];

		if (!empty($filters['building_id'])) { $where[] = 'mf.building_id = ?'; $params[] = $filters['building_id']; }
		if (!empty($filters['unit_id'])) { $where[] = 'mf.unit_id = ?'; $params[] = $filters['unit_id']; }
		if (!empty($filters['period'])) { $where[] = 'mf.period = ?'; $params[] = $filters['period']; }
		if (!empty($filters['status'])) { $where[] = 'mf.status = ?'; $params[] = $filters['status']; }
		if (!empty($filters['due_from'])) { $where[] = 'date(mf.due_date) >= date(?)'; $params[] = $filters['due_from']; }
		if (!empty($filters['due_to'])) { $where[] = 'date(mf.due_date) <= date(?)'; $params[] = $filters['due_to']; }
		if (!empty($filters['overdue_only'])) { $where[] = 'mf.status = \'overdue\''; }

		$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
		return [$whereSql, $params];
	}

	public function __construct()
	{
		$this->db = Database::getInstance();
	}

	public function find($id)
	{
		return $this->db->fetch('SELECT * FROM management_fees WHERE id = ?', [(int)$id]);
	}

	public function list(array $filters = [], int $limit = 50, int $offset = 0): array
	{
		[$whereSql, $params] = $this->buildFilterQuery($filters);
		$sql = "SELECT 
		            mf.*,
		            u.unit_number,
		            u.owner_name,
		            b.name as building_name
		        FROM management_fees mf
		        LEFT JOIN units u ON mf.unit_id = u.id
		        LEFT JOIN buildings b ON mf.building_id = b.id
		        {$whereSql} 
		        ORDER BY mf.due_date DESC, mf.id DESC 
		        LIMIT ? OFFSET ?";
		$params[] = $limit;
		$params[] = $offset;
		return $this->db->fetchAll($sql, $params);
	}

	public function paginate(array $filters = [], int $limit = 50, int $offset = 0): array
	{
		[$whereSql, $params] = $this->buildFilterQuery($filters);
		$countRow = $this->db->fetch("SELECT COUNT(*) as c FROM management_fees mf {$whereSql}", $params);
		$total = (int)($countRow['c'] ?? 0);

		return [
			'data' => $this->list($filters, $limit, $offset),
			'total' => $total,
		];
	}

	/**
	 * Backward-compat: some controllers call all($filters,$limit)
	 */
	public function all(array $filters = [], int $limit = 50, int $offset = 0): array
	{
		return $this->list($filters, $limit, $offset);
	}

	/**
	 * Create management fee
	 * STAGE 3.2: Added duplicate prevention with UNIQUE constraint handling (BUG_011)
	 */
	public function create(array $data)
	{
		$totalAmount = isset($data['total_amount']) ? (float)$data['total_amount'] : 0;
		if ($totalAmount <= 0 && isset($data['base_amount'])) {
			$base = (float)($data['base_amount'] ?? 0);
			$discount = (float)($data['discount_amount'] ?? 0);
			$late = (float)($data['late_fee'] ?? 0);
			$totalAmount = max(0, $base - $discount + $late);
		}
		
		$unitId = (int)$data['unit_id'];
		$period = $data['period'] ?? date('Y-m');
		$feeName = $data['fee_name'] ?? 'Aidat';
		
		// STAGE 3.2: Check for existing fee before insert (application-level check)
		$existing = $this->db->fetch(
			'SELECT id FROM management_fees WHERE unit_id = ? AND period = ? AND fee_name = ?',
			[$unitId, $period, $feeName]
		);
		
		if ($existing) {
			// Fee already exists - return existing ID (idempotent behavior)
			Logger::info('Management fee already exists (duplicate prevention)', [
				'unit_id' => $unitId,
				'period' => $period,
				'fee_name' => $feeName,
				'existing_id' => $existing['id']
			]);
			return (int)$existing['id'];
		}
		
		$payload = [
			'unit_id' => $unitId,
			'building_id' => (int)$data['building_id'],
			'period' => $period,
			'fee_name' => $feeName,
			'base_amount' => isset($data['base_amount']) ? (float)$data['base_amount'] : $totalAmount,
			'discount_amount' => isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0,
			'late_fee' => isset($data['late_fee']) ? (float)$data['late_fee'] : 0,
			'total_amount' => $totalAmount,
			'paid_amount' => isset($data['paid_amount']) ? (float)$data['paid_amount'] : 0,
			'status' => $data['status'] ?? 'pending',
			'due_date' => $data['due_date'] ?? date('Y-m-d'),
			'payment_date' => $data['payment_date'] ?? null,
			'payment_method' => $data['payment_method'] ?? null,
			'receipt_number' => $data['receipt_number'] ?? null,
			'notes' => $data['notes'] ?? null,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];

		// Backward compatibility: filter payload by existing columns in current DB
		try {
			$columns = $this->db->fetchAll('PRAGMA table_info(management_fees)');
			$allowed = [];
			foreach ($columns as $col) {
				if (isset($col['name'])) { $allowed[$col['name']] = true; }
			}
			$filtered = [];
			foreach ($payload as $k => $v) {
				if (isset($allowed[$k])) { $filtered[$k] = $v; }
			}
			
			// STAGE 3.2: Attempt insert - UNIQUE constraint will catch race conditions
			try {
				return $this->db->insert('management_fees', $filtered);
			} catch (Exception $insertException) {
				// STAGE 3.2: Handle UNIQUE constraint violation (race condition protection)
				if (strpos($insertException->getMessage(), 'UNIQUE constraint') !== false || 
					strpos($insertException->getMessage(), 'idx_management_fees_unique_unit_period_fee') !== false) {
					// Another process created the fee concurrently - fetch and return existing
					Logger::warning('UNIQUE constraint violation - fee created by another process (race condition)', [
						'unit_id' => $unitId,
						'period' => $period,
						'fee_name' => $feeName,
						'error' => $insertException->getMessage()
					]);
					
					$existing = $this->db->fetch(
						'SELECT id FROM management_fees WHERE unit_id = ? AND period = ? AND fee_name = ?',
						[$unitId, $period, $feeName]
					);
					
					if ($existing) {
						return (int)$existing['id'];
					}
					// If still not found, re-throw (shouldn't happen)
					throw $insertException;
				}
				// Re-throw if it's a different error
				throw $insertException;
			}
		} catch (Exception $e) {
			// Fallback minimal insert (only if it's not a UNIQUE constraint error)
			if (strpos($e->getMessage(), 'UNIQUE constraint') === false) {
				$minimal = [
					'unit_id' => $unitId,
					'building_id' => (int)$data['building_id'],
					'period' => $period,
					'fee_name' => $feeName,
					'total_amount' => $totalAmount,
					'paid_amount' => 0,
					'status' => 'pending',
					'due_date' => date('Y-m-d'),
					'created_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s'),
				];
				try {
					return $this->db->insert('management_fees', $minimal);
				} catch (Exception $minimalException) {
					// Handle UNIQUE constraint in minimal insert too
					if (strpos($minimalException->getMessage(), 'UNIQUE constraint') !== false) {
						$existing = $this->db->fetch(
							'SELECT id FROM management_fees WHERE unit_id = ? AND period = ? AND fee_name = ?',
							[$unitId, $period, $feeName]
						);
						if ($existing) {
							return (int)$existing['id'];
						}
					}
					throw $minimalException;
				}
			}
			throw $e;
		}
	}

	public function update($id, array $data)
	{
		$fields = ['fee_name','base_amount','discount_amount','late_fee','total_amount','paid_amount','status','due_date','payment_date','payment_method','receipt_number','notes'];
		$payload = [];
		foreach ($fields as $f) { if (array_key_exists($f, $data)) { $payload[$f] = $data[$f]; } }
		if (empty($payload)) { return 0; }
		$payload['updated_at'] = date('Y-m-d H:i:s');
		return $this->db->update('management_fees', $payload, 'id = ?', [(int)$id]);
	}

	public function delete($id)
	{
		return $this->db->delete('management_fees', 'id = ?', [(int)$id]);
	}

	/**
	 * Apply payment to management fee
	 * FIXED: Wrapped in transaction for atomicity (CRIT-004, CRIT-007)
	 */
	public function applyPayment($id, $amount, $method = null, $date = null, $notes = '')
	{
		$row = $this->find($id);
		if (!$row) { return false; }
		
		// CRITICAL FIX: Wrap in transaction to ensure payment update + money entry are atomic
		return $this->db->transaction(function() use ($id, $row, $amount, $method, $date, $notes) {
			$paid = (float)($row['paid_amount'] ?? 0) + max(0, (float)$amount);
			$paid = min($paid, (float)($row['total_amount'] ?? 0));
			$status = 'partial';
			if ($paid <= 0) { $status = 'pending'; }
			elseif ($paid + 0.00001 >= (float)$row['total_amount']) { $status = 'paid'; }

			$reference = $row['receipt_number'] ?? null;
			if (empty($reference)) {
				$reference = $this->generateReceiptNumber($row);
			}
			
			// Update fee record
			$this->update($id, [
				'paid_amount' => $paid,
				'status' => $status,
				'payment_method' => $method ?? $row['payment_method'] ?? null,
				'payment_date' => $date ?? date('Y-m-d'),
				'receipt_number' => $reference,
			]);
			
			// Create money entry for accounting (ATOMIC with fee update)
			$moneyEntryId = $this->createMoneyEntry($row, $amount, $method, $date, $notes, $reference);
			
			$updated = $this->find($id);
			
			// STAGE 4.3: Audit log management fee payment (inside transaction, but won't rollback on audit failure)
			// Note: Audit logging is non-critical, so we catch exceptions
			try {
				if (class_exists('AuditLogger')) {
					$userId = class_exists('Auth') && method_exists('Auth', 'id') ? Auth::id() : null;
					AuditLogger::getInstance()->logBusiness('MANAGEMENT_FEE_PAYMENT_APPLIED', $userId, [
						'fee_id' => $id,
						'unit_id' => $row['unit_id'] ?? null,
						'amount' => (float)$amount,
						'method' => $method,
						'receipt_number' => $reference,
						'status' => $status,
						'paid_total' => $paid
					]);
				}
			} catch (Exception $auditError) {
				// Don't fail payment if audit logging fails
				error_log("Audit log failed for management fee payment: " . $auditError->getMessage());
			}
			
			return [
				'success' => true,
				'fee' => $updated,
				'money_entry_id' => $moneyEntryId,
				'reference' => $reference,
				'status' => $status,
				'amount' => (float)$amount,
				'method' => $method,
				'paid_total' => $paid,
			];
		}); // End transaction - commits if all successful, rolls back on exception
	}

	// Compatibility for controller usage
	public function recordPayment($id, $amount, $method = 'cash', $notes = '')
	{
		return $this->applyPayment($id, $amount, $method, date('Y-m-d'), $notes);
	}

	/**
	 * Create money entry for fee payment
	 */
	private function createMoneyEntry($fee, $amount, $method, $date, $notes, $reference = null)
	{
			$moneyModel = new MoneyEntry();
			
			$description = "Aidat ödemesi - {$fee['fee_name']} ({$fee['period']})";
			if ($notes) {
				$description .= " - " . $notes;
			}
			if ($reference) {
				$description .= " | Makbuz No: {$reference}";
			}
			
		// ===== CRITICAL FIX: Ensure session is started before accessing $_SESSION =====
		// Use SessionHelper for centralized session management
		SessionHelper::ensureStarted();
		// ===== CRITICAL FIX END =====
		
		// Ensure created_by references a valid backend user (FK constraint to users.id)
		$createdBy = $_SESSION['user_id'] ?? 1;
		try {
			$db = Database::getInstance();
			$usersTable = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
			if ($usersTable) {
				$exists = $db->fetch("SELECT id FROM users WHERE id = ?", [$createdBy]);
				if (!$exists) {
					// Create a minimal system user with ADMIN role
					$db->insert('users', [
						'id' => $createdBy,
						'username' => 'system',
						'password_hash' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
						'role' => 'ADMIN',
						'is_active' => 1,
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s'),
					]);
				}
			}
		} catch (Throwable $e) {
			// Best effort
		}

		$insertId = $moneyModel->create([
				'kind' => 'INCOME',
				'category' => 'MANAGEMENT_FEE',
				'amount' => $amount,
				'date' => $date ?? date('Y-m-d'),
				'note' => $description,
				'job_id' => null,
				'recurring_job_id' => null,
				'created_by' => $createdBy,
			]);

		if (!$insertId) {
			throw new Exception('Money entry could not be created');
		}

		return $insertId;
	}

	private function generateReceiptNumber(array $fee): string
	{
		$prefix = 'MF';
		$unit = str_pad((string)($fee['unit_id'] ?? 0), 4, '0', STR_PAD_LEFT);
		$random = strtoupper(bin2hex(random_bytes(3)));
		return sprintf('%s-%s-%s', $prefix, date('ymd'), $unit . substr($random, 0, 6));
	}

	/**
	 * Generate management fees for a period
	 * STAGE 3.2: Enhanced duplicate prevention with UNIQUE constraint (BUG_011)
	 */
	public function generateForPeriod($buildingId, $period)
	{
		$db = Database::getInstance();
        
        // Wrap in transaction for atomicity
        return $db->transaction(function() use ($db, $buildingId, $period) {
            $defs = $db->fetchAll('SELECT * FROM management_fee_definitions WHERE building_id = ?', [(int)$buildingId]);
            $units = $db->fetchAll('SELECT * FROM units WHERE building_id = ? AND status = "active"', [(int)$buildingId]);
            // If no units with explicit active status, fallback to all units in the building
            if (empty($units)) {
                $units = $db->fetchAll('SELECT * FROM units WHERE building_id = ?', [(int)$buildingId]);
            }
            if (empty($units)) { return 0; }
            
            $count = 0;
            $skipped = 0;
            
            foreach ($units as $unit) {
                if (!empty($defs)) {
                    foreach ($defs as $def) {
                        // STAGE 3.2: Check for duplicates (application-level check)
                        // The create() method will also check, but this avoids unnecessary processing
                        $existing = $db->fetch(
                            'SELECT id FROM management_fees WHERE unit_id = ? AND period = ? AND fee_name = ?',
                            [(int)$unit['id'], $period, $def['name']]
                        );
                        if ($existing) {
                            $skipped++;
                            continue;
                        }
                        
                        $base = (float)$def['amount'];
                        $name = $def['name'];
                        
                        // STAGE 3.2: create() method now handles UNIQUE constraint violations
                        // If race condition occurs, it will return existing ID (idempotent)
                        $feeId = $this->create([
                            'unit_id' => (int)$unit['id'],
                            'building_id' => (int)$buildingId,
                            'definition_id' => $def['id'] ?? null,
                            'period' => $period,
                            'fee_name' => $name,
                            'base_amount' => $base,
                            'discount_amount' => 0,
                            'late_fee' => 0,
                            'total_amount' => $base,
                            'due_date' => date('Y-m-d', strtotime($period . '-10')),
                        ]);
                        
                        // Only count if a new fee was created (not skipped due to duplicate)
                        if ($feeId) {
                            $count++;
                        } else {
                            $skipped++;
                        }
                    }
                } else {
                    // Fallback: use unit.monthly_fee if definitions are not configured
                    $base = isset($unit['monthly_fee']) ? (float)$unit['monthly_fee'] : 0.0;
                    if ($base > 0) {
                        // STAGE 3.2: Check for duplicates
                        $existing = $db->fetch(
                            'SELECT id FROM management_fees WHERE unit_id = ? AND period = ? AND fee_name = ?',
                            [(int)$unit['id'], $period, 'Aidat']
                        );
                        if ($existing) {
                            $skipped++;
                            continue;
                        }
                        
                        // STAGE 3.2: create() method handles UNIQUE constraint violations
                        $feeId = $this->create([
                            'unit_id' => (int)$unit['id'],
                            'building_id' => (int)$buildingId,
                            'period' => $period,
                            'fee_name' => 'Aidat',
                            'base_amount' => $base,
                            'discount_amount' => 0,
                            'late_fee' => 0,
                            'total_amount' => $base,
                            'due_date' => date('Y-m-d', strtotime($period . '-10')),
                        ]);
                        
                        if ($feeId) {
                            $count++;
                        } else {
                            $skipped++;
                        }
                    }
                }
            }
            
            Logger::info('Management fee generation completed', [
                'building_id' => $buildingId,
                'period' => $period,
                'created' => $count,
                'skipped' => $skipped
            ]);
            
            return $count;
        });
	}

	public function calculateLateFees($buildingId = null)
	{
		$db = Database::getInstance();
        
        // Wrap in transaction for atomicity
        return $db->transaction(function() use ($buildingId) {
            $filters = ['status' => 'overdue'];
            if ($buildingId) { $filters['building_id'] = $buildingId; }
            $fees = $this->all($filters, 10000, 0);
            $updated = 0;
            
            foreach ($fees as $fee) {
                $due = strtotime($fee['due_date'] ?? '');
                if ($due && $due < strtotime(date('Y-m-d'))) {
                    $days = (int)floor((time() - $due) / 86400);
                    $late = max(0, round(((float)$fee['total_amount']) * 0.001 * $days, 2));
                    $this->update((int)$fee['id'], ['late_fee' => $late, 'total_amount' => (float)$fee['base_amount'] - (float)$fee['discount_amount'] + $late]);
                    $updated++;
                }
            }
            
            return $updated;
        });
	}
	
	/**
	 * Aylık aidat tahsilat özeti
	 */
	public function getMonthlySummary(int $buildingId, int $year): array
	{
		$monthlyData = [];
		for ($month = 1; $month <= 12; $month++) {
			$period = sprintf('%04d-%02d', $year, $month);
			
			$result = $this->db->fetch(
				"SELECT 
					COUNT(*) as count,
					COALESCE(SUM(total_amount), 0) as total_amount,
					COALESCE(SUM(paid_amount), 0) as paid_amount,
					COALESCE(SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END), 0) as paid_count,
					COALESCE(SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END), 0) as overdue_count
				 FROM management_fees 
				 WHERE building_id = ? 
				   AND period = ?",
				[$buildingId, $period]
			);
			
			$monthlyData[$month] = [
				'month' => $month,
				'month_name' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'][$month - 1],
				'total_count' => (int)($result['count'] ?? 0),
				'total_amount' => (float)($result['total_amount'] ?? 0),
				'paid_amount' => (float)($result['paid_amount'] ?? 0),
				'paid_count' => (int)($result['paid_count'] ?? 0),
				'overdue_count' => (int)($result['overdue_count'] ?? 0)
			];
		}
		
		return $monthlyData;
	}
}


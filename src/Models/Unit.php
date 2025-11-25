<?php
/**
 * Unit Model
 */

class Unit
{
	private $db;

	public function __construct()
	{
		$this->db = Database::getInstance();
	}

	public function find(int $id)
	{
		return $this->db->fetch('SELECT * FROM units WHERE id = ?', [$id]);
	}

	public function allByBuilding(int $buildingId): array
	{
		return $this->db->fetchAll('SELECT * FROM units WHERE building_id = ? ORDER BY floor_number, unit_number', [$buildingId]);
	}

	/**
	 * Backward-compat: UnitController expects getByBuilding()
	 */
	public function getByBuilding(int $buildingId): array
	{
		return $this->allByBuilding($buildingId);
	}

	/**
	 * Backward-compat: UnitController expects all($limit,$offset)
	 */
	public function all(int $limit = 50, int $offset = 0): array
	{
		return $this->db->fetchAll('SELECT * FROM units ORDER BY building_id, floor_number, unit_number LIMIT ? OFFSET ?', [$limit, $offset]);
	}

	public function paginate(?int $buildingId, ?string $search, int $limit, int $offset): array
	{
		$where = [];
		$params = [];

		if (!empty($buildingId)) {
			$where[] = 'building_id = ?';
			$params[] = $buildingId;
		}

		$search = trim((string)$search);
		if ($search !== '') {
			$where[] = '(unit_number LIKE ? OR owner_name LIKE ? OR tenant_name LIKE ?)';
			$like = '%' . $search . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

		$countRow = $this->db->fetch("SELECT COUNT(*) as c FROM units {$whereSql}", $params);
		$total = (int)($countRow['c'] ?? 0);

		$dataParams = $params;
		$dataParams[] = $limit;
		$dataParams[] = $offset;

		$rows = $this->db->fetchAll(
			"SELECT * FROM units {$whereSql} ORDER BY building_id, floor_number, unit_number LIMIT ? OFFSET ?",
			$dataParams
		);

		return [
			'data' => $rows,
			'total' => $total,
		];
	}

	public function create(array $data)
	{
		$payload = [
			'building_id' => (int)$data['building_id'],
			'unit_type' => $data['unit_type'] ?? 'daire',
			'floor_number' => isset($data['floor_number']) ? (int)$data['floor_number'] : null,
			'unit_number' => $data['unit_number'] ?? '',
			'gross_area' => isset($data['gross_area']) ? (float)$data['gross_area'] : null,
			'net_area' => isset($data['net_area']) ? (float)$data['net_area'] : null,
			'room_count' => $data['room_count'] ?? null,
			'owner_type' => $data['owner_type'] ?? 'owner',
			'owner_name' => $data['owner_name'] ?? '',
			'owner_phone' => $data['owner_phone'] ?? null,
			'owner_email' => $data['owner_email'] ?? null,
			'owner_id_number' => $data['owner_id_number'] ?? null,
			'owner_address' => $data['owner_address'] ?? null,
			'tenant_name' => $data['tenant_name'] ?? null,
			'tenant_phone' => $data['tenant_phone'] ?? null,
			'tenant_email' => $data['tenant_email'] ?? null,
			'tenant_contract_start' => $data['tenant_contract_start'] ?? null,
			'tenant_contract_end' => $data['tenant_contract_end'] ?? null,
			'monthly_fee' => isset($data['monthly_fee']) ? (float)$data['monthly_fee'] : 0,
			'debt_balance' => isset($data['debt_balance']) ? (float)$data['debt_balance'] : 0,
			'parking_count' => isset($data['parking_count']) ? (int)$data['parking_count'] : 0,
			'storage_count' => isset($data['storage_count']) ? (int)$data['storage_count'] : 0,
			'status' => $data['status'] ?? 'active',
			'notes' => $data['notes'] ?? null,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];
		return $this->db->insert('units', $payload);
	}

	public function update(int $id, array $data)
	{
		$fields = ['unit_type','floor_number','unit_number','gross_area','net_area','room_count','owner_type','owner_name','owner_phone','owner_email','owner_id_number','owner_address','tenant_name','tenant_phone','tenant_email','tenant_contract_start','tenant_contract_end','monthly_fee','debt_balance','parking_count','storage_count','status','notes'];
		$payload = [];
		foreach ($fields as $f) {
			if (array_key_exists($f, $data)) { $payload[$f] = $data[$f]; }
		}
		if (empty($payload)) { return 0; }
		$payload['updated_at'] = date('Y-m-d H:i:s');
		return $this->db->update('units', $payload, 'id = ?', [$id]);
	}

	public function delete(int $id)
	{
		return $this->db->delete('units', 'id = ?', [$id]);
	}

	public function adjustDebt(int $id, float $delta): bool
	{
		$row = $this->find($id);
		if (!$row) { return false; }
		$new = (float)($row['debt_balance'] ?? 0) + $delta;
		$new = max(-99999999.0, min(99999999.0, $new));
		$this->update($id, ['debt_balance' => $new]);
		return true;
	}

	/**
	 * Get comprehensive statistics for a unit
	 */
	public function getStatistics(int $unitId): array
	{
		$unit = $this->find($unitId);
		if (!$unit) {
			return [];
		}

		// Fee statistics
		$feeModel = new ManagementFee();
		$allFees = $feeModel->all(['unit_id' => $unitId], 1000);
		
		$totalFees = (float)0;
		$paidFees = (float)0;
		$pendingFees = (float)0;
		$overdueFees = (float)0;
		
		foreach ($allFees as $fee) {
			$totalFees += (float)($fee['total_amount'] ?? 0);
			if ($fee['status'] === 'paid') {
				$paidFees += (float)($fee['paid_amount'] ?? 0);
			} elseif ($fee['status'] === 'pending' || $fee['status'] === 'partial') {
				$pendingFees += (float)(($fee['total_amount'] ?? 0) - ($fee['paid_amount'] ?? 0));
			} elseif ($fee['status'] === 'overdue') {
				$overdueFees += (float)(($fee['total_amount'] ?? 0) - ($fee['paid_amount'] ?? 0));
			}
		}

		// Recent payments
		$recentPayments = [];
		foreach ($allFees as $fee) {
			if ($fee['status'] === 'paid' && $fee['payment_date']) {
				$recentPayments[] = [
					'period' => $fee['period'] ?? '',
					'amount' => (float)($fee['paid_amount'] ?? 0),
					'date' => $fee['payment_date']
				];
			}
		}
		usort($recentPayments, function($a, $b) {
			return strtotime($b['date']) - strtotime($a['date']);
		});
		$recentPayments = array_slice($recentPayments, 0, 10);

		// Resident user count
		$residentCount = (int)($this->db->fetch(
			"SELECT COUNT(*) as c FROM resident_users WHERE unit_id = ? AND is_active = 1",
			[$unitId]
		)['c'] ?? 0);

		// Document count
		$docCount = (int)($this->db->fetch(
			"SELECT COUNT(*) as c FROM building_documents WHERE unit_id = ?",
			[$unitId]
		)['c'] ?? 0);

		return [
			'unit' => $unit,
			'fees' => [
				'total' => $totalFees,
				'paid' => $paidFees,
				'pending' => $pendingFees,
				'overdue' => $overdueFees,
				'count' => count($allFees)
			],
			'recent_payments' => $recentPayments,
			'residents' => [
				'count' => $residentCount
			],
			'documents' => [
				'count' => $docCount
			]
		];
	}
}


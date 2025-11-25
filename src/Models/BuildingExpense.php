<?php
/**
 * Building Expense Model
 */

class BuildingExpense
{
	private $db;

	public function __construct()
	{
		$this->db = Database::getInstance();
	}

	public function find(int $id)
	{
		return $this->db->fetch('SELECT * FROM building_expenses WHERE id = ?', [$id]);
	}

	public function list(array $filters = [], int $limit = 50, int $offset = 0): array
	{
		$where = [];
		$params = [];
		if (!empty($filters['building_id'])) { $where[] = 'building_id = ?'; $params[] = $filters['building_id']; }
		if (!empty($filters['category'])) { $where[] = 'category = ?'; $params[] = $filters['category']; }
		if (!empty($filters['date_from'])) { $where[] = 'date(expense_date) >= date(?)'; $params[] = $filters['date_from']; }
		if (!empty($filters['date_to'])) { $where[] = 'date(expense_date) <= date(?)'; $params[] = $filters['date_to']; }
		if (!empty($filters['status'])) { $where[] = 'approval_status = ?'; $params[] = $filters['status']; }
		$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
		$sql = "SELECT * FROM building_expenses {$whereSql} ORDER BY date(expense_date) DESC, id DESC LIMIT ? OFFSET ?";
		$params[] = $limit;
		$params[] = $offset;
		return $this->db->fetchAll($sql, $params);
	}

	/**
	 * Backward-compat: some controllers call all($filters,$limit)
	 */
	public function all(array $filters = [], int $limit = 50, int $offset = 0): array
	{
		return $this->list($filters, $limit, $offset);
	}

	public function create(array $data)
	{
		$payload = [
			'building_id' => (int)$data['building_id'],
			'category' => $data['category'] ?? 'other',
			'amount' => isset($data['amount']) ? (float)$data['amount'] : 0,
			'expense_date' => $data['expense_date'] ?? date('Y-m-d'),
			'invoice_number' => $data['invoice_number'] ?? null,
			'vendor_name' => $data['vendor_name'] ?? null,
			'vendor_tax_number' => $data['vendor_tax_number'] ?? null,
			'payment_method' => $data['payment_method'] ?? null,
			'is_recurring' => !empty($data['is_recurring']) ? 1 : 0,
			'description' => $data['description'] ?? null,
			'receipt_path' => $data['receipt_path'] ?? null,
			'created_by' => $data['created_by'] ?? null,
			'approved_by' => $data['approved_by'] ?? null,
			'approval_status' => $data['approval_status'] ?? 'pending',
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];
		return $this->db->insert('building_expenses', $payload);
	}

	public function update(int $id, array $data)
	{
		$fields = ['category','amount','expense_date','invoice_number','vendor_name','vendor_tax_number','payment_method','is_recurring','description','receipt_path','approved_by','approval_status'];
		$payload = [];
		foreach ($fields as $f) { if (array_key_exists($f, $data)) { $payload[$f] = $data[$f]; } }
		if (empty($payload)) { return 0; }
		$payload['updated_at'] = date('Y-m-d H:i:s');
		return $this->db->update('building_expenses', $payload, 'id = ?', [$id]);
	}

	public function delete(int $id)
	{
		return $this->db->delete('building_expenses', 'id = ?', [$id]);
	}

	public function approve(int $id, int $userId)
	{
		return $this->db->update('building_expenses', [
			'approval_status' => 'approved',
			'approved_by' => $userId,
			'updated_at' => date('Y-m-d H:i:s')
		], 'id = ?', [$id]);
	}

	public function reject(int $id, int $userId)
	{
		return $this->db->update('building_expenses', [
			'approval_status' => 'rejected',
			'approved_by' => $userId,
			'updated_at' => date('Y-m-d H:i:s')
		], 'id = ?', [$id]);
	}

	/**
	 * Aylık gider özeti
	 */
	public function getMonthlySummary(int $buildingId, int $year): array
	{
		$monthlyData = [];
		for ($month = 1; $month <= 12; $month++) {
			$monthStart = sprintf('%04d-%02d-01', $year, $month);
			$monthEnd = sprintf('%04d-%02d-%02d', $year, $month, date('t', strtotime($monthStart)));
			
			$result = $this->db->fetch(
				"SELECT 
					COUNT(*) as count,
					COALESCE(SUM(CASE WHEN approval_status = 'approved' THEN amount ELSE 0 END), 0) as approved_amount,
					COALESCE(SUM(CASE WHEN approval_status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
					COALESCE(SUM(CASE WHEN approval_status = 'rejected' THEN amount ELSE 0 END), 0) as rejected_amount
				 FROM building_expenses 
				 WHERE building_id = ? 
				   AND expense_date >= ? 
				   AND expense_date <= ?",
				[$buildingId, $monthStart, $monthEnd]
			);
			
			$monthlyData[$month] = [
				'month' => $month,
				'month_name' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'][$month - 1],
				'total_count' => (int)($result['count'] ?? 0),
				'approved_amount' => (float)($result['approved_amount'] ?? 0),
				'pending_amount' => (float)($result['pending_amount'] ?? 0),
				'rejected_amount' => (float)($result['rejected_amount'] ?? 0)
			];
		}
		
		return $monthlyData;
	}
}


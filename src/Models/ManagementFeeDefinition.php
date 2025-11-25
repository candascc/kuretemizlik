<?php
/**
 * Management Fee Definition Model (Aidat Şablonu)
 */

class ManagementFeeDefinition
{
	private $db;

	public function __construct()
	{
		$this->db = Database::getInstance();
	}

	public function find($id)
	{
		return $this->db->fetch('SELECT * FROM management_fee_definitions WHERE id = ?', [(int)$id]);
	}

	public function allByBuilding(int $buildingId): array
	{
		return $this->db->fetchAll('SELECT * FROM management_fee_definitions WHERE building_id = ? ORDER BY id DESC', [$buildingId]);
	}

	// Backward-compat wrapper
	public function all($buildingId = null)
	{
		if ($buildingId) {
			return $this->allByBuilding($buildingId);
		}
		return $this->db->fetchAll("SELECT * FROM management_fee_definitions ORDER BY building_id, name ASC");
	}

	public function create(array $data)
	{
		$payload = [
			'building_id' => (int)$data['building_id'],
			'name' => trim((string)($data['name'] ?? '')),
			'fee_type' => $data['fee_type'] ?? 'fixed',
			'amount' => isset($data['amount']) ? (float)$data['amount'] : 0,
			'is_mandatory' => !empty($data['is_mandatory']) ? 1 : 0,
			'description' => $data['description'] ?? null,
			'created_at' => date('Y-m-d H:i:s'),
		];
		return $this->db->insert('management_fee_definitions', $payload);
	}

	public function update(int $id, array $data)
	{
		$fields = ['name','fee_type','amount','is_mandatory','description'];
		$payload = [];
		foreach ($fields as $f) { if (array_key_exists($f, $data)) { $payload[$f] = $data[$f]; } }
		if (empty($payload)) { return 0; }
		return $this->db->update('management_fee_definitions', $payload, 'id = ?', [$id]);
	}

	public function delete(int $id)
	{
		return $this->db->delete('management_fee_definitions', 'id = ?', [$id]);
	}
}


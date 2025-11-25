<?php
/**
 * Fee Generation Service
 * Aidat oluşturma servisi - otomatik ve toplu işlemler
 */

class FeeGenerationService
{
    private $feeModel;
    private $feeDefModel;
    private $buildingModel;
    private $unitModel;

    public function __construct()
    {
        $this->feeModel = new ManagementFee();
        $this->feeDefModel = new ManagementFeeDefinition();
        $this->buildingModel = new Building();
        $this->unitModel = new Unit();
    }

    /**
     * Tüm binalar için belirli dönem aidatlarını oluştur
     */
    public function generateForAllBuildings($period): array
    {
        $buildings = $this->buildingModel->active();
        $results = ['success' => 0, 'failed' => 0, 'details' => []];

        foreach ($buildings as $building) {
            try {
                $count = $this->generateForBuilding($building['id'], $period);
                $results['success']++;
                $results['details'][$building['id']] = [
                    'name' => $building['name'],
                    'count' => $count,
                    'status' => 'success'
                ];
            } catch (Exception $e) {
                $results['failed']++;
                $results['details'][$building['id']] = [
                    'name' => $building['name'],
                    'error' => $e->getMessage(),
                    'status' => 'failed'
                ];
            }
        }

        return $results;
    }

    /**
     * Belirli bina için aidat oluştur
     */
    public function generateForBuilding($buildingId, $period): int
    {
        return $this->feeModel->generateForPeriod($buildingId, $period);
    }

    /**
     * Eksik aidatları tespit et ve oluştur
     */
    public function generateMissingFees($buildingId = null): array
    {
        $currentPeriod = date('Y-m');
        $previousPeriod = date('Y-m', strtotime('-1 month'));
        
        $periods = [$previousPeriod, $currentPeriod];
        $results = [];

        $buildings = $buildingId ? [$this->buildingModel->find($buildingId)] : $this->buildingModel->active();

        foreach ($buildings as $building) {
            if (!$building) continue;

            foreach ($periods as $period) {
                $missing = $this->findMissingFees($building['id'], $period);
                if ($missing > 0) {
                    $count = $this->generateForBuilding($building['id'], $period);
                    $results[] = [
                        'building' => $building['name'],
                        'period' => $period,
                        'generated' => $count
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Eksik aidat sayısını bul
     */
    private function findMissingFees($buildingId, $period): int
    {
        $db = Database::getInstance();
        
        // Aktif birimler
        $units = $this->unitModel->getByBuilding($buildingId);
        $activeUnits = array_filter($units, fn($u) => $u['status'] === 'active');
        $totalUnits = count($activeUnits);

        // Aidat tanımları
        $definitions = $this->feeDefModel->all($buildingId);
        $totalExpected = $totalUnits * count($definitions);

        // Oluşturulmuş aidatlar
        $created = $db->fetch(
            "SELECT COUNT(*) as count FROM management_fees WHERE building_id = ? AND period = ?",
            [$buildingId, $period]
        )['count'] ?? 0;

        return max(0, $totalExpected - $created);
    }

    /**
     * Geçikme ücretlerini hesapla ve uygula
     */
    public function calculateLateFeesForAll($buildingId = null): int
    {
        return $this->feeModel->calculateLateFees($buildingId);
    }
}


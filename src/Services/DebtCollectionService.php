<?php
/**
 * Debt Collection Service
 * Borç tahsilat servisi - geçikme yönetimi ve hatırlatmalar
 */

class DebtCollectionService
{
    private $feeModel;
    private $unitModel;
    private $buildingModel;

    public function __construct()
    {
        $this->feeModel = new ManagementFee();
        $this->unitModel = new Unit();
        $this->buildingModel = new Building();
    }

    /**
     * Geciken borçları listele
     */
    public function getOverdueDebts($buildingId = null, $daysOverdue = null): array
    {
        $filters = ['overdue_only' => true];
        if ($buildingId) $filters['building_id'] = $buildingId;

        $overdueFees = $this->feeModel->all($filters);

        if ($daysOverdue !== null) {
            $cutoffDate = date('Y-m-d', strtotime("-{$daysOverdue} days"));
            $overdueFees = array_filter($overdueFees, function($fee) use ($cutoffDate) {
                return $fee['due_date'] < $cutoffDate;
            });
        }

        return $overdueFees;
    }

    /**
     * Borç özeti (bina bazında)
     */
    public function getDebtSummary($buildingId): array
    {
        $fees = $this->feeModel->all(['building_id' => $buildingId]);
        
        $summary = [
            'total_debt' => 0,
            'overdue_debt' => 0,
            'pending_debt' => 0,
            'overdue_count' => 0,
            'pending_count' => 0
        ];

        foreach ($fees as $fee) {
            $debt = $fee['total_amount'] - $fee['paid_amount'];
            
            if ($debt > 0) {
                if ($fee['status'] === 'overdue') {
                    $summary['overdue_debt'] += $debt;
                    $summary['overdue_count']++;
                } elseif ($fee['status'] === 'pending') {
                    $summary['pending_debt'] += $debt;
                    $summary['pending_count']++;
                }
                
                $summary['total_debt'] += $debt;
            }
        }

        return $summary;
    }

    /**
     * Borçlu birimleri listele
     */
    public function getDebtors($buildingId = null): array
    {
        return $this->unitModel->withDebt($buildingId);
    }

    /**
     * Aidat hatırlatma email'i gönder
     */
    public function sendReminders($feeIds): array
    {
        $results = ['success' => 0, 'failed' => 0];
        $emailService = new EmailService();

        foreach ($feeIds as $feeId) {
            try {
                $fee = $this->feeModel->find($feeId);
                if (!$fee || !$fee['owner_email']) {
                    continue;
                }

                // Email gönder
                $subject = "Aidat Hatırlatması - {$fee['period']}";
                $body = $this->getReminderEmailTemplate($fee);
                
                $emailService->send(
                    $fee['owner_email'],
                    $subject,
                    $body
                );

                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                error_log("Reminder email error for fee {$feeId}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Hatırlatma email template
     */
    private function getReminderEmailTemplate($fee): string
    {
        $debt = $fee['total_amount'] - $fee['paid_amount'];
        return "
            <h2>Aidat Hatırlatması</h2>
            <p>Sayın {$fee['owner_name']},</p>
            <p>{$fee['period']} dönemi aidatınız henüz ödenmemiştir.</p>
            <ul>
                <li><strong>Bina:</strong> {$fee['building_name']}</li>
                <li><strong>Daire:</strong> {$fee['unit_number']}</li>
                <li><strong>Dönem:</strong> {$fee['period']}</li>
                <li><strong>Kalan Tutar:</strong> {$debt} TL</li>
                <li><strong>Son Ödeme Tarihi:</strong> {$fee['due_date']}</li>
            </ul>
            <p>Lütfen gecikme ücreti uygulanmadan önce ödemenizi yapınız.</p>
        ";
    }
}


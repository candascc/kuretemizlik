<?php
/**
 * Helper for keeping job payments and finance entries in sync.
 */

class PaymentService
{
    /**
     * Creates an income entry for a job and persists the related payment.
     * STAGE 3.3: Wrapped in transaction for atomicity (BUG_014)
     *
     * @return int Created finance entry id
     */
    public static function createIncomeWithPayment(int $jobId, float $amount, string $paidAt, ?string $note = null, ?string $category = null): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $db = Database::getInstance();
        
        // STAGE 3.3: Wrap finance entry creation + job payment + job sync in transaction
        return $db->transaction(function() use ($jobId, $amount, $paidAt, $note, $category) {
            $category = $category ?: 'Hizmet Geliri';
            $paidDate = self::normalizeDate($paidAt);
            $sanitizedNote = self::sanitizeNullable($note);

            $moneyModel = new MoneyEntry();
            $entryId = $moneyModel->create([
                'kind' => 'INCOME',
                'category' => $category,
                'amount' => $amount,
                'date' => $paidDate,
                'note' => $sanitizedNote,
                'job_id' => $jobId,
                'created_by' => Auth::id()
            ]);

            // STAGE 3.3: Job payment creation and sync inside transaction
            self::createJobPayment($jobId, $amount, $paidDate, $sanitizedNote, $entryId);

            // Activity logging can happen outside transaction (non-critical)
            // But we'll do it inside to ensure consistency
            ActivityLogger::incomeAdded($amount, $category);

            return (int) $entryId;
        });
    }

    /**
     * Ensures the payment row that backs a finance entry matches latest values.
     * STAGE 3.3: Wrapped in transaction for atomicity (BUG_014)
     */
    public static function syncFinancePayment(int $financeId, int $jobId, float $amount, string $paidAt, ?string $note = null): void
    {
        $db = Database::getInstance();
        
        // STAGE 3.3: Wrap all operations in transaction for atomicity
        // This ensures finance entry update + job payment update + job sync are all-or-nothing
        $db->transaction(function() use ($financeId, $jobId, $amount, $paidAt, $note) {
            $paymentModel = new JobPayment();
            $existing = $paymentModel->findByFinance($financeId);
            $paidDate = self::normalizeDate($paidAt);
            $sanitizedNote = self::sanitizeNullable($note);

            $previousJobId = $existing['job_id'] ?? null;

            if ($amount <= 0) {
                if ($existing) {
                    $paymentModel->deleteByFinance($financeId);
                    if ($previousJobId) {
                        Job::syncPayments((int) $previousJobId);
                    }
                }
                return;
            }

            if ($existing) {
                $paymentModel->update($existing['id'], [
                    'job_id' => $jobId,
                    'amount' => $amount,
                    'paid_at' => $paidDate,
                    'note' => $sanitizedNote,
                    'finance_id' => $financeId
                ]);
            } else {
                $paymentModel->create([
                    'job_id' => $jobId,
                    'amount' => $amount,
                    'paid_at' => $paidDate,
                    'note' => $sanitizedNote,
                    'finance_id' => $financeId
                ]);
            }

            // STAGE 3.3: Job sync inside transaction ensures consistency
            Job::syncPayments($jobId);

            if ($existing && $previousJobId && (int) $previousJobId !== $jobId) {
                Job::syncPayments((int) $previousJobId);
            }
        });
    }

    /**
     * Removes payment rows when finance entries are deleted.
     * STAGE 3.3: Wrapped in transaction for atomicity (BUG_014)
     */
    public static function deleteFinancePayment(int $financeId): void
    {
        $db = Database::getInstance();
        
        // STAGE 3.3: Wrap payment deletion + job sync in transaction
        $db->transaction(function() use ($financeId) {
            $paymentModel = new JobPayment();
            $payment = $paymentModel->findByFinance($financeId);
            if (!$payment) {
                return;
            }

            $jobId = (int) $payment['job_id'];
            $paymentModel->deleteByFinance($financeId);
            
            // STAGE 3.3: Job sync inside transaction ensures consistency
            Job::syncPayments($jobId);
        });
    }

    /**
     * Creates a standalone payment row for a job.
     * STAGE 3.3: Wrapped in transaction for atomicity (BUG_014)
     */
    public static function createJobPayment(int $jobId, float $amount, string $paidAt, ?string $note = null, ?int $financeId = null): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $db = Database::getInstance();
        
        // STAGE 3.3: Wrap payment creation + job sync in transaction
        return $db->transaction(function() use ($jobId, $amount, $paidAt, $note, $financeId) {
            $paymentModel = new JobPayment();
            $paymentId = $paymentModel->create([
                'job_id' => $jobId,
                'amount' => $amount,
                'paid_at' => self::normalizeDate($paidAt),
                'note' => self::sanitizeNullable($note),
                'finance_id' => $financeId
            ]);

            // STAGE 3.3: Job sync inside transaction ensures consistency
            Job::syncPayments($jobId);

            return (int) $paymentId;
        });
    }

    private static function normalizeDate(?string $date): string
    {
        if (empty($date)) {
            return date('Y-m-d');
        }

        try {
            $dt = new DateTime($date);
            return $dt->format('Y-m-d');
        } catch (Exception $e) {
            return date('Y-m-d');
        }
    }

    private static function sanitizeNullable(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Utils::sanitize($value);
    }
}
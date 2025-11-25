<?php
/**
 * RecurringOccurrence Model
 */

class RecurringOccurrence
{
    use CompanyScope;

    private $db;
    
    // ===== KOZMOS_SCHEMA_COMPAT: constants (begin)
    private const COL_SCHED_DATE = 'scheduled_date';
    private const COL_SCHED_START = 'scheduled_start_at';
    private const VIEW = 'v_recurring_job_occurrences';
    // ===== KOZMOS_SCHEMA_COMPAT: constants (end)

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getByRecurringAndRange($recurringJobId, $startDate, $endDate)
    {
        $where = $this->scopeToCompany(
            "WHERE o.recurring_job_id = ? AND o." . self::COL_SCHED_DATE . " BETWEEN ? AND ?",
            'o'
        );

        return $this->db->fetchAll(
            "SELECT o.* FROM " . self::VIEW . " o {$where} ORDER BY o." . self::COL_SCHED_DATE . ", o." . self::COL_SCHED_START,
            [$recurringJobId, $startDate, $endDate]
        );
    }

    public function findExisting($recurringJobId, $date, $startAt)
    {
        $where = $this->scopeToCompany(
            "WHERE o.recurring_job_id = ? AND o." . self::COL_SCHED_DATE . " = ? AND o." . self::COL_SCHED_START . " = ?",
            'o'
        );

        return $this->db->fetch(
            "SELECT o.* FROM " . self::VIEW . " o {$where}",
            [$recurringJobId, $date, $startAt]
        );
    }

    public function createIfNotExists(array $data)
    {
        $existing = $this->findExisting($data['recurring_job_id'], $data['date'], $data['start_at']);
        if ($existing) { return $existing['id']; }
        $job = (new RecurringJob())->find((int)$data['recurring_job_id']);
        if (!$job) {
            return 0;
        }

        return $this->db->insert('recurring_job_occurrences', [
            'recurring_job_id' => (int)$data['recurring_job_id'],
            'date' => $data['date'],
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'status' => $data['status'] ?? 'PLANNED',
            'company_id' => (int)($job['company_id'] ?? $this->getCompanyIdForInsert()),
        ]);
    }

    public function markStatus($id, $status)
    {
        if (!$this->ensureOccurrenceAccess($id)) {
            return 0;
        }

        return $this->db->update('recurring_job_occurrences', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    public static function getByRecurringJobId($recurringJobId)
    {
        return (new self())->getAllByRecurringJobId($recurringJobId);
    }

    public static function findByOccurrenceId($occurrenceId)
    {
        return (new self())->findOccurrence($occurrenceId);
    }

    public static function markAsGenerated($occurrenceId, $jobId)
    {
        return (new self())->updateOccurrence($occurrenceId, [
            'status' => 'GENERATED',
            'job_id' => $jobId,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function markAsSkipped($occurrenceId)
    {
        return (new self())->updateOccurrence($occurrenceId, [
            'status' => 'SKIPPED',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function markAsConflict($occurrenceId)
    {
        return (new self())->updateOccurrence($occurrenceId, [
            'status' => 'CONFLICT',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function getAllByRecurringJobId($recurringJobId)
    {
        $where = $this->scopeToCompany(
            "WHERE o.recurring_job_id = ?",
            'o'
        );

        return $this->db->fetchAll(
            "SELECT o.* FROM " . self::VIEW . " o {$where} ORDER BY o." . self::COL_SCHED_DATE . ", o." . self::COL_SCHED_START,
            [$recurringJobId]
        );
    }

    private function findOccurrence($occurrenceId)
    {
        $where = $this->scopeToCompany('WHERE o.id = ?', 'o');
        return $this->db->fetch("SELECT o.* FROM " . self::VIEW . " o {$where}", [$occurrenceId]);
    }

    private function ensureOccurrenceAccess($occurrenceId): bool
    {
        return (bool)$this->findOccurrence($occurrenceId);
    }

    private function updateOccurrence($occurrenceId, array $payload)
    {
        if (!$this->ensureOccurrenceAccess($occurrenceId)) {
            return 0;
        }

        return $this->db->update('recurring_job_occurrences', $payload, 'id = ?', [$occurrenceId]);
    }
}

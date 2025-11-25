<?php
/**
 * RecurringGenerator Service
 */

class RecurringGenerator
{
    /**
     * Generate occurrences for a recurring job between today and daysAhead.
     */
    public static function generateForJob(int $recurringJobId, int $daysAhead = 30): int
    {
        $db = Database::getInstance();
        $jobModel = new RecurringJob();
        $occModel = new RecurringOccurrence();

        $rj = $jobModel->find($recurringJobId);
        if (!$rj || ($rj['status'] ?? '') !== 'ACTIVE') {
            return 0;
        }

        $start = new DateTime(max(date('Y-m-d'), substr($rj['start_date'], 0, 10)));
        $endDateStr = $rj['end_date'] ?? null;
        $end = new DateTime(date('Y-m-d'));
        $end->modify('+' . $daysAhead . ' day');
        if (!empty($endDateStr)) {
            $limit = new DateTime(substr($endDateStr, 0, 10));
            if ($limit < $end) { $end = $limit; }
        }

        $tz = new DateTimeZone($rj['timezone'] ?: 'Europe/Istanbul');
        $byweekday = RecurringJob::decodeJsonList($rj['byweekday'] ?? null);
        $exclusions = array_flip(RecurringJob::decodeJsonList($rj['exclusions'] ?? null));
        $frequency = $rj['frequency'];
        $interval = max(1, (int)($rj['interval'] ?? 1));
        $byhour = $rj['byhour'] !== null ? (int)$rj['byhour'] : 9;
        $byminute = $rj['byminute'] !== null ? (int)$rj['byminute'] : 0;
        $duration = max(15, (int)($rj['duration_min'] ?? 60));

        $created = 0;
        $cur = clone $start;

        if ($frequency === 'DAILY') {
            $step = new DateInterval('P' . $interval . 'D');
            while ($cur <= $end) {
                $dateStr = $cur->format('Y-m-d');
                if (!isset($exclusions[$dateStr])) {
                    $created += self::createOccurrenceIfEligible($occModel, $rj['id'], $dateStr, $byhour, $byminute, $duration, $tz);
                }
                $cur->add($step);
            }
        } elseif ($frequency === 'WEEKLY') {
            // Map weekday strings to ISO numbers 1..7
            $weekdayMap = ['MON'=>1,'TUE'=>2,'WED'=>3,'THU'=>4,'FRI'=>5,'SAT'=>6,'SUN'=>7];
            $wanted = array_values(array_filter(array_map(function($w) use ($weekdayMap){ return $weekdayMap[strtoupper($w)] ?? null; }, $byweekday)));
            if (empty($wanted)) { $wanted = [(int)$cur->format('N')]; }

            // Advance week by interval
            while ($cur <= $end) {
                // For each wanted weekday in this week window
                $weekStart = (clone $cur)->modify('monday this week');
                foreach ($wanted as $iso) {
                    $d = (clone $weekStart)->modify('+' . ($iso-1) . ' day');
                    if ($d < $start) { continue; }
                    if ($d > $end) { continue; }
                    $dateStr = $d->format('Y-m-d');
                    if (!isset($exclusions[$dateStr])) {
                        $created += self::createOccurrenceIfEligible($occModel, $rj['id'], $dateStr, $byhour, $byminute, $duration, $tz);
                    }
                }
                $cur->modify('+' . ($interval) . ' week');
            }
        } elseif ($frequency === 'MONTHLY') {
            $bymonthday = isset($rj['bymonthday']) ? (int)$rj['bymonthday'] : null;
            if ($bymonthday === null || $bymonthday < 1 || $bymonthday > 31) {
                // Default to start date's day if not specified
                $bymonthday = (int)$start->format('d');
            }
            
            while ($cur <= $end) {
                // Get last day of current month
                $lastDayOfMonth = (int)$cur->format('t');
                
                // Use specified day, but if it exceeds month's last day, use last day
                $day = min($bymonthday, $lastDayOfMonth);
                
                try {
                    $d = (clone $cur)->setDate((int)$cur->format('Y'), (int)$cur->format('m'), $day);
                    
                    if ($d >= $start && $d <= $end) {
                        $dateStr = $d->format('Y-m-d');
                        if (!isset($exclusions[$dateStr])) {
                            $created += self::createOccurrenceIfEligible($occModel, $rj['id'], $dateStr, $byhour, $byminute, $duration, $tz);
                        }
                    }
                } catch (Exception $e) {
                    // Invalid date (e.g., Feb 31), skip this month
                    error_log("Invalid date generated for MONTHLY recurrence: " . $e->getMessage());
                }
                
                // Move to next month interval
                $cur->modify('+' . $interval . ' month');
            }
        }

        return $created;
    }

    private static function createOccurrenceIfEligible(RecurringOccurrence $occModel, int $recurringJobId, string $dateStr, int $hour, int $minute, int $durationMin, DateTimeZone $tz): int
    {
        $startDt = new DateTime($dateStr . ' ' . sprintf('%02d:%02d', $hour, $minute), $tz);
        $endDt = (clone $startDt)->modify('+' . $durationMin . ' minutes');
        $occId = $occModel->createIfNotExists([
            'recurring_job_id' => $recurringJobId,
            'date' => $dateStr,
            'start_at' => $startDt->format('Y-m-d H:i'),
            'end_at' => $endDt->format('Y-m-d H:i'),
            'status' => 'PLANNED',
        ]);
        return $occId ? 1 : 0;
    }

    /**
     * Materialize occurrences into jobs (idempotent):
     * If a job with occurrence_id exists, skip. No auto finance creation.
     */
    public static function materializeToJobs(int $recurringJobId): int
    {
        $db = Database::getInstance();
        $jobModel = new RecurringJob();
        $occModel = new RecurringOccurrence();
        $rj = $jobModel->find($recurringJobId);
        if (!$rj) { return 0; }

        // Only process PLANNED occurrences, not GENERATED ones
        $occurrences = $db->fetchAll("SELECT * FROM recurring_job_occurrences WHERE recurring_job_id = ? AND status = 'PLANNED' ORDER BY date, start_at", [$recurringJobId]);
        $inserted = 0;
        $changes = 0; // track any state changes (e.g., conflicts)
        
        // No occurrences to process
        if (empty($occurrences)) {
            return 0;
        }
        
        // Use transaction for atomicity
        try {
            $db->beginTransaction();
        } catch (Exception $e) {
            error_log("Failed to begin transaction: " . $e->getMessage());
            // Continue without transaction if not supported
        }

        foreach ($occurrences as $occ) {
            // Check if a job already exists for this occurrence
            $exists = $db->fetch('SELECT id FROM jobs WHERE occurrence_id = ?', [$occ['id']]);
            if ($exists) {
                continue;
            }

            // Validate foreign key references before inserting
            // Check if customer exists
            if (empty($rj['customer_id'])) {
                error_log("Recurring job {$recurringJobId}: customer_id is empty");
                continue;
            }
            $customerExists = $db->fetch('SELECT id FROM customers WHERE id = ?', [$rj['customer_id']]);
            if (!$customerExists) {
                error_log("Recurring job {$recurringJobId}: customer_id {$rj['customer_id']} does not exist");
                continue;
            }

            // Check if service exists (if provided)
            $serviceId = $rj['service_id'] ?? null;
            if ($serviceId !== null) {
                $serviceExists = $db->fetch('SELECT id FROM services WHERE id = ?', [$serviceId]);
                if (!$serviceExists) {
                    error_log("Recurring job {$recurringJobId}: service_id {$serviceId} does not exist, setting to null");
                    $serviceId = null;
                }
            }

            // Check if address exists (if provided)
            $addressId = $rj['address_id'] ?? null;
            if ($addressId !== null) {
                $addressExists = $db->fetch('SELECT id FROM addresses WHERE id = ?', [$addressId]);
                if (!$addressExists) {
                    error_log("Recurring job {$recurringJobId}: address_id {$addressId} does not exist, setting to null");
                    $addressId = null;
                }
            }

            // Calculate total_amount based on pricing model
            $pricingModel = $rj['pricing_model'] ?? 'PER_JOB';
            $totalAmount = 0;
            
            switch ($pricingModel) {
                case 'PER_JOB':
                    $totalAmount = (float)($rj['default_total_amount'] ?? 0);
                    break;
                case 'PER_MONTH':
                case 'TOTAL_CONTRACT':
                    // These models don't set amount on individual jobs
                    $totalAmount = 0;
                    break;
                default:
                    $totalAmount = (float)($rj['default_total_amount'] ?? 0);
            }
            
            $payload = [
                'service_id' => $serviceId,
                'customer_id' => $rj['customer_id'],
                'company_id' => $rj['company_id'] ?? 1,
                'address_id' => $addressId,
                'start_at' => $occ['start_at'],
                'end_at' => $occ['end_at'],
                'status' => 'SCHEDULED',
                'assigned_to' => null,
                'note' => $rj['default_notes'] ?? null,
                'total_amount' => $totalAmount,
                'amount_paid' => 0,
                'payment_status' => 'UNPAID',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'recurring_job_id' => $recurringJobId,
                'occurrence_id' => $occ['id'],
            ];

            // LOGIC-001 FIX: Check for conflicts BEFORE creating job
            // SECURITY: Add company_id filter for multi-tenant isolation
            $recurringJobCompanyId = $rj['company_id'] ?? null;
            $conflicts = $db->fetchAll("
                SELECT j.id, j.start_at, j.end_at, c.name as customer_name
                FROM jobs j
                LEFT JOIN customers c ON j.customer_id = c.id
                WHERE j.status != 'CANCELLED'
                  AND j.id != 0
                  AND (strftime('%s', j.end_at) - strftime('%s', j.start_at)) <= 43200 -- ignore spans > 12h
                  " . ($recurringJobCompanyId ? "AND j.company_id = ?" : "") . "
                  AND (
                    (j.start_at <= ? AND j.end_at > ?) OR
                    (j.start_at < ? AND j.end_at >= ?) OR
                    (j.start_at >= ? AND j.end_at <= ?)
                  )
            ", array_merge(
                $recurringJobCompanyId ? [$recurringJobCompanyId] : [],
                [
                    $occ['start_at'], $occ['start_at'],  // Starts before or at same time
                    $occ['end_at'], $occ['end_at'],      // Ends after or at same time
                    $occ['start_at'], $occ['end_at']     // Completely within
                ]
            ));
            
            if (!empty($conflicts)) {
                // Mark occurrence as CONFLICT instead of creating job
                $db->update('recurring_job_occurrences', [
                    'status' => 'CONFLICT',
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$occ['id']]);
                $changes++;
                
                // Send notification to admin
                try {
                    if (class_exists('NotificationService') && method_exists('NotificationService', 'send')) {
                        NotificationService::send([
                            'type' => 'recurring_conflict',
                            'title' => 'İş Çakışması Tespit Edildi',
                            'message' => "Periyodik iş oluşturulurken çakışma: {$occ['start_at']} - Mevcut iş ile çakışıyor",
                            'data' => [
                                'recurring_job_id' => $recurringJobId,
                                'occurrence_id' => $occ['id'],
                                'conflict_with' => $conflicts[0]['id']
                            ],
                            'action_url' => '/recurring/' . $recurringJobId
                        ]);
                    }
                } catch (Exception $notifError) {
                    error_log("Conflict notification failed: " . $notifError->getMessage());
                }
                
                error_log("Conflict detected for recurring job {$recurringJobId}, occurrence {$occ['id']}: overlaps with job {$conflicts[0]['id']}");
                continue; // Skip job creation
            }
            
            try {
                $jobId = $db->insert('jobs', $payload);
                
                if (!$jobId) {
                    error_log("Failed to get job ID after insert for occurrence {$occ['id']}");
                    continue;
                }
                
                // Update occurrence with job_id and mark as GENERATED
                $updateResult = $db->update('recurring_job_occurrences', [
                    'status' => 'GENERATED',
                    'job_id' => $jobId,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$occ['id']]);
                
                if ($updateResult === false || $updateResult === 0) {
                    error_log("Failed to update occurrence {$occ['id']} with job_id {$jobId}");
                    // Rollback the job insert by deleting it
                    try {
                        $db->delete('jobs', 'id = ?', [$jobId]);
                    } catch (Exception $deleteErr) {
                        error_log("Failed to rollback job {$jobId}: " . $deleteErr->getMessage());
                    }
                    continue;
                }
                
                $inserted++;
                $changes++;
            } catch (Exception $e) {
                // Log error safely (don't expose sensitive payload in production)
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Failed to create job for occurrence {$occ['id']}: " . $e->getMessage());
                    error_log("Payload: " . json_encode($payload));
                    error_log("Stack trace: " . $e->getTraceAsString());
                } else {
                    error_log("Failed to create job for occurrence {$occ['id']}: " . $e->getMessage());
                }
                // Continue with next occurrence instead of failing completely
                continue;
            }
        }
        
        // Commit transaction if no critical errors, rollback on failure
        try {
            if ($db->inTransaction()) {
                if ($inserted > 0 || $changes > 0) {
                    $db->commit();
                } else {
                    // No jobs inserted, rollback to avoid partial state
                    $db->rollback();
                }
            }
        } catch (Exception $e) {
            error_log("Transaction commit/rollback failed: " . $e->getMessage());
            if ($db->inTransaction()) {
                try {
                    $db->rollback();
                } catch (Exception $rollbackErr) {
                    error_log("Rollback also failed: " . $rollbackErr->getMessage());
                }
            }
            // Don't throw - we've already logged and handled errors per occurrence
        }

        return $inserted;
    }

    /**
     * Preview first N occurrences from an unsaved or saved definition-like array.
     */
    public static function preview(array $definition, int $limit = 10): array
    {
        $tz = new DateTimeZone($definition['timezone'] ?? 'Europe/Istanbul');
        $byweekday = RecurringJob::decodeJsonList($definition['byweekday'] ?? []);
        $exclusions = array_flip(RecurringJob::decodeJsonList($definition['exclusions'] ?? []));
        $frequency = $definition['frequency'];
        $interval = max(1, (int)($definition['interval'] ?? 1));
        $byhour = isset($definition['byhour']) ? (int)$definition['byhour'] : 9;
        $byminute = isset($definition['byminute']) ? (int)$definition['byminute'] : 0;
        $duration = max(15, (int)($definition['duration_min'] ?? 60));
        $startDate = new DateTime(substr($definition['start_date'], 0, 10));
        $endDate = (new DateTime())->modify('+2 years');
        if (!empty($definition['end_date'])) {
            $tmp = new DateTime(substr($definition['end_date'], 0, 10));
            if ($tmp < $endDate) { $endDate = $tmp; }
        }

        $out = [];
        $cur = clone $startDate;
        if ($frequency === 'DAILY') {
            $step = new DateInterval('P' . $interval . 'D');
            while ($cur <= $endDate && count($out) < $limit) {
                $dateStr = $cur->format('Y-m-d');
                if (!isset($exclusions[$dateStr])) {
                    $startDt = new DateTime($dateStr . ' ' . sprintf('%02d:%02d', $byhour, $byminute), $tz);
                    $endDt = (clone $startDt)->modify('+' . $duration . ' minutes');
                    $out[] = ['date' => $dateStr, 'start_at' => $startDt->format('Y-m-d H:i'), 'end_at' => $endDt->format('Y-m-d H:i')];
                }
                $cur->add($step);
            }
        } elseif ($frequency === 'WEEKLY') {
            $weekdayMap = ['MON'=>1,'TUE'=>2,'WED'=>3,'THU'=>4,'FRI'=>5,'SAT'=>6,'SUN'=>7];
            $wanted = array_values(array_filter(array_map(function($w) use ($weekdayMap){ return $weekdayMap[strtoupper($w)] ?? null; }, $byweekday)));
            if (empty($wanted)) { $wanted = [(int)$cur->format('N')]; }
            while ($cur <= $endDate && count($out) < $limit) {
                $weekStart = (clone $cur)->modify('monday this week');
                foreach ($wanted as $iso) {
                    $d = (clone $weekStart)->modify('+' . ($iso-1) . ' day');
                    if ($d < $startDate) { continue; }
                    $dateStr = $d->format('Y-m-d');
                    if (!isset($exclusions[$dateStr])) {
                        $startDt = new DateTime($dateStr . ' ' . sprintf('%02d:%02d', $byhour, $byminute), $tz);
                        $endDt = (clone $startDt)->modify('+' . $duration . ' minutes');
                        $out[] = ['date' => $dateStr, 'start_at' => $startDt->format('Y-m-d H:i'), 'end_at' => $endDt->format('Y-m-d H:i')];
                        if (count($out) >= $limit) { break; }
                    }
                }
                $cur->modify('+' . ($interval) . ' week');
            }
        } elseif ($frequency === 'MONTHLY') {
            $bymonthday = isset($definition['bymonthday']) ? (int)$definition['bymonthday'] : null;
            if ($bymonthday === null || $bymonthday < 1 || $bymonthday > 31) {
                $bymonthday = (int)$startDate->format('d');
            }
            
            while ($cur <= $endDate && count($out) < $limit) {
                $lastDayOfMonth = (int)$cur->format('t');
                $day = min($bymonthday, $lastDayOfMonth);
                
                try {
                    $d = (clone $cur)->setDate((int)$cur->format('Y'), (int)$cur->format('m'), $day);
                    if ($d >= $startDate) {
                        $dateStr = $d->format('Y-m-d');
                        if (!isset($exclusions[$dateStr])) {
                            $startDt = new DateTime($dateStr . ' ' . sprintf('%02d:%02d', $byhour, $byminute), $tz);
                            $endDt = (clone $startDt)->modify('+' . $duration . ' minutes');
                            $out[] = ['date' => $dateStr, 'start_at' => $startDt->format('Y-m-d H:i'), 'end_at' => $endDt->format('Y-m-d H:i')];
                            if (count($out) >= $limit) { break; }
                        }
                    }
                } catch (Exception $e) {
                    // Skip invalid dates
                }
                
                $cur->modify('+' . $interval . ' month');
            }
        }
        return $out;
    }

    public function updateFutureOccurrences($recurringJobId, $newHour, $newMinute)
    {
        $db = Database::getInstance();
        
        // ===== KOZMOS_SCHEMA_COMPAT: use actual columns (begin)
        // Update future occurrences
        $sql = "UPDATE recurring_job_occurrences 
                SET start_at = date(date) || ' ' || ? || ':' || ?,
                    end_at = datetime(start_at, '+' || duration_min || ' minutes')
                WHERE recurring_job_id = ? 
                AND date >= date('now')
                AND status = 'PLANNED'";
        // ===== KOZMOS_SCHEMA_COMPAT: use actual columns (end)
        
        $db->query($sql, [
            str_pad($newHour, 2, '0', STR_PAD_LEFT),
            str_pad($newMinute, 2, '0', STR_PAD_LEFT),
            $recurringJobId
        ]);
        
        return true;
    }

    public function deleteFutureOccurrences($recurringJobId)
    {
        $db = Database::getInstance();
        
        // ===== KOZMOS_SCHEMA_COMPAT: use actual columns (begin)
        // Count future occurrences
        $countSql = "SELECT COUNT(*) as count FROM recurring_job_occurrences 
                     WHERE recurring_job_id = ? 
                     AND date >= date('now')
                     AND status = 'PLANNED'";
        // ===== KOZMOS_SCHEMA_COMPAT: use actual columns (end)
        $count = $db->fetch($countSql, [$recurringJobId])['count'];
        
        // ===== KOZMOS_SCHEMA_COMPAT: use actual columns (begin)
        // Delete future occurrences
        $deleteSql = "DELETE FROM recurring_job_occurrences 
                      WHERE recurring_job_id = ? 
                      AND date >= date('now')
                      AND status = 'PLANNED'";
        // ===== KOZMOS_SCHEMA_COMPAT: use actual columns (end)
        
        $db->query($deleteSql, [$recurringJobId]);
        
        return $count;
    }

    public function generateSingleOccurrence($recurringJobId, $occurrenceId)
    {
        $occurrence = RecurringOccurrence::findByOccurrenceId($occurrenceId);
        if (!$occurrence) {
            throw new Exception("Occurrence not found: {$occurrenceId}");
        }
        
        if ($occurrence['status'] !== 'PLANNED') {
            throw new Exception("Occurrence is not in PLANNED status");
        }
        
        // Create the actual job
        $recurringJobModel = new RecurringJob();
        $recurringJob = $recurringJobModel->find($recurringJobId);
        $jobId = (new Job())->create([
            'customer_id' => $recurringJob['customer_id'],
            'address_id' => $recurringJob['address_id'],
            'service_id' => $recurringJob['service_id'],
            // ===== KOZMOS_SCHEMA_COMPAT: use actual columns (begin)
            'start_at' => $occurrence['start_at'],
            'end_at' => $occurrence['end_at'],
            // ===== KOZMOS_SCHEMA_COMPAT: use actual columns (end)
            'total_amount' => $recurringJob['default_total_amount'],
            'note' => $recurringJob['default_notes'],
            'status' => 'SCHEDULED',
            'recurring_job_id' => $recurringJobId,
            'occurrence_id' => $occurrenceId
        ]);
        
        // Mark occurrence as generated
        RecurringOccurrence::markAsGenerated($occurrenceId, $jobId);
        
        return $jobId;
    }
}

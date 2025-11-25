<?php

require_once __DIR__ . '/../Lib/Calendar/Providers/ProviderInterface.php';
require_once __DIR__ . '/../Lib/Calendar/Providers/GoogleProvider.php';
require_once __DIR__ . '/../Lib/Calendar/Providers/MicrosoftProvider.php';
require_once __DIR__ . '/../Models/CalendarExternalEvent.php';
require_once __DIR__ . '/../Lib/Calendar/Normalizer.php';

class CalendarSyncService
{
    private static $syncModel;

    private static function getSyncModel(): CalendarSync
    {
        if (!self::$syncModel) {
            self::$syncModel = new CalendarSync();
        }
        return self::$syncModel;
    }

    public static function getProvider(string $provider): CalendarProviderInterface
    {
        if ($provider === 'google') { return new GoogleProvider(); }
        if ($provider === 'microsoft') { return new MicrosoftProvider(); }
        throw new InvalidArgumentException('Unknown provider: ' . $provider);
    }

    public static function initialSync(int $userId, string $provider): array
    {
        $prov = self::getProvider($provider);
        $result = $prov->fetchIncremental($userId, null);
        $events = $result['events'] ?? [];
        $extModel = new CalendarExternalEvent();
        $count = 0;
        foreach ($events as $ev) {
            // Expect normalized structure from provider adapter
            $fp = CalendarNormalizer::externalFingerprint($ev);
            $extModel->upsert([
                'user_id' => $userId,
                'provider' => $provider,
                'external_id' => $ev['external_id'] ?? '',
                'etag' => $ev['etag'] ?? null,
                'job_id' => null,
                'fingerprint' => $fp,
            ]);
            $count++;
        }
        // Save next_cursor for future incremental syncs
        if ($result['next_cursor']) {
            self::getSyncModel()->updateCursor($userId, $provider, $result['next_cursor']);
        }
        return ['fetched' => $count, 'next_cursor' => $result['next_cursor'] ?? null];
    }

    public static function incrementalSync(int $userId, string $provider): array
    {
        $cursor = self::getSyncModel()->getCursor($userId, $provider);
        if (!$cursor) {
            // No cursor yet, run initial sync
            return self::initialSync($userId, $provider);
        }
        $prov = self::getProvider($provider);
        $result = $prov->fetchIncremental($userId, $cursor);
        $events = $result['events'] ?? [];
        $extModel = new CalendarExternalEvent();
        $count = 0;
        $conflicts = 0;
        foreach ($events as $ev) {
            $fp = CalendarNormalizer::externalFingerprint($ev);
            // Check for fingerprint mismatch (conflict)
            $existing = self::getSyncModel()->db->fetch(
                "SELECT etag, fingerprint FROM calendar_external_events WHERE user_id=? AND provider=? AND external_id=?",
                [$userId, $provider, $ev['external_id'] ?? '']
            );
            
            if ($existing && $existing['fingerprint'] !== $fp && $existing['etag'] !== ($ev['etag'] ?? null)) {
                // Conflict detected: external event changed since last sync
                // Strategy: last-write-wins (update to new version)
                $conflicts++;
                Logger::warning('Calendar sync conflict resolved', [
                    'user_id' => $userId,
                    'provider' => $provider,
                    'external_id' => $ev['external_id']
                ]);
            }
            
            $extModel->upsert([
                'user_id' => $userId,
                'provider' => $provider,
                'external_id' => $ev['external_id'] ?? '',
                'etag' => $ev['etag'] ?? null,
                'job_id' => null,
                'fingerprint' => $fp,
            ]);
            $count++;
        }
        if ($result['next_cursor']) {
            self::getSyncModel()->updateCursor($userId, $provider, $result['next_cursor']);
        }
        return ['fetched' => $count, 'conflicts' => $conflicts, 'next_cursor' => $result['next_cursor'] ?? null];
    }

    /**
     * Push local job to external calendar (create/update/delete)
     */
    public static function pushJob(int $jobId, string $provider, string $action = 'sync'): bool
    {
        try {
            $db = Database::getInstance();
            $job = $db->fetch(
                "SELECT j.*, c.name AS customer_name, s.name AS service_name, u.id AS user_id
                 FROM jobs j
                 LEFT JOIN customers c ON c.id=j.customer_id
                 LEFT JOIN services s ON s.id=j.service_id
                 LEFT JOIN users u ON u.id=j.assigned_user_id
                 WHERE j.id = ?",
                [$jobId]
            );
            
            if (!$job) {
                return false;
            }
            
            $userId = (int)$job['user_id'];
            $prov = self::getProvider($provider);
            
            // Check if job already linked to external event
            $linked = $db->fetch(
                "SELECT external_id FROM calendar_external_events WHERE user_id=? AND provider=? AND job_id=?",
                [$userId, $provider, $jobId]
            );
            
            $normalized = CalendarNormalizer::jobToExternal($job);
            
            if ($linked && $action === 'delete') {
                // Delete from external calendar
                return $prov->deleteEvent($userId, $linked['external_id']);
            } elseif ($linked) {
                // Update existing
                $result = $prov->updateEvent($userId, $linked['external_id'], $normalized);
                if ($result['external_id']) {
                    $db->update('calendar_external_events', [
                        'etag' => $result['etag'],
                        'last_sync_at' => date('Y-m-d H:i:s')
                    ], 'user_id = :uid AND provider = :p AND job_id = :jid', [
                        'uid' => $userId, 'p' => $provider, 'jid' => $jobId
                    ]);
                }
            } else {
                // Create new
                $result = $prov->createEvent($userId, $normalized);
                if ($result['external_id']) {
                    $fp = CalendarNormalizer::externalFingerprint($normalized);
                    $db->insert('calendar_external_events', [
                        'user_id' => $userId,
                        'provider' => $provider,
                        'external_id' => $result['external_id'],
                        'etag' => $result['etag'],
                        'job_id' => $jobId,
                        'last_sync_at' => date('Y-m-d H:i:s'),
                        'fingerprint' => $fp
                    ]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            Logger::error('Failed to push job to external calendar', [
                'job_id' => $jobId,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}



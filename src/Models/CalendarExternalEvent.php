<?php

class CalendarExternalEvent
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function upsert(array $data): void
    {
        $existing = $this->db->fetch(
            "SELECT id FROM calendar_external_events WHERE user_id=? AND provider=? AND external_id=?",
            [(int)$data['user_id'], $data['provider'], $data['external_id']]
        );
        $payload = [
            'user_id' => (int)$data['user_id'],
            'provider' => $data['provider'],
            'external_id' => $data['external_id'],
            'etag' => $data['etag'] ?? null,
            'job_id' => $data['job_id'] ?? null,
            'last_sync_at' => date('Y-m-d H:i:s'),
            'fingerprint' => $data['fingerprint'] ?? null,
        ];
        if ($existing) {
            unset($payload['user_id'], $payload['provider'], $payload['external_id']);
            $this->db->update('calendar_external_events', $payload, 'id = :id', ['id' => $existing['id']]);
        } else {
            $this->db->insert('calendar_external_events', $payload);
        }
    }
}



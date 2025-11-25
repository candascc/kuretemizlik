<?php

require_once __DIR__ . '/ProviderInterface.php';

class GoogleProvider implements CalendarProviderInterface
{
    private $syncModel;

    public function __construct()
    {
        $this->syncModel = new CalendarSync();
    }

    public function fetchIncremental(int $userId, ?string $cursor = null): array
    {
        $token = $this->syncModel->getValidAccessToken($userId, 'google');
        if (!$token) {
            return ['events' => [], 'next_cursor' => null];
        }
        $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events?maxResults=250';
        if ($cursor) {
            $url .= '&syncToken=' . urlencode($cursor);
        } else {
            $url .= '&timeMin=' . date('c', strtotime('-30 days'));
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_TIMEOUT => 20
        ]);
        $resp = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http >= 400) {
            return ['events' => [], 'next_cursor' => $cursor];
        }
        $data = json_decode($resp, true) ?: [];
        $events = [];
        foreach ($data['items'] ?? [] as $ev) {
            if (($ev['status'] ?? '') === 'cancelled') {
                continue;
            }
            $start = $ev['start']['dateTime'] ?? $ev['start']['date'] ?? null;
            $end = $ev['end']['dateTime'] ?? $ev['end']['date'] ?? null;
            if (!$start || !$end) {
                continue;
            }
            $events[] = [
                'external_id' => $ev['id'] ?? '',
                'etag' => $ev['etag'] ?? null,
                'title' => $ev['summary'] ?? '',
                'description' => $ev['description'] ?? '',
                'location' => $ev['location'] ?? '',
                'start_at' => $start,
                'end_at' => $end
            ];
        }
        return ['events' => $events, 'next_cursor' => $data['nextSyncToken'] ?? $data['nextPageToken'] ?? null];
    }

    public function createEvent(int $userId, array $payload): array
    {
        $token = $this->syncModel->getValidAccessToken($userId, 'google');
        if (!$token) {
            return ['external_id' => null, 'etag' => null];
        }
        $body = [
            'summary' => $payload['title'] ?? '',
            'description' => $payload['description'] ?? '',
            'location' => $payload['location'] ?? '',
            'start' => ['dateTime' => $payload['start_at'], 'timeZone' => 'Europe/Istanbul'],
            'end' => ['dateTime' => $payload['end_at'], 'timeZone' => 'Europe/Istanbul']
        ];
        $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 15
        ]);
        $resp = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http >= 400) {
            return ['external_id' => null, 'etag' => null];
        }
        $data = json_decode($resp, true) ?: [];
        return ['external_id' => $data['id'] ?? null, 'etag' => $data['etag'] ?? null];
    }

    public function updateEvent(int $userId, string $externalId, array $payload): array
    {
        $token = $this->syncModel->getValidAccessToken($userId, 'google');
        if (!$token) {
            return ['external_id' => $externalId, 'etag' => null];
        }
        $body = [
            'summary' => $payload['title'] ?? '',
            'description' => $payload['description'] ?? '',
            'location' => $payload['location'] ?? '',
            'start' => ['dateTime' => $payload['start_at'], 'timeZone' => 'Europe/Istanbul'],
            'end' => ['dateTime' => $payload['end_at'], 'timeZone' => 'Europe/Istanbul']
        ];
        $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events/' . urlencode($externalId));
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 15
        ]);
        $resp = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http >= 400) {
            return ['external_id' => $externalId, 'etag' => null];
        }
        $data = json_decode($resp, true) ?: [];
        return ['external_id' => $externalId, 'etag' => $data['etag'] ?? null];
    }

    public function deleteEvent(int $userId, string $externalId): bool
    {
        $token = $this->syncModel->getValidAccessToken($userId, 'google');
        if (!$token) {
            return false;
        }
        $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events/' . urlencode($externalId));
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_TIMEOUT => 10
        ]);
        curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $http >= 200 && $http < 300;
    }
}



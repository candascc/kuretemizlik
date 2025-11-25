<?php

require_once __DIR__ . '/ProviderInterface.php';

class MicrosoftProvider implements CalendarProviderInterface
{
    private $syncModel;

    public function __construct()
    {
        $this->syncModel = new CalendarSync();
    }

    public function fetchIncremental(int $userId, ?string $cursor = null): array
    {
        $token = $this->syncModel->getValidAccessToken($userId, 'microsoft');
        if (!$token) {
            return ['events' => [], 'next_cursor' => null];
        }
        $url = 'https://graph.microsoft.com/v1.0/me/calendarview?$top=250&$orderby=start/dateTime';
        if ($cursor) {
            $url .= '&$skiptoken=' . urlencode($cursor);
        } else {
            $start = date('c', strtotime('-30 days'));
            $end = date('c', strtotime('+365 days'));
            $url .= '&startDateTime=' . urlencode($start) . '&endDateTime=' . urlencode($end);
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
        foreach ($data['value'] ?? [] as $ev) {
            if (!isset($ev['id']) || !isset($ev['start'])) {
                continue;
            }
            $events[] = [
                'external_id' => $ev['id'] ?? '',
                'etag' => ($ev['@odata.etag'] ?? null) ? str_replace('"', '', $ev['@odata.etag']) : null,
                'title' => $ev['subject'] ?? '',
                'description' => $ev['body']['content'] ?? '',
                'location' => ($ev['location']['displayName'] ?? ''),
                'start_at' => $ev['start']['dateTime'] ?? '',
                'end_at' => $ev['end']['dateTime'] ?? ''
            ];
        }
        return ['events' => $events, 'next_cursor' => $data['@odata.nextLink'] ? parse_url($data['@odata.nextLink'], PHP_URL_QUERY) : null];
    }

    public function createEvent(int $userId, array $payload): array
    {
        $token = $this->syncModel->getValidAccessToken($userId, 'microsoft');
        if (!$token) {
            return ['external_id' => null, 'etag' => null];
        }
        $body = [
            'subject' => $payload['title'] ?? '',
            'body' => ['contentType' => 'text', 'content' => $payload['description'] ?? ''],
            'location' => ['displayName' => $payload['location'] ?? ''],
            'start' => ['dateTime' => $payload['start_at'], 'timeZone' => 'Europe/Istanbul'],
            'end' => ['dateTime' => $payload['end_at'], 'timeZone' => 'Europe/Istanbul']
        ];
        $ch = curl_init('https://graph.microsoft.com/v1.0/me/events');
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
        return ['external_id' => $data['id'] ?? null, 'etag' => ($data['@odata.etag'] ?? null) ? str_replace('"', '', $data['@odata.etag']) : null];
    }

    public function updateEvent(int $userId, string $externalId, array $payload): array
    {
        $token = $this->syncModel->getValidAccessToken($userId, 'microsoft');
        if (!$token) {
            return ['external_id' => $externalId, 'etag' => null];
        }
        $body = [
            'subject' => $payload['title'] ?? '',
            'body' => ['contentType' => 'text', 'content' => $payload['description'] ?? ''],
            'location' => ['displayName' => $payload['location'] ?? ''],
            'start' => ['dateTime' => $payload['start_at'], 'timeZone' => 'Europe/Istanbul'],
            'end' => ['dateTime' => $payload['end_at'], 'timeZone' => 'Europe/Istanbul']
        ];
        $ch = curl_init('https://graph.microsoft.com/v1.0/me/events/' . urlencode($externalId));
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PATCH',
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
        return ['external_id' => $externalId, 'etag' => ($data['@odata.etag'] ?? null) ? str_replace('"', '', $data['@odata.etag']) : null];
    }

    public function deleteEvent(int $userId, string $externalId): bool
    {
        $token = $this->syncModel->getValidAccessToken($userId, 'microsoft');
        if (!$token) {
            return false;
        }
        $ch = curl_init('https://graph.microsoft.com/v1.0/me/events/' . urlencode($externalId));
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



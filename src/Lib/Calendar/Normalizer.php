<?php

class CalendarNormalizer
{
    public static function jobToExternal(array $job): array
    {
        return [
            'title' => trim(($job['service_name'] ?? '') . ' - ' . ($job['customer_name'] ?? '')),
            'description' => $job['notes'] ?? '',
            'start_at' => $job['start_at'],
            'end_at' => $job['end_at'] ?? $job['start_at'],
            'location' => $job['address_line'] ?? ''
        ];
    }

    public static function externalFingerprint(array $ext): string
    {
        return sha1(json_encode([
            $ext['title'] ?? '', $ext['description'] ?? '', $ext['start_at'] ?? '', $ext['end_at'] ?? ''
        ], JSON_UNESCAPED_UNICODE));
    }
}



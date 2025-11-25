<?php

interface CalendarProviderInterface
{
    /**
     * Fetch events changed since the given sync cursor.
     * @param int $userId
     * @param string|null $cursor
     * @return array [ 'events' => array<array>, 'next_cursor' => string|null ]
     */
    public function fetchIncremental(int $userId, ?string $cursor = null): array;

    /**
     * Create an external event based on local job payload.
     * @param int $userId
     * @param array $payload normalized fields (title, description, start_at, end_at, location)
     * @return array provider response including 'external_id' and 'etag'
     */
    public function createEvent(int $userId, array $payload): array;

    /**
     * Update external event.
     */
    public function updateEvent(int $userId, string $externalId, array $payload): array;

    /**
     * Delete external event.
     */
    public function deleteEvent(int $userId, string $externalId): bool;
}



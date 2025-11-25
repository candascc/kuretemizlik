<?php

interface ResidentMetricsCacheInterface
{
    public function get(string $key): ?array;

    public function set(string $key, array $value, int $ttl): void;

    public function clear(?string $pattern = null): void;
}


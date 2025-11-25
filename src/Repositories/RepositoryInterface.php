<?php
/**
 * Repository Interface
 */

interface RepositoryInterface
{
    public function find(int $id): ?array;
    public function all(array $filters = [], int $limit = null, int $offset = 0): array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function count(array $filters = []): int;
}

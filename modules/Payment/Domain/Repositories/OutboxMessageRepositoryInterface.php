<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Repositories;

use Modules\Payment\Domain\Entities\OutboxMessage;

interface OutboxMessageRepositoryInterface
{
    public function create(array $data): OutboxMessage;

    public function update(int $id, array $data): OutboxMessage;

    public function findById(int $id): ?OutboxMessage;

    public function findByIdempotencyKey(string $key): ?OutboxMessage;

    public function findPendingForDelivery(int $limit = 100): array;

    public function findFailed(): array;

    public function deleteDeliveredBefore(\DateTimeImmutable $cutoff): int;
}

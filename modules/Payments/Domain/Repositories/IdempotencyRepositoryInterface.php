<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Repositories;

use Modules\Payments\Domain\ValueObjects\IdempotencyKey;

interface IdempotencyRepositoryInterface
{
    public function exists(int $tenantId, string $operation, IdempotencyKey $key): bool;
    
    /** @return array<string, mixed>|null */
    public function getResponse(int $tenantId, string $operation, IdempotencyKey $key): ?array;
    
    /** @param array<string, mixed> $response */
    public function saveResponse(int $tenantId, string $operation, IdempotencyKey $key, array $response, int $ttlSeconds = 86400): void;
}

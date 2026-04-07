<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Repositories;

use Modules\Payments\Domain\Repositories\IdempotencyRepositoryInterface;
use Modules\Payments\Domain\ValueObjects\IdempotencyKey;
use Modules\Payments\Infrastructure\Models\IdempotencyModel;

/**
 * Реализация IdempotencyRepository через Eloquent.
 */
final class EloquentIdempotencyRepository implements IdempotencyRepositoryInterface
{
    public function exists(int $tenantId, string $operation, IdempotencyKey $key): bool
    {
        return IdempotencyModel::where('tenant_id', $tenantId)
            ->where('operation', $operation)
            ->where('idempotency_key', $key->value)
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function getResponse(int $tenantId, string $operation, IdempotencyKey $key): ?array
    {
        $model = IdempotencyModel::where('tenant_id', $tenantId)
            ->where('operation', $operation)
            ->where('idempotency_key', $key->value)
            ->where('expires_at', '>', now())
            ->first();

        return $model ? (array) $model->response_data : null;
    }

    public function store(
        int $tenantId,
        string $operation,
        IdempotencyKey $key,
        array $response,
        string $payloadHash,
        \DateTimeImmutable $expiresAt,
    ): void {
        IdempotencyModel::updateOrCreate(
            [
                'tenant_id'       => $tenantId,
                'operation'       => $operation,
                'idempotency_key' => $key->value,
            ],
            [
                'payload_hash'  => $payloadHash,
                'response_data' => $response,
                'expires_at'    => $expiresAt->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function deleteExpired(): int
    {
        return IdempotencyModel::where('expires_at', '<', now())->delete();
    }
}

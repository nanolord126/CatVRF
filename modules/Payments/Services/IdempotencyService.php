<?php

declare(strict_types=1);

namespace Modules\Payments\Services;

use App\Models\PaymentIdempotencyRecord;
use Illuminate\Database\Connection;
use Illuminate\Log\LogManager;
use Modules\Common\Services\AbstractTechnicalVerticalService;

final class IdempotencyService extends AbstractTechnicalVerticalService
{
    public function __construct(
        private readonly Connection $db,
        private readonly LogManager $log,
    ) {}

    public function isEnabled(): bool
    {
        return true;
    }

    public function check(string $operation, string $idempotencyKey, string $payloadHash, int $ttlSeconds): array
    {
        $correlationId = $this->getCorrelationId();
        $tenantId = $this->resolveTenantId();
        
        $existing = PaymentIdempotencyRecord::where('operation', $operation)
            ->where('idempotency_key', $idempotencyKey)
            ->where('tenant_id', $tenantId)
            ->active()
            ->first();
        
        if ($existing !== null) {
            if ($existing->payload_hash !== $payloadHash) {
                $this->log->channel('audit')->warning('idempotency.hash_mismatch', [
                    'correlation_id' => $correlationId,
                    'operation' => $operation,
                    'tenant_id' => $tenantId,
                ]);
                throw new \RuntimeException('Idempotency conflict: payload mismatch');
            }
        
            return [
                'hit' => true,
                'response' => $existing->response_data ?? ['status' => 'pending'],
            ];
        }
        
        $this->db->transaction(function () use ($operation, $idempotencyKey, $payloadHash, $ttlSeconds, $correlationId, $tenantId): void {
            PaymentIdempotencyRecord::create([
                'tenant_id' => $tenantId,
                'operation' => $operation,
                'idempotency_key' => $idempotencyKey,
                'payload_hash' => $payloadHash,
                'status' => PaymentIdempotencyRecord::STATUS_PENDING,
                'expires_at' => now()->addSeconds($ttlSeconds),
                'correlation_id' => $correlationId,
            ]);
        });
        
        return [
            'hit' => false,
            'response' => ['status' => 'pending'],
        ];
    }

    public function record(string $operation, string $idempotencyKey, array $response): void
    {
        $tenantId = $this->resolveTenantId();
        
        $this->db->transaction(static function () use ($operation, $idempotencyKey, $response, $tenantId): void {
            PaymentIdempotencyRecord::where('operation', $operation)
                ->where('idempotency_key', $idempotencyKey)
                ->where('tenant_id', $tenantId)
                ->update([
                    'response_data' => $response,
                    'status' => PaymentIdempotencyRecord::STATUS_COMPLETED,
                ]);
        });
    }

    public static function hashPayload(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}

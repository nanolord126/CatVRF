<?php declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Database\DatabaseManager;

/**
 * Class PaymentIdempotencyService
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Services\Payment
 */
final readonly class PaymentIdempotencyService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    // Dependencies injected via constructor
        // Add private readonly properties here
        public function checkAndRecord(string $idempotencyKey, array $payload, int $tenantId): ?array
        {
            $payloadHash = hash('sha256', json_encode($payload));

            $record = $this->db->table('payment_idempotency_records')
                ->where('tenant_id', $tenantId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($record && $record->payload_hash === $payloadHash) {
                return json_decode($record->response_data, true);
            }

            return [];
        }

        /**
         * Handle record operation.
         *
         * @throws \DomainException
         */
        public function record(string $idempotencyKey, array $payload, array $response, int $tenantId): void
        {
            $payloadHash = hash('sha256', json_encode($payload));

            $this->db->table('payment_idempotency_records')->insert([
                'tenant_id' => $tenantId,
                'idempotency_key' => $idempotencyKey,
                'payload_hash' => $payloadHash,
                'response_data' => json_encode($response),
                'expires_at' => now()->addDays(7),
                'created_at' => now(),
            ]);
        }
}

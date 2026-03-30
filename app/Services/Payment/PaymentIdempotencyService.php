<?php declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentIdempotencyService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        public function checkAndRecord(string $idempotencyKey, array $payload, int $tenantId): ?array
        {
            $payloadHash = hash('sha256', json_encode($payload));

            $record = DB::table('payment_idempotency_records')
                ->where('tenant_id', $tenantId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($record && $record->payload_hash === $payloadHash) {
                return json_decode($record->response_data, true);
            }

            return [];
        }

        public function record(string $idempotencyKey, array $payload, array $response, int $tenantId): void
        {
            $payloadHash = hash('sha256', json_encode($payload));

            DB::table('payment_idempotency_records')->insert([
                'tenant_id' => $tenantId,
                'idempotency_key' => $idempotencyKey,
                'payload_hash' => $payloadHash,
                'response_data' => json_encode($response),
                'expires_at' => now()->addDays(7),
                'created_at' => now(),
            ]);
        }
}

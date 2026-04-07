<?php declare(strict_types=1);

namespace App\Services\Security;



use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class IdempotencyService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}


    /**
         * Проверить идемпотентность операции.
         *
         * Возвращает cached response, если найдена идентичная операция,
         * бросает исключение при конфликте payload_hash,
         * возвращает null если операция новая.
         *
         * @param string $operation Тип операции (payment_init, refund, payout)
         * @param string $idempotencyKey Уникальный ключ от клиента
         * @param array $payload Полный payload запроса
         * @param int $tenantId Tenant для scoping
         * @return array|null Cached response или null для новой операции
         * @throws DuplicatePaymentException Если payload_hash не совпадает
         */
        public function check(
            string $operation,
            string $idempotencyKey,
            array $payload,
            int $tenantId
        ): array {
            $payloadHash = $this->generateHash($payload);

            $record = $this->db->table('payment_idempotency_records')
                ->where('operation', $operation)
                ->where('idempotency_key', $idempotencyKey)
                ->where('tenant_id', $tenantId)
                ->where('expires_at', '>', now())
                ->first();

            if (!$record) {
                // Новая операция — возвращаем пустой массив как сигнал
                // (это OK для новых операций, не исключение)
                return [];
            }

            // Проверить, совпадает ли payload
            if ($record->payload_hash !== $payloadHash) {
                $this->logger->channel('fraud_alert')->critical('Idempotency payload mismatch detected', [
                    'operation' => $operation,
                    'idempotency_key' => $idempotencyKey,
                    'tenant_id' => $tenantId,
                    'expected_hash' => $record->payload_hash,
                    'actual_hash' => $payloadHash,
                    'correlation_id' => $payload['correlation_id'] ?? null,
                ]);

                throw new InvalidPayloadException(
                    'Payload for this idempotency key does not match previous request. Possible replay attack.'
                );
            }

            // Payload совпадает - вернуть cached response
            $this->logger->channel('audit')->info('Idempotency cache hit', [
                'operation' => $operation,
                'idempotency_key' => $idempotencyKey,
                'tenant_id' => $tenantId,
                'correlation_id' => $payload['correlation_id'] ?? null,
            ]);

            return json_decode($record->response_data, true, 512, JSON_THROW_ON_ERROR);
        }

        /**
         * Записать результат операции для идемпотентности.
         *
         * @param string $operation Тип операции
         * @param string $idempotencyKey Уникальный ключ от клиента
         * @param array $payload Полный payload запроса
         * @param array $response Результат операции
         * @param int $tenantId Tenant для scoping
         * @param int $ttlMinutes TTL записи в минутах (по умолчанию 7 дней)
         * @return bool
         */
        public function record(
            string $operation,
            string $idempotencyKey,
            array $payload,
            array $response,
            int $tenantId,
            int $ttlMinutes = 10080  // 7 дней
        ): bool {
            $payloadHash = $this->generateHash($payload);

            try {
                $this->db->transaction(function () use ($operation, $idempotencyKey, $tenantId, $payloadHash, $response, $ttlMinutes) {
                    $this->db->table('payment_idempotency_records')
                        ->insertOrIgnore([
                            'operation' => $operation,
                            'idempotency_key' => $idempotencyKey,
                            'tenant_id' => $tenantId,
                            'payload_hash' => $payloadHash,
                            'response_data' => json_encode($response, JSON_THROW_ON_ERROR),
                            'expires_at' => now()->addMinutes($ttlMinutes),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                });

                $this->logger->channel('audit')->info('Idempotency record created', [
                    'operation' => $operation,
                    'idempotency_key' => $idempotencyKey,
                    'tenant_id' => $tenantId,
                    'correlation_id' => $payload['correlation_id'] ?? null,
                ]);

                return true;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Failed to record idempotency', [
                    'operation' => $operation,
                    'idempotency_key' => $idempotencyKey,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $payload['correlation_id'] ?? null,
                ]);

                return false;
            }
        }

        /**
         * Очистить истекшие записи (для Job).
         *
         * @return int Количество удалённых записей
         */
        public function cleanup(): int
        {
            return $this->db->transaction(function () {
                $count = $this->db->table('payment_idempotency_records')
                    ->where('expires_at', '<', now())
                    ->delete();

                $this->logger->channel('audit')->info('Idempotency cleanup completed', [
                    'deleted_records' => $count,
                ]);

                return $count;
            });
        }

        /**
         * Генерировать SHA-256 hash payload.
         *
         * Сортирует ключи для консистентности,
         * исключает служебные поля (correlation_id, timestamp).
         *
         * @param array $payload
         * @return string SHA-256 hash
         */
        private function generateHash(array $payload): string
        {
            // Исключить служебные поля, которые могут отличаться
            $filtered = collect($payload)
                ->except(['correlation_id', 'timestamp', 'X-Correlation-ID', 'X-Request-ID'])
                ->sortKeys()
                ->all();

            $jsonPayload = json_encode($filtered, JSON_THROW_ON_ERROR | JSON_SORT_KEYS);
            return hash('sha256', $jsonPayload);
        }

        /**
         * Получить информацию о записи (для отладки).
         *
         * @param string $idempotencyKey
         * @param int $tenantId
         * @return array|null
         */
        public function getRecord(string $idempotencyKey, int $tenantId): array
        {
            $record = $this->db->table('payment_idempotency_records')
                ->where('idempotency_key', $idempotencyKey)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$record) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "Idempotency record not found for key: {$idempotencyKey}"
                );
            }

            return [
                'operation' => $record->operation,
                'payload_hash' => $record->payload_hash,
                'response_data' => json_decode($record->response_data, true),
                'created_at' => $record->created_at,
                'expires_at' => $record->expires_at,
                'is_expired' => Carbon::parse($record->expires_at)->isPast(),
            ];
        }
}

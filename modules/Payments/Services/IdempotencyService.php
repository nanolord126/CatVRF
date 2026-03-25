<?php
declare(strict_types=1);

namespace Modules\Payments\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\PaymentIdempotencyRecord;
use Exception;

final readonly class IdempotencyService
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * Проверяет идемпотентность операции по ключу и хешу payload.
     *
     * @param string $operationId Уникальный идентификатор операции
     * @param string $payloadHash Хеш тела запроса (SHA256)
     * @param string $merchantId Идентификатор бизнеса (tenant)
     * @param int $expiresInSeconds Время жизни записи в секундах
     * @param string $correlationId Идентификатор корреляции
     * @return array{isIdempotent: bool, response: array|null, hashedPayload: string}
     * @throws Exception
     */
    public function checkIdempotency(
        string $operationId,
        string $payloadHash,
        string $merchantId,
        int $expiresInSeconds = 86400,
        string $correlationId = '',
    ): array {
        try {
            $this->log->channel('audit')->info('Проверка идемпотентности', [
                'operation_id' => $operationId,
                'merchant_id' => $merchantId,
                'correlation_id' => $correlationId,
            ]);

            // Поиск существующей записи
            $existing = PaymentIdempotencyRecord::where('operation_id', $operationId)
                ->where('merchant_id', $merchantId)
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now())
                ->first();

            if ($existing) {
                // Проверка соответствия хеша
                if ($existing->payload_hash !== $payloadHash) {
                    $this->log->channel('audit')->warning('Идемпотентный конфликт: разные payload', [
                        'operation_id' => $operationId,
                        'stored_hash' => $existing->payload_hash,
                        'current_hash' => $payloadHash,
                        'correlation_id' => $correlationId,
                    ]);

                    throw new Exception('Идемпотентный конфликт: попытка отправить разный payload с одним operation_id');
                }

                // Возвращаем сохранённый ответ
                return [
                    'isIdempotent' => true,
                    'response' => $existing->response_data ?? [],
                    'hashedPayload' => $payloadHash,
                ];
            }

            // Создаём новую запись идемпотентности
            $this->db->transaction(function () use ($operationId, $merchantId, $payloadHash, $expiresInSeconds, $correlationId) {
                PaymentIdempotencyRecord::create([
                    'operation_id' => $operationId,
                    'merchant_id' => $merchantId,
                    'payload_hash' => $payloadHash,
                    'response_data' => null,
                    'correlation_id' => $correlationId,
                    'expires_at' => now()->addSeconds($expiresInSeconds),
                ]);
            });

            return [
                'isIdempotent' => false,
                'response' => null,
                'hashedPayload' => $payloadHash,
            ];
        } catch (Exception $e) {
            $this->log->channel('audit')->error('Ошибка при проверке идемпотентности', [
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Сохраняет результат операции для идемпотентности.
     *
     * @param string $operationId Уникальный идентификатор операции
     * @param string $merchantId Идентификатор бизнеса
     * @param array $responseData Данные ответа (будут сохранены как JSON)
     * @param string $correlationId Идентификатор корреляции
     * @return bool
     * @throws Exception
     */
    public function recordResponse(
        string $operationId,
        string $merchantId,
        array $responseData,
        string $correlationId = '',
    ): bool {
        try {
            $this->db->transaction(function () use ($operationId, $merchantId, $responseData) {
                PaymentIdempotencyRecord::where('operation_id', $operationId)
                    ->where('merchant_id', $merchantId)
                    ->update([
                        'response_data' => $responseData,
                        'recorded_at' => now(),
                    ]);
            });

            $this->log->channel('audit')->info('Результат операции сохранён', [
                'operation_id' => $operationId,
                'merchant_id' => $merchantId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (Exception $e) {
            $this->log->channel('audit')->error('Ошибка при сохранении результата операции', [
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Удаляет истёкшие записи идемпотентности.
     *
     * @return int Количество удалённых записей
     */
    public function cleanupExpiredRecords(): int
    {
        return PaymentIdempotencyRecord::where('expires_at', '<', now())->delete();
    }

    /**
     * Генерирует хеш payload для идемпотентности.
     *
     * @param array $payload Тело запроса
     * @return string SHA256 хеш
     */
    public static function hashPayload(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Shared\CRM\Jobs;

use App\Domains\Shared\CRM\Services\InternalCRMAdapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CRMSyncJob - Job для асинхронной синхронизации с CRM.
 *
 * Выполняет отправку данных в CRM с retry логикой:
 * - 5 попыток максимум
 * - Exponential backoff (8 * 2^(attempt-1) секунд)
 * - При исчерпании попыток - запись в failed_crm_syncs
 * - Таймаут 25 секунд
 */
final class CRMSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;                    // Максимум попыток
    public int $timeout = 25;                 // Таймаут в секундах
    public int $backoff = 8;                  // Базовая задержка в секундах
    public string $queue = 'crm-sync';

    public function __construct(
        public readonly string $endpoint,
        public readonly array $payload,
        public readonly string $eventName
    ) {
        $this->onQueue('crm-sync');
    }

    public function handle(InternalCRMAdapter $adapter): void
    {
        $attempt = $this->attempts();
        $correlationId = $this->payload['correlation_id'] ?? 'unknown';

        Log::info('CRMSyncJob started', [
            'correlation_id' => $correlationId,
            'event' => $this->eventName,
            'endpoint' => $this->endpoint,
            'attempt' => $attempt,
        ]);

        try {
            $success = $adapter->send($this->endpoint, $this->payload);

            if ($success) {
                Log::info('CRM Sync Success', [
                    'correlation_id' => $correlationId,
                    'event' => $this->eventName,
                    'endpoint' => $this->endpoint,
                    'order_id' => $this->payload['order_id'] ?? null,
                    'attempt' => $attempt,
                ]);
                return;
            }

            // Если adapter вернул false — считаем ошибкой
            throw new \Exception("CRM returned non-success status");

        } catch (Throwable $e) {
            Log::warning('CRM Sync Failed', [
                'correlation_id' => $correlationId,
                'event' => $this->eventName,
                'endpoint' => $this->endpoint,
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);

            if ($attempt >= $this->tries) {
                // Последняя попытка провалилась
                $this->fail($e);
                $this->sendToDeadLetter($e);
                return;
            }

            // Exponential backoff: 8, 16, 32, 64 секунды
            $delay = $this->backoff * (int) pow(2, $attempt - 1);
            Log::info('CRM Sync retry scheduled', [
                'correlation_id' => $correlationId,
                'attempt' => $attempt,
                'next_attempt' => $attempt + 1,
                'delay_seconds' => $delay,
            ]);

            $this->release($delay);
        }
    }

    /**
     * Что делать при исчерпании попыток.
     */
    public function failed(Throwable $exception): void
    {
        $correlationId = $this->payload['correlation_id'] ?? 'unknown';

        Log::error('CRMSyncJob FAILED after all retries', [
            'correlation_id' => $correlationId,
            'event' => $this->eventName,
            'endpoint' => $this->endpoint,
            'payload' => $this->maskSensitiveData($this->payload),
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Можно отправить алерт в Telegram / Sentry / Email
        // app(AlertService::class)->sendCritical("CRM Sync permanently failed", $this->payload);
    }

    /**
     * Сохранить проваленную синхронизацию в dead letter таблицу.
     */
    private function sendToDeadLetter(Throwable $e): void
    {
        try {
            DB::table('failed_crm_syncs')->insert([
                'event_name' => $this->eventName,
                'endpoint' => $this->endpoint,
                'payload' => json_encode($this->payload, JSON_UNESCAPED_UNICODE),
                'error' => $e->getMessage(),
                'attempts' => $this->attempts(),
                'failed_at' => now(),
                'correlation_id' => $this->payload['correlation_id'] ?? null,
                'order_id' => $this->payload['order_id'] ?? null,
            ]);

            Log::info('Failed CRM sync saved to dead letter', [
                'correlation_id' => $this->payload['correlation_id'] ?? null,
                'event' => $this->eventName,
            ]);
        } catch (\Exception $deadLetterError) {
            Log::error('Failed to save to dead letter table', [
                'error' => $deadLetterError->getMessage(),
                'original_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Маскировать чувствительные данные.
     */
    private function maskSensitiveData(array $data): array
    {
        $masked = $data;
        
        $sensitiveKeys = ['phone', 'email', 'card_number', 'passport', 'inn', 'kpp'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($masked[$key])) {
                $value = (string) $masked[$key];
                $masked[$key] = substr($value, 0, 2) . '***' . substr($value, -2);
            }
        }

        if (isset($masked['buyer_phone'])) {
            $value = (string) $masked['buyer_phone'];
            $masked['buyer_phone'] = substr($value, 0, 2) . '***' . substr($value, -2);
        }

        if (isset($masked['buyer_email'])) {
            $value = (string) $masked['buyer_email'];
            $parts = explode('@', $value);
            $masked['buyer_email'] = substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '***');
        }

        return $masked;
    }
}

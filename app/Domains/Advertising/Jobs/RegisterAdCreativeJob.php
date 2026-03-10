<?php

namespace App\Domains\Advertising\Jobs;

use App\Domains\Advertising\Models\AdBanner;
use App\Domains\Advertising\Compliance\EridOrchestrator;
use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Job для асинхронной регистрации рекламных материалов в ОРД (Production 2026).
 * 
 * Выполняет:
 * - Регистрацию креатива в системе ОРД
 * - Получение ERID (уникальный идентификатор рекламы по 347-ФЗ)
 * - Полное логирование и audit trail
 * - Автоматические повторные попытки при временных ошибках
 */
class RegisterAdCreativeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 60; // Экспоненциальная задержка между попытками
    public int $timeout = 300; // 5 минут на выполнение

    private string $correlationId;

    public function __construct(private AdBanner $banner)
    {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('advertising');
    }

    /**
     * Основная логика выполнения job.
     */
    public function handle(EridOrchestrator $orchestrator): void
    {
        try {
            Log::info('Ad creative registration job started', [
                'banner_id' => $this->banner->id,
                'campaign_id' => $this->banner->campaign_id,
                'correlation_id' => $this->correlationId,
                'attempt' => $this->attempts(),
            ]);

            // Вызываем оркестратор регистрации в ОРД
            $erid = $orchestrator->registerCreative($this->banner);

            // Логируем успех
            AuditLog::create([
                'action' => 'advertising.creative_registration_job_completed',
                'description' => "Job успешно завершена, ERID получен",
                'model_type' => 'AdBanner',
                'model_id' => $this->banner->id,
                'correlation_id' => $this->correlationId,
                'metadata' => [
                    'erid' => $erid,
                    'attempts' => $this->attempts(),
                ],
            ]);

            Log::info('Ad creative registration job completed', [
                'banner_id' => $this->banner->id,
                'erid' => $erid,
                'correlation_id' => $this->correlationId,
            ]);

        } catch (Throwable $e) {
            Log::warning('Ad creative registration job failed (will retry)', [
                'banner_id' => $this->banner->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'correlation_id' => $this->correlationId,
            ]);

            AuditLog::create([
                'action' => 'advertising.creative_registration_job_failed',
                'description' => "Job ошибка: {$e->getMessage()}",
                'model_type' => 'AdBanner',
                'model_id' => $this->banner->id,
                'correlation_id' => $this->correlationId,
                'metadata' => [
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                    'will_retry' => $this->attempts() < $this->tries,
                ],
            ]);

            // Если это последняя попытка, отмечаем как failed
            if ($this->attempts() >= $this->tries) {
                $this->banner->update([
                    'compliance_status' => 'registration_failed_permanently',
                ]);

                Log::error('Ad creative registration job failed permanently', [
                    'banner_id' => $this->banner->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                AuditLog::create([
                    'action' => 'advertising.creative_registration_permanently_failed',
                    'description' => "Job исчерпаны все попытки",
                    'model_type' => 'AdBanner',
                    'model_id' => $this->banner->id,
                    'correlation_id' => $this->correlationId,
                ]);
            } else {
                // Переводим баннер в статус "в процессе регистрации"
                $this->banner->update([
                    'compliance_status' => 'registering',
                ]);

                // Пробрасываем исключение для повтора
                throw $e;
            }
        }
    }

    /**
     * Обработка, когда job достигнет максимума попыток.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Ad creative registration job failed permanently after max retries', [
            'banner_id' => $this->banner->id,
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);

        $this->banner->update([
            'compliance_status' => 'registration_failed',
        ]);

        AuditLog::create([
            'action' => 'advertising.creative_registration_job_failed_permanently',
            'description' => "Job полностью провалена после всех попыток",
            'model_type' => 'AdBanner',
            'model_id' => $this->banner->id,
            'correlation_id' => $this->correlationId,
            'metadata' => [
                'error' => $exception->getMessage(),
                'total_attempts' => $this->tries,
            ],
        ]);
    }

    /**
     * Определить delay перед следующей попыткой (экспоненциальная задержка).
     */
    public function backoff(): int
    {
        return pow(2, $this->attempts() - 1) * 60; // 1 мин, 2 мин, 4 мин, 8 мин, 16 мин
    }
}

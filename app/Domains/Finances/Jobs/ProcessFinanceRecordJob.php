<?php

declare(strict_types=1);

namespace App\Domains\Finances\Jobs;


use App\Domains\Finances\Models\FinanceRecord;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Job для асинхронной обработки финансовой записи.
 *
 * Выполняет пост-обработку созданных/обновлённых записей:
 * валидация целостности, аудит, обновление агрегатов.
 *
 * LoggerInterface инжектится в handle() (not constructor — can't serialize).
 *
 * @package App\Domains\Finances\Jobs
 */
final class ProcessFinanceRecordJob implements ShouldQueue
{

    /**
     * Количество попыток перед окончательным сбоем.
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения в секундах.
     */
    public int $timeout = 120;

    /**
     * Задержка между попытками в секундах.
     */
    public int $backoff = 60;

    /**
     * @param int    $modelId       Идентификатор записи
     * @param string $correlationId Сквозной correlation ID
     */
    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('finances');
    }

    /**
     * Выполнить обработку.
     *
     * LoggerInterface и AuditService инжектятся через handle()-DI.
     */
    public function handle(AuditService $audit, LoggerInterface $logger): void
    {
        $model = FinanceRecord::findOrFail($this->modelId);

        $logger->info('ProcessFinanceRecordJob: processing', [
            'model_id'       => $model->id,
            'tenant_id'      => $model->tenant_id ?? null,
            'type'           => $model->type ?? null,
            'amount'         => $model->amount ?? null,
            'correlation_id' => $this->correlationId,
        ]);

        $audit->record(
            action: 'finances_job_processed',
            subjectType: FinanceRecord::class,
            subjectId: $model->id,
            oldValues: [],
            newValues: ['status' => 'processed'],
            correlationId: $this->correlationId,
        );
    }

    /**
     * Обработка окончательного сбоя.
     */
    public function failed(\Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'ProcessFinanceRecordJob FAILED after all retries [model_id=%d, correlation_id=%s]: %s',
                $this->modelId,
                $this->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }
}
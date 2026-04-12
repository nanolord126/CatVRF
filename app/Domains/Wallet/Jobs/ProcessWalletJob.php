<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Jobs;


use App\Domains\Wallet\Models\Wallet;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Фоновая задача обработки кошелька (пересчёт, сверка, очистка expired hold'ов).
 *
 * LoggerInterface и AuditService инжектируются в handle() (не в конструктор),
 * чтобы Job оставалась сериализуемой для Queue.
 */
final class ProcessWalletJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('wallet');
    }

    /** Основная логика — зависимости инжектируются Laravel'ом автоматически. */
    public function handle(LoggerInterface $logger, AuditService $audit): void
    {
        $model = Wallet::findOrFail($this->modelId);

        $logger->info('ProcessWalletJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id,
        ]);

        $audit->record(
            action: 'wallet_job_processed',
            subjectType: Wallet::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(\Throwable $exception): void
    {
        report($exception);
    }
}

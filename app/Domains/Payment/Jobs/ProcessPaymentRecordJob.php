<?php

declare(strict_types=1);

namespace App\Domains\Payment\Jobs;


use App\Domains\Payment\Models\PaymentRecord;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Job: фоновая обработка платёжной записи.
 *
 * КРИТИЧНО: LoggerInterface и AuditService — только в handle(),
 * НЕ в конструкторе (не сериализуются через Queue).
 */
final class ProcessPaymentRecordJob implements ShouldQueue
{

    /**
     * Максимум попыток.
     */
    public int $tries = 3;

    /**
     * Задержка между попытками (секунды).
     */
    public int $backoff = 60;

    public function __construct(
        public readonly int $paymentRecordId,
        public readonly string $correlationId,
    ) {
        $this->onQueue('payments');
    }

    /**
     * Выполнение задания.
     *
     * Logger и AuditService инжектируются Laravel'ом в handle().
     */
    public function handle(LoggerInterface $logger, AuditService $audit): void
    {
        $logger->info('Processing payment record job', [
            'payment_record_id' => $this->paymentRecordId,
            'correlation_id' => $this->correlationId,
        ]);

        $record = PaymentRecord::find($this->paymentRecordId);

        if ($record === null) {
            $logger->warning('Payment record not found in job', [
                'payment_record_id' => $this->paymentRecordId,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        $audit->record(
            action: 'payment_record_processed',
            subjectType: PaymentRecord::class,
            subjectId: $record->id,
            newValues: ['status' => $record->status?->value ?? 'unknown'],
            correlationId: $this->correlationId,
        );

        $logger->info('Payment record job completed', [
            'payment_record_id' => $this->paymentRecordId,
            'status' => $record->status?->value ?? 'unknown',
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Обработка неудачных попыток.
     */
    public function failed(\Throwable $exception): void
    {
        report($exception);
    }
}

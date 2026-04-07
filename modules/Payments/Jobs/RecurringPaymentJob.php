<?php

declare(strict_types=1);

namespace Modules\Payments\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Payments\Application\UseCases\InitiatePayment\InitiatePaymentCommand;
use Modules\Payments\Application\UseCases\InitiatePayment\InitiatePaymentUseCase;

/**
 * Job: Рекуррентный (повторяющийся) платёж.
 * Layer 7 — Jobs.
 */
final class RecurringPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $tags    = ['payments', 'recurring'];

    public function __construct(
        private readonly int    $userId,
        private readonly int    $tenantId,
        private readonly int    $amountKopeks,
        private readonly string $description,
        private readonly string $successUrl,
        private readonly string $failUrl,
        private readonly array  $metadata = [],
    ) {}

    public function handle(InitiatePaymentUseCase $useCase): void
    {
        $correlationId = Str::uuid()->toString();

        Log::channel('audit')->info('recurring.payment.job.start', [
            'correlation_id' => $correlationId,
            'user_id'        => $this->userId,
            'tenant_id'      => $this->tenantId,
            'amount'         => $this->amountKopeks,
        ]);

        $useCase->execute(new InitiatePaymentCommand(
            tenantId:       $this->tenantId,
            userId:         $this->userId,
            amountKopeks:   $this->amountKopeks,
            currency:       'RUB',
            idempotencyKey: Str::uuid()->toString(),
            correlationId:  $correlationId,
            description:    $this->description,
            successUrl:     $this->successUrl,
            failUrl:        $this->failUrl,
            hold:           false,
            recurring:      true,
            metadata:       array_merge($this->metadata, ['type' => 'recurring']),
        ));
    }
}

<?php

declare(strict_types=1);

namespace Modules\Payments\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Payments\Application\UseCases\HandleWebhook\HandleWebhookCommand;
use Modules\Payments\Application\UseCases\HandleWebhook\HandleWebhookUseCase;

/**
 * Job: Асинхронная обработка webhook от шлюза.
 * Layer 7 — Jobs.
 */
final class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public int   $timeout = 30;
    public array $tags    = ['payments', 'webhook'];

    public function __construct(
        private readonly HandleWebhookCommand $command,
    ) {}

    public function handle(HandleWebhookUseCase $useCase): void
    {
        try {
            $useCase->execute($this->command);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('webhook.job.failed', [
                'correlation_id' => $this->command->correlationId,
                'gateway'        => $this->command->gatewayCode,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(10);
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Psr\Log\LoggerInterface;

use App\Domains\CRM\Services\CRMIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncWithCRMJob implements ShouldQueue
{

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly int $userId,
        private readonly string $vertical,
        private readonly string $action,
        private readonly array $data,
        private readonly string $correlationId,
        private readonly LogManager $log,
    ) {}

    public function handle(CRMIntegrationService $crm): void
    {
        try {
            $crm->syncUserActivity(
                userId: $this->userId,
                vertical: $this->vertical,
                action: $this->action,
                data: $this->data,
                correlationId: $this->correlationId
            );

            $this->log->channel('audit')->info('CRM sync completed successfully', [
                'user_id' => $this->userId,
                'vertical' => $this->vertical,
                'action' => $this->action,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('CRM sync failed', [
                'user_id' => $this->userId,
                'vertical' => $this->vertical,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('SyncWithCRMJob failed', [
            'user_id' => $this->userId,
            'vertical' => $this->vertical,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
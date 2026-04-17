<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Domains\CRM\Services\CRMIntegrationService;

final readonly class SyncWithCRMJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public readonly int $tries;
    public readonly int $timeout;

    public function __construct(
        public int $userId,
        public string $vertical,
        public string $action,
        public array $data,
        public string $correlationId,
        int $tries = 3,
        int $timeout = 60,
    ) {
        $this->tries = $tries;
        $this->timeout = $timeout;
    }

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

            Log::channel('audit')->info('CRM sync completed successfully', [
                'user_id' => $this->userId,
                'vertical' => $this->vertical,
                'action' => $this->action,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('CRM sync failed', [
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
        Log::channel('audit')->error('SyncWithCRMJob failed', [
            'user_id' => $this->userId,
            'vertical' => $this->vertical,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}

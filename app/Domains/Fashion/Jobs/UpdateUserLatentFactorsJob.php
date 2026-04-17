<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use App\Domains\Fashion\Services\FashionCollaborativeFilteringService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class UpdateUserLatentFactorsJob implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        private readonly int $userId,
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('ml-processing');
    }

    public function handle(FashionCollaborativeFilteringService $service): void
    {
        try {
            $service->updateUserLatentFactors($this->userId, $this->tenantId);
            
            Log::channel('audit')->info('User latent factors updated', [
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to update user latent factors', [
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}

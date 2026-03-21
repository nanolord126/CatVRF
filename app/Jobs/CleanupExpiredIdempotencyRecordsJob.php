<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Services\Security\IdempotencyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CleanupExpiredIdempotencyRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $timeout = 300;  // 5 минут
    
    /**
     * Выполнить job.
     *
     * @param IdempotencyService $service
     * @return void
     */
    public function handle(IdempotencyService $service): void
    {
        try {
            $deletedCount = $service->cleanup();
            
            Log::channel('audit')->info('Idempotency cleanup job completed', [
                'deleted_records' => $deletedCount,
                'job_id' => $this->job->getJobId(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Idempotency cleanup job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}

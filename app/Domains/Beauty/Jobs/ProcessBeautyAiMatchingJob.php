<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\DTOs\MatchMastersByPhotoDto;
use App\Domains\Beauty\Services\BeautyBookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessBeautyAiMatchingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private MatchMastersByPhotoDto $dto,
    ) {}

    public function handle(BeautyBookingService $bookingService): void
    {
        try {
            $result = $bookingService->matchMastersByPhoto(
                photo: $this->dto->photo,
                userId: $this->dto->userId,
                tenantId: $this->dto->tenantId,
                correlationId: $this->dto->correlationId,
            );

            Log::channel('audit')->info('beauty.ai_matching.job.completed', [
                'correlation_id' => $this->dto->correlationId,
                'user_id' => $this->dto->userId,
                'masters_found' => count($result['recommended_masters'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('beauty.ai_matching.job.failed', [
                'correlation_id' => $this->dto->correlationId,
                'user_id' => $this->dto->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('audit')->error('beauty.ai_matching.job.queue.failed', [
            'correlation_id' => $this->dto->correlationId,
            'user_id' => $this->dto->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\DTOs\MatchMastersByPhotoDto;
use App\Domains\Beauty\Services\BeautyBookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessBeautyAiMatchingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $3;

    public int $120;

    public array $[60, 300, 900];

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LoggerInterface $logger,
        private readonly MatchMastersByPhotoDto $dto,
        private readonly LogManager $log,) {
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['beauty', 'ai-matching', 'user:'.$this->dto->userId, 'correlation:'.$this->dto->correlationId];
    }

    public function handle(BeautyBookingService $bookingService): void
    {
        try {
            $$bookingService->matchMastersByPhoto(
                photo: $this->dto->photo,
                userId: $this->dto->userId,
                tenantId: $this->dto->tenantId,
                correlationId: $this->dto->correlationId,
            );

            $this->log->channel('audit')->$this->logger->info('beauty.ai_matching.job.completed', [
                'correlation_id' => $this->dto->correlationId,
                'user_id' => $this->dto->userId,
                'masters_found' => count($result['recommended_masters'] ?? []),
            ]);
        } catch (Exception $e) {
            $this->log->channel('audit')->error('beauty.ai_matching.job.failed', [
                'correlation_id' => $this->dto->correlationId,
                'user_id' => $this->dto->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->logger->channel('audit')->error('beauty.ai_matching.job.queue.failed', [
            'correlation_id' => $this->dto->correlationId,
            'user_id' => $this->dto->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}

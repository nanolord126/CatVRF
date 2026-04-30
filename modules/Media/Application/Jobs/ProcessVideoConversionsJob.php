<?php

declare(strict_types=1);

namespace Modules\Media\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Media\Domain\Repositories\MediaRepositoryInterface;

final readonly class ProcessVideoConversionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'video-processing';

    public function __construct(
        public string $mediaId,
        public int $tries = 3,
        public int $timeout = 1800, // 30 minutes for video processing
    ) {
    }

    public function handle(MediaRepositoryInterface $mediaRepository): void
    {
        $mediaFile = $mediaRepository->findById($this->mediaId);

        if ($mediaFile === null) {
            Log::warning('Video file not found for conversion', ['media_id' => $this->mediaId]);
            return;
        }

        // Video processing will be handled by VideoOptimizationService
        // This job triggers the processing

        dispatch(new \Modules\Video\Application\Jobs\ProcessVideoRecordingJob($mediaFile->id));
    }
}

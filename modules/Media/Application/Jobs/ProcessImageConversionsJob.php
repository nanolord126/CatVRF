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
use Modules\Media\Domain\ValueObjects\MediaStatus;

final readonly class ProcessImageConversionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'media-optimization';

    public function __construct(
        public string $mediaId,
        public int $tries = 3,
        public int $timeout = 300,
    ) {
    }

    public function handle(MediaRepositoryInterface $mediaRepository): void
    {
        $mediaFile = $mediaRepository->findById($this->mediaId);

        if ($mediaFile === null) {
            Log::warning('Media file not found for conversion', ['media_id' => $this->mediaId]);
            return;
        }

        // Conversions will be handled by Spatie MediaLibrary automatically
        // This job is for additional custom processing if needed

        // Mark as optimized after conversions complete
        $cdnUrl = $this->generateCdnUrl($mediaFile);
        $mediaRepository->markAsOptimized($this->mediaId, $cdnUrl);
    }

    private function generateCdnUrl(object $mediaFile): string
    {
        // Will be implemented with CDN service
        return '';
    }
}

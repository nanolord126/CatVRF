<?php

declare(strict_types=1);

namespace Modules\Video\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Modules\Video\Application\Services\VideoOptimizationService;
use Modules\Video\Domain\Repositories\VideoRoomRepositoryInterface;

/**
 * ProcessVideoRecordingJob — Job for processing video recordings
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class ProcessVideoRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'video-processing';

    public function __construct(
        public string $roomId,
        public string $recordingPath,
        public int $tries = 3,
        public int $timeout = 1800, // 30 minutes
    ) {
    }

    public function handle(
        VideoRoomRepositoryInterface $roomRepository,
        VideoOptimizationService $optimizationService,
        LogManager $log,
        FilesystemManager $storage,
    ): void {
        $room = $roomRepository->findById($this->roomId);

        if ($room === null) {
            $log->warning('Video room not found for recording processing', ['room_id' => $this->roomId]);
            return;
        }

        try {
            // Optimize video with FFmpeg
            $optimizedUrl = $optimizationService->processRecording(
                $this->recordingPath,
                $room->type,
            );

            // Update room with optimized recording URL
            $room = $room->end($optimizedUrl);
            $roomRepository->save($room);

            $log->info('Video recording processed successfully', [
                'room_id' => $this->roomId,
                'optimized_url' => $optimizedUrl,
            ]);

            // Cleanup original recording
            if ($storage->exists($this->recordingPath)) {
                $storage->delete($this->recordingPath);
            }
        } catch (\Exception $e) {
            $log->error('Failed to process video recording', [
                'room_id' => $this->roomId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

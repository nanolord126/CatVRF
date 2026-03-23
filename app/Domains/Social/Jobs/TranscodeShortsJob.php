<?php

declare(strict_types=1);

namespace App\Domains\Social\Jobs;

use App\Domains\Social\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;

/**
 * Job для транскодирования Shorts в 9:16 (720p) через FFmpeg
 */
final class TranscodeShortsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        public SocialPost $post,
        public string $correlationId
    ) {
    }

    public function handle(): void
    {
        Log::channel('audit')->info('Transcoding started', [
            'post_id' => $this->post->id,
            'correlation_id' => $this->correlationId,
        ]);

        $this->post->update(['transcoding_status' => 'processing']);

        try {
            $ffmpeg = FFMpeg::create();
            $video = $ffmpeg->open(Storage::disk('s3')->path($this->post->media_url));

            $outputPath = 'shorts/' . $this->post->uuid . '_transcoded.mp4';

            // Настройка формата (720p, 9:16)
            $format = new X264('libmp3lame', 'libx264');
            
            // Фильтры для вертикального видео (если нужно)
            $video->filters()->resize(new \FFMpeg\Coordinate\Dimension(720, 1280))->synchronize();
            
            // Сохранение (в реальности используем временный файл, потом загружаем в S3)
            $video->save($format, Storage::disk('s3')->path($outputPath));

            $this->post->update([
                'media_url' => $outputPath,
                'transcoding_status' => 'completed',
            ]);

            Log::channel('audit')->info('Transcoding completed', [
                'post_id' => $this->post->id,
                'correlation_id' => $this->correlationId,
            ]);

        } catch (\Exception $e) {
            $this->post->update(['transcoding_status' => 'failed']);
            Log::error('Transcoding error: ' . $e->getMessage(), [
                'post_id' => $this->post->id,
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}

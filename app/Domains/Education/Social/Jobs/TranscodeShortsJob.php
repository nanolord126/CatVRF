<?php declare(strict_types=1);

namespace App\Domains\Education\Social\Jobs;


use Psr\Log\LoggerInterface;
final class TranscodeShortsJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $timeout = 600;

        public function __construct(
            public SocialPost $post,
            public string $correlationId, private readonly LoggerInterface $logger
        ) {
        }

        public function handle(\Illuminate\Filesystem\FilesystemManager $storage): void
        {
            $this->logger->info('Transcoding started', [
                'post_id' => $this->post->id,
                'correlation_id' => $this->correlationId,
            ]);

            $this->post->update(['transcoding_status' => 'processing']);

            try {
                $ffmpeg = FFMpeg::create();
                $video = $ffmpeg->open($storage->disk('s3')->path($this->post->media_url));

                $outputPath = 'shorts/' . $this->post->uuid . '_transcoded.mp4';

                // Настройка формата (720p, 9:16)
                $format = new X264('libmp3lame', 'libx264');

                // Фильтры для вертикального видео (если нужно)
                $video->filters()->resize(new \FFMpeg\Coordinate\Dimension(720, 1280))->synchronize();

                // Сохранение (в реальности используем временный файл, потом загружаем в S3)
                $video->save($format, $storage->disk('s3')->path($outputPath));

                $this->post->update([
                    'media_url' => $outputPath,
                    'transcoding_status' => 'completed',
                ]);

                $this->logger->info('Transcoding completed', [
                    'post_id' => $this->post->id,
                    'correlation_id' => $this->correlationId,
                ]);

            } catch (\Throwable $e) {
                $this->post->update(['transcoding_status' => 'failed']);
                $this->logger->error('Transcoding error: ' . $e->getMessage(), [
                    'post_id' => $this->post->id,
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }
}

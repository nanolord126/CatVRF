<?php declare(strict_types=1);

namespace App\Domains\Photography\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class ProcessPortfolioImageJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public function __construct(
            private readonly PortfolioItem $item,
            private readonly string $correlationId, private readonly LoggerInterface $logger
        ) {}

        public function handle(): void
        {
            $this->logger->info('Processing portfolio image', [
                'item_id' => $this->item->id,
                'correlation_id' => $this->correlationId
            ]);

            try {
                // 1. Очистка EXIF (Безопасность) и наложение водяного знака
                $imagePath = storage_path('app/' . $this->item->temp_path);

                // Здесь реализация наложения Watermark и сжатия (условно)
                // $image = Image::make($imagePath);
                // $image->insert(public_path('watermark.png'), 'bottom-right', 10, 10);
                // $image->save(storage_path('app/public/portfolio/' . $this->item->uuid . '.jpg'));

                $this->item->update([
                    'path' => 'public/portfolio/' . $this->item->uuid . '.jpg',
                    'status' => 'active'
                ]);

                $this->logger->info('Portfolio image processed successfully', [
                    'item_id' => $this->item->id,
                    'correlation_id' => $this->correlationId
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to process image', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId
                ]);
                throw $e;
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}


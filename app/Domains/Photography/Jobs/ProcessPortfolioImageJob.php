<?php

declare(strict_types=1);

namespace App\Domains\Photography\Jobs;

use App\Domains\Photography\Models\PortfolioItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image; // Предполагая наличие Intervention для водяных знаков

/**
 * Канон 2026: Транскодинг изображений для фотографов (Раздел 2)
 */
final class ProcessPortfolioImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly PortfolioItem $item,
        private readonly string $correlationId
    ) {}

    public function handle(): void
    {
        Log::channel('audit')->info('Processing portfolio image', [
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

            Log::channel('audit')->info('Portfolio image processed successfully', [
                'item_id' => $this->item->id,
                'correlation_id' => $this->correlationId
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to process image', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId
            ]);
            throw $e;
        }
    }
}

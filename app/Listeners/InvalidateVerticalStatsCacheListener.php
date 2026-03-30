<?php declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InvalidateVerticalStatsCacheListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(object $event): void
        {
            if (!isset($event->vertical)) {
                return;
            }

            try {
                $cacheTag = "vertical_stats_{$event->vertical}";
                Cache::store('redis')->tags([$cacheTag])->flush();

                Log::channel('audit')->info('Vertical stats cache invalidated', [
                    'vertical' => $event->vertical,
                    'event' => class_basename($event),
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to invalidate vertical stats cache', [
                    'vertical' => $event->vertical ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
}

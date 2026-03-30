<?php declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InvalidateAIConstructorCacheListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(object $event): void
        {
            if (!isset($event->userId)) {
                return;
            }

            try {
                $cacheTag = "ai_constructor_{$event->userId}";
                Cache::store('redis')->tags([$cacheTag])->flush();

                Log::channel('audit')->info('AI constructor cache invalidated', [
                    'user_id' => $event->userId,
                    'vertical' => $event->vertical ?? null,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to invalidate AI constructor cache', [
                    'user_id' => $event->userId ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
}

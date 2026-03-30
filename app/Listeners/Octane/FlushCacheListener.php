<?php declare(strict_types=1);

namespace App\Listeners\Octane;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlushCacheListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(RequestHandled $event): void
        {
            // Flush non-persistent caches to avoid memory leaks
            if (config('octane.flush_views', false)) {
                view()->flushViewsCache();
            }

            // Reset stateful services
            if (config('octane.isolation', false)) {
                app('cache')->flush();
            }
        }
}

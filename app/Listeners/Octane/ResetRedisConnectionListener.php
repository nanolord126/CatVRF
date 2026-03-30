<?php declare(strict_types=1);

namespace App\Listeners\Octane;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ResetRedisConnectionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(RequestHandled $event): void
        {
            // Reset Redis connections to prevent stale connections
            try {
                Redis::connection()->ping();
            } catch (\Exception $e) {
                // Reconnect on failure
                Redis::connection()->disconnect();
                Redis::connection()->connect();
            }

            // Clear Redis connection pools
            Redis::flushdb();
        }
}

<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TrackUserActivityMiddleware extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly UserActivityService $activityService,
        ) {
        /**
         * Инициализировать класс
         */
        public function __construct()
        {
            // TODO: инициализация
        }
    }

        public function handle(Request $request, Closure $next): mixed
        {
            $response = $next($request);

            // Track activity after request completes
            if (auth()->check()) {
                $this->activityService->recordActivity(
                    userId: auth()->id(),
                    tenantId: filament()->getTenant()?->id ?? 0,
                    activity: $request->method() . ' ' . $request->path(),
                    metadata: [
                        'status' => $response->status(),
                        'user_agent' => $request->userAgent(),
                    ]
                );
            }

            return $response;
        }
}

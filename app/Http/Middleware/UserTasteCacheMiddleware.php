<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserTasteCacheMiddleware extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private const CACHE_TTL_MINUTES = 30;

        public function handle(Request $request, Closure $next)
        {
            $userId = auth()->id();

            if (!$userId) {
                return $next($request);
            }

            $cacheKey = "user_taste_profile_{$userId}";
            $cacheTag = "user_taste_{$userId}";

            $tasteProfile = Cache::tags([$cacheTag])->remember(
                $cacheKey,
                now()->addMinutes(self::CACHE_TTL_MINUTES),
                fn() => $this->buildTasteProfile($userId)
            );

            $request->attributes->set('user_taste_profile', $tasteProfile);

            return $next($request);
        }

        private function buildTasteProfile(int $userId): array
        {
            // Placeholder - реальная логика в UserTasteProfileService
            return [
                'user_id' => $userId,
                'categories' => [],
                'price_range' => 'mid',
                'preferred_brands' => [],
                'analyzed_at' => now()->toIso8601String(),
            ];
        }
}

<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2CB2BCacheMiddleware extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(Request $request, Closure $next)
        {
            $userId = auth()->id();

            if (!$userId) {
                return $next($request);
            }

            $cacheKey = "user_{$userId}_b2b_mode";
            $cacheTag = "user_b2c_b2b_{$userId}";

            $isB2B = Cache::tags([$cacheTag])->remember($cacheKey, now()->addHour(), function () use ($request) {
                return $request->has('inn') && $request->has('business_card_id');
            });

            $request->merge(['is_b2b' => $isB2B]);
            $request->attributes->set('b2c_mode', !$isB2B);

            return $next($request);
        }
}

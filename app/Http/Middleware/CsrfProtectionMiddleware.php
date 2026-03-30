<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CsrfProtectionMiddleware extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Защита от CSRF атак (для форм и Livewire).
         */
        public function handle(Request $request, Closure $next)
        {
            // Проверяем CSRF токен для изменяющихся методов
            if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
                // Пропускаем для API запросов с Bearer токенами
                if ($request->bearerToken() === null && !$request->is('api/*')) {
                    $token = $request->input('_token') ?? $request->header('X-CSRF-Token');

                    if (!$token || !hash_equals($token, session('XSRF-TOKEN'))) {
                        return response()->json([
                            'message' => 'CSRF token mismatch',
                            'correlation_id' => $request->header('X-Correlation-ID'),
                        ], 419);
                    }
                }
            }

            return $next($request);
        }
}

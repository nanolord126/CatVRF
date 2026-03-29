<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * B2CB2BMiddleware — Определение режима B2C/B2B
 *
 * Production 2026 CANON
 *
 * Определяет режим работы на основе наличия INN (ИНН) и business_card_id:
 * - B2C: физическое лицо, нет INN
 * - B2B: юридическое лицо, есть INN + business_card_id
 *
 * Устанавливает флаги в request для использования в контроллерах:
 * - $request->b2c_mode: bool
 * - $request->b2b_mode: bool
 * - $request->mode_type: 'b2c' | 'b2b'
 *
 * ✓ Middleware execution order: 4th (correlation-id → auth:sanctum → tenant → b2c-b2b → rate-limit → fraud-check → age-verify)
 *
 * @author CatVRF Team
 * @version 2026.03.28
 */
final class B2CB2BMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $correlationId = $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        try {
            // Определяем режим по наличию INN и business_card_id
            $inn = $request->input('inn') ?? $request->header('X-Inn');
            $businessCardId = $request->input('business_card_id') ?? $request->header('X-Business-Card-Id');

            // B2B если есть оба параметра
            $isB2B = !empty($inn) && !empty($businessCardId);
            $isB2C = !$isB2B;

            // Устанавливаем флаги в request
            $request->merge([
                'b2c_mode' => $isB2C,
                'b2b_mode' => $isB2B,
                'mode_type' => $isB2B ? 'b2b' : 'b2c',
            ]);

            // Логируем определение режима
            Log::channel('audit')->debug('B2C/B2B mode determined', [
                'user_id' => auth()->id(),
                'mode' => $request->get('mode_type'),
                'inn' => $inn ? '***' . substr($inn, -4) : null,
                'path' => $request->path(),
                'correlation_id' => $correlationId,
            ]);

            // Если B2B, проверяем, что юзер имеет доступ к этому бизнес-аккаунту
            if ($isB2B && auth()->check()) {
                $user = auth()->user();
                $hasBusinessAccess = $user->businesses()
                    ->where('inn', $inn)
                    ->where('business_card_id', $businessCardId)
                    ->exists();

                if (!$hasBusinessAccess) {
                    Log::channel('fraud_alert')->warning('Unauthorized B2B access attempt', [
                        'user_id' => $user->id,
                        'inn' => '***' . substr($inn, -4),
                        'path' => $request->path(),
                        'correlation_id' => $correlationId,
                    ]);

                    return response()->json([
                        'error' => 'Unauthorized B2B access',
                        'correlation_id' => $correlationId,
                    ], 403);
                }
            }

            return $next($request);

        } catch (\Throwable $e) {
            Log::channel('audit')->error('B2C/B2B middleware error', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}


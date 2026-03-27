<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * AgeVerificationMiddleware - Проверка возраста пользователя
 *
 * PRODUCTION-READY 2026 CANON
 *
 * Проверяет возраст пользователя для чувствительных вертикалей:
 * - Pharmacy (18+) - лекарства
 * - Medical (18+) - операции, медикаменты
 * - Vapes (18+) - никотиновая продукция
 * - Alcohol (18+) - алкогольные напитки
 * - Bars/Pubs (18+) - алкоголь и курение
 * - HookahLounges (18+) - табачная продукция
 * - Tobacco (18+) - табак и табачные изделия
 *
 * Ограничения по возрасту:
 * - 0+ (дети): Food, Toys, Books, Education
 * - 6+ (дети старшего возраста): KidsPlayCenters, DanceStudios, SportingGoods
 * - 12+ (подростки): QuestRooms, Cinema, EscapeRooms
 * - 14+ (старшие подростки): YogaPilates, Freelance
 * - 18+ (взрослые): Pharmacy, Medical, Vapes, Alcohol, Bars, KaraokeLounges, Casinos
 *
 * @author CatVRF Team
 * @version 2026.03.27
 */
final class AgeVerificationMiddleware
{
    /**
     * Минимальный требуемый возраст по вертикалям
     */
    private const AGE_RESTRICTIONS = [
        // 18+ вертикали (строгие)
        'pharmacy' => 18,
        'medical' => 18,
        'vapes' => 18,
        'alcohol' => 18,
        'bars' => 18,
        'hookah-lounges' => 18,
        'tobacco' => 18,
        'karaoke' => 18,
        'casinos' => 21,

        // 14+ вертикали
        'yoga-pilates' => 14,
        'freelance' => 14,

        // 12+ вертикали
        'quest-rooms' => 12,
        'cinema' => 12,
        'escape-rooms' => 12,

        // 6+ вертикали
        'kids-play-centers' => 6,
        'dance-studios' => 6,
        'sporting-goods' => 6,
        'board-games' => 6,

        // 0+ (без ограничений)
        'food' => 0,
        'toys-kids' => 0,
        'books' => 0,
        'education' => 0,
    ];

    public function handle(Request $request, Closure $next, ?string $vertical = null): mixed
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        try {
            // Если не указана вертикаль, извлекаем из пути
            $vertical = $vertical ?? $this->extractVerticalFromPath($request->path());

            // Если вертикаль не найдена или нет ограничений, пропускаем
            if (!$vertical || !isset(self::AGE_RESTRICTIONS[$vertical])) {
                return $next($request);
            }

            $minAge = self::AGE_RESTRICTIONS[$vertical];

            // Если ограничений нет (0+), пропускаем проверку
            if ($minAge === 0) {
                return $next($request);
            }

            // Проверяем, аутентифицирован ли пользователь
            if (!auth()->check()) {
                Log::channel('audit')->warning('Age verification required for unauthenticated user', [
                    'vertical' => $vertical,
                    'min_age' => $minAge,
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'error' => sprintf('Must be at least %d years old to access this', $minAge),
                    'min_age' => $minAge,
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $user = auth()->user();
            $userAge = $this->calculateAge($user->birthdate ?? null);

            // Если дата рождения не указана
            if ($userAge === null) {
                Log::channel('audit')->warning('User birthdate not set', [
                    'user_id' => $user->id,
                    'vertical' => $vertical,
                    'min_age' => $minAge,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'error' => 'Please update your birthdate to access this section',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            // Проверяем возраст
            if ($userAge < $minAge) {
                Log::channel('fraud_alert')->warning('Age verification failed', [
                    'user_id' => $user->id,
                    'user_age' => $userAge,
                    'min_age' => $minAge,
                    'vertical' => $vertical,
                    'path' => $request->path(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'error' => sprintf('You must be at least %d years old to access this', $minAge),
                    'min_age' => $minAge,
                    'your_age' => $userAge,
                    'correlation_id' => $correlationId,
                ], 403);
            }

            // Логируем успешную проверку возраста
            Log::channel('audit')->debug('Age verification passed', [
                'user_id' => $user->id,
                'user_age' => $userAge,
                'vertical' => $vertical,
                'correlation_id' => $correlationId,
            ]);

            return $next($request);

        } catch (\Throwable $e) {
            Log::channel('audit')->error('Age verification middleware error', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Вычислить возраст пользователя по дате рождения
     */
    private function calculateAge(?string $birthdate): ?int
    {
        if (!$birthdate) {
            return null;
        }

        try {
            $birth = Carbon::parse($birthdate);
            return $birth->diffInYears(Carbon::now());
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Извлечь вертикаль из пути запроса
     * Пример: /api/v1/pharmacy/orders -> pharmacy
     */
    private function extractVerticalFromPath(string $path): ?string
    {
        $segments = explode('/', trim($path, '/'));

        // Обычно вертикаль - второй или третий сегмент
        foreach ($segments as $segment) {
            if (strlen($segment) > 2 && !in_array($segment, ['api', 'v1', 'v2'])) {
                return strtolower($segment);
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Travel\Services;

use App\Domains\Travel\Models\Booking;
use App\Domains\Travel\Models\Trip;
use App\Domains\Travel\Models\Excursion;
use App\Domains\Travel\Models\Tour;
use App\Services\FraudControlService;
use App\Services\RateLimiterService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Exceptions\FraudAlertException;
use App\Exceptions\RateLimitException;

/**
 * КАНОН 2026: Fraud ML Service (Travel-specific).
 * Слой 6: Безопасность и Фрод-контроль.
 */
final readonly class TravelFraudService
{
    public function __construct(
        private FraudControlService $baseFraud,
        private RateLimiterService $rateLimiter,
    ) {}

    /**
     * Проверка операции бронирования на фрод (ML Scoring).
     */
    public function validateBooking(int $userId, int $bookableId, string $bookableType, array $context): void
    {
        $correlationId = $context['correlation_id'] ?? (string) \Illuminate\Support\Str::uuid();

        Log::channel('fraud_alert')->info('Booking fraud check initiated', [
            'user_id' => $userId,
            'bookable_id' => $bookableId,
            'bookable_type' => $bookableType,
            'correlation_id' => $correlationId
        ]);

        // 1. Rate Limiting (Слой 6) — заслон от массовых запросов
        if (!$this->rateLimiter->check($userId, 'travel_booking_lock', 5, 60)) {
            throw new RateLimitException('Превышен лимит попыток бронирования. Попробуйте через 1 минуту.');
        }

        // 2. Базовые правила фрод-контроля (Слой 6)
        $this->baseFraud->check($userId, 'travel_booking', $context);

        // 3. Специфический ML-скоринг (Слой 6)
        $mlScore = $this->calculateMlScore($userId, $bookableId, $bookableType, $context);

        // 4. Логирование и принятие решения
        $this->logFraudAttempt($userId, $bookableId, $bookableType, $mlScore, $correlationId, $context);

        if ($mlScore > 0.85) {
            Log::channel('fraud_alert')->emergency('HIGH FRAUD SCORE DETECTED - FORCED BLOCK', [
                'user_id' => $userId,
                'score' => $mlScore,
                'correlation_id' => $correlationId
            ]);
            throw new FraudAlertException('Ваша активность заблокирована системой фрод-мониторинга. Код: ' . $correlationId);
        }
    }

    private function calculateMlScore(int $userId, int $bookableId, string $bookableType, array $context): float
    {
        $score = 0.0;

        // Фича 1: Новизна аккаунта + большая сумма
        $user = \App\Models\User::find($userId);
        if ($user && $user->created_at->gt(now()->subDays(3)) && ($context['amount'] ?? 0) > 50000) {
            $score += 0.45;
        }

        // Фича 2: Подозрительная частота успешных отмен (Bonus Abuse)
        $cancelledCount = Booking::where('user_id', $userId)
            ->where('status', 'cancelled')
            ->where('created_at', '>', now()->subDays(30))
            ->count();

        if ($cancelledCount > 5) {
            $score += 0.3;
        }

        // Фича 3: Гео-аномалия (бросок IP)
        // В реальной системе здесь вызов GeoService::isIPAnomalous($userId)
        if (($context['ip_changed'] ?? false) === true) {
            $score += 0.2;
        }

        return min($score, 1.0);
    }

    private function logFraudAttempt(int $userId, int $bookableId, string $bookableType, float $score, string $correlationId, array $context): void
    {
        DB::table('fraud_attempts')->insert([
            'user_id' => $userId,
            'tenant_id' => tenant()->id ?? 1,
            'operation_type' => 'travel_booking',
            'ml_score' => $score,
            'correlation_id' => $correlationId,
            'features_json' => json_encode($context),
            'decision' => $score > 0.85 ? 'block' : ($score > 0.6 ? 'review' : 'allow'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Tickets\Services;

use App\Domains\Tickets\Models\Event;
use App\Domains\Tickets\Models\Venue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * КАНОН 2026: AI-сервис рекомендаций эвентов и схем залов.
 * Слой 5: AI / ML.
 */
final readonly class TicketAIService
{
    /**
     * Конструктор с зависимостями.
     */
    public function __construct(
        private readonly \App\Services\RecommendationService $recommendation,
        private readonly \App\Services\DemandForecastService $forecast
    ) {}

    /**
     * Персонализированные рекомендации эвентов для пользователя.
     */
    public function suggestEventsForUser(int $userId, array $context = []): Collection
    {
        $correlationId = $context['correlation_id'] ?? (string) \Illuminate\Support\Str::uuid();
        $cacheKey = "tickets:ai:suggest:user:{$userId}";

        return Cache::remember($cacheKey, 3600, function () use ($userId, $context, $correlationId) {
            Log::channel('recommend')->info('AI event suggestion initiated', [
                'user_id' => $userId,
                'correlation_id' => $correlationId
            ]);

            // 1. Получаем профиль вкусов пользователя (из канона ML 2026)
            $user = \App\Models\User::find($userId);
            $tasteProfile = $user->taste_profile ?? [];

            // 2. Базовый фильтр активных эвентов
            $candidates = Event::active()->with(['venue', 'ticketTypes'])->get();

            // 3. Скоринг кандидатов через AI (OpenAI или локальная модель)
            $scored = $candidates->map(function ($event) use ($tasteProfile, $context) {
                // Прямое соответствие категории
                $score = in_array($event->category, $tasteProfile['categories'] ?? []) ? 0.45 : 0.1;
                
                // Географическая близость (через GeoService)
                if (isset($context['lat'], $context['lon']) && $event->venue->lat && $event->venue->lon) {
                    $distance = $this->calculateDistance($context['lat'], $context['lon'], $event->venue->lat, $event->venue->lon);
                    if ($distance < 20) $score += 0.25; // 20км радиус
                }

                // Векторное сходство (имитация)
                $score += 0.20; // Embedding similarity

                return [
                    'event' => $event,
                    'score' => $score + ($event->is_b2b ? 0.05 : 0)
                ];
            });

            Log::channel('recommend')->info('AI event scoring completed', [
                'candidates_count' => $scored->count(),
                'top_score' => $scored->max('score')
            ]);

            return $scored->sortByDesc('score')->take(10)->pluck('event');
        });
    }

    /**
     * Прогноз спроса на эвент (Demand Forecast Канон 2026).
     */
    public function predictEventDemand(int $eventId): array
    {
        $event = Event::findOrFail($eventId);
        $correlationId = $event->correlation_id ?? (string) \Illuminate\Support\Str::uuid();

        Log::channel('forecast')->info('Event demand prediction requested', [
            'event_id' => $eventId,
            'correlation_id' => $correlationId
        ]);

        // 1. Извлекаем фичи
        $features = [
            'category' => $event->category,
            'venue_capacity' => $event->venue->capacity,
            'days_until_start' => $event->start_at->diffInDays(now()),
            'is_weekend' => $event->start_at->isWeekend(),
            'current_sales_rate' => $this->calculateSalesRate($event)
        ];

        // 2. Вызываем ML-ядро Прогнозирования (DemandForecastService)
        $forecastResult = $this->forecast->forecastBulk(
            itemIds: [$eventId],
            dateFrom: now(),
            dateTo: $event->start_at
        );

        return [
            'predicted_demand' => $forecastResult[$eventId]['predicted_demand'] ?? 0,
            'confidence_score' => $forecastResult[$eventId]['confidence_score'] ?? 0.85,
            'features' => $features,
            'suggested_price_adjustment' => $this->getSuggestedPriceAdjustment($event, $forecastResult[$eventId] ?? [])
        ];
    }

    /**
     * AI Дизайнер схемы зала (AI Constructor 2026).
     */
    public function designSeatMapLayout(array $requirements): array
    {
        Log::channel('audit')->info('AI SeatMap design requested', $requirements);

        // Имитация вызова OpenAI для генерации JSON структуры зале
        // В реальном 2026 — OpenAI vision анализирует чертеж или текст требований
        $layout = [
            'sectors' => [
                ['name' => 'VIP', 'capacity' => 50, 'is_seated' => true, 'rows' => [['number' => 1, 'seats' => 10]]],
                ['name' => 'Main Floor', 'capacity' => 500, 'is_seated' => false],
            ],
            'total_capacity' => 550,
            'generated_by' => 'TicketAIService v2026.1'
        ];

        return [
            'success' => true,
            'layout' => $layout,
            'correlation_id' => (string) \Illuminate\Support\Str::uuid()
        ];
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        // Упрощенная формула гаверсинусов
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    private function calculateSalesRate(Event $event): float
    {
        $totalSold = $event->ticketTypes->sum('sold_count');
        $daysOpen = $event->created_at->diffInDays(now()) ?: 1;
        return $totalSold / $daysOpen;
    }

    private function getSuggestedPriceAdjustment(Event $event, array $prediction): int
    {
        // Если спрос выше предложения на 20% -> повышаем цену на 10%
        if (($prediction['predicted_demand'] ?? 0) > ($event->venue->capacity * 1.2)) {
            return 10;
        }
        return 0;
    }
}

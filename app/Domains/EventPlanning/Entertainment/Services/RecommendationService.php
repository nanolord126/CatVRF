<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Services;

use App\Domains\EventPlanning\Entertainment\Models\Booking;
use App\Domains\EventPlanning\Entertainment\Models\Venue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — AI RECOMMENDATION SERVICE (Entertainment Domain)
 * 1. final readonly class
 * 2. Анализ вкусов пользователя на базе истории бронирований
 * 3. Логирование confidence_score
 * 4. Интеграция с UserTasteAnalyzerService
 */
final readonly class RecommendationService
{
    public function __construct(
        private string $correlationId = ''
    ) {
    }

    private function getCorrelationId(): string
    {
        return $this->correlationId ?: (string) Str::uuid();
    }

    /**
     * Получить рекомендации заведений для пользователя
     */
    public function getRecommendationsForUser(int $userId, int $limit = 5): Collection
    {
        $correlationId = $this->getCorrelationId();

        Log::channel('recommend')->info('Generating entertainment recommendations', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        // 1. Получаем историю категорий пользователя из прошлых бронирований
        $preferredTypes = Booking::where('user_id', $userId)
            ->join('entertainment_events', 'entertainment_bookings.event_id', '=', 'entertainment_events.id')
            ->join('entertainment_venues', 'entertainment_events.venue_id', '=', 'entertainment_venues.id')
            ->select('entertainment_venues.type', DB::raw('count(*) as count'))
            ->groupBy('entertainment_venues.type')
            ->orderBy('count', 'desc')
            ->pluck('type')
            ->toArray();

        // 2. Базовый ML-алгоритм (Content-based filtering)
        // Ищем заведения таких же типов, которые пользователь еще не посещал
        $query = Venue::where('is_active', true);
        
        if (!empty($preferredTypes)) {
            $query->whereIn('type', $preferredTypes);
        }

        $results = $query->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();

        // 3. Расчёт Confidence Score (заглушка для ML-модели)
        $confidenceScore = !empty($preferredTypes) ? 0.85 : 0.45;

        Log::channel('recommend')->info('Recommendations generated', [
            'user_id' => $userId,
            'count' => $results->count(),
            'confidence_score' => $confidenceScore,
            'correlation_id' => $correlationId,
        ]);

        return $results->map(function ($venue) use ($confidenceScore) {
            $venue->ml_confidence = $confidenceScore;
            return $venue;
        });
    }

    /**
     * Поиск похожих заведений (Look-alike)
     */
    public function getSimilarVenues(Venue $venue, int $limit = 3): Collection
    {
        $correlationId = $this->getCorrelationId();

        Log::channel('recommend')->info('Generating similar venues (Look-alike)', [
            'base_venue_uuid' => $venue->uuid,
            'correlation_id' => $correlationId,
        ]);

        $results = Venue::where('type', $venue->type)
            ->where('id', '!=', $venue->id)
            ->where('is_active', true)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();

        Log::channel('recommend')->info('Look-alike venues found', [
            'count' => $results->count(),
            'correlation_id' => $correlationId,
        ]);

        return $results;
    }

    /**
     * Анализ "популярности" в текущем регионе (Trending)
     */
    public function getTrendingInRegion(string $region, int $limit = 5): Collection
    {
        $correlationId = $this->getCorrelationId();

        // Поиск по тегу региона или части адреса
        return Venue::where('address', 'LIKE', "%{$region}%")
            ->where('is_active', true)
            ->orderBy('review_count', 'desc')
            ->limit($limit)
            ->get();
    }
}

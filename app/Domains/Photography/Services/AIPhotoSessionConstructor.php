<?php declare(strict_types=1);

namespace App\Domains\Photography\Services;


use Psr\Log\LoggerInterface;
final readonly class AIPhotoSessionConstructor
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    /**
         * Конструирование идеальной сессии
         */
        public function constructSession(array $preferences, ?string $correlationId = null): array
        {
            $correlationId ??= (string) Str::uuid();

            $this->logger->info('AI Photography Constructor started', [
                'preferences' => $preferences,
                'correlation_id' => $correlationId
            ]);

            // 1. Поиск подходящего стиля (имитация ML)
            $style = $preferences['style'] ?? 'minimalism';

            // 2. Подбор фотографа по специализации и рейтингу
            $photographer = Photographer::where('specialization', 'like', "%{$style}%")
                ->where('is_available', true)
                ->orderBy('experience_years', 'desc')
                ->first();

            // 3. Подбор студии по удобствам
            $studio = PhotoStudio::where('is_active', true)
                ->where('amenities', 'like', "%{$style}%")
                ->first();

            // 4. Подбор наиболее подходящего пакета
            $session = PhotoSession::where('is_active', true)
                ->where('price_kopecks', '<=', $preferences['max_budget'] ?? 1000000)
                ->orderBy('price_kopecks', 'desc')
                ->first();

            $result = [
                'recommended_style' => $style,
                'photographer' => $photographer?->full_name ?? 'Expert System Choice',
                'photographer_uuid' => $photographer?->uuid,
                'studio' => $studio?->name ?? 'Outdoor / Location',
                'studio_uuid' => $studio?->uuid,
                'session_package' => $session?->name ?? 'Standard Package',
                'estimated_total_kopecks' => ($session?->price_kopecks ?? 500000) + ($photographer?->base_price_hour_kopecks ?? 0),
                'confidence_score' => 0.92,
                'correlation_id' => $correlationId
            ];

            $this->logger->info('AI Photography Constructor finished', [
                'result' => $result,
                'correlation_id' => $correlationId
            ]);

            return $result;
        }
}

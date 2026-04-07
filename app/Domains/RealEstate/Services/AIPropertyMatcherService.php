<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;


use Psr\Log\LoggerInterface;
final readonly class AIPropertyMatcherService
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    /**
         * Подобрать недвижимость на основе текстовых предпочтений или описания "мечты"
         *
         * @param string $userDream
         * @param int $userId
         * @param string $correlationId
         * @return Collection
         */
        public function matchByDream(string $userDream, int $userId, string $correlationId): Collection
        {
            $this->logger->info('AI matching by dream start', [
                'user_id' => $userId,
                'dream' => $userDream,
                'correlation_id' => $correlationId
            ]);

            // 1. Извлечение ключевых параметров из текста пользователя
            $params = $this->extractParamsFromText($userDream);

            // 2. Поиск в БД по параметрам (базовый)
            $query = Listing::query()
                ->where('status', 'active')
                ->where('price', '<=', $params['max_price'] ?? 5000000000); // 50 млн руб по умолчанию

            if (!empty($params['type'])) {
                $query->whereHas('property', fn($q) => $q->where('type', $params['type']));
            }

            if (!empty($params['min_area'])) {
                $query->whereHas('property', fn($q) => $q->where('area', '>=', $params['min_area']));
            }

            $results = $query->with('property')->limit(10)->get();

            // 3. AI-скоринг релевантности для каждого результата
            return $results->map(function ($listing) use ($userDream) {
                $listing->ai_match_score = $this->calculateMatchScore($listing, $userDream);
                return $listing;
            })->sortByDesc('ai_match_score');
        }

        /**
         * AI-калькулятор доходности для B2B инвесторов
         *
         * @param Listing $listing
         * @param string $correlationId
         * @return array
         */
        public function calculateInvestmentPotential(Listing $listing, string $correlationId): array
        {
            $property = $listing->property;
            $cost = $listing->price;

            // Моделируем доходность на 5 лет
            $yearlyGross = ($listing->deal_type === 'rent_long') ? ($listing->price * 12) : ($listing->price * 0.08);
            $expenses = $yearlyGross * 0.20; // 20% на налоги и управление
            $netOperatingIncome = $yearlyGross - $expenses;

            $capRate = ($netOperatingIncome / $cost) * 100;

            return [
                'listing_uuid' => $listing->uuid,
                'cap_rate' => round($capRate, 2),
                'expected_roi_5y' => round($capRate * 5 + 15, 2), // Пример: +15% рост рынка
                'investment_grade' => $capRate > 7 ? 'A' : ($capRate > 4 ? 'B' : 'C'),
                'recommended_strategy' => $capRate > 6 ? 'Yield' : 'Capital Appreciation',
                'correlation_id' => $correlationId
            ];
        }

        private function extractParamsFromText(string $text): array
        {
            // В реальном проекте здесь вызов OpenAI
            // Имитируем парсинг: "хочу квартиру в центре 50 метров до 10 млн"
            return [
                'type' => Str::contains($text, ['квартира', 'апартаменты']) ? 'apartment' : 'house',
                'max_price' => Str::contains($text, '10 млн') ? 1000000000 : null,
                'min_area' => Str::contains($text, '50 метр') ? 50 : null,
            ];
        }

        private function calculateMatchScore(Listing $listing, string $dream): float
        {
             // Простое подобие для примера
             $score = 0.5;
             if (Str::contains($dream, $listing->property->type)) $score += 0.2;
             if (Str::contains($dream, $listing->property->address)) $score += 0.3;
             return min($score, 1.0);
        }
}

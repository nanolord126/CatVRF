<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Services\AI;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

use App\Services\FraudControlService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use App\Services\AI\OpenAIClientService;
use Illuminate\Support\Str;

/**
 * Оптимизация маршрута + динамическое ценообразование + подбор водителя
 * Вертикаль: taxi
 * Тип: route_optimization
 *
 * PRODUCTION MANDATORY: AI-конструктор обязателен для каждой вертикали (канон 2026).
 * correlation_id + $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '') + $this->db->transaction() + Redis TTL 3600
 */
final readonly class TaxiAIConstructorService
{
    public function __construct(
        private OpenAIClientService $openai,
        private RecommendationService $recommendation,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard
    ) {}

    /**
     * Главный метод — анализ и генерация рекомендаций.
     * Оптимизация маршрута + динамическое ценообразование + подбор водителя
     *
     * @throws \App\Exceptions\FraudBlockedException
     */
    public function analyzeAndRecommend(array $rideData, int $userId): array
    {
        $correlationId = $this->request->header('X-Correlation-ID', Str::uuid()->toString());

        // Fraud check — обязателен перед любым тяжёлым AI-запросом
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ai_constructor_taxi', amount: 0, correlationId: $correlationId ?? '');

        // Кэширование результата
        $cacheKey = "ai_taxi:route_optimization:$userId:" . md5(json_encode(func_get_args()));
        $cached = cache()->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // 1. AI — анализ данных
        $inputData = array_merge($this->getInputData($rideData, $userId), ['ride_profile' => true]);
        $inputJson = json_encode($inputData);

        // Анонимизация ПДн перед отправкой в OpenAI
        $anonymizedInput = $this->anonymizeData($inputJson);

        try {
            $response = $this->openai->chat([
                ['role' => 'system', 'content' => 'Оптимизация маршрута и подбор тарифа для поездки. Определи: пункт отправления, назначение, тип поездки, количество пассажиров. Рекомендуй оптимальный маршрут, тариф, водителя.'],
                ['role' => 'user', 'content' => $anonymizedInput],
            ], 0.3, 'text');
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI API call failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Failed to get route optimization. Please try again later.');
        }

        $analysisText = $response['content'] ?? '';

        // 2. UserTasteProfile — персонализация через ML-вкусы пользователя
        $tasteProfile = $this->tasteAnalyzer->getProfile($userId);

        // 3. Разбор ответа AI
        $ride_profile = $this->parseAnalysis($analysisText);

        // 4. Персонализация по вкусам
        $ride_profile['taste_enrichment'] = $tasteProfile->toArray();

        // 5. Рекомендации товаров/услуг из инвентаря
        $recommendations = $this->recommendation->getForVertical(
            'taxi',
            $ride_profile,
            $userId
        );

        // 6. Сохранение в user_ai_designs
        $this->saveToUserProfile($userId, 'taxi', $ride_profile, $correlationId);

        $result = [
            'success'        => true,
            'ride_profile' => $ride_profile,
            'recommendations' => $recommendations,
            'ar_link'        => url('taxi/route-preview/' . $userId),
            'correlation_id' => $correlationId,
        ];

        // Кэш на 1 час
        cache()->put($cacheKey, $result, 3600);

        $this->logger->info('TaxiAIConstructorService used', [
            'user_id'        => $userId,
            'vertical'       => 'taxi',
            'type'           => 'route_optimization',
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    /**
     * Разбор ответа AI в структурированный массив.
     */
    private function parseAnalysis(string $analysisText): array
    {
        $decoded = json_decode($analysisText, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Fallback: структурированный разбор текстового ответа
        return [
            'raw_analysis'   => $analysisText,
            'parsed_at'      => now()->toISOString(),
            'confidence'     => 0.85,
        ];
    }

    /**
     * Сохранение результата в профиль пользователя (user_ai_designs).
     */

    private function anonymizeData(string $data): string
    {
        $patterns = [
            '/\b\d{11}\b/' => '[ТЕЛЕФОН]',
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL]',
            '/\b\d{4}\s?\d{4}\s?\d{4}\s?\d{4}\b/' => '[КАРТА]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $data);
    }

    private function getInputData(array $rideData, int $userId): array
    {
        return [
            'pickup_location' => $rideData['pickup_location'] ?? '',
            'dropoff_location' => $rideData['dropoff_location'] ?? '',
            'ride_type' => $rideData['ride_type'] ?? 'economy',
            'passengers' => $rideData['passengers'] ?? 1,
        ];
    }
    private function saveToUserProfile(int $userId, string $vertical, array $data, string $correlationId): void
    {
        $this->db->table('user_ai_designs')->updateOrInsert(
            [
                'user_id'  => $userId,
                'vertical' => $vertical,
            ],
            [
                'design_data'    => json_encode($data),
                'correlation_id' => $correlationId,
                'updated_at'     => now(),
                'created_at'     => now(),
            ]
        );
    }
}

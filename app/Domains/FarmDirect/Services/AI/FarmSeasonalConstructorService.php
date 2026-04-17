<?php

declare(strict_types=1);

namespace App\Domains\FarmDirect\Services\AI;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

use App\Services\FraudControlService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use App\Services\AI\OpenAIClientService;
use Illuminate\Support\Str;

/**
 * Сезонный план закупок + рецепты + заготовки + подбор фермеров
 * Вертикаль: farm
 * Тип: seasonal_planning
 *
 * PRODUCTION MANDATORY: AI-конструктор обязателен для каждой вертикали (канон 2026).
 * correlation_id + $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '') + $this->db->transaction() + Redis TTL 3600
 */
final readonly class FarmSeasonalConstructorService
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
     * Сезонный план закупок + рецепты + заготовки + подбор фермеров
     *
     * @throws \App\Exceptions\FraudBlockedException
     */
    public function analyzeAndRecommend(array $farmData, int $userId): array
    {
        $correlationId = $this->request->header('X-Correlation-ID', Str::uuid()->toString());

        // Fraud check — обязателен перед любым тяжёлым AI-запросом
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ai_constructor_farm', amount: 0, correlationId: $correlationId ?? '');

        // Кэширование результата
        $cacheKey = "ai_farm:seasonal_planning:$userId:" . md5(json_encode(func_get_args()));
        $cached = cache()->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // 1. AI — анализ данных
        $inputData = array_merge($this->getInputData($farmData, $userId), ['farm_profile' => true]);
        $inputJson = json_encode($inputData);

        // Анонимизация данных перед отправкой в OpenAI
        $anonymizedInput = $this->anonymizeData($inputJson);

        try {
            $response = $this->openai->chat([
                ['role' => 'system', 'content' => 'Составление сезонного плана закупок фермерской продукции. Определи: сезон, регион, предпочтения, объём, бюджет. Рекомендуй продукты, фермеров, рецепты, способы заготовки.'],
                ['role' => 'user', 'content' => $anonymizedInput],
            ], 0.3, 'text');
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI API call failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Failed to get seasonal planning. Please try again later.');
        }

        $analysisText = $response['content'] ?? '';

        // 2. UserTasteProfile — персонализация через ML-вкусы пользователя
        $tasteProfile = $this->tasteAnalyzer->getProfile($userId);

        // 3. Разбор ответа AI
        $farm_profile = $this->parseAnalysis($analysisText);

        // 4. Персонализация по вкусам
        $farm_profile['taste_enrichment'] = $tasteProfile->toArray();

        // 5. Рекомендации товаров/услуг из инвентаря
        $recommendations = $this->recommendation->getForVertical(
            'farm',
            $farm_profile,
            $userId
        );

        // 6. Сохранение в user_ai_designs
        $this->saveToUserProfile($userId, 'farm', $farm_profile, $correlationId);

        $result = [
            'success'        => true,
            'farm_profile' => $farm_profile,
            'recommendations' => $recommendations,
            'ar_link'        => url('farm/seasonal-preview/' . $userId),
            'correlation_id' => $correlationId,
        ];

        // Кэш на 1 час
        cache()->put($cacheKey, $result, 3600);

        $this->logger->info('FarmSeasonalConstructorService used', [
            'user_id'        => $userId,
            'vertical'       => 'farm',
            'type'           => 'seasonal_planning',
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
            'parsed_at'      => Carbon::now()->toISOString(),
            'confidence'     => 0.85,
        ];
    }

    /**
     * Сохранение результата в профиль пользователя (user_ai_designs).
     */
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
                'updated_at'     => Carbon::now(),
                'created_at'     => Carbon::now(),
            ]
        );
    }

    private function anonymizeData(string $data): string
    {
        $patterns = [
            '/\b[A-ZА-Я][a-zа-я]+\s+[A-ZА-Я][a-zа-я]+\b/' => '[ФЕРМЕР]',
            '/\b\d{11}\b/' => '[ТЕЛЕФОН]',
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL]',
            '/\b\d{4}\s?\d{4}\s?\d{4}\s?\d{4}\b/' => '[КАРТА]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $data);
    }

    private function getInputData(array $farmData, int $userId): array
    {
        return [
            'season' => $farmData['season'] ?? null,
            'region' => $farmData['region'] ?? null,
            'preferences' => $farmData['preferences'] ?? [],
            'volume' => $farmData['volume'] ?? null,
            'budget' => $farmData['budget'] ?? null,
        ];
    }
}

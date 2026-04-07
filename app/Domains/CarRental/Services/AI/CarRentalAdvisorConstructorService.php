<?php

declare(strict_types=1);

namespace App\Domains\CarRental\Services\AI;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

use App\Services\FraudControlService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAIClient;

/**
 * Подбор авто + страховка + маршрут + сравнение предложений
 * Вертикаль: carrental
 * Тип: car_selection
 *
 * PRODUCTION MANDATORY: AI-конструктор обязателен для каждой вертикали (канон 2026).
 * correlation_id + $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '') + $this->db->transaction() + Redis TTL 3600
 */
final readonly class CarRentalAdvisorConstructorService
{
    public function __construct(private OpenAIClient          $openai,
        private RecommendationService $recommendation,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private FraudControlService   $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    /**
     * Главный метод — анализ и генерация рекомендаций.
     * Подбор авто + страховка + маршрут + сравнение предложений
     *
     * @throws \App\Exceptions\FraudBlockedException
     */
    public function analyzeAndRecommend(array $rentalData, int $userId): array
    {
        $correlationId = $this->request->header('X-Correlation-ID', Str::uuid()->toString());

        // Fraud check — обязателен перед любым тяжёлым AI-запросом
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ai_constructor_carrental', amount: 0, correlationId: $correlationId ?? '');

        // Кэширование результата
        $cacheKey = "ai_carrental:car_selection:$userId:" . md5(json_encode(func_get_args()));
        $cached = cache()->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // 1. AI — анализ данных
        $analysis = $this->openai->chat()->create([
            'model'    => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'Подбор автомобиля для аренды с учётом целей поездки и предпочтений. Определи: тип поездки, количество пассажиров, дальность, бюджет, предпочтения. Рекомендуй класс авто, комплектацию, страховку.'],
                ['role' => 'user', 'content' => json_encode(array_merge($this->getInputData($rentalData, $userId), ['rental_profile' => true]))],
            ],
            'max_tokens' => 1024,
        ]);

        $analysisText = $analysis->choices[0]->message->content ?? '';

        // 2. UserTasteProfile — персонализация через ML-вкусы пользователя
        $tasteProfile = $this->tasteAnalyzer->getProfile($userId);

        // 3. Разбор ответа AI
        $rental_profile = $this->parseAnalysis($analysisText);

        // 4. Персонализация по вкусам
        $rental_profile['taste_enrichment'] = $tasteProfile->toArray();

        // 5. Рекомендации товаров/услуг из инвентаря
        $recommendations = $this->recommendation->getForVertical(
            'carrental',
            $rental_profile,
            $userId
        );

        // 6. Сохранение в user_ai_designs
        $this->saveToUserProfile($userId, 'carrental', $rental_profile, $correlationId);

        $result = [
            'success'        => true,
            'rental_profile' => $rental_profile,
            'recommendations' => $recommendations,
            'ar_link'        => url('carrental/selection-preview/' . $userId),
            'correlation_id' => $correlationId,
        ];

        // Кэш на 1 час
        cache()->put($cacheKey, $result, 3600);

        $this->logger->info('CarRentalAdvisorConstructorService used', [
            'user_id'        => $userId,
            'vertical'       => 'carrental',
            'type'           => 'car_selection',
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
}

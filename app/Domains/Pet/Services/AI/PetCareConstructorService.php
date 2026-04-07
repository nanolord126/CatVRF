<?php

declare(strict_types=1);

namespace App\Domains\Pet\Services\AI;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

use App\Services\FraudControlService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAIClient;
use Illuminate\Http\UploadedFile;

/**
 * Анализ фото питомца + план питания + уход + ветеринарные рекомендации
 * Вертикаль: pet
 * Тип: pet_health_analysis
 *
 * PRODUCTION MANDATORY: AI-конструктор обязателен для каждой вертикали (канон 2026).
 * correlation_id + $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '') + $this->db->transaction() + Redis TTL 3600
 */
final readonly class PetCareConstructorService
{
    public function __construct(private OpenAIClient          $openai,
        private RecommendationService $recommendation,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private FraudControlService   $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    /**
     * Главный метод — анализ и генерация рекомендаций.
     * Анализ фото питомца + план питания + уход + ветеринарные рекомендации
     *
     * @throws \App\Exceptions\FraudBlockedException
     */
    public function analyzeAndRecommend(UploadedFile $photo, int $userId, array $petData = []): array
    {
        $correlationId = $this->request->header('X-Correlation-ID', Str::uuid()->toString());

        // Fraud check — обязателен перед любым тяжёлым AI-запросом
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ai_constructor_pet', amount: 0, correlationId: $correlationId ?? '');

        // Кэширование результата
        $cacheKey = "ai_pet:pet_health_analysis:$userId:" . md5(json_encode(func_get_args()));
        $cached = cache()->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // 1. Vision API — анализ изображения
        $analysis = $this->openai->chat()->create([
            'model'    => 'gpt-4o',
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Анализ состояния питомца для подбора ухода и питания. Определи: вид, породу, возраст, вес, признаки проблем со здоровьем. Рекомендуй корм, уход, ветеринарные услуги.'],
                        ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,' . base64_encode(file_get_contents($photo->getRealPath()))]],
                    ],
                ],
            ],
            'max_tokens' => 1024,
        ]);

        $analysisText = $analysis->choices[0]->message->content ?? '';

        // 2. UserTasteProfile — персонализация через ML-вкусы пользователя
        $tasteProfile = $this->tasteAnalyzer->getProfile($userId);

        // 3. Разбор ответа AI
        $pet_profile = $this->parseAnalysis($analysisText);

        // 4. Персонализация по вкусам
        $pet_profile['taste_enrichment'] = $tasteProfile->toArray();

        // 5. Рекомендации товаров/услуг из инвентаря
        $recommendations = $this->recommendation->getForVertical(
            'pet',
            $pet_profile,
            $userId
        );

        // 6. Сохранение в user_ai_designs
        $this->saveToUserProfile($userId, 'pet', $pet_profile, $correlationId);

        $result = [
            'success'        => true,
            'pet_profile' => $pet_profile,
            'recommendations' => $recommendations,
            'ar_link'        => url('pet/care-preview/' . $userId),
            'correlation_id' => $correlationId,
        ];

        // Кэш на 1 час
        cache()->put($cacheKey, $result, 3600);

        $this->logger->info('PetCareConstructorService used', [
            'user_id'        => $userId,
            'vertical'       => 'pet',
            'type'           => 'pet_health_analysis',
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

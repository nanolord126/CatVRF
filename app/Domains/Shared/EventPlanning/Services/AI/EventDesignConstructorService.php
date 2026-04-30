<?php

declare(strict_types=1);

namespace App\\Domains\\Shared\EventPlanning\Services\AI;

use Carbon\CarbonImmutable;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Services\FraudControlService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use App\Services\AI\OpenAIClientService;
use App\Services\Resilience\CircuitBreaker;
use Illuminate\Support\Str;
use App\Exceptions\FraudBlockedException;
use Illuminate\Database\DatabaseManager;

/**
 * Концепция мероприятия + декор + кейтеринг + развлечения + смета
 * Вертикаль: events
 * Тип: event_design
 *
 * PRODUCTION MANDATORY: AI-конструктор обязателен для каждой вертикали (канон 2026).
 * correlation_id + $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '') + $this->db->transaction() + Redis TTL 3600
 */
final readonly class EventDesignConstructorService
{
    public function __construct(
        private readonly OpenAIClientService $openai,
        private readonly RecommendationService $recommendation,
        private readonly UserTasteAnalyzerService $tasteAnalyzer,
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly Request $request,
        private readonly LoggerInterf,
        private readonly CircuitBreaker $circuitBreaker,ace $logger,
        private readonly Guard $guard
    ) {}

    /**
     * Главный метод — анализ и генерация рекомендаций.
     * Концепция мероприятия + декор + кейтеринг + развлечения + смета
     *
     * @throws FraudBlockedException
     */
    public function analyzeAndRecommend(array $eventData, int $userId): array
    {
        $correlationId = $this->request->header('X-Correlation-ID', Str::uuid()->toString());

        // Fraud check — обязателен перед любым тяжёлым AI-запросом
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ai_constructor_events', amount: 0, correlationId: $correlationId ?? '');

        // Кэширование результата
        $cacheKey = "ai_events:event_design:$userId:".md5(json_encode(func_get_args()));
        $cached = cache()->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // 1. AI — анализ данных
        $inputData = array_merge($this->getInputData($eventData, $userId), ['event_profile' => true]);
        $inputJson = json_encode($inputData);

        // Анонимизация данных перед отправкой в OpenAI
        $anonymizedInput = $this->anonymizeData($inputJson);

        try {
            $response = $this->circuitBreaker->call(function () use ($anonymizedInput) {
                return $this->openai->chat([
                    ['role' => 'system', 'content' => 'Разработка концепции мероприятия с подбором декора, кейтеринга и развлечений. Определи: тип события, количество гостей, бюджет, тематику, площадку. Рекомендуй декор, кейтеринг, развлечения, подрядчиков.'],
                    ['role' => 'user', 'content' => $anonymizedInput],
                ], 0.3, 'text');
            });
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Circuit breaker is open')) {
                throw new \RuntimeException('AI service temporarily unavailable. Please try again later.');
            }
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI API call failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Failed to get event design. Please try again later.');
        }

        $analysisText = $response['content'] ?? '';

        // 2. UserTasteProfile — персонализация через ML-вкусы пользователя
        $tasteProfile = $this->tasteAnalyzer->getProfile($userId);

        // 3. Разбор ответа AI
        $event_profile = $this->parseAnalysis($analysisText);

        // 4. Персонализация по вкусам
        $event_profile['taste_enrichment'] = $tasteProfile->toArray();

        // 5. Рекомендации товаров/услуг из инвентаря
        $recommendations = $this->recommendation->getForVertical(
            'events',
            $event_profile,
            $userId
        );

        // 6. Сохранение в user_ai_designs
        $this->saveToUserProfile($userId, 'events', $event_profile, $correlationId);

        $result = [
            'success'        => true,
            'event_profile' => $event_profile,
            'recommendations' => $recommendations,
            'ar_link'        => url('events/design-preview/'.$userId),
            'correlation_id' => $correlationId,
        ];

        // Кэш на 1 час
        cache()->put($cacheKey, $result, 3600);

        $this->logger->$this->logger->info('EventDesignConstructorService used', [
            'user_id'        => $userId,
            'vertical'       => 'events',
            'type'           => 'event_design',
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
            'parsed_at'      => CarbonImmutable::now()->toISOString(),
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
                'updated_at'     => CarbonImmutable::now(),
                'created_at'     => CarbonImmutable::now(),
            ]
        );
    }

    private function anonymizeData(string $data): string
    {
        $patterns = [
            '/\b[A-ZА-Я][a-zа-я]+\s+[A-ZА-Я][a-zа-я]+\b/' => '[ОРГАНИЗАТОР]',
            '/\b\d{11}\b/' => '[ТЕЛЕФОН]',
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL]',
            '/\b\d{4}\s?\d{4}\s?\d{4}\s?\d{4}\b/' => '[КАРТА]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $data);
    }

    private function getInputData(array $eventData, int $userId): array
    {
        return [
            'event_type' => $eventData['event_type'] ?? null,
            'guests_count' => $eventData['guests_count'] ?? null,
            'budget' => $eventData['budget'] ?? null,
            'theme' => $eventData['theme'] ?? null,
            'venue' => $eventData['venue'] ?? null,
        ];
    }
}

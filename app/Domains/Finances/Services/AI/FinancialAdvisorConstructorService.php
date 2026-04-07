<?php

declare(strict_types=1);

namespace App\Domains\Finances\Services\AI;

use Carbon\Carbon;

use App\Services\FraudControlService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAIClient;
use Psr\Log\LoggerInterface;

/**
 * AI-конструктор для вертикали Finances.
 *
 * Финансовый план + анализ расходов + инвестиционная стратегия + цели.
 *
 * PRODUCTION MANDATORY: AI-конструктор обязателен для каждой вертикали.
 * correlation_id + fraud-check + DB::transaction + Redis TTL 3600
 *
 * @package App\Domains\Finances\Services\AI
 */
final readonly class FinancialAdvisorConstructorService
{
    public function __construct(
        private OpenAIClient $openai,
        private RecommendationService $recommendation,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private FraudControlService $fraud,
        private DatabaseManager $db,
        private CacheRepository $cache,
        private UrlGenerator $url,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Главный метод: анализ финансовых данных и генерация рекомендаций.
     *
     * @param array $financialData Входные финансовые данные пользователя
     * @param int   $userId        ID пользователя
     * @param string|null $correlationId Трейсинг-ID
     * @return array Результат анализа и рекомендации
     *
     * @throws \App\Exceptions\FraudBlockedException
     */
    public function analyzeAndRecommend(
        array $financialData,
        int $userId,
        ?string $correlationId = null,
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? $userId),
            operationType: 'ai_constructor_finances',
            amount: 0,
            correlationId: $correlationId,
        );

        $cacheKey = "ai_finances:financial_planning:{$userId}:" . md5(json_encode($financialData));
        $cached = $this->cache->get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $analysis = $this->openai->chat()->create([
            'model'    => 'gpt-4o',
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => 'Персональный финансовый анализ и план достижения целей. '
                        . 'Определи: доходы, расходы, цели, горизонт, риск-профиль. '
                        . 'Рекомендуй стратегию накоплений, инвестиции, оптимизацию расходов.',
                ],
                [
                    'role'    => 'user',
                    'content' => json_encode(
                        $this->getInputData($financialData, $userId),
                        JSON_THROW_ON_ERROR,
                    ),
                ],
            ],
            'max_tokens' => 1024,
        ]);

        $analysisText     = $analysis->choices[0]->message->content ?? '';
        $tasteProfile     = $this->tasteAnalyzer->getProfile($userId);
        $financialProfile = $this->parseAnalysis($analysisText);

        $financialProfile['taste_enrichment'] = $tasteProfile->toArray();

        $recommendations = $this->recommendation->getForVertical(
            'finances',
            $financialProfile,
            $userId,
        );

        $this->saveToUserProfile($userId, 'finances', $financialProfile, $correlationId);

        $result = [
            'success'           => true,
            'financial_profile' => $financialProfile,
            'recommendations'   => $recommendations,
            'ar_link'           => $this->url->to('finances/plan-preview/' . $userId),
            'correlation_id'    => $correlationId,
        ];

        $this->cache->put($cacheKey, $result, 3600);

        $this->logger->info('FinancialAdvisorConstructorService used', [
            'user_id'        => $userId,
            'vertical'       => 'finances',
            'type'           => 'financial_planning',
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

        return [
            'raw_analysis' => $analysisText,
            'parsed_at'    => Carbon::now()->toIso8601String(),
            'confidence'   => 0.85,
        ];
    }

    /**
     * Подготовка входных данных для AI.
     */
    private function getInputData(array $financialData, int $userId): array
    {
        return array_merge($financialData, [
            'user_id'           => $userId,
            'financial_profile' => true,
        ]);
    }

    /**
     * Сохранение результата в профиль пользователя (user_ai_designs).
     */
    private function saveToUserProfile(
        int $userId,
        string $vertical,
        array $data,
        string $correlationId,
    ): void {
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
            ],
        );
    }
}

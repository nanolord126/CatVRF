<?php declare(strict_types=1);

namespace App\Domains\Art\Services\AI;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use App\Services\UserTasteAnalyzerService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAIClient;

final readonly class ArtConstructorService
{
    public function __construct(
        private FraudControlService $fraud,
        private RecommendationService $recommendation,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private Cache $cache,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
        private readonly OpenAIClient $openai
    ) {}

    /**
     * Универсальный метод AI-конструктора.
     * Принимает payload (параметры запроса) и возвращает персонализированные рекомендации.
     */
    public function analyzeAndRecommend(array $payload, int $userId): array
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'art_ai_constructor', amount: 0, correlationId: $correlationId ?? '');

        $cacheKey = 'user_ai_designs:Art:' . $userId . ':' . md5(serialize($payload));

        return $this->cache->remember($cacheKey, Carbon::now()->addHour(), function () use ($payload, $userId, $correlationId) {
            // Получаем профиль вкусов пользователя
            $taste = $this->tasteAnalyzer->getProfile($userId);

            // Строим полный профиль: payload + вкусы
            $fullProfile = array_merge($payload, (array) ($taste->preferences ?? []));

            // AI-анализ через OpenAI (вынесен из DB transaction)
            $aiAnalysis = $this->analyzeWithAI($fullProfile, $correlationId);

            // Получаем рекомендации на основе AI-анализа
            $recommendations = $this->recommendation->getForVertical('Art', $fullProfile, $userId);

            $this->logger->info('Art AI constructor used', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
                'vertical' => 'Art',
            ]);

            return [
                'success' => true,
                'vertical' => 'Art',
                'profile' => $fullProfile,
                'ai_analysis' => $aiAnalysis,
                'recommendations' => $recommendations,
                'correlation_id' => $correlationId,
                'prompt_hint' => 'Анализ художественных предпочтений. Подбери произведения искусства по стилю и бюджету.',
            ];
        });
    }

    private function analyzeWithAI(array $profile, string $correlationId): array
    {
        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Ты эксперт по искусству. Анализируй художественные предпочтения и давай рекомендации по стилю, жанру и бюджету.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($profile, JSON_UNESCAPED_UNICODE),
                    ],
                ],
                'max_tokens' => 1024,
                'temperature' => 0.7,
            ]);

            $content = $response->choices[0]->message->content ?? '{}';
            $analysis = json_decode($content, true);

            if ($analysis === null || !is_array($analysis)) {
                return ['raw_analysis' => $content, 'confidence' => 0.5];
            }

            return array_merge($analysis, ['confidence' => 0.85]);
        } catch (\Throwable $e) {
            $this->logger->error('Art AI analysis failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return ['error' => 'AI analysis temporarily unavailable', 'confidence' => 0.0];
        }
    }
}
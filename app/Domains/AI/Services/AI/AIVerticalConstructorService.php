<?php

declare(strict_types=1);

namespace App\Domains\AI\Services\AI;

use Carbon\CarbonImmutable;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use App\Domains\AI\Services\UserTasteAnalyzerService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

final readonly class AIVerticalConstructorService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly RecommendationService $recommendation,
        private readonly UserTasteAnalyzerService $tasteAnalyzer,
        private readonly Cache $cache,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Универсальный метод AI-конструктора.
     * Принимает payload (параметры запроса) и возвращает персонализированные рекомендации.
     */
    public function analyzeAndRecommend(array $payload, int $userId): array
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ai_ai_constructor', amount: 0, correlationId: $correlationId ?? '');

        $cacheKey = 'user_ai_designs:AI:'.$userId.':'.md5(serialize($payload));

        return $this->cache->remember($cacheKey, CarbonImmutable::now()->addHour(), function () use ($payload, $userId, $correlationId) {
            // Получаем профиль вкусов пользователя
            $taste = $this->tasteAnalyzer->analyzeUserPreferences($userId);

            // Строим полный профиль: payload + вкусы
            $fullProfile = array_merge($payload, (array) ($taste ?? []));

            // Получаем рекомендации
            $recommendations = $this->recommendation->getForUser($userId, 'AI', $fullProfile);

            $this->logger->info('AI AI constructor used', [
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
                'vertical'       => 'AI',
            ]);

            return [
                'success'         => true,
                'vertical'        => 'AI',
                'profile'         => $fullProfile,
                'recommendations' => $recommendations,
                'correlation_id'  => $correlationId,
                'prompt_hint'     => 'Подбор AI-инструментов и моделей под конкретную задачу пользователя.',
            ];
        });
    }
}

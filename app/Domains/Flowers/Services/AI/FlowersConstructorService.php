<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Services\AI;

use App\Services\FraudControlService;
use App\Services\RecommendationService;
use App\Services\UserTasteAnalyzerService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FlowersConstructorService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly RecommendationService $recommendation,
        private readonly UserTasteAnalyzerService $tasteAnalyzer,
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Универсальный метод AI-конструктора.
     * Принимает payload (параметры запроса) и возвращает персонализированные рекомендации.
     *
     * @param array $payload Параметры запроса
     * @param int   $userId  ID пользователя
     *
     * @return array Результат AI-конструктора
     */
    public function analyzeAndRecommend(array $payload, int $userId): array
    {
        $correlationId = Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'flowers_ai_constructor',
            amount: 0,
            correlationId: $correlationId,
        );

        $cacheKey = 'user_ai_designs:Flowers:' . $userId . ':' . md5(serialize($payload));

        return $this->cache->remember($cacheKey, Carbon::now()->addHour(), function () use ($payload, $userId, $correlationId): array {
            // Получаем профиль вкусов пользователя
            $taste = $this->tasteAnalyzer->getProfile($userId);

            // Строим полный профиль: payload + вкусы
            $fullProfile = array_merge($payload, (array) ($taste->preferences ?? []));

            // Получаем рекомендации
            $recommendations = $this->recommendation->getForVertical('Flowers', $fullProfile, $userId);

            $this->logger->info('Flowers AI constructor used', [
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
                'vertical'       => 'Flowers',
            ]);

            return [
                'success'         => true,
                'vertical'        => 'Flowers',
                'profile'         => $fullProfile,
                'recommendations' => $recommendations,
                'correlation_id'  => $correlationId,
                'prompt_hint'     => 'Подбор цветочной композиции. Определи повод, стиль и предпочтения получателя.',
            ];
        });
    }
}
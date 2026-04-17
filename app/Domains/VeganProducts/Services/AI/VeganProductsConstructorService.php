<?php declare(strict_types=1);

namespace App\Domains\VeganProducts\Services\AI;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use App\Services\UserTasteAnalyzerService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

final readonly class VeganProductsConstructorService
{
    public function __construct(
        private FraudControlService   $fraud,
        private RecommendationService  $recommendation,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private Cache                  $cache, private readonly LoggerInterface $logger, private readonly Guard $guard
    ) {}

    /**
     * Универсальный метод AI-конструктора.
     * Принимает payload (параметры запроса) и возвращает персонализированные рекомендации.
     */
    public function analyzeAndRecommend(array $payload, int $userId): array
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'veganproducts_ai_constructor', amount: 0, correlationId: $correlationId ?? '');

        $cacheKey = 'user_ai_designs:VeganProducts:' . $userId . ':' . md5(serialize($payload));

        return $this->cache->tags(['vegan', 'ai', 'constructor'])->remember($cacheKey, now()->addHour(), function () use ($payload, $userId, $correlationId) {
            // Получаем профиль вкусов пользователя
            $taste = $this->tasteAnalyzer->getProfile($userId);

            // Строим полный профиль: payload + вкусы
            $fullProfile = array_merge($payload, (array) ($taste->preferences ?? []));

            // Получаем рекомендации
            $recommendations = $this->recommendation->getForVertical('VeganProducts', $fullProfile, $userId);

            $this->logger->info('VeganProducts AI constructor used', [
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
                'vertical'       => 'VeganProducts',
            ]);

            return [
                'success'         => true,
                'vertical'        => 'VeganProducts',
                'profile'         => $fullProfile,
                'recommendations' => $recommendations,
                'correlation_id'  => $correlationId,
                'prompt_hint'     => 'Подбор веганских продуктов. Определи диетические ограничения и предпочтения.',
            ];
        });
    }
}
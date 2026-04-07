<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Services\AI;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use App\Services\UserTasteAnalyzerService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

final readonly class ShortTermRentalsConstructorService
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

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'shorttermrentals_ai_constructor', amount: 0, correlationId: $correlationId ?? '');

        $cacheKey = 'user_ai_designs:ShortTermRentals:' . $userId . ':' . md5(serialize($payload));

        return $this->cache->remember($cacheKey, now()->addHour(), function () use ($payload, $userId, $correlationId) {
            // Получаем профиль вкусов пользователя
            $taste = $this->tasteAnalyzer->getProfile($userId);

            // Строим полный профиль: payload + вкусы
            $fullProfile = array_merge($payload, (array) ($taste->preferences ?? []));

            // Получаем рекомендации
            $recommendations = $this->recommendation->getForVertical('ShortTermRentals', $fullProfile, $userId);

            $this->logger->info('ShortTermRentals AI constructor used', [
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
                'vertical'       => 'ShortTermRentals',
            ]);

            return [
                'success'         => true,
                'vertical'        => 'ShortTermRentals',
                'profile'         => $fullProfile,
                'recommendations' => $recommendations,
                'correlation_id'  => $correlationId,
                'prompt_hint'     => 'Подбор краткосрочной аренды. Определи предпочтения по жилью, локации и бюджету.',
            ];
        });
    }
}
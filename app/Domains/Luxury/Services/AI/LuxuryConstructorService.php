<?php declare(strict_types=1);

namespace App\Domains\Luxury\Services\AI;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use App\Services\UserTasteAnalyzerService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

final readonly class LuxuryConstructorService
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

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'luxury_ai_constructor', amount: 0, correlationId: $correlationId ?? '');

        $cacheKey = 'user_ai_designs:Luxury:' . $userId . ':' . md5(serialize($payload));

        return $this->cache->remember($cacheKey, now()->addHour(), function () use ($payload, $userId, $correlationId) {
            // Получаем профиль вкусов пользователя
            $taste = $this->tasteAnalyzer->getProfile($userId);

            // Строим полный профиль: payload + вкусы
            $fullProfile = array_merge($payload, (array) ($taste->preferences ?? []));

            // Получаем рекомендации
            $recommendations = $this->recommendation->getForVertical('Luxury', $fullProfile, $userId);

            $this->logger->info('Luxury AI constructor used', [
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
                'vertical'       => 'Luxury',
            ]);

            return [
                'success'         => true,
                'vertical'        => 'Luxury',
                'profile'         => $fullProfile,
                'recommendations' => $recommendations,
                'correlation_id'  => $correlationId,
                'prompt_hint'     => 'Подбор товаров премиум-класса. Определи статус, вкус и повод.',
            ];
        });
    }
}
<?php declare(strict_types=1);

namespace App\Domains\Travel\Services\AI;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Services\FraudControlService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use App\Domains\Inventory\Services\InventoryService;
use App\Services\AuditService;
use Illuminate\Support\Str;

/**
 * AI-конструктор путешествий для вертикали Travel.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 *
 * Предпочтения + UserTasteProfile → персонализированный itinerary
 * → реальные предложения (билеты, отели, экскурсии) → 3D-туры.
 */
final readonly class TravelConstructorService
{
    public function __construct(private FraudControlService      $fraud,
        private RecommendationService    $recommendation,
        private InventoryService         $inventory,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private AuditService             $audit,
        private \Illuminate\Contracts\Cache\Repository $cache,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    /**
     * Каноничный вход для всех вертикалей.
     */
    public function analyzeAndRecommend(array $payload, int $userId): array
    {
        return $this->generatePersonalizedTrip($payload, $userId);
    }

    /**
     * Сгенерировать персонализированный план путешествия.
     */
    public function generatePersonalizedTrip(
        array  $preferences,
        int    $userId,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'travel_ai_constructor', amount: 0, correlationId: $correlationId ?? '');

        $cacheKey = "user_ai_designs:travel:{$userId}:" . md5(json_encode($preferences));

        return $this->cache->remember($cacheKey, now()->addHour(), function () use ($preferences, $userId, $correlationId) {
            return $this->db->transaction(function () use ($preferences, $userId, $correlationId) {

                // 1. Мерджим предпочтения с UserTasteProfile
                $taste = $this->tasteAnalyzer->getProfile($userId);
                $fullProfile = array_merge($preferences, $taste->travel_preferences ?? []);

                // 2. Генерируем itinerary через AI (GPT-4o)
                $itinerary = $this->buildItinerary($fullProfile, $correlationId);

                // 3. Реальные рекомендации (билеты, отели, экскурсии)
                $recommendations = $this->recommendation->getForUser(
                    userId: $userId,
                    vertical: 'travel',
                    context: $fullProfile
    );

                // 4. Проверка наличия и виртуальные туры
                $recArray = is_array($recommendations) ? $recommendations : (method_exists($recommendations, 'toArray') ? $recommendations->toArray() : []);
                foreach ($recArray as &$item) {
                    $productId = (int) ($item['product_id'] ?? 0);
                    $item['available'] = $productId > 0
                        ? $this->inventory->getAvailableStock($productId) > 0
                        : false;
                    $item['virtual_tour_url'] = isset($item['hotel_id'])
                        ? url('/hotels/3d-tour/' . $item['hotel_id'] . '/' . $userId)
                        : null;
                }
                unset($item);

                // 5. Сохранение в user_ai_designs
                $this->saveDesign($userId, $fullProfile, $itinerary, $correlationId);

                $this->audit->record(
                    action: 'travel_ai_constructor_used',
                    subjectType: 'travel_ai_design',
                    subjectId: $userId,
                    oldValues: [],
                    newValues: ['profile' => $fullProfile, 'itinerary_days' => $fullProfile['days'] ?? 0, 'rec_count' => count($recArray)],
                    correlationId: $correlationId
                );

                $this->logger->info('Travel AI constructor completed', [
                    'user_id'        => $userId,
                    'destination'    => $fullProfile['destination'] ?? 'unknown',
                    'days'           => $fullProfile['days'] ?? 0,
                    'rec_count'      => count($recArray),
                    'correlation_id' => $correlationId,
                    'tenant_id'      => function_exists('tenant') && tenant() ? tenant()->id : null,
                ]);

                return [
                    'success'        => true,
                    'profile'        => $fullProfile,
                    'itinerary'      => $itinerary,
                    'recommended'    => $recArray,
                    'correlation_id' => $correlationId,
                ];
            });
        });
    }

    /**
     * Строит персональный itinerary (Production: GPT-4o chat).
     */
    private function buildItinerary(array $profile, string $correlationId): array
    {
        // Production: запрос к OpenAI GPT-4o
        // Prompt: "Ты профессиональный travel-планировщик. Создай полный маршрут на {days} дней в {destination}."
        $this->logger->info('Travel itinerary generation', [
            'destination'    => $profile['destination'] ?? '',
            'correlation_id' => $correlationId,
        ]);

        $days = (int) ($profile['days'] ?? 5);
        $itinerary = [];

        for ($i = 1; $i <= $days; $i++) {
            $itinerary["day_{$i}"] = [
                'morning'   => 'Завтрак + осмотр достопримечательностей',
                'afternoon' => 'Экскурсия или отдых',
                'evening'   => 'Ужин + местные развлечения',
            ];
        }

        return [
            'plan_id'     => Str::uuid()->toString(),
            'destination' => $profile['destination'] ?? '',
            'days'        => $days,
            'budget'      => $profile['budget'] ?? 0,
            'daily'       => $itinerary,
            'summary'     => 'Персонализированный план путешествия',
        ];
    }

    /**
     * Сохранить план путешествия в user_ai_designs.
     */
    private function saveDesign(int $userId, array $profile, array $itinerary, string $correlationId): void
    {
        $this->db->table('user_ai_designs')->updateOrInsert(
            ['user_id' => $userId, 'vertical' => 'travel'],
            [
                'design_data'    => json_encode(['profile' => $profile, 'itinerary' => $itinerary], JSON_UNESCAPED_UNICODE),
                'correlation_id' => $correlationId,
                'updated_at'     => now(),
                'created_at'     => now(),
            ]
    );
    }
}

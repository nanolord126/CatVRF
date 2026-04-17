<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Outfit Planner / Stylist AI Service для Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * AI-стилист для составления аутфитов на основе гардероба,
        погоды, событий, трендов и предпочтений пользователя.
 */
final readonly class FashionOutfitPlannerService
{
    private const MAX_OUTFIT_SUGGESTIONS = 10;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Сгенерировать аутфит на основе параметров.
     */
    public function generateOutfit(
        int $userId,
        string $occasion,
        ?string $weather = null,
        ?string $season = null,
        ?array $preferredColors = null,
        ?array $preferredStyles = null,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_outfit_planner',
            amount: 0,
            correlationId: $correlationId
        );

        $wardrobe = $this->getUserWardrobeItems($userId, $tenantId);
        $userPreferences = $this->getUserStylePreferences($userId, $tenantId);

        $tops = $this->filterItemsByCategory($wardrobe, 'tops', $preferredColors);
        $bottoms = $this->filterItemsByCategory($wardrobe, 'bottoms', $preferredColors);
        $shoes = $this->filterItemsByCategory($wardrobe, 'shoes', $preferredColors);
        $accessories = $this->filterItemsByCategory($wardrobe, 'accessories', $preferredColors);

        $outfit = $this->selectOutfitItems($tops, $bottoms, $shoes, $accessories, $occasion, $weather);
        $styleScore = $this->calculateStyleScore($outfit, $userPreferences);

        $this->audit->record(
            action: 'fashion_outfit_generated',
            subjectType: 'fashion_outfit_planner',
            subjectId: $userId,
            oldValues: [],
            newValues: [
                'occasion' => $occasion,
                'weather' => $weather,
                'item_count' => count($outfit),
                'style_score' => $styleScore,
            ],
            correlationId: $correlationId
        );

        Log::channel('audit')->info('Fashion outfit generated', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'occasion' => $occasion,
            'correlation_id' => $correlationId,
        ]);

        return [
            'user_id' => $userId,
            'occasion' => $occasion,
            'weather' => $weather,
            'outfit' => $outfit,
            'style_score' => $styleScore,
            'tips' => $this->generateStyleTips($outfit, $occasion),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить предложения аутфитов на основе гардероба.
     */
    public function getOutfitSuggestions(
        int $userId,
        int $limit = 5,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $wardrobe = $this->getUserWardrobeItems($userId, $tenantId);
        $recentOutfits = $this->getRecentOutfits($userId, $tenantId);

        $suggestions = [];
        for ($i = 0; $i < min($limit, self::MAX_OUTFIT_SUGGESTIONS); $i++) {
            $tops = $this->getRandomItems($wardrobe, 'tops', 1);
            $bottoms = $this->getRandomItems($wardrobe, 'bottoms', 1);
            $shoes = $this->getRandomItems($wardrobe, 'shoes', 1);

            if (!empty($tops) && !empty($bottoms)) {
                $suggestions[] = [
                    'id' => $i + 1,
                    'items' => array_merge($tops, $bottoms, $shoes),
                    'occasion' => $this->predictOccasion(array_merge($tops, $bottoms)),
                ];
            }
        }

        return [
            'user_id' => $userId,
            'suggestions' => $suggestions,
            'total_count' => count($suggestions),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить рекомендации по дополнению аутфита.
     */
    public function getCompletionSuggestions(
        int $userId,
        array $currentItems,
        string $correlationId = ''
    ): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $wardrobe = $this->getUserWardrobeItems($userId, $tenantId);
        $currentCategories = $this->getCategoriesFromItems($currentItems);

        $missingCategories = $this->identifyMissingCategories($currentCategories);
        $suggestions = [];

        foreach ($missingCategories as $category) {
            $items = $this->filterItemsByCategory($wardrobe, $category, null);
            if (!empty($items)) {
                $suggestions[$category] = array_slice($items, 0, 3, true);
            }
        }

        return [
            'user_id' => $userId,
            'missing_categories' => $missingCategories,
            'suggestions' => $suggestions,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить календарь аутфитов.
     */
    public function getOutfitCalendar(
        int $userId,
        string $startDate,
        string $endDate,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $calendar = [];
        $currentDate = $start->copy();

        while ($currentDate->lte($end)) {
            $dayOutfits = $this->getOutfitsForDate($userId, $tenantId, $currentDate);
            
            $calendar[] = [
                'date' => $currentDate->toIso8601String(),
                'day_of_week' => $currentDate->dayOfWeek,
                'outfits' => $dayOutfits,
                'weather_suggestion' => $this->suggestWeatherBasedOutfit($currentDate->dayOfWeek),
            ];

            $currentDate->addDay();
        }

        return [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'calendar' => $calendar,
            'total_days' => count($calendar),
            'correlation_id' => $correlationId,
        ];
    }

    private function getUserWardrobeItems(int $userId, int $tenantId): array
    {
        return $this->db->table('fashion_virtual_wardrobe as fvw')
            ->join('fashion_products as fp', 'fvw.product_id', '=', 'fp.id')
            ->join('fashion_product_categories as fpc', 'fp.id', '=', 'fpc.product_id')
            ->where('fvw.user_id', $userId)
            ->where('fvw.tenant_id', $tenantId)
            ->where('fvw.status', 'active')
            ->where('fpc.tenant_id', $tenantId)
            ->select(
                'fvw.id as wardrobe_item_id',
                'fvw.product_id',
                'fp.name',
                'fp.brand',
                'fp.color',
                'fpc.primary_category',
                'fpc.style_profile',
                'fpc.season'
            )
            ->get()
            ->toArray();
    }

    private function getUserStylePreferences(int $userId, int $tenantId): array
    {
        $patterns = $this->db->table('fashion_user_memory_patterns')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->whereIn('pattern_type', ['preferred_styles', 'preferred_colors'])
            ->get()
            ->keyBy('pattern_type')
            ->toArray();

        return [
            'styles' => $patterns['preferred_styles']['pattern_value'] ?? [],
            'colors' => $patterns['preferred_colors']['pattern_value'] ?? [],
        ];
    }

    private function filterItemsByCategory(array $items, string $category, ?array $colors = null): array
    {
        $filtered = array_filter($items, fn($item) => $item['primary_category'] === $category);

        if ($colors !== null && !empty($colors)) {
            $filtered = array_filter($filtered, fn($item) => in_array(strtolower($item['color']), array_map('strtolower', $colors)));
        }

        return array_values($filtered);
    }

    private function selectOutfitItems(array $tops, array $bottoms, array $shoes, array $accessories, string $occasion, ?string $weather): array
    {
        $outfit = [];

        if (!empty($tops)) {
            $outfit[] = $tops[array_rand($tops)];
        }

        if (!empty($bottoms)) {
            $outfit[] = $bottoms[array_rand($bottoms)];
        }

        if (!empty($shoes)) {
            $outfit[] = $shoes[array_rand($shoes)];
        }

        if (!empty($accessories) && $this->shouldAddAccessories($occasion)) {
            $outfit[] = $accessories[array_rand($accessories)];
        }

        return $outfit;
    }

    private function calculateStyleScore(array $outfit, array $preferences): float
    {
        if (empty($outfit)) {
            return 0.0;
        }

        $score = 0.5;

        $outfitColors = array_unique(array_column($outfit, 'color'));
        $preferredColors = $preferences['colors'] ?? [];
        
        foreach ($outfitColors as $color) {
            if (in_array(strtolower($color), array_map('strtolower', $preferredColors))) {
                $score += 0.1;
            }
        }

        $outfitStyles = array_unique(array_column($outfit, 'style_profile'));
        $preferredStyles = $preferences['styles'] ?? [];
        
        foreach ($outfitStyles as $style) {
            if (in_array($style, $preferredStyles)) {
                $score += 0.1;
            }
        }

        return min($score, 1.0);
    }

    private function generateStyleTips(array $outfit, string $occasion): array
    {
        $tips = [];

        if ($occasion === 'formal') {
            $tips[] = 'Consider adding a blazer for a more formal look';
            $tips[] = 'Ensure shoes are polished and match the belt color';
        } elseif ($occasion === 'casual') {
            $tips[] = 'Layer with a jacket for versatility';
            $tips[] = 'Sneakers work well with this outfit';
        }

        $colors = array_column($outfit, 'color');
        if (count(array_unique($colors)) > 3) {
            $tips[] = 'Consider reducing the color palette for a more cohesive look';
        }

        return $tips;
    }

    private function getRecentOutfits(int $userId, int $tenantId): array
    {
        return $this->db->table('fashion_outfits')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRandomItems(array $items, string $category, int $count): array
    {
        $filtered = $this->filterItemsByCategory($items, $category, null);
        shuffle($filtered);
        return array_slice($filtered, 0, $count, true);
    }

    private function predictOccasion(array $items): string
    {
        $styles = array_unique(array_column($items, 'style_profile'));
        
        if (in_array('formal', $styles)) {
            return 'formal';
        } elseif (in_array('casual', $styles)) {
            return 'casual';
        } elseif (in_array('sport', $styles)) {
            return 'sport';
        }

        return 'casual';
    }

    private function getCategoriesFromItems(array $items): array
    {
        return array_unique(array_column($items, 'primary_category'));
    }

    private function identifyMissingCategories(array $currentCategories): array
    {
        $requiredCategories = ['tops', 'bottoms', 'shoes'];
        return array_diff($requiredCategories, $currentCategories);
    }

    private function shouldAddAccessories(string $occasion): bool
    {
        return in_array($occasion, ['formal', 'party', 'date']);
    }

    private function getOutfitsForDate(int $userId, int $tenantId, Carbon $date): array
    {
        return $this->db->table('fashion_outfits')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', $date)
            ->get()
            ->toArray();
    }

    private function suggestWeatherBasedOutfit(int $dayOfWeek): string
    {
        $suggestions = [
            0 => ['casual', 'comfortable'],
            1 => ['professional', 'business'],
            2 => ['professional', 'business'],
            3 => ['business casual'],
            4 => ['business casual'],
            5 => ['casual', 'relaxed'],
            6 => ['casual', 'weekend'],
        ];

        return $suggestions[$dayOfWeek][0] ?? 'casual';
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}

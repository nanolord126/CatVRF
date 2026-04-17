<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

final readonly class FashionOnlineStylistService
{
    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    public function getStyleConsultation(
        int $userId,
        string $gender,
        string $category,
        ?array $preferences = [],
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(userId: $userId, operationType: 'fashion_stylist', amount: 0, correlationId: $correlationId);

        $recommendations = $this->getGenderBasedRecommendations($userId, $gender, $category, $tenantId, $preferences);
        $styleTips = $this->getStyleTips($gender, $category);
        $trendingItems = $this->getTrendingItems($gender, $category, $tenantId);

        return [
            'user_id' => $userId,
            'gender' => $gender,
            'category' => $category,
            'recommendations' => $recommendations,
            'style_tips' => $styleTips,
            'trending_items' => $trendingItems,
            'correlation_id' => $correlationId,
        ];
    }

    public function getMensStyle(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'men', 'all', [], $correlationId);
    }

    public function getWomensStyle(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'women', 'all', [], $correlationId);
    }

    public function getWomensUnderwear(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'women', 'underwear', [], $correlationId);
    }

    public function getMensShoes(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'men', 'shoes', [], $correlationId);
    }

    public function getWomensShoes(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'women', 'shoes', [], $correlationId);
    }

    public function getChildrensClothing(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'children', 'clothing', [], $correlationId);
    }

    public function getChildrensShoes(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'children', 'shoes', [], $correlationId);
    }

    public function getScarvesAndAccessories(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'unisex', 'scarves', [], $correlationId);
    }

    public function getHeadwear(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'unisex', 'headwear', [], $correlationId);
    }

    public function getCareProducts(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'unisex', 'care_products', [], $correlationId);
    }

    public function getUmbrellas(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'unisex', 'umbrellas', [], $correlationId);
    }

    public function getMensAccessories(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'men', 'accessories', [], $correlationId);
    }

    public function getWomensAccessories(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        return $this->getStyleConsultation($userId, 'women', 'accessories', [], $correlationId);
    }

    private function getGenderBasedRecommendations(int $userId, string $gender, string $category, int $tenantId, array $preferences): array
    {
        $categories = $this->getCategoryMapping($gender, $category);
        
        $query = $this->db->table('fashion_products as fp')
            ->where('fp.tenant_id', $tenantId)
            ->where('fp.status', 'active')
            ->where('fp.stock_quantity', '>', 0)
            ->where('fp.gender', $gender);

        if ($categories !== 'all') {
            $query->whereExists(function ($q) use ($categories, $tenantId) {
                $q->select(DB::raw(1))
                    ->from('fashion_product_categories')
                    ->whereColumn('fashion_product_categories.product_id', 'fp.id')
                    ->where('fashion_product_categories.tenant_id', $tenantId)
                    ->whereIn('primary_category', (array) $categories);
            });
        }

        if (!empty($preferences['price_min'])) {
            $query->where('fp.price_b2c', '>=', $preferences['price_min']);
        }
        if (!empty($preferences['price_max'])) {
            $query->where('fp.price_b2c', '<=', $preferences['price_max']);
        }

        return $query->limit(20)->select('fp.*')->get()->toArray();
    }

    private function getCategoryMapping(string $gender, string $category): array|string
    {
        if ($category === 'all') return 'all';

        return match (true) {
            $gender === 'men' && $category === 'shoes' => ['shoes', 'sneakers', 'boots', 'formal_shoes', 'loafers'],
            $gender === 'women' && $category === 'shoes' => ['shoes', 'heels', 'sneakers', 'boots', 'sandals', 'flats'],
            $gender === 'women' && $category === 'underwear' => ['underwear', 'lingerie', 'bras', 'panties', 'bodysuits'],
            $gender === 'children' && $category === 'clothing' => ['tops', 'bottoms', 'dresses', 'jackets', 'sportswear', 'suits'],
            $gender === 'children' && $category === 'shoes' => ['shoes', 'sneakers', 'boots', 'sandals'],
            $gender === 'unisex' && $category === 'scarves' => ['scarves', 'shawls', 'wraps', 'neck_warmers'],
            $gender === 'unisex' && $category === 'headwear' => ['hats', 'caps', 'beanies', 'berets', 'headbands', 'turbans'],
            $gender === 'unisex' && $category === 'care_products' => ['fabric_care', 'leather_care', 'shoe_care', 'detergents', 'stain_removers'],
            $gender === 'unisex' && $category === 'umbrellas' => ['umbrellas', 'parasols', 'rain_gear'],
            $gender === 'men' && $category === 'accessories' => ['belts', 'ties', 'bowties', 'cufflinks', 'wallets', 'bags', 'scarves', 'hats', 'gloves'],
            $gender === 'women' && $category === 'accessories' => ['belts', 'handbags', 'clutches', 'jewelry', 'scarves', 'hats', 'gloves', 'hair_accessories'],
            default => [$category],
        };
    }

    private function getStyleTips(string $gender, string $category): array
    {
        return match (true) {
            $gender === 'men' => [
                'Fit is everything - ensure clothes fit well at shoulders and chest',
                'Classic colors (navy, gray, black) are versatile',
                'Quality shoes complete any outfit',
                'Invest in good basics: white shirts, dark jeans, blazer',
                'Accessories like watches and belts elevate your look',
            ],
            $gender === 'women' && $category === 'underwear' => [
                'Comfort should never be compromised',
                'Seamless options work under any outfit',
                'Match underwear to your outfit color',
                'Proper sizing is crucial for comfort and fit',
            ],
            $gender === 'women' => [
                'Know your body type and dress accordingly',
                'Accessories can transform a simple outfit',
                'Neutral basics allow statement pieces to shine',
                'Invest in quality over quantity',
                'Handbags and jewelry are powerful style statements',
            ],
            $gender === 'children' => [
                'Comfort and durability are priorities',
                'Easy-to-wear fabrics for active kids',
                'Buy slightly larger for growing room',
                'Bright colors and fun patterns appeal to kids',
            ],
            $gender === 'unisex' && $category === 'scarves' => [
                'Scarves add instant sophistication to any outfit',
                'Silk scarves elevate formal looks, wool for casual',
                'Experiment with different tying techniques',
                'Neutral colors work year-round, bold colors make statements',
            ],
            $gender === 'unisex' && $category === 'headwear' => [
                'Hats can frame your face and complete a look',
                'Consider face shape when choosing hat styles',
                'Beanies are versatile for casual looks',
                'Wide-brimmed hats provide sun protection and style',
            ],
            $gender === 'unisex' && $category === 'care_products' => [
                'Proper care extends garment life significantly',
                'Follow care labels religiously',
                'Invest in quality hangers to maintain shape',
                'Regular cleaning prevents permanent stains',
            ],
            $gender === 'unisex' && $category === 'umbrellas' => [
                'A quality umbrella is both functional and stylish',
                'Classic black umbrellas match everything',
                'Compact umbrellas are perfect for daily carry',
                'Consider automatic open/close for convenience',
            ],
            $gender === 'men' && $category === 'accessories' => [
                'Less is more - choose one statement piece',
                'Match belt and shoe colors for cohesion',
                'Watches should complement your lifestyle',
                'Ties should contrast but not clash with shirts',
            ],
            $gender === 'women' && $category === 'accessories' => [
                'Mix metals for a modern, eclectic look',
                'Statement jewelry can elevate simple outfits',
                'Bags should balance your body proportions',
                'Scarves add versatility to your wardrobe',
            ],
            default => ['Focus on comfort and personal style'],
        };
    }

    private function getTrendingItems(string $gender, string $category, int $tenantId): array
    {
        $categories = $this->getCategoryMapping($gender, $category);
        
        return $this->db->table('fashion_trend_scores as fts')
            ->join('fashion_products as fp', 'fts.product_id', '=', 'fp.id')
            ->where('fp.tenant_id', $tenantId)
            ->where('fp.gender', $gender)
            ->where('fp.status', 'active')
            ->where('fts.trend_score', '>', 0.5)
            ->orderBy('fts.trend_score', 'desc')
            ->limit(10)
            ->select('fp.*', 'fts.trend_score')
            ->get()
            ->toArray();
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}

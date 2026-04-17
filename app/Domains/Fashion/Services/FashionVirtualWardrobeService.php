<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Virtual Wardrobe / Digital Closet Service для Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * Цифровой гардероб пользователя: управление вещами,
        организация по категориям, стилям, сезонам,
        создание аутфитов, статистика носки.
 */
final readonly class FashionVirtualWardrobeService
{
    private const MAX_WARDROBE_ITEMS = 500;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Добавить вещь в цифровой гардероб.
     */
    public function addToWardrobe(
        int $userId,
        int $productId,
        ?array $customTags = [],
        ?string $purchaseDate = null,
        ?string $purchasePrice = null,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_wardrobe_add',
            amount: (int) ($purchasePrice ?? 0),
            correlationId: $correlationId
        );

        $itemCount = $this->db->table('fashion_virtual_wardrobe')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->count();

        if ($itemCount >= self::MAX_WARDROBE_ITEMS) {
            throw new \RuntimeException('Wardrobe capacity reached', 400);
        }

        $wardrobeItemId = $this->db->table('fashion_virtual_wardrobe')->insertGetId([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'custom_tags' => json_encode($customTags),
            'purchase_date' => $purchaseDate ? Carbon::parse($purchaseDate) : null,
            'purchase_price' => $purchasePrice,
            'times_worn' => 0,
            'last_worn_at' => null,
            'is_favorite' => false,
            'status' => 'active',
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->audit->record(
            action: 'fashion_wardrobe_item_added',
            subjectType: 'fashion_virtual_wardrobe',
            subjectId: $wardrobeItemId,
            oldValues: [],
            newValues: [
                'user_id' => $userId,
                'product_id' => $productId,
                'purchase_price' => $purchasePrice,
            ],
            correlationId: $correlationId
        );

        Log::channel('audit')->info('Fashion wardrobe item added', [
            'wardrobe_item_id' => $wardrobeItemId,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return [
            'wardrobe_item_id' => $wardrobeItemId,
            'user_id' => $userId,
            'product_id' => $productId,
            'status' => 'active',
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить гардероб пользователя.
     */
    public function getUserWardrobe(
        int $userId,
        ?array $filters = [],
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $query = $this->db->table('fashion_virtual_wardrobe as fvw')
            ->join('fashion_products as fp', 'fvw.product_id', '=', 'fp.id')
            ->where('fvw.user_id', $userId)
            ->where('fvw.tenant_id', $tenantId)
            ->where('fvw.status', 'active')
            ->select(
                'fvw.id as wardrobe_item_id',
                'fvw.product_id',
                'fp.name',
                'fp.brand',
                'fp.color',
                'fp.price_b2c',
                'fvw.custom_tags',
                'fvw.purchase_date',
                'fvw.purchase_price',
                'fvw.times_worn',
                'fvw.last_worn_at',
                'fvw.is_favorite',
                'fvw.created_at'
            );

        if (!empty($filters['category'])) {
            $query->whereExists(function ($q) use ($filters, $tenantId) {
                $q->select(DB::raw(1))
                    ->from('fashion_product_categories')
                    ->whereColumn('fashion_product_categories.product_id', 'fvw.product_id')
                    ->where('fashion_product_categories.tenant_id', $tenantId)
                    ->where('primary_category', $filters['category']);
            });
        }

        if (!empty($filters['color'])) {
            $query->where('fp.color', $filters['color']);
        }

        if (!empty($filters['brand'])) {
            $query->where('fp.brand', $filters['brand']);
        }

        if (!empty($filters['is_favorite'])) {
            $query->where('fvw.is_favorite', true);
        }

        if (!empty($filters['season'])) {
            $query->whereExists(function ($q) use ($filters, $tenantId) {
                $q->select(DB::raw(1))
                    ->from('fashion_product_categories')
                    ->whereColumn('fashion_product_categories.product_id', 'fvw.product_id')
                    ->where('fashion_product_categories.tenant_id', $tenantId)
                    ->where('season', $filters['season']);
            });
        }

        $items = $query->orderBy('fvw.created_at', 'desc')->get()->toArray();

        return [
            'user_id' => $userId,
            'total_items' => count($items),
            'items' => $items,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Записать ношку вещи.
     */
    public function recordWear(int $wardrobeItemId, int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $item = $this->db->table('fashion_virtual_wardrobe')
            ->where('id', $wardrobeItemId)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($item === null) {
            throw new \InvalidArgumentException('Wardrobe item not found', 404);
        }

        $this->db->table('fashion_virtual_wardrobe')
            ->where('id', $wardrobeItemId)
            ->increment('times_worn');

        $this->db->table('fashion_virtual_wardrobe')
            ->where('id', $wardrobeItemId)
            ->update(['last_worn_at' => Carbon::now()]);

        $this->db->table('fashion_wear_history')->insert([
            'wardrobe_item_id' => $wardrobeItemId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'worn_at' => Carbon::now(),
            'correlation_id' => $correlationId,
        ]);

        return [
            'wardrobe_item_id' => $wardrobeItemId,
            'recorded' => true,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать аутфит.
     */
    public function createOutfit(
        int $userId,
        string $name,
        array $wardrobeItemIds,
        ?string $occasion = null,
        ?string $season = null,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        foreach ($wardrobeItemIds as $itemId) {
            $exists = $this->db->table('fashion_virtual_wardrobe')
                ->where('id', $itemId)
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->exists();

            if (!$exists) {
                throw new \InvalidArgumentException('Wardrobe item not found', 404);
            }
        }

        $outfitId = $this->db->table('fashion_outfits')->insertGetId([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'name' => $name,
            'occasion' => $occasion,
            'season' => $season,
            'is_favorite' => false,
            'times_worn' => 0,
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        foreach ($wardrobeItemIds as $itemId) {
            $this->db->table('fashion_outfit_items')->insert([
                'outfit_id' => $outfitId,
                'tenant_id' => $tenantId,
                'wardrobe_item_id' => $itemId,
                'correlation_id' => $correlationId,
            ]);
        }

        $this->audit->record(
            action: 'fashion_outfit_created',
            subjectType: 'fashion_outfit',
            subjectId: $outfitId,
            oldValues: [],
            newValues: [
                'user_id' => $userId,
                'name' => $name,
                'item_count' => count($wardrobeItemIds),
            ],
            correlationId: $correlationId
        );

        return [
            'outfit_id' => $outfitId,
            'user_id' => $userId,
            'name' => $name,
            'item_count' => count($wardrobeItemIds),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить статистику гардероба.
     */
    public function getWardrobeStats(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $totalItems = $this->db->table('fashion_virtual_wardrobe')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        $totalValue = $this->db->table('fashion_virtual_wardrobe')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->sum('purchase_price') ?? 0;

        $mostWorn = $this->db->table('fashion_virtual_wardrobe as fvw')
            ->join('fashion_products as fp', 'fvw.product_id', '=', 'fp.id')
            ->where('fvw.user_id', $userId)
            ->where('fvw.tenant_id', $tenantId)
            ->orderBy('fvw.times_worn', 'desc')
            ->limit(5)
            ->select('fp.name', 'fp.brand', 'fvw.times_worn')
            ->get()
            ->toArray();

        $leastWorn = $this->db->table('fashion_virtual_wardrobe as fvw')
            ->join('fashion_products as fp', 'fvw.product_id', '=', 'fp.id')
            ->where('fvw.user_id', $userId)
            ->where('fvw.tenant_id', $tenantId)
            ->where('fvw.times_worn', '>', 0)
            ->orderBy('fvw.times_worn', 'asc')
            ->limit(5)
            ->select('fp.name', 'fp.brand', 'fvw.times_worn')
            ->get()
            ->toArray();

        $neverWorn = $this->db->table('fashion_virtual_wardrobe as fvw')
            ->join('fashion_products as fp', 'fvw.product_id', '=', 'fp.id')
            ->where('fvw.user_id', $userId)
            ->where('fvw.tenant_id', $tenantId)
            ->where('fvw.times_worn', 0)
            ->count();

        $byCategory = $this->db->table('fashion_virtual_wardrobe as fvw')
            ->join('fashion_product_categories as fpc', 'fvw.product_id', '=', 'fpc.product_id')
            ->where('fvw.user_id', $userId)
            ->where('fvw.tenant_id', $tenantId)
            ->where('fvw.status', 'active')
            ->where('fpc.tenant_id', $tenantId)
            ->selectRaw('fpc.primary_category, COUNT(*) as count')
            ->groupBy('fpc.primary_category')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        return [
            'user_id' => $userId,
            'total_items' => $totalItems,
            'total_value' => $totalValue,
            'never_worn_count' => $neverWorn,
            'most_worn' => $mostWorn,
            'least_worn' => $leastWorn,
            'by_category' => $byCategory,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить рекомендации на основе гардероба.
     */
    public function getWardrobeRecommendations(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $wardrobe = $this->getUserWardrobe($userId, [], $correlationId);
        $userColors = array_unique(array_column($wardrobe['items'], 'color'));
        $userBrands = array_unique(array_column($wardrobe['items'], 'brand'));

        $recommendations = $this->db->table('fashion_products as fp')
            ->where('fp.tenant_id', $tenantId)
            ->where('fp.status', 'active')
            ->where('fp.stock_quantity', '>', 0)
            ->whereIn('fp.color', $userColors)
            ->whereNotIn('fp.id', array_column($wardrobe['items'], 'product_id'))
            ->limit(20)
            ->select('fp.*')
            ->get()
            ->toArray();

        return [
            'user_id' => $userId,
            'recommendations' => $recommendations,
            'total_count' => count($recommendations),
            'reason' => 'Based on your wardrobe colors and brands',
            'correlation_id' => $correlationId,
        ];
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}

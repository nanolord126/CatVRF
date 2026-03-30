<?php declare(strict_types=1);

namespace App\Domains\Food\Beverages\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeverageService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param FraudControlService $fraudService
         */
        public function __construct(
            private FraudControlService $fraudService
        ) {}

        /**
         * Register a new beverage shop (coffee shop, bar, etc.)
         *
         * @param array $data
         * @param string|null $correlationId
         * @return BeverageShop
         * @throws Exception
         */
        public function createShop(array $data, ?string $correlationId = null): BeverageShop
        {
            $correlationId = $correlationId ?? (string) Str::uuid();

            Log::channel('audit')->info('Initializing shop creation', [
                'correlation_id' => $correlationId,
                'name' => $data['name'],
                'type' => $data['type'],
            ]);

            return DB::transaction(function () use ($data, $correlationId) {
                // 1. Mandatory Fraud Check
                $this->fraudService->check('beverage_shop_create', [
                    'tenant_id' => $data['tenant_id'],
                    'user_id' => auth()->id() ?? 0,
                    'correlation_id' => $correlationId,
                ]);

                // 2. Create the shop
                $shop = BeverageShop::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $data['tenant_id'],
                    'business_group_id' => $data['business_group_id'] ?? null,
                    'correlation_id' => $correlationId,
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'address' => $data['address'],
                    'geo_point' => $data['geo_point'] ?? null,
                    'schedule' => $data['schedule'] ?? [],
                    'is_active' => $data['is_active'] ?? true,
                    'tags' => $data['tags'] ?? [],
                ]);

                // 3. Optional: Create initial categories
                if (!empty($data['initial_categories'])) {
                    foreach ($data['initial_categories'] as $categoryName) {
                        BeverageCategory::create([
                            'shop_id' => $shop->id,
                            'tenant_id' => $shop->tenant_id,
                            'name' => $categoryName,
                            'correlation_id' => $correlationId,
                        ]);
                    }
                }

                Log::channel('audit')->info('Beverage shop successfully registered', [
                    'shop_id' => $shop->id,
                    'uuid' => $shop->uuid,
                    'correlation_id' => $correlationId,
                ]);

                return $shop;
            });
        }

        /**
         * Add new drink item to a shop's menu.
         */
        public function addMenuItem(int $shopId, array $data, ?string $correlationId = null): BeverageItem
        {
            $correlationId = $correlationId ?? (string) Str::uuid();

            return DB::transaction(function () use ($shopId, $data, $correlationId) {
                $shop = BeverageShop::findOrFail($shopId);

                $item = BeverageItem::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $shop->tenant_id,
                    'shop_id' => $shop->id,
                    'category_id' => $data['category_id'],
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'price' => $data['price'],
                    'volume_ml' => $data['volume_ml'],
                    'ingredients' => $data['ingredients'] ?? [],
                    'allergens' => $data['allergens'] ?? [],
                    'nutritional_value' => $data['nutritional_value'] ?? [],
                    'stock_count' => $data['stock_count'] ?? 0,
                    'freshness_control_type' => $data['freshness_control_type'] ?? 'none',
                    'shelf_life_hours' => $data['shelf_life_hours'] ?? null,
                    'is_available' => $data['is_available'] ?? true,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Menu item added', [
                    'shop_id' => $shopId,
                    'item_id' => $item->id,
                    'correlation_id' => $correlationId,
                ]);

                return $item;
            });
        }

        /**
         * Get menu for specific shop grouped by categories.
         */
        public function getShopMenu(int $shopId): Collection
        {
            return BeverageCategory::where('shop_id', $shopId)
                ->with(['items' => function ($query) {
                    $query->where('is_available', true);
                }])
                ->orderBy('sort_order', 'asc')
                ->get();
        }
}

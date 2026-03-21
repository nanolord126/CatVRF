<?php declare(strict_types=1);

namespace Modules\Food\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Food\Models\Restaurant;
use Modules\Food\Models\RestaurantOrder;
use Modules\Inventory\Services\InventoryManagementService;
use Illuminate\Support\Str;

/**
 * Restaurant Management Service
 * CANON 2026 - Production Ready
 */
final class RestaurantService
{
    public function __construct(
        private readonly InventoryManagementService $inventoryService,
    ) {}

    public function createRestaurant(array $data, int $tenantId, string $correlationId): Restaurant
    {
        return DB::transaction(function () use ($data, $tenantId, $correlationId) {
            Log::channel('audit')->info('Creating restaurant', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ]);

            return Restaurant::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'address' => $data['address'],
                'geo_point' => $data['geo_point'] ?? null,
                'cuisine_type' => json_encode($data['cuisine_type'] ?? []),
                'schedule_json' => json_encode($data['schedule'] ?? []),
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function createOrder(array $data, int $restaurantId, int $userId, string $correlationId): RestaurantOrder
    {
        return DB::transaction(function () use ($data, $restaurantId, $userId, $correlationId) {
            Log::channel('audit')->info('Creating restaurant order', [
                'correlation_id' => $correlationId,
                'restaurant_id' => $restaurantId,
                'user_id' => $userId,
            ]);

            return RestaurantOrder::create([
                'restaurant_id' => $restaurantId,
                'user_id' => $userId,
                'items_json' => json_encode($data['items'] ?? []),
                'total_price' => $data['total_price'],
                'status' => 'pending',
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function getRestaurantStats(Restaurant $restaurant): array
    {
        $totalOrders = RestaurantOrder::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 'completed')
            ->count();

        $totalRevenue = RestaurantOrder::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 'completed')
            ->sum('total_price');

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'rating' => $restaurant->rating ?? 0,
        ];
    }
}

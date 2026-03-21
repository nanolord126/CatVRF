<?php declare(strict_types=1);

namespace App\Domains\FashionRetail\Services;

use App\Domains\FashionRetail\Models\FashionRetailOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class OrderService
{
    public function getUserOrders(int $userId): Collection
    {
        return FashionRetailOrder::where('user_id', $userId)
            ->with('shop', 'returns')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getShopOrders(int $shopId): Collection
    {
        return FashionRetailOrder::where('shop_id', $shopId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByStatus(string $status): Collection
    {
        return FashionRetailOrder::where('status', $status)
            ->with('shop', 'user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function calculateTotal(array $items): float
    {
        return collect($items)->sum(function ($item) {
            return ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        });
    }

    public function calculateCommission(float $total): float
    {
        return $total * 0.15; // 15% комиссия для FashionRetail
    }

    public function updateStatus(int $orderId, string $status, string $correlationId): void
    {
        DB::transaction(function () use ($orderId, $status, $correlationId) {
            $order = FashionRetailOrder::lockForUpdate()->findOrFail($orderId);

            $order->update([
                'status' => $status,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('FashionRetail order status updated', [
                'order_id' => $orderId,
                'status' => $status,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function cancelOrder(int $orderId, string $correlationId): void
    {
        DB::transaction(function () use ($orderId, $correlationId) {
            $order = FashionRetailOrder::lockForUpdate()->findOrFail($orderId);

            if (in_array($order->status, ['pending', 'confirmed'])) {
                $order->update([
                    'status' => 'cancelled',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('FashionRetail order cancelled', [
                    'order_id' => $orderId,
                    'correlation_id' => $correlationId,
                ]);
            }
        });
    }
}

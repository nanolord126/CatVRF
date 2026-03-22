<?php declare(strict_types=1);

namespace App\Domains\FashionRetail\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;


use App\Domains\FashionRetail\Models\FashionRetailOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class OrderService
{
    public function getUserOrders(int $userId): Collection
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        return FashionRetailOrder::where('user_id', $userId)
            ->with('shop', 'returns')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getShopOrders(int $shopId): Collection
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        return FashionRetailOrder::where('shop_id', $shopId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByStatus(string $status): Collection
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        return FashionRetailOrder::where('status', $status)
            ->with('shop', 'user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function calculateTotal(array $items): float
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        return collect($items)->sum(function ($item) {
            return ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        });
    }

    public function calculateCommission(float $total): float
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        return $total * 0.15; // 15% комиссия для FashionRetail
    }

    public function updateStatus(int $orderId, string $status, string $correlationId): void
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

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
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

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

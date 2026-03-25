<?php declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;
use Illuminate\Support\Str;


use App\Domains\Flowers\Events\B2BFlowerOrderPlaced;
use App\Domains\Flowers\Models\B2BFlowerOrder;
use App\Domains\Flowers\Models\B2BFlowerStorefront;

use Illuminate\Support\Facades\DB;

final class B2BFlowerOrderService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {
        $correlationId = Str::uuid()->toString();
        $this->log->channel('audit')->info('Service method called in Flowers', ['correlation_id' => $correlationId]);
}

    public function createB2BOrder(
        int $tenantId,
        int $storefrontId,
        array $items,
        array $deliveryData,
        string $correlationId = '',
    ): B2BFlowerOrder {
        $correlationId = $correlationId ?: (string)Str::uuid()->toString();

        try {
            $this->fraudControlService->check(
                tenantId: $tenantId,
                action: 'b2b_flower_order_create',
                correlationId: $correlationId,
            );

            return $this->db->transaction(function () use ($tenantId, $storefrontId, $items, $deliveryData, $correlationId) {
                $storefront = B2BFlowerStorefront::query()
                    ->where('id', $storefrontId)
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                $subtotal = 0;
                foreach ($items as $item) {
                    $subtotal += $item['total_price'];
                }

                $bulkDiscount = $this->calculateBulkDiscount($storefront, $subtotal);
                $commissionAmount = ($subtotal - $bulkDiscount) * 0.14;
                $totalAmount = $subtotal - $bulkDiscount;

                $order = B2BFlowerOrder::query()->create([
                    'tenant_id' => $tenantId,
                    'storefront_id' => $storefrontId,
                    'shop_id' => $storefront->shop_id,
                    'order_number' => $this->generateOrderNumber(),
                    'subtotal' => $subtotal,
                    'bulk_discount' => $bulkDiscount,
                    'commission_amount' => $commissionAmount,
                    'total_amount' => $totalAmount,
                    'delivery_address' => $deliveryData['delivery_address'],
                    'delivery_date' => $deliveryData['delivery_date'],
                    'status' => 'draft',
                    'payment_status' => 'pending',
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('B2B flower order created', [
                    'order_id' => $order->id,
                    'storefront_id' => $storefrontId,
                    'total_amount' => $totalAmount,
                    'commission_amount' => $commissionAmount,
                    'correlation_id' => $correlationId,
                ]);

                B2BFlowerOrderPlaced::dispatch($order, $correlationId);

                return $order;
            });
        } catch (\Exception $exception) {
            $this->log->channel('audit')->error('B2B flower order creation failed', [
                'storefront_id' => $storefrontId,
                'error' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $exception;
        }
    }

    private function calculateBulkDiscount(B2BFlowerStorefront $storefront, float $subtotal): float
    {
        if (!$storefront->bulk_discounts) {
            return 0;
        }

        $discounts = json_decode(json_encode($storefront->bulk_discounts), true);
        $discount = 0;

        foreach ($discounts as $qty => $percent) {
            if ($subtotal >= (int)$qty) {
                $discount = ($subtotal * $percent) / 100;
            }
        }

        return $discount;
    }

    private function generateOrderNumber(): string
    {
        return 'B2BFLO-' . date('Ymd') . '-' . Str::upper(Str::random(8));
    }
}

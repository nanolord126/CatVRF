<?php

namespace App\Services\B2B;

use App\Models\B2BManufacturer;
use App\Models\B2BProduct;
use App\Models\B2BBulkOrder;
use App\Models\B2BBulkOrderItem;
use App\Models\WholesaleContract;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Bridge service for B2B Procurement between Tenants and Manufacturers.
 */
class B2BProcurementService
{
    /**
     * Create a bulk order for a tenant.
     */
    public function createBulkOrder(string $tenantId, int $manufacturerId, array $items): B2BBulkOrder
    {
        return DB::transaction(function () use ($tenantId, $manufacturerId, $items) {
            $manufacturer = B2BManufacturer::findOrFail($manufacturerId);
            
            // Check for active contract
            $contract = WholesaleContract::where('manufacturer_id', $manufacturerId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->first();

            $totalAmount = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $product = B2BProduct::findOrFail($item['product_id']);
                
                // Calculate price based on AI scoring and contracts
                $unitPrice = $product->base_wholesale_price;
                
                if ($contract && $contract->special_discount_percent > 0) {
                    $unitPrice *= (1 - ($contract->special_discount_percent / 100));
                }

                $subtotal = $unitPrice * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'correlation_id' => (string) Str::uuid(),
                ];
            }

            // Platform commission (e.g. 5%)
            $commission = $totalAmount * 0.05;

            $order = B2BBulkOrder::create([
                'manufacturer_id' => $manufacturerId,
                'tenant_id' => $tenantId,
                'contract_id' => $contract?->id,
                'total_amount' => $totalAmount,
                'commission_amount' => $commission,
                'status' => 'pending',
                'payment_status' => $contract && $contract->deferred_payment_days > 0 ? 'deferred' : 'unpaid',
                'correlation_id' => (string) Str::uuid(),
            ]);

            foreach ($orderItems as $orderItem) {
                $orderItem['order_id'] = $order->id;
                B2BBulkOrderItem::create($orderItem);
            }

            return $order;
        });
    }

    /**
     * Recommend suppliers using AI logic.
     */
    public function getRecommendedSuppliers(string $category): array
    {
        return B2BManufacturer::where('category', $category)
            ->where('is_active', true)
            ->orderByDesc('ai_trust_score')
            ->limit(5)
            ->get()
            ->toArray();
    }
}

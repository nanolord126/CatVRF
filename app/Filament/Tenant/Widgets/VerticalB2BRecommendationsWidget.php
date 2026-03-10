<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\Widget;
use App\Services\Common\AI\RecommendationServiceVertical;
use App\Models\B2BProduct;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class VerticalB2BRecommendationsWidget extends Widget
{
    protected static string $view = 'filament.tenant.widgets.vertical-b2-b-recommendations-widget';

    protected int | string | array $columnSpan = 'full';

    public $recommendations = [];
    public $crossRecommendations = [];

    public function mount(RecommendationServiceVertical $service)
    {
        $tenant = auth()->user()->currentTenant ?? tenant();
        $vertical = $tenant->vertical_type ?? 'General';

        $this->recommendations = $service->forVertical($tenant, $vertical, 4);
        $this->crossRecommendations = $service->crossVerticalRecommendations($tenant);
    }

    /**
     * Smart Purchase Logic: 1-click order + invoice generation.
     */
    public function smartPurchase(int $productId)
    {
        $product = B2BProduct::find($productId);
        $tenant = tenant();

        if (!$product) {
            Notification::make()->danger()->title('Product not found')->send();
            return;
        }

        try {
            DB::transaction(function () use ($product, $tenant) {
                // 1. Create B2B Order
                $order = \App\Models\B2BBulkOrder::create([
                    'tenant_id' => $tenant->id,
                    'manufacturer_id' => $product->manufacturer_id,
                    'status' => 'pending',
                    'total_amount' => $product->base_wholesale_price * $product->min_order_quantity,
                    'commission_amount' => 0, // Simplified for now
                    'correlation_id' => bin2hex(random_bytes(16)),
                ]);

                \App\Models\B2BBulkOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $product->min_order_quantity,
                    'unit_price' => $product->base_wholesale_price,
                    'subtotal' => $product->base_wholesale_price * $product->min_order_quantity,
                    'correlation_id' => $order->correlation_id,
                ]);

                // 2. Generate Invoice
                \App\Models\B2BInvoice::create([
                    'b2_b_order_id' => $order->id,
                    'amount' => $order->total_amount,
                    'due_date' => now()->addDays(7),
                    'status' => 'unpaid',
                    'correlation_id' => $order->correlation_id,
                ]);
            });

            Notification::make()
                ->success()
                ->title('Smart Purchase Successful')
                ->body("Order for {$product->name} has been placed and invoice generated.")
                ->send();

        } catch (\Exception $e) {
            Log::error("SmartPurchase Error: " . $e->getMessage());
            Notification::make()->danger()->title('Purchase failed')->send();
        }
    }
}

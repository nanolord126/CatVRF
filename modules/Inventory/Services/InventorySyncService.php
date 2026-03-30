<?php declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InventorySyncService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        /**
         * Списание товара со склада при продаже.
         * 
         * @param int|string $productId
         * @param int|float $quantity
         * @param string $referenceType (e.g., 'Order')
         * @param string $referenceId (e.g., order_id)
         */
        public function deductStock($productId, $quantity, string $referenceType, string $referenceId): void
        {
            $correlationId = request()->header('X-Correlation-ID', bin2hex(random_bytes(16)));
    
            DB::transaction(function () use ($productId, $quantity, $referenceType, $referenceId, $correlationId) {
                $product = Product::findOrFail($productId);
    
                // Атомарное уменьшение остатка (database level)
                $product->decrement('stock', $quantity);
    
                // Регистрация движения (Audit Log + Traceability)
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'reason' => "Sale via {$referenceType} #{$referenceId}",
                    'correlation_id' => $correlationId,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'status' => 'completed',
                    'is_approved' => true
                ]);
    
                // Low stock check (Канон 2026: Уведомления)
                if ($product->stock <= $product->min_stock) {
                    Log::warning("Inventory Alert: Product '{$product->name}' (SKU: {$product->sku}) is below minimum threshold.", [
                        'current_stock' => $product->stock,
                        'min_stock' => $product->min_stock,
                        'tenant_id' => tenant('id')
                    ]);
                }
            });
        }
}

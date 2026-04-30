<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Inventory\Exceptions\ShelfLifeException;
use App\Domains\Inventory\Models\InventoryItemWithExpiry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to block requests attempting to use expired items.
 *
 * This middleware should be applied to routes that involve:
 * - Sales/cashier operations
 * - Veterinary prescriptions
 * - Kitchen usage
 * - Grooming operations
 *
 * Usage in routes:
 * Route::middleware(['require.valid.expiry'])->group(function () {
 *     Route::post('/sales', [SaleController::class, 'store']);
 *     Route::post('/prescriptions', [PrescriptionController::class, 'store']);
 * });
 */
final class RequireValidExpiryMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if request contains inventory_item_id
        $itemId = $request->input('inventory_item_id') 
            ?? $request->input('item_id') 
            ?? $request->input('product_id');

        if ($itemId === null) {
            // No item ID in request, proceed
            return $next($request);
        }

        try {
            $item = InventoryItemWithExpiry::findOrFail($itemId);

            // Check if expired
            if ($item->isExpired()) {
                Log::warning('Attempt to use expired item', [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'expiry_date' => $item->expiry_date->format('Y-m-d'),
                    'user_id' => $request->user()?->id,
                    'route' => $request->route()?->getName(),
                ]);

                throw ShelfLifeException::expiredItem(
                    itemName: $item->name,
                    expiryDate: $item->expiry_date->format('Y-m-d'),
                    itemId: $item->id
                );
            }

            // Check if controlled item without expiry
            if ($item->is_controlled && $item->expiry_date === null) {
                Log::warning('Attempt to use controlled item without expiry date', [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'user_id' => $request->user()?->id,
                    'route' => $request->route()?->getName(),
                ]);

                throw ShelfLifeException::missingExpiryDate(
                    itemName: $item->name,
                    itemId: $item->id
                );
            }
        } catch (ShelfLifeException $e) {
            return response()->json([
                'error' => 'shelf_life_violation',
                'message' => $e->getMessage(),
                'context' => $e->context(),
            ], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Item not found, let the controller handle it
            return $next($request);
        }

        return $next($request);
    }
}

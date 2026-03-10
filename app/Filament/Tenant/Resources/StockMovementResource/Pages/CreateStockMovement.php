<?php

namespace App\Filament\Tenant\Resources\StockMovementResource\Pages;

use App\Filament\Tenant\Resources\StockMovementResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Product;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function afterCreate(): void
    {
        $movement = $this->record;
        $product = Product::find($movement->product_id);

        if (!$product) return;

        DB::transaction(function () use ($product, $movement) {
            if ($movement->type === 'in') {
                $product->increment('stock', $movement->quantity);
            } elseif ($movement->type === 'out') {
                $product->decrement('stock', $movement->quantity);
            } elseif ($movement->type === 'adjustment') {
                // For adjustments, we might assume quantity is the new balance 
                // but usually adjustment is "add/subtract to correct". 
                // Let's assume it's additive/subtractive based on negative/positive quantity 
                // for simplicity or a separate field. Here we subtract it to be safe (out).
                $product->increment('stock', $movement->quantity); // User specifies + for increase, - for decrease
            }
        });
    }
}

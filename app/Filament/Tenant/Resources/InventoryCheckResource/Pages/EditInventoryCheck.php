<?php

namespace App\Filament\Tenant\Resources\InventoryCheckResource\Pages;

use App\Filament\Tenant\Resources\InventoryCheckResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Str;

class EditInventoryCheck extends EditRecord
{
    protected static string $resource = InventoryCheckResource::class;

    protected function afterSave(): void
    {
        $check = $this->record;

        if ($check->status === 'completed') {
            DB::transaction(function () use ($check) {
                foreach ($check->items as $item) {
                    $diff = $item->actual_quantity - $item->expected_quantity;
                    
                    if ($diff != 0) {
                        // Update product stock
                        $product = Product::find($item->product_id);
                        $product->stock = $item->actual_quantity;
                        $product->save();

                        // Record adjustment movement
                        StockMovement::create([
                            'product_id' => $item->product_id,
                            'type' => 'adjustment',
                            'quantity' => $diff,
                            'reason' => 'Inventory check adjustment (Record #' . $check->id . ')',
                            'correlation_id' => (string) Str::uuid(),
                            'user_id' => $check->user_id,
                        ]);
                    }
                }
            });
        }
    }
}

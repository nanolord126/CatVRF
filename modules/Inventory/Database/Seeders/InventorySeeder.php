<?php declare(strict_types=1);

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InventorySeeder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function run(): void
        {
            $user = User::first();
            if (!$user) return;
    
            $products = [
                [
                    'name' => 'Professional Shampoo Classic Blue',
                    'sku' => 'SHAMPOO-01',
                    'category' => 'Care',
                    'unit' => 'ml',
                    'stock' => 5000,
                    'min_stock' => 500,
                    'price' => 15.50,
                    'is_consumable' => true,
                ],
                [
                    'name' => 'Styling Wax Extra Hold',
                    'sku' => 'GEL-01',
                    'category' => 'Care',
                    'unit' => 'pcs',
                    'stock' => 10,
                    'min_stock' => 20,
                    'price' => 8.20,
                    'is_consumable' => true,
                ],
                [
                    'name' => 'Hair Dryer HD-100 Premium',
                    'sku' => 'DRYER-01',
                    'category' => 'Equipment',
                    'unit' => 'pcs',
                    'stock' => 5,
                    'min_stock' => 2,
                    'price' => 250.00,
                    'is_consumable' => false,
                ],
            ];
    
            foreach ($products as $pData) {
                $product = Product::updateOrCreate(['sku' => $pData['sku']], $pData);
    
                // Record initial arrival to justify stock
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $product->stock,
                    'reason' => 'Seeder initial load',
                    'correlation_id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                ]);
            }
        }
}

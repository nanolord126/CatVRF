<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\Clothing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Одежда и ткани (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class ClothingSeeder extends Seeder
{
    public function run(): void
    {
        Clothing::factory()
            ->count(3)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
} 			'name' => 'Classic White Cotton T-Shirt', 			'description' => 'Comfortable everyday white cotton t-shirt', 			'size' => 'M', 			'color' => 'white', 			'material' => 'cotton', 			'price' => 24.99, 			'quantity_in_stock' => 150, 			'category' => 'mens_casual', 			'brand' => 'Basic Wear', 			'image_url' => 'https://example.com/tshirt.jpg', 			'status' => 'published', 		]);  		Clothing::create([ 			'name' => 'Blue Denim Jeans', 			'description' => 'Premium dark blue denim jeans for all occasions', 			'size' => 'L', 			'color' => 'blue', 			'material' => 'denim', 			'price' => 79.99, 			'quantity_in_stock' => 80, 			'category' => 'mens_bottoms', 			'brand' => 'Denim Masters', 			'image_url' => 'https://example.com/jeans.jpg', 			'status' => 'published', 		]);  		Clothing::create([ 			'name' => 'Elegant Black Evening Dress', 			'description' => 'Sophisticated black evening dress for special occasions', 			'size' => 'S', 			'color' => 'black', 			'material' => 'polyester_blend', 			'price' => 149.99, 			'quantity_in_stock' => 30, 			'category' => 'womens_formal', 			'brand' => 'Elegance Line', 			'image_url' => 'https://example.com/dress.jpg', 			'status' => 'published', 		]); 	} }

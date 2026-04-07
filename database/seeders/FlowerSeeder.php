<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\Flower;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Цветы (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class FlowerSeeder extends Seeder
{
    public function run(): void
    {
        Flower::factory()
            ->count(3)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
} 			'name' => 'Red Roses Bouquet', 			'description' => 'Beautiful bouquet of 12 red roses with greenery', 			'flower_type' => 'rose', 			'color' => 'red', 			'stem_length' => 40, 			'price_per_piece' => 5.00, 			'price_per_bouquet' => 55.00, 			'availability' => 'in_stock', 			'seasonality' => json_encode(['spring', 'summer', 'fall', 'winter']), 			'care_instructions' => 'Change water daily, trim stems, keep cool', 			'delivery_available' => true, 			'is_available' => true, 			'status' => 'published', 			'image_url' => 'https://example.com/roses.jpg', 		]);  		Flower::create([ 			'name' => 'Sunflower Mix', 			'description' => 'Bright sunflowers mixed with complementary greens', 			'flower_type' => 'sunflower', 			'color' => 'yellow', 			'stem_length' => 50, 			'price_per_piece' => 3.50, 			'price_per_bouquet' => 35.00, 			'availability' => 'in_stock', 			'seasonality' => json_encode(['summer', 'fall']), 			'care_instructions' => 'Keep away from direct heat, change water daily', 			'delivery_available' => true, 			'is_available' => true, 			'status' => 'published', 			'image_url' => 'https://example.com/sunflowers.jpg', 		]);  		Flower::create([ 			'name' => 'Orchid Collection', 			'description' => 'Elegant white and purple orchids arrangement', 			'flower_type' => 'orchid', 			'color' => 'white', 			'stem_length' => 35, 			'price_per_piece' => 8.00, 			'price_per_bouquet' => 80.00, 			'availability' => 'limited', 			'seasonality' => json_encode(['spring', 'summer']), 			'care_instructions' => 'Humid environment, weekly watering', 			'delivery_available' => true, 			'is_available' => true, 			'status' => 'published', 			'image_url' => 'https://example.com/orchids.jpg', 		]); 	} }

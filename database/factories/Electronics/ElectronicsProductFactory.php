<?php

declare(strict_types=1);

namespace Database\Factories\Electronics;

use App\Domains\Electronics\Models\ElectronicsProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ElectronicsProductFactory extends Factory
{
    protected string|false $model = ElectronicsProduct::class;

    public function definition(): array
    {
        $brands = ['Apple', 'Samsung', 'Sony', 'LG', 'Dell', 'Asus', 'Xiaomi', 'Huawei', 'Lenovo', 'HP'];
        $categories = ['Smartphones', 'Laptops', 'Tablets', 'Headphones', 'Monitors', 'Keyboards', 'Cameras', 'Smartwatches'];
        $colors = ['Black', 'White', 'Silver', 'Gray', 'Blue', 'Red', 'Gold', 'Rose Gold'];
        $types = ['smartphones', 'laptops', 'tablets', 'headphones', 'tv', 'cameras', 'smartwatches', 'gaming', 'audio', 'networking', 'accessories', 'wearable', 'home_automation', 'car_electronics', 'appliances'];
        
        $brand = $this->faker->randomElement($brands);
        $category = $this->faker->randomElement($categories);
        $type = $this->faker->randomElement($types);
        $color = $this->faker->randomElement($colors);
        
        $priceKopecks = $this->faker->numberBetween(100000, 10000000); // 1000 - 100000 RUB
        $hasDiscount = $this->faker->boolean(30);
        
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'category_id' => $this->faker->numberBetween(1, 10),
            'store_id' => $this->faker->numberBetween(1, 5),
            'name' => $this->generateProductName($brand, $category),
            'sku' => strtoupper('ELEC-' . $this->faker->bothify('????-####')),
            'brand' => $brand,
            'category' => $category,
            'type' => $type,
            'model_number' => $this->faker->bothify('??###'),
            'description' => $this->faker->paragraphs(3, true),
            'price_kopecks' => $priceKopecks,
            'original_price_kopecks' => $hasDiscount ? (int) ($priceKopecks * 1.2) : null,
            'b2b_price_kopecks' => $this->faker->boolean(50) ? (int) ($priceKopecks * 0.9) : null,
            'current_stock' => $this->faker->numberBetween(0, 100),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'hold_stock' => $this->faker->numberBetween(0, 10),
            'min_threshold' => $this->faker->numberBetween(1, 10),
            'availability' => $this->faker->randomElement(['in_stock', 'pre_order', 'out_of_stock']),
            'availability_status' => $this->faker->randomElement(['in_stock', 'low_stock', 'out_of_stock', 'pre_order', 'discontinued']),
            'is_active' => $this->faker->boolean(90),
            'specs' => $this->generateSpecs($category),
            'color' => $color,
            'images' => $this->generateImages(),
            'package_contents' => ['device', 'charger', 'cable', 'manual', 'warranty_card'],
            'weight_kg' => $this->faker->randomFloat(2, 0.1, 5.0),
            'rating' => $this->faker->randomFloat(1, 3.0, 5.0),
            'reviews_count' => $this->faker->numberBetween(0, 500),
            'views_count' => $this->faker->numberBetween(0, 10000),
            'is_bestseller' => $this->faker->boolean(15),
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => $this->faker->randomElements(['electronics', 'tech', 'new', 'sale', 'popular'], $this->faker->numberBetween(1, 3)),
        ];
    }

    private function generateProductName(string $brand, string $category): string
    {
        $models = [
            'Smartphones' => ['Pro', 'Max', 'Ultra', 'Plus', 'Lite', 'Mini'],
            'Laptops' => ['Pro', 'Air', 'Book', 'ThinkPad', 'ZenBook', 'Inspiron'],
            'Tablets' => ['Pro', 'Air', 'Tab', 'Pad', 'Galaxy Tab'],
            'Headphones' => ['Pro', 'Max', 'Studio', 'Buds', 'Elite'],
            'Monitors' => ['Ultra', 'Pro', 'Gaming', '4K', 'Curved'],
            'Keyboards' => ['Mechanical', 'Wireless', 'RGB', 'Pro', 'Elite'],
            'Cameras' => ['Pro', 'Alpha', 'EOS', 'Lumix', 'D-SLR'],
            'Smartwatches' => ['Pro', 'Series', 'Watch', 'Galaxy Watch', 'Fit'],
        ];

        $suffix = $models[$category][$this->faker->numberBetween(0, count($models[$category]) - 1)] ?? '';
        $year = $this->faker->randomElement([2023, 2024, 2025]);
        
        return "$brand $category $suffix $year";
    }

    private function generateSpecs(string $category): array
    {
        $specs = [
            'Smartphones' => [
                'screen_size' => $this->faker->randomElement(['6.1"', '6.5"', '6.7"', '6.8"']),
                'ram' => $this->faker->randomElement(['4GB', '6GB', '8GB', '12GB', '16GB']),
                'storage' => $this->faker->randomElement(['64GB', '128GB', '256GB', '512GB', '1TB']),
                'cpu' => $this->faker->randomElement(['A16 Bionic', 'Snapdragon 8 Gen 2', 'Dimensity 9000']),
                'battery' => $this->faker->randomElement(['4000mAh', '4500mAh', '5000mAh']),
                'camera' => $this->faker->randomElement(['48MP', '64MP', '108MP', '12MP Triple']),
                'os' => $this->faker->randomElement(['iOS 17', 'Android 14', 'OneUI 6']),
            ],
            'Laptops' => [
                'screen_size' => $this->faker->randomElement(['13"', '14"', '15"', '16"', '17"']),
                'ram' => $this->faker->randomElement(['8GB', '16GB', '32GB', '64GB']),
                'storage' => $this->faker->randomElement(['256GB SSD', '512GB SSD', '1TB SSD', '2TB SSD']),
                'cpu' => $this->faker->randomElement(['Intel i5', 'Intel i7', 'Intel i9', 'AMD Ryzen 5', 'AMD Ryzen 7', 'Apple M2', 'Apple M3']),
                'gpu' => $this->faker->randomElement(['Integrated', 'RTX 4050', 'RTX 4060', 'RTX 4070', 'RX 7600']),
                'os' => $this->faker->randomElement(['Windows 11', 'macOS Sonoma', 'Ubuntu']),
            ],
            'Tablets' => [
                'screen_size' => $this->faker->randomElement(['10"', '11"', '12"', '13"']),
                'ram' => $this->faker->randomElement(['4GB', '6GB', '8GB', '16GB']),
                'storage' => $this->faker->randomElement(['64GB', '128GB', '256GB', '512GB']),
                'cpu' => $this->faker->randomElement(['A14', 'A15', 'A16', 'Snapdragon 8 Gen 2']),
                'os' => $this->faker->randomElement(['iPadOS 17', 'Android 14']),
            ],
            'Headphones' => [
                'type' => $this->faker->randomElement(['Over-ear', 'On-ear', 'In-ear', 'True Wireless']),
                'noise_cancellation' => $this->faker->boolean(70) ? 'Active' : 'None',
                'battery_life' => $this->faker->randomElement(['20h', '30h', '40h', '60h']),
                'wireless' => $this->faker->boolean(90) ? 'Bluetooth 5.3' : 'Wired',
            ],
            'Monitors' => [
                'screen_size' => $this->faker->randomElement(['24"', '27"', '32"', '34"', '38"']),
                'resolution' => $this->faker->randomElement(['1920x1080', '2560x1440', '3840x2160', '5120x2880']),
                'refresh_rate' => $this->faker->randomElement(['60Hz', '120Hz', '144Hz', '165Hz', '240Hz']),
                'panel_type' => $this->faker->randomElement(['IPS', 'VA', 'TN', 'OLED']),
            ],
            'Keyboards' => [
                'type' => $this->faker->randomElement(['Mechanical', 'Membrane', 'Scissor']),
                'switch' => $this->faker->randomElement(['Cherry MX Red', 'Cherry MX Blue', 'Cherry MX Brown', 'Razer Green']),
                'backlight' => $this->faker->boolean(60) ? 'RGB' : 'None',
                'wireless' => $this->faker->boolean(40),
            ],
            'Cameras' => [
                'sensor' => $this->faker->randomElement(['APS-C', 'Full Frame', 'Micro Four Thirds']),
                'megapixels' => $this->faker->randomElement(['20MP', '24MP', '30MP', '45MP', '61MP']),
                'video' => $this->faker->randomElement(['4K 30fps', '4K 60fps', '6K 30fps', '8K 30fps']),
            ],
            'Smartwatches' => [
                'screen_size' => $this->faker->randomElement(['1.4"', '1.5"', '1.9"', '2.0"']),
                'battery_life' => $this->faker->randomElement(['18h', '24h', '36h', '7 days', '14 days']),
                'water_resistance' => $this->faker->randomElement(['IP68', '5ATM', '50m']),
                'gps' => $this->faker->boolean(80),
            ],
        ];

        return $specs[$category] ?? [];
    }

    private function generateImages(): array
    {
        return [
            "https://via.placeholder.com/400x400?text=Product+1",
            "https://via.placeholder.com/400x400?text=Product+2",
            "https://via.placeholder.com/400x400?text=Product+3",
        ];
    }

    // State methods for common scenarios
    public function withDiscount(): self
    {
        return $this->state(fn (array $attributes) => [
            'original_price_kopecks' => (int) ($attributes['price_kopecks'] * 1.3),
        ]);
    }

    public function bestseller(): self
    {
        return $this->state([
            'is_bestseller' => true,
            'views_count' => $this->faker->numberBetween(5000, 20000),
            'reviews_count' => $this->faker->numberBetween(100, 500),
        ]);
    }

    public function inStock(): self
    {
        return $this->state([
            'availability' => 'in_stock',
            'availability_status' => 'in_stock',
            'current_stock' => $this->faker->numberBetween(10, 100),
            'stock_quantity' => $this->faker->numberBetween(10, 100),
        ]);
    }

    public function outOfStock(): self
    {
        return $this->state([
            'availability' => 'out_of_stock',
            'availability_status' => 'out_of_stock',
            'current_stock' => 0,
            'stock_quantity' => 0,
        ]);
    }

    public function highRating(): self
    {
        return $this->state([
            'rating' => $this->faker->randomFloat(1, 4.5, 5.0),
            'reviews_count' => $this->faker->numberBetween(50, 500),
        ]);
    }

    public function premium(): self
    {
        return $this->state([
            'price_kopecks' => $this->faker->numberBetween(5000000, 10000000),
            'brand' => $this->faker->randomElement(['Apple', 'Sony', 'Samsung']),
        ]);
    }

    public function budget(): self
    {
        return $this->state([
            'price_kopecks' => $this->faker->numberBetween(100000, 500000),
            'brand' => $this->faker->randomElement(['Xiaomi', 'Huawei', 'Lenovo']),
        ]);
    }

    public function inactive(): self
    {
        return $this->state([
            'is_active' => false,
            'availability_status' => 'discontinued',
        ]);
    }
}

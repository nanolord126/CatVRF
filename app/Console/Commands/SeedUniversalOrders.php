<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SeedUniversalOrders extends Command
{
    protected $signature = 'orders:seed {--count=10}';
    protected $description = 'Seed universal orders without booting Filament';

    private const VERTICALS = [
        'beauty', 'food', 'real_estate', 'fashion', 'travel', 'auto', 'hotels',
        'medical', 'electronics', 'fitness', 'sports', 'luxury', 'insurance',
        'legal', 'logistics', 'education', 'crm', 'delivery', 'payment',
        'analytics', 'consulting', 'content', 'freelance', 'event_planning',
        'staff', 'inventory', 'taxi', 'tickets', 'wallet', 'pet',
        'wedding_planning', 'veterinary', 'toys_and_games', 'advertising',
        'car_rental', 'finances', 'flowers', 'furniture', 'pharmacy',
        'photography', 'short_term_rentals', 'sports_nutrition',
        'personal_development', 'home_services', 'gardening', 'geo',
        'geo_logistics', 'grocery_and_delivery', 'farm_direct', 'meat_shops',
        'office_catering', 'party_supplies', 'confectionery',
        'construction_and_repair', 'cleaning_services', 'communication',
        'books_and_literature', 'collectibles', 'hobby_and_craft',
        'household_goods', 'marketplace', 'music_and_instruments',
        'vegan_products', 'art',
    ];

    public function handle(): int
    {
        $count = (int) $this->option('count');

        $this->info("Seeding {$count} B2C and B2B orders for " . count(self::VERTICALS) . " verticals...");

        $totalOrders = 0;

        foreach (self::VERTICALS as $vertical) {
            for ($i = 0; $i < $count; $i++) {
                $this->seedB2COrder($vertical);
                $totalOrders++;
            }

            for ($i = 0; $i < max(1, $count / 2); $i++) {
                $this->seedB2BOrder($vertical);
                $totalOrders++;
            }
        }

        $this->info("Successfully seeded {$totalOrders} orders.");

        return Command::SUCCESS;
    }

    private function seedB2COrder(string $vertical): void
    {
        $tenantId = 1;
        $userId = 1;
        $subtotal = rand(10000, 500000);
        $shippingCost = rand(0, 10000);
        $discountAmount = rand(0, 50000);
        $total = $subtotal + $shippingCost - $discountAmount;
        $platformCommission = (int) ($total * 0.14);
        $sellerEarnings = $total - $platformCommission;

        $orderId = DB::table('orders')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'business_group_id' => null,
            'vertical' => $vertical,
            'status' => $this->getRandomStatus(),
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'platform_commission' => $platformCommission,
            'seller_earnings' => $sellerEarnings,
            'currency' => 'RUB',
            'payment_status' => $this->getRandomPaymentStatus(),
            'payment_method' => $this->getRandomPaymentMethod(),
            'is_b2b' => false,
            'inn' => null,
            'business_card_id' => null,
            'delivery_address' => $this->getRandomAddress(),
            'delivery_lat' => rand(55000000, 60000000) / 1000000,
            'delivery_lon' => rand(35000000, 40000000) / 1000000,
            'metadata' => json_encode(['seeded' => true, 'b2c' => true]),
            'tags' => json_encode(['b2b' => false, 'vertical' => $vertical]),
            'correlation_id' => Str::uuid()->toString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemCount = rand(1, 5);
        for ($i = 0; $i < $itemCount; $i++) {
            $quantity = rand(1, 5);
            $unitPrice = rand(1000, 50000);

            DB::table('order_items')->insert([
                'order_id' => $orderId,
                'product_type' => $vertical . '_product',
                'product_id' => rand(1, 1000),
                'product_name' => ucfirst($vertical) . ' Product ' . ($i + 1),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'options' => json_encode(['color' => $this->getRandomColor(), 'size' => $this->getRandomSize()]),
                'correlation_id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedB2BOrder(string $vertical): void
    {
        $tenantId = 1;
        $userId = 1;
        $subtotal = rand(100000, 10000000);
        $shippingCost = 0;
        $discountAmount = (int) ($subtotal * rand(5, 10) / 100);
        $total = $subtotal + $shippingCost - $discountAmount;
        $platformCommission = (int) ($total * 0.12);
        $sellerEarnings = $total - $platformCommission;

        $orderId = DB::table('orders')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'business_group_id' => 1,
            'vertical' => $vertical,
            'status' => $this->getRandomStatus(),
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'platform_commission' => $platformCommission,
            'seller_earnings' => $sellerEarnings,
            'currency' => 'RUB',
            'payment_status' => $this->getRandomPaymentStatus(),
            'payment_method' => 'b2b_credit',
            'is_b2b' => true,
            'inn' => $this->getRandomINN(),
            'business_card_id' => 'BC-' . Str::random(8),
            'delivery_address' => $this->getRandomAddress(),
            'delivery_lat' => rand(55000000, 60000000) / 1000000,
            'delivery_lon' => rand(35000000, 40000000) / 1000000,
            'metadata' => json_encode(['seeded' => true, 'b2b' => true, 'tier' => 'standard']),
            'tags' => json_encode(['b2b' => true, 'vertical' => $vertical]),
            'correlation_id' => Str::uuid()->toString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemCount = rand(1, 5);
        for ($i = 0; $i < $itemCount; $i++) {
            $quantity = rand(10, 100);
            $unitPrice = rand(1000, 50000);

            DB::table('order_items')->insert([
                'order_id' => $orderId,
                'product_type' => $vertical . '_product',
                'product_id' => rand(1, 1000),
                'product_name' => ucfirst($vertical) . ' Product ' . ($i + 1),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'options' => json_encode(['color' => $this->getRandomColor(), 'size' => $this->getRandomSize()]),
                'correlation_id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function getRandomStatus(): string
    {
        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        return $statuses[array_rand($statuses)];
    }

    private function getRandomPaymentStatus(): string
    {
        $statuses = ['pending', 'paid', 'failed', 'refunded', 'partial_refund'];
        return $statuses[array_rand($statuses)];
    }

    private function getRandomPaymentMethod(): string
    {
        $methods = ['card', 'sbp', 'wallet'];
        return $methods[array_rand($methods)];
    }

    private function getRandomAddress(): string
    {
        $streets = ['Main Street', 'Park Avenue', 'Oak Road', 'Elm Street', 'Broadway'];
        $numbers = rand(1, 999);
        return $numbers . ' ' . $streets[array_rand($streets)] . ', Moscow';
    }

    private function getRandomINN(): string
    {
        return str_pad((string) rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT);
    }

    private function getRandomColor(): string
    {
        $colors = ['red', 'blue', 'green', 'black', 'white', 'yellow', 'purple'];
        return $colors[array_rand($colors)];
    }

    private function getRandomSize(): string
    {
        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        return $sizes[array_rand($sizes)];
    }
}

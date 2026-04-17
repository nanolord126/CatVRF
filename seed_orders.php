<?php
// Simple PHP script to seed orders without booting Laravel
require __DIR__ . '/vendor/autoload.php';

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_DATABASE') ?: 'catvrf';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $verticals = [
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

    $totalOrders = 0;
    $countPerVertical = 5;

    foreach ($verticals as $vertical) {
        // B2C orders
        for ($i = 0; $i < $countPerVertical; $i++) {
            $subtotal = rand(10000, 500000);
            $shippingCost = rand(0, 10000);
            $discountAmount = rand(0, 50000);
            $total = $subtotal + $shippingCost - $discountAmount;
            $platformCommission = (int) ($total * 0.14);
            $sellerEarnings = $total - $platformCommission;

            $stmt = $pdo->prepare("INSERT INTO orders (uuid, tenant_id, user_id, vertical, status, subtotal, shipping_cost, discount_amount, total, platform_commission, seller_earnings, currency, payment_status, payment_method, is_b2b, delivery_address, delivery_lat, delivery_lon, metadata, tags, correlation_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                bin2hex(random_bytes(16)),
                1,
                1,
                $vertical,
                ['pending', 'confirmed', 'processing', 'shipped', 'delivered'][rand(0, 4)],
                $subtotal,
                $shippingCost,
                $discountAmount,
                $total,
                $platformCommission,
                $sellerEarnings,
                'RUB',
                ['pending', 'paid'][rand(0, 1)],
                ['card', 'sbp', 'wallet'][rand(0, 2)],
                0,
                rand(1, 999) . ' Main Street, Moscow',
                rand(55000000, 60000000) / 1000000,
                rand(35000000, 40000000) / 1000000,
                json_encode(['seeded' => true, 'b2c' => true]),
                json_encode(['b2b' => false, 'vertical' => $vertical]),
                bin2hex(random_bytes(16)),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ]);

            $orderId = $pdo->lastInsertId();
            $totalOrders++;

            // Add order items
            $itemCount = rand(1, 5);
            for ($j = 0; $j < $itemCount; $j++) {
                $quantity = rand(1, 5);
                $unitPrice = rand(1000, 50000);
                
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_type, product_id, product_name, quantity, unit_price, total_price, options, correlation_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $orderId,
                    $vertical . '_product',
                    rand(1, 1000),
                    ucfirst(str_replace('_', ' ', $vertical)) . ' Product ' . ($j + 1),
                    $quantity,
                    $unitPrice,
                    $quantity * $unitPrice,
                    json_encode(['color' => ['red', 'blue', 'green'][rand(0, 2)]]),
                    bin2hex(random_bytes(16)),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]);
            }
        }

        // B2B orders
        for ($i = 0; $i < max(1, $countPerVertical / 2); $i++) {
            $subtotal = rand(100000, 10000000);
            $shippingCost = 0;
            $discountAmount = (int) ($subtotal * rand(5, 10) / 100);
            $total = $subtotal + $shippingCost - $discountAmount;
            $platformCommission = (int) ($total * 0.12);
            $sellerEarnings = $total - $platformCommission;

            $stmt = $pdo->prepare("INSERT INTO orders (uuid, tenant_id, user_id, business_group_id, vertical, status, subtotal, shipping_cost, discount_amount, total, platform_commission, seller_earnings, currency, payment_status, payment_method, is_b2b, inn, business_card_id, delivery_address, delivery_lat, delivery_lon, metadata, tags, correlation_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                bin2hex(random_bytes(16)),
                1,
                1,
                1,
                $vertical,
                ['pending', 'confirmed', 'processing'][rand(0, 2)],
                $subtotal,
                $shippingCost,
                $discountAmount,
                $total,
                $platformCommission,
                $sellerEarnings,
                'RUB',
                ['pending', 'paid'][rand(0, 1)],
                'b2b_credit',
                1,
                str_pad((string) rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT),
                'BC-' . bin2hex(random_bytes(4)),
                rand(1, 999) . ' Business Ave, Moscow',
                rand(55000000, 60000000) / 1000000,
                rand(35000000, 40000000) / 1000000,
                json_encode(['seeded' => true, 'b2b' => true, 'tier' => 'standard']),
                json_encode(['b2b' => true, 'vertical' => $vertical]),
                bin2hex(random_bytes(16)),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ]);

            $orderId = $pdo->lastInsertId();
            $totalOrders++;

            // Add order items
            $itemCount = rand(1, 5);
            for ($j = 0; $j < $itemCount; $j++) {
                $quantity = rand(10, 100);
                $unitPrice = rand(1000, 50000);
                
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_type, product_id, product_name, quantity, unit_price, total_price, options, correlation_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $orderId,
                    $vertical . '_product',
                    rand(1, 1000),
                    ucfirst(str_replace('_', ' ', $vertical)) . ' Product ' . ($j + 1),
                    $quantity,
                    $unitPrice,
                    $quantity * $unitPrice,
                    json_encode(['color' => ['red', 'blue', 'green'][rand(0, 2)]]),
                    bin2hex(random_bytes(16)),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    echo "Successfully seeded {$totalOrders} orders for " . count($verticals) . " verticals.\n";
    echo "B2C orders: " . (count($verticals) * $countPerVertical) . "\n";
    echo "B2B orders: " . (count($verticals) * max(1, $countPerVertical / 2)) . "\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}

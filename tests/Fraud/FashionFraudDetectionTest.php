<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Fashion\Domain\Entities\Product;
use Modules\Fashion\Domain\Entities\Order;
use Modules\Fashion\Domain\Enums\OrderStatus;
use Illuminate\Support\Facades\Cache;

final class FashionFraudDetectionTest extends BaseFraudTest
{
    public function test_multiple_orders_same_address_different_cards(): void
    {
        $address = '123 Fraud Street, Moscow';
        $cards = ['4111111111111111', '4222222222222222', '4333333333333333'];

        foreach ($cards as $card) {
            Order::create([
                'user_id' => rand(1, 100),
                'tenant_id' => 1,
                'shipping_address' => $address,
                'payment_method' => 'card',
                'card_last_four' => substr($card, -4),
                'amount' => 15000,
                'status' => OrderStatus::Pending,
            ]);
        }

        $this->fraudControl->checkShippingFraud([
            'address' => $address,
            'card_count' => count($cards),
            'time_window_hours' => 1,
        ]);

        $this->assertFraudAlertCreated(
            'order',
            1,
            FraudType::CardTesting,
            FraudSeverity::Critical
        );
    }

    public function test_return_fraud_pattern(): void
    {
        $user = 1;
        
        // Simulate high return rate
        for ($i = 0; $i < 20; $i++) {
            Order::create([
                'user_id' => $user,
                'tenant_id' => 1,
                'amount' => 10000,
                'status' => OrderStatus::Returned,
                'returned_at' => now()->subDays($i),
            ]);
        }

        $returnRate = $this->fraudML->calculateReturnRate($user);
        
        $this->assertGreaterThan(70, $returnRate, 'High return rate should trigger fraud alert');

        if ($returnRate > 70) {
            $this->assertFraudAlertCreated(
                'user',
                $user,
                FraudType::ReturnFraud,
                FraudSeverity::High
            );
        }
    }

    public function test_price_manipulation(): void
    {
        $product = Product::factory()->create(['price' => 5000]);

        // Simulate rapid price changes
        for ($i = 0; $i < 5; $i++) {
            $product->update(['price' => $product->price * 0.5]); // Drastic price drop
        }

        $this->fraudControl->checkPriceManipulation($product);

        $this->assertFraudAlertCreated(
            'product',
            $product->id,
            FraudType::PriceManipulation,
            FraudSeverity::Medium
        );
    }

    public function test_wishlist_manipulation_for_ranking(): void
    {
        $product = Product::factory()->create();
        $deviceFingerprint = 'fp_wishlist_bot';

        // Simulate bot adding to wishlist
        for ($i = 0; $i < 100; $i++) {
            $this->fraudControl->checkWishlistManipulation([
                'product_id' => $product->id,
                'user_id' => $i + 1000, // Fake users
                'device_fingerprint' => $deviceFingerprint,
            ]);
        }

        $this->assertFraudAlertCreated(
            'product',
            $product->id,
            FraudType::WishlistManipulation,
            FraudSeverity::High
        );
    }

    public function test_inventory_fraud(): void
    {
        $product = Product::factory()->create(['stock' => 100]);

        // Simulate inventory manipulation
        $product->update(['stock' => 1000]); // Artificial stock inflation
        $this->fraudControl->checkInventoryFraud($product);

        $this->assertFraudAlertCreated(
            'product',
            $product->id,
            FraudType::InventoryManipulation,
            FraudSeverity::High
        );
    }
}

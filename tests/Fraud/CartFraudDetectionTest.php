<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Cart\Domain\Entities\Cart;
use Modules\Cart\Domain\Entities\CartItem;
use Illuminate\Support\Facades\Cache;

final class CartFraudDetectionTest extends BaseFraudTest
{
    public function test_cart_abandonment_manipulation(): void
    {
        $userId = 1;

        for ($i = 0; $i < 100; $i++) {
            $cart = Cart::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'status' => 'active',
                'total_amount' => 50000,
            ]);

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $i + 1,
                'quantity' => 10,
                'price' => 5000,
            ]);

            // Immediately abandon
            $cart->update(['status' => 'abandoned']);
        }

        $this->fraudControl->checkCartManipulation([
            'user_id' => $userId,
            'abandon_count' => 100,
            'total_value_abandoned' => 5000000,
            'time_window_hours' => 24,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceAbuse,
            FraudSeverity::Medium
        );
    }

    public function test_price_tampering_in_cart(): void
    {
        $userId = 1;

        $cart = Cart::create([
            'user_id' => $userId,
            'tenant_id' => 1,
            'status' => 'active',
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 1,
            'quantity' => 1,
            'original_price' => 10000,
            'price' => 1000, // 90% discount
        ]);

        $this->fraudControl->checkPriceManipulation([
            'entity_type' => 'cart_item',
            'entity_id' => $item->id,
            'original_price' => 10000,
            'modified_price' => 1000,
            'discount_percentage' => 90,
            'authorized' => false,
        ]);

        $this->assertFraudAlertCreated(
            'cart_item',
            $item->id,
            FraudType::PriceManipulation,
            FraudSeverity::Critical
        );
    }

    public function test_cart_quantity_manipulation(): void
    {
        $userId = 1;

        $cart = Cart::create([
            'user_id' => $userId,
            'tenant_id' => 1,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 1,
            'quantity' => 999999, // Unrealistic quantity
            'price' => 100,
        ]);

        $this->fraudControl->checkQuantityManipulation([
            'cart_item_id' => 1,
            'quantity' => 999999,
            'max_allowed' => 100,
            'inventory_available' => 50,
        ]);

        $this->assertFraudAlertCreated(
            'cart',
            $cart->id,
            FraudType::OrderManipulation,
            FraudSeverity::High
        );
    }

    public function test_multiple_carts_same_user(): void
    {
        $userId = 1;

        for ($i = 0; $i < 20; $i++) {
            Cart::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'status' => 'active',
                'total_amount' => 10000,
            ]);
        }

        $this->fraudControl->checkCartManipulation([
            'user_id' => $userId,
            'active_cart_count' => 20,
            'max_allowed' => 1,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceAbuse,
            FraudSeverity::Medium
        );
    }

    public function test_cart_hoarding(): void
    {
        $userId = 1;

        for ($i = 0; $i < 50; $i++) {
            $cart = Cart::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'status' => 'active',
                'total_amount' => 20000,
            ]);

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $i + 1,
                'quantity' => 5,
                'price' => 4000,
            ]);
        }

        $this->fraudControl->checkResourceHoarding([
            'user_id' => $userId,
            'resource_type' => 'cart',
            'cart_count' => 50,
            'total_value' => 1000000,
            'time_window_minutes' => 30,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceHoarding,
            FraudSeverity::Medium
        );
    }
}

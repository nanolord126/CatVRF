<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Supermarket\Domain\Entities\Order;
use Modules\Supermarket\Domain\Entities\Product;
use Modules\Supermarket\Domain\Enums\OrderStatus;
use Illuminate\Support\Facades\Cache;

final class SupermarketFraudDetectionTest extends BaseFraudTest
{
    public function test_self_checkout_fraud(): void
    {
        $userId = 1;

        // Simulate self-checkout with missing item scans
        for ($i = 0; $i < 15; $i++) {
            $this->fraudControl->checkSelfCheckoutFraud([
                'user_id' => $userId,
                'scanned_items' => 5,
                'actual_items' => 8,
                'difference_value' => 1500,
                'checkout_kiosk_id' => 'kiosk_001',
            ]);
        }

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::Theft,
            FraudSeverity::High
        );
    }

    public function test_coupon_abuse(): void
    {
        $userId = 1;
        $couponCode = 'SAVE50';

        // Simulate multiple uses of single-use coupon
        for ($i = 0; $i < 10; $i++) {
            Order::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'coupon_code' => $couponCode,
                'discount_amount' => 500,
                'amount' => 2000,
                'status' => OrderStatus::Completed,
            ]);
        }

        $this->fraudControl->checkCouponAbuse([
            'user_id' => $userId,
            'coupon_code' => $couponCode,
            'use_count' => 10,
            'coupon_type' => 'single_use',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::CouponAbuse,
            FraudSeverity::Medium
        );
    }

    public function test_price_switching(): void
    {
        $product = Product::factory()->create(['price' => 500, 'barcode' => '123456']);
        
        // Simulate price switching at checkout
        $this->fraudControl->checkPriceSwitching([
            'product_barcode' => '123456',
            'scanned_price' => 100, // Cheaper than actual
            'actual_price' => 500,
            'user_id' => 1,
            'attempts' => 5,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            1,
            FraudType::PriceManipulation,
            FraudSeverity::Medium
        );
    }

    public function test_return_fraud_with_receipt(): void
    {
        $userId = 1;

        // Simulate returning items bought with stolen cards
        for ($i = 0; $i < 8; $i++) {
            $order = Order::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'amount' => 5000,
                'status' => OrderStatus::Completed,
                'payment_method' => 'card',
            ]);

            // Return for cash
            $order->update([
                'status' => OrderStatus::Returned,
                'refund_method' => 'cash',
                'refund_amount' => 5000,
            ]);
        }

        $this->fraudControl->checkReturnFraud([
            'user_id' => $userId,
            'return_count' => 8,
            'original_payment' => 'card',
            'refund_method' => 'cash',
            'time_window_days' => 7,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ReturnFraud,
            FraudSeverity::High
        );
    }

    public function test_loyalty_points_manipulation(): void
    {
        $userId = 1;

        // Simulate rapid points accumulation
        $this->fraudControl->checkLoyaltyFraud([
            'user_id' => $userId,
            'points_before' => 1000,
            'points_after' => 50000,
            'accumulation_time_minutes' => 5,
            'transaction_count' => 50,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::LoyaltyFraud,
            FraudSeverity::High
        );
    }
}

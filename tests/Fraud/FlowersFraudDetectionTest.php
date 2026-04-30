<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Flowers\Domain\Entities\Order;
use Modules\Flowers\Domain\Entities\Bouquet;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Illuminate\Support\Facades\Cache;

final class FlowersFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_delivery_address(): void
    {
        $userId = 1;
        $address = 'Non-existent address, Moscow';

        for ($i = 0; $i < 10; $i++) {
            Order::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'delivery_address' => $address,
                'amount' => 3000,
                'status' => OrderStatus::Delivered,
                'delivery_success' => false,
            ]);
        }

        $this->fraudControl->checkDeliveryFraud([
            'user_id' => $userId,
            'address' => $address,
            'order_count' => 10,
            'success_rate' => 0,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::DeliveryFraud,
            FraudSeverity::High
        );
    }

    public function test_bouquet_price_manipulation(): void
    {
        $bouquet = Bouquet::factory()->create(['price' => 2000]);

        for ($i = 0; $i < 8; $i++) {
            $bouquet->update(['price' => $bouquet->price * 1.5]);
        }

        $this->fraudControl->checkPriceManipulation([
            'entity_type' => 'bouquet',
            'entity_id' => $bouquet->id,
            'price_change_count' => 8,
            'change_percentage_total' => 2500,
        ]);

        $this->assertFraudAlertCreated(
            'bouquet',
            $bouquet->id,
            FraudType::PriceManipulation,
            FraudSeverity::Medium
        );
    }

    public function test_review_manipulation(): void
    {
        $floristId = 1;
        $deviceFingerprint = 'fp_flowers_bot';

        for ($i = 0; $i < 30; $i++) {
            $this->fraudControl->checkReviewManipulation([
                'entity_type' => 'florist',
                'entity_id' => $floristId,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'rating' => 5,
                'comment' => 'Beautiful flowers!',
            ]);
        }

        $this->assertFraudAlertCreated(
            'florist',
            $floristId,
            FraudType::FakeReviews,
            FraudSeverity::High
        );
    }

    public function test_order_cancellation_abuse(): void
    {
        $userId = 1;

        for ($i = 0; $i < 15; $i++) {
            $order = Order::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'amount' => 5000,
                'status' => OrderStatus::Confirmed,
            ]);

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now()->addMinutes(5),
            ]);
        }

        $this->fraudControl->checkOrderManipulation([
            'user_id' => $userId,
            'cancel_count' => 15,
            'avg_cancel_time_minutes' => 5,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::OrderManipulation,
            FraudSeverity::Medium
        );
    }

    public function test_seasonal_price_gouging(): void
    {
        $bouquet = Bouquet::factory()->create(['base_price' => 2000]);

        $bouquet->update(['price' => 15000]); // 7.5x for Valentine's Day

        $this->fraudControl->checkPriceGouging($bouquet, [
            'original_price' => 2000,
            'new_price' => 15000,
            'season_multiplier' => 7.5,
            'is_peak_season' => true,
        ]);

        $this->assertFraudAlertCreated(
            'bouquet',
            $bouquet->id,
            FraudType::PriceManipulation,
            FraudSeverity::High
        );
    }
}

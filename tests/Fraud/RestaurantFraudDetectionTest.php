<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Restaurant\Domain\Entities\Order;
use Modules\Restaurant\Domain\Entities\Restaurant;
use Modules\Restaurant\Domain\Enums\OrderStatus;
use Illuminate\Support\Facades\Cache;

final class RestaurantFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_order_cancellation(): void
    {
        $restaurant = Restaurant::factory()->create();
        $userId = 1;

        // Simulate ordering and immediately cancelling to manipulate inventory
        for ($i = 0; $i < 25; $i++) {
            $order = Order::create([
                'restaurant_id' => $restaurant->id,
                'user_id' => $userId,
                'tenant_id' => 1,
                'amount' => 2000,
                'status' => OrderStatus::Confirmed,
            ]);

            // Cancel within 30 seconds
            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now()->addSeconds(30),
            ]);
        }

        $this->fraudControl->checkOrderManipulation([
            'user_id' => $userId,
            'restaurant_id' => $restaurant->id,
            'cancel_rate' => 100,
            'avg_cancel_time_seconds' => 30,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::OrderManipulation,
            FraudSeverity::Medium
        );
    }

    public function test_review_rating_manipulation(): void
    {
        $restaurant = Restaurant::factory()->create();
        $deviceFingerprint = 'fp_restaurant_bot';

        // Bot posting 5-star reviews
        for ($i = 0; $i < 40; $i++) {
            $this->fraudControl->checkReviewManipulation([
                'entity_type' => 'restaurant',
                'entity_id' => $restaurant->id,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'rating' => 5,
                'comment' => 'Best restaurant ever!',
            ]);
        }

        $this->assertFraudAlertCreated(
            'restaurant',
            $restaurant->id,
            FraudType::FakeReviews,
            FraudSeverity::High
        );
    }

    public function test_delivery_address_fraud(): void
    {
        $userId = 1;
        $address = '999 Fake Street, Apt 0, Moscow';

        // Multiple orders to non-existent address
        for ($i = 0; $i < 10; $i++) {
            Order::create([
                'restaurant_id' => Restaurant::factory()->create()->id,
                'user_id' => $userId,
                'tenant_id' => 1,
                'delivery_address' => $address,
                'amount' => 3000,
                'status' => OrderStatus::Delivered,
            ]);
        }

        $this->fraudControl->checkDeliveryFraud([
            'user_id' => $userId,
            'address' => $address,
            'order_count' => 10,
            'delivery_success_rate' => 0, // All failed
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::DeliveryFraud,
            FraudSeverity::High
        );
    }

    public function test_menu_price_manipulation(): void
    {
        $restaurant = Restaurant::factory()->create();

        // Simulate rapid price changes to manipulate orders
        $this->fraudControl->checkPriceManipulation([
            'entity_type' => 'restaurant_menu',
            'entity_id' => $restaurant->id,
            'price_change_count' => 15,
            'time_window_minutes' => 60,
            'change_percentage_avg' => 50,
        ]);

        $this->assertFraudAlertCreated(
            'restaurant',
            $restaurant->id,
            FraudType::PriceManipulation,
            FraudSeverity::Medium
        );
    }

    public function test_table_reservation_hoarding(): void
    {
        $restaurant = Restaurant::factory()->create();
        $userId = 1;

        // User reserving multiple tables for same time slot
        for ($i = 0; $i < 8; $i++) {
            Order::create([
                'restaurant_id' => $restaurant->id,
                'user_id' => $userId,
                'tenant_id' => 1,
                'reservation_time' => now()->addDay()->setHour(19),
                'table_number' => $i + 1,
                'amount' => 1000,
                'status' => OrderStatus::Reserved,
            ]);
        }

        $this->fraudControl->checkResourceHoarding([
            'user_id' => $userId,
            'resource_type' => 'table',
            'resource_count' => 8,
            'time_slot' => now()->addDay()->setHour(19),
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceHoarding,
            FraudSeverity::Medium
        );
    }
}

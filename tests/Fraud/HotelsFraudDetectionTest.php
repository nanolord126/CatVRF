<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Hotels\Domain\Entities\Hotel;
use Modules\Hotels\Domain\Entities\Booking;
use Modules\Hotels\Domain\Enums\BookingStatus;
use Illuminate\Support\Facades\Cache;

final class HotelsFraudDetectionTest extends BaseFraudTest
{
    public function test_multiple_bookings_same_credit_card(): void
    {
        $cardLastFour = '1234';
        $hotel = Hotel::factory()->create();

        for ($i = 0; $i < 15; $i++) {
            Booking::create([
                'hotel_id' => $hotel->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'card_last_four' => $cardLastFour,
                'check_in' => now()->addDays($i),
                'check_out' => now()->addDays($i + 2),
                'amount' => 10000,
                'status' => BookingStatus::Confirmed,
            ]);
        }

        $this->fraudControl->checkCardFraud([
            'card_last_four' => $cardLastFour,
            'booking_count' => 15,
            'time_window_hours' => 24,
        ]);

        $this->assertFraudAlertCreated(
            'hotel',
            $hotel->id,
            FraudType::CardTesting,
            FraudSeverity::Critical
        );
    }

    public function test_fake_review_manipulation(): void
    {
        $hotel = Hotel::factory()->create();
        $deviceFingerprint = 'fp_hotel_bot';

        for ($i = 0; $i < 50; $i++) {
            $this->fraudControl->checkReviewManipulation([
                'entity_type' => 'hotel',
                'entity_id' => $hotel->id,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'rating' => 5,
                'comment' => 'Amazing hotel!',
            ]);
        }

        $this->assertFraudAlertCreated(
            'hotel',
            $hotel->id,
            FraudType::FakeReviews,
            FraudSeverity::High
        );
    }

    public function test_no_show_pattern_fraud(): void
    {
        $user = 1;
        $hotel = Hotel::factory()->create();

        // Simulate repeated no-shows
        for ($i = 0; $i < 10; $i++) {
            Booking::create([
                'hotel_id' => $hotel->id,
                'user_id' => $user,
                'tenant_id' => 1,
                'check_in' => now()->subDays($i * 2),
                'check_out' => now()->subDays($i * 2 + 1),
                'amount' => 8000,
                'status' => BookingStatus::NoShow,
            ]);
        }

        $noShowRate = $this->fraudML->calculateNoShowRate($user, $hotel->id);
        
        $this->assertGreaterThan(80, $noShowRate, 'High no-show rate should trigger fraud');

        if ($noShowRate > 80) {
            $this->assertFraudAlertCreated(
                'user',
                $user,
                FraudType::NoShowFraud,
                FraudSeverity::Medium
            );
        }
    }

    public function test_price_gouging(): void
    {
        $hotel = Hotel::factory()->create(['base_price' => 5000]);

        // Simulate price gouging during peak season
        $hotel->update(['base_price' => 25000]); // 5x increase

        $this->fraudControl->checkPriceGouging($hotel, [
            'original_price' => 5000,
            'new_price' => 25000,
            'season_multiplier' => 5.0,
        ]);

        $this->assertFraudAlertCreated(
            'hotel',
            $hotel->id,
            FraudType::PriceManipulation,
            FraudSeverity::High
        );
    }

    public function test_booking_cancellation_abuse(): void
    {
        $user = 1;
        $hotel = Hotel::factory()->create();

        // Simulate booking and immediate cancellation pattern
        for ($i = 0; $i < 20; $i++) {
            $booking = Booking::create([
                'hotel_id' => $hotel->id,
                'user_id' => $user,
                'tenant_id' => 1,
                'check_in' => now()->addDays($i),
                'check_out' => now()->addDays($i + 1),
                'amount' => 8000,
                'status' => BookingStatus::Confirmed,
            ]);

            // Cancel immediately
            $booking->update(['status' => BookingStatus::Cancelled]);
        }

        $this->fraudControl->checkCancellationAbuse($user, $hotel->id);

        $this->assertFraudAlertCreated(
            'user',
            $user,
            FraudType::BookingAbuse,
            FraudSeverity::Medium
        );
    }
}

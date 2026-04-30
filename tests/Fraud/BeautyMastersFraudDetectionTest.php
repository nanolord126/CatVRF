<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\BeautyMasters\Domain\Entities\Appointment;
use Modules\BeautyMasters\Domain\Entities\BeautySalon;
use Modules\BeautyMasters\Domain\Entities\Master;
use Modules\BeautyMasters\Domain\Enums\AppointmentStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

final class BeautyMastersFraudDetectionTest extends BaseFraudTest
{
    public function test_multiple_appointments_same_slot_different_users(): void
    {
        // Simulate multiple users trying to book same slot
        $salon = BeautySalon::factory()->create();
        $master = Master::factory()->for($salon)->create();
        $slotTime = now()->addDay()->setHour(10)->setMinute(0);

        $attempts = 0;
        foreach (range(1, 10) as $i) {
            try {
                Appointment::create([
                    'salon_id' => $salon->id,
                    'master_id' => $master->id,
                    'user_id' => $i,
                    'tenant_id' => 1,
                    'scheduled_at' => $slotTime,
                    'status' => AppointmentStatus::Pending,
                    'amount' => 5000,
                ]);
                $attempts++;
            } catch (\Exception $e) {
                // Expected failures due to slot conflicts
            }
        }

        // Should trigger fraud alert if too many attempts from same IP
        if ($attempts > 5) {
            $this->assertFraudAlertCreated(
                'appointment',
                $salon->id,
                FraudType::BookingManipulation,
                FraudSeverity::High
            );
        }
    }

    public function test_rapid_booking_attempts_from_single_ip(): void
    {
        $ipAddress = '192.168.1.100';
        $salon = BeautySalon::factory()->create();
        $master = Master::factory()->for($salon)->create();

        // Simulate rapid booking attempts
        for ($i = 0; $i < 20; $i++) {
            $this->simulateSuspiciousActivity([
                'ip_address' => $ipAddress,
                'request_count' => $i + 1,
                'time_window_seconds' => 10,
            ]);

            try {
                Appointment::create([
                    'salon_id' => $salon->id,
                    'master_id' => $master->id,
                    'user_id' => 1,
                    'tenant_id' => 1,
                    'scheduled_at' => now()->addDays($i + 1),
                    'status' => AppointmentStatus::Pending,
                    'amount' => 5000,
                    'ip_address' => $ipAddress,
                ]);
            } catch (\Exception $e) {
                // Expected failures
            }
        }

        // Should trigger rate limiting fraud alert
        $this->assertFraudAlertCreated(
            'appointment',
            $salon->id,
            FraudType::RateLimitBypass,
            FraudSeverity::Critical
        );
    }

    public function test_fake_reviews_from_same_device(): void
    {
        $deviceFingerprint = 'fp_fraud_device';
        $salon = BeautySalon::factory()->create();

        // Simulate multiple reviews from same device
        for ($i = 1; $i <= 15; $i++) {
            $this->fraudControl->checkReviewManipulation([
                'entity_type' => 'salon',
                'entity_id' => $salon->id,
                'user_id' => $i,
                'device_fingerprint' => $deviceFingerprint,
                'rating' => 5,
                'comment' => 'Great service!',
            ]);
        }

        $this->assertFraudAlertCreated(
            'salon',
            $salon->id,
            FraudType::FakeReviews,
            FraudSeverity::High
        );
    }

    public function test_unusual_high_value_appointment(): void
    {
        $salon = BeautySalon::factory()->create();
        
        // Create unusually high value appointment
        $appointment = Appointment::create([
            'salon_id' => $salon->id,
            'master_id' => Master::factory()->for($salon)->create()->id,
            'user_id' => 1,
            'tenant_id' => 1,
            'scheduled_at' => now()->addDay(),
            'status' => AppointmentStatus::Pending,
            'amount' => 500000, // Unusually high for beauty service
            'ip_address' => '192.168.1.1',
        ]);

        $fraudScore = $this->fraudML->calculateFraudScore([
            'entity_type' => 'appointment',
            'entity_id' => $appointment->id,
            'amount' => $appointment->amount,
            'user_id' => $appointment->user_id,
            'ip_address' => $appointment->ip_address,
        ]);

        $this->assertGreaterThan(80, $fraudScore, 'High value appointment should have high fraud score');
    }

    public function test_master_profile_manipulation(): void
    {
        $salon = BeautySalon::factory()->create();
        $master = Master::factory()->for($salon)->create();

        // Simulate rapid profile updates to boost ranking
        for ($i = 0; $i < 10; $i++) {
            $master->update([
                'rating' => min(5.0, $master->rating + 0.1),
                'review_count' => $master->review_count + 10,
            ]);
        }

        $this->fraudControl->checkProfileManipulation($master);

        $this->assertFraudAlertCreated(
            'master',
            $master->id,
            FraudType::ProfileManipulation,
            FraudSeverity::Medium
        );
    }
}

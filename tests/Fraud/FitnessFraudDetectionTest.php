<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Fitness\Infrastructure\Models\MembershipModel;
use Modules\Fitness\Infrastructure\Models\VenueModel;
use Illuminate\Support\Facades\Cache;

final class FitnessFraudDetectionTest extends BaseFraudTest
{
    public function test_membership_trial_abuse(): void
    {
        $userId = 1;
        $gym = VenueModel::factory()->create();

        // User creating multiple trial accounts
        for ($i = 0; $i < 10; $i++) {
            MembershipModel::create([
                'user_id' => $userId + $i,
                'gym_id' => $gym->id,
                'tenant_id' => 1,
                'ip_address' => '192.168.1.60',
                'device_fingerprint' => 'fp_fitness_abuse',
                'type' => 'trial',
                'status' => 'active',
            ]);
        }

        $this->fraudControl->checkTrialAbuse([
            'ip_address' => '192.168.1.60',
            'device_fingerprint' => 'fp_fitness_abuse',
            'trial_count' => 10,
            'time_window_days' => 30,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            'trial_abuse',
            'medium'
        );
    }

    public function test_class_booking_hoarding(): void
    {
        $userId = 1;
        $gym = VenueModel::factory()->create();

        // User booking all classes for a week
        for ($i = 0; $i < 20; $i++) {
            $this->fraudControl->checkResourceHoarding([
                'user_id' => $userId,
                'resource_type' => 'fitness_class',
                'gym_id' => $gym->id,
                'booking_count' => 20,
                'time_window_days' => 7,
            ]);
        }

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            'resource_hoarding',
            'medium'
        );
    }

    public function test_fake_attendance_check(): void
    {
        $userId = 1;

        // Simulate fake attendance scanning
        for ($i = 0; $i < 30; $i++) {
            $this->fraudControl->checkAttendanceFraud([
                'user_id' => $userId,
                'check_in_time' => now()->subHours($i),
                'check_out_time' => now()->subHours($i - 1),
                'duration_minutes' => 60,
                'location_verified' => false,
            ]);
        }

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::AttendanceFraud,
            FraudSeverity::High
        );
    }

    public function test_instructor_rating_manipulation(): void
    {
        $gym = VenueModel::factory()->create();
        $instructorId = 1;
        $deviceFingerprint = 'fp_fitness_bot';

        for ($i = 0; $i < 25; $i++) {
            $this->fraudControl->checkReviewManipulation([
                'entity_type' => 'instructor',
                'entity_id' => $instructorId,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'rating' => 5,
                'comment' => 'Best instructor!',
            ]);
        }

        $this->assertFraudAlertCreated(
            'instructor',
            $instructorId,
            'fake_reviews',
            'high'
        );
    }

    public function test_membership_refund_fraud(): void
    {
        $userId = 1;

        for ($i = 0; $i < 8; $i++) {
            $membership = MembershipModel::create([
                'user_id' => $userId,
                'gym_id' => VenueModel::factory()->create()->id,
                'tenant_id' => 1,
                'type' => 'annual',
                'amount' => 30000,
                'status' => 'active',
            ]);

            // Immediate refund request
            $membership->update([
                'status' => 'cancelled',
                'refunded' => true,
                'refund_amount' => 30000,
            ]);
        }

        $this->fraudControl->checkRefundFraud([
            'user_id' => $userId,
            'refund_count' => 8,
            'avg_time_to_refund_hours' => 1,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::RefundFraud,
            FraudSeverity::High
        );
    }
}

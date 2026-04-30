<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\VetGrooming\Domain\Entities\GroomingSession;
use Modules\VetGrooming\Domain\Entities\Pet;
use Modules\VetGrooming\Domain\Enums\SessionStatus;
use Illuminate\Support\Facades\Cache;

final class VetGroomingFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_pet_registration(): void
    {
        $userId = 1;

        // Register multiple fake pets
        for ($i = 0; $i < 15; $i++) {
            Pet::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'name' => "Pet {$i}",
                'species' => 'dog',
                'breed' => 'mixed',
                'age' => rand(1, 10),
                'medical_records' => [],
            ]);
        }

        $this->fraudControl->checkPetRegistrationFraud([
            'user_id' => $userId,
            'pet_count' => 15,
            'time_window_days' => 7,
            'avg_medical_records' => 0,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::FakeAccount,
            FraudSeverity::Medium
        );
    }

    public function test_appointment_no_show_abuse(): void
    {
        $userId = 1;

        // Repeated no-shows
        for ($i = 0; $i < 12; $i++) {
            $session = GroomingSession::create([
                'user_id' => $userId,
                'pet_id' => Pet::factory()->create()->id,
                'tenant_id' => 1,
                'scheduled_at' => now()->subDays($i),
                'status' => SessionStatus::Booked,
                'amount' => 3000,
            ]);

            $session->update([
                'status' => SessionStatus::NoShow,
            ]);
        }

        $noShowRate = $this->fraudML->calculateNoShowRate($userId);
        
        $this->assertGreaterThan(80, $noShowRate, 'High no-show rate should trigger fraud');

        if ($noShowRate > 80) {
            $this->assertFraudAlertCreated(
                'user',
                $userId,
                FraudType::BookingAbuse,
                FraudSeverity::Medium
            );
        }
    }

    public function test_groomer_rating_manipulation(): void
    {
        $groomerId = 1;
        $deviceFingerprint = 'fp_vet_bot';

        for ($i = 0; $i < 20; $i++) {
            $this->fraudControl->checkReviewManipulation([
                'entity_type' => 'groomer',
                'entity_id' => $groomerId,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'rating' => 5,
                'comment' => 'Excellent groomer!',
            ]);
        }

        $this->assertFraudAlertCreated(
            'groomer',
            $groomerId,
            FraudType::FakeReviews,
            FraudSeverity::High
        );
    }

    public function test_medical_record_fraud(): void
    {
        $petId = 1;

        // Simulate fake medical records
        for ($i = 0; $i < 10; $i++) {
            $this->fraudControl->checkRecordTampering([
                'pet_id' => $petId,
                'record_type' => 'vaccination',
                'previous_value' => null,
                'new_value' => 'vaccinated',
                'verified_by_vet' => false,
                'update_interval_hours' => 1,
            ]);
        }

        $this->assertFraudAlertCreated(
            'pet',
            $petId,
            FraudType::DataTampering,
            FraudSeverity::Critical
        );
    }

    public function test_certification_fraud(): void
    {
        $groomerId = 1;

        // Simulate fake certification claims
        $this->fraudControl->checkCertificationFraud([
            'groomer_id' => $groomerId,
            'certification_type' => 'professional_groomer',
            'certificate_number' => 'FAKE_CERT_123',
            'issuing_authority' => 'fake_authority',
            'verified' => false,
        ]);

        $this->assertFraudAlertCreated(
            'groomer',
            $groomerId,
            FraudType::CredentialFraud,
            FraudSeverity::High
        );
    }
}

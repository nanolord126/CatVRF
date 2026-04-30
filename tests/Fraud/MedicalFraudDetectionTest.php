<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Medical\Domain\Entities\Doctor;
use Modules\Medical\Domain\Entities\Appointment;
use Modules\Medical\Domain\Enums\AppointmentStatus;
use Illuminate\Support\Facades\Cache;

final class MedicalFraudDetectionTest extends BaseFraudTest
{
    public function test_prescription_fraud(): void
    {
        $doctor = Doctor::factory()->create();

        // Simulate unusual prescription pattern
        for ($i = 0; $i < 30; $i++) {
            $this->fraudControl->checkPrescriptionFraud([
                'doctor_id' => $doctor->id,
                'patient_id' => rand(1, 100),
                'medication' => 'controlled_substance',
                'quantity' => 100,
                'frequency' => 'daily',
            ]);
        }

        $this->assertFraudAlertCreated(
            'doctor',
            $doctor->id,
            FraudType::PrescriptionFraud,
            FraudSeverity::Critical
        );
    }

    public function test_insurance_fraud(): void
    {
        $patientId = 1;
        
        // Simulate multiple insurance claims for same condition
        for ($i = 0; $i < 10; $i++) {
            $this->fraudControl->checkInsuranceFraud([
                'patient_id' => $patientId,
                'insurance_provider' => 'test_insurance',
                'diagnosis_code' => 'J00', // Same diagnosis
                'claim_amount' => 50000,
                'claim_date' => now()->subDays($i),
            ]);
        }

        $this->assertFraudAlertCreated(
            'patient',
            $patientId,
            FraudType::InsuranceFraud,
            FraudSeverity::Critical
        );
    }

    public function test_appointment_slot_hoarding(): void
    {
        $doctor = Doctor::factory()->create();
        $user = 1;

        // Simulate user booking multiple slots with same doctor
        for ($i = 0; $i < 10; $i++) {
            Appointment::create([
                'doctor_id' => $doctor->id,
                'user_id' => $user,
                'tenant_id' => 1,
                'scheduled_at' => now()->addDays($i),
                'status' => AppointmentStatus::Booked,
            ]);
        }

        $this->fraudControl->checkSlotHoarding($user, $doctor->id);

        $this->assertFraudAlertCreated(
            'user',
            $user,
            FraudType::ResourceHoarding,
            FraudSeverity::Medium
        );
    }

    public function test_medical_record_tampering(): void
    {
        $patientId = 1;

        // Simulate rapid medical record updates
        for ($i = 0; $i < 15; $i++) {
            $this->fraudControl->checkRecordTampering([
                'patient_id' => $patientId,
                'record_type' => 'diagnosis',
                'previous_value' => 'condition_a',
                'new_value' => 'condition_b',
                'updated_by' => 1,
                'update_interval_seconds' => 60,
            ]);
        }

        $this->assertFraudAlertCreated(
            'patient',
            $patientId,
            FraudType::DataTampering,
            FraudSeverity::Critical
        );
    }

    public function test_fake_doctor_reviews(): void
    {
        $doctor = Doctor::factory()->create();
        $deviceFingerprint = 'fp_medical_bot';

        for ($i = 0; $i < 25; $i++) {
            $this->fraudControl->checkReviewManipulation([
                'entity_type' => 'doctor',
                'entity_id' => $doctor->id,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'rating' => 5,
                'comment' => 'Excellent doctor!',
            ]);
        }

        $this->assertFraudAlertCreated(
            'doctor',
            $doctor->id,
            FraudType::FakeReviews,
            FraudSeverity::High
        );
    }
}

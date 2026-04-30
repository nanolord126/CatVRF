<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Contraindications\Domain\Entities\Contraindication;
use Modules\Contraindications\Domain\Entities\MedicalRecord;
use Illuminate\Support\Facades\Cache;

final class ContraindicationsFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_medical_records(): void
    {
        $userId = 1;

        for ($i = 0; $i < 20; $i++) {
            MedicalRecord::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'record_type' => 'allergy',
                'description' => 'Fake allergy',
                'verified_by' => null,
                'verified_at' => null,
            ]);
        }

        $this->fraudControl->checkRecordTampering([
            'user_id' => $userId,
            'record_count' => 20,
            'verified_percentage' => 0,
            'time_window_days' => 1,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::DataTampering,
            FraudSeverity::Critical
        );
    }

    public function test_contraindication_bypass(): void
    {
        $userId = 1;
        $productId = 100;
        $contraindicationId = 1;

        $this->fraudControl->checkContraindicationBypass([
            'user_id' => $userId,
            'product_id' => $productId,
            'contraindication_id' => $contraindicationId,
            'severity' => 'critical',
            'bypass_attempted' => true,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::SafetyViolation,
            FraudSeverity::Critical
        );
    }

    public function test_fake_allergy_claims(): void
    {
        $userId = 1;

        for ($i = 0; $i < 15; $i++) {
            Contraindication::create([
                'user_id' => $userId,
                'type' => 'allergy',
                'substance' => "allergen_{$i}",
                'severity' => 'severe',
                'verified' => false,
            ]);
        }

        $this->fraudControl->checkAllergyFraud([
            'user_id' => $userId,
            'allergy_count' => 15,
            'verified_count' => 0,
            'time_window_days' => 7,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::DataTampering,
            FraudSeverity::High
        );
    }

    public function test_medical_history_manipulation(): void
    {
        $userId = 1;

        $this->fraudControl->checkHistoryManipulation([
            'user_id' => $userId,
            'record_type' => 'chronic_condition',
            'previous_value' => null,
            'new_value' => 'diabetes',
            'verified' => false,
            'update_count' => 10,
            'time_window_hours' => 1,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::DataTampering,
            FraudSeverity::Critical
        );
    }

    public function test_prescription_interference(): void
    {
        $userId = 1;
        $prescriptionId = 1;

        $this->fraudControl->checkPrescriptionInterference([
            'user_id' => $userId,
            'prescription_id' => $prescriptionId,
            'contraindication_added' => true,
            'added_by_user' => true,
            'verified_by_doctor' => false,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::SafetyViolation,
            FraudSeverity::Critical
        );
    }
}

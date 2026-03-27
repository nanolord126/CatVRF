<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Medical;

use App\Domains\Medical\Models\MedicalClinic;
use App\Domains\Medical\Models\MedicalDoctor;
use App\Domains\Medical\Models\MedicalService;
use App\Domains\Medical\Services\MedicalService as DomainMedicalService;
use App\Domains\Medical\Services\AIMedicalTriageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * КАНОН 2026 — MEDICAL UNIT TESTS
 * Слой 8: Тестирование
 */
final class MedicalServiceTest extends TestCase
{
    use RefreshDatabase;

    private DomainMedicalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DomainMedicalService::class);
    }

    /** @test */
    public function it_can_create_a_valid_appointment(): void
    {
        $clinic = MedicalClinic::factory()->create(['tenant_id' => 1]);
        $doctor = MedicalDoctor::factory()->create(['clinic_id' => $clinic->id, 'tenant_id' => 1]);
        $medicalService = MedicalService::factory()->create(['clinic_id' => $clinic->id, 'tenant_id' => 1]);

        $appointment = $this->service->createAppointment([
            'tenant_id' => 1,
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctor->id,
            'service_id' => $medicalService->id,
            'client_id' => 999,
            'starts_at' => now()->addHour(),
            'total_amount_kopecks' => 500000,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $this->assertDatabaseHas('medical_appointments', [
            'id' => $appointment->id,
            'status' => 'pending',
            'client_id' => 999
        ]);
    }

    /** @test */
    public function it_can_perform_ai_triage(): void
    {
        $triageService = app(AIMedicalTriageService::class);
        $result = $triageService->analyzeSymptoms("У меня болит сердце и одышка", 1);

        $this->assertArrayHasKey('preliminary_diagnosis', $result);
        $this->assertArrayHasKey('recommended_doctor_specialization', $result);
        $this->assertArrayHasKey('urgency_score', $result);
    }
}

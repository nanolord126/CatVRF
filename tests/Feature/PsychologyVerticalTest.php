<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Tests;

use App\Domains\Medical\Psychology\Models\Psychologist;
use App\Domains\Medical\Psychology\Models\PsychologicalClinic;
use App\Domains\Medical\Psychology\Services\PsychologicalService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Тест реализации вертикали Психологии.
 */
final class PsychologyVerticalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // В 2026 тут должен быть сетап тенанта
    }

    public function test_can_register_psychologist(): void
    {
        $service = app(PsychologicalService::class);
        $correlationId = (string) Str::uuid();

        $clinic = PsychologicalClinic::create([
            'tenant_id' => 1,
            'name' => 'Test Clinic',
            'address' => 'Test Address',
            'is_verified' => true,
        ]);

        $psychologist = $service->registerPsychologist([
            'tenant_id' => 1,
            'clinic_id' => $clinic->id,
            'full_name' => 'Dr. Freud',
            'specialization' => ['Psychoanalysis'],
            'experience_years' => 20,
            'rating' => 5.0,
        ], $correlationId);

        $this->assertInstanceOf(Psychologist::class, $psychologist);
        $this->assertEquals('Dr. Freud', $psychologist->full_name);
        $this->assertDatabaseHas('psychologists', ['full_name' => 'Dr. Freud']);
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Tests;


use Carbon\Carbon;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyMaster as Master;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautySalon;
use App\Domains\Beauty\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BeautyDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_appointment_through_service(): void
    {
        // 1. Setup Data
        $tenantId = 1;
        $correlationId = (string) Str::uuid();

        $salon = BeautySalon::factory()->create([
            'tenant_id' => $tenantId,
            'name' => 'Test Salon',
        ]);

        $master = Master::factory()->create([
            'tenant_id' => $tenantId,
            'salon_id' => $salon->id,
            'full_name' => 'Master X',
        ]);

        /** @var AppointmentService $appointmentService */
        $appointmentService = $this->app->make(AppointmentService::class);

        // 2. Action
        $appointment = $appointmentService->createAppointment([
            'salon_id' => $salon->id,
            'master_id' => $master->id,
            'datetime_start' => Carbon::now()->addDay(),
            'price' => 500000, // 5000.00 руб
            'status' => 'pending',
            'correlation_id' => $correlationId,
        ]);

        // 3. Assertions
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'master_id' => $master->id,
            'price' => 500000,
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        $this->assertNotNull($appointment->uuid);
    }

    /**
     * Тест: корзина привязана к конкретному tenant.
     */
    public function test_appointment_belongs_to_correct_tenant(): void
    {
        $tenantId = 1;
        $correlationId = (string) Str::uuid();

        $salon = BeautySalon::factory()->create([
            'tenant_id' => $tenantId,
            'name' => 'Tenant-Scoped Salon',
        ]);

        $master = Master::factory()->create([
            'tenant_id' => $tenantId,
            'salon_id' => $salon->id,
            'full_name' => 'Master Y',
        ]);

        /** @var AppointmentService $appointmentService */
        $appointmentService = $this->app->make(AppointmentService::class);

        $appointment = $appointmentService->createAppointment([
            'salon_id' => $salon->id,
            'master_id' => $master->id,
            'datetime_start' => Carbon::now()->addDays(2),
            'price' => 300000,
            'status' => 'pending',
            'correlation_id' => $correlationId,
        ]);

        $this->assertEquals($tenantId, $appointment->tenant_id);
        $this->assertEquals($correlationId, $appointment->correlation_id);
    }
}

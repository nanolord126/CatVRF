<?php declare(strict_types=1);

namespace App\Domains\Beauty\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyDomainTest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use RefreshDatabase;

        public function test_can_create_appointment_through_service(): void
        {
            // 1. Setup Data
            $tenantId = 1;
            $correlationId = (string) Str::uuid();

            $salon = BeautySalon::factory()->create([
                'tenant_id' => $tenantId,
                'name' => 'Test Salon'
            ]);

            $master = Master::factory()->create([
                'tenant_id' => $tenantId,
                'salon_id' => $salon->id,
                'full_name' => 'Master X'
            ]);

            $service = AppointmentService::class;
            $appointmentService = app($service);

            // 2. Action
            $appointment = $appointmentService->createAppointment([
                'salon_id' => $salon->id,
                'master_id' => $master->id,
                'datetime_start' => now()->addDay(),
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
                'correlation_id' => $correlationId
            ]);

            $this->assertNotNull($appointment->uuid);
        }
}

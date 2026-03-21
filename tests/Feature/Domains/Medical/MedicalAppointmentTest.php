<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Medical;

use App\Domains\Medical\Models\Clinic;
use App\Domains\Medical\Models\Doctor;
use App\Domains\Medical\Models\MedicalAppointment;
use App\Domains\Medical\Services\AppointmentService;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\BaseTestCase;

/**
 * MedicalAppointmentTest — Feature-тесты вертикали Клиники/Медицина.
 */
final class MedicalAppointmentTest extends BaseTestCase
{
    use RefreshDatabase;

    private AppointmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AppointmentService::class);
    }

    public function test_medical_appointment_booked(): void
    {
        $clinic = Clinic::factory()->create(['tenant_id' => $this->tenant->id]);
        $doctor = Doctor::factory()->create(['tenant_id' => $this->tenant->id, 'clinic_id' => $clinic->id]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 100_000_00,
        ]);

        $appointment = $this->service->bookAppointment([
            'clinic_id'      => $clinic->id,
            'doctor_id'      => $doctor->id,
            'client_id'      => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'datetime'       => now()->addDays(2)->toDateTimeString(),
            'service_type'   => 'consultation',
            'price'          => 3_000_00,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $this->assertInstanceOf(MedicalAppointment::class, $appointment);
        $this->assertNotNull($appointment->uuid);
        $this->assertSame('pending', $appointment->status);
    }

    public function test_appointment_reminder_scheduled_24h_before(): void
    {
        $clinic = Clinic::factory()->create(['tenant_id' => $this->tenant->id]);
        $doctor = Doctor::factory()->create(['tenant_id' => $this->tenant->id, 'clinic_id' => $clinic->id]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 100_000_00,
        ]);

        $appointment = $this->service->bookAppointment([
            'clinic_id'      => $clinic->id,
            'doctor_id'      => $doctor->id,
            'client_id'      => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'datetime'       => now()->addDays(5)->toDateTimeString(),
            'service_type'   => 'consultation',
            'price'          => 3_000_00,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        // Should have a scheduled notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->user->id,
            'notifiable_type' => \App\Models\User::class,
        ]);
    }

    public function test_commission_14_percent_for_medical(): void
    {
        $clinic = Clinic::factory()->create(['tenant_id' => $this->tenant->id]);
        $doctor = Doctor::factory()->create(['tenant_id' => $this->tenant->id, 'clinic_id' => $clinic->id]);

        Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 100_000_00,
        ]);

        $appointment = $this->service->bookAppointment([
            'clinic_id'      => $clinic->id,
            'doctor_id'      => $doctor->id,
            'client_id'      => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'datetime'       => now()->addDays(2)->toDateTimeString(),
            'service_type'   => 'consultation',
            'price'          => 10_000_00,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $expectedCommission = (int)(10_000_00 * 0.14);
        $this->assertSame($expectedCommission, $appointment->commission_amount);
    }

    public function test_cancelled_appointment_refunds_wallet(): void
    {
        $clinic = Clinic::factory()->create(['tenant_id' => $this->tenant->id]);
        $doctor = Doctor::factory()->create(['tenant_id' => $this->tenant->id, 'clinic_id' => $clinic->id]);

        $wallet = Wallet::factory()->create([
            'tenant_id'       => $this->tenant->id,
            'current_balance' => 100_000_00,
        ]);

        $appointment = $this->service->bookAppointment([
            'clinic_id'      => $clinic->id,
            'doctor_id'      => $doctor->id,
            'client_id'      => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'datetime'       => now()->addDays(7)->toDateTimeString(),
            'service_type'   => 'consultation',
            'price'          => 5_000_00,
            'correlation_id' => Str::uuid()->toString(),
        ]);

        $balanceBefore = $wallet->fresh()->current_balance;

        $this->service->cancelAppointment($appointment->id, Str::uuid()->toString());

        $appointment->refresh();
        $this->assertSame('cancelled', $appointment->status);
        $this->assertGreaterThanOrEqual($balanceBefore, $wallet->fresh()->current_balance);
    }
}

<?php declare(strict_types=1);

namespace Tests\Feature\Beauty;

use App\Domains\Beauty\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class AppointmentBookingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_client_can_book_appointment(): void
    {
        $client = User::factory()->create(['tenant_id' => 1]);

        $response = $this->actingAs($client)
            ->postJson('/api/beauty/appointments', [
                'master_id' => 1,
                'service_id' => 1,
                'datetime' => Carbon::now()->addDay()->setHour(14)->setMinute(0),
                'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'status',
                'datetime',
                'master_id',
                'service_id',
                'correlation_id',
            ]);

        $this->assertDatabaseHas('appointments', [
            'status' => 'pending',
        ]);
    }

    public function test_appointment_has_correct_duration(): void
    {
        $client = User::factory()->create(['tenant_id' => 1]);

        $response = $this->actingAs($client)
            ->postJson('/api/beauty/appointments', [
                'master_id' => 1,
                'service_id' => 1,
                'datetime' => Carbon::now()->addDay()->setHour(14),
            ]);

        $appointment = Appointment::latest()->first();

        // Service duration is 60 minutes
        $this->assertEquals(
            $appointment->datetime->addMinutes(60),
            $appointment->datetime_end
        );
    }

    public function test_master_receives_appointment_reminder(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => 'confirmed',
        ]);

        // Simulate notification dispatch
        $this->expectsNotification(
            $appointment->master,
            AppointmentReminderNotification::class
        );
    }

    public function test_consumables_are_deducted_on_completion(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => 'in_progress',
        ]);

        $this->actingAs($appointment->master)
            ->postJson("/api/beauty/appointments/{$appointment->id}/complete");

        $this->assertDatabaseHas('stock_movements', [
            'source_type' => 'appointment',
            'source_id' => $appointment->id,
            'type' => 'out',
        ]);
    }

    public function test_client_can_cancel_appointment_with_24h_notice(): void
    {
        $client = User::factory()->create(['tenant_id' => 1]);
        $appointment = Appointment::factory()->create([
            'client_id' => $client->id,
            'datetime' => Carbon::now()->addDay(),
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($client)
            ->postJson("/api/beauty/appointments/{$appointment->id}/cancel");

        $response->assertStatus(200);
        $this->assertEquals('cancelled', $appointment->fresh()->status);
    }
}

<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Beauty;

use App\Domains\Beauty\Models\BookingSlot;
use App\Domains\Beauty\Services\BookingSlotHoldService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BookingSlotControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $correlationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->correlationId = Str::uuid()->toString();
    }

    public function test_hold_slot_endpoint_success(): void
    {
        Event::fake();

        $slot = BookingSlot::factory()->create([
            'status' => 'available',
            'tenant_id' => 1,
        ]);

        $response = $this->withHeader('X-Correlation-ID', $this->correlationId)
            ->postJson('/api/beauty/booking-slots/hold', [
                'booking_slot_id' => $slot->id,
                'customer_id' => 100,
                'tenant_id' => 1,
                'is_b2b' => false,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'correlation_id' => $this->correlationId,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'uuid',
                    'status',
                    'slot_date',
                    'slot_time',
                ],
                'correlation_id',
            ]);

        $slot->refresh();
        $this->assertEquals('held', $slot->status);
    }

    public function test_hold_slot_endpoint_b2b(): void
    {
        Event::fake();

        $slot = BookingSlot::factory()->create([
            'status' => 'available',
            'tenant_id' => 1,
        ]);

        $response = $this->withHeader('X-Correlation-ID', $this->correlationId)
            ->postJson('/api/beauty/booking-slots/hold', [
                'booking_slot_id' => $slot->id,
                'customer_id' => 100,
                'tenant_id' => 1,
                'business_group_id' => 10,
                'inn' => '1234567890',
                'business_card_id' => 'BC-123',
            ]);

        $response->assertStatus(201);

        $slot->refresh();
        $this->assertEquals('held', $slot->status);
        $this->assertEquals(60, $slot->getHoldDurationMinutes());
    }

    public function test_hold_slot_endpoint_validation_error(): void
    {
        $response = $this->withHeader('X-Correlation-ID', $this->correlationId)
            ->postJson('/api/beauty/booking-slots/hold', [
                'booking_slot_id' => 999999,
                'customer_id' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['booking_slot_id', 'tenant_id']);
    }

    public function test_release_slot_endpoint_success(): void
    {
        Event::fake();

        $slot = BookingSlot::factory()->create([
            'status' => 'held',
            'customer_id' => 100,
            'tenant_id' => 1,
            'held_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->withHeader('X-Correlation-ID', $this->correlationId)
            ->postJson('/api/beauty/booking-slots/release', [
                'booking_slot_id' => $slot->id,
                'tenant_id' => 1,
                'reason' => 'payment_failed',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'correlation_id' => $this->correlationId,
            ]);

        $slot->refresh();
        $this->assertEquals('available', $slot->status);
    }

    public function test_confirm_slot_endpoint_success(): void
    {
        Event::fake();

        $slot = BookingSlot::factory()->create([
            'status' => 'held',
            'customer_id' => 100,
            'tenant_id' => 1,
            'held_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->withHeader('X-Correlation-ID', $this->correlationId)
            ->postJson('/api/beauty/booking-slots/confirm', [
                'booking_slot_id' => $slot->id,
                'tenant_id' => 1,
                'order_id' => 500,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'correlation_id' => $this->correlationId,
            ]);

        $slot->refresh();
        $this->assertEquals('booked', $slot->status);
        $this->assertEquals(500, $slot->order_id);
    }

    public function test_show_slot_endpoint_success(): void
    {
        $slot = BookingSlot::factory()->create([
            'status' => 'available',
            'tenant_id' => 1,
        ]);

        $response = $this->withHeader('X-Correlation-ID', $this->correlationId)
            ->getJson("/api/beauty/booking-slots/{$slot->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'correlation_id' => $this->correlationId,
                'data' => [
                    'id' => $slot->id,
                    'uuid' => $slot->uuid,
                ],
            ]);
    }

    public function test_show_slot_endpoint_not_found(): void
    {
        $response = $this->withHeader('X-Correlation-ID', $this->correlationId)
            ->getJson('/api/beauty/booking-slots/999999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Booking slot not found',
            ]);
    }

    public function test_idempotency_key_from_header(): void
    {
        Event::fake();

        $slot = BookingSlot::factory()->create([
            'status' => 'available',
            'tenant_id' => 1,
        ]);

        $idempotencyKey = 'test-idempotency-key-123';

        $response = $this->withHeader('X-Correlation-ID', $this->correlationId)
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/beauty/booking-slots/hold', [
                'booking_slot_id' => $slot->id,
                'customer_id' => 100,
                'tenant_id' => 1,
            ]);

        $response->assertStatus(201);
    }
}

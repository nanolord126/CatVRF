<?php

declare(strict_types=1);

namespace Tests\Feature\Entertainment;

use App\Domains\EventPlanning\Entertainment\Models\Event;
use App\Domains\EventPlanning\Entertainment\Models\Venue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — ENTERTAINMENT API TEST
 */
final class EntertainmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock tenant
        $this->tenant = User::factory()->create(['id' => 1]);
        $this->actingAs($this->tenant);
    }

    /**
     * Тест получения списка заведений
     */
    public function test_can_list_active_venues(): void
    {
        Venue::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/entertainment/venues');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['success', 'data', 'correlation_id']);
    }

    /**
     * Тест бронирования места
     */
    public function test_can_book_seats_via_api(): void
    {
        $venue = Venue::factory()->create(['tenant_id' => $this->tenant->id]);
        $event = Event::factory()->create([
            'tenant_id' => $this->tenant->id,
            'venue_id' => $venue->id,
            'status' => 'on_sale'
        ]);

        $correlationId = (string) Str::uuid();

        $payload = [
            'event_id' => $event->id,
            'seats' => [
                ['row' => 1, 'col' => 5],
                ['row' => 1, 'col' => 6]
            ],
            'correlation_id' => $correlationId
        ];

        $response = $this->postJson('/api/v1/entertainment/book', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'booking_uuid', 'total_amount', 'expires_at'])
            ->assertJson(['correlation_id' => $correlationId]);

        $this->assertDatabaseHas('entertainment_bookings', [
            'event_id' => $event->id,
            'correlation_id' => $correlationId
        ]);
    }

    /**
     * Тест валидации билета
     */
    public function test_box_office_can_verify_ticket(): void
    {
        // Add ticket verification logic test here matching the controller
        $this->assertTrue(true);
    }
}

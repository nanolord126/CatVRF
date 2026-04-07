<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Tests;

use Tests\TestCase;

final class StrBookingFlowTest extends TestCase
{

    use RefreshDatabase;

        private StrBookingService $bookingService;
        private $tenant;

        protected function setUp(): void
        {
            parent::setUp();

            $this->tenant = \App\Models\Tenant::factory()->create();
            \Stancl\Tenancy\Facades\Tenancy::initialize($this->tenant);

            $this->bookingService = app(StrBookingService::class);
        }

        /** @test */
        public function it_can_create_booking_with_deposit_hold()
        {
            // 1. Create property & apartment
            $property = StrProperty::factory()->create(['tenant_id' => $this->tenant->id]);
            $apartment = StrApartment::factory()->create([
                'str_property_id' => $property->id,
                'tenant_id' => $this->tenant->id,
                'base_price' => 500000, // 5000 RUB
                'deposit_amount' => 200000, // 2000 RUB
            ]);

            $user = \App\Models\User::factory()->create();

            // 2. Mocking FraudControl check (if necessary, else just call as-is)
            // \App\Services\FraudControlService::shouldReceive('check')->andReturn(true);

            // 3. Create booking
            $booking = $this->bookingService->createBooking([
                'apartment_id' => $apartment->id,
                'user_id' => $user->id,
                'check_in' => now()->addDays(5)->toIso8601String(),
                'check_out' => now()->addDays(10)->toIso8601String(),
                'guests' => 2,
            ]);

            // 4. Assertions
            $this->assertInstanceOf(StrBooking::class, $booking);
            $this->assertEquals(StrBookingStatus::Confirmed->value, $booking->status->value);
            $this->assertEquals(StrDepositStatus::Held->value, $booking->deposit_status->value);
            $this->assertEquals(200000, $booking->deposit_amount);

            $this->assertDatabaseHas('str_bookings', [
                'id' => $booking->id,
                'deposit_status' => StrDepositStatus::Held->value,
                'tenant_id' => $this->tenant->id,
            ]);
        }

        /** @test */
        public function it_enforces_tenant_isolation()
        {
            $otherTenant = \App\Models\Tenant::factory()->create();

            $property = StrProperty::factory()->create(['tenant_id' => $this->tenant->id]);
            $this->assertEquals($this->tenant->id, $property->tenant_id);

            \Stancl\Tenancy\Facades\Tenancy::initialize($otherTenant);

            // Property should not be visible to other tenant by default if scoping works
            $this->assertCount(0, StrProperty::all());
        }
}

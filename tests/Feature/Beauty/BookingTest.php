<?php

namespace Tests\Feature\Beauty;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Beauty\Enums\BookingStatus;
use Modules\Beauty\Models\Booking;
use Modules\Beauty\Models\BeautySalon;
use Modules\Beauty\Models\Service;
use Modules\Beauty\Services\BookingService;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private BeautySalon $salon;
    private Service $service;
    private BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test data
        $this->salon = BeautySalon::factory()->create([
            'tenant_id' => 'test-tenant',
        ]);

        $this->service = Service::factory()->create([
            'salon_id' => $this->salon->id,
            'tenant_id' => 'test-tenant',
            'price' => 1500.00,
            'is_active' => true,
        ]);

        $this->bookingService = app(BookingService::class);
    }

    public function test_customer_can_create_booking(): void
    {
        $customerId = 1;
        $scheduledAt = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        $booking = $this->bookingService->createBooking(
            $this->service,
            $customerId,
            $scheduledAt->toDateTimeString(),
            'Боль в спине'
        );

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($customerId, $booking->customer_id);
        $this->assertEquals($this->service->id, $booking->service_id);
        $this->assertEquals(BookingStatus::PENDING, $booking->status);
        $this->assertNotNull($booking->correlation_id);

        $this->assertDatabaseHas('beauty_bookings', [
            'id' => $booking->id,
            'service_id' => $this->service->id,
            'customer_id' => $customerId,
            'status' => BookingStatus::PENDING->value,
        ]);
    }

    public function test_booking_cannot_be_created_for_inactive_service(): void
    {
        $this->service->update(['is_active' => false]);
        $customerId = 1;
        $scheduledAt = now()->addDays(2);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Услуга неактивна');

        $this->bookingService->createBooking(
            $this->service,
            $customerId,
            $scheduledAt->toDateTimeString()
        );
    }

    public function test_booking_cannot_be_created_for_past_date(): void
    {
        $customerId = 1;
        $scheduledAt = now()->subDays(1);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Дата бронирования не может быть в прошлом');

        $this->bookingService->createBooking(
            $this->service,
            $customerId,
            $scheduledAt->toDateTimeString()
        );
    }

    public function test_booking_status_transitions(): void
    {
        $customerId = 1;
        $scheduledAt = now()->addDays(2);

        $booking = $this->bookingService->createBooking(
            $this->service,
            $customerId,
            $scheduledAt->toDateTimeString()
        );

        // PENDING -> CONFIRMED
        $booking = $this->bookingService->confirmBooking($booking);
        $this->assertEquals(BookingStatus::CONFIRMED, $booking->status);

        // CONFIRMED -> COMPLETED
        $booking = $this->bookingService->completeBooking($booking);
        $this->assertEquals(BookingStatus::COMPLETED, $booking->status);
    }

    public function test_booking_can_be_cancelled(): void
    {
        $customerId = 1;
        $scheduledAt = now()->addDays(2);

        $booking = $this->bookingService->createBooking(
            $this->service,
            $customerId,
            $scheduledAt->toDateTimeString()
        );

        $booking = $this->bookingService->cancelBooking($booking, 'Пациент отменил');
        $this->assertEquals(BookingStatus::CANCELLED, $booking->status);
        $this->assertStringContainsString('Пациент отменил', $booking->notes);
    }

    public function test_completed_booking_cannot_be_cancelled(): void
    {
        $customerId = 1;
        $scheduledAt = now()->addDays(2);

        $booking = $this->bookingService->createBooking(
            $this->service,
            $customerId,
            $scheduledAt->toDateTimeString()
        );

        $booking = $this->bookingService->confirmBooking($booking);
        $booking = $this->bookingService->completeBooking($booking);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Завершённое бронирование не может быть отменено');

        $this->bookingService->cancelBooking($booking);
    }

    public function test_upcoming_scope_returns_future_bookings(): void
    {
        $customerId = 1;
        $futureDate = now()->addDays(2);
        $pastDate = now()->subDays(1);

        // Create future booking
        $futureBooking = Booking::factory()->create([
            'service_id' => $this->service->id,
            'salon_id' => $this->salon->id,
            'scheduled_at' => $futureDate,
            'status' => BookingStatus::CONFIRMED,
            'tenant_id' => 'test-tenant',
        ]);

        $upcomingBookings = Booking::forTenant('test-tenant')->upcoming()->get();

        $this->assertTrue($upcomingBookings->contains($futureBooking));
    }
}

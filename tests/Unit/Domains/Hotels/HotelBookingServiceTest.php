<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Hotels;

use App\Domains\Hotels\Services\HotelBookingService;
use App\Domains\Hotels\DTOs\CreateBookingDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HotelBookingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_booking_successfully(): void
    {
        $dto = new CreateBookingDto(
            hotelId: 1,
            roomId: 1,
            userId: 1,
            checkIn: now()->addDay(),
            checkOut: now()->addDays(3),
            guests: 2,
            specialRequests: null,
            tenantId: 1,
            isB2B: false,
            correlationId: 'test-correlation',
        );

        $service = app(HotelBookingService::class);
        $booking = $service->createBooking($dto);

        $this->assertNotNull($booking);
        $this->assertEquals(1, $booking->user_id);
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_calculate_total_price(): void
    {
        $service = app(HotelBookingService::class);
        $total = $service->calculateTotalPrice(
            hotelId: 1,
            roomId: 1,
            checkIn: now()->addDay(),
            checkOut: now()->addDays(3),
            guestCount: 2,
            tenantId: 1,
            correlationId: 'test-correlation',
        );

        $this->assertGreaterThan(0, $total);
    }

    public function test_check_availability(): void
    {
        $service = app(HotelBookingService::class);
        $available = $service->checkAvailability(
            hotelId: 1,
            roomId: 1,
            checkIn: now()->addDay(),
            checkOut: now()->addDays(3),
            tenantId: 1,
            correlationId: 'test-correlation',
        );

        $this->assertIsBool($available);
    }
}
